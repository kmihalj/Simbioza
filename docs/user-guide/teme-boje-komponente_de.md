# Farben festlegen und Komponenten prüfen

<a id="set-colours-and-check-components"></a>


> Die Bildschirmaufnahmen zeigen die englische Benutzeroberfläche.

[Designs](teme_de.md)

**Ziel:** Text, Bedienelemente und Nachrichten im Hell- und Dunkelmodus klar verständlich halten. Sie müssen nicht jedes Feld ändern: Verwenden Sie die Gruppe, die dem entspricht, was Sie anpassen möchten.

<a id="change-one-colour"></a>

## Ändern Sie eine Farbe

1. Öffnen Sie den entsprechenden Abschnitt und aktivieren Sie **Helle Variante** oder **Dunkle Variante**.
2. Verwenden Sie den Farbwähler oder geben Sie den angebotenen HEX-Wert ein, z. B. `#276E82`.
3. Überprüfen Sie diese Komponente und ihre Zustände in der Vorschau unter dem Editor.
4. Überprüfen Sie die andere Variante und wählen Sie **Design speichern**.

**Ergebnis:** Die gespeicherte Farbe ändert sich zu diesem Zweck, ohne die andere Variante zu überschreiben. Eine primäre Schaltflächenfarbe legt nicht alle Schaltflächen-, Tabellen- und Nachrichtenfarben fest.

<a id="base-colours"></a>

## Grundfarben

| Feldgruppe | Gilt für |
| --- | --- |
| Seitenhintergrund und Text | Haupthintergrund und Haupttext. |
| Stummgeschalteter Text | Unterstützende Angaben, die trotz reduzierter Hervorhebung lesbar bleiben müssen. |
| Oberflächenhintergrund und Text | Allgemeine Bauteiloberflächen. |
| Rand und Link | Elementgrenzen und Links. |

<figure>
<img src="images/theme-guide-17-en.jpg" class="img-fluid rounded border" alt="Helle und dunkle Grundfarben des Designs" />
<figcaption>Vergleichen Sie den Textkörper und den stummgeschalteten Text mit dem tatsächlichen Hintergrund.</figcaption>
</figure>

<a id="buttons-and-document-actions"></a>

## Schaltflächen und Dokumentaktionen

Primär-, Sekundär-, Erfolgs-, Warn-, Gefahren- und Informationsfarben dienen unterschiedlichen Zwecken. Der Text für jeden der sechs Schaltflächentypen verfügt in beiden Varianten über einen eigenen Selektor:

| Textfarbmodus | Wirkung |
| --- | --- |
| Automatisch | Bestimmt die Textfarbe anhand des ausgewählten Schaltflächenhintergrunds. |
| Handbuch | Ermöglicht Ihre eigene Farbe; überprüfen Sie die Lesbarkeit erneut, nachdem Sie den Hintergrund geändert haben. |

**Dokumentaktionen** verfügen über separate Hintergrund-, Symbol-/Text-, Rahmen-, Hover- und Aktivzustandsfelder. Hierbei handelt es sich um Steuerelemente wie Symbole neben einem Dokument. Sie müssen nicht die gleichen Farben wie eine große Primärschaltfläche haben.

<figure>
<img src="images/theme-guide-18-en.jpg" class="img-fluid rounded border" alt="Schaltflächenfarben mit automatischer oder manueller Textfarbe" />
<figcaption>Überprüfen Sie die gefüllten und umrandeten Schaltflächen, einschließlich ihres Normal- und Hover-Status.</figcaption>
</figure>

<figure>
<img src="images/theme-guide-19-en.jpg" class="img-fluid rounded border" alt="Farben für Dokumentaktionen und Schaltflächenzustände" />
<figcaption>Dokumentsteuerelemente verfügen über Farbeinstellungen, die von großen Aktionsschaltflächen getrennt sind.</figcaption>
</figure>

<a id="cards-and-tables"></a>

## Karten und Tische

- **Karten** haben Hintergrund-, Text-, Rahmen-, Akzent- und Schattenfarben.
- **Titel-, Inhalts- und Seitenkarten** verfügen über separate Hintergrund-, Text-, Rahmen- und Akzentfarben.
- **Tabellen** haben Kopfzeilenhintergrund/-text, reguläre Zeilenhintergrund/-text, alternative Zeilenhintergrund und Rahmen.

Form, Schattensichtbarkeit und Akzentposition werden unter [Inhalt und Karten](teme-sadrzaj-kartice_de.md) festgelegt. Durch die Auswahl einer Schattenfarbe wird kein deaktivierter Schatten aktiviert.

<figure>
<img src="images/theme-guide-20-en.jpg" class="img-fluid rounded border" alt="Farben für verschiedene Kartenoberflächen und Tische" />
<figcaption>Getrennte Oberflächen ermöglichen einen ruhigeren Titel, klare Inhalte und lesbare Seitenkarten.</figcaption>
</figure>

<a id="messages-labels-and-statuses"></a>

## Nachrichten, Labels und Status

Primär-, Erfolgs-, Warn-, Gefahren- und Informationsmeldungen haben Hintergrund-, Text- und Rahmenfarben. **Badge**- und **Tag**-Etiketten haben ihren eigenen Hintergrund und Text. Erfolg und Warnung sollten auch durch Text oder Symbole und nicht nur durch Farbe verständlich sein.

<figure>
<img src="images/theme-guide-21-en.jpg" class="img-fluid rounded border" alt="Feedback-Nachricht und Small-Label-Farben" />
<figcaption>Wählen Sie Farben, die für jeden Nachrichtentyp unterschiedlich, aber lesbar sind.</figcaption>
</figure>

<a id="forms-and-content-blocks"></a>

## Formulare und Inhaltsblöcke

| Gruppe | Was zu überprüfen ist |
| --- | --- |
| Fokus, deaktivierte Felder, gültige/ungültige Zustände | Der Tastaturfokus bleibt sichtbar; Deaktivierte Text- und Fehlermeldungen bleiben lesbar. |
| Code, Angebot und Callout | Code-Hintergrund/Text, Zitatrahmen und Callout-Hintergrund/Text/Rahmen. |
| Inhaltsregisterkarten des Editors | Standard-, Hover- und Aktivstatus, Rahmen, Akzent und Panel-Hintergrund/Text. |
| Herausgeber Akkordeon | Separate Überschriften- und Texthintergründe/Text. Öffnen und schließen Sie das Beispiel. |

<figure>
<img src="images/theme-guide-22-en.jpg" class="img-fluid rounded border" alt="Formularzustände und Inhaltsblockfarben" />
<figcaption>Die Lesbarkeit umfasst auch deaktivierte Felder, Fehler und den Tastaturfokus.</figcaption>
</figure>

<figure>
<img src="images/theme-guide-23-en.jpg" class="img-fluid rounded border" alt="Editor-Inhaltsregisterkarte und Akkordeonfarben" />
<figcaption>Inhaltsregisterkarten und die Hauptnavigation verwenden nicht unbedingt dieselbe Farbgruppe.</figcaption>
</figure>

<a id="final-check"></a>

## Endkontrolle

Die Vorschau umfasst Schaltflächen, Karten, eine Tabelle, Nachrichten, Registerkarten, Akkordeon, Code, einen Dialog, ein Dropdown-Menü und kurze Benachrichtigungen. Vergleichen Sie helle und dunkle Seiten und öffnen Sie dann echte Seiten mit einem Formular und längerem Inhalt. Wenn ein Text schwer lesbar ist, passen Sie seine Farbe oder die entsprechende Oberfläche an, anstatt den gesamten Hero-Bereich für eine Beschreibung zu ändern.
