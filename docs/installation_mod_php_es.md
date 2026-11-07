# Instalar Simbioza con Apache mod_php

<a id="install-simbioza-with-apache-mod_php"></a>


> Las capturas de pantalla muestran la interfaz en inglés.

[Versión croata](installation_mod_php_hr.md)

Esta es una guía completa de instalación nueva, desde una terminal normal hasta el primer inicio de sesión de administrador. No se requiere ninguna otra guía de instalación. Apache ejecuta PHP dentro de su proceso web compartido; las operaciones de paquetes y las actualizaciones de aplicaciones siguen siendo CLI únicamente. Nginx no puede usar mod_php. Nunca haga que el código de la aplicación se pueda escribir en la web para obtener la instalación del paquete GUI.

<a id="1-prepare-the-host"></a>

## 1. Prepara el servidor

Comience con un shell de administrador normal en Linux o macOS. Necesita un dominio que apunte al servidor y un certificado TLS válido para los ejemplos HTTPS. Ejecute solo los comandos para su sistema operativo. No continúe después de un error.

En Debian/Ubuntu, instale las herramientas y las extensiones PHP:

```bash
sudo apt-get update
sudo apt-get install git rsync composer acl apache2 php-cli php-curl php-mbstring php-xml php-zip php-intl php-sqlite3 php-mysql php-pgsql libapache2-mod-php
```

En macOS, instale Homebrew primero si no está instalado y luego:

```bash
brew install git rsync composer httpd php
```

Verifique las herramientas instaladas:

```bash
php -v
php -m
composer --version
git --version
```

PHP debe ser **8.2 o posterior**, Composer debe ser la versión 2 y PHP necesita `ctype dom fileinfo intl json libxml mbstring openssl pdo session xmlreader zip`. Utilice `pdo_sqlite`, `pdo_mysql` o `pdo_pgsql` para la base de datos elegida. CLI PHP y el controlador web PHP deben tener versiones y extensiones coincidentes.

<a id="2-download-a-clean-simbioza-0339-release"></a>

## 2. Descargue una versión limpia Simbioza 0.3.44

Elija **un** directorio de aplicaciones. En Linux:

```bash
SIMBIOZA_APP_ROOT=/srv/simbioza
```

En macOS, use el disco interno, no una copia de desarrollo o un volumen montado con `noowners`:

```bash
SIMBIOZA_APP_ROOT=/Users/Shared/Simbioza/simbioza
```

Mantenga este terminal abierto. Cambie la ruta elegida ahora si necesita otro sitio. El directorio **no debe existir todavía**; esta verificación se niega deliberadamente a sobrescribir una instalación existente. Un administrador que reinstale un sitio primero debe manejar su servicio y datos antiguos por separado. Nada aquí los borra.

Ejecute el bloque de descarga completo como su cuenta de mantenimiento normal:

```bash
test ! -e "$SIMBIOZA_APP_ROOT" && test ! -L "$SIMBIOZA_APP_ROOT" &&
sudo install -d -o "$(id -un)" -g "$(id -gn)" -m 0755 "$SIMBIOZA_APP_ROOT" &&
SIMBIOZA_FETCH_DIR="$(mktemp -d)" &&
git clone --depth 1 --branch 0.3.44 https://github.com/kmihalj/Simbioza.git "$SIMBIOZA_FETCH_DIR/source" &&
rsync -a --exclude=.git/ "$SIMBIOZA_FETCH_DIR/source/" "$SIMBIOZA_APP_ROOT/" &&
cd "$SIMBIOZA_APP_ROOT" &&
head -n 1 VERSION &&
composer update --no-interaction --prefer-dist --with-all-dependencies --optimize-autoloader &&
composer check-platform-reqs &&
mkdir -p data/cache data/logs data/sessions data/setup-requests data/tmp
```

Espere `0.3.44` y `success` para cada requisito de plataforma. El aviso **detached HEAD** de Git es normal cuando se revisa una etiqueta de lanzamiento. No ejecute Composer con `sudo`, no copie la configuración o base de datos de otro sitio, no incluya `.git/` en la aplicación implementada ni use `--no-dev` aquí. La versión 0.3.0 es una base de instalación nueva, no una actualización de 0.2.x.

