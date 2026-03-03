<section class="app-card mb-4">
    <div class="app-card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
            <div>
                <h1 class="h4 mb-1">Report #<?= (int)$report['id'] ?></h1>
                <p class="text-secondary mb-0">Snapshot of one submitted validation run.</p>
            </div>
            <a href="/software/<?= (int)$report['software_id'] ?><?= !empty($adminToken) ? '?admin_token=' . urlencode((string)$adminToken) : '' ?>" class="btn btn-sm btn-outline-secondary">Back to software</a>
        </div>

        <?php $reportSoftwareName = html_entity_decode((string)$report['software_name'], ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?>
        <div class="report-summary-grid">
            <div class="report-summary-item">
                <span class="report-summary-label">Software</span>
                <a href="/software/<?= (int)$report['software_id'] ?><?= !empty($adminToken) ? '?admin_token=' . urlencode((string)$adminToken) : '' ?>" class="report-summary-value"><?= htmlspecialchars($reportSoftwareName, ENT_QUOTES, 'UTF-8') ?></a>
            </div>
            <div class="report-summary-item">
                <span class="report-summary-label">Submitted by</span>
                <span class="report-summary-value"><?= htmlspecialchars((string)$report['submitter_name'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="report-summary-item">
                <span class="report-summary-label">Risk</span>
                <span class="report-summary-value"><span class="badge text-bg-dark text-uppercase"><?= htmlspecialchars((string)$report['severity_resolved'], ENT_QUOTES, 'UTF-8') ?></span></span>
            </div>
            <div class="report-summary-item">
                <span class="report-summary-label">Version tested</span>
                <span class="report-summary-value"><?= htmlspecialchars((string)($report['wordpress_version'] ?: '-'), ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="report-summary-item report-summary-item-wide">
                <span class="report-summary-label">Notes</span>
                <span class="report-summary-value"><?= nl2br(htmlspecialchars((string)($report['submission_comment'] ?: '—'), ENT_QUOTES, 'UTF-8')) ?></span>
            </div>
        </div>

        <?php if (!empty($adminMode)): ?>
            <form method="post" action="/reports/<?= (int)$report['id'] ?>/admin/severity<?= !empty($adminToken) ? '?admin_token=' . urlencode((string)$adminToken) : '' ?>" class="row g-2 mt-3 border rounded p-3 bg-white">
                <div class="col-md-6">
                    <label for="severity_admin_override" class="form-label mb-1">Admin risk override</label>
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

<section class="app-card mb-4">
    <div class="app-card-body">
        <h2 class="h5 mb-3">Test outcomes</h2>
        <div class="table-responsive">
            <table class="table app-table align-middle mb-0">
                <thead>
                <tr><th>Email</th><th>Expected</th><th>Actual</th><th>Result</th></tr>
                </thead>
                <tbody>
                <?php foreach ($tests as $test): ?>
                    <?php $failed = (int)$test['failure_detected'] === 1; ?>
                    <tr>
                        <td><code><?= htmlspecialchars((string)$test['email_address'], ENT_QUOTES, 'UTF-8') ?></code></td>
                        <td><?= (int)$test['expected_valid'] === 1 ? 'Valid' : 'Invalid' ?></td>
                        <td><?= htmlspecialchars((string)$test['actual_result'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><span class="badge text-bg-<?= $failed ? 'danger' : 'success' ?>"><?= $failed ? 'Failure' : 'Pass' ?></span></td>
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
                <span class="badge text-bg-secondary">Report thread</span>
            </div>
            <?php if (!empty($flash)): ?><div class="alert alert-<?= htmlspecialchars((string)($flashType ?? 'info'), ENT_QUOTES, 'UTF-8') ?>" role="status"><?= htmlspecialchars((string)$flash, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
            <form method="post" action="/reports/<?= (int)$report['id'] ?>/comments" class="row g-2 mb-3">
                <div class="col-md-4"><input class="form-control" name="author_name" placeholder="Your name"></div>
                <div class="col-md-6"><input class="form-control" name="comment" placeholder="Write a comment"></div>
                <div class="col-md-2 d-grid"><button class="btn btn-primary" type="submit">Post</button></div>
            </form>
            <?php foreach ($comments as $comment): ?>
                <article class="comment-item">
                    <div class="small text-secondary"><?= htmlspecialchars((string)$comment['author_name'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars((string)$comment['created_at'], ENT_QUOTES, 'UTF-8') ?></div>
                    <p class="mb-0"><?= nl2br(htmlspecialchars((string)$comment['comment'], ENT_QUOTES, 'UTF-8')) ?></p>
                    <?php if (!empty($adminMode)): ?><form method="post" action="/reports/<?= (int)$report['id'] ?>/comments/<?= (int)$comment['id'] ?>/hide<?= !empty($adminToken) ? '?admin_token=' . urlencode((string)$adminToken) : '' ?>" class="mt-2"><input type="hidden" name="admin_token" value="<?= htmlspecialchars((string)$adminToken, ENT_QUOTES, 'UTF-8') ?>"><button class="btn btn-sm btn-outline-danger" type="submit">Hide comment</button></form><?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
