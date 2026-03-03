<?php

declare(strict_types=1);

final class HomeController
{
    public function __construct(private SubmissionRepository $submissionRepository)
    {
    }

    public function index(?string $flash = null): void
    {
        View::render('home', [
            'history' => $this->submissionRepository->latest(),
            'flash' => $flash,
        ]);
    }
}