La raíz de configuración predeterminada es `config/` (`HPH_CONFIG_PATH`), con un subdirectorio para cada módulo. Los archivos persistentes pertenecen a `data/`. El servidor web solo expondrá `public/`. Mantenga las rutas predeterminadas para esta primera instalación; Las capas de configuración externas son una opción avanzada, no un requisito previo.

<a id="3-choose-and-prepare-the-database"></a>

## 3. Elija y prepara la base de datos.

Elija **una** base de datos. SQLite es la ruta más corta y no necesita servidor de base de datos ni contraseña de base de datos: seleccione SQLite en el asistente y creará `data/simbioza.sqlite`. Continúe con el paso 4.

Si eliges una base de datos de servidor y aún no tiene su servicio, instala **solo la que elegiste**. En Debian/Ubuntu: `sudo apt-get install mariadb-server` o `sudo apt-get install postgresql`. En macOS: `brew install mariadb`, luego `brew services start mariadb`, o `brew install postgresql@18`, luego `brew services start postgresql@18`. Para las herramientas versionadas Homebrew PostgreSQL, utilice `/opt/homebrew/opt/postgresql@18/bin/createuser` y `/opt/homebrew/opt/postgresql@18/bin/createdb` a continuación.

Para **MySQL**, inicie su servicio MySQL/MariaDB e ingrese su cliente administrador. Cree una base de datos vacía y una cuenta de aplicación restringida:

```sql
CREATE DATABASE simbioza CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'simbioza'@'127.0.0.1' IDENTIFIED BY '<unique-database-password>';
GRANT ALL PRIVILEGES ON simbioza.* TO 'simbioza'@'127.0.0.1';
```

Utilice el host `127.0.0.1`, el puerto de su servicio (normalmente `3306`), la base de datos `simbioza`, el usuario `simbioza` y esa contraseña en el asistente. No utilice el administrador de la base de datos como cuenta de la aplicación.

Para **PostgreSQL**, inicie su servicio y ejecute estos comandos como administrador PostgreSQL (en Debian/Ubuntu, anteponga cada uno con `sudo -u postgres`):

```bash
createuser --pwprompt --no-superuser --no-createdb --no-createrole simbioza
createdb --owner=simbioza --encoding=UTF8 simbioza
```

Utilice el host `127.0.0.1`, el puerto de su servicio (normalmente `5432`), la base de datos y el usuario `simbioza`, y la contraseña ingresada en el mensaje privado. Verifique que la autenticación TCP local de la base de datos permita esa cuenta. No coloque contraseñas reales en la documentación, el historial de comandos o Git.

<a id="4-prepare-narrow-runtime-permissions"></a>

## 4. Prepare permisos de tiempo de ejecución limitados

Los paquetes del sistema proporcionan la cuenta web compartida: `www-data` en Debian/Ubuntu, `_www` para el ejemplo Homebrew de Apache. Confirme su usuario/grupo de Apache real antes de aplicar estos comandos. El código sigue siendo propiedad de su mantenedor; la cuenta web escribe solo datos de tiempo de ejecución y configuraciones dinámicas.

En Debian/Ubuntu:

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

En MacOS:

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

No otorgue acceso de escritura web a la raíz de la versión, `src/`, `scripts/`, `public/`, `vendor/`, `composer.json` o la configuración del servicio estático PHP. Los directorios de configuración fijos permiten el reemplazo atómico de configuraciones propiedad de la web sin permitir el reemplazo del código de servicio propiedad del mantenedor. Nunca utilice `chmod 777`.

<a id="5-confirm-that-apache-actually-runs-mod_php"></a>

## 5. Confirme que Apache realmente ejecuta mod_php

La versión CLI PHP por sí sola no es prueba de que Apache use el mismo PHP. Inspeccione los módulos y el controlador de Apache activos; verifique que PHP en un navegador utilice una versión compatible y las extensiones requeridas. La compilación habitual `mod_php` no segura para subprocesos requiere el `prefork` MPM de Apache. No habilite tanto el controlador de proxy FPM como el controlador `mod_php` para este sitio.

En Debian/Ubuntu, habilite el controlador PHP sin subprocesos y los módulos necesarios:

```bash
sudo a2dismod mpm_event
sudo a2enmod mpm_prefork rewrite ssl
sudo a2enmod "php$(php -r 'echo PHP_MAJOR_VERSION, ".", PHP_MINOR_VERSION;')"
sudoedit /etc/apache2/sites-available/simbioza.conf
```

