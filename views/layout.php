<!doctype html>
<html lang="en" data-theme="corporate">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>IDN Email Validation Test Directory</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/daisyui@5" rel="stylesheet" type="text/css">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body class="app-body min-h-screen bg-base-200 text-base-content">
<header class="app-header border-b border-base-300 bg-base-100/90 backdrop-blur">
    <div class="navbar mx-auto max-w-6xl px-4 md:px-6">
        <a href="/" class="btn btn-ghost px-0 text-lg font-semibold normal-case">IDN Validation Directory</a>
        <nav class="ml-auto flex gap-1">
            <a href="/" class="btn btn-sm btn-ghost normal-case">Home</a>
            <a href="/software" class="btn btn-sm btn-ghost normal-case">Software</a>
            <a href="/submit-report" class="btn btn-sm btn-primary normal-case">Submit Report</a>
        </nav>
    </div>
</header>

<main class="mx-auto flex w-full max-w-6xl flex-col gap-6 px-4 py-6 md:px-6 md:py-8">
    <?= $content ?>
</main>

<footer class="mx-auto mb-6 w-full max-w-6xl rounded-box border border-base-300 bg-base-100 px-4 py-3 md:px-6">
    <?php $adminHref = !empty($_SESSION['admin_auth_type']) ? '/admin' : '/admin/login'; ?>
    <div class="flex flex-wrap items-center justify-between gap-2">
        <span class="text-sm text-base-content/70">IDN Email Validation Test Directory</span>
        <nav class="flex flex-wrap gap-1">
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
<script src="/assets/js/app.js"></script>
</body>
</html>
