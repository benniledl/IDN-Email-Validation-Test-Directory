<?php $flashTone = match ((string)($flashType ?? 'info')) { 'danger' => 'error', default => (string)($flashType ?? 'info') }; ?>
<?php $old = is_array($old ?? null) ? $old : []; ?>
<?php $isOldForm = (string)($old['_form'] ?? '') === 'submit_report'; ?>

<section class="card border border-base-300 bg-base-100 shadow-sm" aria-labelledby="submit-title">
    <div class="card-body gap-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 id="submit-title" class="text-2xl font-semibold tracking-tight">Submit Report</h1>
                <p class="text-base-content/70">Fast workflow: software, reporter, and tested outcomes.</p>
            </div>
            <a href="/software" class="btn btn-sm btn-ghost">Back to software</a>
        </div>

        <?php if (!empty($flash)): ?>
            <div class="alert alert-<?= htmlspecialchars($flashTone, ENT_QUOTES, 'UTF-8') ?>" role="status" data-dismissible="true"><?= htmlspecialchars((string)$flash, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <div id="submit-form-status" class="alert alert-error" role="alert" aria-live="polite" hidden></div>

        <form method="post" action="/submissions" class="grid gap-4" novalidate>
            <input type="hidden" name="_form" value="submit_report">
            <section class="rounded-box border border-base-300 bg-base-100 p-4">
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-base-content/70">1. Software</h2>

                <div class="mb-3 grid gap-2 sm:grid-cols-2" role="radiogroup" aria-label="Software type">
                    <input type="radio" class="btn-check" name="software_type" id="software_type_wp" value="wp_plugin" <?= (($isOldForm ? (string)($old['software_type'] ?? 'wp_plugin') : 'wp_plugin') === 'wp_plugin') ? 'checked' : '' ?>>
                    <label class="card cursor-pointer border border-base-300 bg-base-100 p-3" for="software_type_wp">
                        <span class="font-semibold">WordPress plugin</span>
                        <span class="text-sm text-base-content/70">Use plugin URL or slug.</span>
                    </label>

                    <input type="radio" class="btn-check" name="software_type" id="software_type_other" value="other" <?= (($isOldForm ? (string)($old['software_type'] ?? 'wp_plugin') : 'wp_plugin') === 'other') ? 'checked' : '' ?>>
                    <label class="card cursor-pointer border border-base-300 bg-base-100 p-3" for="software_type_other">
                        <span class="font-semibold">External software</span>
                        <span class="text-sm text-base-content/70">Use full product URL.</span>
                    </label>
                </div>

                <div class="grid gap-3 md:grid-cols-2">
                    <div id="software_name_group">
                        <label for="software_name" class="label"><span class="label-text font-medium">Software name *</span></label>
                        <input id="software_name" name="software_name" class="input input-bordered w-full" value="<?= htmlspecialchars($isOldForm ? (string)($old['software_name'] ?? '') : '', ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div>
                        <label for="software_url" class="label"><span class="label-text font-medium" id="software_url_label">WordPress plugin URL *</span></label>
                        <input id="software_url" name="software_url" class="input input-bordered w-full" value="<?= htmlspecialchars($isOldForm ? (string)($old['software_url'] ?? '') : '', ENT_QUOTES, 'UTF-8') ?>" placeholder="e.g. contact-form-7 or https://wordpress.org/plugins/contact-form-7/" required>
                        <p class="mt-1 text-xs text-base-content/70" id="software_url_help">You can paste a plugin URL or slug.</p>
                    </div>
                </div>
            </section>

            <section class="rounded-box border border-base-300 bg-base-100 p-4">
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-base-content/70">2. Reporter</h2>
                <div class="grid gap-3 md:grid-cols-2">
                    <div>
                        <label for="submitter_name" class="label"><span class="label-text font-medium">Your name *</span></label>
                        <input id="submitter_name" name="submitter_name" class="input input-bordered w-full" value="<?= htmlspecialchars($isOldForm ? (string)($old['submitter_name'] ?? '') : '', ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div>
                        <label for="submitter_email" class="label"><span class="label-text font-medium">Your email *</span></label>
                        <input id="submitter_email" name="submitter_email" type="email" class="input input-bordered w-full" value="<?= htmlspecialchars($isOldForm ? (string)($old['submitter_email'] ?? '') : '', ENT_QUOTES, 'UTF-8') ?>" autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false" required>
                        <p class="mt-1 text-xs text-base-content/70">Visible only to administrators.</p>
                    </div>
                </div>
            </section>

            <section class="rounded-box border border-base-300 bg-base-100 p-4">
                <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-base-content/70">3. Tested outcomes</h2>
                    <span class="badge badge-outline" id="tested-count-badge">0 tested</span>
                </div>
                <p class="mb-3 text-sm text-base-content/70">Select only templates you tested. At least one result is required.</p>

                <div class="overflow-x-auto">
                    <table class="table table-zebra">
                        <thead>
                        <tr>
                            <th>Email template</th>
                            <th>Expected</th>
                            <th>Your result *</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($templates as $template): ?>
                            <?php $expectedLabel = (int)$template['expected_valid'] === 1 ? 'Valid' : 'Invalid'; ?>
                            <tr>
                                <td><code><?= htmlspecialchars((string)$template['email_address'], ENT_QUOTES, 'UTF-8') ?></code></td>
                                <td><span class="badge badge-ghost"><?= $expectedLabel ?></span></td>
                                <td>
                                    <?php $oldResult = $isOldForm ? (string)($old['result_' . (int)$template['id']] ?? 'not_tested') : 'not_tested'; ?>
                                    <select
                                        name="result_<?= (int)$template['id'] ?>"
                                        class="select select-bordered select-sm w-full max-w-xs result-select state-not-tested"
                                        data-expected="<?= (int)$template['expected_valid'] ?>"
                                        aria-label="Result for <?= htmlspecialchars((string)$template['email_address'], ENT_QUOTES, 'UTF-8') ?>">
                                        <option value="not_tested" <?= $oldResult === 'not_tested' ? 'selected' : '' ?>>Not tested</option>
                                        <option value="accepted" <?= $oldResult === 'accepted' ? 'selected' : '' ?>>Accepted</option>
                                        <option value="rejected" <?= $oldResult === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                                    </select>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="rounded-box border border-dashed border-base-300 bg-base-100 p-4">
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-base-content/60">Optional details</h2>
                <div class="grid gap-3 md:grid-cols-3">
                    <div>
                        <label for="wordpress_version" class="label"><span class="label-text">Version tested</span></label>
                        <input id="wordpress_version" name="wordpress_version" class="input input-bordered w-full" value="<?= htmlspecialchars($isOldForm ? (string)($old['wordpress_version'] ?? '') : '', ENT_QUOTES, 'UTF-8') ?>" placeholder="e.g. 6.8.1">
                    </div>
                    <div>
                        <label for="submitter_role" class="label"><span class="label-text">Role</span></label>
                        <?php $oldRole = $isOldForm ? (string)($old['submitter_role'] ?? '') : ''; ?>
                        <select id="submitter_role" name="submitter_role" class="select select-bordered w-full">
                            <option value="" <?= $oldRole === '' ? 'selected' : '' ?>>Prefer not to say</option>
                            <option value="developer" <?= $oldRole === 'developer' ? 'selected' : '' ?>>Developer</option>
                            <option value="user" <?= $oldRole === 'user' ? 'selected' : '' ?>>User</option>
                        </select>
                    </div>
                    <div id="software_description_group">
                        <label for="software_description" class="label"><span class="label-text">Software description</span></label>
                        <input id="software_description" name="software_description" class="input input-bordered w-full" value="<?= htmlspecialchars($isOldForm ? (string)($old['software_description'] ?? '') : '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Optional context">
                    </div>
                </div>
                <div class="mt-3">
                    <label for="submission_comment" class="label"><span class="label-text">Notes</span></label>
                    <textarea id="submission_comment" name="submission_comment" class="textarea textarea-bordered w-full" rows="2" placeholder="Any environment notes"><?= htmlspecialchars($isOldForm ? (string)($old['submission_comment'] ?? '') : '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
            </section>

            <div class="flex justify-end gap-2">
                <a href="/software" class="btn btn-ghost">Cancel</a>
                <button id="submit-button" class="btn btn-primary" type="submit">Publish report</button>
            </div>
        </form>
    </div>
</section>
