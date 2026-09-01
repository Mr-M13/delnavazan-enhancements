# Staged migration and rollback

## Before the switch

1. Take a database and files backup.
2. Record the active versions: Attendance 1.3.4, Site Enhancements 1.0.0 and active snippets 11, 17 and 18.
3. Upload `delnavazan-enhancements-2.0.15-production.zip`. This focused update preserves the live-verified native handoff, replaces the two-column notice with an explicit mobile-first overlay and retains the exact false payment-error wording correction without changing Amelia rules.
4. Confirm the site and `/wp-admin/` load. The safe-migration notice should list each still-active legacy plugin.

## Controlled switch

Use a quiet test window. Do not delete anything.

1. Deactivate Delnavazan Attendance Tracker.
2. Deactivate Delnavazan Site Enhancements.
3. Deactivate Code Snippets 11, 17 and 18.
4. Activate Delnavazan Enhancements.
5. Clear the site/page cache once.

Version 2.0.15 retains the registry and Pixel overlap guards, although the legacy plugins and Code Snippets were removed after acceptance on 31 August 2026. The guards are transition protection, not permission to reinstall duplicate components alongside the unified plugin.

If a failed 2.0.0 folder is restored from backup, leave it inactive. The obsolete live folders were removed after 2.0.6 acceptance and contain no unique Attendance data.

## Completed cleanup state

- Attendance Tracker 1.3.3, Site Enhancements 1.0.0 and inactive Enhancements 2.0.1 were deleted.
- Code Snippets was deleted with Complete Uninstall disabled, preserving its historical database for reversible inspection.
- The rollback packages and operational snippet snapshots are local artifacts, not live WordPress plugins.
- If rollback is necessary, deactivate Delnavazan Enhancements before restoring any legacy component.

## Required live acceptance

1. Open the homepage, article archive, one article, `/enrol/`, `/payment/`, `/honarjo/` and `/ostad/` on desktop and mobile.
2. Confirm navigation order, mobile CTA, payment currency selector and the Amelia catalogue still render.
3. Confirm exactly one Meta Pixel PageView is emitted.
4. Create a controlled new-student booking with a fresh email. Verify booking success remains visible, the email-confirmation page is never shown as the password dialog, the password window appears with real password fields, password completion works, and the student can sign in.
5. Request one password-reset email and inspect its link and Persian layout.
6. Confirm Attendance settings and existing records remain present.
7. Confirm the WhatsApp controlled-test section displays only the highest-ID recent eligible booking and still requires an administrator-entered recipient.
8. Run one controlled WhatsApp test only after checking the selected booking and number.
9. Verify an unconnected employee sees the Google authorization card after authenticated teacher login, and a logged-out visitor does not.
10. Run Teacher Payment Report sync for a controlled date range and verify introductory sessions remain non-payable.
11. Confirm the hourly reconciliation and daily cleanup cron events are scheduled.
12. In WordPress administration, confirm **Delnavazan** contains Overview, Attendance log, Teacher payments, Documentation and Settings, and that the existing Attendance links still open correctly.

## Rollback

1. Deactivate Delnavazan Enhancements.
2. Reactivate Delnavazan Site Enhancements and Delnavazan Attendance Tracker.
3. Reactivate snippets 11, 17 and 18.
4. Clear cache and repeat the affected acceptance path.

Do not delete the Attendance table or its options. The old components remain installed until the unified plugin has been stable and the owner explicitly approves deletion.

