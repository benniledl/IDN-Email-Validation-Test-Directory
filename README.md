# IDN Email Validation Test Directory

## 1. Project Purpose

This platform collects and publicly documents **test results of IDN (Internationalized Domain Name) email validation behavior** in software.

The focus is strictly on:

> **IDN domain validation** (e.g. `max@müller.de`)
> NOT testing SMTPUTF8 / UTF-8 local-part addresses.

The platform allows users to:

* Test predefined IDN email addresses
* Submit test results without login
* Associate results with WordPress plugins or other software
* Publicly document validation failures
* Automatically determine bug severity
* Enable discussion at report and software level

Submissions are instantly public but can be hidden by an admin.

---

# 2. Scope Definition

## Included

* IDN domain validation testing only
* WordPress plugins (primary focus)
* Other arbitrary software (secondary support)
* Public test result directory
* Auto severity classification
* Plugin-level and report-level comments
* WordPress.org metadata fetching (name, icon, banner)

## Explicitly Excluded

* SMTP delivery testing
* SMTPUTF8 / non-ASCII local-part testing
* Environment tracking (PHP, hosting stack, mail server)
* Login system
* Duplicate detection
* Confidence scoring
* Data export
* Categories
* Advanced search/filtering
* Spam protection

---

# 3. High-Level System Overview

## Two-Step Submission Flow

### Step 1 – Select Software

User chooses:

#### Option A: WordPress Plugin

Input field accepts:

* Full WordPress plugin URL

  * `https://wordpress.org/plugins/contact-form-7/`
  * `https://de.wordpress.org/plugins/contact-form-7/`
* Plugin slug only

  * `contact-form-7`

Not accepted:

* Download URLs
* ZIP URLs

System behavior:

* Normalize to canonical slug
* Validate slug against WordPress.org API
* Fetch plugin metadata immediately
* Cache metadata
* Store canonical URL

User must provide:

* WordPress version used for testing

#### Option B: Other Software

User must provide:

* Software name (required)
* Software URL (required)
* Short description (optional)

---

### Step 2 – Submit Test Results

User sees predefined email template list.

Each template email includes:

* Email address
* Expected validity (valid or invalid)
* Copy button

User tests addresses manually in the software.

For each address they tested:

* Select outcome:

  * Accepted
  * Rejected

Submission includes:

* Batch of multiple tested addresses
* Submission comment (optional)
* Submitter name (required)
* Submitter email (required, NOT public)
* Role (optional: Developer / User)

Submission becomes immediately public.

Admin may later:

* Hide submission
* Adjust severity
* Comment

---

# 4. Email Template Definition

Only IDN domain testing.

Examples (final list to be defined later):

Valid (must be accepted):

* `max@müller.de`
* `info@büro.at`
* `kontakt@straße.de`

Invalid (must be rejected):

* `max@-müller.de`
* `max@müller..de`
* `max@müller`

Each template entry contains:

* ID
* Email address
* Expected validity (boolean)
* Severity weight (for auto scoring)

No SMTPUTF8 examples (no UTF-8 local-part).

---

# 5. Severity System (Auto Classification)

Severity is calculated per submission.

A submission contains multiple test results.
Severity = highest severity triggered by any failing case.

## Severity Levels

### CRITICAL

Triggered if:

* A simple valid IDN domain fails
  e.g. `max@müller.de` rejected

Impact:

* Software does not support basic IDN domains
* Severe standards violation

---

### HIGH

Triggered if:

* A standard IDN variant fails but simple case works
* Example:

  * `info@büro.at` rejected
  * but `max@müller.de` accepted

Impact:

* Partial IDN support
* Significant inconsistency

---

### MEDIUM

Triggered if:

* Edge IDN formatting issues fail
* Less common but still valid formats rejected

---

### LOW

Triggered if:

* Only invalid addresses are incorrectly accepted
* Validation too permissive but IDN works

---

### NONE

All expected valid emails accepted
All expected invalid emails rejected

---

## Severity Calculation Logic

For each tested address:

If:

* Expected valid AND Rejected → validation failure
* Expected invalid AND Accepted → validation failure

