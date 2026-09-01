# Delnavazan Enhancements operations

Release: 2.0.17  
Database schema: 9  
Current status: live production release; notification sends remain administrator-controlled and no release test send runs automatically.

This file is the machine-readable operational source bundled with the plugin. It must never contain passwords, access tokens, OAuth client secrets, webhook secrets, personal test numbers, or other credentials.

## System ownership

- The booking plugin is authoritative for appointments, approval status, teachers, students, services, scheduled times and Google Meet URLs.
- Delnavazan Attendance owns signed join and absence links, next-day WhatsApp tracking, send-once term-renewal reminders, absence evidence, Google Meet reconciliation, archive state and teacher payment reporting.
- Google Meet API provides participant and session intervals.
- Meta owns approved WhatsApp template bodies, message acceptance and delivery/read/failure status events.
- Delnavazan records Meta message IDs and updates only locally known messages when protected webhook events arrive.
- The attendance plugin does not automatically cancel, reschedule or approve a replacement class.

## WhatsApp booking confirmation and delivery status

- Approved introductory bookings use the configured `student_intro_class_confirmed` template.
- Automatic sends listen to Amelia's documented booking-added and booking-status-updated actions. Both the booking and its appointment must be Approved.
- The sender requires a student name, valid WhatsApp number, service, localized class date/time, assigned teacher and valid Google Meet URL before calling Meta.
- The production send marker is written only after Meta accepts the message. **Meta accepted — delivery receipt pending** is not presented as delivery. Matching webhook events supply Sent, Delivered, Read or Failed.
- Tracked class-link activity and administrator-recorded customer confirmation are shown as separate evidence sources with separate timestamps.
- Reschedules preserve the signed public ID and refresh expiry so already-delivered buttons remain valid; cancellation, archive state and expiry still revoke access.
- The sender validates both signed action buttons before transmission, and the admin preview shows student-local, teacher-local and Brisbane site times.
- Administrators can use **Send confirmation now** for eligible approved introductory bookings and **Send reminder now** for approved next-day bookings. Both controls show the complete Amelia phone number, require a visible **Confirm production send** checkbox and block duplicate production sends. Manual reminders use an atomic lock only during the Meta request; unattended scheduled retries retain the 10-minute guard.
- Meta's existing Amelia callback remains authoritative and its `messages` field stays subscribed. Delnavazan observes status POST events at priority 1 before Amelia continues processing, so adding delivery tracking does not replace or interrupt Amelia's webhook.
- Attendance workflow labels are deliberately different from Amelia booking labels: `pending` displays as **Awaiting attendance check**, `join_clicked` as **Join link opened**, `absence_reported` as **Absence reported**, `attended` as **Attendance confirmed**, `review_needed` as **Attendance review required**, `no_show` as **No attendance detected**, and `cancelled` as **Amelia booking cancelled**.

## Unified plugin ownership

- Delnavazan Enhancements owns the former Site Enhancements modules, Attendance, Meta Pixel snippet 11, password-reset email snippet 17 and post-booking password helper snippet 18.
- The legacy plugins and Code Snippets were removed after explicit owner approval and successful consolidation checks. Local packages and snippet snapshots remain the rollback artifacts.
- Existing `dln_attendance_*` database, option, cron, shortcode, page and public-route identifiers are preserved.
- The Site presentation rules are intentionally preserved for the first unified release; selector cleanup requires separate page-by-page visual regression work.
- The full migration, acceptance and rollback workflow is bundled in `INSTALLATION.md` and `docs/WORKFLOWS.md`.
- WordPress administration is grouped under the top-level **Delnavazan** menu. Existing `dln-attendance*` page slugs remain valid beneath it.

## Unified migration workflow

