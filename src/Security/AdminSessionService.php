<?php

declare(strict_types=1);

final class AdminSessionService
{
    private const SESSION_TOKEN_KEY = 'admin_auth_token';
    private const SESSION_CSRF_KEY = 'admin_csrf_token';
    private const SESSION_TYPE_KEY = 'admin_auth_type';
    private const SESSION_USER_ID_KEY = 'admin_user_id';

    public function __construct(private AdminUserRepository $adminUserRepository)
    {
    }

    /** @return array{message: string, type: string} */
    public function login(array $post): array
    {
        $loginMode = trim((string)($post['login_mode'] ?? 'password'));

        if ($loginMode === 'token') {
            $providedToken = trim((string)($post['admin_token'] ?? ''));
            if ($providedToken === '') {
                return ['message' => 'Admin token is required.', 'type' => 'danger'];
            }

            if (!$this->isValidMainAdminToken($providedToken)) {
                return ['message' => 'Admin token is invalid.', 'type' => 'danger'];
            }

            session_regenerate_id(true);
            $_SESSION[self::SESSION_TYPE_KEY] = 'token';
            $_SESSION[self::SESSION_TOKEN_KEY] = $providedToken;
            unset($_SESSION[self::SESSION_USER_ID_KEY]);
            $_SESSION[self::SESSION_CSRF_KEY] = bin2hex(random_bytes(32));

            return ['message' => 'Admin mode enabled with token login.', 'type' => 'success'];
        }

        $email = trim((string)($post['email'] ?? ''));
        $password = (string)($post['password'] ?? '');
        if ($email === '' || $password === '') {
            return ['message' => 'Admin email and password are required.', 'type' => 'danger'];
        }

        $admin = $this->adminUserRepository->verifyCredentials($email, $password);
        if ($admin === null) {
            return ['message' => 'Admin email or password is invalid.', 'type' => 'danger'];
        }

        session_regenerate_id(true);
        $_SESSION[self::SESSION_TYPE_KEY] = 'password';
        $_SESSION[self::SESSION_USER_ID_KEY] = (int)$admin['id'];
        unset($_SESSION[self::SESSION_TOKEN_KEY]);
        $_SESSION[self::SESSION_CSRF_KEY] = bin2hex(random_bytes(32));

        return ['message' => 'Admin mode enabled.', 'type' => 'success'];
    }

    /** @return array{message: string, type: string} */
    public function logout(array $post): array
    {
        $authError = $this->authError($post);
        if ($authError !== null) {
            return $authError;
        }

        $this->clearSession();
        session_regenerate_id(true);

        return ['message' => 'Admin mode disabled.', 'type' => 'info'];
    }

    public function isAuthenticated(): bool
    {
        $authType = trim((string)($_SESSION[self::SESSION_TYPE_KEY] ?? ''));
        if ($authType === 'token') {
            $token = trim((string)($_SESSION[self::SESSION_TOKEN_KEY] ?? ''));
            if ($token === '' || !$this->isValidMainAdminToken($token)) {
                $this->clearSession();
                return false;
            }

            return true;
        }

        if ($authType === 'password') {
            $adminId = (int)($_SESSION[self::SESSION_USER_ID_KEY] ?? 0);
            if ($adminId <= 0 || !$this->adminUserRepository->userIsActive($adminId)) {
                $this->clearSession();
                return false;
            }

            return true;
        }

        return false;
    }

    /** @return array{message: string, type: string}|null */
    public function authError(array $post): ?array
    {
        if (!$this->isAuthenticated()) {
            return ['message' => 'Admin session missing or invalid.', 'type' => 'danger'];
        }

        $csrfFromPost = trim((string)($post['csrf_token'] ?? ''));
        if ($csrfFromPost === '' || !hash_equals($this->csrfToken(), $csrfFromPost)) {
            return ['message' => 'Invalid admin security token. Please refresh and retry.', 'type' => 'danger'];
        }

        return null;
    }

    public function csrfToken(): string
    {
        $existing = trim((string)($_SESSION[self::SESSION_CSRF_KEY] ?? ''));
        if ($existing !== '') {
            return $existing;
        }

        $generated = bin2hex(random_bytes(32));
        $_SESSION[self::SESSION_CSRF_KEY] = $generated;

        return $generated;
    }

    public function authType(): string
    {
        return (string)($_SESSION[self::SESSION_TYPE_KEY] ?? '');
    }

    public function userId(): int
    {
        return (int)($_SESSION[self::SESSION_USER_ID_KEY] ?? 0);
    }

    private function isValidMainAdminToken(string $providedToken): bool
    {
        $configuredToken = trim((string)getenv('ADMIN_TOKEN'));
        return $configuredToken !== '' && hash_equals($configuredToken, $providedToken);
    }

    private function clearSession(): void
    {
        unset(
            $_SESSION[self::SESSION_TYPE_KEY],
            $_SESSION[self::SESSION_TOKEN_KEY],
            $_SESSION[self::SESSION_USER_ID_KEY],
            $_SESSION[self::SESSION_CSRF_KEY]
        );
    }
}
