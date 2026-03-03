<section class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
            <div>
                <?php $softwareName = html_entity_decode((string)$software['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?>
                <h1 class="h4 mb-1"><?= htmlspecialchars($softwareName, ENT_QUOTES, 'UTF-8') ?></h1>
                <div class="text-secondary small"><?= htmlspecialchars((string)$software['type'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php if (!empty($software['plugin_author']) || !empty($software['plugin_active_installs']) || !empty($software['plugin_tested'])): ?>
                    <div class="small text-secondary mt-1">
                        <?php if (!empty($software['plugin_author'])): ?><span class="me-3">👤 <?= htmlspecialchars((string)$software['plugin_author'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                        <?php if (!empty($software['plugin_active_installs'])): ?><span class="me-3">📦 <?= htmlspecialchars((string)$software['plugin_active_installs'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                        <?php if (!empty($software['plugin_tested'])): ?><span>🧪 Tested with <?= htmlspecialchars((string)$software['plugin_tested'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            <a href="/software" class="btn btn-sm btn-outline-secondary">Back to overview</a>
        </div>
        <a href="<?= htmlspecialchars((string)$software['canonical_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="d-inline-block mb-2">
            <?= htmlspecialchars((string)$software['canonical_url'], ENT_QUOTES, 'UTF-8') ?>
        </a>
        <p class="mb-0"><?= nl2br(htmlspecialchars((string)($software['description'] ?: 'No description provided yet.'), ENT_QUOTES, 'UTF-8')) ?></p>
    </div>
</section>

<section class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <h2 class="h5">Reports for this software</h2>
        <div class="table-responsive">
            <table class="table table-striped align-middle mb-0">
                <thead>
                <tr>
                    <th>Report ID</th>
                    <th>Submitter</th>
                    <th>Version tested</th>
                    <th>Severity</th>
                    <th>Created</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($reports as $report): ?>
                    <tr>
                        <td>#<?= (int)$report['id'] ?></td>
                        <td><?= htmlspecialchars((string)$report['submitter_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($report['wordpress_version'] ?: '-'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><span class="badge text-bg-dark text-uppercase"><?= htmlspecialchars((string)$report['severity_auto'], ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td><?= htmlspecialchars((string)$report['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><a href="/reports/<?= (int)$report['id'] ?>" class="btn btn-sm btn-outline-secondary">Details</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<section class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <h2 class="h5">Software comments</h2>

        <?php if (!empty($flash)): ?>
            <div class="alert alert-<?= htmlspecialchars((string)($flashType ?? 'info'), ENT_QUOTES, 'UTF-8') ?>" role="status"><?= htmlspecialchars((string)$flash, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="post" action="/software/<?= (int)$software['id'] ?>/comments" class="row g-2 mb-3">
            <div class="col-md-4"><input class="form-control" name="author_name" placeholder="Your name"></div>
            <div class="col-md-6"><input class="form-control" name="comment" placeholder="Add a software-level comment"></div>
            <div class="col-md-2 d-grid"><button class="btn btn-primary" type="submit">Post</button></div>
        </form>

        <?php foreach ($comments as $comment): ?>
            <article class="comment-item">
                <div class="small text-secondary"><?= htmlspecialchars((string)$comment['author_name'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars((string)$comment['created_at'], ENT_QUOTES, 'UTF-8') ?></div>
                <p class="mb-0"><?= nl2br(htmlspecialchars((string)$comment['comment'], ENT_QUOTES, 'UTF-8')) ?></p>
            </article>
        <?php endforeach; ?>
    </div>
</section>
