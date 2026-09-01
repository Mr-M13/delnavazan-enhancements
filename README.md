# Delnavazan Enhancements

Unified replacement for:

- Delnavazan Site Enhancements
- Delnavazan Attendance Tracker
- Code Snippets 11, 17 and 18

Version 2.0.17 makes reminder evidence explicit, keeps already-delivered class buttons valid after reschedules, validates every class-action button before calling Meta, and previews student, teacher and Brisbane times without changing intentionally disabled Amelia notifications.

## Version 2.0.17 — stable class buttons and evidence-based delivery labels

- Keeps signed public IDs stable across Amelia time changes while refreshing the 30-day post-class expiry. Cancellation, archive state and expiry still revoke access.
- Validates the Join class and Report absence buttons before submitting a reminder to Meta.
- Shows student-local, teacher-local and Brisbane site times before production sends.
- Distinguishes **Meta accepted — delivery receipt pending**, Meta delivery/read/failure receipts, tracked class-link activity, and administrator-recorded customer confirmation.
- Adds auditable manual receipt fields and upgrades the Attendance schema from 8 to 9. No message is sent by the manual confirmation control.

## Version 2.0.16 — WhatsApp delivery visibility and clearer controls

- Adds the approved-booking student confirmation workflow using the active `student_intro_class_confirmed` template.
- Adds administrator-only **Send confirmation now** and **Send reminder now** actions beside eligible Attendance records, showing the complete Amelia phone number and blocking duplicate production sends.
- Manual reminder requests use an atomic lock only while Meta is processing the request; the lock clears immediately after the API response. The unattended scheduler keeps its separate 10-minute retry guard.
- Stores confirmation attempts, Meta message IDs, errors, and delivery states.
- Preserves Amelia's existing Meta `messages` webhook and observes its status events before Amelia continues processing. Delnavazan updates only message IDs it already recorded and displays **Accepted**, **Sent**, **Delivered**, **Read**, or **Failed**.
- Presents Booking approval, Next-day reminder, Teacher absence, and Term-renewal workflows in a responsive two-column card grid with the same field order, equal card height, and consistent production/test buttons.
- Renames Attendance workflow states so they cannot be mistaken for Amelia booking statuses; for example, `pending` is displayed as **Awaiting attendance check**.
- Streamlines the Delnavazan Overview and removes obsolete experimental integration controls while preserving their saved credentials/settings.
- Upgrades the Attendance schema from 7 to 8. Existing records are preserved and the migration only adds WhatsApp status fields.
- A fresh full UpdraftPlus backup was completed to Google Drive on 31 August 2026 at 22:08 before this release was applied.
- Live acceptance passed on temporary test booking #281 (subsequently removed): Meta accepted the approval confirmation and the Attendance log advanced to **Read** through the shared Amelia webhook.
- The production next-day reminder for actual booking #262 was accepted by Meta at 23:36 on 31 August 2026; its latest delivery state remains visible in Attendance.

## Included modules

- Site presentation: header, homepage, articles, enrolment and payment-page enhancements.
- Attendance: signed class links, absence reporting, Google authorization and reconciliation, archive workflow, payment statements and WhatsApp workflows.
- Enrolment password setup: after Amelia renders a confirmed Appointment ID and its own password action, the helper displays a Persian handoff notice and invokes that native action. Amelia remains responsible for generating and validating the secure customer URL.
- Branded password-reset email.
- Meta Pixel ID `888269353885959` with an overlap guard.

## Production consolidation state

The live consolidation was completed on 31 August 2026. Delnavazan Enhancements is the only installed Delnavazan custom plugin. Attendance Tracker 1.3.3, Site Enhancements 1.0.0, duplicate Enhancements 2.0.1 and Code Snippets 3.10.1 were removed after live acceptance. WordPress showed 14 installed plugins, all active.

The removed packages and operational snippet sources remain available locally for rollback. Code Snippets Complete Uninstall was left disabled, so its historical database is retained if the plugin must be reinstalled for inspection. Never restore a legacy custom component alongside the unified plugin; deactivate the unified plugin first.

## Data preservation

Uninstall preserves Attendance data by default. Data removal requires the explicit `DLN_ATTENDANCE_REMOVE_DATA_ON_UNINSTALL` constant.

## Production acceptance

- Teacher Google authorization was tested live: initial connect, logout/login persistence, disconnect and reconnect all worked.
- The card appears only after Amelia provider login, remains stable across temporary rate-limit/network responses, and clears after logout, invalid session or successful durable connection.
- Attendance, Teacher Payment Report, Settings, homepage, enrolment and teacher-portal surfaces were opened during the consolidation acceptance pass.
- The WordPress administrator navigation is grouped under **Delnavazan**, with Overview, Attendance log, Teacher payments, Documentation and Settings. Existing Attendance URLs remain valid.
- Version 2.0.16 live acceptance confirmed schema 8, four uniform workflow cards, the Meta `messages` subscription, a simulated Delivered callback, and an approved-booking confirmation advancing to Read.
- Version 2.0.13 passed the live native password handoff test. Version 2.0.15 requires a mobile presentation check of both explicit overlay actions and one controlled repeat-student error-copy check.
- A full independent metered security scan was not completed after the final source changed; structural parsing and named security invariants are not a substitute for that scan.

