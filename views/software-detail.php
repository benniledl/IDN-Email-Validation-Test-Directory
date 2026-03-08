<?php $flashTone = match ((string)($flashType ?? 'info')) { 'danger' => 'error', default => (string)($flashType ?? 'info') }; ?>
<?php $old = is_array($old ?? null) ? $old : []; ?>
<?php $oldForm = (string)($old['_form'] ?? ''); ?>
<?php $autoOpenModal = $oldForm === 'admin_solution' ? 'software-action-publish-modal' : ''; ?>
<?php
$softwareName = html_entity_decode((string)$software['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
$overallSeverity = (string)($software['overall_severity'] ?? 'none');
$severityClass = match ($overallSeverity) {
    'high' => 'badge-error',
    'medium' => 'badge-warning',
    'low' => 'badge-info',
    default => 'badge-ghost',
};
?>

<section class="card border border-base-300 bg-base-100 shadow-sm">
    <div class="card-body gap-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight"><?= htmlspecialchars($softwareName, ENT_QUOTES, 'UTF-8') ?></h1>
                <div class="mt-1 flex flex-wrap items-center gap-2">
                    <span class="badge <?= $severityClass ?> badge-outline uppercase">Highest severity: <?= htmlspecialchars($overallSeverity, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php if (!empty($software['plugin_author'])): ?><span class="text-sm text-base-content/70">by <?= htmlspecialchars((string)$software['plugin_author'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="<?= htmlspecialchars((string)$software['canonical_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline">Official page</a>
                <?php if (!empty($adminMode)): ?>
                                        <div class="dropdown dropdown-end table-actions-dropdown">
                        <button tabindex="0" type="button" class="btn btn-sm btn-ghost">Admin actions</button>
                        <ul tabindex="0" class="menu dropdown-content z-[1] mt-1 w-56 rounded-box border border-base-300 bg-base-100 p-1 shadow-lg">
                            <li><button type="button" data-admin-modal-open="software-action-publish-modal">Publish official update</button></li>
                            <?php if ((string)$software['type'] === 'other'): ?>
                                <li>
                                    <form method="post" action="/software/<?= (int)$software['id'] ?>/admin/hide" data-confirm="Delete this custom software from public listings?" class="w-full">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)$adminCsrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                        <button type="submit" class="w-full text-left">Delete custom software</button>
                                    </form>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                <a href="/software" class="btn btn-sm btn-ghost">Back</a>
            </div>
        </div>

        <?php if (!empty($software['plugin_banner_url'])): ?>
            <img class="w-full rounded-box border border-base-300" src="<?= htmlspecialchars((string)$software['plugin_banner_url'], ENT_QUOTES, 'UTF-8') ?>" <?php if (!empty($software['plugin_banner_2x_url'])): ?>srcset="<?= htmlspecialchars((string)$software['plugin_banner_url'], ENT_QUOTES, 'UTF-8') ?> 772w, <?= htmlspecialchars((string)$software['plugin_banner_2x_url'], ENT_QUOTES, 'UTF-8') ?> 1544w" sizes="(min-width: 900px) 1000px, 100vw"<?php endif; ?> alt="<?= htmlspecialchars($softwareName, ENT_QUOTES, 'UTF-8') ?> banner" loading="lazy">
        <?php endif; ?>

        <?php $description = trim((string)($software['description'] ?? '')); ?>
        <?php if ($description !== ''): ?><p class="text-base-content/80"><?= nl2br(htmlspecialchars($description, ENT_QUOTES, 'UTF-8')) ?></p><?php endif; ?>
    </div>
</section>

<section class="card border border-base-300 bg-base-100 shadow-sm">
    <div class="card-body">
        <h2 class="mb-3 text-xl font-semibold">Reports</h2>
        <?php if (empty($reports)): ?>
            <div class="rounded-box border border-base-300 bg-base-200 px-4 py-6 text-center text-base-content/70">No reports for this software yet.</div>
        <?php else: ?>
            <div class="space-y-2 md:hidden">
                <?php foreach ($reports as $report): ?>
                    <article class="rounded-box border border-base-300 bg-base-100 p-3">
                        <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                            <span class="font-semibold">Report #<?= (int)$report['id'] ?></span>
                            <span class="badge badge-ghost badge-outline uppercase"><?= htmlspecialchars((string)$report['severity_resolved'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <div class="mb-3 text-xs text-base-content/70">
                            <span><?= htmlspecialchars((string)$report['submitter_name'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="mx-1">·</span>
                            <span>Version: <?= htmlspecialchars((string)($report['wordpress_version'] ?: '-'), ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="mx-1">·</span>
                            <span title="<?= htmlspecialchars((string)$report['created_at'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(View::timeAgo((string)$report['created_at']), ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <div class="flex flex-wrap gap-1.5">
                            <a href="/reports/<?= (int)$report['id'] ?>" class="btn btn-xs btn-outline">Details</a>
                            <?php if (!empty($adminMode)): ?>
                                <form method="post" action="/reports/<?= (int)$report['id'] ?>/admin/hide" data-confirm="Delete report #<?= (int)$report['id'] ?>?" class="inline">
                                    <input type="hidden" name="software_id" value="<?= (int)$software['id'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)$adminCsrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                    <button type="submit" class="btn btn-xs btn-error btn-outline" title="Delete report">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2m-8 0l1 12a1 1 0 001 1h6a1 1 0 001-1l1-12"/></svg>
                                        Delete
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="hidden overflow-x-auto md:block">
                <table class="table table-zebra">
                    <thead><tr><th>Report</th><th>Submitter</th><th>Version</th><th>Severity</th><th>Submitted</th><th class="text-right">Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($reports as $report): ?>
                        <tr>
                            <td>#<?= (int)$report['id'] ?></td>
                            <td><?= htmlspecialchars((string)$report['submitter_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($report['wordpress_version'] ?: '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><span class="badge badge-ghost uppercase"><?= htmlspecialchars((string)$report['severity_resolved'], ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td title="<?= htmlspecialchars((string)$report['created_at'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(View::timeAgo((string)$report['created_at']), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="text-right">
                                <div class="inline-flex gap-1">
                                    <a href="/reports/<?= (int)$report['id'] ?>" class="btn btn-xs btn-outline">Details</a>
                                    <?php if (!empty($adminMode)): ?>
                                        <form method="post" action="/reports/<?= (int)$report['id'] ?>/admin/hide" data-confirm="Delete report #<?= (int)$report['id'] ?>?" class="inline">
                                            <input type="hidden" name="software_id" value="<?= (int)$software['id'] ?>">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)$adminCsrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                            <button type="submit" class="btn btn-xs btn-error btn-outline" title="Delete report">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2m-8 0l1 12a1 1 0 001 1h6a1 1 0 001-1l1-12"/></svg>
                                                Delete
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
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
            <span class="badge badge-outline">Software thread</span>
        </div>
        <?php if (!empty($flash)): ?><div class="alert alert-<?= htmlspecialchars($flashTone, ENT_QUOTES, 'UTF-8') ?>" role="status" data-dismissible="true"><?= htmlspecialchars((string)$flash, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

        <form method="post" action="/software/<?= (int)$software['id'] ?>/comments" class="mb-5 rounded-box border border-base-300 bg-base-200/60 p-3 md:p-4">
            <input type="hidden" name="_form" value="software_comment">
            <div class="grid gap-3 md:grid-cols-[1fr_auto] md:items-end">
                <label class="form-control w-full">
                    <div class="label pb-1">
                        <span class="label-text">Your name</span>
                    </div>
                    <input class="input input-bordered w-full" name="author_name" value="<?= htmlspecialchars($oldForm === 'software_comment' ? (string)($old['author_name'] ?? '') : '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Jane Doe" required>
                </label>
                <button class="btn btn-primary md:mb-0.5" type="submit">Post comment</button>
            </div>
            <label class="form-control mt-2">
                <div class="label pb-1">
                    <span class="label-text">Comment</span>
                </div>
                <textarea class="textarea textarea-bordered min-h-24 w-full" name="comment" placeholder="Share findings, reproduction notes, or suggested fixes" required><?= htmlspecialchars($oldForm === 'software_comment' ? (string)($old['comment'] ?? '') : '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </label>
        </form>

        <?php if (empty($comments)): ?>
            <div class="alert border border-base-300 bg-base-100 text-base-content/70">
                <span>No comments yet.</span>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($comments as $comment): ?>
                    <?php
                    $commentAuthor = trim((string)($comment['author_name'] ?? 'Anonymous'));
                    $commentInitial = function_exists('mb_substr') ? (string)mb_substr($commentAuthor, 0, 1, 'UTF-8') : substr($commentAuthor, 0, 1);
                    $commentInitial = function_exists('mb_strtoupper') ? (string)mb_strtoupper($commentInitial, 'UTF-8') : strtoupper($commentInitial);
                    if ($commentInitial === '') {
                        $commentInitial = 'A';
                    }
                    ?>
                    <article class="chat chat-start">
                        <div class="chat-image avatar placeholder">
                            <div class="flex w-10 items-center justify-center rounded-full bg-base-300 text-base-content">
                                <span class="text-xs font-semibold uppercase leading-none"><?= htmlspecialchars($commentInitial, ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        </div>
                        <div class="chat-header mb-1 flex items-center gap-2 text-sm">
                            <span class="font-semibold text-base-content"><?= htmlspecialchars($commentAuthor, ENT_QUOTES, 'UTF-8') ?></span>
                            <time class="text-base-content/60" title="<?= htmlspecialchars((string)$comment['created_at'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(View::timeAgo((string)$comment['created_at']), ENT_QUOTES, 'UTF-8') ?></time>
                            <?php if ((int)($comment['is_admin_solution'] ?? 0) === 1): ?><span class="badge badge-success badge-sm">Official</span><?php endif; ?>
                        </div>
                        <div class="chat-bubble chat-bubble-neutral whitespace-pre-wrap bg-base-200 text-base-content"><?= htmlspecialchars((string)$comment['comment'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php if (!empty($adminMode)): ?>
                            <div class="chat-footer mt-2">
                                <form method="post" action="/software/<?= (int)$software['id'] ?>/comments/<?= (int)$comment['id'] ?>/hide" data-confirm="Delete this software comment?">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)$adminCsrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                    <button class="btn btn-xs btn-error btn-outline" type="submit" title="Delete comment">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2m-8 0l1 12a1 1 0 001 1h6a1 1 0 001-1l1-12"/></svg>
                                        Delete
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php if (!empty($adminMode)): ?>
    <div class="admin-modal fixed inset-0 z-50 grid place-items-center bg-base-content/40 p-4" id="software-action-publish-modal" hidden>
        <div class="card w-full max-w-2xl border border-base-300 bg-base-100 shadow-xl" role="dialog" aria-modal="true" aria-labelledby="software-action-publish-title">
            <div class="card-body gap-4">
                <div class="flex items-start justify-between gap-3">
                    <h3 class="card-title" id="software-action-publish-title">Publish official update</h3>
                    <button type="button" class="btn btn-sm btn-square btn-ghost" aria-label="Close" data-admin-modal-close>x</button>
                </div>
                <form method="post" action="/software/<?= (int)$software['id'] ?>/admin/solution" class="grid gap-2 md:grid-cols-[1fr_2fr_auto]">
                    <input type="hidden" name="_form" value="admin_solution">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)$adminCsrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <input class="input input-bordered" name="author_name" value="<?= htmlspecialchars($oldForm === 'admin_solution' ? (string)($old['author_name'] ?? 'Admin') : 'Admin', ENT_QUOTES, 'UTF-8') ?>" placeholder="Admin name">
                    <input class="input input-bordered" name="comment" value="<?= htmlspecialchars($oldForm === 'admin_solution' ? (string)($old['comment'] ?? '') : '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Official update" required>
                    <button class="btn btn-primary" type="submit">Publish</button>
                </form>
            </div>
        </div>
    </div>

<?php endif; ?>

<?php if ($autoOpenModal !== ''): ?>
    <div id="auto-open-modal" data-target="<?= htmlspecialchars($autoOpenModal, ENT_QUOTES, 'UTF-8') ?>" hidden></div>
<?php endif; ?>
