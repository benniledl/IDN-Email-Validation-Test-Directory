<?php

declare(strict_types=1);

final class DirectoryController
{
    public function __construct(
        private SubmissionRepository $submissionRepository,
        private WordPressPluginService $wordPressPluginService
    ) {
    }

    public function softwareIndex(?string $flash = null, string $flashType = 'info'): void
    {
        $search = trim((string)($_GET['q'] ?? ''));
        $adminMode = $this->isAdminRequest();

        View::render('software-index', [
            'softwareItems' => $this->enrichSoftwareDirectory($this->submissionRepository->softwareDirectory($search)),
            'search' => $search,
            'adminMode' => $adminMode,
            'adminToken' => $adminMode ? trim((string)($_GET['admin_token'] ?? ($_POST['admin_token'] ?? ''))) : '',
            'flash' => $flash,
            'flashType' => $flashType,
        ]);
    }

    public function softwareDetail(int $softwareId, ?string $flash = null, string $flashType = 'info'): void
    {
        $software = $this->submissionRepository->findSoftware($softwareId);
        if ($software === null) {
            http_response_code(404);
            View::render('not-found', ['resource' => 'Software']);
            return;
        }

        $adminMode = $this->isAdminRequest();

        View::render('software-detail', [
            'software' => $this->enrichSoftwareDetail($software),
            'reports' => $this->submissionRepository->softwareSubmissions($softwareId),
            'comments' => $this->submissionRepository->softwareComments($softwareId),
            'admins' => $adminMode ? $this->submissionRepository->activeAdmins() : [],
            'adminMode' => $adminMode,
            'adminToken' => $adminMode ? trim((string)($_GET['admin_token'] ?? ($_POST['admin_token'] ?? ''))) : '',
            'flash' => $flash,
            'flashType' => $flashType,
        ]);
    }

    public function reportDetail(int $reportId, ?string $flash = null, string $flashType = 'info'): void
    {
        $report = $this->submissionRepository->findReport($reportId);
        if ($report === null) {
            http_response_code(404);
            View::render('not-found', ['resource' => 'Report']);
            return;
        }

        $adminMode = $this->isAdminRequest();

        View::render('report-detail', [
            'report' => $report,
            'tests' => $this->submissionRepository->reportTests($reportId),
            'comments' => $this->submissionRepository->reportComments($reportId),
            'adminMode' => $adminMode,
            'adminToken' => $adminMode ? trim((string)($_GET['admin_token'] ?? ($_POST['admin_token'] ?? ''))) : '',
            'flash' => $flash,
            'flashType' => $flashType,
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
        if (!$this->isAdminRequest()) {
            return ['message' => 'Admin token missing or invalid.', 'type' => 'danger'];
        }

        if (!$this->submissionRepository->hideSubmission($submissionId)) {
            return ['message' => 'Submission not found or already hidden.', 'type' => 'danger'];
        }

        return ['message' => 'Submission hidden.', 'type' => 'success'];
    }

    /** @return array{message: string, type: string} */
    public function adminHideCustomSoftware(int $softwareId): array
    {
        if (!$this->isAdminRequest()) {
            return ['message' => 'Admin token missing or invalid.', 'type' => 'danger'];
        }

        if (!$this->submissionRepository->hideCustomSoftware($softwareId)) {
            return ['message' => 'Only custom software can be hidden.', 'type' => 'danger'];
        }

        return ['message' => 'Custom software hidden.', 'type' => 'success'];
    }

    /** @return array{message: string, type: string} */
    public function adminOverrideSeverity(int $submissionId, array $post): array
    {
        if (!$this->isAdminRequest()) {
            return ['message' => 'Admin token missing or invalid.', 'type' => 'danger'];
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
        if (!$this->isAdminRequest()) {
            return ['message' => 'Admin token missing or invalid.', 'type' => 'danger'];
        }

        if (!$this->submissionRepository->hideSoftwareComment($commentId)) {
            return ['message' => 'Software comment not found.', 'type' => 'danger'];
        }

        return ['message' => 'Software comment hidden.', 'type' => 'success'];
    }

    /** @return array{message: string, type: string} */
    public function adminHideReportComment(int $commentId): array
    {
        if (!$this->isAdminRequest()) {
            return ['message' => 'Admin token missing or invalid.', 'type' => 'danger'];
        }

        if (!$this->submissionRepository->hideReportComment($commentId)) {
            return ['message' => 'Report comment not found.', 'type' => 'danger'];
        }

        return ['message' => 'Report comment hidden.', 'type' => 'success'];
    }

    /** @return array{message: string, type: string} */
    public function adminAddSoftwareSolution(int $softwareId, array $post): array
    {
        if (!$this->isAdminRequest()) {
            return ['message' => 'Admin token missing or invalid.', 'type' => 'danger'];
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
        if (!$this->isAdminRequest()) {
            return ['message' => 'Admin token missing or invalid.', 'type' => 'danger'];
        }

        $name = trim((string)($post['name'] ?? ''));
        $email = trim((string)($post['email'] ?? ''));
        $token = trim((string)($post['new_admin_token'] ?? ''));

        if ($name === '' || $email === '' || $token === '') {
            return ['message' => 'Admin name, email, and token are required.', 'type' => 'danger'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['message' => 'Admin email is invalid.', 'type' => 'danger'];
        }

        if (!$this->submissionRepository->addAdminUser($name, $email, $token)) {
            return ['message' => 'Could not add admin user (email/token may already exist).', 'type' => 'danger'];
        }

        return ['message' => 'Admin user added successfully.', 'type' => 'success'];
    }

    private function isAdminRequest(): bool
    {
        $providedToken = trim((string)($_POST['admin_token'] ?? ($_GET['admin_token'] ?? '')));
        if ($providedToken === '') {
            return false;
        }

        $configuredToken = trim((string)getenv('ADMIN_TOKEN'));
        if ($configuredToken !== '' && hash_equals($configuredToken, $providedToken)) {
            return true;
        }

        return $this->submissionRepository->adminTokenExists($providedToken);
    }
}