1. Take a database and files backup.
2. Upload the unified ZIP but leave it inactive.
3. Deactivate the legacy Attendance and Site Enhancements plugins.
4. Deactivate snippets 11, 17 and 18.
5. Activate Delnavazan Enhancements and clear cache once.
6. Test homepage, articles, enrolment, payment, student and teacher panels on desktop and mobile.
7. Complete a fresh-email booking and prove the password dialog appears only for a real password form.
8. Confirm one Meta Pixel PageView, existing Attendance records/settings, one controlled WhatsApp test booking, Google authorization visibility, payment sync and recurring cron events.
9. Keep all legacy components installed but inactive until the owner explicitly approves deletion.

Rollback: deactivate Delnavazan Enhancements, reactivate the two legacy plugins and snippets 11, 17 and 18, clear cache, and retest the affected path. Never delete the Attendance table or options during rollback.

## Completion rule

- The assigned teacher and booked student must both be identified in the same Google Meet conference.
- Their participant intervals must overlap for at least the configured minimum, currently 20 minutes.
- A link click is tracking evidence only and never completes a class.
- Missing names, missing participant data or shorter overlap produce `review_needed`.
- A documented administrator correction may set the final attendance status.

## Archive lifecycle

- Normal log query: `archived_at IS NULL`.
- Archived view query: `archived_at IS NOT NULL`.
- Archive is reversible and records `archived_at` plus `archived_by`.
- Restore clears both archive fields and returns the record to the normal log.
- A restored record can be archived again.
- Daily cleanup permanently deletes only rows whose `archived_at` is older than the configured retention period, default 180 days.
- Active historical attendance and payment evidence is never purged by this archive cleanup.

## Teacher-panel authorization security

- The shortcode outputs an empty hidden slot before a teacher signs in.
- Browser state alone is not accepted as authority.
- For Amelia-only login, the browser sends the active cabinet token to the site endpoint over same-origin HTTPS.
- The server calls the booking system current-user endpoint and verifies the returned ID and email against an employee/provider database row.
- For a WordPress-linked teacher login, the server accepts the signed-in WordPress user only when its email exactly matches an Amelia provider row.
- Only a verified teacher receives a five-minute, one-time OAuth grant.
- The OAuth start route rejects missing, expired and reused grants.
- The Google email returned by OAuth must match the exact verified teacher identity that started the flow.
- Connected display state comes from the server-side renewable Google account record, not local storage.
- Logged-out, invalid or unverified sessions receive no card and no usable OAuth start URL.

## Teacher-panel presentation

- Show the attendance connection card only to a verified, unconnected teacher.
- Hide it after a durable connection succeeds.
- Re-display it when the server marks the account disconnected or refresh authority invalid.
- Use the booking panel typography, blue primary action, spacing, border radius and responsive mobile width.
- Never modify booking-plugin core files for this presentation.

## Student next-day reminder

- Technical template name is configured separately in Attendance Settings.
- Student time uses the valid customer timezone when available; otherwise it uses and labels the site timezone.
- Both the tracked join button and the copyable fallback link use the signed attendance URL.
- Duplicate production reminders are prevented by `whatsapp_sent_at` and the recent-attempt lock.

## Teacher absence alert

Technical template: `teacher_student_absence_alert`  
Language: must exactly match the approved Meta template.  
Trigger: only after a student confirms absence through the signed site page.  
Duplicate protection: `teacher_whatsapp_sent_at`.

Body variables in exact order:

1. Teacher name
2. Student name
3. Class or service name
4. Original date
5. Original time and timezone
6. Class code

Intended Persian copy:

درود {{1}} عزیز

هنرجوی شما، {{2}}، اعلام کرده است که نمی‌تواند در کلاس «{{3}}» در تاریخ {{4}}، ساعت {{5}} حضور داشته باشد.

کد کلاس: {{6}}

هنرجو برای هماهنگی زمان جایگزین، براساس زمان‌های در دسترس شما، با شما تماس خواهد گرفت. پس از توافق درباره زمان جدید، لطفاً وارد حساب کاربری خود در سایت دلنوازان شوید و تاریخ و ساعت کلاس را تغییر دهید. پس از تأیید شما، پیام تأیید زمان جدید برای هنرجو ارسال خواهد شد.

