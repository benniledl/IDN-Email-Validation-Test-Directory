<section class="card border border-base-300 bg-base-100 shadow-sm" aria-labelledby="reports-title">
    <div class="card-body gap-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 id="reports-title" class="text-2xl font-semibold tracking-tight">Reports</h1>
                <p class="text-base-content/70">Browse published test reports and jump directly to details.</p>
            </div>
            <div class="flex gap-2">
                <a href="/submit-report" class="btn btn-sm btn-primary">Submit report</a>
                <a href="/software" class="btn btn-sm btn-outline">Software</a>
            </div>
        </div>

        <?php
        $selectedSeverity = (string)($severity ?? '');
        $activeSearch = trim((string)($search ?? ''));
        $hasActiveFilters = $activeSearch !== '' || $selectedSeverity !== '';
        ?>
        <details class="rounded-box border border-base-300 bg-base-200/50" <?= $hasActiveFilters ? 'open' : '' ?>>
            <summary class="flex cursor-pointer list-none items-center justify-between gap-2 px-3 py-3 md:px-4">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-sm font-semibold">Filters</span>
                    <?php if ($hasActiveFilters): ?><span class="badge badge-outline badge-sm">Active</span><?php endif; ?>
                </div>
                <div class="flex items-center gap-2">
                    <span class="badge badge-ghost"><?= count($reports) ?> result<?= count($reports) === 1 ? '' : 's' ?></span>
                    <span class="text-xs text-base-content/60">Toggle</span>
                </div>
            </summary>
            <div class="border-t border-base-300 px-3 pb-4 pt-3 md:px-4 md:pb-5 md:pt-4">
                <div class="bg-base-100 py-3 md:py-4">
                    <form method="get" action="/reports" class="grid gap-3 md:grid-cols-[minmax(0,1fr)_220px_auto] md:items-end">
                        <label class="form-control">
                            <div class="label py-0 pb-1">
                                <span class="label-text">Search by software or submitter</span>
                            </div>
                            <input type="search" name="q" value="<?= htmlspecialchars((string)($search ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="input input-bordered w-full" placeholder="e.g. Contact Form 7 or Alex" aria-label="Search reports">
                        </label>
                        <label class="form-control">
                            <div class="label py-0 pb-1">
                                <span class="label-text">Severity</span>
                            </div>
                            <select name="severity" class="select select-bordered w-full" aria-label="Filter by severity">
                                <option value="" <?= $selectedSeverity === '' ? 'selected' : '' ?>>All severities</option>
                                <option value="high" <?= $selectedSeverity === 'high' ? 'selected' : '' ?>>High</option>
                                <option value="medium" <?= $selectedSeverity === 'medium' ? 'selected' : '' ?>>Medium</option>
                                <option value="low" <?= $selectedSeverity === 'low' ? 'selected' : '' ?>>Low</option>
                                <option value="none" <?= $selectedSeverity === 'none' ? 'selected' : '' ?>>None</option>
                            </select>
                        </label>
                        <div class="flex flex-wrap gap-2 pb-1 md:justify-end md:pb-0.5">
                            <button type="submit" class="btn btn-primary">Apply</button>
                            <?php if ($hasActiveFilters): ?>
                                <a href="/reports" class="btn btn-ghost">Reset</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </details>

        <?php if (empty($reports)): ?>
            <div class="rounded-box border border-base-300 bg-base-200 px-4 py-6 text-center text-base-content/70">No reports found for this filter.</div>
        <?php else: ?>
            <div class="space-y-2 md:hidden">
                <?php foreach ($reports as $report): ?>
                    <?php
                    $severityValue = (string)($report['severity_resolved'] ?? 'none');
                    $severityBadge = match ($severityValue) {
                        'high' => 'badge-error',
                        'medium' => 'badge-warning',
                        'low' => 'badge-info',
                        default => 'badge-success',
                    };
                    $reportSoftwareName = html_entity_decode((string)$report['software_name'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    ?>
                    <article class="rounded-box border border-base-300 bg-base-100 p-3">
                        <div class="mb-2 flex items-center justify-between gap-2">
                            <a href="/reports/<?= (int)$report['id'] ?>" class="font-semibold link link-hover">Report #<?= (int)$report['id'] ?></a>
                            <span class="badge <?= $severityBadge ?> badge-outline uppercase"><?= htmlspecialchars($severityValue, ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <p class="mb-2 text-sm"><a href="/software/<?= (int)$report['software_id'] ?>" class="link link-hover"><?= htmlspecialchars($reportSoftwareName, ENT_QUOTES, 'UTF-8') ?></a></p>
                        <div class="text-xs text-base-content/70">
                            <span><?= htmlspecialchars((string)$report['submitter_name'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="mx-1">·</span>
                            <span>Version: <?= htmlspecialchars((string)($report['wordpress_version'] ?: '-'), ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="mx-1">·</span>
                            <span title="<?= htmlspecialchars((string)$report['created_at'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(View::timeAgo((string)$report['created_at']), ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="hidden overflow-x-auto md:block">
                <table class="table table-zebra">
                    <thead>
                    <tr>
                        <th>Report</th>
                        <th>Software</th>
                        <th>Submitter</th>
                        <th>Version</th>
                        <th>Severity</th>
                        <th>Submitted</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($reports as $report): ?>
                        <?php
                        $severityValue = (string)($report['severity_resolved'] ?? 'none');
                        $severityBadge = match ($severityValue) {
                            'high' => 'badge-error',
                            'medium' => 'badge-warning',
                            'low' => 'badge-info',
                            default => 'badge-success',
                        };
                        $reportSoftwareName = html_entity_decode((string)$report['software_name'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        ?>
                        <tr>
                            <td><a href="/reports/<?= (int)$report['id'] ?>" class="link link-hover font-medium">#<?= (int)$report['id'] ?></a></td>
                            <td><a href="/software/<?= (int)$report['software_id'] ?>" class="link link-hover"><?= htmlspecialchars($reportSoftwareName, ENT_QUOTES, 'UTF-8') ?></a></td>
                            <td><?= htmlspecialchars((string)$report['submitter_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($report['wordpress_version'] ?: '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><span class="badge <?= $severityBadge ?> badge-outline uppercase"><?= htmlspecialchars($severityValue, ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td title="<?= htmlspecialchars((string)$report['created_at'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(View::timeAgo((string)$report['created_at']), ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>
