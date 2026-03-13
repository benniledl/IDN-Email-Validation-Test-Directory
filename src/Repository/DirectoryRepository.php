<?php

declare(strict_types=1);

final class DirectoryRepository
{
    public function __construct(private PDO $pdo)
    {
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
            $sql .= ' AND (
                        LOWER(sw.name) LIKE LOWER(:search_name)
                        OR EXISTS (
                            SELECT 1
                            FROM submissions sx
                            WHERE sx.software_id = sw.id
                              AND sx.is_hidden = 0
                              AND LOWER(sx.submitter_name) LIKE LOWER(:search_submitter)
                        )
                    )';
            $params[':search_name'] = '%' . $search . '%';
            $params[':search_submitter'] = '%' . $search . '%';
        }

        $sql .= ' GROUP BY sw.id ORDER BY last_report_at DESC, sw.name ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function reportsDirectory(string $search = '', string $severity = '', int $limit = 80): array
    {
        $sql = 'SELECT s.id, s.submitter_name, s.wordpress_version, s.created_at,
                       COALESCE(s.severity_admin_override, s.severity_auto) AS severity_resolved,
                       sw.id AS software_id, sw.name AS software_name
                FROM submissions s
                JOIN software sw ON sw.id = s.software_id
                WHERE s.is_hidden = 0 AND sw.is_hidden = 0';

        $params = [];

        $search = trim($search);
        if ($search !== '') {
            $sql .= ' AND (
                        LOWER(sw.name) LIKE LOWER(:search)
                        OR LOWER(s.submitter_name) LIKE LOWER(:search)
                     )';
            $params[':search'] = '%' . $search . '%';
        }

        $severity = trim($severity);
        if ($severity !== '' && in_array($severity, ['none', 'low', 'medium', 'high'], true)) {
            $sql .= ' AND COALESCE(s.severity_admin_override, s.severity_auto) = :severity';
            $params[':severity'] = $severity;
        }

        $sql .= ' ORDER BY s.id DESC LIMIT :limit';

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /** @return array<int, array{slug: string, name: string}> */
    public function wpPluginSlugSuggestions(string $query, int $limit = 8): array
    {
        $needle = strtolower(trim($query));
        if ($needle === '' || strlen($needle) < 4) {
            return [];
        }

        $safeNeedle = preg_replace('/[^a-z0-9-]/', '', $needle);
        if (!is_string($safeNeedle) || $safeNeedle === '') {
            return [];
        }

        $stmt = $this->pdo->prepare(
            "SELECT slug, name
             FROM software
             WHERE type = 'wp_plugin'
               AND is_hidden = 0
               AND slug IS NOT NULL
               AND slug <> ''
               AND LOWER(slug) LIKE LOWER(:needle)
             ORDER BY updated_at DESC
             LIMIT :limit"
        );

        $stmt->bindValue(':needle', $safeNeedle . '%', PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll();
        $result = [];

        foreach ($rows as $row) {
            $slug = strtolower(trim((string)($row['slug'] ?? '')));
            if ($slug === '') {
                continue;
            }

            $name = trim((string)($row['name'] ?? ''));
            if ($name === '') {
                $name = $slug;
            }

            $result[] = [
                'slug' => $slug,
                'name' => $name,
            ];
        }

        return $result;
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
}
