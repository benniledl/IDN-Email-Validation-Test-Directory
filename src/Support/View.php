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

        require __DIR__ . '/../../views/layout.php';
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
