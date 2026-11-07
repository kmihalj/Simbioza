# Elegir las funciones de Simbioza

<a id="choosing-simbioza-features"></a>


> Las capturas de pantalla muestran la interfaz en inglés.

[Versión en croata](modularity_hr.md)

Simbioza reúne páginas, un wiki, calendarios, encuestas y colaboración en una aplicación completa. Si solo necesita algunas de estas características, el marco modular HeartPhrame también permite una instalación enfocada. Los siguientes ajustes preestablecidos describen esa opción adicional.

**Simbioza** en croata significa **simbiosis**: diferentes organismos que viven juntos. La aplicación lleva el nombre de esa idea porque sus módulos tienen funciones distintas pero pueden trabajar juntos. Una página explica un evento del calendario, un Wiki conecta el conocimiento del equipo y una encuesta recopila comentarios. El Simbioza completo combina estas herramientas, tareas, comentarios, notificaciones e integraciones en un solo entorno.

<a id="choose-a-purpose"></a>

## Elija un propósito

| Lo que necesita | Instalación preestablecida | Módulos de funciones |
| --- | --- | --- |
| Todas las funciones disponibles juntas | Completo Simbioza | Todos los módulos opcionales |
| Un sitio web sencillo con HTML páginas | Sitio web HTML | HTML Editor |
| Eventos, reuniones y CalDAV | Calendar | Calendar |
| Un Wiki con capacidad de búsqueda organizado en espacios de trabajo | wiki | Workspace, Workspace Search y Simbioza User |
| Encuestas con preguntas, ramificaciones y resultados. | Encuestas | Survey |

Todas las instalaciones incluyen **Auth, ORM, Menu, Theme, Backup y Audit**. Estos módulos proporcionan autenticación y permisos, acceso a la base de datos, menús, apariencia, copias de seguridad y registros de actividad. «Independiente» significa sin otros módulos funcionales, pero siempre con la base compartida y las dependencias necesarias.

El instalador agrega las dependencias requeridas automáticamente. Por ejemplo, Survey utiliza el editor HTML para presentaciones y explicaciones; el Wiki también necesita el Editor y el usuario Simbioza agrega notificaciones. API, la accesibilidad y otras integraciones opcionales no requieren funciones comerciales no relacionadas.

<a id="install-the-selected-tool"></a>

## Instalar la herramienta seleccionada

1. Prepare una instalación nueva siguiendo la [guía de instalación correspondiente](installation_es.md).
2. En el paso de configuración de la aplicación del asistente, seleccione un **Preajuste de instalación** y haga clic en **Aplicar ajuste preestablecido**.
3. Revisar los módulos seleccionados. Las dependencias requeridas permanecen incluidas; seleccione funciones adicionales solo si las necesita.
4. Seleccione los idiomas de instalación y finalice la configuración. Los idiomas de contenido y traducción se seleccionan entre los idiomas activos instalados.
5. Inicie sesión como administrador, cree su contenido y agregue enlaces de menú para que los visitantes puedan encontrarlo.

El resultado es una aplicación para el propósito elegido. Las páginas de muestra, los calendarios y las encuestas no son datos comerciales que se envían automáticamente a cada sitio nuevo; usted crea su contenido después de la instalación.

<a id="share-a-standalone-html-website"></a>

## Compartir un sitio web independiente HTML

Un sitio web HTML no necesita Wiki para funcionar con varios autores o páginas privadas. Como administrador, abra **Configuración → HTML editor → Permisos de página** y agregue usuarios o grupos. **Agregar** permite crear y editar sus propias páginas. **Edición** permite administrar todas las páginas independientes, incluidas las privadas; no otorga acceso a la configuración del administrador.

En una página individual, abra **Configuración y permisos de página**. Elija lectura pública, todos los usuarios que hayan iniciado sesión o solo usuarios y grupos seleccionados. Esta opción también protege los archivos adjuntos, los enlaces directos y las lecturas API. En un Wiki, continúe usando el espacio de trabajo y los permisos de página existentes.

<a id="extend-an-existing-installation"></a>

## Ampliar una instalación existente

Por ejemplo, agregue un calendario o encuestas a un sitio web HTML, o agregue encuestas, tareas y comentarios a un Wiki. **Configuración → Configuración y módulos** muestra qué está instalado, qué está habilitado y qué dependencias se requieren.

En una instalación de FPM dedicada correctamente configurada, un administrador puede hacer clic en **Instalar** junto a otro módulo. Para mod_php y FPM ordinario, un mantenedor ejecuta el comando CLI correspondiente desde la raíz de la aplicación usando la cuenta de mantenimiento:

```bash
php vendor/bin/hph modules add calendar --fresh
php vendor/bin/hph modules add survey --fresh
```

