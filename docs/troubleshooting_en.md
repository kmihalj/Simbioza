# Troubleshooting

Start with the failing URL, time, signed-in role and visible message. Reproduce
one action and inspect that request's status and request ID in the browser's
Network panel. Keep credentials, session cookies and API keys out of reports.

## Blank page or internal server error

Inspect the application's configured `app.logs.dir` (normally `data/logs`) and
the corresponding PHP-FPM/Apache log. Check the **first** exception for the
request rather than guessing from a generic 500 page. Run read-only checks from
the application directory with the installed PHP binary and correct runtime
configuration:

```bash
php -v
composer check-platform-reqs --no-dev
php vendor/bin/hph modules list
php vendor/bin/hph modules migrate-status
```

HTTP and CLI must use the same `framework.php` path parameters,
`HPH_CONFIG_PATH` and optional `HPH_CONFIG_OVERRIDE_PATHS`. Confirm the selected
module is installed and enabled, and that its migration is applied. Do not
rerun an installer or a destructive restore to diagnose a missing table.

The web runtime must read private settings and write its designated runtime
folders. In a secured FPM installation it should not write application code.
Use [logging](logging_en.md), [configuration](configuration_en.md) and
[deployment instructions](installation_en.md) before changing ownership or ACLs.

## Missing page, menu or permission

- Verify the HTTP method, installation subdirectory and route name. A 404 can
  intentionally conceal private or disabled content.
- For a private page/survey, compare the signed-in account, group membership,
  published/draft state and explicit page access. An external Survey login is
  separate from a Simbioza account.
- A missing module route normally means the module is absent or disabled.
  Use module management rather than adding a raw menu URL to unavailable code.
- The standalone HTML/Survey entry is hidden when Wiki is enabled and there
  is no remaining standalone content. Workspace content remains in its tree.
- When adding Workspace to an HTML installation, existing standalone pages
  continue to work. The administrator can transfer them through the Editor's
  settings; this is a controlled move, not a database repair. See the manual.

## Form rejected or CSRF error

Open a fresh form and retry once. Check that the POST includes the expected
CSRF field/header, that the session cookie belongs to the same installation
path, and that no proxy/cache is serving another session's form. Verify the
public HTTPS URL and trusted-proxy configuration when TLS ends at a proxy.
Keep time and session settings consistent across application workers.

Do not disable CSRF or add a normal write form to an exclusion list. A repeated
failure requires finding the missing token/session boundary. Bearer API routes
have their own authentication policy; a browser session does not authorize an
API request. See the [API contract](api-v1-contract_en.md).

## HTML Editor, scripts or appearance

For a form field, compare asset loading, the editor's form synchronization and
server validation with the [working integration example](html-editor-forms_en.md).
A document editor and a standalone form field have different storage owners.

If an approved content script does not run, check its selection on that page,
approval, exact code/URL, optional integrity value and the browser console's
CSP/SRI message. Scripts intentionally stay inactive in editing and historical
views. Do not weaken the site's script policy to bypass a rejected bundle.

A personal light/dark choice is available only under the global automatic mode.
Check the active theme, fixed mode policy and personal accessibility overrides;
see [personal appearance](personal-appearance_en.md).

Before reporting, record the application/module versions, sanitized request ID,
steps and actual result. Include a screenshot of the relevant state. The
[isolated test runner](end-to-end-testing_en.md) is for development verification;
it must not run against a production database.
