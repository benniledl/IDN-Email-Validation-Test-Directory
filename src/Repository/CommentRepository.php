<?php

declare(strict_types=1);

final class CommentRepository
{
    public function __construct(private PDO $pdo)
    {
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
}
