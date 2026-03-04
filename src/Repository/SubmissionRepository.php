<?php

declare(strict_types=1);

final class SubmissionRepository
{
    public function __construct(private PDO $pdo)
    {
        $this->ensureAdminPasswordSchema();
    }

    private function ensureAdminPasswordSchema(): void
    {
        $columnsStmt = $this->pdo->query('PRAGMA table_info(admin_users)');
        $columns = $columnsStmt->fetchAll();
        $hasPasswordHash = false;

        foreach ($columns as $column) {
            if ((string)($column['name'] ?? '') === 'password_hash') {
                $hasPasswordHash = true;
                break;
            }
        }

        if (!$hasPasswordHash) {
            $this->pdo->exec('ALTER TABLE admin_users ADD COLUMN password_hash TEXT NULL');
        }
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

    /** @return array<int, array<string, mixed>> */
    public function latest(int $limit = 10): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT s.id, s.severity_auto, s.submitter_name, s.created_at, sw.id AS software_id, sw.name AS software_name
             FROM submissions s
             JOIN software sw ON sw.id = s.software_id
             WHERE s.is_hidden = 0 AND sw.is_hidden = 0
             ORDER BY s.id DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function softwareDirectory(string $search = ''): array
    {
        $sql = "SELECT sw.id, sw.name, sw.slug, sw.type, sw.canonical_url, sw.description, sw.plugin_icon_url,
                       COUNT(s.id) AS report_count,
                       MAX(s.created_at) AS last_report_at,
                       SUM(CASE WHEN COALESCE(s.severity_admin_override, s.severity_auto) = 'high' THEN 1 ELSE 0 END) AS high_count,
                       SUM(CASE WHEN COALESCE(s.severity_admin_override, s.severity_auto) = 'medium' THEN 1 ELSE 0 END) AS medium_count,
                       SUM(CASE WHEN COALESCE(s.severity_admin_override, s.severity_auto) = 'low' THEN 1 ELSE 0 END) AS low_count,
                       CASE
                           WHEN SUM(CASE WHEN COALESCE(s.severity_admin_override, s.severity_auto) = 'high' THEN 1 ELSE 0 END) > 0 THEN 'high'
                           WHEN SUM(CASE WHEN COALESCE(s.severity_admin_override, s.severity_auto) = 'medium' THEN 1 ELSE 0 END) > 0 THEN 'medium'
                           WHEN SUM(CASE WHEN COALESCE(s.severity_admin_override, s.severity_auto) = 'low' THEN 1 ELSE 0 END) > 0 THEN 'low'
                           ELSE 'none'
                       END AS overall_severity
                FROM software sw
                LEFT JOIN submissions s ON s.software_id = sw.id AND s.is_hidden = 0
                WHERE sw.is_hidden = 0";

        $params = [];
        if ($search !== '') {
            $sql .= ' AND LOWER(sw.name) LIKE LOWER(:search)';
            $params[':search'] = '%' . $search . '%';
        }

        $sql .= ' GROUP BY sw.id ORDER BY last_report_at DESC, sw.name ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findSoftware(int $softwareId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT sw.id, sw.name, sw.type, sw.slug, sw.canonical_url, sw.description, sw.plugin_icon_url, sw.plugin_banner_url,
                    CASE
                        WHEN SUM(CASE WHEN COALESCE(s.severity_admin_override, s.severity_auto) = 'high' THEN 1 ELSE 0 END) > 0 THEN 'high'
                        WHEN SUM(CASE WHEN COALESCE(s.severity_admin_override, s.severity_auto) = 'medium' THEN 1 ELSE 0 END) > 0 THEN 'medium'
                        WHEN SUM(CASE WHEN COALESCE(s.severity_admin_override, s.severity_auto) = 'low' THEN 1 ELSE 0 END) > 0 THEN 'low'
                        ELSE 'none'
                    END AS overall_severity
             FROM software sw
             LEFT JOIN submissions s ON s.software_id = sw.id AND s.is_hidden = 0
             WHERE sw.id = :id AND sw.is_hidden = 0
             GROUP BY sw.id
             LIMIT 1"
        );
        $stmt->execute([':id' => $softwareId]);
        $software = $stmt->fetch();

        return $software === false ? null : $software;
    }

