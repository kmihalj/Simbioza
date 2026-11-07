# Installer Simbioza avec PHP-FPM classique

<a id="install-simbioza-with-ordinary-php-fpm"></a>


> Les captures d’écran présentent l’interface en anglais.

[Version croate](installation_fpm_hr.md)

Ce guide complet démarre sur un terminal administrateur normal sans installation Simbioza existante. Il comprend chaque étape jusqu'à la première connexion de l'administrateur ; aucun autre guide d'installation n'est requis. PHP-FPM s'exécute via le compte Web partagé du système. La préparation facultative des packages et les mises à jour de l'application concernent uniquement CLI ; ce mode n'installe pas l'assistant de déploiement sécurisé. Le programme d'installation graphique initial, l'interface graphique de configuration et l'interface graphique de langue fonctionnent toujours.

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

## 2. Téléchargez une nouvelle version Simbioza 0.3.41

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
git clone --depth 1 --branch 0.3.41 https://github.com/kmihalj/Simbioza.git "$SIMBIOZA_FETCH_DIR/source" &&
rsync -a --exclude=.git/ "$SIMBIOZA_FETCH_DIR/source/" "$SIMBIOZA_APP_ROOT/" &&
cd "$SIMBIOZA_APP_ROOT" &&
head -n 1 VERSION &&
composer update --no-interaction --prefer-dist --with-all-dependencies --optimize-autoloader &&
composer check-platform-reqs &&
mkdir -p data/cache data/logs data/sessions data/setup-requests data/tmp
```

Attendez-vous à `0.3.41` et `success` pour chaque exigence de plate-forme. La notification **détachée HEAD** de Git est normale lors de l'extraction d'une balise de version. N'exécutez pas Composer avec `sudo`, ne copiez pas la configuration ou la base de données d'un autre site, n'incluez pas `.git/` dans l'application déployée ou n'utilisez pas `--no-dev` ici. La version 0.3.0 est une base de référence pour une nouvelle installation, et non une mise à niveau à partir de la version 0.2.x.

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

<a id="4-prepare-narrow-runtime-permissions"></a>

## 4. Préparez des autorisations d'exécution étroites

Les packages système fournissent le compte Web partagé : `www-data` sur Debian/Ubuntu, `_www` pour l'exemple Homebrew PHP-FPM. Confirmez votre utilisateur/groupe de travail FPM réel avant d'appliquer ces commandes. Le code reste la propriété de votre responsable ; le compte Web écrit uniquement les données d'exécution et les paramètres dynamiques.

Sur Debian/Ubuntu :

```bash
cd "$SIMBIOZA_APP_ROOT"
SIMBIOZA_MAINTAINER="$(id -un)"
sudo chgrp www-data config
sudo chmod 3770 config
sudo chgrp -R www-data data
sudo chmod -R g+rwX data
sudo find data -type d -exec chmod 2770 {} +
sudo setfacl -R -m u:"$SIMBIOZA_MAINTAINER":rwX,u:www-data:rwX data
sudo setfacl -m u:www-data:rwx,d:u:"$SIMBIOZA_MAINTAINER":rwx,d:u:www-data:rwx config
for SIMBIOZA_MODULE in auth email editor-html workspace menu theme backup api calendar; do
  mkdir -p "config/$SIMBIOZA_MODULE"
  sudo chgrp www-data "config/$SIMBIOZA_MODULE"
  sudo chmod 3770 "config/$SIMBIOZA_MODULE"
  sudo setfacl -m u:www-data:rwx,d:u:"$SIMBIOZA_MAINTAINER":rwx,d:u:www-data:rwx "config/$SIMBIOZA_MODULE"
