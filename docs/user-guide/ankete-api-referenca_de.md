# API – Routen, Scopes und Fehler

<a id="api--routes-scopes-and-errors"></a>


> Die Bildschirmaufnahmen zeigen die englische Benutzeroberfläche.

[Für Entwickler – API](ankete-developeri_de.md)

Der Basispfad ist `/api/v1/surveys`, ohne abschließenden Schrägstrich. Stellen Sie ggf. das Installationsunterverzeichnis voran. Jede Anfrage benötigt einen gültigen Schlüssel, den erforderlichen Umfang und die aktuelle Domänenberechtigung des Eigentümers.

<div class="table-responsive">

| Methoden- und Pfadsuffix                                | Umfang           |
|-------------------------------------------------------|-----------------|
| GET /, GET /{survey}                                  | surveys:read    |
| POST /, PATCH /{survey}                               | surveys:write   |
| POST /{survey}/submit-review, withdraw, copy, delete, discard-draft | surveys:write |
| POST /{survey}/publish, return-review, close, archive, restore | surveys:publish |
| GET /{survey}/results, responses                      | surveys:results |
| POST /{survey}/exports; GET /{survey}/exports/{token} | surveys:export  |

</div>

Übergänge verwenden `version`, `revision` und explizit `confirmed`. Beim Kopieren wird ein neuer Titel übernommen. Ändern Sie die vorhandene Struktur nicht, nachdem Antworten vorhanden sind: Kopieren Sie die Umfrage.

Die Auflistung unterstützt `q`, `state`, `page` und `per_page`. Die Ergebnisse akzeptieren Sprache und Paginierung. Manager können auch inklusive `from`/`to`-Berichtstage filtern. Einzelne Einreichungen bedürfen der Verwaltung; die Berechtigung zum Anzeigen von Diagrammen reicht nicht aus.

Exporte akzeptieren `mode` (aggregate/responses), `lang` und explizit `include_identity`. Das zurückgegebene Token läuft ab und bleibt auf denselben autorisierten Ersteller beschränkt. Es handelt sich nicht um einen öffentlichen Link oder ein Backup.

<div class="table-responsive">

| Status | Aktion |
|----|----|
| 401 / 403 | Überprüfen Sie den Schlüssel, den Umfang, das aktive Konto und die aktuellen Berechtigungen. |
| 404 | Die Ressource ist nicht verfügbar oder ein erforderliches Modul ist deaktiviert; Umgehen Sie die Zugangskontrollen nicht. |
| 409 | Lesen Sie die aktuelle Version erneut und gleichen Sie die Änderung ab. |
| 422 | Korrigieren Sie die Definition, Sprache oder Filter mithilfe der Antwortnachricht. |

</div>

Erfolgsantworten verwenden `data`, `meta` und `links`. Anforderungskennungen helfen bei der Diagnose. Protokollieren Sie Antworten oder Schlüssel nicht öffentlich. Es gibt keine reine Token-Einreichung API, die als Teilnehmer fungiert; die normale Anmeldung, Validierung und der Wiederholungsschutz gelten weiterhin.

<a id="removal-and-recovery-through-the-api"></a>

## Entfernung und Wiederherstellung durch API

`delete` verschiebt die gesamte Umfrage in den Papierkorb und behält die Antworten bei. `discard-draft` verwirft einen Arbeitsentwurf; eine bestehende Publikation bleibt unverändert. Beide erfordern den aktuellen Wert `version`, `revision` und den booleschen Wert `confirmed: true`. `restore` erfordert zusätzlich zum oben genannten Schlüssel-Scope `version`, `confirmed: true` und einen Administrator. Eine frühere Veröffentlichung wird geschlossen zurückgegeben. Die Antwort umfasst den Vorgang UUID und `deleted`, der angibt, ob sich die Umfrage jetzt im Papierkorb befindet. HTTP 409 bedeutet, den aktuellen Status erneut zu lesen, bevor eine erneute Entscheidung getroffen wird.
