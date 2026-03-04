<?php $flashTone = match ((string)($flashType ?? 'info')) { 'danger' => 'error', default => (string)($flashType ?? 'info') }; ?>
<?php $old = is_array($old ?? null) ? $old : []; ?>
<?php $oldForm = (string)($old['_form'] ?? ''); ?>
<?php $oldAdminId = (int)($old['admin_id'] ?? 0); ?>
<?php $autoOpenModal = ($oldForm === 'admin_reset_password' && $oldAdminId > 0) ? ('admin-user-password-modal-' . $oldAdminId) : ''; ?>

<section class="card border border-base-300 bg-base-100 shadow-sm" aria-labelledby="admin-panel-title">
    <div class="card-body gap-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 id="admin-panel-title" class="text-2xl font-semibold tracking-tight">Administrator Panel</h1>
                <p class="text-base-content/70">Manage admin accounts and authentication access.</p>
            </div>
            <div class="flex gap-2">
                <a href="/software" class="btn btn-sm btn-ghost">Software overview</a>
                <form method="post" action="/admin/logout" class="inline">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)$adminCsrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <button class="btn btn-sm btn-outline" type="submit">Logout</button>
                </form>
            </div>
        </div>

        <?php if (!empty($flash)): ?>
            <div class="alert alert-<?= htmlspecialchars($flashTone, ENT_QUOTES, 'UTF-8') ?>" role="status" data-dismissible="true"><?= htmlspecialchars((string)$flash, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if (($adminSessionType ?? '') === 'token'): ?><div class="alert alert-info" role="status" data-dismissible="true">Signed in with primary access token.</div><?php endif; ?>

        <div class="grid gap-4 xl:grid-cols-[1.5fr_1fr]">
            <article class="card border border-base-300 bg-base-100 shadow-sm">
                <div class="card-body">
                    <h2 class="card-title text-lg">Admin users</h2>
                    <div class="overflow-x-auto md:overflow-visible">
                        <table class="table table-zebra">
                            <thead><tr><th>Name</th><th>Email</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
                            <tbody>
                            <?php foreach ($admins as $admin): ?>
                                <?php
                                $adminId = (int)$admin['id'];
                                $isActive = (int)($admin['is_active'] ?? 0) === 1;
                                $isCurrentSessionAdmin = (int)($adminSessionUserId ?? 0) === $adminId;
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars((string)$admin['name'], ENT_QUOTES, 'UTF-8') ?><?= $isCurrentSessionAdmin ? ' (You)' : '' ?></td>
                                    <td><?= htmlspecialchars((string)$admin['email'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><span class="badge <?= $isActive ? 'badge-success' : 'badge-ghost' ?> badge-outline"><?= $isActive ? 'Active' : 'Inactive' ?></span></td>
                                    <td class="text-right">
                                        <div class="dropdown dropdown-left table-actions-dropdown">
                                            <button tabindex="0" type="button" class="btn btn-xs btn-outline">Admin actions</button>
                                            <ul tabindex="0" class="menu dropdown-content z-[1] mt-1 w-52 rounded-box border border-base-300 bg-base-100 p-1 shadow-lg">
                                                <li><button type="button" data-admin-modal-open="admin-user-password-modal-<?= $adminId ?>">Reset password</button></li>
                                                <li>
                                                    <form method="post" action="/admin/users/status" data-confirm="<?= $isActive ? 'Deactivate this admin account?' : 'Activate this admin account?' ?>" class="w-full">
                                                        <input type="hidden" name="_form" value="admin_set_status">
                                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)$adminCsrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                                        <input type="hidden" name="admin_id" value="<?= $adminId ?>">
                                                        <input type="hidden" name="is_active" value="<?= $isActive ? '0' : '1' ?>">
                                                        <button type="submit" class="w-full text-left"><?= $isActive ? 'Deactivate account' : 'Activate account' ?></button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </article>

            <article class="card border border-base-300 bg-base-100 shadow-sm">
                <div class="card-body">
                    <h2 class="card-title text-lg">Create admin user</h2>
                    <form method="post" action="/admin/users" class="grid gap-2">
                        <input type="hidden" name="_form" value="admin_create_user">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)$adminCsrfToken, ENT_QUOTES, 'UTF-8') ?>">
                        <input id="new_admin_name" class="input input-bordered w-full" name="name" value="<?= htmlspecialchars($oldForm === 'admin_create_user' ? (string)($old['name'] ?? '') : '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Name" required>
                        <input id="new_admin_email" class="input input-bordered w-full" name="email" type="email" value="<?= htmlspecialchars($oldForm === 'admin_create_user' ? (string)($old['email'] ?? '') : '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Email" required>
                        <input id="new_admin_password" class="input input-bordered w-full" type="password" name="new_admin_password" value="<?= htmlspecialchars($oldForm === 'admin_create_user' ? (string)($old['new_admin_password'] ?? '') : '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Initial password" minlength="10" required>
                        <input id="new_admin_password_confirm" class="input input-bordered w-full" type="password" name="new_admin_password_confirm" value="<?= htmlspecialchars($oldForm === 'admin_create_user' ? (string)($old['new_admin_password_confirm'] ?? '') : '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Confirm password" minlength="10" required>
                        <div class="mt-1 flex justify-end"><button class="btn btn-primary" type="submit">Create user</button></div>
                    </form>
                </div>
            </article>
        </div>

        <?php if ((int)($adminSessionUserId ?? 0) > 0): ?>
            <section class="card border border-base-300 bg-base-100 shadow-sm" aria-labelledby="my-password-title">
                <div class="card-body">
                    <h2 id="my-password-title" class="card-title text-lg">My password</h2>
                    <form method="post" action="/admin/users/password" class="grid gap-2 md:grid-cols-[1fr_1fr_auto]">
                        <input type="hidden" name="_form" value="admin_my_password">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)$adminCsrfToken, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="admin_id" value="<?= (int)$adminSessionUserId ?>">
                        <input id="my_new_password" class="input input-bordered w-full" type="password" name="new_password" value="<?= htmlspecialchars($oldForm === 'admin_my_password' ? (string)($old['new_password'] ?? '') : '', ENT_QUOTES, 'UTF-8') ?>" placeholder="New password" minlength="10" required>
                        <input id="my_new_password_confirm" class="input input-bordered w-full" type="password" name="new_password_confirm" value="<?= htmlspecialchars($oldForm === 'admin_my_password' ? (string)($old['new_password_confirm'] ?? '') : '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Confirm password" minlength="10" required>
                        <button class="btn btn-outline" type="submit">Update</button>
                    </form>
                </div>
            </section>
        <?php endif; ?>
    </div>
