<?php

declare(strict_types=1);

final class DirectoryController
{
    private const ADMIN_SESSION_TOKEN_KEY = 'admin_auth_token';
    private const ADMIN_SESSION_CSRF_KEY = 'admin_csrf_token';
    private const ADMIN_SESSION_TYPE_KEY = 'admin_auth_type';
    private const ADMIN_SESSION_USER_ID_KEY = 'admin_user_id';

    public function __construct(
        private SubmissionRepository $submissionRepository,
        private WordPressPluginService $wordPressPluginService
    ) {
    }

    public function softwareIndex(?string $flash = null, string $flashType = 'info'): void
    {
        $search = trim((string)($_GET['q'] ?? ''));
        $adminMode = $this->isAdminSession();

        View::render('software-index', [
            'softwareItems' => $this->enrichSoftwareDirectory($this->submissionRepository->softwareDirectory($search)),
            'search' => $search,
            'adminMode' => $adminMode,
            'adminCsrfToken' => $adminMode ? $this->adminCsrfToken() : '',
            'flash' => $flash,
            'flashType' => $flashType,
        ]);
    }

    public function adminLoginPage(?string $flash = null, string $flashType = 'info', array $old = []): void
    {
        $adminMode = $this->isAdminSession();

        View::render('admin-login', [
            'adminMode' => $adminMode,
            'adminCsrfToken' => $adminMode ? $this->adminCsrfToken() : '',
            'flash' => $flash,
            'flashType' => $flashType,
            'old' => $old,
        ]);
    }

    public function adminPanel(?string $flash = null, string $flashType = 'info', array $old = []): void
    {
        if (!$this->isAdminSession()) {
            $this->adminLoginPage('Please log in as admin to access the panel.', 'warning');
            return;
        }

        View::render('admin-panel', [
            'admins' => $this->submissionRepository->adminUsers(),
            'adminCsrfToken' => $this->adminCsrfToken(),
            'adminSessionType' => (string)($_SESSION[self::ADMIN_SESSION_TYPE_KEY] ?? ''),
            'adminSessionUserId' => (int)($_SESSION[self::ADMIN_SESSION_USER_ID_KEY] ?? 0),
            'flash' => $flash,
            'flashType' => $flashType,
            'old' => $old,
        ]);
    }

    public function softwareDetail(int $softwareId, ?string $flash = null, string $flashType = 'info', array $old = []): void
    {
        $software = $this->submissionRepository->findSoftware($softwareId);
        if ($software === null) {
            http_response_code(404);
            View::render('not-found', ['resource' => 'Software']);
            return;
        }

        $adminMode = $this->isAdminSession();

        View::render('software-detail', [
            'software' => $this->enrichSoftwareDetail($software),
            'reports' => $this->submissionRepository->softwareSubmissions($softwareId),
            'comments' => $this->submissionRepository->softwareComments($softwareId),
            'adminMode' => $adminMode,
            'adminCsrfToken' => $adminMode ? $this->adminCsrfToken() : '',
            'flash' => $flash,
            'flashType' => $flashType,
            'old' => $old,
        ]);
    }

    public function reportDetail(int $reportId, ?string $flash = null, string $flashType = 'info', array $old = []): void
    {
        $report = $this->submissionRepository->findReport($reportId);
        if ($report === null) {
            http_response_code(404);
            View::render('not-found', ['resource' => 'Report']);
            return;
        }

        $adminMode = $this->isAdminSession();

        View::render('report-detail', [
            'report' => $report,
            'tests' => $this->submissionRepository->reportTests($reportId),
            'comments' => $this->submissionRepository->reportComments($reportId),
            'adminMode' => $adminMode,
            'adminCsrfToken' => $adminMode ? $this->adminCsrfToken() : '',
            'flash' => $flash,
            'flashType' => $flashType,
            'old' => $old,
        ]);
    }

    /** @param array<int, array<string, mixed>> $softwareItems
     *  @return array<int, array<string, mixed>>
     */
    private function enrichSoftwareDirectory(array $softwareItems): array
    {
        foreach ($softwareItems as &$item) {
            $item = $this->appendPluginMeta($item);
        }

        return $softwareItems;
    }

    /** @param array<string, mixed> $software
     *  @return array<string, mixed>
     */
    private function enrichSoftwareDetail(array $software): array
    {
        return $this->appendPluginMeta($software);
    }

