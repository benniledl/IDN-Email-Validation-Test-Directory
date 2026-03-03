<?php

declare(strict_types=1);

final class SubmissionController
{
    public function __construct(
        private TemplateEmailRepository $templateRepository,
        private SubmissionRepository $submissionRepository,
        private SeverityCalculator $severityCalculator
    ) {
    }

    /** @return string */
    public function store(array $post): string
    {
        $software = [
            'name' => trim((string)($post['software_name'] ?? '')),
            'software_url' => trim((string)($post['software_url'] ?? '')),
            'software_type' => trim((string)($post['software_type'] ?? 'other')),
            'description' => trim((string)($post['software_description'] ?? '')),
        ];

        $payload = [
            'submitter_name' => trim((string)($post['submitter_name'] ?? '')),
            'submitter_email' => trim((string)($post['submitter_email'] ?? '')),
            'submitter_role' => trim((string)($post['submitter_role'] ?? '')),
            'submission_comment' => trim((string)($post['submission_comment'] ?? '')),
        ];

        if ($software['name'] === '' || $software['software_url'] === '' || $payload['submitter_name'] === '' || $payload['submitter_email'] === '') {
            return 'Please fill all required fields.';
        }

        $templates = $this->templateRepository->all();
        $tests = [];

        foreach ($templates as $template) {
            $key = 'result_' . $template['id'];
            $actualResult = (string)($post[$key] ?? 'not_tested');

            if (!in_array($actualResult, ['accepted', 'rejected', 'not_tested'], true) || $actualResult === 'not_tested') {
                continue;
            }

            $tests[] = [
                'template_id' => (int)$template['id'],
                'email_address' => (string)$template['email_address'],
                'expected_valid' => (int)$template['expected_valid'],
                'actual_result' => $actualResult,
                'severity_level' => (string)$template['severity_level'],
            ];
        }

        if ($tests === []) {
            return 'Please record at least one test result.';
        }

        $severity = $this->severityCalculator->calculate($tests);
        $softwareId = $this->submissionRepository->findOrCreateSoftware($software);
        $this->submissionRepository->createSubmission($softwareId, $payload, $tests, $severity);

        return 'Submission saved. Auto severity: ' . strtoupper($severity);
    }
}
