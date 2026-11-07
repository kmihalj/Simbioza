# Anmeldung und Benutzer

<a id="login-and-users"></a>


> Die Bildschirmaufnahmen zeigen die englische Benutzeroberfläche.

Dieses Handbuch führt einen Administrator durch alle Anmeldeeinstellungen, Benutzerattribute, Gruppen, Benutzerkonten, lokale Registrierung, Identitätswechsel, API-Schlüssel, SSO-Anbieter und persönliche Sicherheits- und Zugriffsbildschirme.

<div class="alert alert-info">

**Die Beispiele wurden auf einer Neuinstallation von SQLite erstellt.** Die hier gezeigten Konten, Gruppen und Schlüssel dienen nur der Dokumentation. Geheimnisse und persönliche Attributwerte wurden aus Screenshots entfernt. Verwenden Sie in der Produktion Ihre eigenen Namen, Domänen, Lebensdauern und Regeln für die geringsten Rechte.

</div>

<a id="contents"></a>

## Inhalt

1.  [Anmeldeeinstellungen](#1-login-settings)
2.  [Benutzerattribute](#2-user-attributes)
3.  [Gruppen](#3-groups)
4.  [Benutzer und lokale Konten](#4-users-and-local-accounts)
5.  [Lokale Anmeldung und Registrierung](#5-local-login-and-registration)
6.  [Identitätswechsel](#6-impersonation)
7.  [API Schlüssel](#7-api-keys)
8.  [Allgemeiner SSO-Workflow](#8-common-sso-workflow)
9.  [SAML](#9-saml)
10. [SAML-Proxy](#10-saml-proxy-and-child-profiles)
11. [CAS](#11-cas)
12. [OIDC](#12-oidc)
13. [OAuth2](#13-oauth2)
14. [Persönliches Profil: Sicherheit und Zugang](#14-personal-profile-security-and-access)
15. [Verifizierungs- und Sicherheitscheckliste](#15-verification-and-security-checklist)

<a id="login-settings"></a>

<a id="1-login-settings"></a>

## 1. Anmeldeeinstellungen

Öffnen Sie **Einstellungen → Benutzer → Anmeldeeinstellungen**. Auf dieser Seite werden Authentifizierungsmethoden für die Anwendung aktiviert. Durch die Aktivierung eines Anbieters werden nur dessen Konfiguration und Menü verfügbar; Jedes einzelne Profil muss noch gespeichert, aktiviert, getestet und korrekt zugeordnet werden.

<span class="figure editor-html-media-figure editor-html-media-image d-inline-block align-top me-2 mb-2 w-100"><img src="images/fb8366ae-1f95-45c3-82a7-b4b374ba51a2.png" class="figure-img img-fluid rounded mb-1" alt="Anmeldeeinstellungen und Konfiguration der verfügbaren Anmeldemethoden." /><span class="figure-caption d-block">Übersicht über die Anmeldeeinstellungen</span></span>

<figure class="my-4">

<figcaption>Anmeldeeinstellungen in der englischen Benutzeroberfläche.</figcaption>
</figure>

<a id="main-switches"></a>

### Hauptschalter

- **Lokale Anmeldung** ermöglicht die von Simbioza gespeicherte Authentifizierung mit Benutzername und Passwort.
- **SimpleSAMLphp, CAS, OIDC und OAuth2** aktivieren die entsprechenden Profilgruppen SSO.
- **Lokale Break-Glass-Anmeldung** hält eine separate direkte Administratorroute verfügbar, wenn die normale lokale Anmeldung deaktiviert ist. Schützen Sie dieses Konto mit einem starken, eindeutigen Passwort und verwenden Sie es nur zur Wiederherstellung.
- **Anmeldedauer** ist ein Inaktivitätslimit in Tagen (1–365), das verlängert wird, solange der angemeldete Benutzer aktiv bleibt. Verwenden Sie auf administrativen und gemeinsam genutzten Geräten einen kürzeren Zeitraum.

<div class="alert alert-warning">

**„Profil aktiv“ ist nicht dasselbe wie das grüne Häkchen.** Aktiv bedeutet, dass das Profil verwendet werden darf. Das grüne Häkchen und der Status „Getestet und betriebsbereit“ werden erst nach einem erfolgreichen Anbietertest und gültigen erforderlichen Zuordnungen angezeigt. Ein aktives Profil ist möglicherweise immer noch nicht betriebsbereit.

</div>

<a id="attributes"></a>

<a id="2-user-attributes"></a>

## 2. Benutzerattribute

Öffnen Sie **Benutzerattribute**. Das Modell umfasst normalerweise E-Mail, Vorname und Nachname. Fügen Sie ein Attribut hinzu, wenn die Anwendung einen Wert außerhalb des Basisprofils speichern muss, beispielsweise eine Institution, Abteilung, Mitarbeiternummer oder eine externe Verzeichnisrolle.

<span class="figure editor-html-media-figure editor-html-media-image d-inline-block align-top me-2 mb-2 w-100"><img src="images/f1ea79f6-6612-4b72-bb14-29ff71f2a9d8.png" class="figure-img img-fluid rounded mb-1" alt="Einstellungen für Standard- und benutzerdefinierte Benutzerattribute." /><span class="figure-caption d-block">Benutzerattribute</span></span>

<figure class="my-4">

<figcaption>Standard- und benutzerdefinierte Attribute in der englischen Benutzeroberfläche.</figcaption>
</figure>

<a id="adding-an-attribute"></a>

### Ein Attribut hinzufügen

1.  Geben Sie einen stabilen Maschinennamen in Kleinbuchstaben ohne Leerzeichen ein, z. B. `affiliation` .
2.  Geben Sie separate kroatische und englische Etiketten an.
3.  Wählen Sie den Datentyp und konfigurieren Sie **Erforderlich**, **Registrierung**, **Profil** und **Aktiv** unabhängig voneinander.
4.  Speichern Sie es. Das neue Feld wird in Benutzerprofilen und in jeder Profilzuordnungstabelle SSO verfügbar.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/6e88b4f0-77b9-4da0-8e7a-e84f74f86e7e.png" class="img-fluid rounded border" alt="Beispiel für ein zweisprachiges benutzerdefiniertes Attribut" /> <span class="figure-caption d-block">Beispiel für ein zweisprachiges benutzerdefiniertes Attribut</span> </span>
<figcaption>Das Feld „Zugehörigkeitsmodell“ trägt die Bezeichnung „Ustanova/Institution“, bleibt für Profile und SSO-Zuordnungen verfügbar und ist von der Selbstregistrierung ausgeschlossen.</figcaption>
</figure>

**Mapping** verbindet ein Simbioza-Modellfeld mit dem genauen Attributnamen, der vom Anbieter zurückgegeben wird. Die Attributnamen sind genau: `mail`, `givenName` und `given_name` sind unterschiedliche Felder. Testen Sie zuerst, überprüfen Sie die zurückgegebenen Namen und geben Sie erst dann die Zuordnungen ein. Eine SSO-Zuordnung fügt das Feld nicht automatisch zum öffentlichen Registrierungsformular hinzu.

<div class="alert alert-info">

**Welche Felder sind in der Selbstregistrierung enthalten?** Der Administrator wählt dies pro Attribut aus. **Registrierung** steuert, ob ein Feld im öffentlichen Formular angezeigt wird, während **Erforderlich** einen leeren Wert in Kontexten ablehnt, in denen dieses Feld beteiligt ist. Wenn beide Einstellungen aktiviert sind, ist das Feld bei der Selbstregistrierung ein Pflichtfeld. Ein erforderliches Attribut muss außerdem über eine gültige SSO-Zuordnung verfügen, damit ein Anbieterprofiltest betriebsbereit ist. In diesem Beispiel speichert `affiliation` die von einem Anbieter bereitgestellte Institution und erscheint im Profil, sodass sie absichtlich von der Registrierung ausgeschlossen wird.

</div>

<a id="groups"></a>

<a id="3-groups"></a>

## 3. Gruppen

Gruppen sammeln Benutzer und übertragen Berechtigungen. Die Administratorgruppe ist eine geschützte Systemgruppe und sollte nicht als normale Unternehmensgruppe verwendet werden. Erstellen Sie separate Gruppen mit den geringsten Rechten für Abteilungen, Projekte oder Rollen.

<span class="figure editor-html-media-figure editor-html-media-image d-inline-block align-top me-2 mb-2 w-100"><img src="images/7b5e6453-1378-4ce6-b042-693585e7829d.png" class="figure-img img-fluid rounded mb-1" alt="Liste der System- und Beispielgruppen mit Verwaltungsaktionen." /><span class="figure-caption d-block">Gruppen</span></span>

System- und Beispielgruppen in der englischen Benutzeroberfläche.\

1.  Wählen Sie **Gruppe hinzufügen** (die Plus-Schaltfläche), geben Sie einen erkennbaren **Gruppennamen** ein und aktivieren Sie **Aktiv**.
2.  Wählen Sie **Gruppen speichern**. Dieser Bildschirm enthält keine Beschreibung oder Inhaltsberechtigungen.
3.  Fügen Sie den Details jedes Benutzers eine Gruppenmitgliedschaft hinzu oder entfernen Sie sie.
4.  Gewähren Sie der Gruppe die erforderlichen Berechtigungen in den Einstellungen eines Arbeitsbereichs, einer Seite, eines Kalenders oder einer Umfrage. Die SSO-Mitgliedschaft kann auch Regeln folgen, die auf einem zugeordneten Attribut basieren.

**Ergebnis:** Die Gruppe existiert und kann beim Zuweisen von Berechtigungen ausgewählt werden. Durch die alleinige Erstellung werden keine Inhalte für die Mitglieder geöffnet.

Um eine Nicht-Systemgruppe vorübergehend zu deaktivieren, deaktivieren Sie **Aktiv** und speichern Sie. Um es dauerhaft zu löschen, **leeren Sie seinen Namen und wählen Sie Gruppen speichern**. Dadurch werden die Gruppe, ihre Mitgliedschaften und ihre automatischen Zuordnungsregeln gelöscht. Benutzerkonten bleiben bestehen. Überprüfen Sie zunächst, wo die Gruppe für den Inhaltszugriff verwendet wurde. Eine neu erstellte Gruppe mit demselben Namen hat eine andere Identität und stellt die alten Mitgliedschaften oder Berechtigungen nicht wieder her. Systemgruppen können auf diese Weise nicht gelöscht werden.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/dae802d6-9f51-47a4-8f9f-769903f624a7.png" class="img-fluid rounded border" alt="Beispielgruppe vor dem Speichern" /> <span class="figure-caption d-block">Beispielgruppe vor dem Speichern</span> </span>
<figcaption>Eine Dokumentationsgruppe vor dem Speichern; Beschriftungen folgen der aktuell ausgewählten Site-Sprache.</figcaption>
</figure>

<a id="users"></a>

<a id="4-users-and-local-accounts"></a>

## 4. Benutzer und lokale Konten

Öffnen Sie **Einstellungen → Benutzer → Benutzer**, um Konten zu finden, ihren Status zu überprüfen und Mitgliedschaft und Zugriff zu verwalten.

<span class="figure editor-html-media-figure editor-html-media-image d-inline-block align-top me-2 mb-2 w-100"><img src="images/a00666a6-9521-4f7d-bd23-8c116dfc53a8.png" class="figure-img img-fluid rounded mb-1" alt="Responsive Liste von Benutzerkonten mit Verwaltungsaktionen." /><span class="figure-caption d-block">Responsive Benutzertabelle</span></span>

<figure class="my-4">
<span>Ausgewogene Benutzertabellenspalten in der englischen Benutzeroberfläche.</span>
</figure>

<a id="creating-a-local-user"></a>

### Erstellen eines lokalen Benutzers

1.  Wählen Sie **Neuer Benutzer** aus.
2.  Geben Sie eine eindeutige Anmeldekennung, einen Anzeigenamen und eine E-Mail-Adresse ein.
3.  Wählen Sie die lokale Authentifizierungsquelle und weisen Sie ein sicheres temporäres Passwort zu.
4.  Bei der ersten Anmeldung ist eine Passwortänderung erforderlich.
5.  Weisen Sie nur die erforderlichen Gruppen zu. Der Administratorzugriff darf nicht die Standardeinstellung sein.

<span class="figure editor-html-media-figure editor-html-media-image d-inline-block align-top me-2 mb-2 w-100"><img src="images/8eb23938-b2cd-4e3f-b7f2-2f19aa91a301.png" class="figure-img img-fluid rounded mb-1" alt="Formular zum Erstellen eines lokalen Benutzerkontos." /><span class="figure-caption d-block">Erstellen Sie ein lokales Benutzerformular</span></span>

<figure class="my-4">
<span>Beispiel für ein lokales Kontoformular ohne angezeigtes Passwort.</span>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/80bea9cb-0777-45c4-96ce-3ee97e33ceab.png" class="img-fluid rounded border" alt="Neuer Benutzer in der Liste" /> <span class="figure-caption d-block">Neuer Benutzer in der Liste</span> </span>
<figcaption>Der Dokumentationsbenutzer nach der Erstellung.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/8caff857-27dd-4bcd-9492-82641074e399.png" class="img-fluid rounded border" alt="Benutzerdetails" /> <span class="figure-caption d-block">Benutzerdetails</span> </span>
<figcaption>Kontostatus, verknüpfte Anbieter und Gruppenmitgliedschaften.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/46024a32-147e-49bb-9097-4a925d195440.png" class="img-fluid rounded border" alt="Obligatorische Passwortänderung bei der ersten Anmeldung" /> <span class="figure-caption d-block">Obligatorische Passwortänderung bei der ersten Anmeldung</span> </span>
<figcaption>Ein temporäres Passwort zwingt den Benutzer, ein neues zu wählen, bevor er fortfahren kann.</figcaption>
</figure>

Überprüfen Sie auf der Detailseite die Kontoaktivität, die Authentifizierungsquelle, verknüpfte externe Identitäten und Gruppenmitgliedschaften. Die Deaktivierung ist in der Regel sicherer als das Löschen, da dadurch der Prüfpfad erhalten bleibt. Ersetzen Sie niemals manuell die stabile Betreff-ID eines externen Anbieters.

<a id="local-login"></a>

<a id="5-local-login-and-registration"></a>

## 5. Lokale Anmeldung und Registrierung

**Lokale Anmeldung** steuert die Selbstregistrierung. Lassen Sie es für geschlossene Systeme deaktiviert und erstellen Sie Konten administrativ oder über vertrauenswürdige SSO. Wenn diese Option aktiviert ist, erstellt die Registrierung ein aktives normales Konto und meldet den Benutzer an. Es wird keine Bestätigungs-E-Mail gesendet und der Besitz der eingegebenen Adresse wird nicht nachgewiesen. Lassen Sie bei einem geschlossenen System die Registrierung deaktiviert und verwenden Sie vom Administrator erstellte Konten oder einen vertrauenswürdigen SSO-Anbieter. Veröffentlichen Sie eine entsprechende Datenschutzerklärung, bevor Sie Registrierungen annehmen. Das öffentliche Formular enthält nur Benutzerattribute, deren Option **Registrierung** aktiviert ist; das SSO-nur-Feld *Institution* wird daher hier nicht angezeigt.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/d88655d6-11ff-40c9-aef9-82a7e31d919b.png" class="img-fluid rounded border" alt="Registrierung deaktiviert" /> <span class="figure-caption d-block">Registrierung deaktiviert</span> </span>
<figcaption>Empfohlener Ausgangszustand für eine geschlossene Anwendung.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/71149ada-dd68-4f4f-8db6-7705ba261edd.png" class="img-fluid rounded border" alt="Registrierung aktiviert" /> <span class="figure-caption d-block">Registrierung aktiviert</span> </span>
<figcaption>Die Registrierung ist nur für ein kontrolliertes Beispiel aktiviert.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/d18a4b43-a1b2-4078-a810-b18a02cc0793.png" class="img-fluid rounded border" alt="Registrierungslink" /> <span class="figure-caption d-block">Registrierungslink</span> </span>
<figcaption>Der Anmeldebildschirm zeigt die Registrierung erst an, nachdem sie aktiviert wurde.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/4f5652ca-62a4-4b3a-857c-6fccf29b2e76.png" class="img-fluid rounded border" alt="Anmeldeformular" /> <span class="figure-caption d-block">Anmeldeformular</span> </span>
<figcaption>Öffentliches Formular mit den ausgewählten Registrierungsfeldern; das für die Zuordnung SSO verwendete Institutionsattribut ist absichtlich ausgeblendet.</figcaption>
</figure>

Stellen Sie nach dem Testen den vorgesehenen Produktionszustand wieder her. Passwörter sollten eindeutig und ausreichend lang sein und in einem Passwort-Manager gespeichert werden. Administratoren dürfen Benutzer niemals nach ihrem aktuellen Passwort fragen.

<a id="impersonation"></a>

<a id="6-impersonation"></a>

## 6. Identitätswechsel

**Benutzer imitieren** öffnet die Anwendung vorübergehend im Kontext des ausgewählten Benutzers. Verwenden Sie es für Berechtigungs- und Anzeigediagnosen, nicht für die Durchführung von Geschäftsaktionen im Namen des Benutzers.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/08b8a426-17a9-4fcd-b69c-1d9f877eb742.png" class="img-fluid rounded border" alt="Bildschirm „Identitätswechsel“." /> <span class="figure-caption d-block">Bildschirm „Identitätswechsel“.</span> </span>
<figcaption>Auswählen eines Kontos für die kontrollierte Support-Diagnose. <span>Wählen Sie nur das Konto aus, das für den Supportfall benötigt wird.</span></figcaption>
</figure>

<figure class="my-4">
<span>Die Schnittstelle zeigt deutlich die aktive imitierte Sitzung an.</span>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/a0e76efd-bc62-4268-b508-d7de6e12fce8.png" class="img-fluid rounded border" alt="Zurück zum Administrator" /> <span class="figure-caption d-block">Zurück zum Administrator</span> </span>
<figcaption>Beenden Sie die Aufgabe, indem Sie zu Ihrem Administratorkonto zurückkehren.</figcaption>
</figure>

<div class="alert alert-warning">

Der Identitätswechsel testet nicht die tatsächliche SSO-Authentifizierung, MFA oder die Anbieterrichtlinie. Nutzen Sie dazu den Profiltest oder ein dediziertes Testkonto.

</div>

<a id="api-keys"></a>

<a id="7-api-keys"></a>

## 7. API-Tasten

Diese Ansichten stehen zur Verfügung, wenn das API-Modul installiert und aktiviert ist. Ein API-Schlüssel handelt mit den Rechten seines Besitzers. Scopes können dessen vorhandene Berechtigungen nur weiter einschränken; sie gewähren keine zusätzlichen Rechte. Verwenden Sie für jede Anbindung einen eigenen Schlüssel, eine aussagekräftige Bezeichnung, eine möglichst kurze Gültigkeit und möglichst wenige Scopes.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/33c88c2c-3956-4eaf-840d-5f236fb052a1.png" class="img-fluid rounded border" alt="API Schlüsselverwaltung" /> <span class="figure-caption d-block">API Schlüsselverwaltung</span> </span>
<figcaption>API Schlüsselverwaltung in der englischen Oberfläche.</figcaption>
</figure>

<a id="direct-key-issuance"></a>

### Direkte Schlüsselausgabe

1.  Wählen Sie den Eigentümer und nur die erforderlichen Scopes aus.
2.  Legen Sie ein Ablaufdatum und bei Bedarf eine zulässige IP-Adresse oder Netzwerkbeschränkung fest.
3.  Erstellen Sie den Schlüssel und kopieren Sie das Geheimnis sofort in den sicheren Speicher.
4.  Das Geheimnis wird nur einmal gezeigt. Bei Verlust den Schlüssel widerrufen und einen neuen ausstellen.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/89146be8-0fe9-4bdd-a406-d92bc09e00bc.png" class="img-fluid rounded border" alt="Schlüssel mit der geringsten Berechtigung API" /> <span class="figure-caption d-block">Schlüssel mit der geringsten Berechtigung API</span> </span>
<figcaption>Ein absichtlich eingeschränkter Schlüssel vor der Erstellung.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/4f972d0e-66b0-4085-8234-5e3b53fdb175.png" class="img-fluid rounded border" alt="Einmaliges API-Geheimnis" /> <span class="figure-caption d-block">Einmaliges API-Geheimnis</span> </span>
<figcaption>Das Geheimnis wurde aus dem Screenshot entfernt.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/39bcd07d-03f4-4f31-8085-0b34a0604698.png" class="img-fluid rounded border" alt="Aktive API-Schlüssel" /> <span class="figure-caption d-block">Aktive API-Schlüssel</span> </span>
<figcaption>Überprüfen und widerrufen Sie aktive Schlüssel aus dieser Liste.</figcaption>
</figure>

<a id="user-request-and-administrator-approval"></a>

### Benutzeranfrage und Administratorgenehmigung

Ein Benutzer kann einen Schlüssel aus dem persönlichen Profil anfordern und seinen Zweck erläutern. Der Administrator überprüft Eigentümer, Scopes, Lebensdauer und Begründung und genehmigt dann die Anfrage oder lehnt sie ab. Nach der Freigabe kann der Nutzer das Geheimnis einmalig im persönlichen Profil preisgeben. Wenn Benachrichtigungen aktiviert sind, verweist eine In-App-Benachrichtigung auch auf diesen Bildschirm. Weder die Benachrichtigung noch eine E-Mail sollten das Geheimnis enthalten.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/fcdeaf36-1804-4d2f-8639-78774d1f3580.png" class="img-fluid rounded border" alt="API Schlüsselanforderungsformular" /> <span class="figure-caption d-block">API Schlüsselanforderungsformular</span> </span>
<figcaption>Der Benutzer fordert einen eingeschränkten Schlüssel an und gibt seinen Zweck an.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/e20ced95-af39-44d6-ad45-775b198caabb.png" class="img-fluid rounded border" alt="Anfrage gesendet" /> <span class="figure-caption d-block">Anfrage gesendet</span> </span>
<figcaption>Die Anfrage wartet auf die Überprüfung durch den Administrator.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/7f412246-9d37-4f65-a008-49c42fd15f68.png" class="img-fluid rounded border" alt="Ausstehende API-Anfrage" /> <span class="figure-caption d-block">Ausstehende API-Anfrage</span> </span>
<figcaption>Der Administrator prüft die Anfrage vor der Genehmigung.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/1b731910-dbde-43d7-8e72-416157977d5a.png" class="img-fluid rounded border" alt="Genehmigte API-Anfrage" /> <span class="figure-caption d-block">Genehmigte API-Anfrage</span> </span>
<figcaption>Die Genehmigung erstellt den Schlüssel, ohne das Geheimnis dem Administrator preiszugeben.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/36020798-5696-4756-a707-3a463d582f49.png" class="img-fluid rounded border" alt="API Schlüsselbenachrichtigung" /> <span class="figure-caption d-block">API Schlüsselbenachrichtigung</span> </span>
<figcaption>Der Benutzer wird über die Entscheidung benachrichtigt.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/be701281-d85e-40a8-ada2-2668a2c212c0.png" class="img-fluid rounded border" alt="Genehmigte Anforderungsbenachrichtigung" /> <span class="figure-caption d-block">Genehmigte Anforderungsbenachrichtigung</span> </span>
<figcaption>Die Benachrichtigung führt zur einmaligen Geheimabfrage.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/140963e5-a521-4a24-83df-d8a549fb49da.png" class="img-fluid rounded border" alt="Einmaliger Abruf des Geheimnisses API" /> <span class="figure-caption d-block">Einmaliger Abruf des Geheimnisses API</span> </span>
<figcaption>Das Geheimnis wird entfernt und kann nach dem Schließen nicht mehr angezeigt werden.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/0c3b8928-1587-4071-b847-2d66589c6c03.png" class="img-fluid rounded border" alt="API-Anfrage abgeschlossen" /> <span class="figure-caption d-block">API-Anfrage abgeschlossen</span> </span>
<figcaption>Die abgeschlossene Anfrage bleibt überprüfbar, ohne dass das Geheimnis preisgegeben wird.</figcaption>
</figure>

<a id="sso"></a>

<a id="8-common-sso-workflow"></a>

## 8. Allgemeiner SSO-Workflow

1.  Registrieren Sie die Anwendung beim Anbieter und geben Sie nur die erforderlichen Endpunkte, die Client-ID und das Geheimnis ein.
2.  Speichern Sie das aktive Profil. Vorhandene Geheimnisse werden beim Bearbeiten nie angezeigt; Wenn Sie das geheime Feld leer lassen, bleibt der serverseitige Wert erhalten.
3.  Wählen Sie **Profil testen**, bevor Sie Zuordnungen erraten. Führen Sie eine echte Anmeldung durch, um die tatsächlichen Attribute des Anbieters zu erfassen.
4.  Ordnen Sie die Felder Betreff, Login, Anzeigename und Benutzer den zurückgegebenen Namen zu.
5.  Testen Sie noch einmal. Nur **Getestet und betriebsbereit** zusammen mit einem grünen Häkchen bestätigt ein vollständiges Profil.

<a id="allowed-domains"></a>

### Erlaubte Domänen

Die Liste beschränkt die Anmeldung auf Organisationsdomänen. Geben Sie eine Domäne pro Zeile oder eine durch Kommas getrennte Liste ein. `example.org` lässt diese Domäne und ihre Unterdomänen zu; ein Suffix wie `hr` lässt Bezeichner zu, deren Domäne auf `.hr` endet. Eine leere Liste erlegt keine Domänenbeschränkung auf.

**Domänenquellenattribut** muss das echte Anbieterattribut sein, das einen Wert wie `user@example.org` oder eine Domäne enthält. Dies ist nur erforderlich, wenn die Liste der zulässigen Domänen ausgefüllt ist. Verwenden Sie keinen Anzeigenamen oder einen anderen veränderlichen Wert.

<a id="public-callback-addresses"></a>

### Öffentliche Rückrufadressen

Der Anbieter muss den Browser auf die öffentliche URL derselben Installation zurückführen. Schließen Sie ein Unterverzeichnis ein, wenn eines verwendet wird: Geben Sie für eine Anwendung unter `https://simbioza.example.org/simbioza` diesen vollständigen Wert als öffentliche Basis-URL ein. Es handelt sich nicht um die interne PHP-FPM-, Container- oder Datenbankadresse. Registrieren Sie die genauen Rückruf-URLs, die vom Profil angezeigt werden.

<a id="saml"></a>

## 9. SAML

Ein SAML-Profil hängt von einem separat installierten und korrekt konfigurierten **SimpleSAMLphp** ab. Sein Autoloader, die SP-Authentifizierungsquelle, Zertifikate, Metadaten und Rückgabe-URLs müssen bereits funktionieren. Der Pfad zu `vendor/autoload.php` und der Name der Authentifizierungsquelle müssen mit dieser Installation übereinstimmen.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/d33dc6ed-ec34-4a5f-b54b-94354364bd31.png" class="img-fluid rounded border" alt="Erstkonfiguration SAML" /> <span class="figure-caption d-block">Erstkonfiguration SAML</span> </span>
<figcaption>Das Profil wird gespeichert, die Zuordnungen sind jedoch noch nicht bestätigt.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/fc09510d-0f1e-44ed-88b5-22d64f4d151f.png" class="img-fluid rounded border" alt="Erster SAML-Test" /> <span class="figure-caption d-block">Erster SAML-Test</span> </span>
<figcaption>Konnektivitätsarbeiten und -attribute wurden gesammelt; Werte werden geschwärzt.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/21676ed7-d1a6-4008-8ab6-8885b087e737.png" class="img-fluid rounded border" alt="Starten des AAI-Logins" /> <span class="figure-caption d-block">Starten des AAI-Logins</span> </span>
<figcaption>Der englische Anmeldebildschirm Simbioza startet den konfigurierten AAI@EduHr SSO-Ablauf, ohne Anmeldeinformationen preiszugeben.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/a92bb0e8-0687-4f28-88c7-e02fa8f36b42.png" class="img-fluid rounded border" alt="SAML Zuordnungen" /> <span class="figure-caption d-block">SAML Zuordnungen</span> </span>
<figcaption>Erforderliche Zuordnungen, die aus den tatsächlich zurückgegebenen Attributen eingegeben wurden.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/31d0a1e9-cc0a-479b-afbc-9aadcd604748.png" class="img-fluid rounded border" alt="Betriebsprofil SAML" /> <span class="figure-caption d-block">Betriebsprofil SAML</span> </span>
<figcaption>Der wiederholte Test bestätigt ein funktionsfähiges SAML-Profil.</figcaption>
</figure>

<a id="saml-proxy"></a>

<a id="10-saml-proxy-and-child-profiles"></a>

## 10. SAML-Proxy- und untergeordnete Profile

Eine Proxy-Instanz stellt eine SimpleSAMLphp-Authentifizierungsquelle dar, die mehrere Upstream-Anbieter verfügbar machen kann. Auf der Registerkarte **Standard** werden der gemeinsame Autoload-Pfad, die Authentifizierungsquelle und die Basiszuordnung definiert. Zusätzliche untergeordnete Registerkarten erben die gemeinsamen Verbindungseinstellungen und behalten gleichzeitig ihre eigenen Attributzuordnungen bei.

**Ein Proxy hat keine Felder für zulässige Domänen oder Domänenquellenattribute.** Dies ist beabsichtigt: Upstream-Anbieter geben möglicherweise unterschiedliche Kennungen und Domänen zurück. Erzwingen Sie Einschränkungen beim Proxy oder Upstream-Anbieter, nicht durch eine gemeinsame Domänenregel Simbioza.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/4f8e5ae8-57d5-4e8a-878c-ac5e78821030.png" class="img-fluid rounded border" alt="Auswahl des Proxy-Anbieters" /> <span class="figure-caption d-block">Auswahl des Proxy-Anbieters</span> </span>
<figcaption>Der echte Proxy-Login bietet AAI@EduHR, Facebook und Google und erklärt die drei untergeordnete Profile.</figcaption>
</figure>

<a id="default--aaieduhr"></a>

### Standard / AAI@EduHR

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/faa2088e-6d03-4385-852f-0bab107c5ae1.png" class="img-fluid rounded border" alt="Erster Standard-Proxy-Test" /> <span class="figure-caption d-block">Erster Standard-Proxy-Test</span> </span>
<figcaption>Vor dem Mapping gesammelte Attribute, wobei die Werte entfernt wurden.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/3cf0d969-0b78-4ba6-ae53-89391e530d7d.png" class="img-fluid rounded border" alt="Standard-Proxy-Zuordnungen" /> <span class="figure-caption d-block">Standard-Proxy-Zuordnungen</span> </span>
<figcaption>Zuordnungen für die Standard-AAI-Quelle.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/d731b742-31b9-447a-9bf7-b481b3417240.png" class="img-fluid rounded border" alt="Operatives Standard-Proxy-Profil" /> <span class="figure-caption d-block">Operatives Standard-Proxy-Profil</span> </span>
<figcaption>Standard-Proxy-Registerkarte nach erfolgreichem Wiederholungstest.</figcaption>
</figure>

### Facebook

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/a44f01cb-48ba-4b0d-bdb4-f7257ede99c5.png" class="img-fluid rounded border" alt="Erster Facebook-Proxy-Test" /> <span class="figure-caption d-block">Erster Facebook-Proxy-Test</span> </span>
<figcaption>Vor dem Mapping gesammelte Facebook-Attribute, wobei die Werte entfernt wurden.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/b8477a91-99c7-474b-bdd0-62f141c956e0.png" class="img-fluid rounded border" alt="Facebook-Zuordnungen des untergeordneten Profils" /> <span class="figure-caption d-block">Facebook-Zuordnungen des untergeordneten Profils</span> </span>
<figcaption>Stabile Facebook-ID, Login und Benutzerfelder werden unabhängig voneinander zugeordnet.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/cd8a17cd-616a-4826-8caa-85ace9446401.png" class="img-fluid rounded border" alt="Funktionales Facebook-untergeordnetes Profil" /> <span class="figure-caption d-block">Funktionales Facebook-untergeordnetes Profil</span> </span>
<figcaption>Untergeordneter Facebook-Tab nach erfolgreicher Zuordnung und Prüfung.</figcaption>
</figure>

### Google

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/c0338f0d-59e3-4a6b-ab0b-fdd3a1ee8f0c.png" class="img-fluid rounded border" alt="Erster Google-Proxy-Test" /> <span class="figure-caption d-block">Erster Google-Proxy-Test</span> </span>
<figcaption>Vor der Zuordnung erfasste Google-Attribute, wobei die Werte entfernt wurden.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/da95a7fc-0699-4fb3-b8f3-874cdfd0f370.png" class="img-fluid rounded border" alt="Google-Zuordnungen des untergeordneten Profils" /> <span class="figure-caption d-block">Google-Zuordnungen des untergeordneten Profils</span> </span>
<figcaption>Google UID ist der Betreff, Google E-Mail ist der Login und Namensfelder werden dem Modell zugeordnet.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/4290218a-cbf8-4cd3-b989-0c4c2700a349.png" class="img-fluid rounded border" alt="Betriebsfähiges Google-untergeordnetes Profil" /> <span class="figure-caption d-block">Betriebsfähiges Google-untergeordnetes Profil</span> </span>
<figcaption>Google Child-Tab nach erfolgreichem Wiederholungstest.</figcaption>
</figure>

<a id="cas"></a>

## 11. CAS

Ein CAS-Profil benötigt die Server-Basis-URL und seine Anmelde-, Validierungs- und Abmelde-Endpunkte. Wenn eine private CA-Kette verwendet wird, stellen Sie einen lesbaren Zertifikatspfad bereit. Deaktivieren Sie niemals die TLS-Validierung, um eine falsche Zertifikatkonfiguration zu umgehen.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/aa4de493-9953-4a64-a76c-548b7aa92c9d.png" class="img-fluid rounded border" alt="Erstkonfiguration CAS" /> <span class="figure-caption d-block">Erstkonfiguration CAS</span> </span>
<figcaption>Die Verbindung CAS ist vorbereitet, aber die Zuordnungen sind noch nicht vollständig.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/417fd550-238d-4b9b-b008-280738935d04.png" class="img-fluid rounded border" alt="Erster CAS-Test" /> <span class="figure-caption d-block">Erster CAS-Test</span> </span>
<figcaption>CAS Authentifizierung gibt Attribute zurück; Werte werden geschwärzt.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/1e2d14d5-7702-4326-8bca-73f266673fa9.png" class="img-fluid rounded border" alt="CAS Zuordnungen" /> <span class="figure-caption d-block">CAS Zuordnungen</span> </span>
<figcaption>Stabile Betreff-, Anmelde-, Anzeigename- und Modellfelder, die aus dem Test zugeordnet wurden.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/c4341d65-56fa-4c42-82cf-28db9b05e563.png" class="img-fluid rounded border" alt="Betriebsprofil CAS" /> <span class="figure-caption d-block">Betriebsprofil CAS</span> </span>
<figcaption>Der wiederholte Test bestätigt das Profil CAS.</figcaption>
</figure>

<a id="oidc"></a>

## 12. OIDC

OIDC verwendet eine Client-ID und ein Geheimnis, eine Autorisierung, ein Token, Benutzerinformationen und optional Sitzungsendpunkte. Der Bereich muss `openid` enthalten, gefolgt von nur erforderlichen Daten wie `profile` und `email`. **Öffentliche Redirect-Basis-URL** ist die vollständige öffentliche Basisadresse dieser Installation, einschließlich eines Unterverzeichnisses, sofern vorhanden.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/a8ba39ed-751e-4586-9199-a5cdf7b80c8c.png" class="img-fluid rounded border" alt="Erstkonfiguration OIDC" /> <span class="figure-caption d-block">Erstkonfiguration OIDC</span> </span>
<figcaption>OIDC Endpunkte und öffentliche Basis-URL vor der Zuordnung.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/7dd1a0ab-0d45-48f9-a529-7fb5016ba951.png" class="img-fluid rounded border" alt="Erster OIDC-Test" /> <span class="figure-caption d-block">Erster OIDC-Test</span> </span>
<figcaption>OIDC Ansprüche, die vor der Zuordnung erfasst wurden, wobei die Werte entfernt wurden.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/ff56adb1-ea0d-4d0a-bf0e-02b358a71c05.png" class="img-fluid rounded border" alt="OIDC Zuordnung und Domäne" /> <span class="figure-caption d-block">OIDC Zuordnung und Domäne</span> </span>
<figcaption>Betreff, Login, Anzeigename, Benutzerfelder und Domänenquelle, zugeordnet aus tatsächlichen Ansprüchen.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/0603f7ff-a5e7-4f13-a6e6-b7b144b992a5.png" class="img-fluid rounded border" alt="Betriebsprofil OIDC" /> <span class="figure-caption d-block">Betriebsprofil OIDC</span> </span>
<figcaption>Der wiederholte Test bestätigt OIDC und zeigt das grüne Häkchen an.</figcaption>
</figure>

Verwenden Sie einen erzwungenen Anmeldeparameter wie `max_age=0` nur, wenn der Anbieter dies unterstützt und **Anmeldung erzwingen** aktiviert ist. Andernfalls lassen Sie das Feld leer, um eine gültige vorhandene SSO-Sitzung beizubehalten.

<a id="oauth2"></a>

## 13. OAuth2

OAuth2 verwendet Autorisierungs-, Token- und Benutzerinfo-Endpunkte sowie die für Benutzerdaten erforderlichen Scopes. Wenn der Anbieter OpenID Connect unterstützt, bevorzugen Sie OIDC, da es die Identitäts- und Tokenvalidierung standardisiert. OAuth2 Für die Abmeldung ist möglicherweise ein dedizierter Endpunkt, ein Umleitungsparametername oder ein Hinweis erforderlich. Geben Sie nur Werte ein, die in der Anbieterdokumentation erforderlich sind.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/c8024292-d7ab-4926-82fc-3ae3135df40e.png" class="img-fluid rounded border" alt="Erstkonfiguration OAuth2" /> <span class="figure-caption d-block">Erstkonfiguration OAuth2</span> </span>
<figcaption>OAuth2 Profil vor der Attributzuordnung.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/5a358208-3572-4641-970a-278bf2b22082.png" class="img-fluid rounded border" alt="Erster OAuth2-Test" /> <span class="figure-caption d-block">Erster OAuth2-Test</span> </span>
<figcaption>Benutzerinfo-Attribute, die durch eine echte Anmeldung erfasst wurden, wobei die Werte entfernt wurden.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/0df4572c-6616-4522-939e-f825f70504d2.png" class="img-fluid rounded border" alt="OAuth2 Zuordnung und Domäne" /> <span class="figure-caption d-block">OAuth2 Zuordnung und Domäne</span> </span>
<figcaption>Aus dem Testergebnis eingetragene Zuordnungen und Domäneneinschränkungen.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/d3faabdb-f174-4a6b-9f00-45f56e11eb3b.png" class="img-fluid rounded border" alt="Betriebsprofil OAuth2" /> <span class="figure-caption d-block">Betriebsprofil OAuth2</span> </span>
<figcaption>Der wiederholte Test bestätigt das Profil OAuth2.</figcaption>
</figure>

<a id="personal-profile"></a>

<a id="14-personal-profile-security-and-access"></a>

## 14. Persönliches Profil: Sicherheit und Zugang

Öffnen Sie **Persönliches Profil** aus dem Benutzermenü. Der Hauptabschnitt zeigt Identität und benutzerdefinierte Attribute. **Sicherheit und Zugriff** zeigt Gruppenmitgliedschaft, lokalen Passwortstatus, verknüpfte externe Anmeldungen und API Schlüsselanforderungen an.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/52960239-b877-4349-bd2a-016f5837e8f7.png" class="img-fluid rounded border" alt="Persönliches Profil" /> <span class="figure-caption d-block">Persönliches Profil</span> </span>
<figcaption>Persönliche Daten und benutzerdefinierte Attribute in der englischen Benutzeroberfläche.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/12c871d9-5263-4a65-93f0-43ed0980d549.png" class="img-fluid rounded border" alt="Sicherheit und Zugriff erweitert" /> <span class="figure-caption d-block">Sicherheit und Zugriff erweitert</span> </span>
<figcaption>Gruppen, lokale Anmeldung, verknüpfte Anbieter und API-Anfragen an einem Ort.</figcaption>
</figure>

Verknüpfen Sie ein externes Konto nur, solange Sie beide Identitäten kontrollieren. Entfernen Sie unbekannte oder nicht verwendete Links, aber stellen Sie zunächst sicher, dass weiterhin eine andere Anmeldemethode verfügbar ist. Ändern Sie lokale Passwörter über dieses sichere Formular, niemals per E-Mail oder einer Administratornachricht.

<a id="verification"></a>

<a id="15-verification-and-security-checklist"></a>

## 15. Verifizierungs- und Sicherheitscheckliste

- Halten Sie mindestens einen kontrollierten Administrator-Login bereit; Bewahren Sie das Glasbruchkonto außerhalb des normalen täglichen Gebrauchs auf.
- Überprüfen Sie für jedes SSO-Profil die genauen Rückruf-URLs, TLS-Zertifikate, erforderliche Scopes und geheime Rotation.
- Akzeptieren Sie kein aktives Kontrollkästchen als Beweis. Erfordern den grünen Betriebszustand nach dem wiederholten Test.
- Der Betreff muss eine unveränderliche Anbieterkennung sein. Login und E-Mail können sich ändern und sind nicht immer geeignete primäre Identifikatoren.
- Testen Sie Domäneneinschränkungen mit einem zugelassenen und einem verweigerten Testkonto.
- Überprüfen Sie regelmäßig Administratoren, Gruppenmitgliedschaften, verknüpfte externe Anmeldungen, aktive API-Schlüssel und ausstehende Anfragen.
- Geben Sie niemals Passwörter, Client-Geheimnisse, Token, API-Geheimnisse oder rohe persönliche Attribute in Screenshots, Dokumentationen, Tickets oder in der Quellcodeverwaltung ein.
- Entfernen oder deaktivieren Sie nach dem Testen nicht mehr benötigte Dokumentationskonten und Schlüssel.

<div class="alert alert-success">

Testen Sie nach dem Speichern eine normale Anmeldung und ein abgelehntes Konto. Stellen Sie sicher, dass jeder Benutzer nur die Inhalte und Aktionen sieht, die seinen Gruppen zugewiesen sind.

</div>
