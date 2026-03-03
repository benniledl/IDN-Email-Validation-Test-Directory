<section class="app-card app-hero" aria-labelledby="page-title">
    <h1 id="page-title" class="app-title">IDN Email Validation Test Directory</h1>
    <p class="text-muted-modern mb-4">Track software report histories, inspect detailed test outcomes, and submit new IDN validation results in one place.</p>
    <div class="d-flex flex-wrap gap-2">
        <a href="/submit-report" class="btn btn-primary">Submit a report</a>
        <a href="/software" class="btn btn-outline-secondary">Software overview</a>
    </div>
</section>

<?php if (!empty($flash)): ?>
    <div class="alert" role="status"><?= htmlspecialchars((string)$flash, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<section class="app-card" aria-labelledby="history-title">
    <div class="app-card-body">
        <h2 id="history-title" class="h5 mb-3">Latest public reports</h2>
        <div class="table-responsive">
            <table class="table app-table mb-0 align-middle">
                <thead>
                <tr>
                    <th>Software</th>
                    <th>Submitter</th>
                    <th>Severity</th>
                    <th>Created</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($history as $item): ?>
                    <tr>
                        <?php $historySoftwareName = html_entity_decode((string)$item['software_name'], ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?>
                        <td><a href="/software/<?= (int)$item['software_id'] ?>"><?= htmlspecialchars($historySoftwareName, ENT_QUOTES, 'UTF-8') ?></a></td>
                        <td><?= htmlspecialchars((string)$item['submitter_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><span class="badge text-bg-dark text-uppercase"><?= htmlspecialchars((string)$item['severity_auto'], ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td><?= htmlspecialchars((string)$item['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><a href="/reports/<?= (int)$item['id'] ?>" class="btn btn-sm btn-outline-secondary">Details</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