The approved body is controlled by Meta. A wording change requires a Meta edit or a new versioned template and approval. The plugin then selects the exact approved technical name and language.

## Student term-renewal reminder

Technical template: `student_term_renewal_reminder`  
Language: `fa`  
Trigger: exactly two future approved non-introductory appointments remain for the same student, service and teacher, with at least ten earlier approved non-introductory appointments.  
Duplicate protection: a local send-history marker based on student, service, teacher and completed 12-session blocks.  
Retry guard: one hour after an unsuccessful attempt.

Body variables in exact order:

1. Student full name
2. Class or service name

The Meta `ورود به درگاه هنرجو` button is static at `https://delnavazan.com/honarjo/`; no button component is sent through the API. Category ID 3 introductory sessions are excluded before term counts are calculated. The feature is opt-in and shares the existing Amelia cron trigger, Meta Phone Number ID, access token, Graph version and default-country setting.

## Payment and audit rules

- Attendance status, payable status and archive state are separate fields.
- Amelia service category ID 3 (`جلسه معارفه`) is the authoritative introductory category.
- Every category 3 booking is automatically labelled introductory, forced to `payable = 0`, and given the payment note `Introductory class — non-payable`.
- When Amelia moves a booking out of category 3, sync reverses only that exact plugin-generated note and restores payable status; unrelated manual payment decisions remain intact.
- Teacher Payment Report provides a nonce-protected, exact-date-range **Sync Amelia changes** action that preserves the selected teacher and rate.
- Introductory sessions remain visible in attendance logs and teacher statements but contribute zero to payment totals.
- Archived rows are excluded from payment totals and statements.
- Payment amounts are displayed as whole Iranian Toman with thousands separators; attendance records are not financially converted in storage.
- Teacher totals count a source appointment once, not once per duplicate customer row.

## Release 1.3.4 verification

- The student term-renewal preview now renders `ترم فعلی «{{2}}»` because Amelia service names may already include `کلاس`.
- The Employee Panel Google card retries transient login/session failures every two seconds and re-checks on focus, page show, storage changes and restored visibility. It remains hidden until the server verifies an Amelia provider or matching WordPress teacher.
- The WhatsApp template name, language, variables, eligibility rules, send history and database schema are unchanged from 1.3.3.

## Release 2.0.16 verification

- A full UpdraftPlus files-and-database backup completed to Google Drive on 31 August 2026 at 22:08 before the live edit.
- Schema 8 adds confirmation and delivery-status columns only; it preserves existing records and workflow settings.
- The Overview is reduced to operational modules, documentation and version/schema information.
- The Settings screen presents Booking approval confirmation, Next-day reminder, Teacher absence alert and Term-renewal reminder as equal-height responsive cards with a consistent field order and button hierarchy.
- Obsolete experimental Amelia/Google controls are hidden, while their stored values are preserved by the settings sanitizer.
- Disabled workflows remain disabled unless an administrator explicitly enables and saves them.
- Live acceptance passed: Overview reported release 2.0.16/schema 8; Settings rendered four equal responsive cards; Meta's Delivered sample reached the shared callback; temporary test booking #281 was accepted and then recorded as Read before the test booking was removed.
- Actual booking #262's production next-day reminder was accepted by Meta at 23:36 on 31 August 2026. Attendance remains the source for its latest Sent/Delivered/Read/Failed state.

## Unified release 2.0.2 verification