    /** @return array<int, array<string, mixed>> */
    public function softwareSubmissions(int $softwareId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, wordpress_version, submitter_name, submitter_role, submission_comment,
                    severity_auto, severity_admin_override,
                    COALESCE(severity_admin_override, severity_auto) AS severity_resolved,
                    created_at
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
            'SELECT id, author_name, author_role, comment, is_admin_solution, created_at
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
                    s.severity_auto, s.severity_admin_override,
                    COALESCE(s.severity_admin_override, s.severity_auto) AS severity_resolved,
                    s.created_at, sw.name AS software_name, sw.canonical_url AS software_url
             FROM submissions s
             JOIN software sw ON sw.id = s.software_id
             WHERE s.id = :id AND s.is_hidden = 0 AND sw.is_hidden = 0
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
            'SELECT id, author_name, author_role, comment, created_at
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

    public function hideSubmission(int $submissionId): bool
    {
        $stmt = $this->pdo->prepare('UPDATE submissions SET is_hidden = 1 WHERE id = :id');
        $stmt->execute([':id' => $submissionId]);

        return $stmt->rowCount() > 0;
    }

    public function hideCustomSoftware(int $softwareId): bool
    {
        $this->pdo->beginTransaction();

        try {
            $softwareStmt = $this->pdo->prepare("UPDATE software SET is_hidden = 1 WHERE id = :id AND type = 'other'");
            $softwareStmt->execute([':id' => $softwareId]);

            if ($softwareStmt->rowCount() === 0) {
                $this->pdo->rollBack();
                return false;
            }

            $submissionStmt = $this->pdo->prepare('UPDATE submissions SET is_hidden = 1 WHERE software_id = :software_id');
            $submissionStmt->execute([':software_id' => $softwareId]);

            $softwareCommentStmt = $this->pdo->prepare('UPDATE plugin_comments SET is_hidden = 1 WHERE software_id = :software_id');
            $softwareCommentStmt->execute([':software_id' => $softwareId]);

            $reportCommentStmt = $this->pdo->prepare(
                'UPDATE submission_comments
                 SET is_hidden = 1
                 WHERE submission_id IN (
                    SELECT id FROM submissions WHERE software_id = :software_id
                 )'
            );
            $reportCommentStmt->execute([':software_id' => $softwareId]);

            $this->pdo->commit();

            return true;
        } catch (Throwable) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            return false;
        }
    }

    public function setSubmissionSeverityOverride(int $submissionId, ?string $severity): bool
    {
        $stmt = $this->pdo->prepare('UPDATE submissions SET severity_admin_override = :severity WHERE id = :id');
        $stmt->bindValue(':id', $submissionId, PDO::PARAM_INT);
        if ($severity === null) {
            $stmt->bindValue(':severity', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':severity', $severity, PDO::PARAM_STR);
        }

        return $stmt->execute();
    }

    public function hideSoftwareComment(int $commentId): bool
    {
        $stmt = $this->pdo->prepare('UPDATE plugin_comments SET is_hidden = 1 WHERE id = :id');
        $stmt->execute([':id' => $commentId]);

        return $stmt->rowCount() > 0;
    }

    public function hideReportComment(int $commentId): bool
    {
        $stmt = $this->pdo->prepare('UPDATE submission_comments SET is_hidden = 1 WHERE id = :id');
        $stmt->execute([':id' => $commentId]);

        return $stmt->rowCount() > 0;
    }

