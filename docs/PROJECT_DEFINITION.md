# IDN Email Validation Test Directory - Project Definition

## Purpose

This platform documents how software validates **IDN domains in email addresses**.
Scope is domain-part IDN support (e.g. `max@müller.de`), not SMTPUTF8 local-part testing.

## Severity model (updated)

Severity is based on the **highest failing test case** in a submission.
A failure means:
- expected valid + actually rejected, or
- expected invalid + actually accepted.

### High
High is triggered if basic/common IDN cases fail, including straightforward domains and long IDN-ready TLD usage.

Examples:
- `max@müller.de`
- `info@büro.at`
- `max@info.versicherung`

### Medium
Medium is triggered if more advanced but still common structures fail, mainly subdomain-based addresses.

Examples:
- `max@newsletter.müller.de`
- `max@news.info.versicherung`

### Low
Low is triggered if only complex/edge IDN scripts fail (for example non-Latin domains such as Chinese examples).

Examples:
- `用户@例子.广告`
- `θσερ@εχαμπλε.ψομ`

## Submission flow (starter)

1. Enter software details (name, URL, type)
2. Test predefined email templates in the target software
3. Record accepted/rejected for each tested template
4. Submit public result and auto-computed severity

## Data model (starter)

- `software` for tested products
- `template_emails` for canonical test list with severity tier
- `submissions` for each report
- `submission_tests` for per-template outcomes and detected failures

The schema is designed so moderation/comments can be added without breaking existing relationships.
