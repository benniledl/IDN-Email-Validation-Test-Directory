<?php $flashTone = match ((string)($flashType ?? 'info')) { 'danger' => 'error', default => (string)($flashType ?? 'info') }; ?>
<?php $old = is_array($old ?? null) ? $old : []; ?>
<?php $oldForm = (string)($old['_form'] ?? ''); ?>
<?php $autoOpenModal = $oldForm === 'admin_override_severity' ? 'report-action-override-modal' : ''; ?>

<section class="card border border-base-300 bg-base-100 shadow-sm">
    <div class="card-body gap-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Report #<?= (int)$report['id'] ?></h1>
                <p class="text-base-content/70">Submitted <span title="<?= htmlspecialchars((string)$report['created_at'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(View::timeAgo((string)$report['created_at']), ENT_QUOTES, 'UTF-8') ?></span></p>
            </div>
            <div class="flex flex-wrap gap-2">
                <?php if (!empty($adminMode)): ?>
                    <div class="dropdown dropdown-end">
                        <button tabindex="0" type="button" class="btn btn-sm btn-ghost">Admin actions</button>
                        <ul tabindex="0" class="menu dropdown-content z-[1] mt-1 w-56 rounded-box border border-base-300 bg-base-100 p-1 shadow-lg">
                            <li><button type="button" data-admin-modal-open="report-action-override-modal">Override severity</button></li>
                            <li>
                                <form method="post" action="/reports/<?= (int)$report['id'] ?>/admin/hide" data-confirm="Delete this report from all public views?" class="w-full">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)$adminCsrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                    <button type="submit" class="w-full text-left">Delete report</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                <?php endif; ?>
                <a href="/software/<?= (int)$report['software_id'] ?>" class="btn btn-sm btn-outline">Back to software</a>
            </div>
        </div>

        <?php $reportSoftwareName = html_entity_decode((string)$report['software_name'], ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?>
        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-box border border-base-300 bg-base-100 p-3"><div class="text-xs uppercase tracking-wide text-base-content/60">Software</div><a href="/software/<?= (int)$report['software_id'] ?>" class="link link-hover font-medium"><?= htmlspecialchars($reportSoftwareName, ENT_QUOTES, 'UTF-8') ?></a></div>
            <div class="rounded-box border border-base-300 bg-base-100 p-3"><div class="text-xs uppercase tracking-wide text-base-content/60">Submitted by</div><div class="font-medium"><?= htmlspecialchars((string)$report['submitter_name'], ENT_QUOTES, 'UTF-8') ?></div></div>
            <div class="rounded-box border border-base-300 bg-base-100 p-3"><div class="text-xs uppercase tracking-wide text-base-content/60">Severity</div><span class="badge badge-ghost uppercase"><?= htmlspecialchars((string)$report['severity_resolved'], ENT_QUOTES, 'UTF-8') ?></span></div>
            <div class="rounded-box border border-base-300 bg-base-100 p-3"><div class="text-xs uppercase tracking-wide text-base-content/60">Version</div><div class="font-medium"><?= htmlspecialchars((string)($report['wordpress_version'] ?: '-'), ENT_QUOTES, 'UTF-8') ?></div></div>
            <div class="rounded-box border border-base-300 bg-base-100 p-3 sm:col-span-2 lg:col-span-4"><div class="text-xs uppercase tracking-wide text-base-content/60">Notes</div><div class="font-medium"><?= nl2br(htmlspecialchars((string)($report['submission_comment'] ?: '—'), ENT_QUOTES, 'UTF-8')) ?></div></div>
        </div>
    </div>
</section>

<section class="card border border-base-300 bg-base-100 shadow-sm">
    <div class="card-body">
        <h2 class="mb-3 text-xl font-semibold">Test outcomes</h2>
        <?php if (empty($tests)): ?>
            <div class="rounded-box border border-base-300 bg-base-200 px-4 py-6 text-center text-base-content/70">No test rows were submitted with this report.</div>
        <?php else: ?>
            <div class="space-y-2 md:hidden">
                <?php foreach ($tests as $test): ?>
                    <?php $failed = (int)$test['failure_detected'] === 1; ?>
                    <article class="rounded-box border border-base-300 bg-base-100 p-3">
                        <code class="mb-2 block break-all text-xs"><?= htmlspecialchars((string)$test['email_address'], ENT_QUOTES, 'UTF-8') ?></code>
                        <div class="flex flex-wrap items-center gap-2 text-xs">
                            <span class="badge badge-ghost badge-sm">Expected: <?= (int)$test['expected_valid'] === 1 ? 'Valid' : 'Invalid' ?></span>
                            <span class="badge <?= $failed ? 'badge-error' : 'badge-success' ?> badge-outline badge-sm"><?= htmlspecialchars((string)$test['actual_result'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="hidden overflow-x-auto md:block">
                <table class="table table-zebra">
                    <thead><tr><th>Email</th><th>Expected</th><th>Actual</th></tr></thead>
                    <tbody>
                    <?php foreach ($tests as $test): ?>
                        <?php $failed = (int)$test['failure_detected'] === 1; ?>
                        <tr>
                            <td><code><?= htmlspecialchars((string)$test['email_address'], ENT_QUOTES, 'UTF-8') ?></code></td>
                            <td><?= (int)$test['expected_valid'] === 1 ? 'Valid' : 'Invalid' ?></td>
                            <td><span class="badge <?= $failed ? 'badge-error' : 'badge-success' ?> badge-outline"><?= htmlspecialchars((string)$test['actual_result'], ENT_QUOTES, 'UTF-8') ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="card border border-base-300 bg-base-100 shadow-sm">
    <div class="card-body">
        <div class="mb-3 flex items-center justify-between gap-2">
            <h2 class="text-xl font-semibold">Discussion</h2>
            <span class="badge badge-outline">Report thread</span>
        </div>
        <?php if (!empty($flash)): ?><div class="alert alert-<?= htmlspecialchars($flashTone, ENT_QUOTES, 'UTF-8') ?>" role="status" data-dismissible="true"><?= htmlspecialchars((string)$flash, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

        <form method="post" action="/reports/<?= (int)$report['id'] ?>/comments" class="mb-5 rounded-box border border-base-300 bg-base-200/60 p-3 md:p-4">
            <input type="hidden" name="_form" value="report_comment">
            <input type="hidden" name="comment_guard" value="<?= htmlspecialchars((string)($commentGuard ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            <div class="hidden" aria-hidden="true">
                <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
            </div>
            <div class="grid gap-3 md:grid-cols-[1fr_auto] md:items-end">
                <label class="form-control w-full">
                    <div class="label pb-1">
                        <span class="label-text">Your name</span>
                    </div>
                    <input class="input input-bordered w-full" name="author_name" value="<?= htmlspecialchars($oldForm === 'report_comment' ? (string)($old['author_name'] ?? '') : '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Jane Doe" required>
                </label>
                <button class="btn btn-primary md:mb-0.5" type="submit">Post comment</button>
            </div>
            <label class="form-control mt-2">
                <div class="label pb-1">
                    <span class="label-text">Comment</span>
                </div>
                <textarea class="textarea textarea-bordered min-h-24 w-full" name="comment" placeholder="Share your validation notes, unexpected behavior, or follow-up questions" required><?= htmlspecialchars($oldForm === 'report_comment' ? (string)($old['comment'] ?? '') : '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </label>
        </form>

        <?php if (empty($comments)): ?>
            <div class="alert border border-base-300 bg-base-100 text-base-content/70">
                <span>No comments yet.</span>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($comments as $comment): ?>
                    <?php $deletePath = '/reports/' . (int)$report['id'] . '/comments/' . (int)$comment['id'] . '/hide'; ?>
                    <?php require __DIR__ . '/partials/comment-item.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php if (!empty($adminMode)): ?>
    <div class="admin-modal fixed inset-0 z-50 grid place-items-center bg-base-content/40 p-4" id="report-action-override-modal" hidden>
        <div class="card w-full max-w-xl border border-base-300 bg-base-100 shadow-xl" role="dialog" aria-modal="true" aria-labelledby="report-action-override-title">
            <div class="card-body gap-4">
                <div class="flex items-start justify-between gap-3">
                    <h3 class="card-title" id="report-action-override-title">Override severity</h3>
                    <button type="button" class="btn btn-sm btn-square btn-ghost" aria-label="Close" data-admin-modal-close>x</button>
                </div>
                <form method="post" action="/reports/<?= (int)$report['id'] ?>/admin/severity" class="grid gap-2 md:grid-cols-[1fr_auto]">
                    <input type="hidden" name="_form" value="admin_override_severity">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)$adminCsrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <?php $oldOverride = $oldForm === 'admin_override_severity' ? (string)($old['severity_admin_override'] ?? '') : (string)($report['severity_admin_override'] ?? ''); ?>
                    <select id="severity_admin_override" name="severity_admin_override" class="select select-bordered">
                        <option value="">No override (use auto)</option>
                        <?php foreach (['none', 'low', 'medium', 'high'] as $severityOption): ?>
                            <option value="<?= $severityOption ?>" <?= $oldOverride === $severityOption ? 'selected' : '' ?>><?= strtoupper($severityOption) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-primary">Save</button>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($autoOpenModal !== ''): ?>
    <div id="auto-open-modal" data-target="<?= htmlspecialchars($autoOpenModal, ENT_QUOTES, 'UTF-8') ?>" hidden></div>
<?php endif; ?>
