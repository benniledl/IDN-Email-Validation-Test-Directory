<section class="card border-0 shadow-sm" aria-labelledby="software-title">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 id="software-title" class="h4 mb-0">Plugin/software overview</h1>
            <a href="/submit-report" class="btn btn-sm btn-primary">Submit report</a>
        </div>

        <?php if (!empty($flash)): ?>
            <div class="alert alert-<?= htmlspecialchars((string)($flashType ?? 'info'), ENT_QUOTES, 'UTF-8') ?>" role="status"><?= htmlspecialchars((string)$flash, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form class="row g-2 mb-4" method="get" action="/software">
            <div class="col-md-10">
                <label for="q" class="form-label">Search by software name</label>
                <input id="q" class="form-control" name="q" value="<?= htmlspecialchars((string)($search ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="e.g. contact form">
            </div>
            <div class="col-md-2 d-grid align-self-end">
                <button class="btn btn-outline-secondary" type="submit">Search</button>
            </div>
        </form>

        <div class="software-directory-list">
            <?php foreach ($softwareItems as $item): ?>
                <?php
                $decodedName = html_entity_decode((string)$item['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $description = trim((string)($item['description'] ?? ''));
                $overallSeverity = (string)($item['overall_severity'] ?? 'none');
                $severityTone = match ($overallSeverity) {
                    'high' => 'danger',
                    'medium' => 'warning',
                    'low' => 'info',
                    default => 'success',
                };
                ?>
                <article class="directory-plugin-card">
                    <div class="directory-plugin-icon-wrap">
                        <?php if (!empty($item['plugin_icon_url'])): ?>
                            <img
                                class="directory-plugin-icon"
                                src="<?= htmlspecialchars((string)$item['plugin_icon_url'], ENT_QUOTES, 'UTF-8') ?>"
                                <?php if (!empty($item['plugin_icon_2x_url'])): ?>
                                    srcset="<?= htmlspecialchars((string)$item['plugin_icon_url'], ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars((string)$item['plugin_icon_2x_url'], ENT_QUOTES, 'UTF-8') ?> 2x"
                                <?php endif; ?>
                                alt="<?= htmlspecialchars($decodedName, ENT_QUOTES, 'UTF-8') ?> icon"
                                loading="lazy"
                            >
                        <?php else: ?>
                            <div class="directory-plugin-fallback-icon"><?= strtoupper(substr(trim($decodedName), 0, 1)) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="directory-plugin-body">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                            <h2 class="h4 mb-0 directory-plugin-title">
                                <a href="/software/<?= (int)$item['id'] ?>"><?= htmlspecialchars($decodedName, ENT_QUOTES, 'UTF-8') ?></a>
                            </h2>
                            <span class="badge text-bg-<?= $severityTone ?> text-uppercase">Overall: <?= htmlspecialchars($overallSeverity, ENT_QUOTES, 'UTF-8') ?></span>
                        </div>

                        <?php if ($description !== ''): ?>
                            <p class="text-secondary mb-3"><?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>

                        <div class="directory-plugin-meta">
                            <?php if (!empty($item['plugin_author'])): ?>
                                <span>👤 <?= htmlspecialchars((string)$item['plugin_author'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                            <?php if (!empty($item['plugin_active_installs'])): ?>
                                <span>📦 <?= htmlspecialchars((string)$item['plugin_active_installs'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                            <?php if (!empty($item['plugin_tested'])): ?>
                                <span>🧪 Tested with <?= htmlspecialchars((string)$item['plugin_tested'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                            <span>📝 <?= (int)$item['report_count'] ?> report(s)</span>
                            <span>⚠️ <?= (int)$item['high_count'] ?>/<?= (int)$item['medium_count'] ?>/<?= (int)$item['low_count'] ?> (high/med/low)</span>
                        </div>

                        <a href="<?= htmlspecialchars((string)$item['canonical_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="small text-decoration-none d-inline-block mt-3">
                            <?= htmlspecialchars((string)$item['canonical_url'], ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