    /** @param array<string, mixed> $item
     *  @return array<string, mixed>
     */
    private function appendPluginMeta(array $item): array
    {
        if (($item['type'] ?? '') !== 'wp_plugin') {
            return $item;
        }

        $slug = trim((string)($item['slug'] ?? ''));
        if ($slug === '') {
            $slug = $this->extractPluginSlug((string)($item['canonical_url'] ?? ''));
        }

        if ($slug === '') {
            return $item;
        }

        $pluginData = $this->wordPressPluginService->fetchBySlug($slug);
        if ($pluginData === null) {
            return $item;
        }

        if (($item['name'] ?? '') === '') {
            $item['name'] = $pluginData['name'];
        }

        if (($item['description'] ?? '') === '') {
            $item['description'] = $pluginData['description'];
        }

        if (($item['plugin_icon_url'] ?? '') === '') {
            $item['plugin_icon_url'] = $pluginData['icon_url'];
        }

        $item['plugin_icon_2x_url'] = $pluginData['icon_2x_url'];
        if (($item['plugin_banner_url'] ?? '') === '') {
            $item['plugin_banner_url'] = $pluginData['banner_url'];
        }

        $item['plugin_banner_2x_url'] = $pluginData['banner_2x_url'];
        $item['plugin_author'] = $pluginData['author'];
        $item['plugin_active_installs'] = $pluginData['active_installs'];
        $item['plugin_tested'] = $pluginData['tested'];

        return $item;
    }

    private function extractPluginSlug(string $canonicalUrl): string
    {
        if (preg_match('#/plugins/([a-z0-9-]+)/?#i', $canonicalUrl, $matches) !== 1) {
            return '';
        }

        return strtolower((string)$matches[1]);
    }

    /** @return array{message: string, type: string} */
    public function storeSoftwareComment(int $softwareId, array $post): array
    {
        $name = trim((string)($post['author_name'] ?? ''));
        $comment = trim((string)($post['comment'] ?? ''));

        if ($name === '' || $comment === '') {
            return ['message' => 'Comment name and message are required.', 'type' => 'danger'];
        }

        $this->submissionRepository->addSoftwareComment($softwareId, $name, $comment);

        return ['message' => 'Comment added to software overview.', 'type' => 'success'];
    }

    /** @return array{message: string, type: string} */
    public function storeReportComment(int $reportId, array $post): array
    {
        $name = trim((string)($post['author_name'] ?? ''));
        $comment = trim((string)($post['comment'] ?? ''));

        if ($name === '' || $comment === '') {
            return ['message' => 'Comment name and message are required.', 'type' => 'danger'];
        }

        $this->submissionRepository->addReportComment($reportId, $name, $comment);

        return ['message' => 'Comment added to report detail.', 'type' => 'success'];
    }

    /** @return array{message: string, type: string} */
    public function adminHideSubmission(int $submissionId): array
    {
        $authError = $this->adminAuthError($_POST);
        if ($authError !== null) {
            return $authError;
        }

        if (!$this->submissionRepository->hideSubmission($submissionId)) {
            return ['message' => 'Submission not found or already hidden.', 'type' => 'danger'];
        }

        return ['message' => 'Submission hidden.', 'type' => 'success'];
    }

    /** @return array{message: string, type: string} */
    public function adminHideCustomSoftware(int $softwareId): array
    {
        $authError = $this->adminAuthError($_POST);
        if ($authError !== null) {
            return $authError;
        }

        if (!$this->submissionRepository->hideCustomSoftware($softwareId)) {
            return ['message' => 'Only custom software can be hidden.', 'type' => 'danger'];
        }

        return ['message' => 'Custom software hidden.', 'type' => 'success'];
    }

    /** @return array{message: string, type: string} */
    public function adminOverrideSeverity(int $submissionId, array $post): array
    {
        $authError = $this->adminAuthError($post);
        if ($authError !== null) {
            return $authError;
        }

        $severity = trim((string)($post['severity_admin_override'] ?? ''));
        $normalized = $severity === '' ? null : $severity;
        if ($normalized !== null && !in_array($normalized, ['none', 'low', 'medium', 'high'], true)) {
            return ['message' => 'Invalid severity override value.', 'type' => 'danger'];
        }

        $this->submissionRepository->setSubmissionSeverityOverride($submissionId, $normalized);

        return ['message' => 'Severity override saved.', 'type' => 'success'];
    }

    /** @return array{message: string, type: string} */
    public function adminHideSoftwareComment(int $commentId): array
    {
        $authError = $this->adminAuthError($_POST);
        if ($authError !== null) {
            return $authError;
        }

        if (!$this->submissionRepository->hideSoftwareComment($commentId)) {
            return ['message' => 'Software comment not found.', 'type' => 'danger'];
        }

        return ['message' => 'Software comment hidden.', 'type' => 'success'];
    }

    /** @return array{message: string, type: string} */
    public function adminHideReportComment(int $commentId): array
    {
        $authError = $this->adminAuthError($_POST);
        if ($authError !== null) {
            return $authError;
        }

        if (!$this->submissionRepository->hideReportComment($commentId)) {
            return ['message' => 'Report comment not found.', 'type' => 'danger'];
        }

        return ['message' => 'Report comment hidden.', 'type' => 'success'];
    }

