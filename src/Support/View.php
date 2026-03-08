<?php

declare(strict_types=1);

final class View
{
    /**
     * @param array<string, mixed> $data
     */
    public static function render(string $template, array $data = []): void
    {
        extract($data, EXTR_SKIP);

        ob_start();
        require __DIR__ . '/../../views/' . $template . '.php';
        $content = (string)ob_get_clean();
        $breadcrumbs = self::buildBreadcrumbs($data);

        require __DIR__ . '/../../views/layout.php';
    }

    /**
     * @param array<string, mixed> $data
     * @return array<int, array{label: string, url: string, current: bool}>
     */
    private static function buildBreadcrumbs(array $data): array
    {
        $path = parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
        $trimmedPath = trim($path, '/');
        $segments = $trimmedPath === '' ? [] : explode('/', $trimmedPath);

        $breadcrumbs = [
            [
                'label' => 'Home',
                'url' => '/',
                'current' => $segments === [],
            ],
        ];

        $cursor = '';
        $count = count($segments);

        foreach ($segments as $index => $segment) {
            $cursor .= '/' . $segment;
            $isCurrent = $index === $count - 1;
            $previous = $index > 0 ? $segments[$index - 1] : '';

            $breadcrumbs[] = [
                'label' => self::breadcrumbLabelForSegment($cursor, $segment, $previous, $data),
                'url' => $cursor,
                'current' => $isCurrent,
            ];
        }

        return $breadcrumbs;
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function breadcrumbLabelForSegment(string $path, string $segment, string $previous, array $data): string
    {
        $knownLabels = [
            '/about' => 'About',
            '/submit-report' => 'Submit report',
            '/software' => 'Software',
            '/reports' => 'Reports',
            '/admin' => 'Admin',
            '/admin/login' => 'Admin login',
            '/admin/users' => 'Users',
        ];

        if (isset($knownLabels[$path])) {
            return $knownLabels[$path];
        }

        if (preg_match('/^\d+$/', $segment) === 1) {
            if ($previous === 'software') {
                $softwareName = trim((string)($data['software']['name'] ?? ''));
                if ($softwareName !== '') {
                    $softwareName = html_entity_decode($softwareName, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                }
                return $softwareName !== '' ? $softwareName : 'Software #' . $segment;
            }

            if ($previous === 'reports') {
                $reportId = (int)($data['report']['id'] ?? 0);
                return $reportId > 0 ? 'Report #' . $reportId : 'Report #' . $segment;
            }

            return '#' . $segment;
        }

        $normalized = str_replace(['-', '_'], ' ', strtolower($segment));
        return ucwords($normalized);
    }

    public static function timeAgo(?string $timestamp): string
    {
        if ($timestamp === null || trim($timestamp) === '') {
            return '';
        }

        try {
            $time = new DateTimeImmutable($timestamp);
            $now = new DateTimeImmutable('now');
        } catch (Throwable) {
            return $timestamp;
        }

        $delta = $now->getTimestamp() - $time->getTimestamp();
        if ($delta < 0) {
            return 'just now';
        }

        if ($delta < 15) {
            return 'just now';
        }

        if ($delta < 60) {
            return $delta . 's ago';
        }

        if ($delta < 3600) {
            $minutes = (int)floor($delta / 60);
            return $minutes . ' min ago';
        }

        if ($delta < 86400) {
            $hours = (int)floor($delta / 3600);
            return $hours . ' hour' . ($hours === 1 ? '' : 's') . ' ago';
        }

        if ($delta < 172800) {
            return 'yesterday';
        }

        if ($delta < 604800) {
            $days = (int)floor($delta / 86400);
            return $days . ' days ago';
        }

        return $time->format('M j, Y');
    }
}
