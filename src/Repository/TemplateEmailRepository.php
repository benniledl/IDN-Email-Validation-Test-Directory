<?php

declare(strict_types=1);

final class TemplateEmailRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        $rows = $this->pdo
            ->query('SELECT id, email_address, expected_valid, severity_weight FROM template_emails ORDER BY id')
            ->fetchAll();

        if ($rows !== []) {
            return $rows;
        }

        $this->seedDefaults();

        return $this->pdo
            ->query('SELECT id, email_address, expected_valid, severity_weight FROM template_emails ORDER BY id')
            ->fetchAll();
    }

    private function seedDefaults(): void
    {
        $templates = [
            ['max@müller.de', 1, 3],
            ['info@büro.at', 1, 3],
            ['max@info.versicherung', 1, 3],
            ['max@newsletter.müller.de', 1, 2],
            ['max@news.info.versicherung', 1, 2],
            ['用户@例子.广告', 1, 1],
            ['max@-müller.de', 0, 3],
            ['max@müller..de', 0, 3],
            ['max@müller', 0, 3],
        ];

        $stmt = $this->pdo->prepare(
            'INSERT INTO template_emails (email_address, expected_valid, severity_weight, created_at)
             VALUES (:email_address, :expected_valid, :severity_weight, :created_at)'
        );
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');

        foreach ($templates as [$email, $expectedValid, $severityWeight]) {
            $stmt->execute([
                ':email_address' => $email,
                ':expected_valid' => $expectedValid,
                ':severity_weight' => $severityWeight,
                ':created_at' => $now,
            ]);
        }
    }
}