    /** @return array{message: string, type: string} */
    public function adminAddSoftwareSolution(int $softwareId, array $post): array
    {
        $authError = $this->adminAuthError($post);
        if ($authError !== null) {
            return $authError;
        }

        $author = trim((string)($post['author_name'] ?? 'Admin'));
        $comment = trim((string)($post['comment'] ?? ''));
        if ($comment === '') {
            return ['message' => 'Solution comment cannot be empty.', 'type' => 'danger'];
        }

        $this->submissionRepository->addAdminSoftwareSolutionComment($softwareId, $author === '' ? 'Admin' : $author, $comment);

        return ['message' => 'Official solution comment posted.', 'type' => 'success'];
    }

    /** @return array{message: string, type: string} */
    public function adminAddUser(array $post): array
    {
        $authError = $this->adminAuthError($post);
        if ($authError !== null) {
            return $authError;
        }

        $name = trim((string)($post['name'] ?? ''));
        $email = trim((string)($post['email'] ?? ''));
        $password = (string)($post['new_admin_password'] ?? '');
        $passwordConfirm = (string)($post['new_admin_password_confirm'] ?? '');

        if ($name === '' || $email === '' || $password === '' || $passwordConfirm === '') {
            return ['message' => 'Admin name, email, password, and confirmation are required.', 'type' => 'danger'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['message' => 'Admin email is invalid.', 'type' => 'danger'];
        }

        if (strlen($password) < 10) {
            return ['message' => 'Admin password must be at least 10 characters.', 'type' => 'danger'];
        }

        if (!hash_equals($password, $passwordConfirm)) {
            return ['message' => 'Password and confirmation must match.', 'type' => 'danger'];
        }

        if (!$this->submissionRepository->addAdminUser($name, $email, $password)) {
            return ['message' => 'Could not add admin user (email may already exist).', 'type' => 'danger'];
        }

        return ['message' => 'Admin user added successfully.', 'type' => 'success'];
    }

    /** @return array{message: string, type: string} */
    public function adminResetUserPassword(array $post): array
    {
        $authError = $this->adminAuthError($post);
        if ($authError !== null) {
            return $authError;
        }

        $adminId = (int)($post['admin_id'] ?? 0);
        $password = (string)($post['new_password'] ?? '');
        $passwordConfirm = (string)($post['new_password_confirm'] ?? '');

        if ($adminId <= 0 || $password === '' || $passwordConfirm === '') {
            return ['message' => 'Admin id, new password, and confirmation are required.', 'type' => 'danger'];
        }

        if (strlen($password) < 10) {
            return ['message' => 'Password must be at least 10 characters.', 'type' => 'danger'];
        }

        if (!hash_equals($password, $passwordConfirm)) {
            return ['message' => 'Password and confirmation must match.', 'type' => 'danger'];
        }

        if (!$this->submissionRepository->updateAdminPassword($adminId, $password)) {
            return ['message' => 'Could not update admin password.', 'type' => 'danger'];
        }

        return ['message' => 'Admin password updated.', 'type' => 'success'];
    }

    /** @return array{message: string, type: string} */
    public function adminSetUserStatus(array $post): array
    {
        $authError = $this->adminAuthError($post);
        if ($authError !== null) {
            return $authError;
        }

        $adminId = (int)($post['admin_id'] ?? 0);
        $newStatus = trim((string)($post['is_active'] ?? ''));
        if ($adminId <= 0 || !in_array($newStatus, ['0', '1'], true)) {
            return ['message' => 'Invalid admin status request.', 'type' => 'danger'];
        }

        $activate = $newStatus === '1';
        $targetAdmin = $this->submissionRepository->adminUserById($adminId);
        if ($targetAdmin === null) {
            return ['message' => 'Admin user not found.', 'type' => 'danger'];
        }

        $currentSessionUserId = (int)($_SESSION[self::ADMIN_SESSION_USER_ID_KEY] ?? 0);
        if (!$activate && $currentSessionUserId > 0 && $currentSessionUserId === $adminId) {
            return ['message' => 'You cannot deactivate your own account while logged in.', 'type' => 'danger'];
        }

        if (!$activate && $this->submissionRepository->activeAdminCount() <= 1) {
            return ['message' => 'At least one active admin account must remain.', 'type' => 'danger'];
        }

        if (!$this->submissionRepository->setAdminUserActive($adminId, $activate)) {
            return ['message' => 'No status change was applied.', 'type' => 'danger'];
        }

        return ['message' => $activate ? 'Admin account activated.' : 'Admin account deactivated.', 'type' => 'success'];
    }

    /** @return array{message: string, type: string} */
    public function adminLogin(array $post): array
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
            $_SESSION[self::ADMIN_SESSION_TYPE_KEY] = 'token';
            $_SESSION[self::ADMIN_SESSION_TOKEN_KEY] = $providedToken;
            unset($_SESSION[self::ADMIN_SESSION_USER_ID_KEY]);
            $_SESSION[self::ADMIN_SESSION_CSRF_KEY] = bin2hex(random_bytes(32));

            return ['message' => 'Admin mode enabled with token login.', 'type' => 'success'];
        }