</section>

<?php foreach ($admins as $admin): ?>
    <?php
    $adminId = (int)$admin['id'];
    $isActive = (int)($admin['is_active'] ?? 0) === 1;
    ?>
    <div class="admin-modal fixed inset-0 z-50 grid place-items-center bg-base-content/40 p-4" id="admin-user-password-modal-<?= $adminId ?>" hidden>
        <div class="card w-full max-w-xl border border-base-300 bg-base-100 shadow-xl" role="dialog" aria-modal="true" aria-labelledby="admin-user-password-title-<?= $adminId ?>">
            <div class="card-body gap-4">
                <div class="flex items-start justify-between gap-3">
                    <h3 class="card-title" id="admin-user-password-title-<?= $adminId ?>">Reset password for <?= htmlspecialchars((string)$admin['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                    <button type="button" class="btn btn-sm btn-square btn-ghost" aria-label="Close" data-admin-modal-close>x</button>
                </div>
                <form method="post" action="/admin/users/password" class="grid gap-2 md:grid-cols-[1fr_1fr_auto]">
                    <input type="hidden" name="_form" value="admin_reset_password">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)$adminCsrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="admin_id" value="<?= $adminId ?>">
                    <input id="new_password_<?= $adminId ?>" class="input input-bordered w-full" type="password" name="new_password" value="<?= htmlspecialchars(($oldForm === 'admin_reset_password' && $oldAdminId === $adminId) ? (string)($old['new_password'] ?? '') : '', ENT_QUOTES, 'UTF-8') ?>" placeholder="New password" minlength="10" required>
                    <input id="new_password_confirm_<?= $adminId ?>" class="input input-bordered w-full" type="password" name="new_password_confirm" value="<?= htmlspecialchars(($oldForm === 'admin_reset_password' && $oldAdminId === $adminId) ? (string)($old['new_password_confirm'] ?? '') : '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Confirm password" minlength="10" required>
                    <button class="btn btn-primary" type="submit">Update</button>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<?php if ($autoOpenModal !== ''): ?>
    <div id="auto-open-modal" data-target="<?= htmlspecialchars($autoOpenModal, ENT_QUOTES, 'UTF-8') ?>" hidden></div>
<?php endif; ?>
