<section class="app-card mb-4">
    <div class="app-card-body">
        <?php
        $softwareName = html_entity_decode((string)$software['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $overallSeverity = (string)($software['overall_severity'] ?? 'none');
        $severityTone = match ($overallSeverity) {
            'high' => 'danger',
            'medium' => 'warning',
            'low' => 'info',
            default => 'success',
        };
        ?>

        <div class="plugin-hero software-hero mb-3">
            <?php if (!empty($software['plugin_banner_url'])): ?>
                <img class="plugin-banner-image" src="<?= htmlspecialchars((string)$software['plugin_banner_url'], ENT_QUOTES, 'UTF-8') ?>"
                    <?php if (!empty($software['plugin_banner_2x_url'])): ?>srcset="<?= htmlspecialchars((string)$software['plugin_banner_url'], ENT_QUOTES, 'UTF-8') ?> 772w, <?= htmlspecialchars((string)$software['plugin_banner_2x_url'], ENT_QUOTES, 'UTF-8') ?> 1544w" sizes="(min-width: 900px) 1000px, 100vw"<?php endif; ?>
                    alt="<?= htmlspecialchars($softwareName, ENT_QUOTES, 'UTF-8') ?> banner" loading="lazy">
            <?php endif; ?>
            <div class="software-hero-overlay">
                <h1 class="h3 mb-2"><?= htmlspecialchars($softwareName, ENT_QUOTES, 'UTF-8') ?></h1>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <span class="badge text-bg-<?= $severityTone ?> text-uppercase">Highest risk: <?= htmlspecialchars($overallSeverity, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php if (!empty($software['plugin_author'])): ?>
                        <span class="software-hero-meta">by <?= htmlspecialchars((string)$software['plugin_author'], ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </div>
                <a href="<?= htmlspecialchars((string)$software['canonical_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="btn btn-sm btn-light">Plugin page</a>
            </div>
        </div>

        <div class="d-flex justify-content-end">
            <a href="/software" class="btn btn-sm btn-outline-secondary">Back to overview</a>
        </div>

        <?php $description = trim((string)($software['description'] ?? '')); ?>
        <?php if ($description !== ''): ?>
            <p class="mt-3 mb-0"><?= nl2br(htmlspecialchars($description, ENT_QUOTES, 'UTF-8')) ?></p>
        <?php endif; ?>
    </div>
</section>

<section class="app-card mb-4">
    <div class="app-card-body">
        <h2 class="h5">Reports for this software</h2>
        <div class="table-responsive">
            <table class="table app-table align-middle mb-0">
                <thead><tr><th>Report ID</th><th>Submitter</th><th>Version tested</th><th>Risk</th><th>Submitted</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($reports as $report): ?>
                    <tr>
                        <td>#<?= (int)$report['id'] ?></td><td><?= htmlspecialchars((string)$report['submitter_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($report['wordpress_version'] ?: '-'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><span class="badge text-bg-dark text-uppercase"><?= htmlspecialchars((string)$report['severity_resolved'], ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td title="<?= htmlspecialchars((string)$report['created_at'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(View::timeAgo((string)$report['created_at']), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <a href="/reports/<?= (int)$report['id'] ?><?= !empty($adminToken) ? '?admin_token=' . urlencode((string)$adminToken) : '' ?>" class="btn btn-sm btn-outline-secondary">Details</a>
                            <?php if (!empty($adminMode)): ?>
                                <form method="post" action="/reports/<?= (int)$report['id'] ?>/admin/hide<?= !empty($adminToken) ? '?admin_token=' . urlencode((string)$adminToken) : '' ?>" class="d-inline">
                                    <input type="hidden" name="software_id" value="<?= (int)$software['id'] ?>"><input type="hidden" name="admin_token" value="<?= htmlspecialchars((string)$adminToken, ENT_QUOTES, 'UTF-8') ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Hide</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<section class="app-card">
    <div class="app-card-body">
        <div class="discussion-block">
            <div class="discussion-head">
                <h2 class="h5 mb-0">Discussion</h2>
                <span class="badge text-bg-secondary">Software thread</span>
            </div>
            <?php if (!empty($flash)): ?><div class="alert alert-<?= htmlspecialchars((string)($flashType ?? 'info'), ENT_QUOTES, 'UTF-8') ?>" role="status"><?= htmlspecialchars((string)$flash, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
            <form method="post" action="/software/<?= (int)$software['id'] ?>/comments" class="row g-2 mb-3">
                <div class="col-md-4"><input class="form-control" name="author_name" placeholder="Your name"></div>
                <div class="col-md-6"><input class="form-control" name="comment" placeholder="Write a comment"></div>
                <div class="col-md-2 d-grid"><button class="btn btn-primary" type="submit">Post</button></div>
            </form>
            <?php if (!empty($adminMode)): ?>
                <form method="post" action="/software/<?= (int)$software['id'] ?>/admin/solution<?= !empty($adminToken) ? '?admin_token=' . urlencode((string)$adminToken) : '' ?>" class="row g-2 mb-4 border rounded p-3 bg-white">
                    <div class="col-12"><h3 class="h6 mb-0">Admin update</h3></div><input type="hidden" name="admin_token" value="<?= htmlspecialchars((string)$adminToken, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="col-md-4"><input class="form-control" name="author_name" value="Admin" placeholder="Admin name"></div>
                    <div class="col-md-6"><input class="form-control" name="comment" placeholder="Official status update"></div>
                    <div class="col-md-2 d-grid"><button class="btn btn-outline-primary" type="submit">Publish</button></div>
                </form>
            <?php endif; ?>
            <?php foreach ($comments as $comment): ?>
                <article class="comment-item">
                    <div class="small text-secondary"><?= htmlspecialchars((string)$comment['author_name'], ENT_QUOTES, 'UTF-8') ?> · <span title="<?= htmlspecialchars((string)$comment['created_at'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(View::timeAgo((string)$comment['created_at']), ENT_QUOTES, 'UTF-8') ?></span>
                        <?php if ((int)($comment['is_admin_solution'] ?? 0) === 1): ?><span class="badge text-bg-success ms-2">Official</span><?php endif; ?></div>
                    <p class="mb-0"><?= nl2br(htmlspecialchars((string)$comment['comment'], ENT_QUOTES, 'UTF-8')) ?></p>
                    <?php if (!empty($adminMode)): ?><form method="post" action="/software/<?= (int)$software['id'] ?>/comments/<?= (int)$comment['id'] ?>/hide<?= !empty($adminToken) ? '?admin_token=' . urlencode((string)$adminToken) : '' ?>" class="mt-2"><input type="hidden" name="admin_token" value="<?= htmlspecialchars((string)$adminToken, ENT_QUOTES, 'UTF-8') ?>"><button class="btn btn-sm btn-outline-danger" type="submit">Hide comment</button></form><?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
