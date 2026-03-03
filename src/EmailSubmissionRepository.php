<?php

declare(strict_types=1);

final class EmailSubmissionRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function save(string $emailInput, string $normalizedEmail, bool $isValid): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO email_submissions (email_input, normalized_email, is_valid, created_at)
             VALUES (:email_input, :normalized_email, :is_valid, :created_at)'
        );

        $statement->execute([
            ':email_input' => $emailInput,
            ':normalized_email' => $normalizedEmail,
            ':is_valid' => $isValid ? 1 : 0,
            ':created_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function latest(int $limit = 10): array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, email_input, normalized_email, is_valid, created_at
             FROM email_submissions
             ORDER BY id DESC
             LIMIT :limit'
        );

        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }
}