Each template email has a predefined severity weight.

Submission severity = highest severity weight among all failures.

Admin can override severity manually.

---

# 6. Data Model Specification

## Table: software

Fields:

* id (PK)
* type (`wp_plugin` | `other`)
* slug (nullable, for WP plugins)
* canonical_url
* name
* description (nullable)
* wp_version_tested (nullable, only per submission actually)
* plugin_icon_url (nullable)
* plugin_banner_url (nullable)
* created_at
* updated_at

For WP plugins:

* Metadata fetched and cached on creation

---

## Table: submissions

Fields:

* id (PK)
* software_id (FK)
* wordpress_version (nullable, required for WP plugin)
* submitter_name
* submitter_email (private)
* submitter_role (`developer` | `user` | null)
* submission_comment (nullable)
* severity_auto
* severity_admin_override (nullable)
* is_hidden (boolean)
* created_at

Publicly visible:

* name
* role
* comment
* severity (resolved value)
* test results

Not public:

* email

---

## Table: submission_tests

Each row = one tested email address

Fields:

* id (PK)
* submission_id (FK)
* template_email_id (FK)
* email_address
* expected_valid (boolean)
* actual_result (`accepted` | `rejected`)
* failure_detected (boolean)
* severity_weight
* created_at

---

## Table: template_emails

Fields:

* id
* email_address
* expected_valid (boolean)
* severity_weight
* created_at

---

## Table: plugin_comments (software-level thread)

Fields:

* id
* software_id
* author_name
* author_role (`admin` | `user`)
* comment
* is_admin_solution (boolean)
* is_hidden
* created_at

---

## Table: submission_comments (per report)

Fields:

* id
* submission_id
* author_name
* author_role
* comment
* is_hidden
* created_at

---

# 7. Public Directory Behavior

## Directory Page

Similar to WordPress plugin directory:

Each card shows:

* Plugin/software name
* Plugin icon
* Banner (if WP)
* Link to canonical WP page (if WP)
* Number of submissions
* Overall status:

  * No issues
  * Low
  * Medium
  * High
  * Critical

Overall status = highest active severity across visible submissions.

Search:

* Only by software name

No categories.

---

## Software Detail Page

Shows:

Header:

* Name
* Icon
* Banner
* Canonical link
* Overall severity

Below:

* Plugin-level discussion thread
* List of submissions (latest first)

Each submission shows:

* Submitter name + role
* WP version tested (if WP)
* Severity
* Submission comment
* Detailed table of tested emails + result
* Report-level comments

---

# 8. WordPress Plugin Handling

Accepted inputs:

* Slug
* wordpress.org URL
* localized wordpress.org URL

Rejected:

* download URLs

Normalization process:

1. Extract slug
2. Validate slug via WordPress.org API
3. Fetch:

   * Name
   * Icon
   * Banner
   * Description
4. Cache metadata
5. Store canonical URL:
   `https://wordpress.org/plugins/{slug}/`

If validation fails:

* Show error, prevent submission

---

# 9. Privacy Rules

Public:

* Submitter name
* Role
* Comment
* Test results

Private (admin only):

* Submitter email

Submission form must clearly state:

* What is public
* What is private
* That submissions are public immediately
* Admin can hide submissions

Minimal privacy notice required.

---

# 10. Moderation Rules

Admin can:

* Hide submissions
* Hide comments
* Override severity
* Add official solution comment at plugin level

No approval workflow.

No spam filtering.

---

# 11. Non-Functional Requirements

* Simple UX
* Minimal friction
* Immediate publication
* Clear severity visualization
* WordPress.org visual resemblance for WP plugins
* Cached plugin metadata (avoid API overuse)
* Clean URL structure

---

# 12. Explicit Constraints

* IDN domain testing only
* No SMTPUTF8 testing
* No environment details except WP version
* No login system
* No duplicate prevention
* No scoring confidence system
* No exports
* Search only by name

---

# 13. Future Extensibility (Optional but Not Required)

Architecture should allow:

* Adding SMTPUTF8 testing later
* Adding version filtering
* Adding duplicate detection
* Adding export

But not required now.
