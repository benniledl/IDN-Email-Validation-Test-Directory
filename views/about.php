<section class="grid gap-4 lg:grid-cols-[1.4fr_1fr]" aria-labelledby="about-title">
    <article class="card border border-base-300 bg-base-100 shadow-sm">
        <div class="card-body gap-3 md:gap-4">
            <p class="badge badge-outline">About the project</p>
            <h1 id="about-title" class="text-3xl font-semibold tracking-tight md:text-4xl">Fixing IDN Email Validation in WordPress Plugins</h1>
            <p class="max-w-3xl text-base-content/75">We are collectively testing and documenting WordPress plugins and related software that reject valid internationalized email domains (IDN), such as <code>max@müller.de</code>. The goal is clear: create reproducible findings that plugin and core contributors can fix.</p>
            <div class="flex flex-wrap gap-2">
                <a href="/submit-report" class="btn btn-primary">Submit a test report</a>
                <a href="/software" class="btn btn-outline">Browse tested software</a>
            </div>
        </div>
    </article>

    <article class="card border border-info/30 bg-gradient-to-br from-info/10 via-base-100 to-base-100 shadow-sm">
        <div class="card-body gap-3">
            <p class="badge badge-info badge-outline">What happens next?</p>
            <h2 class="text-lg font-semibold">What happens with your report?</h2>
            <div class="rounded-box border border-base-300/80 bg-base-100 p-3">
                <ul class="list-disc space-y-1 pl-5 text-sm text-base-content/75">
                    <li>It becomes publicly visible with reproducible test outcomes.</li>
                    <li>Severity is calculated automatically from failed templates.</li>
                    <li>Maintainers and contributors can discuss and triage fixes.</li>
                </ul>
            </div>
        </div>
    </article>
</section>

<section class="grid gap-4 lg:grid-cols-3" aria-label="Project overview">
    <article class="card border border-base-300 bg-base-100 shadow-sm">
        <div class="card-body gap-2">
            <h2 class="card-title text-lg">What we are testing</h2>
            <p class="text-sm text-base-content/75">Domain validation for internationalized email addresses in real plugin and software forms.</p>
            <div class="divider my-1"></div>
            <ul class="list-disc space-y-1 pl-5 text-sm text-base-content/75">
                <li>Valid IDN domains should be accepted.</li>
                <li>Invalid malformed domains should be rejected.</li>
                <li>Failures are recorded per tested template.</li>
            </ul>
        </div>
    </article>

    <article class="card border border-base-300 bg-base-100 shadow-sm">
        <div class="card-body gap-2">
            <h2 class="card-title text-lg">What this is not</h2>
            <p class="text-sm text-base-content/75">This project is focused and intentionally narrow to keep reports actionable.</p>
            <div class="divider my-1"></div>
            <ul class="list-disc space-y-1 pl-5 text-sm text-base-content/75">
                <li>No SMTP delivery testing.</li>
                <li>No environment matrix benchmarking.</li>
                <li>No broad anti-spam workflow.</li>
            </ul>
        </div>
    </article>

    <article class="card border border-base-300 bg-base-100 shadow-sm">
        <div class="card-body gap-2">
            <h2 class="card-title text-lg">Why this matters</h2>
            <p class="text-sm text-base-content/75">Incorrect validation blocks legitimate users and breaks global email workflows.</p>
            <div class="divider my-1"></div>
            <ul class="list-disc space-y-1 pl-5 text-sm text-base-content/75">
                <li>Real users are rejected from forms.</li>
                <li>Plugins diverge in behavior and quality.</li>
                <li>Shared evidence speeds up real fixes.</li>
            </ul>
        </div>
    </article>
</section>

