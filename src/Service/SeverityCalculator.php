<?php

declare(strict_types=1);

final class SeverityCalculator
{
    private const ORDER = [
        'none' => 0,
        'low' => 1,
        'medium' => 2,
        'high' => 3,
    ];

    /**
     * @param array<int, array{severity_level: string, expected_valid: int, actual_result: string}> $tests
     */
    public function calculate(array $tests): string
    {
        $highest = 'none';

        foreach ($tests as $test) {
            $expectedValid = (int)$test['expected_valid'] === 1;
            $actualAccepted = $test['actual_result'] === 'accepted';
            $failed = $expectedValid !== $actualAccepted;

            if (!$failed) {
                continue;
            }

            $severity = strtolower($test['severity_level']);
            if ((self::ORDER[$severity] ?? 0) > self::ORDER[$highest]) {
                $highest = $severity;
            }
        }

        return $highest;
    }
}
