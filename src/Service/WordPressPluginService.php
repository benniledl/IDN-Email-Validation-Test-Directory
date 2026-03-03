<?php

declare(strict_types=1);

final class WordPressPluginService
{
    private const USER_AGENT = 'IDN-Validation-Directory/1.0';
    private const CACHE_TTL_SECONDS = 604800; // 7 days
    private const META_CACHE_DIR = '/storage/cache/wp-plugin-meta';

    /** @return array{name: string, description: string, icon_url: ?string, icon_2x_url: ?string, banner_url: ?string, banner_2x_url: ?string, author: string, active_installs: string, tested: string}|null */
    public function fetchBySlug(string $slug): ?array
    {
        $normalizedSlug = trim(strtolower($slug));
        if ($normalizedSlug === '') {
            return null;
        }

        $cached = $this->readCachedPluginMeta($normalizedSlug);
        if ($cached !== null) {
            return $cached;
        }

        $endpoint = sprintf('https://api.wordpress.org/plugins/info/1.0/%s.json', rawurlencode($normalizedSlug));
        $response = $this->request($endpoint);
        if ($response === null) {
            return null;
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded) || !isset($decoded['name'])) {
            return null;
        }

        $description = trim(strip_tags((string)($decoded['short_description'] ?? '')));
        $icon = $this->resolvePluginIcon($normalizedSlug, $decoded['icons'] ?? null);
        $banner = $this->resolvePluginBanner($normalizedSlug, $decoded['banners'] ?? null);
        $author = trim(strip_tags((string)($decoded['author'] ?? '')));
        $activeInstalls = $this->formatActiveInstalls($decoded['active_installs'] ?? null);
        $tested = trim((string)($decoded['tested'] ?? ''));

        $result = [
            'name' => trim((string)$decoded['name']),
            'description' => $description,
            'icon_url' => $icon['url'],
            'icon_2x_url' => $icon['url_2x'],
            'banner_url' => $banner['url'],
            'banner_2x_url' => $banner['url_2x'],
            'author' => $author,
            'active_installs' => $activeInstalls,
            'tested' => $tested,
        ];

        $this->writeCachedPluginMeta($normalizedSlug, $result);