done
sudo find data -type d -exec setfacl -m d:u:"$SIMBIOZA_MAINTAINER":rwx,d:u:www-data:rwx {} +
```

Sur macOS :

```bash
cd "$SIMBIOZA_APP_ROOT"
SIMBIOZA_MAINTAINER="$(id -un)"
sudo chgrp _www config
sudo chmod 3770 config
sudo chgrp -R _www data
sudo chmod -R g+rwX data
sudo find data -type d -exec chmod 2770 {} +
sudo chmod +a "user:${SIMBIOZA_MAINTAINER} allow read,write,append,execute,delete,readattr,writeattr,readextattr,writeextattr,readsecurity,file_inherit,directory_inherit" config
for SIMBIOZA_MODULE in auth email editor-html workspace menu theme backup api calendar; do
  mkdir -p "config/$SIMBIOZA_MODULE"
  sudo chgrp _www "config/$SIMBIOZA_MODULE"
  sudo chmod 3770 "config/$SIMBIOZA_MODULE"
  sudo chmod +a "user:${SIMBIOZA_MAINTAINER} allow read,write,append,execute,delete,readattr,writeattr,readextattr,writeextattr,readsecurity,file_inherit,directory_inherit" "config/$SIMBIOZA_MODULE"
done
sudo find data -type d -exec chmod +a "user:${SIMBIOZA_MAINTAINER} allow read,write,append,execute,delete,readattr,writeattr,readextattr,writeextattr,readsecurity,file_inherit,directory_inherit" {} +
```

N'accordez pas d'accès en écriture Web à la racine de la version, `src/`, `scripts/`, `public/`, `vendor/`, `composer.json` ou à la configuration de service statique PHP. Les répertoires de configuration collants permettent le remplacement atomique des paramètres appartenant au Web sans autoriser le remplacement du code de service appartenant au responsable. N’utilisez jamais `chmod 777`.

<a id="5-configure-an-ordinary-php-fpm-pool"></a>

## 5. Configurez un pool PHP-FPM ordinaire

Ce mode utilise le compte Web installé par le système d'exploitation, et non une identité de déploiement Simbioza dédiée. Le responsable possède le code et le compte PHP partagé n'a que l'accès d'exécution restreint de l'étape 4. N'exécutez pas `configure_fpm_setup.php --install` pour ce mode : qui sélectionne l'architecture sécurisée distincte.

Sur Debian/Ubuntu, créez un fichier de pool pour la série PHP installée :

```bash
SIMBIOZA_PHP_SERIES="$(php -r 'echo PHP_MAJOR_VERSION, ".", PHP_MINOR_VERSION;')"
sudoedit "/etc/php/$SIMBIOZA_PHP_SERIES/fpm/pool.d/simbioza.conf"
```

Sous macOS, créez un fichier de pool dans le répertoire de configuration de Homebrew :

```bash
SIMBIOZA_PHP_SERIES="$(php -r 'echo PHP_MAJOR_VERSION, ".", PHP_MINOR_VERSION;')"
sudoedit "/opt/homebrew/etc/php/$SIMBIOZA_PHP_SERIES/php-fpm.d/simbioza.conf"
```

Collez le **pool complet** ci-dessous. Utilisez `www-data` sur Debian/Ubuntu et `_www` sur macOS. Remplacez les deux chemins `/srv/simbioza` par la racine réelle de votre application ; PHP-FPM ne développe pas ici les variables de terminal.

```ini
[simbioza]
user = www-data
group = www-data
listen = 127.0.0.1:9077
listen.allowed_clients = 127.0.0.1
pm = ondemand
pm.max_children = 4
pm.process_idle_timeout = 10s
pm.max_requests = 500
clear_env = yes
security.limit_extensions = .php
env[TMPDIR] = /srv/simbioza/data/tmp
php_admin_value[session.save_path] = /srv/simbioza/data/sessions
php_admin_value[max_execution_time] = 900
php_admin_flag[display_errors] = off
```

Vérifiez et démarrez le service FPM installé. Sur Debian/Ubuntu :

```bash
sudo "php-fpm$SIMBIOZA_PHP_SERIES" -tt
sudo systemctl enable --now "php$SIMBIOZA_PHP_SERIES-fpm"
sudo systemctl reload "php$SIMBIOZA_PHP_SERIES-fpm"
```

Sur macOS :

```bash
sudo /opt/homebrew/sbin/php-fpm -tt
sudo /opt/homebrew/bin/brew services start php
```

Attendez-vous à un test de configuration réussi. Le maître FPM doit être démarré par le système/racine afin qu'il puisse exécuter les nœuds de calcul sous le nom `_www` ; un service Homebrew appartenant à l'utilisateur ne peut pas transférer d'identité vers un compte Web non lié. L'écouteur doit rester en boucle uniquement. Pour un autre site, choisissez un nom de pool et un port différents plutôt que de réutiliser celui-ci.

<a id="6-configure-the-web-server"></a>

## 6. Configurez le serveur Web

Choisissez **une** de ces configurations. Ils servent un nom d'hôte dédié à `/` ; remplacez le nom d'hôte, le chemin de version et les paramètres du certificat TLS. Pour une installation dans un sous-répertoire, configurez également le préfixe d'URL sur le serveur Web et transmettez cette URL complète à `install:prepare` à l'étape 8.

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
            SetHandler "proxy:fcgi://127.0.0.1:9077"
        </FilesMatch>
    </Directory>

    SSLEngine on
    SSLCertificateFile /path/to/fullchain.pem
    SSLCertificateKeyFile /path/to/privkey.pem
</VirtualHost>
```

