# Simbioza 0.3.19 — clearer Survey settings and inherited application URLs

[Croatian version](release-0.3.19_hr.md)

This maintenance release includes **Survey 0.3.1**. Framework, Auth and all
other public module versions are unchanged.

## Changes

- External Survey login reuses the trusted public application URL or the
  configured Email link URL. A separate Survey URL is no longer required when
  that address exists. Application prefixes work in HTTP and CLI integrations.
  Previous explicit Survey addresses remain optional advanced overrides.
  Request Host headers never determine callbacks.
- General settings are grouped by purpose. Durations use minutes/hours and
  sizes use MB, with explanations and exact conversion to existing storage.
  Technical options start collapsed; saved values are never silently rounded.
- Optional SAML source evidence is visible only for proxy instances and their
  tabs. Tested subprofiles normally need no extra source attribute.
- Advanced rules explain delegated author filters and mandatory administrator
  restrictions with examples. Examples do not activate rules.
- Native Croatian/English administrator guides and all six Survey interface
  translations are updated. Only installed, enabled languages are selectable.

## Updating

Use the normal GUI update on secured-FPM installations. An installed Survey
receives 0.3.1; sites without Survey retain their module selection. There are
no new migrations or required provider changes. Existing mappings, tests, rules,
responses and intentional URL overrides are preserved. OIDC/OAuth2 providers
still require their displayed callback to be registered.

The already provisioned Demo uses its installed private updater from the Demo
directory. The one-time private-support bootstrap used for 0.3.18 is not needed
again. The updater restores the clean baseline before upgrading and captures a
new clean reset baseline afterward; visitor surveys are not retained.

Docker deployments use `ghcr.io/kmihalj/simbioza:0.3.19`. See the
[Docker guide](docker_en.md) and [module dependencies](module-dependencies_en.md).