Estos son ejemplos separados: elija el módulo que necesita. El sistema agrega sus dependencias requeridas. Utilice `task` y `comment` para tareas y comentarios; Posteriormente, un Wiki puede agregar `confluence-import` para importar contenido existente. Después de ampliar la instalación, configure los permisos, menús y ajustes del nuevo módulo. La instalación de un módulo no otorga a todos los usuarios permiso para editar contenido.

Haga una copia de seguridad antes de cambiar la composición de la instalación. Deshabilitar un módulo opcional no es lo mismo que desinstalarlo: deshabilitarlo conserva los datos, mientras que la desinstalación elimina sus tablas y su paquete después de preparar una copia de seguridad de recuperación. Los seis módulos requeridos no se pueden desactivar ni desinstalar.

<a id="add-workspaces-to-an-existing-html-website"></a>

### Agregar espacios de trabajo a un sitio web HTML existente

Agregar espacios de trabajo no mueve las páginas HTML existentes. Conservan sus direcciones, contenido, traducciones, archivos adjuntos y permisos independientes. Las páginas independientes HTML y las páginas de Workspaces se pueden usar juntas, incluso a través de API.

Solo un administrador puede mover una página existente a un espacio de trabajo:

1. Abra **Configuración → HTML editor → Páginas fuera de los espacios de trabajo**.
2. Busque la página y elija **Mover al espacio de trabajo**.
3. Abra la lista de destinos o busque por nombre. Se cargan más espacios de trabajo a medida que se desplaza.
4. Revise la visibilidad del destino y confirme sus permisos, luego mueva la página.

El resultado es una página en el nivel superior del árbol de destino. Todas las traducciones, el historial de versiones y las versiones adjuntas se mueven con él. Los archivos se transfieren al almacenamiento del espacio de trabajo; La base de datos HTML permanece en los mismos registros de versión con la nueva propiedad del espacio de trabajo. Los enlaces en las páginas almacenadas se reparan, incluidas las opciones de idioma y los anclajes, y las direcciones de páginas y archivos adjuntos existentes siguen siendo utilizables con las comprobaciones de acceso del destino. Las versiones publicadas permanecen publicadas y los borradores de trabajo siguen siendo borradores.

**Los permisos de destino reemplazan el acceso independiente.** Los lectores antiguos no se agregan automáticamente como excepciones. Mover una página pública a un espacio de trabajo privado puede restringirla; Mover una página privada a un espacio de trabajo público puede hacer que su contenido publicado sea público. Revise el destino antes de confirmar. Primero haga una copia de seguridad si necesita un punto de retorno: esta pantalla no ofrece un movimiento inverso.

<a id="updates-and-backups"></a>

## Actualizaciones y copias de seguridad

Las nuevas instalaciones reciben cookies de inicio de sesión independientes, incluso cuando varios sitios comparten un dominio. Una restauración conserva la identidad de la sesión local del sitio de destino en lugar de copiar la identidad de la cookie de inicio de sesión de otro sitio.

FPM dedicado con un asistente restringido configurado correctamente admite actualizaciones a través de **Configuración → Configuración y módulos → Ejecutar actualización**. Simplemente usar FPM no es suficiente: la verificación del entorno debe confirmar que las actualizaciones de la GUI están disponibles.

Para otras instalaciones clásicas, el mantenedor ejecuta estos comandos desde la raíz de la aplicación:

```bash
php update.php --check --lang=en
php update.php --lang=en
```

Las actualizaciones conservan los módulos seleccionados y los datos existentes. Un nuevo módulo opcional está disponible para instalar; no se habilita automáticamente. Una copia de seguridad completa incluye los datos y la configuración portátil de los módulos habilitados. Conserve también una copia separada de la configuración del host y los secretos locales, que no se transfieren mediante copias de seguridad de datos comerciales.

## Docker

Docker usa la misma selección de funciones. Si una imagen contiene todos los módulos, seleccione y habilite solo los necesarios durante la instalación. Para una imagen personalizada más pequeña, cree con módulos seleccionados y sus dependencias requeridas. Un módulo ausente en la imagen requiere una imagen de reemplazo, no un cambio de código dentro de un contenedor en ejecución. Las actualizaciones reemplazan la imagen mientras preservan la base de datos y los datos del tiempo de ejecución. Siga la [guía de Docker](docker_en.md).

<a id="guides-and-integration-with-other-systems"></a>

## Guías e integración con otros sistemas.

Con un Wiki, estas páginas se encuentran en el espacio de trabajo **Guías de usuario**. Sin Wiki, el mismo manual está disponible a través de **Guías**, con una lista de capítulos y navegación. No es necesario instalar un Wiki sólo para leer el manual.

API se puede agregar para cualquier propósito. Una clave de API determina las llamadas permitidas pero nunca pasa por alto los permisos de su propietario. Las rutas y alcances disponibles siguen los módulos instalados y habilitados. Esto admite la integración con otros sistemas y aplicaciones HeartPhrame personalizadas sin reconstruir la autenticación, la navegación, el acceso a la base de datos y otras bases comunes.
