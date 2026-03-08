<?php

declare(strict_types=1);

final class EmailValidator
{
    private const PUNYCODE_BASE = 36;
    private const PUNYCODE_TMIN = 1;
    private const PUNYCODE_TMAX = 26;
    private const PUNYCODE_SKEW = 38;
    private const PUNYCODE_DAMP = 700;
    private const PUNYCODE_INITIAL_BIAS = 72;
    private const PUNYCODE_INITIAL_N = 128;

    /**
     * @return array{is_valid: bool, normalized: string, message: string}
     */
    public function validate(string $email): array
    {
        $email = trim($email);

        if ($email === '') {
            return [
                'is_valid' => false,
                'normalized' => '',
                'message' => 'Email is required.',
            ];
        }

        $parts = $this->splitMailbox($email);
        if ($parts === null) {
            return [
                'is_valid' => false,
                'normalized' => $email,
                'message' => 'Email must contain a single unquoted @ symbol.',
            ];
        }

        [$localPart, $domain] = $parts;

        if (!$this->validateLocalPart($localPart)) {
            return [
                'is_valid' => false,
                'normalized' => $email,
                'message' => 'Email local part is invalid.',
            ];
        }

        $domainValidation = $this->validateDomain($domain);
        if (!$domainValidation['is_valid']) {
            return [
                'is_valid' => false,
                'normalized' => $email,
                'message' => $domainValidation['message'],
            ];
        }

        return [
            'is_valid' => true,
            'normalized' => $localPart . '@' . $domainValidation['normalized_domain'],
            'message' => 'Valid email format.',
        ];
    }

    /**
     * @return array{0: string, 1: string}|null
     */
    private function splitMailbox(string $email): ?array
    {
        $atPosition = -1;
        $inQuotes = false;
        $escaped = false;
        $length = strlen($email);

        for ($index = 0; $index < $length; $index++) {
            $char = $email[$index];

            if ($escaped) {
                $escaped = false;
                continue;
            }

            if ($inQuotes && $char === '\\') {
                $escaped = true;
                continue;
            }

            if ($char === '"') {
                $inQuotes = !$inQuotes;
                continue;
            }

            if ($char === '@' && !$inQuotes) {
                if ($atPosition !== -1) {
                    return null;
                }

                $atPosition = $index;
            }
        }

        if ($inQuotes || $atPosition <= 0 || $atPosition >= $length - 1) {
            return null;
        }

        return [
            substr($email, 0, $atPosition),
            substr($email, $atPosition + 1),
        ];
    }

    private function validateLocalPart(string $localPart): bool
    {
        if ($localPart === '' || strlen($localPart) > 64) {
            return false;
        }

        if (str_starts_with($localPart, '"') || str_ends_with($localPart, '"')) {
            return $this->validateQuotedLocalPart($localPart);
        }

        return $this->validateDotAtomLocalPart($localPart);
    }

    private function validateDotAtomLocalPart(string $localPart): bool
    {
        if ($localPart === '' || str_starts_with($localPart, '.') || str_ends_with($localPart, '.') || str_contains($localPart, '..')) {
            return false;
        }

        $atoms = explode('.', $localPart);
        foreach ($atoms as $atom) {
            if ($atom === '') {
                return false;
            }

            $chars = $this->unicodeChars($atom);
            if ($chars === []) {
                return false;
            }

            foreach ($chars as $char) {
                if (strlen($char) === 1 && ord($char) < 128) {
                    $ascii = ord($char);
                    if (
                        ($ascii >= 48 && $ascii <= 57)
                        || ($ascii >= 65 && $ascii <= 90)
                        || ($ascii >= 97 && $ascii <= 122)
                        || str_contains("!#$%&'*+/=?^_`{|}~-", $char)
                    ) {
                        continue;
                    }

                    return false;
                }

                if (preg_match('/[\p{C}\p{Z}]/u', $char) === 1) {
                    return false;
                }
            }
        }

        return true;
    }