En macOS, edite `/opt/homebrew/etc/httpd/httpd.conf` con `sudoedit`. Habilite `mpm_prefork_module`, `rewrite_module`, `ssl_module` y `socache_shmcb_module`; deshabilite `mpm_event_module`. Agregue `Listen 443` si ninguna directiva ya escucha allí, luego agregue:

```apache
LoadModule php_module /opt/homebrew/opt/php/lib/httpd/modules/libphp.so
```

Para un nuevo Apache Homebrew, establezca explícitamente la identidad del trabajador compartido en `httpd.conf` (no ejecute trabajadores web como su inicio de sesión de propietario de código):

```apache
User _www
Group _www
```

Utilice el siguiente host virtual en el archivo de configuración seleccionado. Reemplace las rutas de dominio y certificado y reemplace **ambas** rutas de aplicación con el `SIMBIOZA_APP_ROOT/public` elegido. La configuración de Apache no expande sus variables de shell.

```apache
<VirtualHost *:443>
    ServerName simbioza.example.org
    DocumentRoot /srv/simbioza/public

    <Directory /srv/simbioza/public>
        Options -Indexes +FollowSymLinks
        AllowOverride FileInfo Indexes Options
        Require all granted
        DirectoryIndex index.php

        <FilesMatch "\.php$">
            SetHandler application/x-httpd-php
        </FilesMatch>
    </Directory>

    SSLEngine on
    SSLCertificateFile /path/to/fullchain.pem
    SSLCertificateKeyFile /path/to/privkey.pem
</VirtualHost>
```

La directiva `LoadModule` del módulo PHP es específica de la distribución y debe apuntar al módulo instalado real. Pruebe la configuración de Apache antes de una recarga elegante. Para una instalación de subdirectorio, configure su alias de URL y use esa URL base completa en el paso 7.

Pruebe e inicie solo este servicio Apache configurado. En Debian/Ubuntu:

```bash
sudo a2ensite simbioza
sudo apache2ctl configtest
sudo systemctl enable --now apache2
sudo systemctl reload apache2
```

En MacOS:

```bash
sudo /opt/homebrew/bin/httpd -t
sudo /opt/homebrew/bin/brew services start httpd
```

Espere `Syntax OK`. Si el servicio ya existe, utilice una recarga elegante validada en lugar de reiniciar sitios no relacionados.

<a id="optional-install-under-a-url-prefix-such-as-simbioza"></a>

### Opcional: instalar bajo un prefijo de URL como /simbioza

Para un nombre de host compartido, coloque este bloque **dentro de su host virtual HTTPS** existente, en lugar de crear otro host virtual con el mismo nombre. Reemplace la ruta de lanzamiento, el prefijo de URL y (para FPM) el puerto del grupo:

```apache
Alias /simbioza "/srv/simbioza/public"
<Directory "/srv/simbioza/public">
    Options -Indexes +FollowSymLinks
    AllowOverride FileInfo Indexes Options
    Require all granted
    DirectoryIndex index.php
    <FilesMatch "\.php$">
        SetHandler "application/x-httpd-php"
    </FilesMatch>
</Directory>
```

Utilice `--base-url=https://your-host.example/simbioza` para el instalador, no el nombre de host sin su prefijo. Para una instalación en `/wiki`, reemplace **ambas** apariciones del prefijo y use `--base-url=https://your-host.example/wiki`. La ruta del directorio es una ruta del sistema de archivos; el prefijo es una ruta URL, no el nombre del directorio de la aplicación. Mantenga privados `config/` y `data/` fuera del alias expuesto. Valide Apache antes de su recarga elegante.

<a id="6-prepare-optional-packages-in-the-cli"></a>

## 6. Prepare paquetes opcionales en CLI

El instalador gráfico no puede ejecutar Composer como cuenta web de Apache. Desde el directorio de lanzamiento, el propietario del código prepara módulos opcionales. Sin una lista explícita, el comando prepara **todos** los módulos opcionales porque el asistente los selecciona todos de forma predeterminada:

```bash
cd "$SIMBIOZA_APP_ROOT"
php scripts/installation_packages.php prepare
```

Para un conjunto más pequeño de funciones adicionales, como Calendario y Correo electrónico, prepare esos módulos y anule la selección de los demás módulos opcionales en el asistente:

```bash
php scripts/installation_packages.php prepare --modules=calendar,email
php scripts/installation_packages.php status
```

Para no instalar módulos opcionales, use `php scripts/installation_packages.php prepare --modules=` y anule la selección de todos los módulos opcionales en el asistente. **Se requieren autenticación, ORM, Menú, Tema, Copia de seguridad y Auditoría** en cada instalación y permanecen instalados y habilitados. No los incluya en `--modules`, que solo acepta módulos opcionales.

El comando `status` muestra qué paquetes están realmente instalados. El asistente marca un paquete faltante para la preparación de CLI; recarga la página después. No otorgue a la cuenta web acceso de escritura a `vendor/` o `composer.json` para evitar este límite.

<a id="7-run-the-graphical-installer"></a>

## 7. Ejecute el instalador gráfico.

Cree la URL del instalador de un solo uso:

```bash
bin/simbioza install:prepare --base-url=https://simbioza.example.org
```

Incluya el subdirectorio en `--base-url` cuando corresponda. Abra la URL del token en una ventana privada del navegador, sin compartirla ni hacer capturas de pantalla. El asistente verifica los requisitos y el acceso a la base de datos, solicita la identidad del sitio, al menos un idioma, zona horaria, primer administrador y módulos opcionales (todos seleccionados de forma predeterminada) y luego muestra una revisión antes de la instalación. Importa los idiomas seleccionados de la guía del usuario.

El asistente crea archivos privados como la cuenta web. Antes del primer comando del mantenedor CLI, finalice el acceso compartido solo a sus archivos de tiempo de ejecución; esto también activa las máscaras heredadas de Linux ACL en archivos creados inicialmente con el modo `0600`. El código de servicio estático PHP no se modifica:

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

Después de una instalación exitosa, elimine el registro de preparación completo. `cleanup` no desinstala paquetes ni elimina datos. Los seis módulos necesarios, incluida la copia de seguridad, permanecen instalados:

```bash
php scripts/installation_packages.php cleanup
vendor/bin/hph modules migrate-status
composer check-platform-reqs
```

Espere cero migraciones pendientes, inicio de sesión y páginas de inicio exitosos y una ruta `/install` bloqueada. Haga una copia de seguridad de la base de datos, `config/`, los archivos de usuario y los datos del tema antes de publicarlo.

<a id="maintenance-after-installation"></a>

## Mantenimiento después de la instalación

Mantenga los comandos de rutina CLI en la cuenta del propietario del código, nunca en la cuenta web ni en la cuenta raíz. Haga una copia de seguridad de la base de datos, los directorios activos `HPH_CONFIG_PATH` y los persistentes `data/` antes de una actualización.

Comprobaciones de sólo lectura:

```bash
cd "$SIMBIOZA_APP_ROOT"
vendor/bin/hph modules list
vendor/bin/hph languages available
php update.php --check
```

Para agregar un módulo más adelante, ejecute `vendor/bin/hph modules add calendar --fresh`; `modules disable calendar` conserva sus datos y `modules enable calendar` los habilita nuevamente. La eliminación utiliza `modules remove calendar --yes` y crea una copia de seguridad del módulo; inspecciónelo con `modules backups calendar` antes de un `modules add calendar --restore` deliberado. Son alternativas, no un bloque para ejecutar uno tras otro.

Los paquetes de idiomas son independientes de las versiones de la aplicación. Utilice `vendor/bin/hph languages install de` o `vendor/bin/hph languages update`; La GUI del lenguaje también funciona cuando se pueden escribir en sus directorios de ejecución.

Cuando desee actualizar la aplicación, utilice `php update.php` como propietario/mantenedor de implementación del código. El actualizador se actualiza primero, conserva los módulos seleccionados y la configuración privada, aplica migraciones y actualiza las guías incluidas. No sustituya un `composer update` independiente en un sitio instalado. Antes de las migraciones, una actualización fallida puede revertir el código; Después de que comienzan las migraciones, el mantenimiento permanece habilitado para una recuperación controlada. Nunca elimine `data/update-maintenance.json` para ocultar una actualización sin terminar.

Luego verifique cero migraciones pendientes, inicio de sesión real, contenido y archivos adjuntos. Los ejemplos utilizan las rutas predeterminadas `config/` y `data/`; Si luego elige capas de configuración externas, mantenga explícitos sus permisos y cobertura de respaldo.
