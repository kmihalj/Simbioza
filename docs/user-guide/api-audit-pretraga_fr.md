# API — audit et recherche

<a id="api--audit-and-search"></a>


> Les captures d’écran présentent l’interface en anglais.

[← API — contenu](api-koristenje_fr.md)

L'audit nécessite un propriétaire de clé d'administrateur et `audit:read`. Les filtres sont `event_key`, `module`, `action`, `target`, `channel`, `outcome`, `created_from`, `created_to`, ainsi que les paramètres du curseur. Il lit les événements d'application enregistrés, et non les fichiers journaux arbitraires du serveur.

La recherche nécessite `workspace-search:read` et filtre les pages publiées en fonction des autorisations utilisateur actuelles. `q` est le terme de recherche et `lang` la langue du contenu. Les entrées supplémentaires sont `page`, `per_page`, `workspace` ou `workspaces[]`, `embedded`, `author`, `from`, `to`, `sort`, `direction`. Il ne divulgue pas les brouillons ou les espaces de travail privés inaccessibles. Les résultats incluent des métadonnées de recherche et une pagination numérotée plutôt que des curseurs.

<a id="operations-and-verified-examples"></a>

## Opérations et exemples vérifiés

### `GET /api/v1/audit`

**Objectif :** renvoie les données d'audit filtrées uniquement à un propriétaire de clé d'administrateur ayant l'étendue.

**Autorisation :** `audit:read`. Propriétaire de la clé administrateur.

**Paramètres de requête :** `event_key`, `module`, `action`, `actor_user_id`, `workspace_id`, `page_id`, `target`, `channel`, `outcome`, `created_from`, `created_to`, `page[limit]`, `page[after]`.

**Corps :** aucun champ de saisie supplémentaire ; suivez l'exemple.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/audit?page[limit]=2&outcome=success" | jq '{data: [.data[] | {id,occurred_at,event_key,module,action,outcome,actor_user_id,target_type,target_id}], meta, links}'
```

Réponse réelle : **HTTP 200**. Il s'agit de la sortie réelle du filtre `jq` affiché.

```json
{
  "data": [
    {
      "id": 596,
      "occurred_at": "2026-11-06 22:29:19",
      "event_key": "auth.view",
      "module": "auth",
      "action": "view",
      "outcome": "success",
      "actor_user_id": 1,
      "target_type": "auth_route",
      "target_id": "me"
    },
    {
      "id": 593,
      "occurred_at": "2026-11-06 22:29:04",
      "event_key": "auth.view",
      "module": "auth",
      "action": "view",
      "outcome": "success",
      "actor_user_id": 1,
      "target_type": "auth_route",
      "target_id": "me"
    }
  ],
  "meta": {
    "request_id": "e9315ac5019849067405f78e0e7c888c",
    "page": {
      "limit": 2,
      "total": 269,
      "has_more": true,
      "next_cursor": "eyJvZmZzZXQiOjJ9"
    }
  },
  "links": {
    "self": "/api/v1/audit?page%5Blimit%5D=2&outcome=success",
    "next": "/api/v1/audit?page%5Blimit%5D=2&page%5Bafter%5D=eyJvZmZzZXQiOjJ9&outcome=success"
  }
}
```

**Erreurs vérifiées pour cette opération :** 401, 403. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `GET /api/v1/workspace-search`

**Objectif :** Recherche uniquement les pages visibles par l'utilisateur réel de la clé API.

**Autorisation :** `workspace-search:read`. Seul le contenu publié actuellement visible par le propriétaire de la clé.

**Paramètres de requête :** `q`, `lang`, `page`, `per_page`, `workspace`, `workspaces`, `embedded`, `author`, `from`, `to`, `sort`, `direction`.

**Corps :** aucun champ de saisie supplémentaire ; suivez l'exemple.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/workspace-search?q=API&lang=hr&page[limit]=5"
```

Réponse réelle : **HTTP 200**.

