# Operational workflows

## Attendance and Amelia synchronization

- Amelia remains authoritative for appointments, status, service category, teacher, student and scheduled time.
- The Attendance log sync covers its configured window.
- Teacher Payment Report has an exact-date-range **Sync Amelia changes** action. Use it after changing an older appointment, service or category in Amelia.
- Category ID 3 is introductory and non-payable. Sync can reverse only the plugin-generated introductory payment note when Amelia moves a booking out of that category.

## WhatsApp production evidence and class buttons

- **Meta accepted — delivery receipt pending** means Meta returned a message ID but Delnavazan has not matched a delivery event for it.
- Matching Meta webhook events remain authoritative for Delivered, Read and Failed.
- Tracked class-link use independently proves receipt; administrator-recorded customer confirmation is separately timestamped and never presented as a webhook result.
- Reschedules update the class time and expiry without rotating the signed public ID, so already-sent buttons remain valid.
- Both signed action buttons and the Google Meet destination are validated before any new reminder is sent.
- Production previews show student-local, teacher-local and Brisbane site times together.

## WhatsApp controlled tests

- The settings page shows only one booking: the eligible booking with the highest Amelia customer-booking ID in the recent/upcoming window.
- The renewal test similarly shows the highest-ID non-introductory booking.
- Tests require `manage_options`, a WordPress nonce and a recipient entered by the administrator.
- A test never defaults to the student number and does not mark production history as sent.

## Teacher Google authorization

- The Employee Panel shortcode is `[dln_attendance_google_connect]`.
- The card stays hidden until the server verifies an Amelia teacher session or a WordPress user whose email exactly matches an Amelia provider.
- The start grant expires after five minutes and is one-use.
- The Google identity must match the verified teacher email.
- When WordPress administration and Amelia use different simultaneous identities, the active Amelia teacher session takes precedence on the Employee Panel.
- After a durable connection, the card is hidden. Focus, page-show, storage and visibility changes trigger a status retry.

## New-student password setup

- Amelia must create the customer user and require a password for login.
- After a successful booking response, the helper captures only a same-origin `customerCabinetUrl` containing Amelia's short-lived account token. The URL itself is treated as booking-success evidence, avoiding dependency on a specific Amelia confirmation-button selector.
- The iframe is created hidden. The dialog is revealed only if the loaded same-origin document contains `input[type="password"]`.
- If Amelia first returns an email-confirmation response, it remains hidden and the helper retries once with `changePass=1`. Other non-password responses are removed after the preflight timeout.
- The one-time retry is not applied to a normal existing-student panel page, so normal bookings do not force existing students to change their password.
- The booking-success view remains visible throughout.
- Acceptance requires a real fresh-email booking; source inspection alone is not end-to-end proof.

## Delnavazan administrator navigation

- The top-level WordPress menu is **Delnavazan**, not Attendance.
- Its Overview summarises site enhancements, student-account helpers, Attendance/Google connection count and WhatsApp status.
- Attendance log, Teacher payments, Documentation and Settings remain separate functions under that menu.
- Existing `page=dln-attendance*` URLs are preserved for bookmarks, redirects and operational links.

## Password-reset email

- WordPress supplies the reset key and user login.
- The plugin constructs the normal WordPress reset URL, sends an RTL HTML message, and does not store or log the key.

## Meta Pixel

- Pixel ID: `888269353885959`.
- The module runs at `wp_head` priority 99 and does nothing when `window.fbq` already exists, protecting the staged migration from duplicate initialization.
- After migration, browser diagnostics must confirm one PageView, not merely that the script appears in source.

## Release and rollback

- Build offline, parse every PHP file, validate embedded JavaScript, inspect the ZIP and hash it.
- Upload inactive, perform the controlled switch, then execute the acceptance list in `INSTALLATION.md`.
- Production cleanup was approved and completed on 31 August 2026. The old plugins and Code Snippets are no longer installed; local ZIP/source snapshots are the rollback artifacts.

