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
            'INSERT INTO software (type, slug, canonical_url, name, description, plugin_icon_url, plugin_banner_url, created_at, updated_at)
             VALUES (:type, :slug, :canonical_url, :name, :description, :plugin_icon_url, :plugin_banner_url, :created_at, :updated_at)'
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

    /** @return array<int, array<string, mixed>> */
    public function latest(int $limit = 10): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT s.id, s.severity_auto, s.submitter_name, s.created_at, sw.id AS software_id, sw.name AS software_name
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

    /** @return array<int, array<string, mixed>> */
    public function softwareDirectory(): array
    {
        return $this->pdo->query(
            "SELECT sw.id, sw.name, sw.slug, sw.type, sw.canonical_url, sw.description, sw.plugin_icon_url,
                    COUNT(s.id) AS report_count,
                    MAX(s.created_at) AS last_report_at,
                    SUM(CASE WHEN s.severity_auto = 'high' THEN 1 ELSE 0 END) AS high_count,
                    SUM(CASE WHEN s.severity_auto = 'medium' THEN 1 ELSE 0 END) AS medium_count,
                    SUM(CASE WHEN s.severity_auto = 'low' THEN 1 ELSE 0 END) AS low_count
             FROM software sw
             LEFT JOIN submissions s ON s.software_id = sw.id AND s.is_hidden = 0
             GROUP BY sw.id
             ORDER BY last_report_at DESC, sw.name ASC"
        )->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findSoftware(int $softwareId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, name, type, canonical_url, description, plugin_icon_url, plugin_banner_url
             FROM software
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $softwareId]);
        $software = $stmt->fetch();

        return $software === false ? null : $software;
    }

    /** @return array<int, array<string, mixed>> */
    public function softwareSubmissions(int $softwareId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, wordpress_version, submitter_name, submitter_role, submission_comment, severity_auto, created_at
             FROM submissions
             WHERE software_id = :software_id AND is_hidden = 0
             ORDER BY id DESC'
        );
        $stmt->execute([':software_id' => $softwareId]);

        return $stmt->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function softwareComments(int $softwareId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT author_name, author_role, comment, is_admin_solution, created_at
             FROM plugin_comments
             WHERE software_id = :software_id AND is_hidden = 0
             ORDER BY id DESC'
        );
        $stmt->execute([':software_id' => $softwareId]);

        return $stmt->fetchAll();
    }

    public function addSoftwareComment(int $softwareId, string $authorName, string $comment): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO plugin_comments (software_id, author_name, author_role, comment, is_admin_solution, is_hidden, created_at)
             VALUES (:software_id, :author_name, 'user', :comment, 0, 0, :created_at)"
        );
        $stmt->execute([
            ':software_id' => $softwareId,
            ':author_name' => $authorName,
            ':comment' => $comment,
            ':created_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);
    }

    /** @return array<string, mixed>|null */
    public function findReport(int $submissionId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT s.id, s.software_id, s.wordpress_version, s.submitter_name, s.submitter_role, s.submission_comment,
                    s.severity_auto, s.created_at, sw.name AS software_name, sw.canonical_url AS software_url
             FROM submissions s
             JOIN software sw ON sw.id = s.software_id
             WHERE s.id = :id AND s.is_hidden = 0
             LIMIT 1'
        );
        $stmt->execute([':id' => $submissionId]);
        $report = $stmt->fetch();

        return $report === false ? null : $report;
    }

    /** @return array<int, array<string, mixed>> */
    public function reportTests(int $submissionId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT email_address, expected_valid, actual_result, failure_detected, severity_weight
             FROM submission_tests
             WHERE submission_id = :submission_id
             ORDER BY id ASC'
        );
        $stmt->execute([':submission_id' => $submissionId]);

        return $stmt->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function reportComments(int $submissionId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT author_name, author_role, comment, created_at
             FROM submission_comments
             WHERE submission_id = :submission_id AND is_hidden = 0
             ORDER BY id DESC'
        );
        $stmt->execute([':submission_id' => $submissionId]);

        return $stmt->fetchAll();
    }

    public function addReportComment(int $submissionId, string $authorName, string $comment): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO submission_comments (submission_id, author_name, author_role, comment, is_hidden, created_at)
             VALUES (:submission_id, :author_name, 'user', :comment, 0, :created_at)"
        );
        $stmt->execute([
            ':submission_id' => $submissionId,
            ':author_name' => $authorName,
            ':comment' => $comment,
            ':created_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);
    }
}
