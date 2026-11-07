# Instalar Simbioza con PHP-FPM protegido

<a id="install-simbioza-with-secured-php-fpm"></a>


> Las capturas de pantalla muestran la interfaz en inglés.

[Versión croata](installation_fpm_secured_hr.md)

Esta guía completa comienza con un directorio de aplicaciones vacío y asume que aún no existen cuentas Simbioza FPM/deploy. No necesitas otra guía de instalación. La herramienta de configuración crea cuentas dedicadas, grupos, un grupo/servicio y un asistente restringido: el proceso web no puede escribir código, pero puede solicitar operaciones de paquetes desde el trabajador de implementación independiente. Este es el modo recomendado. Para la reinstalación, un administrador debe primero inspeccionar las identidades, nombres de instancias y servicios existentes; nunca cree un segundo sitio con la misma identidad.

**SimpleSAMLphp es opcional.** El inicio de sesión local Simbioza, OIDC, CAS y OAuth2 no lo requieren. Complete los pasos 1 a 7 primero. Siga el tutorial opcional SAML solo si esta instalación en particular necesita autenticación SAML.

<a id="1-prepare-the-host"></a>

## 1. Prepara el servidor

Comience con un shell de administrador normal en Linux o macOS. Necesita un dominio que apunte al servidor y un certificado TLS válido para los ejemplos HTTPS. Ejecute solo los comandos para su sistema operativo. No continúe después de un error.

En Debian/Ubuntu, instale las herramientas y las extensiones PHP:

```bash
sudo apt-get update
sudo apt-get install git rsync composer acl apache2 php-cli php-curl php-mbstring php-xml php-zip php-intl php-sqlite3 php-mysql php-pgsql php-fpm
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

## 2. Descargue una versión limpia Simbioza 0.3.43

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
git clone --depth 1 --branch 0.3.43 https://github.com/kmihalj/Simbioza.git "$SIMBIOZA_FETCH_DIR/source" &&
rsync -a --exclude=.git/ "$SIMBIOZA_FETCH_DIR/source/" "$SIMBIOZA_APP_ROOT/" &&
cd "$SIMBIOZA_APP_ROOT" &&
head -n 1 VERSION &&
composer update --no-interaction --prefer-dist --with-all-dependencies --optimize-autoloader &&
composer check-platform-reqs &&
mkdir -p data/cache data/logs data/sessions data/setup-requests data/tmp
```

Espere `0.3.43` y `success` para cada requisito de plataforma. El aviso **detached HEAD** de Git es normal cuando se revisa una etiqueta de lanzamiento. No ejecute Composer con `sudo`, no copie la configuración o base de datos de otro sitio, no incluya `.git/` en la aplicación implementada ni use `--no-dev` aquí. La versión 0.3.0 es una base de instalación nueva, no una actualización de 0.2.x.

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

<a id="4-create-the-dedicated-pool"></a>

## 4. Crea el grupo dedicado

En Debian, instale el paquete PHP-FPM para la misma versión PHP utilizada por el paquete CLI y `acl`. Desde el directorio de lanzamiento, ejecute el paso único del sistema:

```bash
cd "$SIMBIOZA_APP_ROOT"
sudo php scripts/configure_fpm_setup.php --install \
  --app-root="$SIMBIOZA_APP_ROOT" --maintainer="$(id -un)" &&
sudo -H -u fpm-simbioza php scripts/configure_fpm_setup.php --check \
  --app-root="$SIMBIOZA_APP_ROOT" --maintainer="$(id -un)"
```

En macOS con Homebrew PHP, use la ruta de aplicación real y el binario FPM:

```bash
cd "$SIMBIOZA_APP_ROOT"
sudo php scripts/configure_fpm_setup.php --install \
  --app-root="$SIMBIOZA_APP_ROOT" \
  --maintainer="$(id -un)" --php-fpm="$(brew --prefix)/sbin/php-fpm" &&
sudo -H -u fpm-simbioza php scripts/configure_fpm_setup.php --check \
  --app-root="$SIMBIOZA_APP_ROOT" \
  --maintainer="$(id -un)" --php-fpm="$(brew --prefix)/sbin/php-fpm"
```

Para un segundo sitio, repita ambos comandos con opciones coincidentes, por ejemplo `--instance=second --listen=127.0.0.1:9076`. Utilice las mismas dos opciones nuevamente con `--finalize` en el paso 7. No las omita accidentalmente: la instancia predeterminada sin sufijo es un servicio del sistema diferente.

<a id="what-step-4-generates-and-starts"></a>

### Qué genera y comienza el paso 4

**No cree ni complete `php-fpm.conf` manualmente.** El comando `--install` anterior escribe la configuración FPM dedicada completa, prueba su sintaxis, registra el servicio y lo inicia/reinicia. El siguiente comando `--check` no cambia la configuración: crea y elimina solicitudes de diagnóstico de corta duración a través del asistente real. Verifica tanto el viaje de ida y vuelta del asistente como la compatibilidad con CLI para actualizaciones en segundo plano. Una verificación fallida devuelve un estado de salida distinto de cero. Espere `[OK] Real Setup helper round trip` y `[OK] Background updater CLI runtime`; no continúe con Apache o el asistente si alguno de ellos falta o falla.

