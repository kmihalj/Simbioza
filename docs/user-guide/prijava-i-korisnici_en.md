# Login and users

This guide takes an administrator through every login setting, user attribute, group, user account, local registration, impersonation, API key, SSO provider, and personal security and access screen.

<div class="alert alert-info">

**The examples were created on a clean SQLite installation.** Accounts, groups, and keys shown here exist only for documentation. Secrets and personal attribute values have been removed from screenshots. In production, use your own names, domains, lifetimes, and least-privilege rules.

</div>

## Contents

1.  [Login settings](#1-login-settings)
2.  [User attributes](#2-user-attributes)
3.  [Groups](#3-groups)
4.  [Users and local accounts](#4-users-and-local-accounts)
5.  [Local login and registration](#5-local-login-and-registration)
6.  [Impersonation](#6-impersonation)
7.  [API keys](#7-api-keys)
8.  [Common SSO workflow](#8-common-sso-workflow)
9.  [SAML](#9-saml)
10. [SAML proxy](#10-saml-proxy-and-child-profiles)
11. [CAS](#11-cas)
12. [OIDC](#12-oidc)
13. [OAuth2](#13-oauth2)
14. [Personal profile: security and access](#14-personal-profile-security-and-access)
15. [Verification and security checklist](#15-verification-and-security-checklist)

<a id="login-settings"></a>

## 1. Login settings

Open **Settings → Users → Login settings** . This page enables authentication methods for the application. Enabling a provider only makes its configuration and menu available; every individual profile must still be saved, activated, tested, and mapped correctly.

<span class="figure editor-html-media-figure editor-html-media-image d-inline-block align-top me-2 mb-2 w-100"><img src="images/fb8366ae-1f95-45c3-82a7-b4b374ba51a2.png" class="figure-img img-fluid rounded mb-1" alt="Login settings and configuration of available sign-in methods." /><span class="figure-caption d-block">Login settings overview</span></span>

<figure class="my-4">

<figcaption>Login settings in the English interface.</figcaption>
</figure>

### Main switches

- **Local login** enables username and password authentication stored by Simbioza.
- **SimpleSAMLphp, CAS, OIDC, and OAuth2** enable the corresponding SSO profile groups.
- **Break-glass local login** keeps a separate direct administrator route available when normal local login is disabled. Protect that account with a strong unique password and use it only for recovery.
- **Login duration** is an inactivity limit in days (1–365), renewed while the signed-in user remains active. Use a shorter period on administrative and shared devices.

<div class="alert alert-warning">

**“Profile active” is not the same as the green check mark.** Active means that the profile may be used. The green check and *Tested and operational* status appear only after a successful provider test and valid required mappings. An active profile may still be non-operational.

</div>

<a id="attributes"></a>

## 2. User attributes

Open **User attributes** . The model normally includes e-mail, first name, and last name. Add an attribute when the application must store a value outside the basic profile, such as an institution, department, employee number, or an external directory role.

<span class="figure editor-html-media-figure editor-html-media-image d-inline-block align-top me-2 mb-2 w-100"><img src="images/f1ea79f6-6612-4b72-bb14-29ff71f2a9d8.png" class="figure-img img-fluid rounded mb-1" alt="Settings for default and custom user attributes." /><span class="figure-caption d-block">User attributes</span></span>

<figure class="my-4">

<figcaption>Default and custom attributes in the English interface.</figcaption>
</figure>

### Adding an attribute

1.  Enter a stable machine name in lowercase without spaces, such as `affiliation` .
2.  Provide separate Croatian and English labels.
3.  Choose the data type and configure **Required** , **Registration** , **Profile** , and **Active** independently.
4.  Save it. The new field becomes available in user profiles and in every SSO profile mapping table.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/6e88b4f0-77b9-4da0-8e7a-e84f74f86e7e.png" class="img-fluid rounded border" alt="Bilingual custom attribute example" /> <span class="figure-caption d-block">Bilingual custom attribute example</span> </span>
<figcaption>The affiliation model field is labelled Ustanova / Institution, remains available to profiles and SSO mappings, and is excluded from self-registration.</figcaption>
</figure>

**Mapping** connects a Simbioza model field to the exact attribute name returned by the provider. Attribute names are exact: `mail` , `givenName` , and `given_name` are different fields. Test first, inspect the returned names, and only then enter the mappings. An SSO mapping does not automatically add the field to the public registration form.

<div class="alert alert-info">

**Which fields are included in self-registration?** The administrator chooses this per attribute. **Registration** controls whether a field appears in the public form, while **Required** rejects an empty value in contexts where that field participates. When both settings are enabled, the field is mandatory during self-registration. A required attribute must also have a valid SSO mapping for a provider profile test to become operational. In this example, `affiliation` stores the institution supplied by a provider and appears in the profile, so it is intentionally excluded from registration.

</div>

<a id="groups"></a>

## 3. Groups

Groups collect users and carry permissions. The Administrator group is a protected system group and should not be used as a normal business group. Create separate least-privilege groups for departments, projects, or roles.

<span class="figure editor-html-media-figure editor-html-media-image d-inline-block align-top me-2 mb-2 w-100"><img src="images/7b5e6453-1378-4ce6-b042-693585e7829d.png" class="figure-img img-fluid rounded mb-1" alt="List of system and example groups with management actions." /><span class="figure-caption d-block">Groups</span></span>

System and example groups in the English interface.\

1.  Select **New group** and enter a clear name and description.
2.  Assign only the permissions members actually need.
3.  Save the group, then add or remove membership from the user detail screen.
4.  Where supported by the installation, SSO users can also be assigned through a rule based on a mapped attribute.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/dae802d6-9f51-47a4-8f9f-769903f624a7.png" class="img-fluid rounded border" alt="Example group before saving" /> <span class="figure-caption d-block">Example group before saving</span> </span>
<figcaption>A documentation group before saving; labels follow the currently selected site language.</figcaption>
</figure>

<a id="users"></a>

## 4. Users and local accounts

Open **Settings → Users → Users** to find accounts, inspect their state, and manage membership and access.

<span class="figure editor-html-media-figure editor-html-media-image d-inline-block align-top me-2 mb-2 w-100"><img src="images/a00666a6-9521-4f7d-bd23-8c116dfc53a8.png" class="figure-img img-fluid rounded mb-1" alt="Responsive list of user accounts with management actions." /><span class="figure-caption d-block">Responsive user table</span></span>

<figure class="my-4">
<span>Balanced user table columns in the English interface.</span>
</figure>

### Creating a local user

1.  Select **New user** .
2.  Enter a unique login identifier, display name, and e-mail.
3.  Choose the local authentication source and assign a strong temporary password.
4.  Require a password change on first login.
5.  Assign only the required groups; administrator access must not be the default.

<span class="figure editor-html-media-figure editor-html-media-image d-inline-block align-top me-2 mb-2 w-100"><img src="images/8eb23938-b2cd-4e3f-b7f2-2f19aa91a301.png" class="figure-img img-fluid rounded mb-1" alt="Form for creating a local user account." /><span class="figure-caption d-block">Create local user form</span></span>

<figure class="my-4">
<span>Example local account form with no password shown.</span>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/80bea9cb-0777-45c4-96ce-3ee97e33ceab.png" class="img-fluid rounded border" alt="New user in the list" /> <span class="figure-caption d-block">New user in the list</span> </span>
<figcaption>The documentation user after creation.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/8caff857-27dd-4bcd-9492-82641074e399.png" class="img-fluid rounded border" alt="User details" /> <span class="figure-caption d-block">User details</span> </span>
<figcaption>Account state, linked providers, and group memberships.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/46024a32-147e-49bb-9097-4a925d195440.png" class="img-fluid rounded border" alt="Mandatory first-login password change" /> <span class="figure-caption d-block">Mandatory first-login password change</span> </span>
<figcaption>A temporary password forces the user to choose a new one before continuing.</figcaption>
</figure>

On the detail page, verify account activity, authentication source, linked external identities, and group memberships. Deactivation is usually safer than deletion because it preserves the audit trail. Never manually replace an external provider's stable subject identifier.

<a id="local-login"></a>

## 5. Local login and registration

**Local login** controls self-registration. Keep it disabled for closed systems and create accounts administratively or through trusted SSO. If enabled, registration creates an active ordinary account and signs the user in. It does not send a verification e-mail or prove ownership of the entered address. For a closed system, keep registration disabled and use administrator-created accounts or a trusted SSO provider. Publish an appropriate privacy notice before accepting registrations. The public form includes only user attributes whose **Registration** option is enabled; the SSO-only *Institution* field is therefore not shown here.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/d88655d6-11ff-40c9-aef9-82a7e31d919b.png" class="img-fluid rounded border" alt="Registration disabled" /> <span class="figure-caption d-block">Registration disabled</span> </span>
<figcaption>Recommended initial state for a closed application.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/71149ada-dd68-4f4f-8db6-7705ba261edd.png" class="img-fluid rounded border" alt="Registration enabled" /> <span class="figure-caption d-block">Registration enabled</span> </span>
<figcaption>Registration enabled only for a controlled example.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/d18a4b43-a1b2-4078-a810-b18a02cc0793.png" class="img-fluid rounded border" alt="Registration link" /> <span class="figure-caption d-block">Registration link</span> </span>
<figcaption>The login screen shows registration only after it is enabled.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/4f5652ca-62a4-4b3a-857c-6fccf29b2e76.png" class="img-fluid rounded border" alt="Registration form" /> <span class="figure-caption d-block">Registration form</span> </span>
<figcaption>Public form with the selected registration fields; the Institution attribute used for SSO mapping is intentionally hidden.</figcaption>
</figure>

After testing, restore the intended production state. Passwords should be unique, sufficiently long, and stored in a password manager. Administrators must never ask users for their current password.

<a id="impersonation"></a>

## 6. Impersonation

**Impersonate user** temporarily opens the application in the selected user's context. Use it for permission and display diagnostics, not for performing business actions on behalf of the user.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/08b8a426-17a9-4fcd-b69c-1d9f877eb742.png" class="img-fluid rounded border" alt="Impersonation screen" /> <span class="figure-caption d-block">Impersonation screen</span> </span>
<figcaption>Selecting an account for controlled support diagnostics. <span>Select only the account needed for the support case.</span></figcaption>
</figure>

<figure class="my-4">
<span>The interface clearly indicates the active impersonated session.</span>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/a0e76efd-bc62-4268-b508-d7de6e12fce8.png" class="img-fluid rounded border" alt="Return to administrator" /> <span class="figure-caption d-block">Return to administrator</span> </span>
<figcaption>End the task by returning to your administrator account.</figcaption>
</figure>

<div class="alert alert-warning">

Impersonation does not test actual SSO authentication, MFA, or provider policy. Use the profile test or a dedicated test account for that purpose.

</div>

<a id="api-keys"></a>

## 7. API keys

These screens are available when the API module is installed and enabled. An API key acts as its owner. Scopes can only reduce the owner's existing permissions; they cannot grant permissions the owner does not have. Use a separate key per integration, a descriptive label, the shortest practical lifetime, and the smallest set of scopes.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/33c88c2c-3956-4eaf-840d-5f236fb052a1.png" class="img-fluid rounded border" alt="API key administration" /> <span class="figure-caption d-block">API key administration</span> </span>
<figcaption>API key administration in the English interface.</figcaption>
</figure>

### Direct key issuance

1.  Select the owner and only the required scopes.
2.  Set an expiry date and, if needed, an allowed IP address or network restriction.
3.  Create the key and immediately copy the secret to secure storage.
4.  The secret is shown only once. If it is lost, revoke the key and issue a new one.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/89146be8-0fe9-4bdd-a406-d92bc09e00bc.png" class="img-fluid rounded border" alt="Least-privilege API key" /> <span class="figure-caption d-block">Least-privilege API key</span> </span>
<figcaption>An intentionally restricted key before creation.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/4f972d0e-66b0-4085-8234-5e3b53fdb175.png" class="img-fluid rounded border" alt="One-time API secret" /> <span class="figure-caption d-block">One-time API secret</span> </span>
<figcaption>The secret has been removed from the screenshot.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/39bcd07d-03f4-4f31-8085-0b34a0604698.png" class="img-fluid rounded border" alt="Active API keys" /> <span class="figure-caption d-block">Active API keys</span> </span>
<figcaption>Review and revoke active keys from this list.</figcaption>
</figure>

### User request and administrator approval

A user may request a key from the personal profile and explain its purpose. The administrator reviews owner, scopes, lifetime, and justification, then approves or rejects the request. After approval the user can reveal the secret once in the personal profile. When Notifications is enabled, an in-app notification also points to that screen. Neither the notification nor an e-mail should contain the secret.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/fcdeaf36-1804-4d2f-8639-78774d1f3580.png" class="img-fluid rounded border" alt="API key request form" /> <span class="figure-caption d-block">API key request form</span> </span>
<figcaption>The user requests a restricted key and states its purpose.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/e20ced95-af39-44d6-ad45-775b198caabb.png" class="img-fluid rounded border" alt="Request sent" /> <span class="figure-caption d-block">Request sent</span> </span>
<figcaption>The request waits for administrator review.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/7f412246-9d37-4f65-a008-49c42fd15f68.png" class="img-fluid rounded border" alt="Pending API request" /> <span class="figure-caption d-block">Pending API request</span> </span>
<figcaption>The administrator checks the request before approval.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/1b731910-dbde-43d7-8e72-416157977d5a.png" class="img-fluid rounded border" alt="Approved API request" /> <span class="figure-caption d-block">Approved API request</span> </span>
<figcaption>Approval creates the key without revealing the secret to the administrator.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/36020798-5696-4756-a707-3a463d582f49.png" class="img-fluid rounded border" alt="API key notification" /> <span class="figure-caption d-block">API key notification</span> </span>
<figcaption>The user is notified about the decision.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/be701281-d85e-40a8-ada2-2668a2c212c0.png" class="img-fluid rounded border" alt="Approved request notification" /> <span class="figure-caption d-block">Approved request notification</span> </span>
<figcaption>The notification leads to one-time secret retrieval.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/140963e5-a521-4a24-83df-d8a549fb49da.png" class="img-fluid rounded border" alt="One-time API secret retrieval" /> <span class="figure-caption d-block">One-time API secret retrieval</span> </span>
<figcaption>The secret is removed and cannot be displayed again after closing.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/0c3b8928-1587-4071-b847-2d66589c6c03.png" class="img-fluid rounded border" alt="Completed API request" /> <span class="figure-caption d-block">Completed API request</span> </span>
<figcaption>The completed request remains auditable without exposing the secret.</figcaption>
</figure>

<a id="sso"></a>

## 8. Common SSO workflow

1.  Register the application with the provider and enter only the necessary endpoints, Client ID, and secret.
2.  Save the active profile. Existing secrets are never displayed while editing; leaving the secret field empty retains the server-side value.
3.  Select **Test profile** before guessing mappings. Complete a real login to collect the provider's actual attributes.
4.  Map subject, login, display name, and user fields from the returned names.
5.  Test again. Only **Tested and operational** together with a green check confirms a complete profile.

### Allowed domains

The list restricts login to organizational domains. Enter one domain per line or a comma-separated list. `example.org` allows that domain and its subdomains; a suffix such as `hr` permits identifiers whose domain ends in `.hr` . An empty list imposes no domain restriction.

**Domain source attribute** must be the real provider attribute containing a value such as `user@example.org` or a domain. It is required only when the allowed-domain list is populated. Do not use a display name or another mutable value.

### Public callback addresses

The provider must return the browser to the public URL of the same installation. Include a subdirectory when one is used: for an application at `https://simbioza.example.org/simbioza` , enter that complete value as the public base URL. It is not the internal PHP-FPM, container, or database address. Register the exact callback URLs shown by the profile.

<a id="saml"></a>

## 9. SAML

A SAML profile depends on a separately installed and correctly configured **SimpleSAMLphp** . Its autoloader, SP auth source, certificates, metadata, and return URLs must already work. The path to `vendor/autoload.php` and the auth source name must match that installation.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/d33dc6ed-ec34-4a5f-b54b-94354364bd31.png" class="img-fluid rounded border" alt="Initial SAML configuration" /> <span class="figure-caption d-block">Initial SAML configuration</span> </span>
<figcaption>The profile is saved, but mappings are not yet confirmed.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/fc09510d-0f1e-44ed-88b5-22d64f4d151f.png" class="img-fluid rounded border" alt="First SAML test" /> <span class="figure-caption d-block">First SAML test</span> </span>
<figcaption>Connectivity works and attributes were collected; values are redacted.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/21676ed7-d1a6-4008-8ab6-8885b087e737.png" class="img-fluid rounded border" alt="Launching the AAI login" /> <span class="figure-caption d-block">Launching the AAI login</span> </span>
<figcaption>The English Simbioza login screen starts the configured AAI@EduHr SSO flow without exposing credentials.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/a92bb0e8-0687-4f28-88c7-e02fa8f36b42.png" class="img-fluid rounded border" alt="SAML mappings" /> <span class="figure-caption d-block">SAML mappings</span> </span>
<figcaption>Required mappings entered from the actual returned attributes.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/31d0a1e9-cc0a-479b-afbc-9aadcd604748.png" class="img-fluid rounded border" alt="Operational SAML profile" /> <span class="figure-caption d-block">Operational SAML profile</span> </span>
<figcaption>The repeated test confirms an operational SAML profile.</figcaption>
</figure>

<a id="saml-proxy"></a>

## 10. SAML proxy and child profiles

A proxy instance represents one SimpleSAMLphp auth source that can expose several upstream providers. The **Default** tab defines the shared autoload path, auth source, and base mapping. Additional child tabs inherit the shared connection settings while keeping their own attribute mappings.

**A proxy has no Allowed domains or Domain source attribute fields.** This is deliberate: upstream providers may return different identifiers and domains. Enforce restrictions at the proxy or upstream provider, not through one shared Simbioza domain rule.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/4f8e5ae8-57d5-4e8a-878c-ac5e78821030.png" class="img-fluid rounded border" alt="Proxy provider selection" /> <span class="figure-caption d-block">Proxy provider selection</span> </span>
<figcaption>The real proxy login offers AAI@EduHR, Facebook, and Google, explaining the three child profiles.</figcaption>
</figure>

### Default / AAI@EduHR

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/faa2088e-6d03-4385-852f-0bab107c5ae1.png" class="img-fluid rounded border" alt="First default proxy test" /> <span class="figure-caption d-block">First default proxy test</span> </span>
<figcaption>Attributes collected before mapping, with values removed.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/3cf0d969-0b78-4ba6-ae53-89391e530d7d.png" class="img-fluid rounded border" alt="Default proxy mappings" /> <span class="figure-caption d-block">Default proxy mappings</span> </span>
<figcaption>Mappings for the default AAI source.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/d731b742-31b9-447a-9bf7-b481b3417240.png" class="img-fluid rounded border" alt="Operational default proxy profile" /> <span class="figure-caption d-block">Operational default proxy profile</span> </span>
<figcaption>Default proxy tab after the successful repeated test.</figcaption>
</figure>

### Facebook

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/a44f01cb-48ba-4b0d-bdb4-f7257ede99c5.png" class="img-fluid rounded border" alt="First Facebook proxy test" /> <span class="figure-caption d-block">First Facebook proxy test</span> </span>
<figcaption>Facebook attributes collected before mapping, with values removed.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/b8477a91-99c7-474b-bdd0-62f141c956e0.png" class="img-fluid rounded border" alt="Facebook child mappings" /> <span class="figure-caption d-block">Facebook child mappings</span> </span>
<figcaption>Stable Facebook ID, login, and user fields mapped independently.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/cd8a17cd-616a-4826-8caa-85ace9446401.png" class="img-fluid rounded border" alt="Operational Facebook child profile" /> <span class="figure-caption d-block">Operational Facebook child profile</span> </span>
<figcaption>Facebook child tab after successful mapping and test.</figcaption>
</figure>

### Google

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/c0338f0d-59e3-4a6b-ab0b-fdd3a1ee8f0c.png" class="img-fluid rounded border" alt="First Google proxy test" /> <span class="figure-caption d-block">First Google proxy test</span> </span>
<figcaption>Google attributes collected before mapping, with values removed.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/da95a7fc-0699-4fb3-b8f3-874cdfd0f370.png" class="img-fluid rounded border" alt="Google child mappings" /> <span class="figure-caption d-block">Google child mappings</span> </span>
<figcaption>Google UID is the subject, Google e-mail is the login, and name fields map into the model.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/4290218a-cbf8-4cd3-b989-0c4c2700a349.png" class="img-fluid rounded border" alt="Operational Google child profile" /> <span class="figure-caption d-block">Operational Google child profile</span> </span>
<figcaption>Google child tab after the successful repeated test.</figcaption>
</figure>

<a id="cas"></a>

## 11. CAS

A CAS profile needs the server base URL and its login, validation, and logout endpoints. If a private CA chain is used, provide a readable certificate path. Never disable TLS validation to work around an incorrect certificate configuration.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/aa4de493-9953-4a64-a76c-548b7aa92c9d.png" class="img-fluid rounded border" alt="Initial CAS configuration" /> <span class="figure-caption d-block">Initial CAS configuration</span> </span>
<figcaption>The CAS connection is prepared but mappings are not complete.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/417fd550-238d-4b9b-b008-280738935d04.png" class="img-fluid rounded border" alt="First CAS test" /> <span class="figure-caption d-block">First CAS test</span> </span>
<figcaption>CAS authentication returns attributes; values are redacted.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/1e2d14d5-7702-4326-8bca-73f266673fa9.png" class="img-fluid rounded border" alt="CAS mappings" /> <span class="figure-caption d-block">CAS mappings</span> </span>
<figcaption>Stable subject, login, display name, and model fields mapped from the test.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/c4341d65-56fa-4c42-82cf-28db9b05e563.png" class="img-fluid rounded border" alt="Operational CAS profile" /> <span class="figure-caption d-block">Operational CAS profile</span> </span>
<figcaption>The repeated test confirms the CAS profile.</figcaption>
</figure>

<a id="oidc"></a>

## 12. OIDC

OIDC uses a Client ID and secret, authorization, token, userinfo, and optionally end-session endpoints. Scope must include `openid` , followed only by required data such as `profile` and `email` . **Public redirect base URL** is the complete public base address of this installation, including a subdirectory where present.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/a8ba39ed-751e-4586-9199-a5cdf7b80c8c.png" class="img-fluid rounded border" alt="Initial OIDC configuration" /> <span class="figure-caption d-block">Initial OIDC configuration</span> </span>
<figcaption>OIDC endpoints and public base URL before mapping.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/7dd1a0ab-0d45-48f9-a529-7fb5016ba951.png" class="img-fluid rounded border" alt="First OIDC test" /> <span class="figure-caption d-block">First OIDC test</span> </span>
<figcaption>OIDC claims collected before mapping, with values removed.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/ff56adb1-ea0d-4d0a-bf0e-02b358a71c05.png" class="img-fluid rounded border" alt="OIDC mapping and domain" /> <span class="figure-caption d-block">OIDC mapping and domain</span> </span>
<figcaption>Subject, login, display name, user fields, and domain source mapped from actual claims.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/0603f7ff-a5e7-4f13-a6e6-b7b144b992a5.png" class="img-fluid rounded border" alt="Operational OIDC profile" /> <span class="figure-caption d-block">Operational OIDC profile</span> </span>
<figcaption>The repeated test confirms OIDC and displays the green check.</figcaption>
</figure>

Use a forced-login parameter such as `max_age=0` only when the provider supports it and **Force login** is enabled. Otherwise leave it blank to preserve a valid existing SSO session.

<a id="oauth2"></a>

## 13. OAuth2

OAuth2 uses authorization, token, and userinfo endpoints plus the scopes required for user data. If the provider supports OpenID Connect, prefer OIDC because it standardizes identity and token validation. OAuth2 logout may require a dedicated endpoint, redirect parameter name, or hint; enter only values required by the provider documentation.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/c8024292-d7ab-4926-82fc-3ae3135df40e.png" class="img-fluid rounded border" alt="Initial OAuth2 configuration" /> <span class="figure-caption d-block">Initial OAuth2 configuration</span> </span>
<figcaption>OAuth2 profile before attribute mapping.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/5a358208-3572-4641-970a-278bf2b22082.png" class="img-fluid rounded border" alt="First OAuth2 test" /> <span class="figure-caption d-block">First OAuth2 test</span> </span>
<figcaption>Userinfo attributes collected through a real login, with values removed.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/0df4572c-6616-4522-939e-f825f70504d2.png" class="img-fluid rounded border" alt="OAuth2 mapping and domain" /> <span class="figure-caption d-block">OAuth2 mapping and domain</span> </span>
<figcaption>Mappings and domain restriction entered from the test result.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/d3faabdb-f174-4a6b-9f00-45f56e11eb3b.png" class="img-fluid rounded border" alt="Operational OAuth2 profile" /> <span class="figure-caption d-block">Operational OAuth2 profile</span> </span>
<figcaption>The repeated test confirms the OAuth2 profile.</figcaption>
</figure>

<a id="personal-profile"></a>

## 14. Personal profile: security and access

Open **Personal profile** from the user menu. The main section shows identity and custom attributes. **Security and access** displays group membership, local password state, linked external logins, and API key requests.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/52960239-b877-4349-bd2a-016f5837e8f7.png" class="img-fluid rounded border" alt="Personal profile" /> <span class="figure-caption d-block">Personal profile</span> </span>
<figcaption>Personal data and custom attributes in the English interface.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/12c871d9-5263-4a65-93f0-43ed0980d549.png" class="img-fluid rounded border" alt="Security and access expanded" /> <span class="figure-caption d-block">Security and access expanded</span> </span>
<figcaption>Groups, local login, linked providers, and API requests in one place.</figcaption>
</figure>

Link an external account only while you control both identities. Remove unknown or unused links, but first confirm another login method remains available. Change local passwords through this secure form, never through e-mail or an administrator message.

<a id="verification"></a>

## 15. Verification and security checklist

- Keep at least one controlled administrator login available; store the break-glass account outside normal daily use.
- For every SSO profile, verify exact callback URLs, TLS certificates, minimum scopes, and secret rotation.
- Do not accept an active checkbox as proof. Require the green operational state after the repeated test.
- The subject must be an immutable provider identifier. Login and e-mail may change and are not always suitable primary identifiers.
- Test domain restrictions with both an allowed and a denied test account.
- Regularly review administrators, group memberships, linked external logins, active API keys, and pending requests.
- Never put passwords, client secrets, tokens, API secrets, or raw personal attributes in screenshots, documentation, tickets, or source control.
- After testing, remove or deactivate documentation accounts and keys that are no longer required.

<div class="alert alert-success">

After saving, test a normal login and a denied account. Confirm that each user sees only the content and actions assigned to their groups.

</div>
