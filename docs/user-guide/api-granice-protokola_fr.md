# API — limites, CalDAV et ICS

<a id="api--boundaries-caldav-and-ics"></a>


> Les captures d’écran présentent l’interface en anglais.

[← API — contenu](api-koristenje_fr.md)

Ce chapitre distingue le REST public API des autres mécanismes d'intégration. OpenAPI répertorie les routes `/api/v1` enregistrées ; il ne s'agit pas d'un inventaire de chaque page Web ou protocole Simbioza.

<a id="features-without-a-separate-rest-endpoint"></a>

## Fonctionnalités sans point de terminaison REST distinct

- Le menu et le thème n'ont pas de paramètres globaux REST API ; des routes thématiques d’espace de travail privés existent.
- La sauvegarde n'a pas de sauvegarde de site/d'espace de travail REST ni de point de terminaison de restauration. L'exportation HTML ZIP est un format différent, pas une sauvegarde.
- ORM, Email et Accessibilité n’exposent aucune ressource REST publique distincte.
- Le commentaire n'a pas d'extension REST pour créer/supprimer des commentaires.
- La tâche API modifie l'état de la tâche existante ; les définitions appartiennent au contenu de l'éditeur.
- Le sondage API n'accepte pas les soumissions des participants et ne remplace pas la connexion externe.
- Le calendrier REST n’expose aucune invitation/réponse de participant ni approbation de réservation.
- Les routes administratives Web POST ne remplacent pas REST : elles utilisent des sessions, CSRF et leurs propres autorisations.

## CalDAV

CalDAV est un protocole distinct sous `/caldav`, utilisant HTTP Basic avec un identifiant de connexion local et un mot de passe local. Une clé de porteur API n'est pas un identifiant CalDAV. Utilisez HTTPS. Un utilisateur uniquement SSO a besoin d'un mot de passe local pour CalDAV ; sa configuration n'autorise pas elle-même la connexion Web locale. Voir [Connexion des clients de calendrier](kalendari-povezivanje_fr.md).

La synchronisation de base prend en charge les sondes OPTIONS/HEAD, PROPFIND pour racine/principal/collection, REPORT pour un calendrier et GET/PUT/DELETE pour `/{uid}.ics`. PUT/DELETE nécessitent un accès en écriture au calendrier actuel. Le nom du fichier objet suit son UID et doit être codé en URL. La prise en charge complète de CalDAV Scheduling/iTIP n’est pas implémentée ; les invitations à des réunions reçoivent une réponse via l'interface Web.

```sh
export CALDAV_LOGIN='your-local-login'
read -r -s CALDAV_PASSWORD
export CALDAV_PASSWORD
curl --silent --show-error --request PROPFIND \
  --user "$CALDAV_LOGIN:$CALDAV_PASSWORD" \
  --header "Depth: 1" \
  "$BASE_URL/caldav"
```

Résultat réel : HTTP 207, `application/xml; charset=utf-8` ; la liste XML comprend uniquement les calendriers accessibles. Sans authentification de base, ou avec une clé Bearer ordinaire, le même PROPFIND renvoie 401. Ces méthodes ont également été vérifiées avec de vraies requêtes curl :

| Méthode | Chemin | Résultat |
|---|---|---|
| OPTIONS | `/caldav` | 204 |
| GET | `/.well-known/caldav` | 301 |
| PROPFIND | `/caldav` | 207 |
| PUT | `/caldav/{calendarSlug}/api-protocol%40example.invalid.ics` | 201 |
| GET | `/caldav/{calendarSlug}/api-protocol%40example.invalid.ics` | 200 |
| REPORT | `/caldav/{calendarSlug}` | 207 |
| DELETE | `/caldav/{calendarSlug}/api-protocol%40example.invalid.ics` | 204 |

<a id="ics-links-and-export"></a>

## Liens ICS et exportation

REST `/api/v1/calendars/{calendarUuid}/export.ics` nécessite une clé Bearer et `calendar:read`. Les liens ICS d'abonnement public/privé depuis l'interface Web constituent un contrat distinct avec leurs propres règles d'accès ou jetons ; n'ajoutez pas de clé API à ces URL. Un abonnement ICS public est en lecture seule. Obtenez des liens d’abonnement à partir de votre propre calendrier au lieu de les créer à partir des URL de test d’une autre installation.
