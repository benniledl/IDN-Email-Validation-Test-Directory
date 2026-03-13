<?php

declare(strict_types=1);

final class SubmissionController
{
    public function __construct(
        private TemplateEmailRepository $templateRepository,
        private SubmissionRepository $submissionRepository,
        private DirectoryRepository $directoryRepository,
        private SeverityCalculator $severityCalculator,
        private WordPressPluginService $wordPressPluginService,
        private EmailValidator $emailValidator,
        private RateLimiter $rateLimiter
    ) {
    }

    public function create(?string $flash = null, string $flashType = 'info', array $old = []): void
    {
        View::render('submit-report', [
            'templates' => $this->templateRepository->all(),
            'playgroundLaunchUrl' => 'https://playground.wordpress.net/',
            'flash' => $flash,
            'flashType' => $flashType,
            'old' => $old,
        ]);
    }

    /** @return array{message: string, type: string, submission_id?: int} */
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

        $emailValidation = $this->emailValidator->validate($payload['submitter_email']);
        if (!$emailValidation['is_valid']) {
            return ['message' => $emailValidation['message'], 'type' => 'danger'];
        }

        $payload['submitter_email'] = $emailValidation['normalized'];

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
            'submission_id' => $submissionId,
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

        $normalizedExternalUrl = $this->normalizeExternalUrl($canonicalInput);
        if ($normalizedExternalUrl === null) {
            return ['error' => 'For external software, please enter a full URL starting with http:// or https://'];
        }

        return [
            'name' => $softwareName,
            'canonical_url' => $normalizedExternalUrl,
            'type' => 'other',
            'description' => $softwareDescription,
            'slug' => null,
            'plugin_icon_url' => null,
            'plugin_banner_url' => null,
        ];
    }

    private function normalizeExternalUrl(string $input): ?string
    {
        $trimmed = trim($input);
        if ($trimmed === '') {
            return null;
        }

        if (!preg_match('#^https?://#i', $trimmed)) {
            return null;
        }

        $parts = parse_url($trimmed);
        if (!is_array($parts) || empty($parts['host'])) {
            return null;
        }

        $scheme = strtolower((string)($parts['scheme'] ?? ''));
        if (!in_array($scheme, ['http', 'https'], true)) {
            return null;
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
        if (!is_array($parts) || !isset($parts['host'], $parts['path'])) {
            return null;
        }

        $host = strtolower((string)$parts['host']);
        if (!preg_match('/(^|\.)wordpress\.org$/', $host)) {
            return null;
        }

        $path = (string)$parts['path'];
        if (str_contains($path, '/plugin-install.php') || str_contains($path, '.zip')) {
            return null;
        }

        if (preg_match('#^/plugins/([a-z0-9-]+)/?$#', $path, $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }

    public function pluginVersionApi(array $query): void
    {
        $slug = $this->extractPluginSlug((string)($query['slug'] ?? ''));
        $version = null;

        if ($slug !== null && $this->allowPluginProxyRequest()) {
            $version = $this->wordPressPluginService->fetchLatestVersionBySlug($slug);
        }

        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        echo json_encode([
            'slug' => $slug,
            'version' => $version,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function pluginSlugSuggestionsApi(array $query): void
    {
        $needle = trim(strtolower((string)($query['q'] ?? '')));
        $suggestions = [];

        if (strlen($needle) >= 4) {
            $dbSuggestions = $this->directoryRepository->wpPluginSlugSuggestions($needle, 8);
            $cachedSuggestions = $this->wordPressPluginService->cachedSlugSuggestions($needle, 8);

            $merged = [];
            foreach (array_merge($dbSuggestions, $cachedSuggestions) as $item) {
                $slug = strtolower(trim((string)($item['slug'] ?? '')));
                if ($slug === '' || isset($merged[$slug])) {
                    continue;
                }

                $name = trim((string)($item['name'] ?? ''));
                $name = trim(html_entity_decode(strip_tags($name), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                $merged[$slug] = [
                    'slug' => $slug,
                    'name' => $name === '' ? $slug : $name,
                ];
            }

            $suggestions = array_slice(array_values($merged), 0, 8);
        }

        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'suggestions' => $suggestions,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function allowPluginProxyRequest(): bool
    {
        $sessionAllowed = $this->rateLimiter->allow('wp-plugin-version-session-' . session_id(), 24, 600);
        if (!$sessionAllowed) {
            return false;
        }

        return $this->rateLimiter->allow('wp-plugin-version-global', 240, 3600);
    }

    public function validateEmailApi(array $post): void
    {
        $email = trim((string)($post['email'] ?? ''));
        $result = $this->emailValidator->validate($email);

        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'is_valid' => $result['is_valid'],
            'normalized' => $result['normalized'],
            'message' => $result['message'],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