```json
{
  "data": [
    {
      "result_type": "workspace",
      "workspace_id": 4,
      "workspace_slug": "api-ogledno-podrucje",
      "workspace_name": "Ogledno API područje",
      "node_id": 0,
      "node_slug": "",
      "language": "",
      "title": "Ogledno API područje",
      "snippet": "Ažurirano preko API-ja.",
      "snippet_html": "Ažurirano preko <mark>API</mark>-ja.",
      "author_user_id": 0,
      "author_name": "",
      "published_at": "",
      "modified_by_user_id": 0,
      "modified_by_name": "",
      "modified_at": "",
      "url": "/workspace/api-ogledno-podrucje"
    },
    {
      "result_type": "page",
      "workspace_id": 4,
      "workspace_slug": "api-ogledno-podrucje",
      "workspace_name": "Ogledno API područje",
      "node_id": 36,
      "node_slug": "api-plan-radionice",
      "language": "en",
      "title": "Plan API radionice",
      "snippet": "Plan radionice Objasnimo cjelokupni API. Priprema radionice Pregledati upute Provjeriti primjer",
      "snippet_html": "Plan radionice Objasnimo cjelokupni <mark>API</mark>. Priprema radionice Pregledati upute Provjeriti primjer",
      "author_user_id": 1,
      "author_name": "Simbioza Admin",
      "published_at": "2026-11-06 22:44:20",
      "modified_by_user_id": 1,
      "modified_by_name": "Simbioza Admin",
      "modified_at": "2026-11-06 22:49:03",
      "url": "/workspace/api-ogledno-podrucje/api-plan-radionice"
    },
    {
      "result_type": "page",
      "workspace_id": 1,
      "workspace_slug": "korisnicke-upute",
      "workspace_name": "User guides",
      "node_id": 11,
      "node_slug": "ankete-api-php",
      "language": "en",
      "title": "API — PHP examples",
      "snippet": "For developers — API Goal: read and create surveys and retrieve permitted results from your own PHP application. This is a separate PHP example; command-line curl examples remain in their own chapter. 1. Prepare PHP and a key You need PHP 8.1 or later and the cURL extension. No Composer package or Simbioza framework bo …",
      "snippet_html": "For developers — <mark>API</mark> Goal: read and create surveys and retrieve permitted results from your own PHP application. This is a separate PHP example; command-line curl examples remain in their own chapter. 1. Prepare PHP and a key You need PHP 8.1 or later and the cURL extension. No Composer package or Simbioza framework bo …",
      "author_user_id": 1,
      "author_name": "Simbioza Admin",
      "published_at": "2026-10-27 12:57:05",
      "modified_by_user_id": 1,
      "modified_by_name": "Simbioza Admin",
      "modified_at": "2026-10-27 13:00:02",
      "url": "/workspace/korisnicke-upute/ankete-api-php"
    },
    {
      "result_type": "page",
      "workspace_id": 1,
      "workspace_slug": "korisnicke-upute",
      "workspace_name": "User guides",
      "node_id": 12,
      "node_slug": "ankete-api",
      "language": "en",
      "title": "API — cURL examples",
      "snippet": "For developers — API Preparation The administrator enables API and issues a key whose owner has survey permissions. The full example uses surveys:read, surveys:write, surveys:publish and surveys:results. Add surveys:export only for CSV. Grant only necessary scopes to a narrower integration. You need curl and Python 3. …",
      "snippet_html": "For developers — <mark>API</mark> Preparation The administrator enables <mark>API</mark> and issues a key whose owner has survey permissions. The full example uses surveys:read, surveys:write, surveys:publish and surveys:results. Add surveys:export only for CSV. Grant only necessary scopes to a narrower integration. You need curl and Python 3. …",
      "author_user_id": 1,
      "author_name": "Simbioza Admin",
      "published_at": "2026-10-27 09:43:15",
      "modified_by_user_id": 1,
      "modified_by_name": "Simbioza Admin",
      "modified_at": "2026-10-27 12:57:16",
      "url": "/workspace/korisnicke-upute/ankete-api"
    },
    {
      "result_type": "page",
      "workspace_id": 1,
      "workspace_slug": "korisnicke-upute",
      "workspace_name": "User guides",
      "node_id": 13,
      "node_slug": "ankete-api-referenca",
      "language": "en",
      "title": "API — routes, scopes and errors",
      "snippet": "For developers — API The base path is /api/v1/surveys, without a trailing slash. Prefix it with the installation subdirectory where applicable. Every request needs a valid key, its required scope and the owner's current domain permission. Method and path suffix Scope GET /, GET /{survey} surveys:read POST /, PATCH /{su …",
      "snippet_html": "For developers — <mark>API</mark> The base path is /<mark>api</mark>/v1/surveys, without a trailing slash. Prefix it with the installation subdirectory where applicable. Every request needs a valid key, its required scope and the owner&#039;s current domain permission. Method and path suffix Scope GET /, GET /{survey} surveys:read POST /, PATCH /{su …",
      "author_user_id": 1,
      "author_name": "Simbioza Admin",
      "published_at": "2026-10-27 09:43:15",
      "modified_by_user_id": 1,
      "modified_by_name": "Simbioza Admin",
      "modified_at": "2026-10-27 11:30:35",
      "url": "/workspace/korisnicke-upute/ankete-api-referenca"
    },
    {
      "result_type": "page",
      "workspace_id": 1,
      "workspace_slug": "korisnicke-upute",
      "workspace_name": "User guides",
      "node_id": 14,
      "node_slug": "ankete-api-primjeri",
      "language": "en",
      "title": "API — template and examples",
      "snippet": "For developers — API These are illustrative examples without real keys or links to existing surveys. Copy only the relevant block contents into the named file. api-feedback-template.json The template uses Croatian and English, both of which must be installed on the target. Adjust languages and text before sending. { \"v …",
      "snippet_html": "For developers — <mark>API</mark> These are illustrative examples without real keys or links to existing surveys. Copy only the relevant block contents into the named file. <mark>api</mark>-feedback-template.json The template uses Croatian and English, both of which must be installed on the target. Adjust languages and text before sending. { &quot;v …",
      "author_user_id": 1,
      "author_name": "Simbioza Admin",
      "published_at": "2026-10-27 09:43:15",
      "modified_by_user_id": 1,
      "modified_by_name": "Simbioza Admin",
      "modified_at": "2026-10-27 11:13:22",
      "url": "/workspace/korisnicke-upute/ankete-api-primjeri"
    },
    {
      "result_type": "page",
      "workspace_id": 1,
      "workspace_slug": "korisnicke-upute",
      "workspace_name": "User guides",
      "node_id": 10,
      "node_slug": "ankete-developeri",
      "language": "en",
      "title": "For developers — API",
      "snippet": "Surveys This section is for integrating other systems with Simbioza. The API is not required for ordinary authoring or participation. API — cURL examples API — PHP examples for your application Routes, scopes and errors Definition template and response examples HTML content and the security boundary Examples use the pl …",
      "snippet_html": "Surveys This section is for integrating other systems with Simbioza. The <mark>API</mark> is not required for ordinary authoring or participation. <mark>API</mark> — cURL examples <mark>API</mark> — PHP examples for your application Routes, scopes and errors Definition template and response examples HTML content and the security boundary Examples use the pl …",
      "author_user_id": 1,
      "author_name": "Simbioza Admin",
      "published_at": "2026-10-27 09:43:15",
      "modified_by_user_id": 1,
      "modified_by_name": "Simbioza Admin",
      "modified_at": "2026-10-27 12:57:03",
      "url": "/workspace/korisnicke-upute/ankete-developeri"
    },
    {
      "result_type": "page",
      "workspace_id": 1,
      "workspace_slug": "korisnicke-upute",
      "workspace_name": "User guides",
      "node_id": 15,
      "node_slug": "ankete-html-sadrzaj",
      "language": "en",
      "title": "HTML content and safety",
      "snippet": "For developers — API Integrated HTML passes through the same server-side sanitizer as editor input. Source editing is not an exception to the security policy. Content Behaviour Ordinary text, b/strong, i/em, headings, lists, tables and safe media Allowed within HTML Editor policy. script, style and handlers such as onc …",
      "snippet_html": "For developers — <mark>API</mark> Integrated HTML passes through the same server-side sanitizer as editor input. Source editing is not an exception to the security policy. Content Behaviour Ordinary text, b/strong, i/em, headings, lists, tables and safe media Allowed within HTML Editor policy. script, style and handlers such as onc …",
      "author_user_id": 1,
      "author_name": "Simbioza Admin",
      "published_at": "2026-10-27 09:43:15",
      "modified_by_user_id": 1,
      "modified_by_name": "Simbioza Admin",
      "modified_at": "2026-10-27 11:30:43",
      "url": "/workspace/korisnicke-upute/ankete-html-sadrzaj"
    },
    {
      "result_type": "page",
      "workspace_id": 1,
      "workspace_slug": "korisnicke-upute",
      "workspace_name": "User guides",
      "node_id": 9,
      "node_slug": "ankete",
      "language": "en",
      "title": "Surveys",
      "snippet": "… s, sign-in methods and maintenance For administrators Connect another system to Simbioza For developers — API Screenshots show examples. Survey names on your installation will be different. You do not need to install sample surveys to use these guides.",
      "snippet_html": "… s, sign-in methods and maintenance For administrators Connect another system to Simbioza For developers — <mark>API</mark> Screenshots show examples. Survey names on your installation will be different. You do not need to install sample surveys to use these guides.",
      "author_user_id": 1,
      "author_name": "Simbioza Admin",
      "published_at": "2026-10-27 09:43:15",
      "modified_by_user_id": 1,
      "modified_by_name": "Simbioza Admin",
      "modified_at": "2026-10-27 11:29:33",
      "url": "/workspace/korisnicke-upute/ankete"
    },
    {
      "result_type": "page",
      "workspace_id": 1,
      "workspace_slug": "korisnicke-upute",
      "workspace_name": "User guides",
      "node_id": 3,
      "node_slug": "php-fpm-instalacija",
      "language": "en",
      "title": "PHP-FPM installation",
      "snippet": "… ER\":rwx,d:u:www-data:rwx config for SIMBIOZA_MODULE in auth email editor-html workspace menu theme backup api calendar; do mkdir -p \"config/$SIMBIOZA_MODULE\" sudo chgrp www-data \"config/$SIMBIOZA_MODULE\" sudo chmod 3770 \"config/$SIMBIOZA_MODULE\" sudo setfacl -m u:www-data:rwx,d:u:\"$SIMBIOZA_MAINTAINER\":rwx,d:u:www-data …",
      "snippet_html": "… ER&quot;:rwx,d:u:www-data:rwx config for SIMBIOZA_MODULE in auth email editor-html workspace menu theme backup <mark>api</mark> calendar; do mkdir -p &quot;config/$SIMBIOZA_MODULE&quot; sudo chgrp www-data &quot;config/$SIMBIOZA_MODULE&quot; sudo chmod 3770 &quot;config/$SIMBIOZA_MODULE&quot; sudo setfacl -m u:www-data:rwx,d:u:&quot;$SIMBIOZA_MAINTAINER&quot;:rwx,d:u:www-data …",
      "author_user_id": 1,
      "author_name": "Simbioza Admin",
      "published_at": "2026-09-28 18:28:26",
      "modified_by_user_id": 1,
      "modified_by_name": "Simbioza Admin",
      "modified_at": "2026-09-30 11:26:44",
      "url": "/workspace/korisnicke-upute/php-fpm-instalacija"
    },
    {
      "result_type": "page",
      "workspace_id": 1,
      "workspace_slug": "korisnicke-upute",
      "workspace_name": "User guides",
      "node_id": 16,
      "node_slug": "apache-mod-php-instalacija",
      "language": "en",
      "title": "Apache mod_php installation",
      "snippet": "… ER\":rwx,d:u:www-data:rwx config for SIMBIOZA_MODULE in auth email editor-html workspace menu theme backup api calendar; do mkdir -p \"config/$SIMBIOZA_MODULE\" sudo chgrp www-data \"config/$SIMBIOZA_MODULE\" sudo chmod 3770 \"config/$SIMBIOZA_MODULE\" sudo setfacl -m u:www-data:rwx,d:u:\"$SIMBIOZA_MAINTAINER\":rwx,d:u:www-data …",
      "snippet_html": "… ER&quot;:rwx,d:u:www-data:rwx config for SIMBIOZA_MODULE in auth email editor-html workspace menu theme backup <mark>api</mark> calendar; do mkdir -p &quot;config/$SIMBIOZA_MODULE&quot; sudo chgrp www-data &quot;config/$SIMBIOZA_MODULE&quot; sudo chmod 3770 &quot;config/$SIMBIOZA_MODULE&quot; sudo setfacl -m u:www-data:rwx,d:u:&quot;$SIMBIOZA_MAINTAINER&quot;:rwx,d:u:www-data …",
      "author_user_id": 1,
      "author_name": "Simbioza Admin",
      "published_at": "2026-09-28 18:27:36",
      "modified_by_user_id": 1,
      "modified_by_name": "Simbioza Admin",
      "modified_at": "2026-09-30 11:26:44",
      "url": "/workspace/korisnicke-upute/apache-mod-php-instalacija"
    },
    {
      "result_type": "page",
      "workspace_id": 1,
      "workspace_slug": "korisnicke-upute",
      "workspace_name": "User guides",
      "node_id": 2,
      "node_slug": "instalacija",
      "language": "en",
      "title": "Installation",
      "snippet": "… for introductions and explanations; the Wiki also needs the Editor, and Simbioza User adds notifications. API, accessibility and other optional integrations do not require unrelated business features. Install the selected tool Prepare a new installation using the appropriate installation guide. In the wizard's applicat …",
      "snippet_html": "… for introductions and explanations; the Wiki also needs the Editor, and Simbioza User adds notifications. <mark>API</mark>, accessibility and other optional integrations do not require unrelated business features. Install the selected tool Prepare a new installation using the appropriate installation guide. In the wizard&#039;s applicat …",
      "author_user_id": 1,
      "author_name": "Simbioza Admin",
      "published_at": "2026-09-28 18:05:32",
      "modified_by_user_id": 1,
      "modified_by_name": "Simbioza Admin",
      "modified_at": "2026-09-30 11:40:48",
      "url": "/workspace/korisnicke-upute/instalacija"
    },
    {
      "result_type": "page",
      "workspace_id": 1,
      "workspace_slug": "korisnicke-upute",
      "workspace_name": "User guides",
      "node_id": 1,
      "node_slug": "simbioza",
      "language": "en",
      "title": "Simbioza",
      "snippet": "… nt your team uses. Administration and integrations: manage access, appearance, backups, activity logs and API connections in the same environment. Access to content depends on user and group permissions. Different tools work together while private content remains protected. An additional option when you need fewer feat …",
      "snippet_html": "… nt your team uses. Administration and integrations: manage access, appearance, backups, activity logs and <mark>API</mark> connections in the same environment. Access to content depends on user and group permissions. Different tools work together while private content remains protected. An additional option when you need fewer feat …",
      "author_user_id": 1,
      "author_name": "Simbioza Admin",
      "published_at": "2026-08-27 12:34:03",
      "modified_by_user_id": 1,
      "modified_by_name": "Simbioza Admin",
      "modified_at": "2026-08-31 14:53:09",
      "url": "/workspace/korisnicke-upute/simbioza"
    },
    {
      "result_type": "page",
      "workspace_id": 1,
      "workspace_slug": "korisnicke-upute",
      "workspace_name": "User guides",
      "node_id": 6,
      "node_slug": "podrucja",
      "language": "en",
      "title": "Workspaces",
      "snippet": "… installation. The local accounts, groups and content are fictional. Screenshots do not contain passwords, API keys, encryption passphrases or other secrets. Contents Workspace, page and slug Global Workspace settings Creating and managing a Workspace Permissions and access model Tree, contents, page settings and specia …",
      "snippet_html": "… installation. The local accounts, groups and content are fictional. Screenshots do not contain passwords, <mark>API</mark> keys, encryption passphrases or other secrets. Contents Workspace, page and slug Global Workspace settings Creating and managing a Workspace Permissions and access model Tree, contents, page settings and specia …",
      "author_user_id": 1,
      "author_name": "Simbioza Admin",
      "published_at": "2026-08-27 01:14:56",
      "modified_by_user_id": 1,
      "modified_by_name": "Simbioza Admin",
      "modified_at": "2026-08-27 15:43:37",
      "url": "/workspace/korisnicke-upute/podrucja"
    },
    {
      "result_type": "page",
      "workspace_id": 1,
      "workspace_slug": "korisnicke-upute",
      "workspace_name": "User guides",
      "node_id": 4,
      "node_slug": "prijava-i-korisnici",
      "language": "en",
      "title": "Login and users",
      "snippet": "… ator through every login setting, user attribute, group, user account, local registration, impersonation, API key, SSO provider, and personal security and access screen. The examples were created on a clean SQLite installation. Accounts, groups, and keys shown here exist only for documentation. Secrets and personal att …",
      "snippet_html": "… ator through every login setting, user attribute, group, user account, local registration, impersonation, <mark>API</mark> key, SSO provider, and personal security and access screen. The examples were created on a clean SQLite installation. Accounts, groups, and keys shown here exist only for documentation. Secrets and personal att …",
      "author_user_id": 1,
      "author_name": "Simbioza Admin",
      "published_at": "2026-08-26 11:40:36",
      "modified_by_user_id": 1,
      "modified_by_name": "Simbioza Admin",
      "modified_at": "2026-08-31 10:48:54",
      "url": "/workspace/korisnicke-upute/prijava-i-korisnici"
    }
  ],
  "meta": {
    "request_id": "be2ed89a27787af9ecbe9c12d4c6b8fb",
    "query": "API",
    "language": "hr",
    "default_language": "en",
    "page": 1,
    "per_page": 20,
    "total": 15,
    "pages": 1,
    "filters": {
      "workspace": "",
      "embedded": "",
      "author": "",
      "from": "",
      "to": "",
      "sort": "title",
      "direction": "asc"
    }
  },
  "links": {
    "self": "/api/v1/workspace-search?q=API&lang=hr&page%5Blimit%5D=5"
  }
}
```

**Erreurs vérifiées pour cette opération :** 401, 403. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.
