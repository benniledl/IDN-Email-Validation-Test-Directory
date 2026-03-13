<?php

declare(strict_types=1);

final class DirectoryController
{
    public function __construct(
        private DirectoryRepository $directoryRepository,
        private CommentRepository $commentRepository,
        private AdminUserRepository $adminUserRepository,
        private AdminSessionService $adminSessionService,
        private CommentSpamService $commentSpamService,
        private WordPressPluginService $wordPressPluginService
    ) {
    }

    public function softwareIndex(string $search = '', ?string $flash = null, string $flashType = 'info'): void
    {
        $search = trim($search);
        $adminMode = $this->adminSessionService->isAuthenticated();
        $allSoftware = $this->enrichSoftwareDirectory($this->directoryRepository->softwareDirectory(''));

        $softwareItems = $allSoftware;
        if ($search !== '') {
            $dbMatches = $this->directoryRepository->softwareDirectory($search);
            $matchedByDb = [];
            foreach ($dbMatches as $item) {
                $matchedByDb[(int)($item['id'] ?? 0)] = true;
            }

            $searchNeedle = strtolower($search);
            $softwareItems = array_values(array_filter($allSoftware, static function (array $item) use ($matchedByDb, $searchNeedle): bool {
                $id = (int)($item['id'] ?? 0);
                if ($id > 0 && isset($matchedByDb[$id])) {
                    return true;
                }

                $name = strtolower(html_entity_decode((string)($item['name'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                $description = strtolower((string)($item['description'] ?? ''));
                $pluginAuthor = strtolower((string)($item['plugin_author'] ?? ''));

                return str_contains($name, $searchNeedle)
                    || str_contains($description, $searchNeedle)
                    || str_contains($pluginAuthor, $searchNeedle);
            }));
        }

        View::render('software-index', [
            'softwareItems' => $softwareItems,
            'search' => $search,
            'adminMode' => $adminMode,
            'adminCsrfToken' => $adminMode ? $this->adminSessionService->csrfToken() : '',
            'flash' => $flash,
            'flashType' => $flashType,
        ]);
    }

    public function reportsIndex(string $search = '', string $severity = ''): void
    {
        $search = trim($search);
        $severity = trim($severity);
        if (!in_array($severity, ['', 'none', 'low', 'medium', 'high'], true)) {
            $severity = '';
        }

        $reports = $this->directoryRepository->reportsDirectory($search, $severity);

        View::render('reports-index', [
            'reports' => $reports,
            'search' => $search,
            'severity' => $severity,
            'metaTitle' => 'Reports Directory | IDN Validation',
            'metaDescription' => 'Browse submitted IDN validation reports across software and plugins.',
        ]);
    }

    public function adminLoginPage(?string $flash = null, string $flashType = 'info', array $old = []): void
    {
        $adminMode = $this->adminSessionService->isAuthenticated();

        View::render('admin-login', [
            'adminMode' => $adminMode,
            'adminCsrfToken' => $adminMode ? $this->adminSessionService->csrfToken() : '',
            'flash' => $flash,
            'flashType' => $flashType,
            'old' => $old,
        ]);
    }

    public function adminPanel(?string $flash = null, string $flashType = 'info', array $old = []): void
    {
        if (!$this->adminSessionService->isAuthenticated()) {
            $this->adminLoginPage('Please log in as admin to access the panel.', 'warning');
            return;
        }

        View::render('admin-panel', [
            'admins' => $this->adminUserRepository->allUsers(),
            'adminCsrfToken' => $this->adminSessionService->csrfToken(),
            'adminSessionType' => $this->adminSessionService->authType(),
            'adminSessionUserId' => $this->adminSessionService->userId(),
            'flash' => $flash,
            'flashType' => $flashType,
            'old' => $old,
        ]);
    }

    public function softwareDetail(int $softwareId, ?string $flash = null, string $flashType = 'info', array $old = []): void
    {
        $software = $this->directoryRepository->findSoftware($softwareId);
        if ($software === null) {
            http_response_code(404);
            View::render('not-found', ['resource' => 'Software']);
            return;
        }

        $adminMode = $this->adminSessionService->isAuthenticated();
        $enrichedSoftware = $this->enrichSoftwareDetail($software);
        $playgroundLaunchUrl = '';

        if (($enrichedSoftware['type'] ?? '') === 'wp_plugin') {
            $slug = trim((string)($enrichedSoftware['slug'] ?? ''));
            if ($slug === '') {
                $slug = $this->extractPluginSlug((string)($enrichedSoftware['canonical_url'] ?? ''));
            }

            if ($slug !== '') {
                $playgroundLaunchUrl = 'https://playground.wordpress.net/?' . http_build_query([
                    'plugin' => $slug,
                ], '', '&', PHP_QUERY_RFC3986);
            }
        }

        View::render('software-detail', [
            'software' => $enrichedSoftware,
            'reports' => $this->directoryRepository->softwareSubmissions($softwareId),
            'comments' => $this->commentRepository->softwareComments($softwareId),
            'commentGuard' => $this->commentSpamService->issueGuardToken('software:' . $softwareId),
            'playgroundLaunchUrl' => $playgroundLaunchUrl,
            'metaRobots' => 'noindex,follow',
            'adminMode' => $adminMode,
            'adminCsrfToken' => $adminMode ? $this->adminSessionService->csrfToken() : '',
            'flash' => $flash,
            'flashType' => $flashType,
            'old' => $old,
        ]);
    }

    public function reportDetail(int $reportId, ?string $flash = null, string $flashType = 'info', array $old = []): void
    {
        $report = $this->directoryRepository->findReport($reportId);
        if ($report === null) {
            http_response_code(404);
            View::render('not-found', ['resource' => 'Report']);
            return;
        }

        $adminMode = $this->adminSessionService->isAuthenticated();

        View::render('report-detail', [
            'report' => $report,
            'tests' => $this->directoryRepository->reportTests($reportId),
            'comments' => $this->commentRepository->reportComments($reportId),
            'commentGuard' => $this->commentSpamService->issueGuardToken('report:' . $reportId),
            'metaRobots' => 'noindex,follow',
            'adminMode' => $adminMode,
            'adminCsrfToken' => $adminMode ? $this->adminSessionService->csrfToken() : '',
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

        $spamError = $this->commentSpamService->validate('software:' . $softwareId, $post, $name, $comment);
        if ($spamError !== null) {
            return ['message' => $spamError, 'type' => 'danger'];
        }

        if ($name === '' || $comment === '') {
            return ['message' => 'Comment name and message are required.', 'type' => 'danger'];
        }

        $this->commentRepository->addSoftwareComment($softwareId, $name, $comment);
        $this->commentSpamService->recordAccepted($name, $comment);
        $this->commentSpamService->clearGuardToken('software:' . $softwareId);

        return ['message' => 'Comment added to software overview.', 'type' => 'success'];
    }

    /** @return array{message: string, type: string} */
    public function storeReportComment(int $reportId, array $post): array
    {
        $name = trim((string)($post['author_name'] ?? ''));
        $comment = trim((string)($post['comment'] ?? ''));

        $spamError = $this->commentSpamService->validate('report:' . $reportId, $post, $name, $comment);
        if ($spamError !== null) {
            return ['message' => $spamError, 'type' => 'danger'];
        }

        if ($name === '' || $comment === '') {
            return ['message' => 'Comment name and message are required.', 'type' => 'danger'];
        }

        $this->commentRepository->addReportComment($reportId, $name, $comment);
        $this->commentSpamService->recordAccepted($name, $comment);
        $this->commentSpamService->clearGuardToken('report:' . $reportId);

        return ['message' => 'Comment added to report detail.', 'type' => 'success'];
    }


    /** @return array{message: string, type: string} */
    public function adminHideSubmission(int $submissionId, array $post): array
    {
        $authError = $this->adminAuthError($post);
        if ($authError !== null) {
            return $authError;
        }

        if (!$this->directoryRepository->hideSubmission($submissionId)) {
            return ['message' => 'Submission not found or already hidden.', 'type' => 'danger'];
        }

        return ['message' => 'Submission hidden.', 'type' => 'success'];
    }

    /** @return array{message: string, type: string} */
    public function adminHideCustomSoftware(int $softwareId, array $post): array
    {
        $authError = $this->adminAuthError($post);
        if ($authError !== null) {
            return $authError;
        }

        if (!$this->directoryRepository->hideCustomSoftware($softwareId)) {
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

        $this->directoryRepository->setSubmissionSeverityOverride($submissionId, $normalized);

        return ['message' => 'Severity override saved.', 'type' => 'success'];
    }

    /** @return array{message: string, type: string} */
    public function adminHideSoftwareComment(int $commentId, array $post): array
    {
        $authError = $this->adminAuthError($post);
        if ($authError !== null) {
            return $authError;
        }

        if (!$this->commentRepository->hideSoftwareComment($commentId)) {
            return ['message' => 'Software comment not found.', 'type' => 'danger'];
        }

        return ['message' => 'Software comment hidden.', 'type' => 'success'];
    }

    /** @return array{message: string, type: string} */
    public function adminHideReportComment(int $commentId, array $post): array
    {
        $authError = $this->adminAuthError($post);
        if ($authError !== null) {
            return $authError;
        }

        if (!$this->commentRepository->hideReportComment($commentId)) {
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

        $this->commentRepository->addAdminSoftwareSolutionComment($softwareId, $author === '' ? 'Admin' : $author, $comment);

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

        if (!$this->adminUserRepository->addUser($name, $email, $password)) {
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

        if (!$this->adminUserRepository->updatePassword($adminId, $password)) {
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
        $targetAdmin = $this->adminUserRepository->findById($adminId);
        if ($targetAdmin === null) {
            return ['message' => 'Admin user not found.', 'type' => 'danger'];
        }

        $currentSessionUserId = $this->adminSessionService->userId();
        if (!$activate && $currentSessionUserId > 0 && $currentSessionUserId === $adminId) {
            return ['message' => 'You cannot deactivate your own account while logged in.', 'type' => 'danger'];
        }

        if (!$activate && $this->adminUserRepository->activeCount() <= 1) {
            return ['message' => 'At least one active admin account must remain.', 'type' => 'danger'];
        }

        if (!$this->adminUserRepository->setActive($adminId, $activate)) {
            return ['message' => 'No status change was applied.', 'type' => 'danger'];
        }

        return ['message' => $activate ? 'Admin account activated.' : 'Admin account deactivated.', 'type' => 'success'];
    }

    /** @return array{message: string, type: string} */
    public function adminDeleteUser(array $post): array
    {
        $authError = $this->adminAuthError($post);
        if ($authError !== null) {
            return $authError;
        }

        $adminId = (int)($post['admin_id'] ?? 0);
        if ($adminId <= 0) {
            return ['message' => 'Invalid admin user selected.', 'type' => 'danger'];
        }

        $targetAdmin = $this->adminUserRepository->findById($adminId);
        if ($targetAdmin === null) {
            return ['message' => 'Admin user not found.', 'type' => 'danger'];
        }

        $currentSessionUserId = $this->adminSessionService->userId();
        if ($currentSessionUserId > 0 && $currentSessionUserId === $adminId) {
            return ['message' => 'You cannot delete your own account while logged in.', 'type' => 'danger'];
        }

        $isTargetActive = (int)($targetAdmin['is_active'] ?? 0) === 1;
        if ($isTargetActive && $this->adminUserRepository->activeCount() <= 1) {
            return ['message' => 'At least one active admin account must remain.', 'type' => 'danger'];
        }

        if (!$this->adminUserRepository->deleteUser($adminId)) {
            return ['message' => 'Could not delete admin user.', 'type' => 'danger'];
        }

        return ['message' => 'Admin user deleted.', 'type' => 'success'];
    }

    /** @return array{message: string, type: string} */
    public function adminLogin(array $post): array
    {
        return $this->adminSessionService->login($post);
    }

    /** @return array{message: string, type: string} */
    public function adminLogout(array $post): array
    {
        return $this->adminSessionService->logout($post);
    }

    /** @return array{message: string, type: string}|null */
    private function adminAuthError(array $post): ?array
    {
        return $this->adminSessionService->authError($post);
    }
}
