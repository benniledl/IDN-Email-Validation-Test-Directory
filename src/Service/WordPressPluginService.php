<?php

declare(strict_types=1);

final class WordPressPluginService
{
    private const USER_AGENT = 'IDN-Validation-Directory/1.0';
    private const CACHE_TTL_SECONDS = 604800; // 7 days

    /** @return array{name: string, description: string, icon_url: ?string, icon_2x_url: ?string, banner_url: ?string, author: string, active_installs: string, tested: string}|null */
    public function fetchBySlug(string $slug): ?array
    {
        $endpoint = sprintf('https://api.wordpress.org/plugins/info/1.0/%s.json', rawurlencode($slug));
        $response = $this->request($endpoint);
        if ($response === null) {
            return null;
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded) || !isset($decoded['name'])) {
            return null;
        }

        $description = trim(strip_tags((string)($decoded['short_description'] ?? '')));
        $icon = $this->resolvePluginIcon($slug, $decoded['icons'] ?? null);
        $bannerUrl = $this->pickImageUrl($decoded['banners'] ?? null);
        $author = trim(strip_tags((string)($decoded['author'] ?? '')));
        $activeInstalls = $this->formatActiveInstalls($decoded['active_installs'] ?? null);
        $tested = trim((string)($decoded['tested'] ?? ''));

        return [
            'name' => trim((string)$decoded['name']),
            'description' => $description,
            'icon_url' => $icon['icon_url'],
            'icon_2x_url' => $icon['icon_2x_url'],
            'banner_url' => $bannerUrl,
            'author' => $author,
            'active_installs' => $activeInstalls,
            'tested' => $tested,
        ];
    }

    private function formatActiveInstalls(mixed $count): string
    {
        if (!is_int($count) && !is_numeric($count)) {
            return '';
        }

        $normalized = (int)$count;
        if ($normalized <= 0) {
            return '';
        }

        if ($normalized >= 1000000) {
            return sprintf('%s+ million active installations', number_format((int)floor($normalized / 1000000)));
        }

        if ($normalized >= 1000) {
            return sprintf('%s+ active installations', number_format((int)floor($normalized / 1000) * 1000));
        }

        return sprintf('%d active installations', $normalized);
    }

    /** @param mixed $icons
     *  @return array{icon_url: ?string, icon_2x_url: ?string}
     */
    private function resolvePluginIcon(string $slug, mixed $icons): array
    {
        $icon1xCandidates = [];
        $icon2xCandidates = [];

        if (is_array($icons)) {
            if (!empty($icons['svg']) && is_string($icons['svg'])) {
                $icon1xCandidates[] = $icons['svg'];
            }
            if (!empty($icons['1x']) && is_string($icons['1x'])) {
                $icon1xCandidates[] = $icons['1x'];
            }
            if (!empty($icons['default']) && is_string($icons['default'])) {
                $icon1xCandidates[] = $icons['default'];
            }
            if (!empty($icons['2x']) && is_string($icons['2x'])) {
                $icon2xCandidates[] = $icons['2x'];
                if ($icon1xCandidates === []) {
                    $icon1xCandidates[] = $icons['2x'];
                }
            }
        }

        $icon1xCandidates = array_merge($icon1xCandidates, [
            sprintf('https://ps.w.org/%s/assets/icon.svg', rawurlencode($slug)),
            sprintf('https://ps.w.org/%s/assets/icon-128x128.png', rawurlencode($slug)),
            sprintf('https://ps.w.org/%s/assets/icon-256x256.png', rawurlencode($slug)),
        ]);

        $icon2xCandidates = array_merge($icon2xCandidates, [
            sprintf('https://ps.w.org/%s/assets/icon-256x256.png', rawurlencode($slug)),
        ]);

        $icon1x = $this->firstReachableUrl($icon1xCandidates);
        $icon2x = $this->firstReachableUrl($icon2xCandidates);

        $local1x = $icon1x !== null ? $this->cacheIconLocally($slug, $icon1x, '1x') : null;
        $local2x = $icon2x !== null ? $this->cacheIconLocally($slug, $icon2x, '2x') : null;

        return [
            'icon_url' => $local1x ?? $icon1x,
            'icon_2x_url' => $local2x ?? $icon2x,
        ];
    }

    /** @param array<int, string> $candidates */
    private function firstReachableUrl(array $candidates): ?string
    {
        $seen = [];
        foreach ($candidates as $candidate) {
            $url = trim($candidate);
            if ($url === '' || isset($seen[$url])) {
                continue;
            }
            $seen[$url] = true;

            if ($this->remoteFileExists($url)) {
                return $url;
            }
        }

        return null;
    }

    private function cacheIconLocally(string $slug, string $iconUrl, string $variant): ?string
    {
        $extension = pathinfo((string)parse_url($iconUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
        $safeExtension = in_array(strtolower($extension), ['svg', 'png', 'jpg', 'jpeg', 'webp'], true)
            ? strtolower($extension)
            : 'png';

        $cacheDir = dirname(__DIR__, 2) . '/public/assets/plugin-icons';
        if (!is_dir($cacheDir) && !mkdir($cacheDir, 0775, true) && !is_dir($cacheDir)) {
            return null;
        }

        $base = preg_replace('/[^a-z0-9-]/', '-', strtolower($slug));
        $filename = sprintf('%s-%s.%s', trim((string)$base, '-'), $variant, $safeExtension);
        $absolutePath = $cacheDir . '/' . $filename;

        if (is_file($absolutePath) && (time() - (int)filemtime($absolutePath)) < self::CACHE_TTL_SECONDS) {
            return '/assets/plugin-icons/' . $filename;
        }

        $binary = $this->request($iconUrl);
        if ($binary === null || $binary === '') {
            return is_file($absolutePath) ? '/assets/plugin-icons/' . $filename : null;
        }

        if (@file_put_contents($absolutePath, $binary) === false) {
            return null;
        }

        return '/assets/plugin-icons/' . $filename;
    }

    private function remoteFileExists(string $url): bool
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_NOBODY => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 8,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_USERAGENT => self::USER_AGENT,
            ]);

            curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            curl_close($ch);

            return $status >= 200 && $status < 400;
        }

        $headers = @get_headers($url, true);
        if ($headers === false || !isset($headers[0])) {
            return false;
        }

        return str_contains((string)$headers[0], '200') || str_contains((string)$headers[0], '301') || str_contains((string)$headers[0], '302');
    }

    private function request(string $url): ?string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 8,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_USERAGENT => self::USER_AGENT,
            ]);

            $result = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            curl_close($ch);

            if (is_string($result) && $status >= 200 && $status < 300) {
                return $result;
            }
        }

        $context = stream_context_create([
            'http' => [
                'timeout' => 8,
                'ignore_errors' => true,
                'header' => 'User-Agent: ' . self::USER_AGENT . "\r\n",
            ],
        ]);

        $result = @file_get_contents($url, false, $context);

        return is_string($result) ? $result : null;
    }

    private function pickImageUrl(mixed $candidate): ?string
    {
        if (!is_array($candidate)) {
            return null;
        }

        foreach (['svg', '2x', '1x', 'default', 'high', 'low'] as $key) {
            if (!empty($candidate[$key]) && is_string($candidate[$key])) {
                return $candidate[$key];
            }
        }

        return null;
    }
}
