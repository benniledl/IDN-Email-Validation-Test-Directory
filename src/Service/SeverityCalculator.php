<?php

declare(strict_types=1);

final class SeverityCalculator
{
    /**
     * @param array<int, array{severity_weight: int, expected_valid: int, actual_result: string}> $tests
     */
    public function calculate(array $tests): string
    {
        $highestWeight = 0;

        foreach ($tests as $test) {
            $expectedValid = (int)$test['expected_valid'] === 1;
            $actualAccepted = $test['actual_result'] === 'accepted';
            $failed = $expectedValid !== $actualAccepted;

            if (!$failed) {
                continue;
            }

            $weight = (int)$test['severity_weight'];
            if ($weight > $highestWeight) {
                $highestWeight = $weight;
            }
        }

        return match ($highestWeight) {
            3 => 'high',
            2 => 'medium',
            1 => 'low',
            default => 'none',
        };
    }
}
