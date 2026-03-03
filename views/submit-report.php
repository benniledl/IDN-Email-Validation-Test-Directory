<section class="card border-0 shadow-sm" aria-labelledby="submit-title">
    <div class="card-body p-4">
        <h1 id="submit-title" class="h4 mb-3">Submit a report</h1>
        <p class="text-secondary">Provide software details, fill your tested outcomes, and publish the report instantly.</p>

        <?php if (!empty($flash)): ?>
            <div class="alert alert-<?= htmlspecialchars((string)($flashType ?? 'info'), ENT_QUOTES, 'UTF-8') ?>" role="status"><?= htmlspecialchars((string)$flash, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="post" action="/submissions" class="row g-3" novalidate>
            <div class="col-md-6">
                <label for="software_name" class="form-label">Software name *</label>
                <input id="software_name" name="software_name" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label for="software_url" class="form-label">Canonical URL *</label>
                <input id="software_url" name="software_url" type="url" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label for="software_type" class="form-label">Software type</label>
                <select id="software_type" name="software_type" class="form-select">
                    <option value="wp_plugin">WordPress plugin</option>
                    <option value="other" selected>Other software</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="wordpress_version" class="form-label">WordPress version tested</label>
                <input id="wordpress_version" name="wordpress_version" class="form-control" placeholder="e.g. 6.8.1">
            </div>
            <div class="col-md-4">
                <label for="submitter_role" class="form-label">Role</label>
                <select id="submitter_role" name="submitter_role" class="form-select">
                    <option value="">Prefer not to say</option>
                    <option value="developer">Developer</option>
                    <option value="user">User</option>
                </select>
            </div>
            <div class="col-md-6">
                <label for="submitter_name" class="form-label">Your name *</label>
                <input id="submitter_name" name="submitter_name" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label for="submitter_email" class="form-label">Your email (private) *</label>
                <input id="submitter_email" name="submitter_email" type="email" class="form-control" required>
            </div>
            <div class="col-12">
                <label for="software_description" class="form-label">Software description</label>
                <textarea id="software_description" name="software_description" class="form-control" rows="2"></textarea>
            </div>
            <div class="col-12">
                <label for="submission_comment" class="form-label">Submission comment</label>
                <textarea id="submission_comment" name="submission_comment" class="form-control" rows="2"></textarea>
            </div>

            <div class="col-12">
                <h2 class="h6 mt-2">Template results</h2>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                        <tr>
                            <th>Email template</th>
                            <th>Expected</th>
                            <th>Severity bucket</th>
                            <th>Your result</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($templates as $template): ?>
                            <?php $severityLabel = match ((int)$template['severity_weight']) {3 => 'high',2 => 'medium',default => 'low'}; ?>
                            <tr>
                                <td><code><?= htmlspecialchars((string)$template['email_address'], ENT_QUOTES, 'UTF-8') ?></code></td>
                                <td><?= (int)$template['expected_valid'] === 1 ? 'Valid' : 'Invalid' ?></td>
                                <td><span class="badge text-bg-secondary text-uppercase"><?= htmlspecialchars($severityLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td>
                                    <select name="result_<?= (int)$template['id'] ?>" class="form-select form-select-sm result-select">
                                        <option value="not_tested" selected>Not tested</option>
                                        <option value="accepted">Accepted</option>
                                        <option value="rejected">Rejected</option>
                                    </select>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="col-12">
                <button id="submit-button" class="btn btn-primary" type="submit">Publish report</button>
            </div>
        </form>
    </div>
</section>
