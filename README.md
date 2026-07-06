# NRI Forms (pkg_nriforms) — v1.1.0

Custom forms for Joomla 5/6 built on core mechanisms only. No third-party
dependencies. Verified on Joomla 5.x and Joomla 6.1.1 (b/c plugin enabled).

## Architecture

The design piggybacks Joomla's own custom fields system (com_fields):

- **A Field Group IS a form.** Create one per form in Forms > Field Groups.
- **A Field IS an input.** Create fields and assign each to its group.
- The component (`com_nriforms`) registers the `com_nriforms.form` fields
  context, adds submission storage, mail handling, the front-end view, and
  a SEF router. It deliberately owns nothing that com_fields already does.

Three extensions, one package:

| Extension | Purpose |
|---|---|
| `com_nriforms` | Context registration, site form view + submit, Submissions admin, router |
| `plg_fields_nriinputs` | Email and Telephone field types (core EmailRule/TelRule validation, friendly messages, tel pattern) |
| `plg_system_nriforms` | Creates each form's mail template on group save; keeps template placeholder tags in sync on field save |
| `plg_task_nriforms` | Scheduler task deleting submissions whose per-form retention period has passed |

**All three plugins must be enabled after install** (Joomla installs plugins
disabled). For retention to work, also create a Scheduled Task: System >
Scheduled Tasks > New > "NRI Forms - Purge Expired Submissions" (daily),
and ensure the Scheduler's trigger (lazy or web cron) is configured.

## Creating a form (three screens)

1. **Forms > Field Groups** — create the group (= the form). Its mail
   template is created automatically on save.
2. **Forms > Fields** — add inputs, assign each to the group. Conditional
   display: set the field's Showon option (core syntax, e.g. `email!:`
   for "when email has any value"; `type:sample` for equality). The server
   enforces conditions on submit: a condition-unmet field is never required.
3. **Menus** — new item, type *Forms > Display a Form*. Pick the form on
   the Details tab. Email tab: **Recipient(s)** (comma-separated) and
   **Reply-To Field Name** (default `email` — the submitted value of that
   field becomes the email's Reply-To).

## Email configuration doctrine

- **Global Configuration > Server**: transport (SMTP), site-wide From
  identity, Send Mail = Yes (a classic gotcha), HTML mail toggle.
- **System > Mail Templates**: each form has a template
  (`com_nriforms.submission.{groupId}`, listed as "Form: {title}").
  Subject, plain body, and HTML body live here. Per-template Options
  override the From identity for that form only; leave the template's
  Reply-To empty (the dynamic submitter reply-to does that job).
- **Menu item**: only Recipient(s) and Reply-To Field Name. Component
  Options hold a site-wide Default Recipient fallback.

### Template placeholders

`{SITENAME}` `{SITEURL}` `{FORM_TITLE}` `{DATE}` — globals.
`{FIELDS}` — plain-text label/value block. `{FIELDS_HTML}` — HTML table.
`{FIELD_<NAME>}` — one per field (uppercase field name), so templates can
place, reorder, or omit individual values freely. Empty values render as
nothing, never as literal tags.

## Submissions

Stored in `#__nriforms_submissions` BEFORE mail is attempted (mail failure
never loses data; the Mail Sent column flags rows needing attention).
Mail failures are logged to `administrator/logs/com_nriforms.mail.php`
and shown on-screen to logged-in admins. Spam protection: CSRF token +
honeypot (`nri_hp`; bots that fill it get a fake success, no row, no mail).

## Storage, retention & encryption

Per menu item (Storage & Retention tab):
- **Store Submissions**: Use Global / Yes / No. "No" = mail-only; nothing
  is stored on the server — the strongest option for sensitive forms.
- **Retention (days)**: 0 keeps indefinitely; otherwise each submission is
  stamped with an expiry date and deleted by the scheduled purge task.

Component Options: **Encrypt Stored Submissions** encrypts the data column
at rest (libsodium, keyed from the site secret). Caveats: the admin search
filter cannot search inside encrypted rows (filter by form/date instead);
changing the site secret in configuration.php makes previously encrypted
rows unreadable — record this in server-rebuild procedures. Encryption
protects against database exfiltration and stolen backups, not a full
server compromise (the key lives on the same server).

## Styling

The shipped layout is deliberately plain. Override per site at:
`templates/<template>/html/com_nriforms/form/default.php`

## Conventions discovered the hard way (Joomla 6 migration notes)

- `FieldsHelper::prepareForm($context, $form, $data)` takes the CONTEXT
  STRING first; it loads all fields in the context, fieldset-grouped as
  `fields-{groupId}` (we strip foreign groups after load, and clear the
  `disabled` attr prepareForm sets for permission-less guests).
- Fields plugins declare their types via `tmpl/{type}.php` layout files,
  not `params/*.xml`. Multi-type labels: `PLG_FIELDS_{PLUGIN}_{TYPE}_LABEL`.
- `#__mail_templates` has a composite primary key `(template_id, language)`
  — no id column.
- com_mails titles rows from constants derived from the template id; we
  satisfy them via language overrides written with `LanguageHelper`
  (`administrator/language/overrides/*.override.ini`), re-synced on group
  save so renames propagate.
- `getContexts()` must load the component's own language files (core
  com_contact pattern) because com_fields calls it mid-dispatch.

## Deliberately not included (additive later, nothing blocks them)

File uploads; CSV export of submissions; MX-record hardening of email
validation; per-form summary-field selection for the Submissions list;
com_privacy integration (data export/erasure requests); consent-checkbox
guidance pending the institutional DPO conversation. Privacy policy link:
forms should reference the applicable policy (e.g. the University of
Greenwich privacy notice) via a required checkbox field or page text.

## Uninstall behaviour

Component uninstall drops `#__nriforms_submissions`. Field groups/fields
survive (they are com_fields data). Language overrides remain as harmless
orphan strings. Mail template rows for `com_nriforms.*` keys remain.
