<!doctype html>
<html lang="en" data-theme="corporate">
<head>
    <?php
    $defaultMetaTitle = 'IDN Email Validation Test Directory';
    $defaultMetaDescription = 'Community-driven testing and reporting for WordPress plugins and software that validate IDN email domains incorrectly.';
    $metaTitleValue = trim((string)($metaTitle ?? $defaultMetaTitle));
    $metaDescriptionValue = trim((string)($metaDescription ?? $defaultMetaDescription));
    $canonicalUrlValue = trim((string)($canonicalUrl ?? ''));
    $metaImageValue = trim((string)($metaImage ?? ''));
    $structuredDataValue = $structuredData ?? null;
    $cssAssetPath = __DIR__ . '/../public/assets/css/styles.css';
    $jsAssetPath = __DIR__ . '/../public/assets/js/app.js';
    $cssAssetVersion = is_file($cssAssetPath) ? (string)filemtime($cssAssetPath) : '1';
    $jsAssetVersion = is_file($jsAssetPath) ? (string)filemtime($jsAssetPath) : '1';
    ?>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($metaTitleValue, ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="description" content="<?= htmlspecialchars($metaDescriptionValue, ENT_QUOTES, 'UTF-8') ?>">
    <?php if ($canonicalUrlValue !== ''): ?><link rel="canonical" href="<?= htmlspecialchars($canonicalUrlValue, ENT_QUOTES, 'UTF-8') ?>"><?php endif; ?>
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= htmlspecialchars($metaTitleValue, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:description" content="<?= htmlspecialchars($metaDescriptionValue, ENT_QUOTES, 'UTF-8') ?>">
    <?php if ($canonicalUrlValue !== ''): ?><meta property="og:url" content="<?= htmlspecialchars($canonicalUrlValue, ENT_QUOTES, 'UTF-8') ?>"><?php endif; ?>
    <?php if ($metaImageValue !== ''): ?><meta property="og:image" content="<?= htmlspecialchars($metaImageValue, ENT_QUOTES, 'UTF-8') ?>"><?php endif; ?>
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($metaTitleValue, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($metaDescriptionValue, ENT_QUOTES, 'UTF-8') ?>">
    <?php if ($metaImageValue !== ''): ?><meta name="twitter:image" content="<?= htmlspecialchars($metaImageValue, ENT_QUOTES, 'UTF-8') ?>"><?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/daisyui@5" rel="stylesheet" type="text/css">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="/assets/css/styles.css?v=<?= htmlspecialchars($cssAssetVersion, ENT_QUOTES, 'UTF-8') ?>">
    <?php if (is_array($structuredDataValue)): ?>
        <script type="application/ld+json"><?= json_encode($structuredDataValue, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
    <?php endif; ?>
</head>
<body class="app-body min-h-screen bg-base-200 text-base-content">
<input id="mobile-nav-toggle" type="checkbox" class="peer/mobile-nav sr-only" aria-hidden="true">
<header class="app-header border-b border-base-300 bg-base-100/90 backdrop-blur">
    <div class="navbar mx-auto max-w-6xl px-4 md:px-6">
        <a href="/" class="btn btn-ghost px-3 text-lg font-semibold normal-case">IDN Validation Directory</a>
        <div class="ml-auto hidden gap-1 md:flex">
            <a href="/" class="btn btn-sm btn-ghost normal-case">Home</a>
            <a href="/about" class="btn btn-sm btn-ghost normal-case">About</a>
            <a href="/software" class="btn btn-sm btn-ghost normal-case">Software</a>
            <a href="/submit-report" class="btn btn-sm btn-primary normal-case">Submit Report</a>
        </div>
        <div class="ml-auto md:hidden">
            <label for="mobile-nav-toggle" class="btn btn-sm btn-ghost" aria-label="Open navigation menu">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </label>
        </div>
    </div>
</header>

<div class="pointer-events-none fixed inset-0 z-[100] hidden peer-checked/mobile-nav:block md:hidden">
    <label for="mobile-nav-toggle" class="absolute inset-0 block bg-base-content" aria-label="Close navigation menu"></label>
    <section class="pointer-events-auto relative h-full overflow-y-auto bg-base-100">
        <div class="mx-auto flex min-h-full w-full max-w-3xl flex-col px-5 py-6">
            <div class="mb-8 flex items-center justify-between">
                <a href="/" class="text-lg font-semibold">IDN Validation Directory</a>
                <label for="mobile-nav-toggle" class="btn btn-sm btn-ghost btn-circle" aria-label="Close navigation menu">x</label>
            </div>

            <div class="mb-6">
                <p class="text-xs uppercase tracking-[0.2em] text-base-content/60">Navigation</p>
                <h2 class="mt-1 text-2xl font-semibold tracking-tight">Menu</h2>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <a href="/" class="rounded-box border border-base-300 bg-base-100 p-4 shadow-sm transition hover:border-base-400">
                    <p class="text-lg font-semibold">Home</p>
                    <p class="text-sm text-base-content/70">Latest reports and quick actions</p>
                </a>
                <a href="/about" class="rounded-box border border-base-300 bg-base-100 p-4 shadow-sm transition hover:border-base-400">
                    <p class="text-lg font-semibold">About</p>
                    <p class="text-sm text-base-content/70">Scope, privacy, severity, and FAQ</p>
                </a>
                <a href="/software" class="rounded-box border border-base-300 bg-base-100 p-4 shadow-sm transition hover:border-base-400">
                    <p class="text-lg font-semibold">Software</p>
                    <p class="text-sm text-base-content/70">Browse tested plugins and tools</p>
                </a>
                <a href="/submit-report" class="rounded-box border border-primary/30 bg-primary/10 p-4 shadow-sm transition hover:border-primary/50">
                    <p class="text-lg font-semibold">Submit Report</p>
                    <p class="text-sm text-base-content/70">Share your IDN validation findings</p>
                </a>
            </div>
        </div>
    </section>
</div>

<main class="mx-auto flex w-full max-w-6xl flex-col gap-6 px-4 py-6 md:px-6 md:py-8">
    <?php if (!empty($breadcrumbs) && is_array($breadcrumbs)): ?>
        <nav aria-label="Breadcrumb" class="rounded-box border border-base-300 bg-base-100 px-3 py-2 text-sm">
            <div class="breadcrumbs max-w-full p-0 text-base-content/70">
                <ul class="flex-wrap">
                    <?php foreach ($breadcrumbs as $crumb): ?>
                        <?php $crumbLabel = (string)($crumb['label'] ?? ''); ?>
                        <?php $crumbUrl = (string)($crumb['url'] ?? '/'); ?>
                        <?php $crumbCurrent = (bool)($crumb['current'] ?? false); ?>
                        <li class="max-w-full">
                            <?php if ($crumbCurrent): ?>
                                <span class="font-medium text-base-content break-words" aria-current="page"><?= htmlspecialchars($crumbLabel, ENT_QUOTES, 'UTF-8') ?></span>
                            <?php else: ?>
                                <a href="<?= htmlspecialchars($crumbUrl, ENT_QUOTES, 'UTF-8') ?>" class="link link-hover break-words"><?= htmlspecialchars($crumbLabel, ENT_QUOTES, 'UTF-8') ?></a>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </nav>
    <?php endif; ?>
    <?= $content ?>
</main>

<footer class="mx-auto w-full max-w-6xl rounded-box border border-base-300 bg-base-100 px-4 py-3 md:px-6">
    <?php $adminHref = !empty($_SESSION['admin_auth_type']) ? '/admin' : '/admin/login'; ?>
    <div class="flex flex-wrap items-center justify-between gap-2">
        <span class="text-sm text-base-content/70">IDN Email Validation Test Directory</span>
        <nav class="flex flex-wrap gap-1">
            <a href="/about" class="btn btn-xs btn-ghost">About</a>
            <a href="/software" class="btn btn-xs btn-ghost">Software</a>
            <a href="/submit-report" class="btn btn-xs btn-ghost">Submit Report</a>
            <a href="<?= htmlspecialchars($adminHref, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-xs btn-ghost">Admin</a>
        </nav>
    </div>
</footer>

<div class="admin-modal fixed inset-0 z-50 grid place-items-center bg-base-content/40 p-4" id="confirm-modal" hidden>
    <div class="card w-full max-w-lg border border-base-300 bg-base-100 shadow-xl">
        <div class="card-body gap-4">
            <div class="flex items-start justify-between gap-3">
                <h3 class="card-title text-lg">Confirm action</h3>
                <button type="button" class="btn btn-sm btn-square btn-ghost" aria-label="Close" data-admin-modal-close>x</button>
            </div>
            <p id="confirm-modal-message" class="text-base-content/80">Are you sure you want to continue?</p>
            <div class="card-actions justify-end gap-2">
                <button type="button" class="btn btn-outline" data-admin-modal-close>Cancel</button>
                <button type="button" class="btn btn-error" id="confirm-modal-submit">Confirm</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script src="/assets/js/app.js?v=<?= htmlspecialchars($jsAssetVersion, ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>
