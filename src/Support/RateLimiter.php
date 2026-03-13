<?php

declare(strict_types=1);

final class RateLimiter
{
    private string $cacheDir;

    public function __construct(?string $cacheDir = null)
    {
        $defaultDir = dirname(__DIR__, 2) . '/storage/cache/rate-limit';
        $this->cacheDir = $cacheDir !== null && trim($cacheDir) !== '' ? $cacheDir : $defaultDir;
    }

    public function allow(string $key, int $maxAttempts, int $windowSeconds): bool
    {
        if ($maxAttempts < 1 || $windowSeconds < 1) {
            return true;
        }

        if (!$this->ensureCacheDir()) {
            return true;
        }

        $safeKey = preg_replace('/[^a-z0-9-_]/i', '-', strtolower(trim($key)));
        if (!is_string($safeKey) || $safeKey === '') {
            return true;
        }

        $file = $this->cacheDir . '/' . $safeKey . '.json';
        $now = time();

        $handle = @fopen($file, 'c+');
        if (!is_resource($handle)) {
            return true;
        }

        $allowed = true;

        try {
            if (!flock($handle, LOCK_EX)) {
                return true;
            }

            $contents = stream_get_contents($handle);
            $payload = is_string($contents) && $contents !== '' ? json_decode($contents, true) : null;

            $attempts = [];
            if (is_array($payload) && isset($payload['attempts']) && is_array($payload['attempts'])) {
                foreach ($payload['attempts'] as $attemptTs) {
                    $ts = (int)$attemptTs;
                    if ($ts > 0 && ($now - $ts) < $windowSeconds) {
                        $attempts[] = $ts;
                    }
                }
            }

            if (count($attempts) >= $maxAttempts) {
                $allowed = false;
            } else {
                $attempts[] = $now;
            }

            $nextPayload = json_encode([
                'attempts' => $attempts,
            ]);

            if (is_string($nextPayload)) {
                rewind($handle);
                ftruncate($handle, 0);
                fwrite($handle, $nextPayload);
            }
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }

        return $allowed;
    }

    private function ensureCacheDir(): bool
    {
        if (is_dir($this->cacheDir)) {
            return true;
        }

        return mkdir($this->cacheDir, 0775, true) || is_dir($this->cacheDir);
    }
}
