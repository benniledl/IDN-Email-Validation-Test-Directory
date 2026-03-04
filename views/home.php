<?php $flashTone = match ((string)($flashType ?? 'info')) { 'danger' => 'error', default => (string)($flashType ?? 'info') }; ?>

<section class="card border border-base-300 bg-base-100 shadow-sm" aria-labelledby="page-title">
    <div class="card-body gap-4">
        <h1 id="page-title" class="text-3xl font-semibold tracking-tight">IDN Email Validation Test Directory</h1>
        <p class="max-w-3xl text-base-content/70">Track software behavior for internationalized email validation, compare report outcomes, and contribute reproducible test runs.</p>
        <div class="flex flex-wrap gap-2">
            <a href="/submit-report" class="btn btn-primary">Submit Report</a>
            <a href="/software" class="btn btn-outline">Explore Software</a>
        </div>
    </div>
</section>

<?php if (!empty($flash)): ?>
    <div class="alert alert-<?= htmlspecialchars($flashTone, ENT_QUOTES, 'UTF-8') ?>" role="status" data-dismissible="true"><?= htmlspecialchars((string)$flash, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<section class="card border border-base-300 bg-base-100 shadow-sm" aria-labelledby="history-title">
    <div class="card-body">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
            <h2 id="history-title" class="text-xl font-semibold">Latest Public Reports</h2>
            <a href="/software" class="btn btn-sm btn-ghost">View all software</a>
        </div>

        <?php if (empty($history)): ?>
            <div class="rounded-box border border-base-300 bg-base-200 px-4 py-6 text-center text-base-content/70">No reports yet. Be the first to submit one.</div>
        <?php else: ?>
            <div class="overflow-x-auto">
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