Testez la configuration Apache avant un rechargement progressif. N'exposez pas le répertoire de la version parent en tant que racine du document.

Après avoir enregistré, testez et démarrez Apache. Sur Debian/Ubuntu :

```bash
sudo a2ensite simbioza
sudo apache2ctl configtest
sudo systemctl enable --now apache2
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
        fastcgi_pass 127.0.0.1:9077;
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

Testez la configuration Nginx avant de recharger. L'écouteur FPM ne doit pas être accessible à partir d'un réseau non approuvé. La préparation du package via l'installateur graphique peut prendre plusieurs minutes ; l'attente de la passerelle Apache/Nginx de 900 secondes empêche un 504 par défaut de 60 secondes pendant que le travailleur restreint est toujours en cours d'exécution. Ajustez tout délai d'expiration supplémentaire du proxy inverse en amont en conséquence, mais ne désactivez pas les limites de requêtes FPM ou du serveur Web pour les sites non liés. Le pool ordinaire illustré ci-dessus définit également le `max_execution_time` de PHP sur 900 secondes. C'est important : sur la version testée de macOS/PHP 8.5, une valeur de `0` mettait toujours fin à une longue requête après une minute, même si cela ne devrait signifier aucune limite. Vérifiez les deux paramètres de délai d'attente dans le pool généré et testez une préparation de package plus longue.

<a id="optional-install-under-a-url-prefix-such-as-simbioza"></a>

### Facultatif : installer sous un préfixe d'URL tel que /simbioza

Pour un nom d'hôte partagé, placez ce bloc **à l'intérieur de son hôte virtuel HTTPS** existant, au lieu de créer un autre hôte virtuel portant le même nom. Remplacez le chemin de version, le préfixe d'URL et (pour FPM) le port du pool :

```apache
Alias /simbioza "/srv/simbioza/public"
<Directory "/srv/simbioza/public">
    Options -Indexes +FollowSymLinks
    AllowOverride FileInfo Indexes Options
    Require all granted
    DirectoryIndex index.php
    <FilesMatch "\.php$">
        SetHandler "proxy:fcgi://127.0.0.1:9077"
    </FilesMatch>
