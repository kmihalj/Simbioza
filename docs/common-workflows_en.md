# Common development workflows

Simbioza owns application composition. Modules own their domain behavior and
Framework supplies the existing foundation. Change the responsible repository,
keep administrator settings/data intact, and verify the affected user flow.

## Use the HTML Editor on a form field

Start with the [complete form-field example](html-editor-forms_en.md).
It explains assets, rendering an editor for a textarea, synchronizing its HTML
before submission, and server-side handling. This does not require creating a
Wiki page. The host still authenticates the user, checks permission to edit the
record, validates CSRF and saves the field. Do not treat client-supplied HTML
as trusted merely because the visual editor produced it.

## Add an application page

1. Add the route in `config/routes.php` using the existing declaration pattern.
   Choose the HTTP method, stable route name and required middleware.
2. Implement its action in `src/Controllers/`; it must return a PSR-7
   `ResponseInterface`. Put business work in a service instead of a template.
3. Register required dependencies in `config/services.php` and render an HTML
   template under `views/` through the normal response/view service.
4. Generate links by route name so an installation subdirectory is preserved.
5. Test as a guest, an authorized user and a user lacking permission. A hidden
   menu link does not protect a directly entered URL.

For details see [routes](routes_en.md), [views](views_en.md) and
[configuration](configuration_en.md). Preserve the active external configuration
layers when working on an installed site.

## Add a module feature or API resource

Use the owning module's services and optional integration contracts. A module
settings item appears only when its capability is enabled. In Simbioza, manage
installed modules through **Settings → Modules** or the documented CLI; editing
only `app.modules.enabled` bypasses the central lifecycle and dependency policy.

API resources belong to the installed API extension of their owner. They need
Bearer scopes **and** current domain permissions; do not expose a browser form
controller as an API shortcut. Follow the [implemented API contract](api-v1-contract_en.md)
and [module dependencies](module-dependencies_en.md). API-key ownership is not
permission to act as someone else's Survey respondent.

## Change schema or persistent settings

Add a forward migration for an installed database; do not edit an old migration
and expect it to run again. Keep HTTP and CLI configuration roots identical.
Use the owner's configuration store and lock for writable module settings, not
ad-hoc writes to an arbitrary PHP file. Preserve unknown extension keys and
unrelated administrator choices. Document any backup/restore consequences.

## Update instructions and verify

Update both language versions and screenshots when behavior changes. The
[documentation workflow](documentation-quality_en.md) rebuilds the manual used
inside Simbioza. Run focused module checks first, then the applicable
[isolated end-to-end flow](end-to-end-testing_en.md) when modules interact.
Inspect the result in the actual interface; a passing unit test does not prove
a dropdown is usable or that an action is visible to the right role.
