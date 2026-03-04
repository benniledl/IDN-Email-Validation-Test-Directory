<section class="card border border-base-300 bg-base-100 shadow-sm">
    <div class="card-body items-center py-10 text-center">
        <h1 class="text-2xl font-semibold"><?= htmlspecialchars((string)$resource, ENT_QUOTES, 'UTF-8') ?> not found</h1>
        <p class="max-w-xl text-base-content/70">The requested item does not exist, is hidden, or the link is no longer valid.</p>
        <div class="card-actions mt-2 gap-2">
            <a href="/" class="btn btn-outline">Back to home</a>
            <a href="/software" class="btn btn-primary">Browse software</a>
        </div>
    </div>
</section>