<section class="grid gap-4 lg:grid-cols-2" aria-label="Severity and privacy">
    <article class="card border border-base-300 bg-base-100 shadow-sm">
        <div class="card-body gap-3">
            <h2 class="text-xl font-semibold">Severity model</h2>
            <p class="text-sm text-base-content/75">Each report is scored by the highest-impact failed template. This helps maintainers prioritize fixes.</p>
            <div class="grid gap-2 sm:grid-cols-2">
                <div class="rounded-box border border-base-300 bg-base-100 p-3">
                    <span class="badge badge-error badge-outline mb-1">High</span>
                    <p class="text-xs text-base-content/70">Common/basic IDN cases fail.</p>
                </div>
                <div class="rounded-box border border-base-300 bg-base-100 p-3">
                    <span class="badge badge-warning badge-outline mb-1">Medium</span>
                    <p class="text-xs text-base-content/70">Subdomain IDN cases fail.</p>
                </div>
                <div class="rounded-box border border-base-300 bg-base-100 p-3">
                    <span class="badge badge-info badge-outline mb-1">Low</span>
                    <p class="text-xs text-base-content/70">Edge/complex IDN cases fail.</p>
                </div>
                <div class="rounded-box border border-base-300 bg-base-100 p-3">
                    <span class="badge badge-success badge-outline mb-1">None</span>
                    <p class="text-xs text-base-content/70">No failures detected in tested templates.</p>
                </div>
            </div>
        </div>
    </article>

    <article class="card border border-base-300 bg-base-100 shadow-sm">
        <div class="card-body gap-3">
            <h2 class="text-xl font-semibold">Data and privacy</h2>
            <p class="text-sm text-base-content/75">Reports are public for transparency and collaboration, with limited private data.</p>
            <ul class="list-disc space-y-1 pl-5 text-sm text-base-content/75">
                <li><strong>Public:</strong> submitter name, role, test outcomes, severity, comments.</li>
                <li><strong>Private:</strong> submitter email (visible only to admins).</li>
                <li>Administrators can moderate hidden/spammy content.</li>
            </ul>
        </div>
    </article>
</section>

<section class="card border border-base-300 bg-base-100 shadow-sm" aria-labelledby="community-title">
    <div class="card-body gap-4">
        <h2 id="community-title" class="text-2xl font-semibold tracking-tight">Community & WordCamp sessions</h2>
        <p class="text-base-content/75">This project aligns with the WordCamp Vienna 2026 contributor challenge and follow-up publishing session.</p>
        <div class="grid gap-3 md:grid-cols-2">
            <a class="rounded-box border border-base-300 bg-base-100 p-4 transition hover:border-base-400 hover:bg-base-100" href="https://vienna.wordcamp.org/2026/session/contributor-challenge-fixing-idn-email-validation-in-wordpress/" target="_blank" rel="noopener">
                <h3 class="mb-1 font-semibold">Contributor challenge: Fixing IDN & Email Validation in WordPress</h3>
                <p class="text-sm text-base-content/70">Challenge kickoff: why IDN validation breaks, where bugs are, and how contributors can test and document findings.</p>
            </a>
            <a class="rounded-box border border-base-300 bg-base-100 p-4 transition hover:border-base-400 hover:bg-base-100" href="https://vienna.wordcamp.org/2026/session/we-broke-it-now-lets-fix-it-publishing-the-idn-findings/" target="_blank" rel="noopener">
                <h3 class="mb-1 font-semibold">Solutions session: Publishing the IDN findings</h3>
                <p class="text-sm text-base-content/70">Follow-up session focused on patterns, actionable fixes, best practices, and a roadmap for core and plugin improvements.</p>
            </a>
        </div>
    </div>
</section>

<section class="card border border-base-300 bg-base-100 shadow-sm" aria-labelledby="faq-title">
    <div class="card-body gap-3">
        <h2 id="faq-title" class="text-2xl font-semibold tracking-tight">FAQ</h2>
        <div class="space-y-2">
            <details class="collapse collapse-arrow rounded-box border border-base-300 bg-base-100">
                <summary class="collapse-title py-3 font-medium">Do I need an account to submit reports?</summary>
                <div class="collapse-content text-sm text-base-content/75">No. Anyone can submit. The workflow is intentionally lightweight to increase repeat contribution.</div>
            </details>
            <details class="collapse collapse-arrow rounded-box border border-base-300 bg-base-100">
                <summary class="collapse-title py-3 font-medium">Can plugin maintainers use these reports for fixes?</summary>
                <div class="collapse-content text-sm text-base-content/75">Yes. Reports are structured to be reproducible and are designed to support issue tickets, pull requests, and patch discussions.</div>
            </details>
            <details class="collapse collapse-arrow rounded-box border border-base-300 bg-base-100">
                <summary class="collapse-title py-3 font-medium">What should I do before submitting?</summary>
                <div class="collapse-content text-sm text-base-content/75">Test at least one predefined email template in your target software, then submit accepted/rejected outcomes exactly as observed.</div>
            </details>
        </div>
    </div>
</section>
