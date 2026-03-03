<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>IDN Email Validation Test Directory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body class="bg-light text-dark">
<nav class="navbar navbar-expand-lg bg-white border-bottom shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-semibold" href="/">IDN Validation Directory</a>
        <div class="navbar-nav ms-auto gap-2">
            <a class="nav-link" href="/">Home</a>
            <a class="nav-link" href="/submit-report">Submit report</a>
            <a class="nav-link" href="/software">Software overview</a>
        </div>
    </div>
</nav>
<main class="container py-4 py-md-5 app-shell">
    <?= $content ?>
</main>
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script src="/assets/js/app.js"></script>
</body>
</html>
