# Installer Simbioza avec PHP-FPM sécurisé

<a id="install-simbioza-with-secured-php-fpm"></a>


> Les captures d’écran présentent l’interface en anglais.

[Version croate](installation_fpm_secured_hr.md)

Ce guide complet part d'un répertoire d'application vide et suppose qu'aucun compte Simbioza FPM/deploy n'existe encore. Vous n'avez pas besoin d'un autre guide d'installation. L'outil de configuration crée des comptes dédiés, des groupes, un pool/service et un assistant restreint : le processus Web ne peut pas écrire de code, mais il peut demander des opérations de package au travailleur de déploiement distinct. C'est le mode recommandé. Pour la réinstallation, un administrateur doit d'abord inspecter les identités, les noms d'instance et les services existants ; ne créez jamais un deuxième site sous la même identité.

**SimpleSAMLphp est facultatif.** Les connexions locales Simbioza, OIDC, CAS et OAuth2 ne l'exigent pas. Effectuez d'abord les étapes 1 à 7. Suivez la procédure pas à pas facultative SAML uniquement si cette installation particulière nécessite l'authentification SAML.

<a id="1-prepare-the-host"></a>

## 1. Préparez l'hôte

Commencez avec un shell d'administrateur normal sous Linux ou macOS. Vous avez besoin d'un domaine pointant vers le serveur et d'un certificat TLS valide pour les exemples HTTPS. Exécutez uniquement les commandes de votre système d'exploitation. Ne continuez pas après une erreur.

Sur Debian/Ubuntu, installez les outils et les extensions PHP :

```bash
sudo apt-get update
sudo apt-get install git rsync composer acl apache2 php-cli php-curl php-mbstring php-xml php-zip php-intl php-sqlite3 php-mysql php-pgsql php-fpm
```

Sur macOS, installez d'abord Homebrew s'il n'est pas installé, puis :

```bash
brew install git rsync composer httpd php
```

Vérifiez les outils installés :

```bash
php -v
php -m
composer --version
git --version
```

PHP doit être **8.2 ou version ultérieure**, Composer doit être la version 2 et PHP nécessite `ctype dom fileinfo intl json libxml mbstring openssl pdo session xmlreader zip`. Utilisez `pdo_sqlite`, `pdo_mysql` ou `pdo_pgsql` pour la base de données de votre choix. CLI PHP et le gestionnaire Web PHP doivent avoir des versions et des extensions correspondantes.

<a id="2-download-a-clean-simbioza-0339-release"></a>

## 2. Téléchargez une nouvelle version Simbioza 0.3.40

Choisissez **un** répertoire d'application. Sous Linux :

```bash
SIMBIOZA_APP_ROOT=/srv/simbioza
```

Sur macOS, utilisez le disque interne, pas une extraction de développement ou un volume monté avec `noowners` :

```bash
SIMBIOZA_APP_ROOT=/Users/Shared/Simbioza/simbioza
```

Gardez cette coquille ouverte. Modifiez maintenant le chemin choisi si vous avez besoin d'un autre site. Le répertoire ne doit **pas encore exister** ; cette vérification refuse délibérément d'écraser une installation existante. Un administrateur qui réinstalle un site doit d’abord gérer séparément son ancien service et ses données. Rien ici ne les supprime.

Exécutez le bloc de téléchargement complet en tant que compte de responsable normal :

```bash
test ! -e "$SIMBIOZA_APP_ROOT" && test ! -L "$SIMBIOZA_APP_ROOT" &&
sudo install -d -o "$(id -un)" -g "$(id -gn)" -m 0755 "$SIMBIOZA_APP_ROOT" &&
SIMBIOZA_FETCH_DIR="$(mktemp -d)" &&
git clone --depth 1 --branch 0.3.40 https://github.com/kmihalj/Simbioza.git "$SIMBIOZA_FETCH_DIR/source" &&
rsync -a --exclude=.git/ "$SIMBIOZA_FETCH_DIR/source/" "$SIMBIOZA_APP_ROOT/" &&
cd "$SIMBIOZA_APP_ROOT" &&
head -n 1 VERSION &&
composer update --no-interaction --prefer-dist --with-all-dependencies --optimize-autoloader &&
composer check-platform-reqs &&
mkdir -p data/cache data/logs data/sessions data/setup-requests data/tmp
```

Attendez-vous à `0.3.40` et `success` pour chaque exigence de plate-forme. La notification **détachée HEAD** de Git est normale lors de l'extraction d'une balise de version. N'exécutez pas Composer avec `sudo`, ne copiez pas la configuration ou la base de données d'un autre site, n'incluez pas `.git/` dans l'application déployée ou n'utilisez pas `--no-dev` ici. La version 0.3.0 est une base de référence pour une nouvelle installation, et non une mise à niveau à partir de la version 0.2.x.

La racine de configuration par défaut est `config/` (`HPH_CONFIG_PATH`), avec un sous-répertoire pour chaque module. Les fichiers persistants appartiennent sous `data/`. Seul `public/` sera exposé par le serveur Web. Conservez les chemins par défaut pour cette première installation ; les couches de configuration externes sont une option avancée et non une condition préalable.

<a id="3-choose-and-prepare-the-database"></a>

## 3. Choisissez et préparez la base de données

Choisissez **une** base de données. SQLite est la méthode la plus simple et ne nécessite aucun serveur de base de données ni mot de passe de base de données : sélectionnez SQLite dans l'assistant et il crée `data/simbioza.sqlite`. Passez à l'étape 4.

Si vous choisissez une base de données serveur et ne disposez pas encore de son service, installez **uniquement celle que vous avez choisie**. Sur Debian/Ubuntu : `sudo apt-get install mariadb-server` ou `sudo apt-get install postgresql`. Sur macOS : `brew install mariadb`, puis `brew services start mariadb`, ou `brew install postgresql@18`, puis `brew services start postgresql@18`. Pour les outils versionnés Homebrew PostgreSQL, utilisez `/opt/homebrew/opt/postgresql@18/bin/createuser` et `/opt/homebrew/opt/postgresql@18/bin/createdb` ci-dessous.

Pour **MySQL**, démarrez votre service MySQL/MariaDB et entrez son client administrateur. Créez une base de données vide et un compte d'application restreint :

```sql
CREATE DATABASE simbioza CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'simbioza'@'127.0.0.1' IDENTIFIED BY '<unique-database-password>';
GRANT ALL PRIVILEGES ON simbioza.* TO 'simbioza'@'127.0.0.1';
```

Utilisez l'hôte `127.0.0.1`, le port de votre service (normalement `3306`), la base de données `simbioza`, l'utilisateur `simbioza` et ce mot de passe dans l'assistant. N'utilisez pas l'administrateur de base de données comme compte de l'application.

Pour **PostgreSQL**, démarrez son service et exécutez ces commandes en tant qu'administrateur PostgreSQL (sur Debian/Ubuntu, préfixez chacune avec `sudo -u postgres`) :

