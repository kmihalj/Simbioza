# API: conectar otros sistemas

<a id="api--connecting-other-systems"></a>


> Las capturas de pantalla muestran la interfaz en inglés.

Esta guía está dirigida a desarrolladores que conectan su aplicación a Simbioza. El API funciona con usuarios, contenido, espacios de trabajo, calendarios, encuestas y datos personales que ya están permitidos al propietario de la clave. No reemplaza la administración de la instalación ni el inicio de sesión de los participantes en las encuestas.

<a id="getting-started"></a>

## Empezando

1. Un administrador instala/habilita API y emite una clave con los alcances requeridos.
2. Utilice [Cómo comenzar](api-pocetak_es.md) para configurar la dirección y leer `/api/v1/me`.
3. Verifique los recursos disponibles de su instalación y OpenAPI.
4. Abra el capítulo de recursos y siga su ejemplo de creación, lectura, actualización o exportación.
5. Maneje también los errores: la clave no otorga acceso al contenido privado de otro usuario.

<a id="chapters"></a>

## Capítulos

| Capítulo | Operaciones verificadas |
|---|---|
| [API — introducción y reglas compartidas](api-pocetak_es.md) | 3 |
| [API — usuarios y grupos](api-korisnici-grupe_es.md) | 11 |
| [API — espacios de trabajo, árboles, permisos y temas](api-podrucja_es.md) | 30 |
| [API — HTML páginas, borradores y archivos adjuntos](api-html-stranice_es.md) | 22 |
| [API — calendarios y eventos](api-kalendari_es.md) | 13 |
| [API — encuestas y resultados](api-ankete_es.md) | 18 |
| [API — tareas en el contenido](api-zadaci_es.md) | 4 |
| [API — notificaciones y seguimientos personales](api-obavijesti-pracenja_es.md) | 11 |
| [API — webhooks y entregas](api-webhookovi_es.md) | 9 |
| [API — auditoría y búsqueda](api-audit-pretraga_es.md) | 2 |
| [API límites, CalDAV e ICS](api-granice-protokola_es.md) | — |

Este manual cubre las **114 rutas REST registradas** en una instalación completa. La ruta de acción de encuesta nombrada admite 10 operaciones, lo que arroja **123 operaciones verificadas por separado**. Una instalación modular expone solo los recursos cuyos módulos están instalados y activos. CORS `OPTIONS` y el protocolo separado CalDAV están fuera de este total.

Cada ejemplo indica el método, la ruta, los permisos, los parámetros, el comando curl, la respuesta real y los códigos de error verificados. La dirección se reemplaza con su marcador de posición de instalación; Las claves y secretos no se publican. Las respuestas utilizan datos sintéticos, por lo que los nombres, identificaciones y marcas de tiempo difieren de los suyos. Algunas respuestas grandes utilizan una proyección `jq` explícita; el JSON mostrado es entonces la salida real de ese filtro.

[Administrador API-gestión de claves](admin-api_es.md). [Reglas compartidas, errores y un cliente PHP completo](api-pocetak_es.md).
