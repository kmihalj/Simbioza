# API: límites, CalDAV e ICS

<a id="api--boundaries-caldav-and-ics"></a>


> Las capturas de pantalla muestran la interfaz en inglés.

[← API — contenido](api-koristenje_es.md)

Este capítulo distingue el REST público API de otros mecanismos de integración. OpenAPI enumera rutas `/api/v1` registradas; no es un inventario de cada protocolo o página web Simbioza.

<a id="features-without-a-separate-rest-endpoint"></a>

## Funciones sin un punto final REST separado

- El menú y el tema no tienen configuraciones globales REST API; Existen rutas temáticas de espacios de trabajo privados.
- La copia de seguridad no tiene una copia de seguridad del sitio/espacio de trabajo REST ni un punto final de restauración. HTML ZIP la exportación es un formato diferente, no una copia de seguridad.
- ORM, Correo electrónico y Accesibilidad no exponen recursos REST públicos separados.
- El comentario no tiene extensión REST para crear/eliminar comentarios.
- La tarea API cambia el estado de la tarea existente; Las definiciones pertenecen al contenido del Editor.
- La encuesta API no acepta envíos de participantes ni reemplaza el inicio de sesión externo.
- Calendar REST no expone invitaciones/respuestas de asistentes ni aprobaciones de reservas.
- Las rutas web administrativas POST no son un sustituto de REST: utilizan sesiones, CSRF y sus propios permisos.

## CalDAV

CalDAV es un protocolo separado bajo `/caldav`, que utiliza HTTP Basic con un identificador de inicio de sesión local y una contraseña local. Una clave Bearer API no es una credencial CalDAV. Utilice HTTPS. Un usuario exclusivo de SSO necesita una contraseña local para CalDAV; configurarlo no habilita el inicio de sesión web local. Consulte [conexión de clientes de calendario](kalendari-povezivanje_es.md).

La sincronización básica admite sondas OPTIONS/HEAD, PROPFIND para raíz/principal/colección, REPORT para un calendario y GET/PUT/DELETE para `/{uid}.ics`. PUT/DELETE requieren acceso de escritura al calendario actual. El nombre del archivo del objeto sigue su UID y debe estar codificado en URL. La funcionalidad completa de CalDAV Scheduling/iTIP no está implementada; las invitaciones a reuniones se responden a través de la interfaz web.

```sh
export CALDAV_LOGIN='your-local-login'
read -r -s CALDAV_PASSWORD
export CALDAV_PASSWORD
curl --silent --show-error --request PROPFIND \
  --user "$CALDAV_LOGIN:$CALDAV_PASSWORD" \
  --header "Depth: 1" \
  "$BASE_URL/caldav"
```

Resultado real: HTTP 207, `application/xml; charset=utf-8`; la lista XML incluye solo calendarios accesibles. Sin autenticación Basic, o con una clave Bearer normal, el mismo PROPFIND devuelve 401. Estos métodos se verificaron adicionalmente con solicitudes curl reales:

| Método | Camino | Resultado |
|---|---|---|
| OPTIONS | `/caldav` | 204 |
| GET | `/.well-known/caldav` | 301 |
| PROPFIND | `/caldav` | 207 |
| PUT | `/caldav/{calendarSlug}/api-protocol%40example.invalid.ics` | 201 |
| GET | `/caldav/{calendarSlug}/api-protocol%40example.invalid.ics` | 200 |
| REPORT | `/caldav/{calendarSlug}` | 207 |
| DELETE | `/caldav/{calendarSlug}/api-protocol%40example.invalid.ics` | 204 |

<a id="ics-links-and-export"></a>

## Enlaces ICS y exportación

REST `/api/v1/calendars/{calendarUuid}/export.ics` requiere una clave de portador y `calendar:read`. Los enlaces ICS de suscripción pública/privada desde la interfaz web son un contrato independiente con sus propias reglas o tokens de acceso; no agregue una clave de API a esas URL. Una suscripción pública a ICS es de solo lectura. Obtenga enlaces de suscripción desde su propio calendario en lugar de crearlos a partir de las URL de prueba de otra instalación.
