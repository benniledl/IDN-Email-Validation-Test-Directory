<?php

declare(strict_types=1);

final class SubmissionController
{
    public function __construct(
        private TemplateEmailRepository $templateRepository,
        private SubmissionRepository $submissionRepository,
        private SeverityCalculator $severityCalculator,
        private WordPressPluginService $wordPressPluginService
    ) {
    }

    public function create(?string $flash = null, string $flashType = 'info'): void
    {
        View::render('submit-report', [
            'templates' => $this->templateRepository->all(),
            'flash' => $flash,
            'flashType' => $flashType,
        ]);
    }

    /** @return array{message: string, type: string} */
    public function store(array $post): array
    {
        $softwareType = trim((string)($post['software_type'] ?? 'other'));
        $normalizedSoftware = $this->normalizeSoftware(
            $softwareType,
            trim((string)($post['software_url'] ?? '')),
            trim((string)($post['software_name'] ?? '')),
            trim((string)($post['software_description'] ?? '')),
        );

        if (isset($normalizedSoftware['error'])) {
            return ['message' => $normalizedSoftware['error'], 'type' => 'danger'];
        }

        $software = $normalizedSoftware;

        $payload = [
            'submitter_name' => trim((string)($post['submitter_name'] ?? '')),
            'submitter_email' => trim((string)($post['submitter_email'] ?? '')),
            'submitter_role' => trim((string)($post['submitter_role'] ?? '')),
            'submission_comment' => trim((string)($post['submission_comment'] ?? '')),
            'wordpress_version' => trim((string)($post['wordpress_version'] ?? '')),
        ];

        if ($payload['submitter_name'] === '' || $payload['submitter_email'] === '') {
            return ['message' => 'Please fill all required fields.', 'type' => 'danger'];
        }

        if ($software['type'] === 'wp_plugin' && $payload['wordpress_version'] === '') {
            return ['message' => 'Version tested is required for plugin submissions.', 'type' => 'danger'];
        }

        $templates = $this->templateRepository->all();
        $tests = [];

        foreach ($templates as $template) {
            $key = 'result_' . $template['id'];
            $actualResult = (string)($post[$key] ?? 'not_tested');

            if (!in_array($actualResult, ['accepted', 'rejected', 'not_tested'], true) || $actualResult === 'not_tested') {
                continue;
            }

            $tests[] = [
                'template_id' => (int)$template['id'],
                'email_address' => (string)$template['email_address'],
                'expected_valid' => (int)$template['expected_valid'],
                'actual_result' => $actualResult,
                'severity_weight' => (int)$template['severity_weight'],
            ];
        }

        if ($tests === []) {
            return ['message' => 'Please record at least one test result.', 'type' => 'danger'];
        }

        $severity = $this->severityCalculator->calculate($tests);
        $softwareId = $this->submissionRepository->findOrCreateSoftware($software);
        $submissionId = $this->submissionRepository->createSubmission($softwareId, $payload, $tests, $severity);

        return [
            'message' => sprintf('Submission #%d saved. Auto severity: %s.', $submissionId, strtoupper($severity)),
            'type' => 'success',
        ];
    }

    /** @return array<string, mixed> */
    private function normalizeSoftware(string $softwareType, string $canonicalInput, string $softwareName, string $softwareDescription): array
    {
        if (!in_array($softwareType, ['wp_plugin', 'other'], true)) {
            $softwareType = 'other';
        }

        if ($softwareType === 'wp_plugin') {
            $slug = $this->extractPluginSlug($canonicalInput);
            if ($slug === null) {
                return ['error' => 'For WordPress plugins, enter a plugin slug or a valid WordPress.org plugin URL.'];
            }

            $pluginData = $this->wordPressPluginService->fetchBySlug($slug);
            if ($pluginData === null) {
                return ['error' => 'Could not fetch plugin details from the WordPress.org API. Please verify the plugin slug/URL.'];
            }

            return [
                'name' => $pluginData['name'],
                'canonical_url' => sprintf('https://wordpress.org/plugins/%s/', $slug),
                'type' => 'wp_plugin',
                'description' => $pluginData['description'],
                'slug' => $slug,
                'plugin_icon_url' => $pluginData['icon_url'],
                'plugin_banner_url' => $pluginData['banner_url'],
            ];
        }

        if ($softwareName === '' || $canonicalInput === '') {
            return ['error' => 'Please fill all required fields.'];
        }

        return [
            'name' => $softwareName,
            'canonical_url' => $this->normalizeGenericUrl($canonicalInput),
            'type' => 'other',
            'description' => $softwareDescription,
            'slug' => null,
            'plugin_icon_url' => null,
            'plugin_banner_url' => null,
        ];
    }

    private function normalizeGenericUrl(string $input): string
    {
        $trimmed = trim($input);
        if ($trimmed === '') {
            return '';
        }

        if (!preg_match('#^https?://#i', $trimmed)) {
            return 'https://' . ltrim($trimmed, '/');
        }

        return $trimmed;
    }

    private function extractPluginSlug(string $input): ?string
    {
        $value = trim(strtolower($input));
        if ($value === '') {
            return null;
        }

        if (preg_match('/^[a-z0-9][a-z0-9-]*$/', $value) === 1) {
            return $value;
        }

        if (!preg_match('#^https?://#', $value)) {
            $value = 'https://' . ltrim($value, '/');
        }

        $parts = parse_url($value);
        if (!is_array($parts) || !isset($parts['path'])) {
            return null;
        }

        if (preg_match('#/plugins/([a-z0-9-]+)/?#', $parts['path'], $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }
}