</Directory>
```

Utilisez `--base-url=https://your-host.example/simbioza` pour le programme d'installation, pas le nom d'hôte sans son préfixe. Pour une installation sur `/wiki`, remplacez les **deux** occurrences du préfixe et utilisez `--base-url=https://your-host.example/wiki`. Le chemin du répertoire est un chemin du système de fichiers ; le préfixe est un chemin URL, pas le nom du répertoire d'application. Conservez les `config/` et `data/` privés en dehors de l'alias exposé. Validez Apache avant son rechargement gracieux.

<a id="7-prepare-optional-packages-in-the-cli"></a>

## 7. Préparez les packages facultatifs dans le CLI

Le programme d'installation graphique ne peut pas exécuter Composer en tant que compte Web FPM partagé. À partir du répertoire de version, le propriétaire du code prépare les modules optionnels. Sans liste explicite, la commande prépare **tous** les modules facultatifs car l'assistant les sélectionne tous par défaut :

```bash
cd "$SIMBIOZA_APP_ROOT"
php scripts/installation_packages.php prepare
```

Pour un ensemble plus restreint de fonctionnalités supplémentaires, telles que Calendrier et E-mail, préparez ces modules et désélectionnez les autres modules facultatifs dans l'assistant :

```bash
php scripts/installation_packages.php prepare --modules=calendar,email
php scripts/installation_packages.php status
```

Pour n'installer aucun module facultatif, utilisez `php scripts/installation_packages.php prepare --modules=` et désélectionnez chaque module facultatif dans l'assistant. **Auth, ORM, Menu, Thème, Sauvegarde et Audit** sont requis dans chaque installation et restent installés et activés. Ne les répertoriez pas dans `--modules`, qui n'accepte que les modules facultatifs.

La commande `status` indique quels packages sont réellement installés. L'assistant marque un package manquant pour la préparation CLI ; rechargez ensuite la page. N'accordez pas au compte Web un accès en écriture à `vendor/` ou `composer.json` pour contourner cette limite.

<a id="8-run-the-graphical-installer"></a>

## 8. Exécutez le programme d'installation graphique

Créez l'URL du programme d'installation à usage unique :

```bash
bin/simbioza install:prepare --base-url=https://simbioza.example.org
```

Incluez le sous-répertoire dans `--base-url` le cas échéant. Ouvrez l'URL du jeton dans une fenêtre de navigateur privée, sans la partager ni la capturer. L'assistant vérifie les exigences et l'accès à la base de données, demande l'identité du site, au moins une langue, le fuseau horaire, le premier administrateur et les modules facultatifs (tous sélectionnés par défaut), puis affiche un aperçu avant l'installation. Il importe les langues du guide utilisateur sélectionnées.

L'assistant crée des fichiers privés en tant que compte Web. Avant la première commande CLI du responsable, finalisez l'accès partagé uniquement à ses fichiers d'exécution ; cela active également les masques Linux ACL hérités sur les fichiers initialement créés avec le mode `0600`. Le code de service statique PHP n’est pas modifié :

```bash
cd "$SIMBIOZA_APP_ROOT"
sudo find data -type f -exec chmod 0660 {} +
sudo find config -type f \( -name '*.json' -o -name '*.lock' \) -exec chmod 0660 {} +
for SIMBIOZA_FILE in database.php env.php installation.php modules.php languages.php; do
  if [ -f "config/$SIMBIOZA_FILE" ]; then
    sudo chmod 0660 "config/$SIMBIOZA_FILE"
  fi
done
```

Une fois l'installation réussie, supprimez l'enregistrement de préparation terminé. `cleanup` ne désinstalle pas les packages ni ne supprime les données. Les six modules requis, y compris la sauvegarde, restent installés :

```bash
php scripts/installation_packages.php cleanup
vendor/bin/hph modules migrate-status
composer check-platform-reqs
```

Attendez-vous à aucune migration en attente, à une connexion et à des pages d'accueil réussies et à une route `/install` verrouillée. Sauvegardez la base de données, `config/`, les fichiers utilisateur et les données du thème avant la mise en ligne.

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
