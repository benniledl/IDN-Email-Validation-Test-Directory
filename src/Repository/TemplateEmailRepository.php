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
        return $this->pdo
            ->query('SELECT id, email_address, expected_valid, severity_level, complexity_label FROM template_emails ORDER BY id')
            ->fetchAll();
    }
}
