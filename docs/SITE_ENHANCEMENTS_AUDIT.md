# Site Enhancements audit

## Inventory

- Core navigation, performance and global presentation hooks.
- Article archive and single-article presentation.
- Homepage CSS and DOM enhancements.
- Enrolment/Amelia catalogue presentation.
- Payment-page currency presentation.

## Findings and treatment

1. **Large accumulated visual surface.** The migrated modules contain many CSS and DOM rules coupled to current Neve, Otter and Amelia markup. They remain intentionally preserved in 2.0.15 to avoid an uncontrolled visual rewrite. Test affected pages after theme or Amelia updates.
2. **Amelia asset allow-list.** Front-end Amelia assets are removed outside `enrol`, `honarjo`, `ostad` and `how`. Any future page embedding Amelia must be added before publishing or its booking interface may fail.
3. **Hard-coded payment presentation.** Currency labels and displayed term prices are front-end constants. Update and verify them whenever commercial pricing changes; Stripe remains authoritative for the actual charge.
4. **DOM timing and selectors.** Several enhancements wait for dynamic page content and use current class names. A visual page load is required after theme, Otter or Amelia changes.
5. **Meta Pixel ownership.** Pixel ID `888269353885959` is now owned by this plugin. Do not add the same Pixel through another plugin or theme.
6. **No public write endpoints.** The Site presentation modules register front-end filters/actions only. Administrative and public writes belong to the Attendance module and retain their capability, nonce or signed-token controls.
7. **No secret material.** The Site modules and migrated snippets contain no access tokens, passwords or OAuth secrets.

## Production consolidation

The legacy Site Enhancements plugin and Code Snippets were removed after the unified plugin passed the live consolidation checks. Delnavazan Enhancements is now the single owner of the modules listed above. The historical Code Snippets database remains available for reversible inspection because Complete Uninstall was left disabled.

Consolidating repeated CSS and JavaScript inside the migrated modules remains deferred. Any internal cleanup must be done page by page with desktop/mobile screenshot comparison and a rollback package. Known presentation follow-up: the public footer repeats the English Google Calendar disclosure; identify both output owners and preserve privacy requirements before removing the duplicate.