```bash
createuser --pwprompt --no-superuser --no-createdb --no-createrole simbioza
createdb --owner=simbioza --encoding=UTF8 simbioza
```

Utilisez l'hôte `127.0.0.1`, le port de votre service (normalement `5432`), la base de données et l'utilisateur `simbioza`, ainsi que le mot de passe saisi à l'invite privée. Vérifiez que l'authentification TCP locale de la base de données autorise ce compte. Ne mettez pas de vrais mots de passe dans la documentation, l'historique des commandes ou Git.

<a id="4-create-the-dedicated-pool"></a>

## 4. Créez le pool dédié

Sur Debian, installez le package PHP-FPM pour la même version PHP utilisée par le package CLI et `acl`. À partir du répertoire de version, exécutez l'étape système unique :

```bash
cd "$SIMBIOZA_APP_ROOT"
sudo php scripts/configure_fpm_setup.php --install \
  --app-root="$SIMBIOZA_APP_ROOT" --maintainer="$(id -un)" &&
sudo -H -u fpm-simbioza php scripts/configure_fpm_setup.php --check \
  --app-root="$SIMBIOZA_APP_ROOT" --maintainer="$(id -un)"
```

Sur macOS avec Homebrew PHP, utilisez le chemin d'accès réel de l'application et le binaire FPM :

```bash
cd "$SIMBIOZA_APP_ROOT"
sudo php scripts/configure_fpm_setup.php --install \
  --app-root="$SIMBIOZA_APP_ROOT" \
  --maintainer="$(id -un)" --php-fpm="$(brew --prefix)/sbin/php-fpm" &&
sudo -H -u fpm-simbioza php scripts/configure_fpm_setup.php --check \
  --app-root="$SIMBIOZA_APP_ROOT" \
  --maintainer="$(id -un)" --php-fpm="$(brew --prefix)/sbin/php-fpm"
```

Pour un deuxième site, répétez les deux commandes avec les options correspondantes, par exemple `--instance=second --listen=127.0.0.1:9076`. Utilisez à nouveau les deux mêmes options avec `--finalize` à l'étape 7. Ne les omettez pas accidentellement : l'instance par défaut sans suffixe est un service système différent.

<a id="what-step-4-generates-and-starts"></a>

### Quelle étape 4 génère et démarre

**Ne créez pas ou ne remplissez pas `php-fpm.conf` manuellement.** La commande `--install` ci-dessus écrit la configuration FPM dédiée complète, teste sa syntaxe, enregistre le service et le démarre/redémarre. La commande `--check` suivante ne modifie pas les paramètres : elle crée et supprime des requêtes de diagnostic de courte durée via le véritable assistant. Il vérifie à la fois l’aller-retour de l’assistant et la prise en charge de CLI pour les mises à jour en arrière-plan. Une vérification échouée renvoie un statut de sortie différent de zéro. Attendez-vous à `[OK] Real Setup helper round trip` et `[OK] Background updater CLI runtime` ; ne continuez pas vers Apache ou l'assistant s'il manque ou échoue.

La vérification explicite `sudo -H -u fpm-simbioza` utilise immédiatement le compte d'exécution nouvellement créé, sans dépendre de l'adhésion au groupe actualisée de votre session SSH actuelle. Pour une instance nommée, utilisez également le compte d'exécution de cette instance, par exemple `fpm-simbioza-second`. L'assistant reste la propriété de root et limité aux actions approuvées ; n'accordez pas d'autorisations d'écriture de code au processus Web et ne désactivez pas le bac à sable du système de fichiers.

Sous Linux, l'instance par défaut utilise `/etc/php/<major.minor>/php-fpm-simbioza/php-fpm.conf`, où la version correspond au CLI PHP utilisé à l'étape 4. Avec PHP 8.4, le fichier exact est `/etc/php/8.4/php-fpm-simbioza/php-fpm.conf`. Il contient à la fois `[global]` et le pool dédié `[simbioza]` : ses comptes, son écouteur local, ses chemins d'application et `HPH_CONFIG_PATH`, ses répertoires de session privée/temporaires et ses limites PHP. Il ne s'agit **pas** du `/etc/php/8.4/fpm/php-fpm.conf` ou du `pool.d/www.conf` partagé. Le générateur écrit également `php-fpm-simbioza.service`, y compris son masque d'isolation et d'autorisations du système de fichiers. Ces paramètres systemd ne sont pas des entrées FPM INI. Pour inspecter la configuration utilisée par le service dédié, sans la modifier :

```bash
sudo systemctl show php-fpm-simbioza.service -p ExecStart &&
sudo systemctl is-active php-fpm-simbioza.service
```

Pour une instance nommée, le répertoire de configuration et le nom du service reçoivent tous deux le suffixe correspondant, par exemple `php-fpm-simbioza-second`. Sur macOS, l'outil crée à la place `$(brew --prefix)/etc/simbioza/php-fpm.conf`, qui inclut `pool.conf` dans le même répertoire, et enregistre le service launchd dédié. Ne remplacez pas un fichier généré correctement par la configuration d'un autre site. La configuration SimpleSAMLphp est ajoutée uniquement si vous suivez les étapes facultatives SAML. L'étape 5 configure toujours Apache/Nginx séparément ; la génération de FPM ne configure pas le serveur Web. Aucun redémarrage manuel supplémentaire du FPM n'est nécessaire après une étape 4 réussie et inchangée.

Le générateur 0.3.4 lance `Umask` sur l'entier **7** (octal `0007`), correspondant au `UMask=0007` de Linux. Le processus Web et le travailleur de déploiement restreint utilisent ce masque ; le travailleur le définit **après** avoir changé de compte. Leur `TMPDIR` est le `data/tmp` de cette application, donc les verrous temporaires partagés restent dans le même répertoire privé du groupe d'exécution. Les nouveaux fichiers journaux ordinaires sont des fichiers `0660` et non des fichiers `0644` appartenant à FPM auxquels le programme de mise à jour ne peut pas ajouter.

Sur macOS, vérifiez le service généré avant d'ouvrir l'assistant :

```bash
/usr/libexec/PlistBuddy -c 'Print :Umask' /Library/LaunchDaemons/hr.simbioza.php-fpm.plist | grep -qx 7
```

Pour une instance nommée, utilisez son fichier de service correspondant, par exemple `hr.simbioza-second.php-fpm.plist`. Un masque manquant est une condition préalable qui a échoué, et non une raison pour rendre le code accessible en écriture ou exécuter le programme de mise à jour en tant qu'utilisateur root. Les fichiers de session restent privés (`0600`) ; ne rendez pas récursivement toutes les données accessibles en écriture.

