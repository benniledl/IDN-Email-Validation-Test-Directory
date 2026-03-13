<?php

declare(strict_types=1);

final class SubmissionRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @param array<string, mixed> $software */
    public function findOrCreateSoftware(array $software): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT id FROM software
             WHERE type = :type
             AND ((:slug IS NOT NULL AND slug = :slug) OR (:slug IS NULL AND canonical_url = :canonical_url))
             LIMIT 1'
        );
        $stmt->execute([
            ':type' => $software['type'],
            ':slug' => $software['slug'],
            ':canonical_url' => $software['canonical_url'],
        ]);

        $existing = $stmt->fetch();
        if ($existing !== false) {
            $update = $this->pdo->prepare(
                'UPDATE software
                 SET canonical_url = :canonical_url,
                     name = :name,
                     description = :description,
                     plugin_icon_url = :plugin_icon_url,
                     plugin_banner_url = :plugin_banner_url,
                     updated_at = :updated_at
                 WHERE id = :id'
            );
            $update->execute([
                ':id' => (int)$existing['id'],
                ':canonical_url' => $software['canonical_url'],
                ':name' => $software['name'],
                ':description' => $software['description'] ?: null,
                ':plugin_icon_url' => $software['plugin_icon_url'] ?? null,
                ':plugin_banner_url' => $software['plugin_banner_url'] ?? null,
                ':updated_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            ]);

            return (int)$existing['id'];
        }

        $insert = $this->pdo->prepare(
            'INSERT INTO software (type, slug, canonical_url, name, description, plugin_icon_url, plugin_banner_url, is_hidden, created_at, updated_at)
             VALUES (:type, :slug, :canonical_url, :name, :description, :plugin_icon_url, :plugin_banner_url, 0, :created_at, :updated_at)'
        );

        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $insert->execute([
            ':type' => $software['type'],
            ':slug' => $software['slug'],
            ':canonical_url' => $software['canonical_url'],
            ':name' => $software['name'],
            ':description' => $software['description'] ?: null,
            ':plugin_icon_url' => $software['plugin_icon_url'] ?? null,
            ':plugin_banner_url' => $software['plugin_banner_url'] ?? null,
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * @param array<int, array<string, mixed>> $tests
     */
    public function createSubmission(int $softwareId, array $payload, array $tests, string $severity): int
    {
        $this->pdo->beginTransaction();

        try {
            $createdAt = (new DateTimeImmutable())->format('Y-m-d H:i:s');

            $submissionStmt = $this->pdo->prepare(
                'INSERT INTO submissions
                 (software_id, wordpress_version, submitter_name, submitter_email, submitter_role, submission_comment, severity_auto, is_hidden, created_at)
                 VALUES (:software_id, :wordpress_version, :submitter_name, :submitter_email, :submitter_role, :submission_comment, :severity_auto, 0, :created_at)'
            );

            $submissionStmt->execute([
                ':software_id' => $softwareId,
                ':wordpress_version' => $payload['wordpress_version'] ?: null,
                ':submitter_name' => $payload['submitter_name'],
                ':submitter_email' => $payload['submitter_email'],
                ':submitter_role' => $payload['submitter_role'] ?: null,
                ':submission_comment' => $payload['submission_comment'] ?: null,
                ':severity_auto' => $severity,
                ':created_at' => $createdAt,
            ]);

            $submissionId = (int)$this->pdo->lastInsertId();

            $testStmt = $this->pdo->prepare(
                'INSERT INTO submission_tests
                 (submission_id, template_email_id, email_address, expected_valid, actual_result, failure_detected, severity_weight, created_at)
                 VALUES (:submission_id, :template_email_id, :email_address, :expected_valid, :actual_result, :failure_detected, :severity_weight, :created_at)'
            );

            foreach ($tests as $test) {
                $expectedValid = (int)$test['expected_valid'] === 1;
                $actualAccepted = $test['actual_result'] === 'accepted';
                $failure = $expectedValid !== $actualAccepted;

                $testStmt->execute([
                    ':submission_id' => $submissionId,
                    ':template_email_id' => $test['template_id'],
                    ':email_address' => $test['email_address'],
                    ':expected_valid' => $test['expected_valid'],
                    ':actual_result' => $test['actual_result'],
                    ':failure_detected' => $failure ? 1 : 0,
                    ':severity_weight' => $test['severity_weight'],
                    ':created_at' => $createdAt,
                ]);
            }

            $this->pdo->commit();

            return $submissionId;
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }
}
