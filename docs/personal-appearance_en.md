# Personal appearance

An authenticated user can choose the application's appearance in the profile
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

Existing installations receive the `theme_mode` preference through the normal
application migration included in the next Simbioza update. Fresh installations
already create the column in the initial Simbioza User schema.
