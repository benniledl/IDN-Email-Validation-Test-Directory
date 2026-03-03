<?php

declare(strict_types=1);

final class WordPressPluginService
{
    /** @return array{name: string, description: string, icon_url: ?string, banner_url: ?string, author: string, active_installs: string, tested: string}|null */
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
        $iconUrl = $this->pickImageUrl($decoded['icons'] ?? null);
        $bannerUrl = $this->pickImageUrl($decoded['banners'] ?? null);
        $author = trim(strip_tags((string)($decoded['author'] ?? '')));
        $activeInstalls = $this->formatActiveInstalls($decoded['active_installs'] ?? null);
        $tested = trim((string)($decoded['tested'] ?? ''));

        return [
            'name' => trim((string)$decoded['name']),
            'description' => $description,
            'icon_url' => $iconUrl,
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

    private function request(string $url): ?string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 8,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_USERAGENT => 'IDN-Validation-Directory/1.0',
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
                'header' => "User-Agent: IDN-Validation-Directory/1.0\r\n",
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