La verificación explícita `sudo -H -u fpm-simbioza` utiliza la cuenta de tiempo de ejecución recién creada inmediatamente, sin depender de que su sesión SSH actual haya actualizado la membresía del grupo. Para una instancia con nombre, utilice también la cuenta de tiempo de ejecución de esa instancia, por ejemplo `fpm-simbioza-second`. El asistente sigue siendo propiedad de la raíz y está restringido a acciones aprobadas; No otorgue permisos de escritura de código al proceso web ni desactive la zona de pruebas del sistema de archivos.

En Linux, la instancia predeterminada usa `/etc/php/<major.minor>/php-fpm-simbioza/php-fpm.conf`, donde la versión coincide con la CLI PHP utilizada en el paso 4. Con PHP 8.4, el archivo exacto es `/etc/php/8.4/php-fpm-simbioza/php-fpm.conf`. Contiene tanto `[global]` como el grupo dedicado `[simbioza]`: sus cuentas, escucha local, aplicación y rutas `HPH_CONFIG_PATH`, directorios temporales/de sesión privada y límites PHP. Este **no** es el `/etc/php/8.4/fpm/php-fpm.conf` o `pool.d/www.conf` compartido. El generador también escribe `php-fpm-simbioza.service`, incluido el aislamiento del sistema de archivos y la máscara de permisos. Esas configuraciones de systemd no son entradas de FPM INI. Para inspeccionar qué configuración utiliza el servicio dedicado, sin cambiarla:

```bash
sudo systemctl show php-fpm-simbioza.service -p ExecStart &&
sudo systemctl is-active php-fpm-simbioza.service
```

Para una instancia con nombre, el directorio de configuración y el nombre del servicio reciben el sufijo coincidente, por ejemplo `php-fpm-simbioza-second`. En macOS, la herramienta crea `$(brew --prefix)/etc/simbioza/php-fpm.conf`, que incluye `pool.conf` en el mismo directorio, y registra el servicio de lanzamiento dedicado. No reemplace un archivo generado correctamente con la configuración de otro sitio. La configuración SimpleSAMLphp se agrega solo si sigue los pasos opcionales SAML. El paso 5 todavía configura Apache/Nginx por separado; generar FPM no configura el servidor web. No es necesario reiniciar FPM manual adicional después de un paso 4 exitoso y sin cambios.

Los grupos electrógenos 0.3.4 lanzaron `Umask` al número entero **7** (octal `0007`), coincidiendo con el `UMask=0007` de Linux. Tanto el proceso web como el trabajador de implementación restringida utilizan esta máscara; el trabajador lo configura **después** de cambiar de cuenta. Su `TMPDIR` es el `data/tmp` de esta aplicación, por lo que los bloqueos temporales compartidos permanecen en el mismo directorio privado del grupo de ejecución. Los nuevos archivos de registro ordinarios son `0660`, no archivos `0644` propiedad de FPM a los que el actualizador no puede agregar.

En macOS, verifique el servicio generado antes de abrir el asistente:

```bash
/usr/libexec/PlistBuddy -c 'Print :Umask' /Library/LaunchDaemons/hr.simbioza.php-fpm.plist | grep -qx 7
```

Para una instancia con nombre, utilice su archivo de servicio coincidente, por ejemplo `hr.simbioza-second.php-fpm.plist`. Una máscara faltante es un prerrequisito fallido, no una razón para hacer que el código se pueda escribir o ejecutar el actualizador como root. Los archivos de sesión permanecen privados (`0600`); no haga que todos los datos sean escribibles de forma recursiva.

La verificación no cambia la configuración de la aplicación. El grupo predeterminado escucha en `127.0.0.1:9075`; mantenga ese puerto local para el servidor. La herramienta otorga a la cuenta FPM acceso de escritura a los datos del tiempo de ejecución, no a toda la versión, e instala un asistente restringido para cambios de paquetes. También crea los grupos de implementación y ejecución. Verifique todos los resultados de diagnóstico antes de continuar. No utilice `chmod 777`.

<a id="accounts-groups-and-permissions-created-by-the-tool"></a>

### Cuentas, grupos y permisos creados por la herramienta

El comando `--install` anterior incluye la creación de cuentas/grupos; no ejecute por separado `useradd` o `groupadd`. Se crean las identidades faltantes, mientras que las identidades existentes con estos nombres se reutilizan sin reemplazar su UID/GID. La herramienta garantiza los grupos primarios requeridos y membresías complementarias. Para una instancia con nombre, todos los nombres siguientes reciben el mismo sufijo `-instance`.

Al reinstalar en la **misma ruta de la aplicación**, siga ejecutando el `--install` del paso 4: también actualiza el asistente de propiedad raíz fuera del directorio de la aplicación. Eliminar archivos de la aplicación no elimina ese asistente. Las cuentas/grupos existentes se reutilizan; no hay recreación de cuenta manual. Se puede conservar una configuración de Apache existente si coincide con el paso 5, incluida la ruta pública real, el controlador correcto y las clases `AllowOverride`.

