# Elegir una instalación de Simbioza

<a id="choose-a-simbioza-installation"></a>


> Las capturas de pantalla muestran la interfaz en inglés.

[Versión croata](installation_hr.md)

Estas guías instalan Simbioza **0.3.42**. Consulte [versiones y dependencias del módulo](module-dependencies_en.md) para conocer el inventario de paquetes actual. El marco sigue siendo **0.0.25**. La línea base 0.3.x comienza con un nuevo directorio de aplicación, configuración y base de datos. No se admite una actualización desde 0.2.x ni la reutilización de sus copias de seguridad Simbioza.

Elija **una guía completa** a continuación. Cada uno comienza en una terminal de administrador, instala/verifica las herramientas del host, descarga la versión, prepara la base de datos y los permisos, configura el servidor web, ejecuta el asistente y verifica el primer inicio de sesión. No utilice esta descripción general como un paso de instalación adicional.

| Instalación | PHP identidad | Preparación de paquetes y actualizaciones posteriores de la aplicación. | guía completa |
|---|---|---|---|
| Apache mod_php | Cuenta Apache compartida | CLI, como propietario del código | [Inicie la instalación de mod_php](installation_mod_php_es.md) |
| Ordinario PHP-FPM | Cuenta web del sistema compartido en un grupo de FPM | CLI, como propietario del código | [Inicie la instalación normal de FPM](installation_fpm_es.md) |
| PHP-FPM asegurado (recomendado) | Cuentas de implementación y FPM dedicadas, ayuda restringida | GUI a través del trabajador, o CLI como mantenedor de implementación | [Iniciar la instalación segura de FPM](installation_fpm_secured_es.md) |

Los tres tienen un **instalador inicial** gráfico y una GUI de configuración. El FPM ordinario no es lo mismo que el FPM seguro: agregar un controlador FastCGI por sí solo no crea un aislamiento de implementación ni habilita la GUI Composer/actualizaciones de aplicaciones. Nginx requiere FPM; no puede ejecutar mod_php de Apache. [Instalación de Docker](docker_en.md) es una ruta independiente y opcional basada en imágenes.

<a id="before-choosing-a-php-mode"></a>

## Antes de elegir un modo PHP

Los ejemplos predeterminados son para un nuevo host Linux o macOS, no para un grupo de producción existente. Tener un shell de administrador, un certificado de dominio/TLS para un sitio público y una opción de base de datos. SQLite no necesita ningún servicio de base de datos. Cada guía elegido repite los comandos exactos de preparación; No se supone que exista ninguna Simbioza FPM/cuenta de implementación.

Para la reinstalación, el administrador del sistema primero maneja los procesos y datos del sitio antiguo, verifica las identidades existentes y selecciona la instancia y el puerto correctos. Las guías nunca eliminan otra instalación ni recrean un sitio en ejecución simplemente porque existe el directorio de descarga.

La configuración del módulo privado utiliza `HPH_CONFIG_PATH` (predeterminado `config/`); Los archivos persistentes utilizan `data/`. El servidor web sólo expone `public/`.

<a id="after-installation"></a>

## Después de la instalación

Configure [rotación de registros](logging_en.md) para cron, actualizador y salida FPM. El controlador rotativo `app.log` de la aplicación no rota esos archivos separados.

Cada guía completa incluye su propio módulo, idioma y comandos de actualización, límites de respaldo y verificaciones finales. El trabajo de rutina del paquete/código pertenece al mantenedor/identidad de implementación, no a la cuenta web ni al root. Los paquetes de idiomas se pueden actualizar independientemente de la versión de la aplicación.

El actualizador de la aplicación actualiza las guías de usuario incluidas en los paquetes de versión instalados. La lectura de GitHub `main` no reemplaza automáticamente las páginas ya almacenadas en un sitio; su contenido puede pertenecer a una versión anterior. Las páginas de instalación incluidas se generan a partir de estas fuentes de Markdown.
