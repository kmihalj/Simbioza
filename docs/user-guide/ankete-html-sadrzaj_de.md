# HTML-Inhalte und Sicherheit

<a id="html-content-and-safety"></a>


> Die Bildschirmaufnahmen zeigen die englische Benutzeroberfläche.

[Für Entwickler – API](ankete-developeri_de.md)

Integrierter HTML durchläuft denselben serverseitigen Sanitizer wie die Editoreingabe. Das Bearbeiten von Quellen stellt keine Ausnahme von der Sicherheitsrichtlinie dar.

<div class="table-responsive">

| Inhalt | Verhalten |
|----|----|
| Gewöhnlicher Text, Schwarz/Weiß, I/E, Überschriften, Listen, Tabellen und sichere Medien | Zulässig innerhalb der Editor-Richtlinie HTML. |
| Skript, Stil und Handler wie onclick/onerror | ENTFERNT; Verwenden Sie sie niemals für Umfragelogik. |
| javascript:, data: und ähnliche ausführbare URLs | Abgelehnt. |
| Nicht unterstützte Attribute und CSS | Kann entfernt werden, auch wenn keine Gefahr besteht. |
| Diagramme, Roadmaps, Tabs, Akkordeons, Dropdowns und interaktive Tabellen | Ihre unterstützten Editor-Markups und -Einstellungen bleiben erhalten und verwenden die gemeinsame Editor-Anzeige. |
| Kalender-, Aufgaben-, eingeschlossene Seiten- und Arbeitsbereichsblöcke | Sichere Referenzen und Einstellungen bleiben erhalten. Ohne einen Host-Dienst, der den Block auflöst, bleibt er ein inaktiver Platzhalter. |
| Iframe-Einbettungen | Nur von der gemeinsamen Editor-Richtlinie akzeptierte Einbettungen werden beibehalten, einschließlich ihrer erforderlichen Schutzeinstellungen. |

</div>

Verwenden Sie die vom Editor erstellten kanonischen Strukturen anstelle eines willkürlichen ausführbaren Markups. Anwenden und Speichern beibehaltener unterstützter Inhalte; die generierte Diagramm- oder Roadmap-Anzeige ersetzt nicht ihre bearbeitbare Quelle. Survey wandelt Skript-Registrierungsreferenzen nicht in ausführbare Skripts um und gewährt keinen Zugriff auf die Ziele dynamischer Blöcke. Geben Sie Fragebedingungen als unterstützte deklarative Definitionen an, nicht als JavaScript.

Beachten Sie bei der Integration von Systemen die Anhangsberechtigung. Ein Link gewährt keinen Zugriff auf private Inhalte. Behandeln Sie die Antworten der Teilnehmer immer als Daten und nicht als ausführbare Datei HTML.