        return $result;
    }

    /** @return array{name: string, description: string, icon_url: ?string, icon_2x_url: ?string, banner_url: ?string, banner_2x_url: ?string, author: string, active_installs: string, tested: string}|null */
    private function readCachedPluginMeta(string $slug): ?array
    {
        $cacheFile = $this->metaCacheFile($slug);
        if (!is_file($cacheFile)) {
            return null;
        }

        $contents = @file_get_contents($cacheFile);
        if (!is_string($contents) || $contents === '') {
            return null;
        }

        $decoded = json_decode($contents, true);
        if (!is_array($decoded) || !isset($decoded['cached_at'], $decoded['data']) || !is_array($decoded['data'])) {
            return null;
        }

        if ((time() - (int)$decoded['cached_at']) >= self::CACHE_TTL_SECONDS) {
            return null;
        }

        if (!isset($decoded['data']['name'])) {
            return null;
        }

        /** @var array{name: string, description: string, icon_url: ?string, icon_2x_url: ?string, banner_url: ?string, banner_2x_url: ?string, author: string, active_installs: string, tested: string} $data */
        $data = $decoded['data'];

        return $data;
    }

    /** @param array{name: string, description: string, icon_url: ?string, icon_2x_url: ?string, banner_url: ?string, banner_2x_url: ?string, author: string, active_installs: string, tested: string} $data */
    private function writeCachedPluginMeta(string $slug, array $data): void
    {
        $cacheDir = dirname(__DIR__, 2) . self::META_CACHE_DIR;
        if (!is_dir($cacheDir) && !mkdir($cacheDir, 0775, true) && !is_dir($cacheDir)) {
            return;
        }

        $payload = json_encode([
            'cached_at' => time(),
            'data' => $data,
        ]);

        if (!is_string($payload)) {
            return;
        }

        @file_put_contents($this->metaCacheFile($slug), $payload);
    }

    private function metaCacheFile(string $slug): string
    {
        $cacheDir = dirname(__DIR__, 2) . self::META_CACHE_DIR;
        $safeSlug = preg_replace('/[^a-z0-9-]/', '-', strtolower($slug));

        return $cacheDir . '/' . trim((string)$safeSlug, '-') . '.json';
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
     *  @return array{url: ?string, url_2x: ?string}
     */
    private function resolvePluginIcon(string $slug, mixed $icons): array
    {
        $oneX = [];
        $twoX = [];

        if (is_array($icons)) {
            if (!empty($icons['svg']) && is_string($icons['svg'])) {
                $oneX[] = $icons['svg'];
            }
            if (!empty($icons['1x']) && is_string($icons['1x'])) {
                $oneX[] = $icons['1x'];
            }
            if (!empty($icons['default']) && is_string($icons['default'])) {
                $oneX[] = $icons['default'];
            }
            if (!empty($icons['2x']) && is_string($icons['2x'])) {
                $twoX[] = $icons['2x'];
                if ($oneX === []) {
                    $oneX[] = $icons['2x'];
                }
            }
        }

        $slugEncoded = rawurlencode($slug);
        $oneX = array_merge($oneX, [
            sprintf('https://ps.w.org/%s/assets/icon.svg', $slugEncoded),
            sprintf('https://ps.w.org/%s/assets/icon-128x128.png', $slugEncoded),
            sprintf('https://ps.w.org/%s/assets/icon-128x128.jpg', $slugEncoded),
            sprintf('https://ps.w.org/%s/assets/icon-128x128.jpeg', $slugEncoded),
            sprintf('https://ps.w.org/%s/assets/icon-128x128.webp', $slugEncoded),
            sprintf('https://ps.w.org/%s/assets/icon-128x128.gif', $slugEncoded),
            sprintf('https://ps.w.org/%s/assets/icon-256x256.png', $slugEncoded),
            sprintf('https://ps.w.org/%s/assets/icon-256x256.jpg', $slugEncoded),
            sprintf('https://ps.w.org/%s/assets/icon-256x256.jpeg', $slugEncoded),
            sprintf('https://ps.w.org/%s/assets/icon-256x256.webp', $slugEncoded),
            sprintf('https://ps.w.org/%s/assets/icon-256x256.gif', $slugEncoded),
        ]);

        $twoX = array_merge($twoX, [
            sprintf('https://ps.w.org/%s/assets/icon-256x256.png', $slugEncoded),
            sprintf('https://ps.w.org/%s/assets/icon-256x256.jpg', $slugEncoded),
            sprintf('https://ps.w.org/%s/assets/icon-256x256.jpeg', $slugEncoded),
            sprintf('https://ps.w.org/%s/assets/icon-256x256.webp', $slugEncoded),
            sprintf('https://ps.w.org/%s/assets/icon-256x256.gif', $slugEncoded),
        ]);

        return $this->resolveAssetVariant($slug, 'icon', $oneX, $twoX, '1x', '2x');
    }

    /** @param mixed $banners
     *  @return array{url: ?string, url_2x: ?string}
     */
    private function resolvePluginBanner(string $slug, mixed $banners): array
    {
        $oneX = [];
        $twoX = [];

        if (is_array($banners)) {
            if (!empty($banners['low']) && is_string($banners['low'])) {
                $oneX[] = $banners['low'];
            }
            if (!empty($banners['high']) && is_string($banners['high'])) {
                $twoX[] = $banners['high'];
                if ($oneX === []) {
                    $oneX[] = $banners['high'];
                }
            }
        }

        $slugEncoded = rawurlencode($slug);
        $oneX = array_merge($oneX, [
            sprintf('https://ps.w.org/%s/assets/banner-772x250.png', $slugEncoded),
            sprintf('https://ps.w.org/%s/assets/banner-1544x500.png', $slugEncoded),
        ]);

        $twoX = array_merge($twoX, [
            sprintf('https://ps.w.org/%s/assets/banner-1544x500.png', $slugEncoded),
        ]);

        return $this->resolveAssetVariant($slug, 'banner', $oneX, $twoX, '772w', '1544w');
    }

    /**
     * @param array<int, string> $oneXCandidates
     * @param array<int, string> $twoXCandidates
     * @return array{url: ?string, url_2x: ?string}
     */
    private function resolveAssetVariant(string $slug, string $assetPrefix, array $oneXCandidates, array $twoXCandidates, string $variant1x, string $variant2x): array
    {
        $oneX = $this->firstReachableUrl($oneXCandidates);
        $twoX = $this->firstReachableUrl($twoXCandidates);

        $local1x = $oneX !== null ? $this->cacheAssetLocally($slug, $assetPrefix, $oneX, $variant1x) : null;
        $local2x = $twoX !== null ? $this->cacheAssetLocally($slug, $assetPrefix, $twoX, $variant2x) : null;

        return [
            'url' => $local1x ?? $oneX,
            'url_2x' => $local2x ?? $twoX,
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

    private function cacheAssetLocally(string $slug, string $assetPrefix, string $assetUrl, string $variant): ?string
    {
        $extension = pathinfo((string)parse_url($assetUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
        $safeExtension = in_array(strtolower($extension), ['svg', 'png', 'jpg', 'jpeg', 'webp', 'gif'], true)
            ? strtolower($extension)
            : 'png';

        $cacheDir = dirname(__DIR__, 2) . '/public/assets/plugin-assets';
        if (!is_dir($cacheDir) && !mkdir($cacheDir, 0775, true) && !is_dir($cacheDir)) {
            return null;
        }

        $base = preg_replace('/[^a-z0-9-]/', '-', strtolower($slug));
        $filename = sprintf('%s-%s-%s.%s', trim((string)$base, '-'), $assetPrefix, $variant, $safeExtension);
        $absolutePath = $cacheDir . '/' . $filename;

        if (is_file($absolutePath) && (time() - (int)filemtime($absolutePath)) < self::CACHE_TTL_SECONDS) {
            return '/assets/plugin-assets/' . $filename;
        }

        $binary = $this->request($assetUrl);
        if ($binary === null || $binary === '') {
            return is_file($absolutePath) ? '/assets/plugin-assets/' . $filename : null;
        }

        if (@file_put_contents($absolutePath, $binary) === false) {
            return null;
        }

        return '/assets/plugin-assets/' . $filename;
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
}
