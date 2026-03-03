<?php

declare(strict_types=1);

final class EmailValidator
{
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

        if (!str_contains($email, '@')) {
            return [
                'is_valid' => false,
                'normalized' => $email,
                'message' => 'Email must contain @.',
            ];
        }

        [$localPart, $domain] = explode('@', $email, 2);

        $asciiDomain = idn_to_ascii($domain, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
        if ($asciiDomain === false) {
            return [
                'is_valid' => false,
                'normalized' => $email,
                'message' => 'Domain cannot be converted to ASCII.',
            ];
        }

        $normalized = $localPart . '@' . $asciiDomain;

        $isValid = filter_var($normalized, FILTER_VALIDATE_EMAIL) !== false;

        return [
            'is_valid' => $isValid,
            'normalized' => $normalized,
            'message' => $isValid ? 'Valid IDN-aware email.' : 'Invalid email format.',
        ];
    }
}
