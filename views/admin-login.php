<?php $flashTone = match ((string)($flashType ?? 'info')) { 'danger' => 'error', default => (string)($flashType ?? 'info') }; ?>
<?php $old = is_array($old ?? null) ? $old : []; ?>
<?php $oldForm = (string)($old['_form'] ?? ''); ?>

<section class="card border border-base-300 bg-base-100 shadow-sm" aria-labelledby="admin-login-title">
    <div class="card-body gap-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 id="admin-login-title" class="text-2xl font-semibold tracking-tight">Administrator Login</h1>
                <p class="text-base-content/70">Use account credentials. Primary token is available for bootstrap access.</p>
            </div>
            <a href="/software" class="btn btn-sm btn-ghost">Software overview</a>
        </div>

        <?php if (!empty($flash)): ?>
            <div class="alert alert-<?= htmlspecialchars($flashTone, ENT_QUOTES, 'UTF-8') ?>" role="status" data-dismissible="true"><?= htmlspecialchars((string)$flash, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if (!empty($adminMode)): ?>
            <div class="alert alert-success" role="status" data-dismissible="true">
                <span>Administrator session is active.</span>
                <div class="flex gap-2">
                    <a href="/admin" class="btn btn-xs btn-outline">Open admin panel</a>
                    <form method="post" action="/admin/logout" class="inline">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)$adminCsrfToken, ENT_QUOTES, 'UTF-8') ?>">
                        <button class="btn btn-xs btn-ghost" type="submit">Logout</button>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <form method="post" action="/admin/login" class="grid gap-3 rounded-box border border-base-300 bg-base-100 p-4">
                <input type="hidden" name="_form" value="admin_login_password">
                <input type="hidden" name="login_mode" value="password">
                <div class="grid gap-3 md:grid-cols-2">
                    <div>
                        <label for="email" class="label"><span class="label-text">Admin email</span></label>
                        <input id="email" class="input input-bordered w-full" type="email" name="email" value="<?= htmlspecialchars($oldForm === 'admin_login_password' ? (string)($old['email'] ?? '') : '', ENT_QUOTES, 'UTF-8') ?>" autocomplete="username" required>
                    </div>
                    <div>
                        <label for="password" class="label"><span class="label-text">Password</span></label>
                        <input id="password" class="input input-bordered w-full" type="password" name="password" value="<?= htmlspecialchars($oldForm === 'admin_login_password' ? (string)($old['password'] ?? '') : '', ENT_QUOTES, 'UTF-8') ?>" autocomplete="current-password" required>
                    </div>
                </div>
                <div class="flex justify-end"><button class="btn btn-primary" type="submit">Login</button></div>
            </form>

            <details class="collapse collapse-arrow rounded-box border border-base-300 bg-base-100" <?= $oldForm === 'admin_login_token' ? 'open' : '' ?>>
                <summary class="collapse-title text-sm font-medium">Primary access token</summary>
                <div class="collapse-content">
                    <form method="post" action="/admin/login" class="grid gap-3">
                        <input type="hidden" name="_form" value="admin_login_token">
                        <input type="hidden" name="login_mode" value="token">
                        <div>
                            <label for="admin_token" class="label"><span class="label-text">Primary admin token</span></label>
                            <input id="admin_token" class="input input-bordered w-full" type="password" name="admin_token" value="<?= htmlspecialchars($oldForm === 'admin_login_token' ? (string)($old['admin_token'] ?? '') : '', ENT_QUOTES, 'UTF-8') ?>" autocomplete="current-password" required>
                        </div>
                        <div class="flex justify-end"><button class="btn btn-outline" type="submit">Login with token</button></div>
                    </form>
                </div>
            </details>
        <?php endif; ?>
    </div>
</section>
