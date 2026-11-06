# Personal appearance

With the Simbioza User module installed and enabled, an authenticated user can choose the application's appearance in the profile
only while **Settings → Theme → Site-wide settings → Mode policy** is set to
**Automatic**.

The profile offers:

- **Light** — always use the light variant;
- **Dark** — always use the dark variant;
- **Automatic** — inherit the application's automatic policy;
- **System** — explicitly follow the device's `prefers-color-scheme` setting.

The preference is stored per user and applies after sign-in on every device.
It does not change the global theme or another user's choice.

If an administrator changes the site-wide policy to **Light only** or **Dark
only**, the complete Appearance section disappears from profiles. The server
ignores stored personal values and rejects direct profile or API changes while
the fixed policy is active. Returning the global policy to Automatic makes the
section and the previously saved choice available again.

The `theme_mode` preference is stored in the Simbioza User schema and is
preserved by its backup provider. Keep the module migrations current through
the normal application update; do not create or rename the column manually.

The Demo-only header selector previews a different complete theme for one
browser and does not change this global policy. Accessibility contrast choices
may intentionally override a fixed light/dark appearance for that browser.
See [themes](theme-collection_en.md), [module dependencies](module-dependencies_en.md) and
the [user manual](simbioza_overview_en.md).