| Identidad | Finalidad |
| --- | --- |
| `fpm-simbioza` / `app-simbioza` | Cuenta de servicio web y grupo principal bloqueados; No puedo escribir el código de la aplicación. |
| `simbioza-deploy` / `deploy-simbioza` | Cuenta de implementación bloqueada y grupo de mantenimiento de código; su cuenta de mantenedor también se une a este grupo. |
| `run-simbioza` | Grupo de ejecución privado compartido para FPM, implementación y mantenedor. |

En Linux, inspeccione el resultado después de volver a conectarse a SSH:

```bash
getent passwd fpm-simbioza simbioza-deploy &&
getent group app-simbioza deploy-simbioza run-simbioza &&
id "$(id -un)" &&
getfacl -p "$SIMBIOZA_APP_ROOT/config" "$SIMBIOZA_APP_ROOT/data"
```

El código utiliza un propietario de despliegue y un entorno FPM de solo lectura. Los directorios privados de ejecución usan el grupo `run-simbioza` (`2770`), y la raíz de configuración usa un directorio con sticky bit (`3770`) para permitir actualizaciones atómicas controladas. La ejecución normal comparte estos permisos de grupo, sin conceder permisos generales de escritura mediante ACL. Con la configuración privada opcional de SimpleSAMLphp, la herramienta también configura las ACL de acceso y predeterminadas de Linux para conceder a `app-simbioza` **solo lectura y acceso a los directorios** de esa configuración. La guía de SAML siguiente incluye la configuración y la comprobación de ACL. No aplique a Secured FPM las instrucciones de FPM ordinario o compartido para conceder escritura a `www-data` mediante ACL.

Con el directorio de configuración de la aplicación predeterminado, Auth, Email, HTML Editor, Workspace, Menu, Theme y Backup tienen directorios de módulos que se pueden escribir en tiempo de ejecución. La copia de seguridad necesita esto para la publicación del descriptor del proveedor atómico. La configuración del servicio PHP administrada por la versión sigue siendo propiedad de la implementación y de solo lectura en FPM. La herramienta no recrea `data/config` ni otorga derechos de tiempo de ejecución para archivos heredados `editor-html.php`, `email.php` o `workspace.php`.

<a id="5-configure-the-web-server"></a>

## 5. Configurar el servidor web

**Elija la ruta antes de ejecutar los comandos:**

