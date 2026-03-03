<section class="card border-0 shadow-sm" aria-labelledby="software-title">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 id="software-title" class="h4 mb-0">Plugin/software overview</h1>
            <a href="/submit-report" class="btn btn-sm btn-primary">Submit report</a>
        </div>

        <?php if (!empty($flash)): ?>
            <div class="alert alert-<?= htmlspecialchars((string)($flashType ?? 'info'), ENT_QUOTES, 'UTF-8') ?>" role="status"><?= htmlspecialchars((string)$flash, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-striped mb-0 align-middle">
                <thead>
                <tr>
                    <th>Software</th>
                    <th>Type</th>
                    <th>Reports</th>
                    <th>High/Med/Low</th>
                    <th>Last report</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($softwareItems as $item): ?>
                    <tr>
                        <td>
                            <div class="fw-semibold"><?= htmlspecialchars((string)$item['name'], ENT_QUOTES, 'UTF-8') ?></div>
                            <a href="<?= htmlspecialchars((string)$item['canonical_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="small text-decoration-none">
                                <?= htmlspecialchars((string)$item['canonical_url'], ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars((string)$item['type'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (int)$item['report_count'] ?></td>
                        <td><?= (int)$item['high_count'] ?>/<?= (int)$item['medium_count'] ?>/<?= (int)$item['low_count'] ?></td>
                        <td><?= htmlspecialchars((string)($item['last_report_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><a href="/software/<?= (int)$item['id'] ?>" class="btn btn-sm btn-outline-secondary">View reports</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