        $email = trim((string)($post['email'] ?? ''));
        $password = (string)($post['password'] ?? '');
        if ($email === '' || $password === '') {
            return ['message' => 'Admin email and password are required.', 'type' => 'danger'];
        }

        $admin = $this->submissionRepository->verifyAdminCredentials($email, $password);
        if ($admin === null) {
            return ['message' => 'Admin email or password is invalid.', 'type' => 'danger'];
        }

        session_regenerate_id(true);
        $_SESSION[self::ADMIN_SESSION_TYPE_KEY] = 'password';
        $_SESSION[self::ADMIN_SESSION_USER_ID_KEY] = (int)$admin['id'];
        unset($_SESSION[self::ADMIN_SESSION_TOKEN_KEY]);
        $_SESSION[self::ADMIN_SESSION_CSRF_KEY] = bin2hex(random_bytes(32));

        return ['message' => 'Admin mode enabled.', 'type' => 'success'];
    }

    /** @return array{message: string, type: string} */
    public function adminLogout(array $post): array
    {
        $authError = $this->adminAuthError($post);
        if ($authError !== null) {
            return $authError;
        }

        unset($_SESSION[self::ADMIN_SESSION_TOKEN_KEY], $_SESSION[self::ADMIN_SESSION_CSRF_KEY]);
        unset($_SESSION[self::ADMIN_SESSION_TYPE_KEY], $_SESSION[self::ADMIN_SESSION_USER_ID_KEY]);
        session_regenerate_id(true);

        return ['message' => 'Admin mode disabled.', 'type' => 'info'];
    }

    private function isAdminSession(): bool
    {
        $authType = trim((string)($_SESSION[self::ADMIN_SESSION_TYPE_KEY] ?? ''));
        if ($authType === 'token') {
            $token = trim((string)($_SESSION[self::ADMIN_SESSION_TOKEN_KEY] ?? ''));
            if ($token === '') {
                return false;
            }

            if (!$this->isValidMainAdminToken($token)) {
                $this->clearAdminSession();
                return false;
            }

            return true;
        }

        if ($authType === 'password') {
            $adminId = (int)($_SESSION[self::ADMIN_SESSION_USER_ID_KEY] ?? 0);
            if ($adminId <= 0 || !$this->submissionRepository->adminUserIsActive($adminId)) {
                $this->clearAdminSession();
                return false;
            }

            return true;
        }

        if (trim((string)($_SESSION[self::ADMIN_SESSION_TOKEN_KEY] ?? '')) !== '') {
            $token = trim((string)$_SESSION[self::ADMIN_SESSION_TOKEN_KEY]);
            if ($this->isValidMainAdminToken($token)) {
                $_SESSION[self::ADMIN_SESSION_TYPE_KEY] = 'token';
                return true;
            }

            $this->clearAdminSession();
            return false;
        }

        return false;
    }

    /** @return array{message: string, type: string}|null */
    private function adminAuthError(array $post): ?array
    {
        if (!$this->isAdminSession()) {
            return ['message' => 'Admin session missing or invalid.', 'type' => 'danger'];
        }

        $csrfFromPost = trim((string)($post['csrf_token'] ?? ''));
        if ($csrfFromPost === '' || !hash_equals($this->adminCsrfToken(), $csrfFromPost)) {
            return ['message' => 'Invalid admin security token. Please refresh and retry.', 'type' => 'danger'];
        }

        return null;
    }

    private function adminCsrfToken(): string
    {
        $existing = trim((string)($_SESSION[self::ADMIN_SESSION_CSRF_KEY] ?? ''));
        if ($existing !== '') {
            return $existing;
        }

        $generated = bin2hex(random_bytes(32));
        $_SESSION[self::ADMIN_SESSION_CSRF_KEY] = $generated;

        return $generated;
    }

    private function isValidMainAdminToken(string $providedToken): bool
    {
        $configuredToken = trim((string)getenv('ADMIN_TOKEN'));
        if ($configuredToken !== '' && hash_equals($configuredToken, $providedToken)) {
            return true;
        }

        return false;
    }

    private function clearAdminSession(): void
    {
        unset(
            $_SESSION[self::ADMIN_SESSION_TYPE_KEY],
            $_SESSION[self::ADMIN_SESSION_TOKEN_KEY],
            $_SESSION[self::ADMIN_SESSION_USER_ID_KEY],
            $_SESSION[self::ADMIN_SESSION_CSRF_KEY]
        );
    }
}
