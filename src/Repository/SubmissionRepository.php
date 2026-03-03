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
        $stmt = $this->pdo->prepare('SELECT id FROM software WHERE name = :name AND software_url = :software_url LIMIT 1');
        $stmt->execute([
            ':name' => $software['name'],
            ':software_url' => $software['software_url'],
        ]);

        $existing = $stmt->fetch();
        if ($existing !== false) {
            return (int)$existing['id'];
        }

        $insert = $this->pdo->prepare(
            'INSERT INTO software (name, software_url, software_type, description, created_at, updated_at)
             VALUES (:name, :software_url, :software_type, :description, :created_at, :updated_at)'
        );

        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $insert->execute([
            ':name' => $software['name'],
            ':software_url' => $software['software_url'],
            ':software_type' => $software['software_type'],
            ':description' => $software['description'],
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * @param array<int, array<string, mixed>> $tests
     */
    public function createSubmission(int $softwareId, array $payload, array $tests, string $severity): void
    {
        $this->pdo->beginTransaction();

        try {
            $createdAt = (new DateTimeImmutable())->format('Y-m-d H:i:s');

            $submissionStmt = $this->pdo->prepare(
                'INSERT INTO submissions
                 (software_id, submitter_name, submitter_email, submitter_role, submission_comment, severity_auto, is_hidden, created_at)
                 VALUES (:software_id, :submitter_name, :submitter_email, :submitter_role, :submission_comment, :severity_auto, 0, :created_at)'
            );

            $submissionStmt->execute([
                ':software_id' => $softwareId,
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
                 (submission_id, template_email_id, email_address, expected_valid, actual_result, failure_detected, severity_level, created_at)
                 VALUES (:submission_id, :template_email_id, :email_address, :expected_valid, :actual_result, :failure_detected, :severity_level, :created_at)'
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
                    ':severity_level' => $test['severity_level'],
                    ':created_at' => $createdAt,
                ]);
            }

            $this->pdo->commit();
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    /** @return array<int, array<string, mixed>> */
    public function latest(int $limit = 10): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT s.id, s.severity_auto, s.submitter_name, s.created_at, sw.name AS software_name
             FROM submissions s
             JOIN software sw ON sw.id = s.software_id
             WHERE s.is_hidden = 0
             ORDER BY s.id DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
