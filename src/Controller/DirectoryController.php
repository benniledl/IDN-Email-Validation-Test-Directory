<?php

declare(strict_types=1);

final class DirectoryController
{
    public function __construct(
        private SubmissionRepository $submissionRepository,
        private WordPressPluginService $wordPressPluginService
    )
    {
    }

    public function softwareIndex(?string $flash = null, string $flashType = 'info'): void
    {
        View::render('software-index', [
            'softwareItems' => $this->enrichSoftwareDirectory($this->submissionRepository->softwareDirectory()),
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

        View::render('software-detail', [
            'software' => $this->enrichSoftwareDetail($software),
            'reports' => $this->submissionRepository->softwareSubmissions($softwareId),
            'comments' => $this->submissionRepository->softwareComments($softwareId),
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

        View::render('report-detail', [
            'report' => $report,
            'tests' => $this->submissionRepository->reportTests($reportId),
            'comments' => $this->submissionRepository->reportComments($reportId),
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
}
