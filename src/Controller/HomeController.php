<?php

declare(strict_types=1);

final class HomeController
{
    public function __construct(private DirectoryRepository $directoryRepository)
    {
    }

    public function index(?string $flash = null): void
    {
        $baseUrl = $this->baseUrl();
        $canonicalUrl = $baseUrl . '/';

        View::render('home', [
            'history' => $this->directoryRepository->latest(),
            'flash' => $flash,
            'metaTitle' => 'IDN Validation Directory | Submit and Track Reports',
            'metaDescription' => 'Submit IDN email validation test reports, browse tested software, and help maintainers identify reproducible WordPress plugin issues quickly.',
            'canonicalUrl' => $canonicalUrl,
        ]);
    }

    public function about(): void
    {
        $baseUrl = $this->baseUrl();
        $canonicalUrl = $baseUrl . '/about';

        View::render('about', [
            'metaTitle' => 'About: Fixing IDN Email Validation in WordPress Plugins',
            'metaDescription' => 'Learn project scope, severity model, privacy approach, contribution workflow, FAQ, and WordCamp references for the IDN validation initiative.',
            'canonicalUrl' => $canonicalUrl,
            'structuredData' => [
                '@context' => 'https://schema.org',
                '@graph' => [
                    [
                        '@type' => 'WebPage',
                        'name' => 'Fixing IDN Email Validation in WordPress Plugins',
                        'description' => 'Community-driven testing directory for IDN email validation bugs in WordPress plugins and related software.',
                        'url' => $canonicalUrl,
                    ],
                    [
                        '@type' => 'FAQPage',
                        'mainEntity' => [
                            [
                                '@type' => 'Question',
                                'name' => 'What is this project for?',
                                'acceptedAnswer' => [
                                    '@type' => 'Answer',
                                    'text' => 'This directory documents real-world IDN email validation failures in WordPress plugins and software, so maintainers can reproduce, prioritize, and fix them.',
                                ],
                            ],
                            [
                                '@type' => 'Question',
                                'name' => 'What kind of emails are tested?',
                                'acceptedAnswer' => [
                                    '@type' => 'Answer',
                                    'text' => 'We test internationalized domains such as max@müller.de and include selected SMTPUTF8 local-part cases to expose validator interoperability issues.',
                                ],
                            ],
                            [
                                '@type' => 'Question',
                                'name' => 'Can anyone contribute reports?',
                                'acceptedAnswer' => [
                                    '@type' => 'Answer',
                                    'text' => 'Yes. Submissions are public by default. You pick software, run predefined test emails, and submit outcomes. The process is intentionally quick and reproducible.',
                                ],
                            ],
                            [
                                '@type' => 'Question',
                                'name' => 'How is severity determined?',
                                'acceptedAnswer' => [
                                    '@type' => 'Answer',
                                    'text' => 'Severity is derived from the highest-impact failed template in a report: high, medium, low, or none. Administrators can override severity when needed for triage accuracy.',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function imprint(): void
    {
        $baseUrl = $this->baseUrl();

        View::render('imprint', [
            'metaTitle' => 'Impressum',
            'metaDescription' => 'Impressum mit Unternehmens- und Kontaktdaten der Ledl.net GmbH & Co. KG.',
            'canonicalUrl' => $baseUrl . '/impressum',
        ]);
    }

    public function privacy(): void
    {
        $baseUrl = $this->baseUrl();

        View::render('privacy', [
            'metaTitle' => 'Datenschutz (DSGVO)',
            'metaDescription' => 'Informationen zur Datenverarbeitung, Server-Logs, Cookies und Ihren DSGVO-Rechten.',
            'canonicalUrl' => $baseUrl . '/datenschutz',
        ]);
    }

    private function baseUrl(): string
    {
        $https = $_SERVER['HTTPS'] ?? '';
        $isHttps = $https !== '' && strtolower((string)$https) !== 'off';
        $scheme = $isHttps ? 'https' : 'http';
        $host = trim((string)($_SERVER['HTTP_HOST'] ?? 'localhost:8000'));

        return $scheme . '://' . $host;
    }
}
