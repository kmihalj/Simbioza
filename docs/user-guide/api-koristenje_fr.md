# API — connecter d’autres systèmes

<a id="api--connecting-other-systems"></a>


> Les captures d’écran présentent l’interface en anglais.

Ce guide est destiné aux développeurs connectant leur application à Simbioza. L’API fonctionne avec les utilisateurs, le contenu, les espaces de travail, les calendriers, les sondages et les données personnelles déjà autorisées au propriétaire de la clé. Il ne remplace pas l'administration de l'installation ou la connexion des participants aux sondages.

<a id="getting-started"></a>

## Commencer

1. Un administrateur installe/active API et émet une clé avec les étendues requises.
2. Utilisez [Mise en route](api-pocetak_fr.md) pour définir l'adresse et lire `/api/v1/me`.
3. Vérifiez les ressources disponibles de votre installation et OpenAPI.
4. Ouvrez le chapitre des ressources et suivez son exemple de création, de lecture, de mise à jour ou d'exportation.
5. Gérez également les erreurs : la clé ne donne pas accès au contenu privé d’un autre utilisateur.

<a id="chapters"></a>

## Chapitres

| Chapitre | Opérations vérifiées |
|---|---|
| [API — démarrage et règles partagées](api-pocetak_fr.md) | 3 |
| [API — utilisateurs et groupes](api-korisnici-grupe_fr.md) | 11 |
| [API — espaces de travail, arborescences, autorisations et thèmes](api-podrucja_fr.md) | 30 |
| [API — HTML pages, brouillons et pièces jointes](api-html-stranice_fr.md) | 22 |
| [API — calendriers et événements](api-kalendari_fr.md) | 13 |
| [API — sondages et résultats](api-ankete_fr.md) | 18 |
| [API — tâches dans le contenu](api-zadaci_fr.md) | 4 |
| [API — notifications et suivis personnels](api-obavijesti-pracenja_fr.md) | 11 |
| [API — webhooks et livraisons](api-webhookovi_fr.md) | 9 |
| [API — audit et recherche](api-audit-pretraga_fr.md) | 2 |
| [limites API, CalDAV et ICS](api-granice-protokola_fr.md) | — |

Ce manuel couvre les **114 routes REST enregistrées** dans une installation complète. La route d'action Survey nommée prend en charge 10 opérations, ce qui donne **123 opérations vérifiées séparément**. Une installation modulaire expose uniquement les ressources dont les modules sont installés et actifs. CORS `OPTIONS` et le protocole distinct CalDAV sont en dehors de ce total.

Chaque exemple indique la méthode, le chemin, les autorisations, les paramètres, la commande curl, la réponse réelle et les codes d'erreur vérifiés. L'adresse est remplacée par votre espace réservé d'installation ; les clés et les secrets ne sont pas publiés. Les réponses utilisent des données synthétiques, de sorte que les noms, identifiants et horodatages diffèrent des vôtres. Certaines réponses volumineuses utilisent une projection `jq` explicite ; le JSON affiché est alors la sortie réelle de ce filtre.

[Administrateur API-gestion des clés](admin-api_fr.md). [Règles partagées, erreurs et un client PHP complet](api-pocetak_fr.md).