- Controlled booking and renewal tests show only the eligible booking with the highest recent Amelia booking ID.
- Attendance options, table, schema and public routes remain unchanged.
- Recurring Attendance jobs are restored after the legacy Attendance plugin is deactivated.
- Active legacy plugins are detected from the WordPress registry before module loading, preventing load-order function or class redeclarations.
- The active Amelia Employee Panel identity takes precedence over a simultaneous WordPress administrator session when deciding whether to show Google attendance authorization.
- Reusable external secrets are migrated to authenticated encrypted option values derived from WordPress secure-auth salts.
- The migrated Meta Pixel suppresses itself when an earlier `fbq` exists.
- The post-booking window stays hidden unless a same-origin password field is present.
- PHP, embedded JavaScript, ZIP structure, deterministic hash and live acceptance must all be checked before deleting legacy components.

## Release 1.3.3 verification

- PHP syntax check every plugin PHP file.
- Static security check for nonce/capability guards on administrator archive and restore actions.
- Static security check that OAuth start requires a one-time verified-teacher grant.
- Database query check that normal and archived views are mutually exclusive.
- Database query check that cleanup targets only old archived rows.
- Database/query checks that category ID 3 is persisted, backfilled and cannot be made payable.
- Statement checks that introductory sessions remain visible with zero payment while payable totals remain unchanged.
- Term-renewal checks for the ten-past/two-future threshold, introductory-category exclusion, two-value Meta contract, static-button omission, one-hour retry guard and send-once history.
- Controlled renewal-test checks for administrator capability, nonce, mandatory recipient override, introductory-category rejection and no production-history mutation.
- Render and visually inspect the bundled master DOCX.
- Build a deterministic ZIP and inspect its file list and hashes.
- Live acceptance: logged-out teacher page, authenticated unconnected teacher, OAuth completion, reload persistence and logout.
- Live acceptance: controlled teacher absence template after Meta approval.

## Rollback

- Back up the live plugin directory and database before installation.
- Install the unified plugin inactive; do not overwrite or delete the legacy plugins.
- Switch by deactivating the two legacy plugins and snippets 11, 17 and 18, then activate Delnavazan Enhancements.
- If a blocking problem occurs, deactivate the unified plugin and reactivate the legacy plugins and snippets while leaving the database intact.
- Release 2.0.16 uses Attendance schema 8, preserves existing settings/history and only adds local WhatsApp confirmation/delivery fields; it does not modify Amelia tables.
- Release 1.3.3 historically used schema 7.
- Never restore by deleting the attendance table or Google account options.

## Troubleshooting index

