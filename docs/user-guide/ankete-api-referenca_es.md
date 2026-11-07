# API: rutas, ámbitos y errores

<a id="api--routes-scopes-and-errors"></a>


> Las capturas de pantalla muestran la interfaz en inglés.

[Para desarrolladores: API](ankete-developeri_es.md)

La ruta base es `/api/v1/surveys`, sin una barra diagonal al final. Anteponda el subdirectorio de instalación cuando corresponda. Cada solicitud necesita una clave válida, el ámbito requerido y los permisos actuales del propietario para el recurso correspondiente.

<div class="table-responsive">

| Método y sufijo de ruta                                | Alcance           |
|-------------------------------------------------------|-----------------|
| GET /, GET /{survey}                                  | surveys:read    |
| POST /, PATCH /{survey}                               | surveys:write   |
| POST /{survey}/submit-review, withdraw, copy, delete, discard-draft | surveys:write |
| POST /{survey}/publish, return-review, close, archive, restore | surveys:publish |
| GET /{survey}/results, responses                      | surveys:results |
| POST /{survey}/exports; GET /{survey}/exports/{token} | surveys:export  |

</div>

Las transiciones utilizan `version`, `revision` y `confirmed` explícito. La copia acepta un nuevo título. No cambie la estructura vigente después de que existan respuestas: copie la encuesta.

El listado admite `q`, `state`, `page` y `per_page`. Los resultados aceptan idioma y paginación; Los gestores también pueden filtrar los días de informe `from`/`to` inclusivos. Los envíos individuales requieren gestión; El permiso del lector de gráficos es insuficiente.

Las exportaciones aceptan `mode` (aggregate/responses), `lang` y `include_identity` explícito. El token devuelto caduca y permanece restringido al mismo creador autorizado. No es un enlace público ni una copia de seguridad.

<div class="table-responsive">

| Estado | Acción |
|----|----|
| 401 / 403 | Verifique la clave, el alcance, la cuenta activa y los permisos actuales. |
| 404 | El recurso no está disponible o un módulo requerido está deshabilitado; no eludir los controles de acceso. |
| 409 | Vuelva a leer la versión actual y concilie el cambio. |
| 422 | Corrige la definición, idioma o filtros mediante el mensaje de respuesta. |

</div>

Las respuestas correctas utilizan `data`, `meta` y `links`. Los identificadores de solicitud ayudan al diagnóstico. No registre públicamente respuestas ni claves. No existe una API de envío de respuestas basada solo en un token que actúe como participante; siguen aplicándose las reglas habituales de inicio de sesión, validación y prevención de envíos repetidos.

<a id="removal-and-recovery-through-the-api"></a>

## Eliminación y recuperación a través del API

`delete` mueve toda la encuesta a la papelera y conserva las respuestas. `discard-draft` descarta un borrador de trabajo; una publicación existente permanece sin cambios. Ambos requieren el `version`, `revision` y el booleano `confirmed: true` actuales. `restore` requiere `version`, `confirmed: true` y un administrador, además del alcance clave anterior. Una publicación anterior regresa cerrada. La respuesta incluye UUID, operación y `deleted`, lo que indica si la encuesta ahora está en la basura. HTTP 409 significa volver a leer el estado actual antes de decidir nuevamente.
