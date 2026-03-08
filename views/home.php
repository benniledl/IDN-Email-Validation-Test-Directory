<?php $flashTone = match ((string)($flashType ?? 'info')) { 'danger' => 'error', default => (string)($flashType ?? 'info') }; ?>

<section class="grid gap-4 lg:grid-cols-[1.4fr_1fr]" aria-labelledby="page-title">
    <article class="card border border-base-300 bg-base-100 shadow-sm">
        <div class="card-body gap-3 md:gap-4">
            <p class="badge badge-outline">Community Project · WordPress Ecosystem</p>
            <h1 id="page-title" class="text-3xl font-semibold tracking-tight md:text-4xl">Fixing IDN Email Validation in WordPress Plugins</h1>
            <p class="max-w-3xl text-base-content/75">Help test real plugin behavior with internationalized email domains (IDN), publish reproducible results, and support maintainers shipping real fixes.</p>
            <div class="flex flex-wrap gap-2">
                <a href="/submit-report" class="btn btn-primary">Submit a test report</a>
                <a href="/software" class="btn btn-outline">Browse tested software</a>
            </div>
        </div>
    </article>

    <article class="card border border-info/30 bg-gradient-to-br from-info/10 via-base-100 to-base-100 shadow-sm">
        <div class="card-body gap-3">
            <p class="badge badge-info badge-outline">New here?</p>
            <h2 class="text-lg font-semibold">Start quickly, no local setup needed</h2>
            <p class="text-base-content/75">Use <code>playground.wordpress.net</code> to test plugins in a disposable WordPress environment, then submit your findings here.</p>
            <div class="rounded-box border border-base-300/80 bg-base-100 p-3">
                <p class="font-medium">Need context first?</p>
                <a href="/about" class="link link-hover text-sm">Read scope, severity model, privacy, and FAQ on the About page</a>
            </div>
        </div>
    </article>
</section>

<?php if (!empty($flash)): ?>
    <div class="alert alert-<?= htmlspecialchars($flashTone, ENT_QUOTES, 'UTF-8') ?>" role="status" data-dismissible="true"><?= htmlspecialchars((string)$flash, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<section class="card border border-base-300 bg-base-100 shadow-sm" aria-labelledby="how-it-works-title">
    <div class="card-body gap-4">
        <h2 id="how-it-works-title" class="text-2xl font-semibold tracking-tight">How to contribute (about 5 minutes)</h2>
        <div class="grid gap-3 md:grid-cols-3">
            <article class="rounded-box border border-base-300 bg-base-100 p-4">
                <span class="badge badge-outline mb-2">Step 1</span>
                <h3 class="mb-1 font-semibold">Pick software to test</h3>
                <p class="text-sm text-base-content/75">Choose a WordPress plugin (or external software) and note the URL/version.</p>
            </article>
            <article class="rounded-box border border-base-300 bg-base-100 p-4">
                <span class="badge badge-outline mb-2">Step 2</span>
                <h3 class="mb-1 font-semibold">Run the IDN checks</h3>
                <p class="text-sm text-base-content/75">Test the email templates in the target form. Tip: use <code>playground.wordpress.net</code> if you do not have a testing environment.</p>
            </article>
            <article class="rounded-box border border-base-300 bg-base-100 p-4">
                <span class="badge badge-outline mb-2">Step 3</span>
                <h3 class="mb-1 font-semibold">Publish report</h3>
                <p class="text-sm text-base-content/75">Submit and get an immediate public report with computed severity.</p>
            </article>
        </div>
    </div>
</section>

<section class="card border border-base-300 bg-base-100 shadow-sm" aria-labelledby="history-title">
    <div class="card-body">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
            <h2 id="history-title" class="text-xl font-semibold">Latest Public Reports</h2>
            <a href="/software" class="btn btn-sm btn-ghost">View all software</a>
        </div>

        <?php if (empty($history)): ?>
            <div class="rounded-box border border-base-300 bg-base-200 px-4 py-6 text-center text-base-content/70">No reports yet. Be the first to submit one.</div>
        <?php else: ?>
            <div class="space-y-2 md:hidden">
                <?php foreach ($history as $item): ?>
                    <?php $historySoftwareName = html_entity_decode((string)$item['software_name'], ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?>
                    <?php $isPassing = strtolower((string)($item['severity_auto'] ?? 'none')) === 'none'; ?>
                    <article class="rounded-box border border-base-300 bg-base-100 p-3">
                        <div class="mb-2 flex items-start justify-between gap-2">
                            <a href="/software/<?= (int)$item['software_id'] ?>" class="link link-hover font-medium break-words"><?= htmlspecialchars($historySoftwareName, ENT_QUOTES, 'UTF-8') ?></a>
                            <span class="badge <?= $isPassing ? 'badge-success' : 'badge-error' ?> badge-outline"><?= $isPassing ? 'Pass' : 'Fail' ?></span>
                        </div>
                        <div class="mb-3 text-xs text-base-content/65">
                            <span><?= htmlspecialchars((string)$item['submitter_name'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="mx-1">·</span>
                            <span title="<?= htmlspecialchars((string)$item['created_at'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(View::timeAgo((string)$item['created_at']), ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <a href="/reports/<?= (int)$item['id'] ?>" class="btn btn-xs btn-outline">Details</a>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="hidden overflow-x-auto md:block">
                <table class="table table-zebra">
                    <thead>
                    <tr>
                        <th>Software</th>
                        <th>Submitter</th>
                        <th>Result</th>
                        <th>Created</th>
                        <th class="text-right">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($history as $item): ?>
                        <?php $historySoftwareName = html_entity_decode((string)$item['software_name'], ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?>
                        <?php $isPassing = strtolower((string)($item['severity_auto'] ?? 'none')) === 'none'; ?>
                        <tr>
                            <td><a href="/software/<?= (int)$item['software_id'] ?>" class="link link-hover font-medium"><?= htmlspecialchars($historySoftwareName, ENT_QUOTES, 'UTF-8') ?></a></td>
                            <td><?= htmlspecialchars((string)$item['submitter_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><span class="badge <?= $isPassing ? 'badge-success' : 'badge-error' ?> badge-outline"><?= $isPassing ? 'Pass' : 'Fail' ?></span></td>
                            <td title="<?= htmlspecialchars((string)$item['created_at'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(View::timeAgo((string)$item['created_at']), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="text-right"><a href="/reports/<?= (int)$item['id'] ?>" class="btn btn-xs btn-outline">Details</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>
