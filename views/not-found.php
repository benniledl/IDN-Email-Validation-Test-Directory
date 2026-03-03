<section class="card border-0 shadow-sm">
    <div class="card-body p-5 text-center">
        <h1 class="h4 mb-3"><?= htmlspecialchars((string)$resource, ENT_QUOTES, 'UTF-8') ?> not found</h1>
        <p class="text-secondary">The requested item does not exist or is hidden.</p>
        <a href="/" class="btn btn-outline-secondary">Back to home</a>
    </div>
</section>