- **Host virtual HTTPS existente y un prefijo de URL como `/simbioza`:** vaya directamente a [las instrucciones del prefijo de URL](#optional-install-under-a-url-prefix-such-as-simbioza). Edite ese vhost existente. No cree `simbioza.conf` ni ejecute `a2ensite simbioza`; ese comando habilita solo un vhost separado recién creado.
- **Un nuevo nombre de host dedicado:** use la configuración de Apache o Nginx a continuación.

Elija **una** de estas configuraciones. Ofrecen un nombre de host dedicado en `/`; reemplace el nombre de host, la ruta de lanzamiento y la configuración del certificado TLS. Para una instalación en un subdirectorio, configure también el prefijo de URL en el servidor web y pase esa URL completa a `install:prepare` en el paso 6.

<a id="apache-with-php-fpm"></a>

### Apache con PHP-FPM

Habilite Apache `rewrite`, `proxy`, `proxy_fcgi` y TLS. Dirija las solicitudes PHP para este sitio al grupo dedicado; no los dirija a través de `mod_php`.

Para Apache en Debian/Ubuntu, habilite sus módulos proxy FPM y cree el sitio:

```bash
sudo a2enmod rewrite proxy proxy_fcgi ssl
sudoedit /etc/apache2/sites-available/simbioza.conf
```

En macOS, edite `/opt/homebrew/etc/httpd/httpd.conf` con `sudoedit`. Habilite `rewrite_module`, `proxy_module`, `proxy_fcgi_module`, `ssl_module` y `socache_shmcb_module`. Agregue `Listen 443` si ninguna directiva existente escucha allí. No agregue un controlador mod_php para este sitio.

Para un nuevo Apache Homebrew, establezca explícitamente la identidad del trabajador compartido en `httpd.conf` (no ejecute trabajadores web como su inicio de sesión de propietario de código):

```apache
User _www
Group _www
```

En la configuración siguiente, reemplace el dominio, las rutas de certificado TLS y **ambas** rutas `/srv/simbioza/public` con la ruta de su aplicación real (en macOS, generalmente `/Users/Shared/Simbioza/simbioza/public`). Apache no expande `$SIMBIOZA_APP_ROOT` desde su terminal.

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

El `public/.htaccess` enviado usa `DirectoryIndex index.php`, que requiere la clase de anulación `Indexes`; las reglas de reescritura necesitan `FileInfo` y `Options
-Indexes` necesitan `Options`. Mantenga las tres clases en `AllowOverride`. Esto **no** habilita los listados de directorios: `Options -Indexes` todavía los deshabilita. Una clase faltante puede producir HTTP 500 (`DirectoryIndex not allowed here`) incluso cuando `apache2ctl configtest` informa `Syntax OK`, porque `.htaccess` se lee cuando una solicitud llega a ese directorio. Después de recargar, verifique la URL pública real antes de continuar con el asistente. Consulte la [referencia de Apache DirectoryIndex](https://httpd.apache.org/docs/2.4/mod/mod_dir.html#directoryindex). No exponga el directorio de versión principal como raíz del documento.

Para el **nuevo `simbioza.conf` separado creado anteriormente únicamente**, pruebe e inicie Apache. Omita este bloque cuando utilice un vhost compartido existente. En Debian/Ubuntu:

```bash
test -f /etc/apache2/sites-available/simbioza.conf &&
sudo a2ensite simbioza &&
sudo apache2ctl configtest &&
sudo systemctl enable --now apache2 &&
sudo systemctl reload apache2
```

En MacOS:

```bash
sudo /opt/homebrew/bin/httpd -t
sudo /opt/homebrew/bin/brew services start httpd
```

Espere `Syntax OK`; no inicie/recargue después de una verificación fallida. Para un Apache que ya se está ejecutando, utilice una recarga elegante validada en lugar de iniciar un segundo servicio. Continúe con el instalador; La alternativa de Nginx a continuación no es un paso adicional.

<a id="nginx-with-php-fpm"></a>

### Nginx con PHP-FPM

Nginx no puede ejecutar `mod_php`; envía solicitudes PHP al mismo grupo de FPM dedicado. Mantenga juntas las reglas `SCRIPT_FILENAME` y `try_files`:

Elija esta alternativa **en lugar de Apache**: use `nginx` en lugar de `apache2` en la lista de paquetes del paso 1 (`brew install nginx` en macOS) y no ejecute los comandos de configuración/inicio de Apache. Edite `/etc/nginx/sites-available/simbioza` en Debian/Ubuntu o `/opt/homebrew/etc/nginx/nginx.conf` en macOS. Reemplace el dominio, las rutas de los certificados y la raíz de la aplicación en el bloque de servidor completo a continuación.

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

En Debian/Ubuntu, habilite el bloque del nuevo servidor, pruebe e inicie:

```bash
sudo ln -s /etc/nginx/sites-available/simbioza /etc/nginx/sites-enabled/simbioza
sudo nginx -t
sudo systemctl enable --now nginx
sudo systemctl reload nginx
```

En macOS: `sudo /opt/homebrew/bin/nginx -t`, luego `sudo /opt/homebrew/bin/brew services start nginx`. Si el enlace o servicio ya existe, inspecciónelo en lugar de duplicarlo.

Pruebe la configuración de Nginx antes de recargar. No se debe poder acceder al escucha de FPM desde una red que no sea de confianza. La preparación del paquete mediante el instalador gráfico puede tardar varios minutos; La espera de 900 segundos de la puerta de enlace Apache/Nginx evita un 504 predeterminado de 60 segundos mientras el trabajador restringido aún se está ejecutando. Ajuste en consecuencia cualquier tiempo de espera adicional del proxy inverso ascendente, pero no deshabilite el FPM o los límites de solicitud del servidor web para sitios no relacionados. El grupo dedicado generado por `configure_fpm_setup.php` también establece el `max_execution_time` de PHP en 900 segundos. Esto es importante: en la compilación macOS/PHP 8.5 probada, un valor de `0` aún finalizó una solicitud larga después de un minuto, aunque debería significar que no hay límite. Verifique ambas configuraciones de tiempo de espera en el grupo generado y pruebe una preparación de paquete más prolongada.

<a id="optional-install-under-a-url-prefix-such-as-simbioza"></a>

### Opcional: instalar bajo un prefijo de URL como /simbioza

Para un nombre de host compartido, coloque este bloque **dentro de su host virtual HTTPS** existente, en lugar de crear otro host virtual con el mismo nombre. Reemplace la ruta de lanzamiento, el prefijo de URL y (para FPM) el puerto del grupo:

Los tres caminos tienen diferentes propósitos:

| Configuración | Ejemplo |
| --- | --- |
| Raíz de la aplicación utilizada en los pasos 2 y 4 | `/srv/simbioza` |
| Directorio público utilizado tanto por `Alias` como por `<Directory>` | `/srv/simbioza/public` |
| Prefijo de URL pública | `/simbioza` |

La raíz de la aplicación no tiene que estar dentro del `DocumentRoot` del nombre de host compartido. Manténgalo fuera de ese árbol público. No se requiere ningún enlace simbólico del directorio de la aplicación para esta configuración. El enlace simbólico de habilitación del sitio Nginx anterior no está relacionado: habilita la configuración del servidor, no los archivos de la aplicación.

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

También configure `ProxyTimeout 900` en este vhost existente. Si una configuración anterior contiene `<Directory "/var/www/html/simbioza">` para un enlace simbólico de directorio público anterior, **reemplace ese bloque** con el bloque de directorio público real anterior; agregar solo `Alias` no es suficiente. El controlador PHP debe aplicarse al destino real del sistema de archivos del alias. No exponga el directorio principal de la aplicación, `config/`, `data/` o `vendor/` para que funcione una ruta obsoleta.

Para una raíz de aplicación `/data/www/simbioza`, ambas rutas del bloque se convierten en `/data/www/simbioza/public`; la URL pública sigue siendo `/simbioza`. No cambie `SIMBIOZA_APP_ROOT` ni regenere el grupo simplemente para cambiar la asignación de URL. Si la raíz de la aplicación cambia, sus rutas FPM/ayudantes también necesitan una reconfiguración explícita; un alias de Apache no realiza esa reubicación.

Con Puppet/Ansible, realice el cambio equivalente en la definición de vhost administrado autorizado. No edite el vhost generado como una solución permanente, no cree un vhost HTTPS duplicado ni elimine los alias de otras aplicaciones. Si el SAML opcional ya tiene un alias más largo, como `/simbioza/simplesaml`, mantenga ese alias **antes** del alias general `/simbioza`. SAML sigue siendo opcional.

Después de cambiar **solo la configuración de alias/directorio de Apache**, valídelo y vuelva a cargar Apache correctamente. El paso 4 ya inició el servicio FPM dedicado:

```bash
sudo apache2ctl configtest && sudo systemctl reload apache2
```

Utilice este bloque de Linux en lugar de los comandos `a2ensite simbioza` del nuevo sitio cuando el vhost compartido HTTPS ya exista. Los comandos de validación/recarga Homebrew se enumeran en la sección de Apache anterior. Un servicio FPM en ejecución y una sintaxis de Apache válida no prueban que la solicitud utilice la ruta de aplicación correcta: pruebe también la URL pública `/simbioza/`. Deténgase en HTTP 500 o falta un `bootstrap.php`; inspeccione el alias, el controlador de directorio y las rutas FPM en lugar de continuar con el asistente o cambiar los permisos en términos generales.

<a id="reinstallation-only-an-obsolete-custom-fpm-bind-mount"></a>

#### Solo reinstalación: un montaje de enlace FPM personalizado obsoleto

Una instalación limpia no crea un acceso directo para un enlace simbólico antiguo de un directorio público. Si una instalación existente tenía una, inspecciónela antes de reiniciar:

```bash
sudo systemctl cat php-fpm-simbioza.service
sudo systemctl show php-fpm-simbioza.service -p DropInPaths
```

Utilice el nombre del servicio con sufijo para una instancia con nombre. Después de cambiar al alias directo, elimine **solo un complemento personalizado obsoleto confirmado** cuyo único propósito era vincular la ruta del enlace simbólico eliminado. Nunca elimine todo el directorio `.service.d` ni elimine los montajes de enlace reales de la aplicación generada. Un enlace obsoleto a una ruta inexistente puede impedir el siguiente inicio de FPM. Después de esa eliminación específica, use esta secuencia completa de Linux en lugar de la recarga anterior solo de Apache. También se aplica después de editar deliberadamente la configuración FPM dedicada; `daemon-reload` es necesario cuando se cambia una unidad systemd o un drop-in, no simplemente porque `php-fpm.conf` cambió:

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

Utilice el binario FPM, la ruta de configuración y el sufijo de servicio correspondientes para su instalación. Espere una prueba de configuración de FPM exitosa, `Syntax OK` y `active`. La cadena `&&` se detiene después de un comando fallido; No continúe con el asistente después de un error. Un reinicio de FPM interrumpe sus solicitudes actuales, así que no lo ejecute durante el trabajo de instalación/actualización. Esta es una limpieza del host única o la aplicación de un cambio de configuración intencional, **no es un paso adicional requerido en una instalación limpia**. Una configuración de FPM sin cambios no necesita reiniciarse solo para un cambio de alias de Apache.

Utilice `--base-url=https://your-host.example/simbioza` para el instalador, no el nombre de host sin su prefijo. Para una instalación en `/wiki`, reemplace **ambas** apariciones del prefijo y use `--base-url=https://your-host.example/wiki`. La ruta del directorio es una ruta del sistema de archivos; el prefijo es una ruta URL, no el nombre del directorio de la aplicación. Mantenga privados `config/` y `data/` fuera del alias expuesto. Valide Apache antes de su recarga elegante.

<a id="6-run-the-graphical-installer"></a>

## 6. Ejecute el instalador gráfico.

Genere una URL de instalación única desde el directorio de lanzamiento:

```bash
sudo -H -u fpm-simbioza php bin/simbioza install:prepare --base-url=https://simbioza.example.org
```

Si elige `--instance=second`, utilice `-u fpm-simbioza-second` en su lugar. Esto ejecuta PHP como identidad FPM, no como raíz. Para un sitio en `/simbioza`, utilice `--base-url=https://simbioza.example.org/simbioza` en su lugar. Abra la URL en una ventana privada del navegador; no comparta ni haga capturas de pantalla de su token. El asistente verifica los requisitos y el acceso a la base de datos, solicita la identidad del sitio, los idiomas, la zona horaria, el primer administrador y los módulos opcionales y luego muestra una revisión antes de la instalación. Todos los módulos opcionales están preseleccionados; deseleccione aquellos que no desee. Con el asistente dedicado funcionando, los paquetes opcionales seleccionados se instalan a través de la GUI.

Si omite intencionalmente el asistente dedicado, prepare los paquetes opcionales seleccionados como usuario de implementación con `php scripts/installation_packages.php prepare` para todos los módulos, o agregue `--modules=calendar` para una selección más pequeña. Haga coincidir la lista con sus selecciones y vuelva a cargar el instalador. Sin el ayudante, los cambios posteriores en el paquete GUI no estarán disponibles. La instalación/actualización/eliminación del idioma escribe solo datos y configuración del tiempo de ejecución y no requiere esta ayuda. Asegúrese de que PHP pueda escribir en esos directorios; consulte [localización](localization_en.md).

Después de la instalación, inicie sesión como administrador. En **Configuración → Configuración y módulos**, verifique que el grupo dedicado, el asistente y los permisos de tiempo de ejecución estén listos. La verificación de actualización en segundo plano CLI también debe pasar y las operaciones del paquete GUI y las actualizaciones de la aplicación deben estar disponibles. Un resultado gris **No marcado** no es un pase: resuelva primero la verificación de ayuda fallida anterior; no significa que falten las extensiones CLI. El asistente importa los idiomas seleccionados de la guía del usuario.

<a id="7-finalize-permissions-and-verify"></a>

## 7. Finalizar permisos y verificar

Después del asistente, cierre sesión y vuelva a iniciar sesión en SSH/terminal para activar las nuevas membresías del grupo de implementación/tiempo de ejecución. Restaure `SIMBIOZA_APP_ROOT` a la ruta seleccionada en el paso 2, luego ejecute los comandos correspondientes:

```bash
sudo php scripts/configure_fpm_setup.php --finalize \
  --app-root="$SIMBIOZA_APP_ROOT" --maintainer="$(id -un)" &&
php scripts/configure_fpm_setup.php --check \
  --app-root="$SIMBIOZA_APP_ROOT" --maintainer="$(id -un)" &&
vendor/bin/hph modules migrate-status &&
composer check-platform-reqs
```

En macOS, agregue el mismo argumento `--php-fpm` y ruta de aplicación utilizados en el paso
4. Cierre sesión y vuelva a iniciar sesión para que el mantenedor reciba las nuevas membresías del grupo.
Espere cero migraciones pendientes, inicio de sesión y páginas de inicio exitosos y una ruta `/install` bloqueada. Haga una copia de seguridad de la base de datos, `config/`, archivos de usuario y datos del tema.

La configuración única `sudo` **no** significa que las actualizaciones normales requieran root. Los comandos de mantenimiento se incluyen al final de esta guía.

<a id="optional-simplesamlphp-integration"></a>

## Integración opcional SimpleSAMLphp

**Omita esta sección completa a menos que necesite SAML.** No está relacionada con la configuración de OIDC, CAS, OAuth2 o el inicio de sesión local. Mantenga disponibles el administrador local y el inicio de sesión de emergencia hasta que SAML funcione.

<a id="s1-select-the-library-and-public-addresses"></a>

### S1. Seleccione la biblioteca y direcciones públicas

Primero termine la instalación normal. En su shell de mantenimiento, establezca las rutas y URL reales. Ejemplo de un nombre de host dedicado:

```bash
SIMBIOZA_APP_ROOT=/srv/simbioza
SIMBIOZA_SAML_ROOT=/opt/simplesamlphp
SIMBIOZA_SAML_URL=https://simbioza.example.org/simplesaml/
SIMBIOZA_SAML_CONFIG="$SIMBIOZA_APP_ROOT/data/saml/config"
```

Para un sitio en `https://your-host.example/simbioza`, utilice `SIMBIOZA_SAML_URL=https://your-host.example/simbioza/simplesaml/` en su lugar. Mantenga la barra final. La raíz de la biblioteca **no** es el directorio de Simbioza. Se puede reutilizar una biblioteca existente administrada por el sistema, por ejemplo `/usr/share/simplesamlphp-aai`; no modifique su configuración compartida, certificados ni el SP de otra aplicación.

Si ya hay instalada una biblioteca compatible, omita el bloque de descarga. De lo contrario, este ejemplo instala **2.5.3.1 completo**, con su suma de comprobación publicada. Requiere PHP **8.3 o posterior** y las extensiones de los [requisitos SimpleSAMLphp](https://simplesamlphp.org/docs/stable/simplesamlphp-install.html). Consulte [versiones anteriores](https://simplesamlphp.org/download/) para obtener actualizaciones de seguridad; al elegir otra versión, cambie la URL y la suma de verificación juntas. Ejecute lo siguiente sólo para un directorio de biblioteca **nuevo** e inexistente:

En Debian/Ubuntu, primero instale las herramientas de descarga con `sudo apt-get install curl openssl`. macOS ya suministra `curl` y `openssl`.

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

Deténgase si falla algún comando. Verifique la biblioteca seleccionada:

```bash
test -f "$SIMBIOZA_SAML_ROOT/vendor/autoload.php" &&
test -f "$SIMBIOZA_SAML_ROOT/public/module.php" &&
php -r 'require $argv[1]; exit(class_exists("SimpleSAML\\Auth\\Simple") ? 0 : 1);' \
  "$SIMBIOZA_SAML_ROOT/vendor/autoload.php"
```

<a id="s2-prepare-private-configuration-and-trusted-metadata"></a>

### S2. Prepare configuración privada y metadatos confiables

Desde `$SIMBIOZA_APP_ROOT`, cree directorios privados y copie **plantillas**. Este bloque se niega a sobrescribir la configuración. Al restaurar la configuración conservada para el **mismo SP**, restaure su `config`, los certificados y los metadatos requeridos; No copie las credenciales de otro sitio ni restaure sesiones/caché/la base de datos de la aplicación anterior.

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

Edite `config.php` con `sudoedit "$SIMBIOZA_SAML_CONFIG/config.php"`. Establezca estas claves dentro de su matriz `$config` existente. Reemplace las rutas del sistema de archivos de ejemplo con la ruta de su aplicación; Los archivos PHP no expanden las variables del terminal.

| Llave | Valor o acción |
| --- | --- |
| `baseurlpath` | El valor completo de HTTPS `SIMBIOZA_SAML_URL`, incluida la barra diagonal final. |
| `certdir`, `metadatadir` | `/srv/simbioza/data/saml/cert` y `/srv/simbioza/data/saml/metadata`. |
| `datadir`, `cachedir`, `loggingdir`, `tempdir` | Los directorios privados correspondientes `data`, `cache`, `logs`, `tmp` bajo `/srv/simbioza/data/saml`. |
| `metadata.sources` | Una fuente confiable, p. `[['type' => 'flatfile']]` usando el `metadatadir` privado; utilice el mecanismo verificado de su federación cuando corresponda. |
| `secretsalt`, `assets['salt']` | Dos valores aleatorios diferentes, cada uno generado con `openssl rand -hex 32`; edite `salt` dentro de la matriz `assets` existente, no una nueva clave `assets.salt` de nivel superior. Nunca utilice valores de plantilla ni los publique. |
| `auth.adminpassword` | Un hash generado interactivamente con `php "$SIMBIOZA_SAML_ROOT/bin/pwgen.php"`; separada de su contraseña Simbioza. |
| `store.type` | `phpsession` para este tutorial. |
| `session.phpsession.cookiename` | Un nombre específico del sitio, p. `SimbiozaSAML`, diferente de las cookies de sesión de Simbioza y de otros sitios. |
| `session.phpsession.savepath` | `null`, por lo que ambos puntos finales utilizan el `data/sessions` privado de este grupo. |
| `session.cookie.path` | `/` para un nombre de host dedicado o `/simbioza/` para ese prefijo; debe cubrir tanto la aplicación como el punto final SAML. |
| `session.cookie.secure`, `session.phpsession.httponly` | `true`; utilice HTTPS. |
| `showerrors` | `false` solución de problemas controlada externamente. |

En `authsources.php`, configure `default-sp` como `saml:SP` con su propio `entityID` estable y el ID de entidad `idp` del IdP de confianza. Elimine las fuentes de ejemplo no utilizadas. Obtenga metadatos de IdP de su federación/administrador de IdP; guarde los metadatos PHP confiables como `data/saml/metadata/saml20-idp-remote.php` o configure el mecanismo de metadatos admitido por la federación. No deshabilite las comprobaciones de firmas ni confíe en un certificado de firma no verificado.

Si el IdP requiere un certificado de SP, genere una clave/certificado privado para un **nuevo** SP en `data/saml/cert` de este sitio y haga referencia a los nombres de archivo a través de `privatekey` y `certificate` en `default-sp`, siguiendo la política de la federación. Para el **mismo SP registrado**, conserve su EntityID, las claves/certificados existentes y los metadatos relevantes en lugar de generar reemplazos.

<a id="s3-configure-the-dedicated-pool-and-verify-acls"></a>

### S3. Configure el grupo dedicado y verifique las ACL

Desde `$SIMBIOZA_APP_ROOT`, vuelva a ejecutar el generador para la **misma aplicación e instancia**, agregando el argumento de configuración privada. Esto actualiza y reinicia solo ese grupo dedicado, reutilizando sus cuentas/grupos existentes:

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

En macOS, agregue `--php-fpm="$(brew --prefix)/sbin/php-fpm"` a **los tres** comandos. Para una instancia con nombre, repita también las opciones originales `--instance` y `--listen`. El generador exporta `SIMPLESAMLPHP_CONFIG_DIR` dentro del trabajador FPM a pesar de `clear_env = yes`; un SSH `export` o Apache `SetEnv` por sí solo no es suficiente.

En Linux, verifique el acceso de lectura y las ACL de configuración privada:

```bash
getfacl -p "$SIMBIOZA_SAML_CONFIG" "$SIMBIOZA_SAML_CONFIG/config.php" &&
sudo -u fpm-simbioza test -r "$SIMBIOZA_SAML_CONFIG/config.php" &&
sudo -u fpm-simbioza test -r "$SIMBIOZA_SAML_CONFIG/authsources.php" &&
sudo -u fpm-simbioza test ! -w "$SIMBIOZA_SAML_CONFIG/config.php"
```

Espere `group:app-simbioza:r-x` y un valor predeterminado coincidente ACL en el directorio; Los archivos son legibles, no escribibles, por FPM. La implementación conserva la propiedad. Utilice identidades con sufijo para una instancia con nombre. En macOS, el generador utiliza la propiedad del grupo en lugar de los comandos POSIX ACL de Linux.

<a id="s4-send-the-saml-endpoint-to-the-same-fpm-pool"></a>

### S4. Envíe el punto final SAML al mismo grupo de FPM

Agregue este bloque al **vhost HTTPS** existente, no a un segundo vhost ni a un alias SimpleSAMLphp global compartido. Reemplace el prefijo de URL y las rutas del sistema de archivos con los valores de S1; Apache no expande las variables de shell. Un alias SAML más largo debe aparecer **antes** de un alias de aplicación `/simbioza` general.

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

Para `/simbioza`, utilice `Alias /simbioza/simplesaml` y la expresión regular `^/simbioza/simplesaml/(.+\.php(?:/.*)?)$`. Para un paquete del sistema, reemplace **ambas** rutas `/opt/simplesamlphp/public` con su directorio `public/` real. Utilice el puerto real de su grupo. El proxy explícito conserva `module.php/...` PATH_INFO y utiliza la identidad FPM, el entorno y el directorio de sesión de la aplicación. Su controlador ordinario PHP no cubre este alias separado.

En Debian/Ubuntu, valide y luego vuelva a cargar Apache con gracia:

```bash
sudo apache2ctl configtest && sudo systemctl reload apache2
```

En Homebrew Apache use `sudo /opt/homebrew/bin/httpd -t` y, solo en caso de éxito, `sudo /opt/homebrew/bin/httpd -k graceful`. Con Puppet/Ansible, cambie el vhost administrado autorizado en lugar de un archivo generado. Para Nginx, use la [alias oficial/PATH_INFO configuración](https://simplesamlphp.org/docs/stable/simplesamlphp-install.html#configuring-nginx) con este mismo grupo dedicado y prefijo de URL, no un grupo predeterminado compartido. Nunca exponga `config/`, `cert/` o metadatos privados a través del alias web.

<a id="s5-register-the-sp-map-attributes-and-test-authentication"></a>

### S5. Registre el SP, asigne atributos y pruebe la autenticación

Abra la URL base SAML y obtenga metadatos `default-sp` a través de la interfaz de administrador SimpleSAMLphp. Envíelo al administrador de IdP/federación. Verifique que los **EntityID, ACS y SLO** registrados utilicen el prefijo HTTPS SAML de este sitio y espere hasta que el registro esté activo.

En Simbioza **Configuración → Autenticación**, habilite el protocolo SimpleSAMLphp. Cree un perfil SAML con la ruta absoluta `vendor/autoload.php` y `auth_source = default-sp` de la biblioteca. Utilice **Perfil de prueba** para capturar atributos de IdP reales; elija un identificador estable/atributo de inicio de sesión y asigne campos de correo electrónico/nombre. No adivine nombres de atributos de un proveedor diferente. Pruebe nuevamente hasta que el perfil esté operativo, habilítelo y verifique que los usuarios previstos puedan usarlo. La creación automática de cuentas es una elección de perfil explícita.

La GUI escribe la configuración de Simbioza en `HPH_CONFIG_PATH/auth/auth.json`; no edita archivos externos SimpleSAMLphp. Para la configuración de autenticación manual, comience con `auth.json.dist` del módulo de autenticación, nunca reemplazando un archivo activo.

Pruebe en un navegador privado: SAML iniciar sesión → volver a Simbioza → página protegida → cerrar sesión → otro inicio de sesión. La carga de metadatos por sí sola no prueba la autenticación. Con `phpsession`, un directorio de grupo/sesión diferente puede causar bucles de inicio de sesión: corrija el enrutamiento/entorno en lugar de otorgar permisos más amplios. Conserve el acceso de administrador local hasta que el flujo completo del navegador se realice correctamente.

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

<a id="external-configuration-layers"></a>

## Capas de configuración externa

Prepare la configuración completa antes de seleccionarla en el archivo privado `framework.php`. El grupo de procesos generado y el asistente fijo fijan los mismos parámetros base y de sobrescritura; no dependen de que las variables heredadas sobrevivan a `clear_env` o `sudo`. Las reglas de aislamiento de Linux montan las capas inferiores en modo de solo lectura y exponen la capa final junto con el directorio configurado para las solicitudes de Setup. Solo se permiten escrituras donde ya las autorizan los permisos Unix; la herramienta no ejecuta chown/chmod sobre las capas externas.

Conceda a las cuentas de FPM y de despliegue acceso de lectura y recorrido de directorios. El código PHP del servicio debe pertenecer a la cuenta de despliegue y ser de solo lectura para FPM; los archivos de datos y de bloqueo deben pertenecer a la cuenta de ejecución, ser modificables y usar el grupo compartido de ejecución y permisos que permitan el reemplazo atómico. No use un directorio personal protegido ni la raíz pública del sitio para la configuración privada. Cree previamente cualquier directorio de solicitudes distinto del predeterminado con los permisos compartidos necesarios. Después de cambiar las rutas de un sitio existente, repita `--install` y después `--finalize`, con las mismas opciones de instancia, puerto y SAML. `--finalize` por sí solo actualiza el asistente y la propiedad de los archivos, no el grupo de procesos FPM. Consulte la [guía de configuración](configuration_en.md).
