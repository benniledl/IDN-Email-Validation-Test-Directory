<?php

declare(strict_types=1);

final class DirectoryController
{
    public function __construct(private SubmissionRepository $submissionRepository)
    {
    }

    public function softwareIndex(?string $flash = null, string $flashType = 'info'): void
    {
        View::render('software-index', [
            'softwareItems' => $this->submissionRepository->softwareDirectory(),
            'flash' => $flash,
            'flashType' => $flashType,
        ]);
    }

    public function softwareDetail(int $softwareId, ?string $flash = null, string $flashType = 'info'): void
    {
        $software = $this->submissionRepository->findSoftware($softwareId);
        if ($software === null) {
            http_response_code(404);
            View::render('not-found', ['resource' => 'Software']);
            return;
        }

        View::render('software-detail', [
            'software' => $software,
            'reports' => $this->submissionRepository->softwareSubmissions($softwareId),
            'comments' => $this->submissionRepository->softwareComments($softwareId),
            'flash' => $flash,
            'flashType' => $flashType,
        ]);
    }

    public function reportDetail(int $reportId, ?string $flash = null, string $flashType = 'info'): void
    {
        $report = $this->submissionRepository->findReport($reportId);
        if ($report === null) {
            http_response_code(404);
            View::render('not-found', ['resource' => 'Report']);
            return;
        }

        View::render('report-detail', [
            'report' => $report,
            'tests' => $this->submissionRepository->reportTests($reportId),
            'comments' => $this->submissionRepository->reportComments($reportId),
            'flash' => $flash,
            'flashType' => $flashType,
        ]);
    }

    /** @return array{message: string, type: string} */
    public function storeSoftwareComment(int $softwareId, array $post): array
    {
        $name = trim((string)($post['author_name'] ?? ''));
        $comment = trim((string)($post['comment'] ?? ''));

        if ($name === '' || $comment === '') {
            return ['message' => 'Comment name and message are required.', 'type' => 'danger'];
        }

        $this->submissionRepository->addSoftwareComment($softwareId, $name, $comment);

        return ['message' => 'Comment added to software overview.', 'type' => 'success'];
    }

    /** @return array{message: string, type: string} */
    public function storeReportComment(int $reportId, array $post): array
    {
        $name = trim((string)($post['author_name'] ?? ''));
        $comment = trim((string)($post['comment'] ?? ''));

        if ($name === '' || $comment === '') {
            return ['message' => 'Comment name and message are required.', 'type' => 'danger'];
        }

        $this->submissionRepository->addReportComment($reportId, $name, $comment);

        return ['message' => 'Comment added to report detail.', 'type' => 'success'];
    }
}
