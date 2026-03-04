<?php $flashTone = match ((string)($flashType ?? 'info')) { 'danger' => 'error', default => (string)($flashType ?? 'info') }; ?>

<section class="card border border-base-300 bg-base-100 shadow-sm" aria-labelledby="software-title">
    <div class="card-body gap-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 id="software-title" class="text-2xl font-semibold tracking-tight">Software Overview</h1>
                <p class="text-base-content/70">Browse reported software and open detailed report histories.</p>
            </div>
            <a href="/submit-report" class="btn btn-primary">Submit Report</a>
        </div>

        <?php if (!empty($flash)): ?>
            <div class="alert alert-<?= htmlspecialchars($flashTone, ENT_QUOTES, 'UTF-8') ?>" role="status" data-dismissible="true"><?= htmlspecialchars((string)$flash, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="get" action="/software" class="grid gap-2 md:grid-cols-[1fr_auto]">
            <label class="input input-bordered flex items-center gap-2">
                <span class="text-sm text-base-content/60">Search</span>
                <input id="q" name="q" class="grow" value="<?= htmlspecialchars((string)($search ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Software name">
            </label>
            <button class="btn btn-outline" type="submit">Apply</button>
        </form>

        <?php if (empty($softwareItems)): ?>
            <div class="rounded-box border border-base-300 bg-base-200 px-4 py-6 text-center text-base-content/70">No software entries match this filter.</div>
        <?php else: ?>
            <div class="grid gap-3">
                <?php foreach ($softwareItems as $item): ?>
                    <?php
                    $decodedName = html_entity_decode((string)$item['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $description = trim((string)($item['description'] ?? ''));
                    $overallSeverity = (string)($item['overall_severity'] ?? 'none');
                    $severityClass = match ($overallSeverity) {
                        'high' => 'badge-error',
                        'medium' => 'badge-warning',
                        'low' => 'badge-info',
                        default => 'badge-ghost',
                    };
                    $failCount = (int)$item['high_count'] + (int)$item['medium_count'] + (int)$item['low_count'];
                    ?>
                    <article class="card border border-base-300 bg-base-100 shadow-sm">
                        <div class="card-body gap-3 md:flex-row md:items-start">
                            <div class="shrink-0">
                                <?php if (!empty($item['plugin_icon_url'])): ?>
                                    <img class="h-16 w-16 rounded-lg border border-base-300 object-cover" src="<?= htmlspecialchars((string)$item['plugin_icon_url'], ENT_QUOTES, 'UTF-8') ?>" <?php if (!empty($item['plugin_icon_2x_url'])): ?>srcset="<?= htmlspecialchars((string)$item['plugin_icon_url'], ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars((string)$item['plugin_icon_2x_url'], ENT_QUOTES, 'UTF-8') ?> 2x"<?php endif; ?> alt="<?= htmlspecialchars($decodedName, ENT_QUOTES, 'UTF-8') ?> icon" loading="lazy">
                                <?php else: ?>
                                    <div class="grid h-16 w-16 place-items-center rounded-lg border border-base-300 bg-base-200 text-lg font-semibold"><?= strtoupper(substr(trim($decodedName), 0, 1)) ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="grow">
                                <div class="mb-1 flex flex-wrap items-center justify-between gap-2">
                                    <h2 class="text-lg font-semibold"><a href="/software/<?= (int)$item['id'] ?>" class="link link-hover"><?= htmlspecialchars($decodedName, ENT_QUOTES, 'UTF-8') ?></a></h2>
                                    <span class="badge <?= $severityClass ?> badge-outline uppercase">Severity: <?= htmlspecialchars($overallSeverity, ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                                <?php if ($description !== ''): ?><p class="mb-2 text-sm text-base-content/70"><?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
                                <div class="mb-3 flex flex-wrap gap-2 text-xs text-base-content/60">
                                    <?php if (!empty($item['plugin_author'])): ?><span class="badge badge-ghost">Author: <?= htmlspecialchars((string)$item['plugin_author'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                                    <span class="badge badge-ghost">Reports: <?= (int)$item['report_count'] ?></span>
                                    <span class="badge <?= $failCount > 0 ? 'badge-error' : 'badge-success' ?> badge-outline">Fails: <?= $failCount ?></span>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <a href="/software/<?= (int)$item['id'] ?>" class="btn btn-sm btn-outline">Open</a>
                                    <a href="<?= htmlspecialchars((string)$item['canonical_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="btn btn-sm btn-ghost">Official page</a>
                                    <?php if (!empty($adminMode) && (string)($item['type'] ?? '') === 'other'): ?>
                                        <form method="post" action="/software/<?= (int)$item['id'] ?>/admin/hide" data-confirm="Delete this custom software entry?" class="inline">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)$adminCsrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                            <button class="btn btn-sm btn-error btn-outline" type="submit" title="Delete software">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2m-8 0l1 12a1 1 0 001 1h6a1 1 0 001-1l1-12"/></svg>
                                                Delete
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
