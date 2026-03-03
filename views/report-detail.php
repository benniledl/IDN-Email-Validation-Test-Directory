<section class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">Report #<?= (int)$report['id'] ?></h1>
            <a href="/software/<?= (int)$report['software_id'] ?><?= !empty($adminToken) ? '?admin_token=' . urlencode((string)$adminToken) : '' ?>" class="btn btn-sm btn-outline-secondary">Back to software</a>
        </div>
        <?php $reportSoftwareName = html_entity_decode((string)$report['software_name'], ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?>
        <p class="mb-1"><strong>Software:</strong> <a href="/software/<?= (int)$report['software_id'] ?><?= !empty($adminToken) ? '?admin_token=' . urlencode((string)$adminToken) : '' ?>"><?= htmlspecialchars($reportSoftwareName, ENT_QUOTES, 'UTF-8') ?></a></p>
        <p class="mb-1"><strong>Submitter:</strong> <?= htmlspecialchars((string)$report['submitter_name'], ENT_QUOTES, 'UTF-8') ?></p>
        <p class="mb-1"><strong>Severity:</strong> <span class="badge text-bg-dark text-uppercase"><?= htmlspecialchars((string)$report['severity_resolved'], ENT_QUOTES, 'UTF-8') ?></span></p>
        <p class="mb-1"><strong>Version tested:</strong> <?= htmlspecialchars((string)($report['wordpress_version'] ?: '-'), ENT_QUOTES, 'UTF-8') ?></p>
        <p class="mb-0"><strong>Comment:</strong> <?= nl2br(htmlspecialchars((string)($report['submission_comment'] ?: 'No submitter comment.'), ENT_QUOTES, 'UTF-8')) ?></p>

        <?php if (!empty($adminMode)): ?>
            <form method="post" action="/reports/<?= (int)$report['id'] ?>/admin/severity<?= !empty($adminToken) ? '?admin_token=' . urlencode((string)$adminToken) : '' ?>" class="row g-2 mt-3 border rounded p-3">
                <div class="col-md-6">
                    <label for="severity_admin_override" class="form-label mb-1">Admin severity override</label>
                    <select id="severity_admin_override" name="severity_admin_override" class="form-select">
                        <option value="">No override (use auto)</option>
                        <?php foreach (['none', 'low', 'medium', 'high'] as $severityOption): ?>
                            <option value="<?= $severityOption ?>" <?= (string)($report['severity_admin_override'] ?? '') === $severityOption ? 'selected' : '' ?>><?= strtoupper($severityOption) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="admin_token" value="<?= htmlspecialchars((string)$adminToken, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-3 d-grid align-self-end"><button type="submit" class="btn btn-outline-primary">Save override</button></div>
            </form>
        <?php endif; ?>
    </div>
</section>

<section class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <h2 class="h5">Detailed test outcomes</h2>
        <div class="table-responsive">
            <table class="table table-striped align-middle mb-0">
                <thead>
                <tr>
                    <th>Email</th>
                    <th>Expected</th>
                    <th>Actual</th>
                    <th>Failure</th>
                    <th>Weight</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($tests as $test): ?>
                    <tr>
                        <td><code><?= htmlspecialchars((string)$test['email_address'], ENT_QUOTES, 'UTF-8') ?></code></td>
                        <td><?= (int)$test['expected_valid'] === 1 ? 'Valid' : 'Invalid' ?></td>
                        <td><?= htmlspecialchars((string)$test['actual_result'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (int)$test['failure_detected'] === 1 ? 'Yes' : 'No' ?></td>
                        <td><?= (int)$test['severity_weight'] ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<section class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <h2 class="h5">Report comments</h2>

        <?php if (!empty($flash)): ?>
            <div class="alert alert-<?= htmlspecialchars((string)($flashType ?? 'info'), ENT_QUOTES, 'UTF-8') ?>" role="status"><?= htmlspecialchars((string)$flash, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="post" action="/reports/<?= (int)$report['id'] ?>/comments" class="row g-2 mb-3">
            <div class="col-md-4"><input class="form-control" name="author_name" placeholder="Your name"></div>
            <div class="col-md-6"><input class="form-control" name="comment" placeholder="Add a report-level comment"></div>
            <div class="col-md-2 d-grid"><button class="btn btn-primary" type="submit">Post</button></div>
        </form>

        <?php foreach ($comments as $comment): ?>
            <article class="comment-item">
                <div class="small text-secondary"><?= htmlspecialchars((string)$comment['author_name'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars((string)$comment['created_at'], ENT_QUOTES, 'UTF-8') ?></div>
                <p class="mb-0"><?= nl2br(htmlspecialchars((string)$comment['comment'], ENT_QUOTES, 'UTF-8')) ?></p>
                <?php if (!empty($adminMode)): ?>
                    <form method="post" action="/reports/<?= (int)$report['id'] ?>/comments/<?= (int)$comment['id'] ?>/hide<?= !empty($adminToken) ? '?admin_token=' . urlencode((string)$adminToken) : '' ?>" class="mt-2">
                        <input type="hidden" name="admin_token" value="<?= htmlspecialchars((string)$adminToken, ENT_QUOTES, 'UTF-8') ?>">
                        <button class="btn btn-sm btn-outline-danger" type="submit">Hide comment</button>
                    </form>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
</section>