## Safe plugin migration

- Version 2.0.2 checks WordPress's active-plugin registry before loading Site Enhancements or Attendance code. It does not depend on alphabetical plugin load order.
- Version 2.0.4 supports Amelia employee sessions that authenticate without a persisted cabinet token. The identity comes from Amelia's successful-login callback, must match an Amelia provider record, is rate-limited for logged-out WordPress visitors, receives only a five-minute one-time grant, and must match the Google OAuth email before a connection is saved.
- Version 2.0.5 registers the Amelia employee-login callback in the document head on pages containing `[dln_attendance_google_connect]`, ensuring the listener exists before Amelia starts its panel.
- Version 2.0.6 prevents card flicker by preserving the last verified card across temporary rate-limit or network errors. It still clears the card after Amelia logout or a definitive 401/403 response.
- Version 2.0.7 changed documentation and release metadata only.
- Version 2.0.8 adds the Delnavazan administrator overview and hardens the password setup handoff without changing Attendance storage, cron, routes or Amelia tables.
- Version 2.0.9 removes the helper's `Schedule` and `Purchase` hooks. Amelia treats those hooks as continuation gates; the earlier handlers did not invoke Amelia's success callback and caused a free on-site booking to surface a false payment-processing error. The helper now observes only a successful booking response containing the secure customer-panel URL.
- Version 2.0.10 installs the passive observer before Amelia boots, handles nested or encoded Amelia API responses, and falls back to reading Amelia's booked Vue state after confirmation. Every candidate remains restricted to a same-origin token-bearing customer URL and is opened with `changePass=1`; no Schedule or Purchase continuation hook is registered.
- Version 2.0.11 extends that fallback across the Vue components attached to Amelia's rendered confirmation controls and their parent state. Discovery is time-limited, read-only and post-booking; it never calls an Amelia booking action.
- Version 2.0.12 removes the custom iframe and private-state discovery. Only after Amelia renders both a confirmed Appointment ID and its native password action, the helper shows a Persian handoff notice and invokes that exact control after 1.6 seconds. Amelia owns the secure URL and destination page.
- Version 2.0.13 searches the rendered document rather than only the original booking container because Amelia teleports its confirmation controls. The trigger still requires both an Appointment ID and the exact password-action wording before one native click is scheduled.
- Version 2.0.15 presents the handoff as a mobile-first modal overlay after Amelia exposes its verified native password action. The learner explicitly chooses **Choose portal password** or **View booking details**; no timer redirects them and the overlay does not participate in Amelia's confirmation layout. It also replaces only Amelia's exact generic payment sentence on `/enrol/` with truthful no-payment and returning-student guidance; it does not disable customer identity matching or booking limits.
- While either legacy plugin is active, its integrated replacement remains off and an administrator warning shows safe migration mode.
- Snippets 11, 17 and 18 were inactive before final cutover and Code Snippets was removed after verifying every remaining snippet was inactive.
- Google, Meta and webhook secrets are migrated to authenticated encrypted option values. The key derives from WordPress secure-auth salts; if those salts are changed, reconnect or re-enter affected credentials.

## Final cleanup record

- Deleted: Delnavazan Attendance Tracker 1.3.3, Delnavazan Site Enhancements 1.0.0 and inactive Delnavazan Enhancements 2.0.1.
- Removed: Code Snippets 3.10.1 after verifying Current year, Pixel, Delnavazan Password Reset Email and Amelia Enrolment password setup were all inactive.
- Preserved: local legacy ZIP/source snapshots and the Code Snippets database because Complete Uninstall remained disabled.
- Final pre-2.0.8 live inventory: 14 installed plugins, all active; Delnavazan Enhancements 2.0.6 was the sole custom Delnavazan plugin before the 2.0.7 documentation roll-up.
- Rollback: deactivate Delnavazan Enhancements first, restore only the exact legacy component required, and test duplicate classes/functions, Pixel events, emails and Amelia UI before leaving it active.

