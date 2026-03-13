<?php

declare(strict_types=1);

final class CommentSpamService
{
    private const COMMENT_FORM_SESSION_KEY = 'comment_form_tokens';
    private const COMMENT_RATE_LIMIT_SESSION_KEY = 'comment_rate_limit';
    private const COMMENT_MIN_SECONDS = 3;
    private const COMMENT_MAX_SECONDS = 7200;
    private const COMMENT_COOLDOWN_SECONDS = 20;
    private const COMMENT_HOURLY_LIMIT = 100;

    public function issueGuardToken(string $context): string
    {
        $forms = is_array($_SESSION[self::COMMENT_FORM_SESSION_KEY] ?? null)
            ? $_SESSION[self::COMMENT_FORM_SESSION_KEY]
            : [];

        $token = bin2hex(random_bytes(16));
        $forms[$context] = [
            'token' => $token,
            'issued_at' => time(),
        ];
        $_SESSION[self::COMMENT_FORM_SESSION_KEY] = $forms;

        return $token;
    }

    public function clearGuardToken(string $context): void
    {
        $forms = is_array($_SESSION[self::COMMENT_FORM_SESSION_KEY] ?? null)
            ? $_SESSION[self::COMMENT_FORM_SESSION_KEY]
            : [];

        unset($forms[$context]);
        $_SESSION[self::COMMENT_FORM_SESSION_KEY] = $forms;
    }

    public function validate(string $context, array $post, string $name, string $comment): ?string
    {
        $honeypot = trim((string)($post['website'] ?? ''));
        if ($honeypot !== '') {
            return 'Comment could not be posted. Please try again.';
        }

        $token = trim((string)($post['comment_guard'] ?? ''));
        $forms = is_array($_SESSION[self::COMMENT_FORM_SESSION_KEY] ?? null)
            ? $_SESSION[self::COMMENT_FORM_SESSION_KEY]
            : [];
        $guard = $forms[$context] ?? null;

        if (!is_array($guard) || $token === '' || !hash_equals((string)($guard['token'] ?? ''), $token)) {
            return 'Comment form expired. Please refresh and try again.';
        }

        $age = time() - (int)($guard['issued_at'] ?? 0);
        if ($age < self::COMMENT_MIN_SECONDS) {
            return 'Please wait a few seconds before posting your comment.';
        }

        if ($age > self::COMMENT_MAX_SECONDS) {
            return 'Comment form expired. Please refresh and try again.';
        }

        $rateError = $this->rateLimitError($name, $comment);
        if ($rateError !== null) {
            return $rateError;
        }

        if ($name === '' || $comment === '') {
            return null;
        }

        $nameLength = function_exists('mb_strlen') ? mb_strlen($name, 'UTF-8') : strlen($name);
        if ($nameLength < 2 || $nameLength > 60) {
            return 'Comment name must be between 2 and 60 characters.';
        }

        if (preg_match('/https?:\/\//i', $name) === 1 || preg_match('/www\./i', $name) === 1) {
            return 'Comment name cannot contain links.';
        }

        $commentLength = function_exists('mb_strlen') ? mb_strlen($comment, 'UTF-8') : strlen($comment);
        if ($commentLength < 5 || $commentLength > 2000) {
            return 'Comment must be between 5 and 2000 characters.';
        }

        if (strip_tags($comment) !== $comment) {
            return 'Comment cannot contain HTML.';
        }

        $urlMatches = preg_match_all('/(?:https?:\/\/|www\.)/i', $comment);
        if (is_int($urlMatches) && $urlMatches > 1) {
            return 'Please keep comments link-light (max one URL).';
        }

        if (preg_match('/(.)\1{8,}/u', $comment) === 1) {
            return 'Comment appears to contain repeated spam text.';
        }

        $spamPhrases = [
            'buy now',
            'best price',
            'whatsapp',
            'telegram',
            'casino',
            'crypto',
            'backlink',
            'seo service',
            'loan offer',
        ];
        $normalizedComment = strtolower($comment);
        foreach ($spamPhrases as $phrase) {
            if (str_contains($normalizedComment, $phrase)) {
                return 'Comment was blocked by spam protection.';
            }
        }

        return null;
    }

    public function recordAccepted(string $name, string $comment): void
    {
        $now = time();
        $rate = is_array($_SESSION[self::COMMENT_RATE_LIMIT_SESSION_KEY] ?? null)
            ? $_SESSION[self::COMMENT_RATE_LIMIT_SESSION_KEY]
            : [];

        $timestamps = [];
        foreach ((array)($rate['timestamps'] ?? []) as $timestamp) {
            $value = (int)$timestamp;
            if ($value > $now - 3600) {
                $timestamps[] = $value;
            }
        }
        $timestamps[] = $now;

        $hashes = [];
        foreach ((array)($rate['hashes'] ?? []) as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $timestamp = (int)($entry['time'] ?? 0);
            $hash = (string)($entry['hash'] ?? '');
            if ($timestamp > $now - 3600 && $hash !== '') {
                $hashes[] = ['time' => $timestamp, 'hash' => $hash];
            }
        }

        $hashes[] = [
            'time' => $now,
            'hash' => hash('sha256', strtolower(trim($name)) . '|' . strtolower(trim($comment))),
        ];

        $_SESSION[self::COMMENT_RATE_LIMIT_SESSION_KEY] = [
            'timestamps' => $timestamps,
            'hashes' => $hashes,
            'last' => $now,
        ];
    }

    private function rateLimitError(string $name, string $comment): ?string
    {
        $now = time();
        $rate = is_array($_SESSION[self::COMMENT_RATE_LIMIT_SESSION_KEY] ?? null)
            ? $_SESSION[self::COMMENT_RATE_LIMIT_SESSION_KEY]
            : [];

        $timestamps = [];
        foreach ((array)($rate['timestamps'] ?? []) as $timestamp) {
            $value = (int)$timestamp;
            if ($value > $now - 3600) {
                $timestamps[] = $value;
            }
        }

        $hashes = [];
        foreach ((array)($rate['hashes'] ?? []) as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $timestamp = (int)($entry['time'] ?? 0);
            $hash = (string)($entry['hash'] ?? '');
            if ($timestamp > $now - 3600 && $hash !== '') {
                $hashes[] = ['time' => $timestamp, 'hash' => $hash];
            }
        }

        $last = (int)($rate['last'] ?? 0);
        if ($last > 0 && ($now - $last) < self::COMMENT_COOLDOWN_SECONDS) {
            $wait = self::COMMENT_COOLDOWN_SECONDS - ($now - $last);
            return 'Please wait ' . $wait . ' seconds before posting another comment.';
        }

        if (count($timestamps) >= self::COMMENT_HOURLY_LIMIT) {
            return 'Too many comments in a short period. Please try again later.';
        }

        if ($name !== '' || $comment !== '') {
            $hash = hash('sha256', strtolower(trim($name)) . '|' . strtolower(trim($comment)));
            foreach ($hashes as $entry) {
                if (hash_equals((string)$entry['hash'], $hash)) {
                    return 'Duplicate comment detected. Please avoid reposting the same content.';
                }
            }
        }

        $_SESSION[self::COMMENT_RATE_LIMIT_SESSION_KEY] = [
            'timestamps' => $timestamps,
            'hashes' => $hashes,
            'last' => $last,
        ];

        return null;
    }
}