    private function validateQuotedLocalPart(string $localPart): bool
    {
        if (strlen($localPart) < 2 || !str_starts_with($localPart, '"') || !str_ends_with($localPart, '"')) {
            return false;
        }

        $inner = substr($localPart, 1, -1);
        if ($inner !== '' && $this->unicodeChars($inner) === []) {
            return false;
        }

        $escaped = false;

        foreach ($this->unicodeChars($inner) as $char) {
            if ($escaped) {
                if (preg_match('/\p{C}/u', $char) === 1) {
                    return false;
                }

                $escaped = false;
                continue;
            }

            if ($char === '\\') {
                $escaped = true;
                continue;
            }

            if ($char === '"' || preg_match('/\p{C}/u', $char) === 1) {
                return false;
            }
        }

        return !$escaped;
    }

    /**
     * @return array{is_valid: bool, normalized_domain: string, message: string}
     */
    private function validateDomain(string $domain): array
    {
        $domain = trim($domain);
        if ($domain === '') {
            return [
                'is_valid' => false,
                'normalized_domain' => '',
                'message' => 'Email domain is required.',
            ];
        }

        if (str_starts_with($domain, '[') || str_ends_with($domain, ']')) {
            return [
                'is_valid' => false,
                'normalized_domain' => '',
                'message' => 'Address-literal domains are not allowed in this project.',
            ];
        }

        if (str_starts_with($domain, '.') || str_ends_with($domain, '.') || str_contains($domain, '..')) {
            return [
                'is_valid' => false,
                'normalized_domain' => '',
                'message' => 'Email domain format is invalid.',
            ];
        }

        $labels = explode('.', $domain);
        if (count($labels) < 2) {
            return [
                'is_valid' => false,
                'normalized_domain' => '',
                'message' => 'Email domain must be fully-qualified (for example: example.com).',
            ];
        }

        $normalizedLabels = [];

        foreach ($labels as $label) {
            if ($label === '') {
                return [
                    'is_valid' => false,
                    'normalized_domain' => '',
                    'message' => 'Email domain format is invalid.',
                ];
            }

            $hasNonAscii = preg_match('/[^\x00-\x7F]/', $label) === 1;
            if ($hasNonAscii && $this->violatesIdnaHyphenRestrictions($label)) {
                return [
                    'is_valid' => false,
                    'normalized_domain' => '',
                    'message' => 'Email domain label format is invalid.',
                ];
            }

            $asciiLabel = $hasNonAscii ? $this->unicodeLabelToAscii($label) : strtolower($label);
            if ($asciiLabel === null || !$this->isValidAsciiDomainLabel($asciiLabel)) {
                return [
                    'is_valid' => false,
                    'normalized_domain' => '',
                    'message' => 'Email domain contains an invalid label.',
                ];
            }

            if (str_starts_with($asciiLabel, 'xn--') && !$this->isValidPunycodeLabel(substr($asciiLabel, 4))) {
                return [
                    'is_valid' => false,
                    'normalized_domain' => '',
                    'message' => 'Email domain contains an invalid IDN label.',
                ];
            }

            $normalizedLabels[] = $asciiLabel;
        }

        $normalizedDomain = implode('.', $normalizedLabels);
        if (strlen($normalizedDomain) > 253) {
            return [
                'is_valid' => false,
                'normalized_domain' => '',
                'message' => 'Email domain is too long.',
            ];
        }

        return [
            'is_valid' => true,
            'normalized_domain' => $normalizedDomain,
            'message' => 'Valid domain.',
        ];
    }

    private function violatesIdnaHyphenRestrictions(string $label): bool
    {
        $chars = $this->unicodeChars($label);
        $count = count($chars);
        if ($count === 0) {
            return true;
        }

        if ($chars[0] === '-' || $chars[$count - 1] === '-') {
            return true;
        }

        return $count >= 4 && $chars[2] === '-' && $chars[3] === '-';
    }

    private function unicodeLabelToAscii(string $label): ?string
    {
        if (function_exists('idn_to_ascii')) {
            $flags = 0;
            if (defined('IDNA_NONTRANSITIONAL_TO_ASCII')) {
                $flags |= IDNA_NONTRANSITIONAL_TO_ASCII;
            }
            if (defined('IDNA_CHECK_BIDI')) {
                $flags |= IDNA_CHECK_BIDI;
            }
            if (defined('IDNA_CHECK_CONTEXTJ')) {
                $flags |= IDNA_CHECK_CONTEXTJ;
            }
            if (defined('IDNA_USE_STD3_RULES')) {
                $flags |= IDNA_USE_STD3_RULES;
            }

            $variant = defined('INTL_IDNA_VARIANT_UTS46') ? INTL_IDNA_VARIANT_UTS46 : 0;
            $asciiLabel = @idn_to_ascii($label, $flags, $variant);
            if (is_string($asciiLabel) && $asciiLabel !== '') {
                return strtolower($asciiLabel);
            }
        }

        $punycode = $this->encodePunycode($label);
        if ($punycode === null || $punycode === '') {
            return null;
        }

        return 'xn--' . strtolower($punycode);
    }