La vérification ne modifie aucun paramètre de l'application. Le pool par défaut écoute sur `127.0.0.1:9075` ; gardez ce port local sur le serveur. L'outil donne au compte FPM un accès en écriture aux données d'exécution, et non à l'ensemble de la version, et installe un assistant étroitement restreint pour les modifications de packages. Il crée également les groupes de déploiement et d'exécution. Vérifiez chaque résultat de diagnostic avant de continuer. N'utilisez pas `chmod 777`.

<a id="accounts-groups-and-permissions-created-by-the-tool"></a>

### Comptes, groupes et autorisations créés par l'outil

La commande `--install` ci-dessus inclut la création de compte/groupe ; n'exécutez pas séparément `useradd` ou `groupadd`. Les identités manquantes sont créées, tandis que les identités existantes portant ces noms sont réutilisées sans remplacer leur UID/GID. L’outil garantit les groupes principaux requis et les adhésions supplémentaires. Pour une instance nommée, chaque nom ci-dessous reçoit le même suffixe `-instance`.

Lors de la réinstallation sur le **même chemin d'application**, exécutez toujours `--install` de l'étape 4 : il actualise également l'assistant appartenant à root en dehors du répertoire de l'application. La suppression des fichiers d'application ne supprime pas cet assistant. Les comptes/groupes existants sont réutilisés ; il n’y a pas de recréation de compte manuelle. Une configuration Apache existante peut être conservée si elle correspond à l'étape 5, y compris le chemin public réel, le gestionnaire correct et les classes `AllowOverride`.

| Identité | But |
| --- | --- |
| `fpm-simbioza` / `app-simbioza` | Compte de service Web et groupe principal verrouillés ; ne peut pas écrire le code de l'application. |
| `simbioza-deploy` / `deploy-simbioza` | Compte de déploiement verrouillé et groupe de maintenance du code ; votre compte de responsable rejoint également ce groupe. |
| `run-simbioza` | Groupe d'exécution privé partagé pour FPM, le déploiement et le responsable. |

Sous Linux, inspectez le résultat après vous être reconnecté à SSH :

```bash
getent passwd fpm-simbioza simbioza-deploy &&
getent group app-simbioza deploy-simbioza run-simbioza &&
id "$(id -un)" &&
getfacl -p "$SIMBIOZA_APP_ROOT/config" "$SIMBIOZA_APP_ROOT/data"
```

Le code utilise la propriété du déploiement et un bac à sable FPM en lecture seule ; les répertoires d'exécution privés utilisent le groupe `run-simbioza` (`2770`) et la racine de configuration utilise un répertoire persistant (`3770`) pour les mises à jour atomiques contrôlées. Le partage d'exécution normal utilise ces autorisations de groupe, et non les larges autorisations d'écriture ACL. Avec la configuration privée facultative SimpleSAMLphp, l'outil définit en outre l'accès Linux et les ACL par défaut donnant `app-simbioza` **lecture/traverse uniquement** sur cette configuration. La procédure pas à pas SAML ci-dessous comprend la configuration et la vérification ACL. N’appliquez pas la recette FPM ordinaire/partagé `www-data` write-ACL au FPM sécurisé.

Avec le répertoire de configuration d'application par défaut, Auth, Email, HTML Editor, Workspace, Menu, Theme et Backup ont des répertoires de modules inscriptibles à l'exécution. La sauvegarde en a besoin pour la publication du descripteur de fournisseur atomique. La configuration du service PHP gérée par la version reste la propriété du déploiement et en lecture seule sur FPM. L'outil ne recrée pas `data/config` et n'accorde pas de droits d'exécution sur les anciens fichiers `editor-html.php`, `email.php` ou `workspace.php` supprimés.

<a id="5-configure-the-web-server"></a>

## 5. Configurez le serveur Web

**Choisissez la méthode avant d’exécuter les commandes :**

- **L'hôte virtuel HTTPS existant et un préfixe d'URL tel que `/simbioza` :** accèdent directement aux [instructions du préfixe d'URL](#optional-install-under-a-url-prefix-such-as-simbioza). Modifiez cet hôte virtuel existant. Ne créez pas `simbioza.conf` et n'exécutez pas `a2ensite simbioza` ; cette commande active uniquement un hôte virtuel séparé nouvellement créé.
- **Un nouveau nom d'hôte dédié :** utilisez la configuration Apache ou Nginx ci-dessous.

Choisissez **une** de ces configurations. Ils servent un nom d'hôte dédié à `/` ; remplacez le nom d'hôte, le chemin de version et les paramètres du certificat TLS. Pour une installation dans un sous-répertoire, configurez également le préfixe d'URL sur le serveur Web et transmettez cette URL complète à `install:prepare` à l'étape 6.

<a id="apache-with-php-fpm"></a>

### Apache avec PHP-FPM

Activez Apache `rewrite`, `proxy`, `proxy_fcgi` et TLS. Dirigez les requêtes PHP pour ce site vers le pool dédié ; ne les acheminez pas via `mod_php`.

Pour Apache sur Debian/Ubuntu, activez ses modules proxy FPM et créez le site :

```bash
sudo a2enmod rewrite proxy proxy_fcgi ssl
sudoedit /etc/apache2/sites-available/simbioza.conf
```

Sur macOS, modifiez `/opt/homebrew/etc/httpd/httpd.conf` avec `sudoedit`. Activez `rewrite_module`, `proxy_module`, `proxy_fcgi_module`, `ssl_module` et `socache_shmcb_module`. Ajoutez `Listen 443` si aucune directive existante n'y écoute. N'ajoutez pas de gestionnaire mod_php pour ce site.

Pour un nouvel Apache Homebrew, définissez explicitement l'identité du travailleur partagé dans `httpd.conf` (n'exécutez pas les travailleurs Web en tant que connexion propriétaire du code) :

```apache
User _www
Group _www
```

Dans la configuration ci-dessous, remplacez le domaine, les chemins de certificat TLS et les **deux** chemins `/srv/simbioza/public` par le chemin d'application réel de votre application (sur macOS, généralement `/Users/Shared/Simbioza/simbioza/public`). Apache ne développe pas `$SIMBIOZA_APP_ROOT` à partir de votre terminal.

```apache
<VirtualHost *:443>
    ServerName simbioza.example.org
    DocumentRoot /srv/simbioza/public
    ProxyTimeout 900

    <Directory /srv/simbioza/public>
        Options -Indexes +FollowSymLinks
        AllowOverride FileInfo Indexes Options
        Require all granted
        DirectoryIndex index.php

        <FilesMatch "\.php$">
            SetHandler "proxy:fcgi://127.0.0.1:9075"
        </FilesMatch>
    </Directory>

    SSLEngine on
    SSLCertificateFile /path/to/fullchain.pem
    SSLCertificateKeyFile /path/to/privkey.pem
</VirtualHost>
```

