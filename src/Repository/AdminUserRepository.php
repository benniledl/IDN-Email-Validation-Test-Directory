<?php

declare(strict_types=1);

final class AdminUserRepository
{
    public function __construct(private PDO $pdo)
    {
        $this->ensurePasswordHashColumn();
    }

    private function ensurePasswordHashColumn(): void
    {
        $columnsStmt = $this->pdo->query('PRAGMA table_info(admin_users)');
        $columns = $columnsStmt->fetchAll();

        foreach ($columns as $column) {
            if ((string)($column['name'] ?? '') === 'password_hash') {
                return;
            }
        }

        $this->pdo->exec('ALTER TABLE admin_users ADD COLUMN password_hash TEXT NULL');
    }

    /** @return array{id: int, name: string, email: string}|null */
    public function verifyCredentials(string $email, string $password): ?array
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

    public function userIsActive(int $adminId): bool
    {
        $stmt = $this->pdo->prepare('SELECT id FROM admin_users WHERE id = :id AND is_active = 1 LIMIT 1');
        $stmt->execute([':id' => $adminId]);

        return $stmt->fetch() !== false;
    }

    public function activeCount(): int
    {
        $stmt = $this->pdo->query('SELECT COUNT(id) AS count_all FROM admin_users WHERE is_active = 1');
        $result = $stmt->fetch();

        return (int)($result['count_all'] ?? 0);
    }

    /** @return array<int, array<string, mixed>> */
    public function allUsers(): array
    {
        $stmt = $this->pdo->query('SELECT id, name, email, is_active, created_at FROM admin_users ORDER BY is_active DESC, id ASC');

        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findById(int $adminId): ?array
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

    public function addUser(string $name, string $email, string $password): bool
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

    public function updatePassword(int $adminId, string $password): bool
    {
        $stmt = $this->pdo->prepare('UPDATE admin_users SET password_hash = :password_hash WHERE id = :id');

        return $stmt->execute([
            ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ':id' => $adminId,
        ]) && $stmt->rowCount() > 0;
    }

    public function setActive(int $adminId, bool $isActive): bool
    {
        $stmt = $this->pdo->prepare('UPDATE admin_users SET is_active = :is_active WHERE id = :id');

        return $stmt->execute([
            ':is_active' => $isActive ? 1 : 0,
            ':id' => $adminId,
        ]) && $stmt->rowCount() > 0;
    }

    public function deleteUser(int $adminId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM admin_users WHERE id = :id');

        return $stmt->execute([':id' => $adminId]) && $stmt->rowCount() > 0;
    }

    private function generateOpaqueAdminToken(): string
    {
        return bin2hex(random_bytes(32));
    }
}
