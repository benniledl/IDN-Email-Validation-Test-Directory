# Project Re-evaluation (Definition vs Current Implementation)

Date: 2026-03-03

This document re-checks the implementation against `docs/PROJECT_DEFINITION.md` and summarizes what is done, partially done, and still missing.

## Status legend

- ✅ Implemented
- 🟡 Partially implemented
- ❌ Missing

## 1) Core submission flow

- ✅ Two-step style submission exists on a single page (software details + template outcomes + submitter info).
- ✅ WordPress plugin input supports slug or URL and fetches metadata from WordPress.org API.
- ✅ "Other software" mode allows manual name + URL + optional description.
- ✅ Submission requires at least one tested template result.
- ✅ Auto severity is calculated from failing template cases.

## 2) Data model

- ✅ All core tables from the definition exist (`software`, `submissions`, `submission_tests`, `template_emails`, `plugin_comments`, `submission_comments`).
- ✅ Seeded IDN template emails exist with expected validity and severity weights.
- ✅ Hidden flags and admin-override columns exist in schema.

## 3) Public directory & detail pages

- ✅ Public software directory exists.
- ✅ Software detail page exists with reports list and software-level comments.
- ✅ Report detail page exists with per-template outcomes and report-level comments.

## 4) What is still missing or incomplete

### A. Search by software name (required in definition)

- ❌ No name search UI/route/filtering is currently implemented.

### B. Severity display behavior on software pages

- 🟡 Directory currently shows counts and a "problematic report(s)" badge, but not an explicit resolved overall severity label (`none`/`low`/`medium`/`high`) as defined.
- ❌ Software detail header does not display overall severity.

### C. Privacy notice requirements on submit form

- 🟡 Form labels indicate private email, but there is no explicit privacy notice block that clearly states:
  - what is public,
  - what is private,
  - immediate publication,
  - and admin hide capability.

### D. Moderation/admin operations

- ❌ No admin workflow/endpoints/UI currently exist for:
  - hiding submissions,
  - hiding comments,
  - overriding severity,
  - posting official solution comments.
- 🟡 Database fields for those capabilities are present, but application-level controls are not.

### E. WordPress URL acceptance constraints

- 🟡 Slug extraction accepts any URL path containing `/plugins/{slug}` and does not verify wordpress.org host explicitly.
- 🟡 This likely works for practical use but is looser than the strict accepted/rejected URL rules in the definition.

## 5) Conclusion

The project is a strong MVP and covers most of the core user-facing submission + public directory goals. The main remaining work to fully match the definition is:

1. Add software-name search.
2. Show resolved overall severity consistently (directory + software header).
3. Add explicit privacy notice text on submission page.
4. Implement admin moderation controls (or clearly scope them to a next phase).
5. Tighten WordPress URL host validation to wordpress.org domains only.

## 6) Suggested next milestone

"Definition Compliance Pass" (small-medium scope):

- Add search query on `/software`.
- Compute and display resolved overall severity in repository/controller/view.
- Add submit-form privacy notice panel.
- Add minimal admin-only endpoints for hide + override (could be simple shared-secret gate initially).
- Add strict plugin URL host validation for wordpress.org and localized subdomains.