    public function addAdminSoftwareSolutionComment(int $softwareId, string $authorName, string $comment): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO plugin_comments (software_id, author_name, author_role, comment, is_admin_solution, is_hidden, created_at)
             VALUES (:software_id, :author_name, 'admin', :comment, 1, 0, :created_at)"
        );
        $stmt->execute([
            ':software_id' => $softwareId,
            ':author_name' => $authorName,
            ':comment' => $comment,
            ':created_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);
    }

    /** @return array{id: int, name: string, email: string}|null */
    public function verifyAdminCredentials(string $email, string $password): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, name, email, password_hash
             FROM admin_users
             WHERE email = :email AND is_active = 1
             LIMIT 1'
        );
        $stmt->execute([':email' => strtolower($email)]);
        $admin = $stmt->fetch();
        if ($admin === false) {
            return null;
        }

        $passwordHash = trim((string)($admin['password_hash'] ?? ''));
        if ($passwordHash === '' || !password_verify($password, $passwordHash)) {
            return null;
        }

        if (password_needs_rehash($passwordHash, PASSWORD_DEFAULT)) {
            $rehashStmt = $this->pdo->prepare('UPDATE admin_users SET password_hash = :password_hash WHERE id = :id');
            $rehashStmt->execute([
                ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
                ':id' => (int)$admin['id'],
            ]);
        }

        return [
            'id' => (int)$admin['id'],
            'name' => (string)$admin['name'],
            'email' => (string)$admin['email'],
        ];
    }

    public function adminUserIsActive(int $adminId): bool
    {
        $stmt = $this->pdo->prepare('SELECT id FROM admin_users WHERE id = :id AND is_active = 1 LIMIT 1');
        $stmt->execute([':id' => $adminId]);

        return $stmt->fetch() !== false;
    }

    public function activeAdminCount(): int
    {
        $stmt = $this->pdo->query('SELECT COUNT(id) AS count_all FROM admin_users WHERE is_active = 1');
        $result = $stmt->fetch();

        return (int)($result['count_all'] ?? 0);
    }

    /** @return array<int, array<string, mixed>> */
    public function adminUsers(): array
    {
        $stmt = $this->pdo->query('SELECT id, name, email, is_active, created_at FROM admin_users ORDER BY is_active DESC, id ASC');

        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function adminUserById(int $adminId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, name, email, is_active, created_at
             FROM admin_users
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $adminId]);
        $admin = $stmt->fetch();

        return $admin === false ? null : $admin;
    }

    public function addAdminUser(string $name, string $email, string $password): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO admin_users (name, email, admin_token, password_hash, is_active, created_at)
             VALUES (:name, :email, :token, :password_hash, 1, :created_at)'
        );

        $emailLower = strtolower($email);
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $createdAt = (new DateTimeImmutable())->format('Y-m-d H:i:s');

        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                return $stmt->execute([
                    ':name' => $name,
                    ':email' => $emailLower,
                    ':token' => $this->generateOpaqueAdminToken(),
                    ':password_hash' => $passwordHash,
                    ':created_at' => $createdAt,
                ]);
            } catch (PDOException $exception) {
                $message = strtolower($exception->getMessage());
                if (str_contains($message, 'admin_users.admin_token')) {
                    continue;
                }

                return false;
            }
        }

        return false;
    }

    public function updateAdminPassword(int $adminId, string $password): bool
    {
        $stmt = $this->pdo->prepare('UPDATE admin_users SET password_hash = :password_hash WHERE id = :id');

        return $stmt->execute([
            ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ':id' => $adminId,
        ]) && $stmt->rowCount() > 0;
    }

    public function setAdminUserActive(int $adminId, bool $isActive): bool
    {
        $stmt = $this->pdo->prepare('UPDATE admin_users SET is_active = :is_active WHERE id = :id');

        return $stmt->execute([
            ':is_active' => $isActive ? 1 : 0,
            ':id' => $adminId,
        ]) && $stmt->rowCount() > 0;
    }

    private function generateOpaqueAdminToken(): string
    {
        return bin2hex(random_bytes(32));
    }
}