    private function isValidAsciiDomainLabel(string $label): bool
    {
        if ($label === '' || strlen($label) > 63) {
            return false;
        }

        if ($label[0] === '-' || $label[strlen($label) - 1] === '-') {
            return false;
        }

        return preg_match('/^[a-z0-9-]+$/', $label) === 1;
    }

    private function isValidPunycodeLabel(string $payload): bool
    {
        if ($payload === '' || str_starts_with($payload, '-') || str_ends_with($payload, '-')) {
            return false;
        }

        $decoded = $this->decodePunycode($payload);
        if ($decoded === null || $decoded === '') {
            return false;
        }

        $reencoded = $this->encodePunycode($decoded);
        return $reencoded !== null && strtolower($reencoded) === strtolower($payload);
    }

    private function encodePunycode(string $input): ?string
    {
        $codePoints = $this->unicodeCodePoints($input);
        if ($codePoints === null || $codePoints === []) {
            return null;
        }

        $output = '';
        $basicCount = 0;

        foreach ($codePoints as $codePoint) {
            if ($codePoint < 0x80) {
                $char = chr($codePoint);
                if (!preg_match('/^[A-Za-z0-9-]$/', $char)) {
                    return null;
                }

                $output .= strtolower($char);
                $basicCount++;
            }
        }

        $handledCount = $basicCount;
        if ($basicCount > 0) {
            $output .= '-';
        }

        $n = self::PUNYCODE_INITIAL_N;
        $delta = 0;
        $bias = self::PUNYCODE_INITIAL_BIAS;
        $inputLength = count($codePoints);

        while ($handledCount < $inputLength) {
            $m = PHP_INT_MAX;
            foreach ($codePoints as $codePoint) {
                if ($codePoint >= $n && $codePoint < $m) {
                    $m = $codePoint;
                }
            }

            if ($m === PHP_INT_MAX) {
                return null;
            }

            $step = ($m - $n) * ($handledCount + 1);
            if ($step < 0 || $delta > PHP_INT_MAX - $step) {
                return null;
            }

            $delta += $step;
            $n = $m;

            foreach ($codePoints as $codePoint) {
                if ($codePoint < $n) {
                    $delta++;
                    if ($delta < 0) {
                        return null;
                    }

                    continue;
                }

                if ($codePoint !== $n) {
                    continue;
                }

                $q = $delta;
                for ($k = self::PUNYCODE_BASE; ; $k += self::PUNYCODE_BASE) {
                    $t = $this->punycodeThreshold($k, $bias);
                    if ($q < $t) {
                        break;
                    }

                    $baseMinusT = self::PUNYCODE_BASE - $t;
                    $output .= $this->punycodeEncodeDigit($t + (($q - $t) % $baseMinusT));
                    $q = intdiv($q - $t, $baseMinusT);
                }

                $output .= $this->punycodeEncodeDigit($q);
                $bias = $this->punycodeAdapt($delta, $handledCount + 1, $handledCount === $basicCount);
                $delta = 0;
                $handledCount++;
            }

            $delta++;
            $n++;
        }

        return $output;
    }