- Card visible before login: confirm release 1.3.3 assets are active and page/cache is cleared.
- Card absent after teacher login: inspect the administrator-only Employee Panel Google diagnostic for `invalid_session`, `invalid_wordpress_teacher`, `invalid_teacher_session`, `already_connected`, or `grant_ready`; never log or expose the cabinet token.
- Amelia 9.8.1 may authenticate an employee panel without persisting a reusable cabinet token. Version 2.0.4 listens to Amelia's `window.ameliaBooking.cabinet.userLoggedIn` success callback, rechecks its identity against the Amelia provider table, rate-limits anonymous grant creation, issues only a five-minute one-time grant, and requires the Google OAuth email to match the exact provider email before saving a connection.
- Version 2.0.5 installs that supported Amelia callback in `wp_head` on the shortcode page, before the employee panel boots. This prevents Amelia from completing authentication before the attendance shortcode has registered its listener.
- Version 2.0.6 keeps an already verified connection card visible during temporary network or rate-limit responses, but clears it on a genuine 401/403 validation failure or Amelia `userLoggedOut` callback.
- Version 2.0.7 is a documentation-only roll-up of the production-verified 2.0.6 functional baseline.
- Version 2.0.8 removes the brittle Amelia confirmation-button dependency, accepts only a same-origin token-bearing account URL, and retries `changePass=1` only after detecting an email-confirmation response. The dialog still remains hidden until a real password field exists.
- Version 2.0.8 groups Overview, Attendance log, Teacher payments, Documentation and Settings under a top-level Delnavazan menu while preserving all existing Attendance URLs.
- Version 2.0.9 is a focused booking hotfix. It removes the password helper's `Schedule` and `Purchase` registrations because Amelia uses those actions as continuation gates. The earlier handlers did not call Amelia's supplied success callback, which caused free on-site bookings to report a false payment-processing error. Secure account URL capture remains response-observation only.
- Version 2.0.10 keeps that passive booking path and strengthens password-link discovery: the observer installs in the page head, accepts nested and encoded Amelia response data, falls back to Amelia's booked Vue state, and opens only same-origin token-bearing customer URLs in `changePass=1` mode.
- Version 2.0.11 searches the Vue components attached to Amelia's visible confirmation controls and their parent state when the global booked store is not exposed on the container. The fallback stops after fifteen seconds and remains post-booking and read-only.
- Version 2.0.12 abandons the custom popup after repeated live capture failures. It waits for a confirmed Appointment ID and Amelia's rendered password control, presents a Persian transition notice, and invokes that native action without reading a token or private Vue state.
- Version 2.0.13 fixes the scope of that native handoff: Amelia renders the confirmation outside the original booking container, so the helper observes the document body while retaining the strict Appointment ID plus password-control checks.
- Version 2.0.15 moves the password handoff into a responsive modal overlay, removes the timer and lets the learner explicitly choose password setup or Amelia booking details. It retains the exact false payment-sentence replacement and learner-login path. Amelia identity matching stays enabled so repeat bookings attach to the correct customer rather than creating duplicates.
- Stale statement after editing Amelia: select the affected dates in Teacher Payment Report and click **Sync Amelia changes**.
- OAuth start forbidden: request a fresh grant by reloading the signed-in teacher page; grants expire in five minutes and are one-use.
- Card returns after connection: inspect refresh token presence and `connection_error` in the server-side Google account record.
- Archived record still in normal log: confirm the page query uses `archive=active` and no stale object/page cache is serving the old plugin.
- Missing teacher WhatsApp: confirm feature enabled, approved template name/language, teacher phone, six non-empty parameters and row error diagnostic.
- Missing term reminder: confirm the feature is enabled, template/language are exact, the student has a valid phone, category 3 is excluded, and the same student/service/teacher group has at least ten past plus exactly two future approved appointments.
- Missing approval confirmation: confirm the introductory booking and appointment are both Approved, the student phone and Google Meet URL are valid, the approval workflow is enabled, and inspect the per-booking attempt/error plus Meta message status.
- Delivery remains Accepted: confirm Meta's `messages` field is still subscribed on the existing Amelia callback, then inspect the Settings webhook timestamp and the saved Meta message ID.
- Incorrect teacher total: confirm status attended, payable enabled, archived empty and unique source appointment mapping.

## Final production consolidation — 31 August 2026

- Live Google acceptance passed with a disconnected Amelia teacher: connect, logout/login, disconnect and reconnect all worked; the card remained stable.
- Deleted after acceptance: Attendance Tracker 1.3.3, Site Enhancements 1.0.0 and inactive Enhancements 2.0.1.
- Code Snippets 3.10.1 was removed after all four remaining snippets were confirmed inactive. Complete Uninstall remained disabled, preserving the historical snippets database for rollback inspection.
- WordPress then showed 14 installed plugins, all active. Delnavazan Enhancements was the sole Delnavazan custom plugin.
- Local legacy packages and snippet snapshots are rollback evidence. Never activate them alongside the unified plugin.
- Remaining acceptance gap for 2.0.8: install the package, confirm the Delnavazan administrator menu, then perform one genuine new-student booking with a fresh email to verify the password-setup window end to end.
- Remaining QA: verify one Meta Pixel PageView after snippet removal, run template-specific WhatsApp delivery tests before enabling still-disabled automations, and resolve the duplicated English Google disclosure only after identifying both owners.
- Security evidence is limited to structural parsing and named invariants; a full independent metered repository scan was not completed after the final source changed.

