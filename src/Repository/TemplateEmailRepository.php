<?php

declare(strict_types=1);

final class TemplateEmailRepository
{
    /**
     * @var array<int, array{0: int, 1: string, 2: int, 3: int}>
     */
    private const DEFAULT_TEMPLATES = [
        [1, 'max@müller.de', 1, 3],
        [3, 'büro@test.de', 1, 3],
        [4, 'max@info.versicherung', 1, 3],
        [5, 'max@newsletter.müller.de', 1, 2],
        [7, 'max@例子.广告', 1, 1],
    ];

    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        $this->syncDefaults();

        $rows = $this->pdo
            ->query('SELECT id, email_address, expected_valid, severity_weight FROM template_emails ORDER BY id')
            ->fetchAll();

        $allowedIds = [];
        foreach (self::DEFAULT_TEMPLATES as [$id]) {
            $allowedIds[(int)$id] = true;
        }

        $filtered = [];
        foreach ($rows as $row) {
            $id = (int)($row['id'] ?? 0);
            if (isset($allowedIds[$id])) {
                $filtered[] = $row;
            }
        }

        return $filtered;
    }

    private function syncDefaults(): void
    {
        $selectStmt = $this->pdo->prepare(
            'SELECT id, email_address, expected_valid, severity_weight
             FROM template_emails
             WHERE id = :id
             LIMIT 1'
        );
        $updateStmt = $this->pdo->prepare(
            'UPDATE template_emails
             SET email_address = :email_address,
                 expected_valid = :expected_valid,
                 severity_weight = :severity_weight
             WHERE id = :id'
        );
        $insertStmt = $this->pdo->prepare(
            'INSERT INTO template_emails (id, email_address, expected_valid, severity_weight, created_at)
             VALUES (:id, :email_address, :expected_valid, :severity_weight, :created_at)'
        );

        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');

        $this->pdo->beginTransaction();

        try {
            foreach (self::DEFAULT_TEMPLATES as [$id, $email, $expectedValid, $severityWeight]) {
                $selectStmt->execute([':id' => $id]);
                $existing = $selectStmt->fetch();
                if ($existing !== false) {
                    if (
                        (string)($existing['email_address'] ?? '') !== $email
                        || (int)($existing['expected_valid'] ?? -1) !== $expectedValid
                        || (int)($existing['severity_weight'] ?? -1) !== $severityWeight
                    ) {
                        $updateStmt->execute([
                            ':id' => $id,
                            ':email_address' => $email,
                            ':expected_valid' => $expectedValid,
                            ':severity_weight' => $severityWeight,
                        ]);
                    }

                    continue;
                }

                $insertStmt->execute([
                    ':id' => $id,
                    ':email_address' => $email,
                    ':expected_valid' => $expectedValid,
                    ':severity_weight' => $severityWeight,
                    ':created_at' => $now,
                ]);
            }

            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }
}