    private function decodePunycode(string $input): ?string
    {
        if ($input === '' || preg_match('/^[a-z0-9-]+$/i', $input) !== 1) {
            return null;
        }

        $input = strtolower($input);
        $delimiterPosition = strrpos($input, '-');

        $codePoints = [];
        $index = 0;

        if ($delimiterPosition !== false) {
            $basic = substr($input, 0, $delimiterPosition);
            if ($basic !== '') {
                foreach (str_split($basic) as $char) {
                    if (!preg_match('/^[a-z0-9]$/', $char)) {
                        return null;
                    }

                    $codePoints[] = ord($char);
                }
            }

            $index = $delimiterPosition + 1;
        }

        $n = self::PUNYCODE_INITIAL_N;
        $i = 0;
        $bias = self::PUNYCODE_INITIAL_BIAS;
        $inputLength = strlen($input);

        while ($index < $inputLength) {
            $oldI = $i;
            $weight = 1;

            for ($k = self::PUNYCODE_BASE; ; $k += self::PUNYCODE_BASE) {
                if ($index >= $inputLength) {
                    return null;
                }

                $digit = $this->punycodeDecodeDigit($input[$index]);
                if ($digit < 0) {
                    return null;
                }

                $index++;

                if ($digit > intdiv(PHP_INT_MAX - $i, $weight)) {
                    return null;
                }

                $i += $digit * $weight;
                $t = $this->punycodeThreshold($k, $bias);
                if ($digit < $t) {
                    break;
                }

                $baseMinusT = self::PUNYCODE_BASE - $t;
                if ($weight > intdiv(PHP_INT_MAX, $baseMinusT)) {
                    return null;
                }

                $weight *= $baseMinusT;
            }

            $pointCount = count($codePoints) + 1;
            $bias = $this->punycodeAdapt($i - $oldI, $pointCount, $oldI === 0);
            if (intdiv($i, $pointCount) > PHP_INT_MAX - $n) {
                return null;
            }

            $n += intdiv($i, $pointCount);
            if ($n > 0x10FFFF) {
                return null;
            }

            $insertAt = $i % $pointCount;
            array_splice($codePoints, $insertAt, 0, [$n]);
            $i = $insertAt + 1;
        }

        $decoded = '';
        foreach ($codePoints as $codePoint) {
            $decoded .= mb_chr($codePoint, 'UTF-8');
        }

        return $decoded;
    }

    private function punycodeAdapt(int $delta, int $pointCount, bool $firstTime): int
    {
        $delta = $firstTime ? intdiv($delta, self::PUNYCODE_DAMP) : intdiv($delta, 2);
        $delta += intdiv($delta, $pointCount);

        $k = 0;
        $limit = intdiv((self::PUNYCODE_BASE - self::PUNYCODE_TMIN) * self::PUNYCODE_TMAX, 2);
        while ($delta > $limit) {
            $delta = intdiv($delta, self::PUNYCODE_BASE - self::PUNYCODE_TMIN);
            $k += self::PUNYCODE_BASE;
        }

        return $k + intdiv((self::PUNYCODE_BASE - self::PUNYCODE_TMIN + 1) * $delta, $delta + self::PUNYCODE_SKEW);
    }

    private function punycodeThreshold(int $k, int $bias): int
    {
        if ($k <= $bias + self::PUNYCODE_TMIN) {
            return self::PUNYCODE_TMIN;
        }

        if ($k >= $bias + self::PUNYCODE_TMAX) {
            return self::PUNYCODE_TMAX;
        }

        return $k - $bias;
    }

    private function punycodeEncodeDigit(int $digit): string
    {
        return $digit < 26
            ? chr(ord('a') + $digit)
            : chr(ord('0') + ($digit - 26));
    }

    private function punycodeDecodeDigit(string $char): int
    {
        $ascii = ord($char);

        if ($ascii >= 48 && $ascii <= 57) {
            return $ascii - 22;
        }

        if ($ascii >= 65 && $ascii <= 90) {
            return $ascii - 65;
        }

        if ($ascii >= 97 && $ascii <= 122) {
            return $ascii - 97;
        }

        return -1;
    }

    /**
     * @return array<int, int>|null
     */
    private function unicodeCodePoints(string $value): ?array
    {
        $chars = $this->unicodeChars($value);
        if ($chars === []) {
            return null;
        }

        $codePoints = [];
        foreach ($chars as $char) {
            $codePoint = mb_ord($char, 'UTF-8');
            if ($codePoint < 0) {
                return null;
            }

            $codePoints[] = $codePoint;
        }

        return $codePoints;
    }

    /**
     * @return array<int, string>
     */
    private function unicodeChars(string $value): array
    {
        if ($value === '') {
            return [];
        }

        if (!mb_check_encoding($value, 'UTF-8')) {
            return [];
        }

        $chars = preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY);
        return is_array($chars) ? $chars : [];
    }
}