Le `public/.htaccess` livré utilise `DirectoryIndex index.php`, qui nécessite la classe de remplacement `Indexes` ; les règles de réécriture nécessitent `FileInfo` et `Options -Indexes` nécessitent `Options`. Conservez les trois classes dans `AllowOverride`. Cela n'active **pas** les listes de répertoires : `Options -Indexes` les désactive toujours. Une classe manquante peut produire HTTP 500 (`DirectoryIndex not allowed here`) même lorsque `apache2ctl configtest` signale `Syntax OK`, car `.htaccess` est lu lorsqu'une requête atteint ce répertoire. Après le rechargement, vérifiez l'URL publique réelle avant de passer à l'assistant. Consultez la [référence Apache DirectoryIndex](https://httpd.apache.org/docs/2.4/mod/mod_dir.html#directoryindex). N'exposez pas le répertoire de la version parent en tant que racine du document.

Pour le **nouveau `simbioza.conf` distinct créé ci-dessus uniquement**, testez et démarrez Apache. Ignorez ce bloc lorsque vous utilisez un hôte virtuel partagé existant. Sur Debian/Ubuntu :

```bash
test -f /etc/apache2/sites-available/simbioza.conf &&
sudo a2ensite simbioza &&
sudo apache2ctl configtest &&
sudo systemctl enable --now apache2 &&
sudo systemctl reload apache2
```

Sur macOS :

```bash
sudo /opt/homebrew/bin/httpd -t
sudo /opt/homebrew/bin/brew services start httpd
```

Attendez-vous à `Syntax OK` ; ne pas démarrer/recharger après une vérification échouée. Pour un Apache déjà en cours d'exécution, utilisez un rechargement progressif validé au lieu de démarrer un deuxième service. Continuez vers le programme d'installation ; l'alternative Nginx ci-dessous n'est pas une étape supplémentaire.

<a id="nginx-with-php-fpm"></a>

### Nginx avec PHP-FPM

Nginx ne peut pas exécuter `mod_php` ; il envoie des requêtes PHP au même pool FPM dédié. Conservez les règles `SCRIPT_FILENAME` et `try_files` ensemble :

Choisissez cette alternative **au lieu d'Apache** : utilisez `nginx` plutôt que `apache2` dans la liste des packages de l'étape 1 (`brew install nginx` sur macOS) et n'exécutez pas les commandes de configuration/démarrage d'Apache. Modifiez `/etc/nginx/sites-available/simbioza` sur Debian/Ubuntu ou `/opt/homebrew/etc/nginx/nginx.conf` sur macOS. Remplacez le domaine, les chemins de certificat et la racine de l'application dans le bloc serveur complet ci-dessous.

```nginx
server {
    listen 443 ssl;
    server_name simbioza.example.org;
    root /srv/simbioza/public;
    index index.php;

    ssl_certificate /path/to/fullchain.pem;
    ssl_certificate_key /path/to/privkey.pem;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        try_files $uri =404;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass 127.0.0.1:9075;
        fastcgi_read_timeout 900s;
    }

    location ~ /\. {
        deny all;
    }
}
```

Sur Debian/Ubuntu, activez le blocage du nouveau serveur, testez et démarrez :

```bash
sudo ln -s /etc/nginx/sites-available/simbioza /etc/nginx/sites-enabled/simbioza
sudo nginx -t
sudo systemctl enable --now nginx
sudo systemctl reload nginx
```

Sur macOS : `sudo /opt/homebrew/bin/nginx -t`, puis `sudo /opt/homebrew/bin/brew services start nginx`. Si le lien ou le service existe déjà, inspectez-le plutôt que de le dupliquer.

Testez la configuration Nginx avant de recharger. L'écouteur FPM ne doit pas être accessible à partir d'un réseau non approuvé. La préparation du package via l'installateur graphique peut prendre plusieurs minutes ; l'attente de la passerelle Apache/Nginx de 900 secondes empêche un 504 par défaut de 60 secondes pendant que le travailleur restreint est toujours en cours d'exécution. Ajustez tout délai d'expiration supplémentaire du proxy inverse en amont en conséquence, mais ne désactivez pas les limites de requêtes FPM ou du serveur Web pour les sites non liés. Le pool dédié généré par `configure_fpm_setup.php` définit également `max_execution_time` de PHP sur 900 secondes. C'est important : sur la version testée de macOS/PHP 8.5, une valeur de `0` mettait toujours fin à une longue requête après une minute, même si cela ne devrait signifier aucune limite. Vérifiez les deux paramètres de délai d'attente dans le pool généré et testez une préparation de package plus longue.

<a id="optional-install-under-a-url-prefix-such-as-simbioza"></a>

### Facultatif : installer sous un préfixe d'URL tel que /simbioza

Pour un nom d'hôte partagé, placez ce bloc **à l'intérieur de son hôte virtuel HTTPS** existant, au lieu de créer un autre hôte virtuel portant le même nom. Remplacez le chemin de version, le préfixe d'URL et (pour FPM) le port du pool :

Les trois chemins ont des objectifs différents :

| Paramètre | Exemple |
| --- | --- |
| Racine de l'application utilisée aux étapes 2 et 4 | `/srv/simbioza` |
| Répertoire public utilisé à la fois par `Alias` et `<Directory>` | `/srv/simbioza/public` |
| Préfixe d'URL publique | `/simbioza` |

Il n’est pas nécessaire que la racine de l’application se trouve dans le `DocumentRoot` du nom d’hôte partagé. Gardez-le en dehors de cette arborescence public. Aucun lien symbolique vers le répertoire d’application n’est requis pour cette configuration. Le lien symbolique d'activation du site Nginx ci-dessus n'a aucun rapport : il active la configuration du serveur, pas les fichiers d'application.

```apache
Alias /simbioza "/srv/simbioza/public"
<Directory "/srv/simbioza/public">
    Options -Indexes +FollowSymLinks
    AllowOverride FileInfo Indexes Options
    Require all granted
    DirectoryIndex index.php
    <FilesMatch "\.php$">
        SetHandler "proxy:fcgi://127.0.0.1:9075"
    </FilesMatch>
</Directory>
```

Définissez également `ProxyTimeout 900` dans ce vhost existant. Si une ancienne configuration contient `<Directory "/var/www/html/simbioza">` pour un ancien lien symbolique de répertoire public, **remplacez ce bloc** par le véritable bloc de répertoire public ci-dessus ; ajouter uniquement `Alias` n'est pas suffisant. Le gestionnaire PHP doit s'appliquer à la cible réelle du système de fichiers de l'alias. N'exposez pas le répertoire parent de l'application, `config/`, `data/` ou `vendor/` pour faire fonctionner un chemin obsolète.

Pour une racine d'application `/data/www/simbioza`, les deux chemins du bloc deviennent `/data/www/simbioza/public` ; l'URL publique reste `/simbioza`. Ne modifiez pas `SIMBIOZA_APP_ROOT` et ne régénérez pas le pool simplement pour modifier le mappage d'URL. Si la racine de l'application elle-même change, ses chemins FPM/helper nécessitent également une reconfiguration explicite ; un alias Apache n'effectue pas cette relocalisation.

Avec Puppet/Ansible, effectuez la modification équivalente dans la définition de l'hôte virtuel géré faisant autorité. Ne modifiez pas le vhost généré en tant que correctif permanent, ne créez pas un vhost HTTPS en double et ne supprimez pas les alias d'autres applications. Si SAML facultatif a déjà un alias plus long tel que `/simbioza/simplesaml`, conservez cet alias **avant** l'alias général `/simbioza`. SAML reste facultatif.

Après avoir modifié **uniquement la configuration de l'alias/du répertoire d'Apache**, validez-le et rechargez gracieusement Apache. L'étape 4 a déjà démarré le service FPM dédié :

```bash
sudo apache2ctl configtest && sudo systemctl reload apache2
```

Utilisez ce bloc Linux au lieu des commandes `a2ensite simbioza` du nouveau site lorsque le vhost HTTPS partagé existe déjà. Les commandes de validation/rechargement Homebrew sont répertoriées dans la section Apache ci-dessus. Un service FPM en cours d'exécution et une syntaxe Apache valide ne prouvent pas que la requête utilise le chemin d'application correct : testez également l'URL publique `/simbioza/`. Arrêtez-vous sur HTTP 500 ou un `bootstrap.php` manquant ; inspectez l'alias, le gestionnaire de répertoire et les chemins FPM au lieu de continuer avec l'assistant ou de modifier largement les autorisations.

<a id="reinstallation-only-an-obsolete-custom-fpm-bind-mount"></a>

#### Réinstallation uniquement : un montage de liaison FPM personnalisé obsolète

Une nouvelle installation ne crée pas de drop-in pour un ancien lien symbolique de répertoire public. Si une installation existante en possédait une, inspectez-la avant de redémarrer :

```bash
sudo systemctl cat php-fpm-simbioza.service
sudo systemctl show php-fpm-simbioza.service -p DropInPaths
```

Utilisez le nom de service suffixé pour une instance nommée. Après être passé à l'alias direct, supprimez **uniquement un drop-in personnalisé obsolète confirmé** dont le seul objectif était de lier le chemin du lien symbolique supprimé. Ne supprimez jamais l’intégralité du répertoire `.service.d` ni les montages de liaison réels de l’application générée. Une liaison obsolète à un chemin inexistant peut empêcher le prochain démarrage de FPM. Après cette suppression spécifique, utilisez cette séquence Linux complète au lieu du rechargement Apache uniquement ci-dessus. Cela s'applique également après avoir délibérément modifié la configuration FPM dédiée ; `daemon-reload` est nécessaire lorsqu'une unité systemd ou un drop-in a été modifié, pas simplement parce que `php-fpm.conf` a changé :

```bash
SIMBIOZA_PHP_VERSION="$(php -r 'echo PHP_MAJOR_VERSION, ".", PHP_MINOR_VERSION;')" &&
sudo systemctl daemon-reload &&
sudo "/usr/sbin/php-fpm$SIMBIOZA_PHP_VERSION" --test \
  --fpm-config="/etc/php/$SIMBIOZA_PHP_VERSION/php-fpm-simbioza/php-fpm.conf" &&
sudo apache2ctl configtest &&
sudo systemctl restart php-fpm-simbioza.service &&
sudo systemctl is-active php-fpm-simbioza.service &&
sudo systemctl reload apache2
```

Utilisez le binaire FPM, le chemin de configuration et le suffixe de service correspondants pour votre installation. Attendez-vous à un test de configuration FPM réussi, `Syntax OK` et `active`. La chaîne `&&` s'arrête après l'échec d'une commande ; ne continuez pas vers l'assistant après une erreur. Un redémarrage de FPM interrompt ses requêtes en cours, ne l'exécutez donc pas pendant le travail d'installation/mise à jour. Il s'agit d'un nettoyage unique de l'hôte ou de l'application d'un changement de configuration intentionnel, **et non d'une étape supplémentaire requise lors d'une nouvelle installation**. Une configuration FPM inchangée ne nécessite aucun redémarrage pour un seul changement d'alias Apache.

Utilisez `--base-url=https://your-host.example/simbioza` pour le programme d'installation, pas le nom d'hôte sans son préfixe. Pour une installation sur `/wiki`, remplacez les **deux** occurrences du préfixe et utilisez `--base-url=https://your-host.example/wiki`. Le chemin du répertoire est un chemin du système de fichiers ; le préfixe est un chemin URL, pas le nom du répertoire d'application. Conservez les `config/` et `data/` privés en dehors de l'alias exposé. Validez Apache avant son rechargement gracieux.

<a id="6-run-the-graphical-installer"></a>

## 6. Exécutez le programme d'installation graphique

Générez une URL d'installation unique à partir du répertoire de version :

```bash
sudo -H -u fpm-simbioza php bin/simbioza install:prepare --base-url=https://simbioza.example.org
```

Si vous avez choisi `--instance=second`, utilisez plutôt `-u fpm-simbioza-second`. Cela exécute PHP en tant qu'identité FPM, et non en tant que root. Pour un site à `/simbioza`, utilisez plutôt `--base-url=https://simbioza.example.org/simbioza`. Ouvrez l'URL dans une fenêtre de navigateur privée ; ne partagez pas et ne capturez pas son jeton. L'assistant vérifie les exigences et l'accès à la base de données, demande l'identité du site, les langues, le fuseau horaire, le premier administrateur et les modules facultatifs, puis affiche un examen avant l'installation. Tous les modules optionnels sont présélectionnés ; désélectionnez ceux dont vous ne voulez pas. Lorsque l'assistant dédié fonctionne, les packages facultatifs sélectionnés sont installés via l'interface graphique.

Si vous omettez intentionnellement l'assistant dédié, préparez les packages facultatifs sélectionnés en tant qu'utilisateur de déploiement avec `php scripts/installation_packages.php prepare` pour tous les modules, ou ajoutez `--modules=calendar` pour une sélection plus petite. Faites correspondre la liste à vos sélections et rechargez le programme d'installation. Sans l'assistant, les modifications ultérieures du package GUI ne sont pas disponibles. L'installation/mise à jour/suppression de la langue écrit uniquement les données d'exécution et la configuration et ne nécessite pas cette aide. Assurez-vous que ces répertoires sont accessibles en écriture par PHP ; voir [localisation](localization_en.md).

Après l'installation, connectez-vous en tant qu'administrateur. Dans **Paramètres → Configuration et modules**, vérifiez que les autorisations du pool dédié, de l'assistant et de l'exécution sont toutes signalées comme prêtes. La vérification de mise à jour en arrière-plan CLI doit également réussir, et les opérations du package GUI et les mises à jour d'application doivent être disponibles. Un résultat gris **Non vérifié** n'est pas une réussite : résolvez d'abord la vérification d'assistance qui a échoué précédemment ; cela ne signifie pas que les extensions CLI sont manquantes. L'assistant importe les langues du guide utilisateur sélectionnées.

<a id="7-finalize-permissions-and-verify"></a>

## 7. Finalisez les autorisations et vérifiez

Après l'assistant, déconnectez-vous et reconnectez-vous à SSH/au terminal pour activer les nouvelles adhésions aux groupes de déploiement/d'exécution. Restaurez `SIMBIOZA_APP_ROOT` sur le chemin sélectionné à l'étape 2, puis exécutez les commandes correspondantes :

```bash
sudo php scripts/configure_fpm_setup.php --finalize \
  --app-root="$SIMBIOZA_APP_ROOT" --maintainer="$(id -un)" &&
php scripts/configure_fpm_setup.php --check \
  --app-root="$SIMBIOZA_APP_ROOT" --maintainer="$(id -un)" &&
vendor/bin/hph modules migrate-status &&
composer check-platform-reqs
```

Sur macOS, ajoutez le même argument `--php-fpm` et le même chemin d'application que ceux utilisés à l'étape
4. Déconnectez-vous et reconnectez-vous pour que le responsable reçoive les nouvelles adhésions au groupe.
Attendez-vous à aucune migration en attente, à une connexion et à des pages d'accueil réussies et à une route `/install` verrouillée. Sauvegardez la base de données, `config/`, les fichiers utilisateur et les données du thème.

La configuration unique de `sudo` ne signifie **pas** que les mises à jour normales nécessitent un root. Les commandes de maintenance sont incluses à la fin de ce guide.

<a id="optional-simplesamlphp-integration"></a>

## Intégration SimpleSAMLphp facultative

**Ignorez toute cette section, sauf si vous avez besoin de SAML.** Cela n'a aucun rapport avec la configuration de OIDC, CAS, OAuth2 ou la connexion locale. Gardez l'administrateur local et la connexion par bris de glace disponibles jusqu'à ce que SAML fonctionne.

<a id="s1-select-the-library-and-public-addresses"></a>

### S1. Sélectionnez la bibliothèque et les adresses publiques

Terminez d’abord l’installation normale. Dans le shell de votre responsable, définissez les chemins et URL réels. Exemple pour un nom d'hôte dédié :

```bash
SIMBIOZA_APP_ROOT=/srv/simbioza
SIMBIOZA_SAML_ROOT=/opt/simplesamlphp
SIMBIOZA_SAML_URL=https://simbioza.example.org/simplesaml/
SIMBIOZA_SAML_CONFIG="$SIMBIOZA_APP_ROOT/data/saml/config"
```

Pour un site à `https://your-host.example/simbioza`, utilisez plutôt `SIMBIOZA_SAML_URL=https://your-host.example/simbioza/simplesaml/`. Gardez la barre oblique finale. La racine de la bibliothèque n'est **pas** le répertoire de Simbioza. Une bibliothèque gérée par le système existante peut être réutilisée, par exemple `/usr/share/simplesamlphp-aai` ; ne modifiez pas sa configuration partagée, ses certificats ou le SP d'une autre application.

Si une bibliothèque compatible est déjà installée, ignorez le bloc de téléchargement. Sinon, cet exemple installe en amont **2.5.3.1 complet**, avec sa somme de contrôle publiée. Il nécessite PHP **8.3 ou version ultérieure** et les extensions des [exigences SimpleSAMLphp](https://simplesamlphp.org/docs/stable/simplesamlphp-install.html). Vérifiez [versions en amont](https://simplesamlphp.org/download/) pour les mises à jour de sécurité ; lorsque vous choisissez une autre version, modifiez simultanément l'URL et la somme de contrôle. Exécutez la commande suivante uniquement pour un **nouveau** répertoire de bibliothèque inexistant :

Sur Debian/Ubuntu, installez d'abord les outils de téléchargement avec `sudo apt-get install curl openssl`. macOS fournit déjà `curl` et `openssl`.

```bash
test ! -e "$SIMBIOZA_SAML_ROOT" && test ! -L "$SIMBIOZA_SAML_ROOT" &&
SIMBIOZA_SAML_FETCH="$(mktemp -d)" &&
curl --fail --location --proto '=https' --tlsv1.2 \
  --output "$SIMBIOZA_SAML_FETCH/simplesamlphp.tar.gz" \
  https://github.com/simplesamlphp/simplesamlphp/releases/download/v2.5.3.1/simplesamlphp-2.5.3.1-full.tar.gz &&
test "$(openssl dgst -sha256 "$SIMBIOZA_SAML_FETCH/simplesamlphp.tar.gz" | awk '{print $NF}')" = \
  b199ebcba6e30220e7b5df2783ca42f8947dd9bdf046e9e6302fef3d3a438d01 &&
sudo install -d -m 0755 "$SIMBIOZA_SAML_ROOT" &&
sudo tar -xzf "$SIMBIOZA_SAML_FETCH/simplesamlphp.tar.gz" \
  --strip-components=1 -C "$SIMBIOZA_SAML_ROOT"
```

Arrêtez-vous si une commande échoue. Vérifiez la bibliothèque sélectionnée :

```bash
test -f "$SIMBIOZA_SAML_ROOT/vendor/autoload.php" &&
test -f "$SIMBIOZA_SAML_ROOT/public/module.php" &&
php -r 'require $argv[1]; exit(class_exists("SimpleSAML\\Auth\\Simple") ? 0 : 1);' \
  "$SIMBIOZA_SAML_ROOT/vendor/autoload.php"
```

<a id="s2-prepare-private-configuration-and-trusted-metadata"></a>

### S2. Préparer une configuration privée et des métadonnées fiables

À partir de `$SIMBIOZA_APP_ROOT`, créez des répertoires privés et copiez les **modèles**. Ce bloc refuse d'écraser la configuration. Lors de la restauration des paramètres conservés pour le **même SP**, restaurez plutôt ses `config`, ses certificats et ses métadonnées requises ; ne copiez pas les informations d'identification d'un autre site et ne restaurez pas les sessions/le cache/l'ancienne base de données de l'application.

```bash
mkdir -p "$SIMBIOZA_APP_ROOT/data/saml/config" \
  "$SIMBIOZA_APP_ROOT/data/saml/metadata" "$SIMBIOZA_APP_ROOT/data/saml/cert" \
  "$SIMBIOZA_APP_ROOT/data/saml/data" "$SIMBIOZA_APP_ROOT/data/saml/cache" \
  "$SIMBIOZA_APP_ROOT/data/saml/logs" "$SIMBIOZA_APP_ROOT/data/saml/tmp" &&
test ! -e "$SIMBIOZA_SAML_CONFIG/config.php" &&
test ! -e "$SIMBIOZA_SAML_CONFIG/authsources.php" &&
cp "$SIMBIOZA_SAML_ROOT/config/config.php.dist" "$SIMBIOZA_SAML_CONFIG/config.php" &&
cp "$SIMBIOZA_SAML_ROOT/config/authsources.php.dist" "$SIMBIOZA_SAML_CONFIG/authsources.php"
```

Modifiez `config.php` avec `sudoedit "$SIMBIOZA_SAML_CONFIG/config.php"`. Définissez ces clés dans son tableau `$config` existant. Remplacez les exemples de chemins de système de fichiers par le chemin de votre application ; Les fichiers PHP ne développent pas les variables du terminal.

| Clé | Valeur ou action |
| --- | --- |
| `baseurlpath` | La valeur HTTPS `SIMBIOZA_SAML_URL` complète, y compris la barre oblique finale. |
| `certdir`, `metadatadir` | `/srv/simbioza/data/saml/cert` et `/srv/simbioza/data/saml/metadata`. |
| `datadir`, `cachedir`, `loggingdir`, `tempdir` | Les répertoires privés correspondants `data`, `cache`, `logs`, `tmp` sous `/srv/simbioza/data/saml`. |
| `metadata.sources` | Une source fiable, par ex. `[['type' => 'flatfile']]` en utilisant le `metadatadir` privé ; utilisez le mécanisme vérifié de votre fédération, le cas échéant. |
| `secretsalt`, `assets['salt']` | Deux valeurs aléatoires différentes, chacune générée avec `openssl rand -hex 32` ; modifiez `salt` à l'intérieur du tableau `assets` existant, et non une nouvelle clé `assets.salt` de niveau supérieur. N'utilisez jamais de valeurs de modèle et ne les publiez jamais. |
| `auth.adminpassword` | Un hachage généré de manière interactive avec `php "$SIMBIOZA_SAML_ROOT/bin/pwgen.php"` ; distinct de votre mot de passe Simbioza. |
| `store.type` | `phpsession` pour cette procédure pas à pas. |
| `session.phpsession.cookiename` | Un nom spécifique au site, par ex. `SimbiozaSAML`, différent des cookies de session de Simbioza et d'autres sites. |
| `session.phpsession.savepath` | `null`, de sorte que les deux points de terminaison utilisent le `data/sessions` privé de ce pool. |
| `session.cookie.path` | `/` pour un nom d'hôte dédié ou `/simbioza/` pour ce préfixe ; doit couvrir à la fois l’application et le point de terminaison SAML. |
| `session.cookie.secure`, `session.phpsession.httponly` | `true` ; utilisez HTTPS. |
| `showerrors` | `false` en dehors du dépannage contrôlé. |

Dans `authsources.php`, configurez `default-sp` comme `saml:SP` avec votre propre `entityID` stable et l'ID d'entité `idp` de l'IdP de confiance. Supprimez les exemples de sources inutilisés. Obtenez les métadonnées IdP auprès de votre fédération/administrateur IdP ; enregistrez les métadonnées PHP fiables sous `data/saml/metadata/saml20-idp-remote.php` ou configurez le mécanisme de métadonnées pris en charge par la fédération. Ne désactivez pas les vérifications de signature et ne faites pas confiance à un certificat de signature non vérifié.

Si l'IdP nécessite un certificat SP, générez une clé/un certificat privé pour un **nouveau** SP dans `data/saml/cert` de ce site et référencez les noms de fichiers via `privatekey` et `certificate` dans `default-sp`, conformément à la politique de la fédération. Pour le **même SP enregistré**, conservez son EntityID, les clés/certificats existants et les métadonnées pertinentes plutôt que de générer des remplacements.

<a id="s3-configure-the-dedicated-pool-and-verify-acls"></a>

### S3. Configurer le pool dédié et vérifier les ACL

À partir de `$SIMBIOZA_APP_ROOT`, réexécutez le générateur pour la **même application et instance**, en ajoutant l'argument de configuration privé. Cela met à jour et redémarre uniquement ce pool dédié, en réutilisant ses comptes/groupes existants :

```bash
cd "$SIMBIOZA_APP_ROOT" &&
sudo php scripts/configure_fpm_setup.php --install \
  --app-root="$SIMBIOZA_APP_ROOT" --maintainer="$(id -un)" \
  --simplesaml-config-dir="$SIMBIOZA_SAML_CONFIG" &&
sudo php scripts/configure_fpm_setup.php --finalize \
  --app-root="$SIMBIOZA_APP_ROOT" --maintainer="$(id -un)" \
  --simplesaml-config-dir="$SIMBIOZA_SAML_CONFIG" &&
php scripts/configure_fpm_setup.php --check \
  --app-root="$SIMBIOZA_APP_ROOT" --maintainer="$(id -un)" \
  --simplesaml-config-dir="$SIMBIOZA_SAML_CONFIG"
```

Sur macOS, ajoutez `--php-fpm="$(brew --prefix)/sbin/php-fpm"` aux **trois** commandes. Pour une instance nommée, répétez également les options d'origine `--instance` et `--listen`. Le générateur exporte `SIMPLESAMLPHP_CONFIG_DIR` à l'intérieur du travailleur FPM malgré `clear_env = yes` ; un SSH `export` ou Apache `SetEnv` seul n'est pas suffisant.

Sous Linux, vérifiez l'accès en lecture et les ACL de configuration privée :

```bash
getfacl -p "$SIMBIOZA_SAML_CONFIG" "$SIMBIOZA_SAML_CONFIG/config.php" &&
sudo -u fpm-simbioza test -r "$SIMBIOZA_SAML_CONFIG/config.php" &&
sudo -u fpm-simbioza test -r "$SIMBIOZA_SAML_CONFIG/authsources.php" &&
sudo -u fpm-simbioza test ! -w "$SIMBIOZA_SAML_CONFIG/config.php"
```

Attendez-vous à `group:app-simbioza:r-x` et à un ACL par défaut correspondant sur le répertoire ; les fichiers sont lisibles et non inscriptibles par FPM. Le déploiement conserve la propriété. Utilisez des identités suffixées pour une instance nommée. Sur macOS, le générateur utilise la propriété de groupe au lieu des commandes Linux POSIX ACL.

<a id="s4-send-the-saml-endpoint-to-the-same-fpm-pool"></a>

### S4. Envoyer le point de terminaison SAML au même pool FPM

Ajoutez ce bloc au **hôte virtuel HTTPS** existant, et non à un deuxième hôte virtuel ou à un alias SimpleSAMLphp global partagé. Remplacez le préfixe d'URL et les chemins du système de fichiers par les valeurs de S1 ; Apache ne développe pas les variables shell. Un alias SAML plus long doit apparaître **avant** un alias d'application général `/simbioza`.

```apache
Alias /simplesaml "/opt/simplesamlphp/public"
<Directory "/opt/simplesamlphp/public">
    Options -Indexes +FollowSymLinks
    AllowOverride None
    Require all granted
    AcceptPathInfo On
</Directory>
ProxyPassMatch "^/simplesaml/(.+\.php(?:/.*)?)$" "fcgi://127.0.0.1:9075/opt/simplesamlphp/public/$1"
```

Pour `/simbioza`, utilisez `Alias /simbioza/simplesaml` et l'expression régulière `^/simbioza/simplesaml/(.+\.php(?:/.*)?)$`. Pour un package système, remplacez les **deux** chemins `/opt/simplesamlphp/public` par son répertoire `public/` réel. Utilisez le port réel de votre pool. Le proxy explicite préserve `module.php/...` PATH_INFO et utilise l'identité FPM, l'environnement et le répertoire de session de l'application. Son gestionnaire PHP ordinaire ne couvre pas cet alias distinct.

Sur Debian/Ubuntu validez, puis rechargez gracieusement Apache :

```bash
sudo apache2ctl configtest && sudo systemctl reload apache2
```

Sur Homebrew Apache, utilisez `sudo /opt/homebrew/bin/httpd -t` et, uniquement en cas de succès, `sudo /opt/homebrew/bin/httpd -k graceful`. Avec Puppet/Ansible, modifiez le vhost géré faisant autorité plutôt qu'un fichier généré. Pour Nginx, utilisez la [configuration officielle alias/PATH_INFO](https://simplesamlphp.org/docs/stable/simplesamlphp-install.html#configuring-nginx) avec ce même pool dédié et préfixe d'URL, pas un pool par défaut partagé. N’exposez jamais `config/`, `cert/` ou des métadonnées privées via l’alias Web.

<a id="s5-register-the-sp-map-attributes-and-test-authentication"></a>

### S5. Enregistrez le SP, mappez les attributs et testez l'authentification

Ouvrez l'URL de base SAML et obtenez les métadonnées `default-sp` via l'interface d'administrateur SimpleSAMLphp. Envoyez-le à l’administrateur IdP/fédération. Vérifiez que les **EntityID, ACS et SLO** enregistrés utilisent le préfixe HTTPS SAML de ce site et attendez que l'enregistrement soit actif.

Dans Simbioza **Paramètres → Authentification**, activez le protocole SimpleSAMLphp. Créez un profil SAML avec le chemin absolu `vendor/autoload.php` et `auth_source = default-sp` de la bibliothèque. Utilisez le **Profil de test** pour capturer les attributs IdP réels ; choisissez un attribut sujet/connexion stable et mappez les champs e-mail/nom. Ne devinez pas les noms d’attributs d’un autre fournisseur. Testez à nouveau jusqu'à ce que le profil soit opérationnel, activez-le et vérifiez que les utilisateurs prévus peuvent l'utiliser. La création automatique de compte est un choix de profil explicite.

L'interface graphique écrit les paramètres de Simbioza dans `HPH_CONFIG_PATH/auth/auth.json` ; il ne modifie pas les fichiers externes SimpleSAMLphp. Pour une configuration manuelle de l'authentification, commencez par le `auth.json.dist` du module Auth, jamais en remplaçant un fichier actif.

Testez dans un navigateur privé : connexion SAML → retour à Simbioza → page protégée → déconnexion → une autre connexion. Le chargement des métadonnées à lui seul ne prouve pas l’authentification. Avec `phpsession`, un répertoire de pool/session différent peut provoquer des boucles de connexion : corriger le routage/l'environnement au lieu d'accorder des autorisations plus larges. Conservez l’accès de l’administrateur local jusqu’à ce que le flux complet du navigateur réussisse.

<a id="maintenance-after-installation"></a>

## Entretien après installation

Conservez les commandes de routine CLI sous le compte du propriétaire du code, jamais le compte Web et non root. Sauvegardez la base de données, les répertoires `HPH_CONFIG_PATH` actifs et `data/` persistants avant une mise à jour.

Vérifications en lecture seule :

```bash
cd "$SIMBIOZA_APP_ROOT"
vendor/bin/hph modules list
vendor/bin/hph languages available
php update.php --check
```

Pour ajouter un module ultérieurement, exécutez `vendor/bin/hph modules add calendar --fresh` ; `modules disable calendar` conserve ses données et `modules enable calendar` les réactive. La suppression utilise `modules remove calendar --yes` et crée une sauvegarde de module ; inspectez-le avec `modules backups calendar` avant un `modules add calendar --restore` délibéré. Ce sont des alternatives, pas un bloc à exécuter les uns après les autres.

Les modules linguistiques sont indépendants des versions de l'application. Utilisez `vendor/bin/hph languages install de` ou `vendor/bin/hph languages update` ; l'interface graphique du langage fonctionne également lorsque ses répertoires d'exécution sont accessibles en écriture.

Lorsque vous avez l'intention de mettre à jour l'application, utilisez `php update.php` comme propriétaire du code/mainteneur du déploiement. Le programme de mise à jour s'actualise d'abord, préserve les modules sélectionnés et la configuration privée, applique les migrations et actualise les guides fournis. Ne remplacez pas un `composer update` autonome sur un site installé. Avant les migrations, un échec de mise à jour peut restaurer le code ; après le démarrage des migrations, la maintenance reste activée pour une récupération contrôlée. Ne supprimez jamais `data/update-maintenance.json` pour masquer une mise à jour inachevée.

Vérifiez ensuite aucune migration en attente, la connexion réelle, le contenu et les pièces jointes. Les exemples utilisent les chemins par défaut `config/` et `data/` ; si vous choisissez ultérieurement des couches de configuration externes, gardez leurs autorisations et leur couverture de sauvegarde explicites.

<a id="external-configuration-layers"></a>

## Couches de configuration externes

Préparez la configuration complète avant de la sélectionner en privé `framework.php`. Le pool généré et l'assistant fixe épinglent les mêmes paramètres de base et de remplacement ; ils ne s'appuient pas sur des variables héritées survivant à `clear_env` ou `sudo`. Les règles du bac à sable Linux lient les couches inférieures en lecture seule et exposent la couche finale ainsi que le répertoire de requêtes d'installation configuré. Cela autorise l'écriture uniquement là où les droits Unix le permettent déjà ; l'outil ne chown/chmod les couches externes.

Donnez au FPM et déployez un accès en lecture/recherche aux comptes. Conservez le code de service PHP déployé et en lecture seule sur FPM ; donner aux fichiers de données inscriptibles appartenant au moment de l'exécution/verrouille le groupe d'exécution partagé et les autorisations de remplacement atomique. N'utilisez pas de répertoire personnel protégé ou de racine de document public pour une configuration privée. Pré-créez un répertoire de requêtes personnalisé avec les autorisations partagées requises. Après avoir modifié les chemins sur un site existant, répétez `--install`, puis `--finalize`, en utilisant les mêmes options d'instance, de port et SAML. `--finalize` actualise à lui seul l'assistant/la propriété, pas le pool FPM. Consultez le [guide de configuration](configuration_en.md).
