# Inicio de sesión y usuarios

<a id="login-and-users"></a>


> Las capturas de pantalla muestran la interfaz en inglés.

Esta guía guía al administrador a través de cada configuración de inicio de sesión, atributo de usuario, grupo, cuenta de usuario, registro local, suplantación, clave de API, proveedor SSO y pantalla de acceso y seguridad personal.

<div class="alert alert-info">

**Los ejemplos se crearon en una instalación limpia SQLite.** Las cuentas, los grupos y las claves que se muestran aquí existen solo para documentación. Los secretos y los valores de atributos personales se han eliminado de las capturas de pantalla. En producción, utilice sus propios nombres, dominios, vidas útiles y reglas de privilegios mínimos.

</div>

<a id="contents"></a>

## Contenido

1.  [Configuración de inicio de sesión](#1-login-settings)
2.  [Atributos de usuario](#2-user-attributes)
3.  [Grupos](#3-groups)
4.  [Usuarios y cuentas locales](#4-users-and-local-accounts)
5.  [Inicio de sesión y registro local](#5-local-login-and-registration)
6.  [Suplantación](#6-impersonation)
7.  [clave de APIs](#7-api-keys)
8.  [Flujo de trabajo común SSO](#8-common-sso-workflow)
9.  [SAML](#9-saml)
10. [SAML proxy](#10-saml-proxy-and-child-profiles)
11. [CAS](#11-cas)
12. [OIDC](#12-oidc)
13. [OAuth2](#13-oauth2)
14. [Perfil personal: seguridad y acceso](#14-personal-profile-security-and-access)
15. [Lista de verificación y seguridad](#15-verification-and-security-checklist)

<a id="login-settings"></a>

<a id="1-login-settings"></a>

## 1. Configuración de inicio de sesión

Abra **Configuración → Usuarios → Configuración de inicio de sesión**. Esta página habilita métodos de autenticación para la aplicación. Habilitar un proveedor solo hace que su configuración y menú estén disponibles; cada perfil individual aún debe guardarse, activarse, probarse y asignarse correctamente.

<span class="figure editor-html-media-figure editor-html-media-image d-inline-block align-top me-2 mb-2 w-100"><img src="images/fb8366ae-1f95-45c3-82a7-b4b374ba51a2.png" class="figure-img img-fluid rounded mb-1" alt="Configuración de inicio de sesión y configuración de métodos de inicio de sesión disponibles." /><span class="figure-caption d-block">Descripción general de la configuración de inicio de sesión</span></span>

<figure class="my-4">

<figcaption>Configuración de inicio de sesión en la interfaz en inglés.</figcaption>
</figure>

<a id="main-switches"></a>

### interruptores principales

- **El inicio de sesión local** habilita la autenticación de nombre de usuario y contraseña almacenados por Simbioza.
- **SimpleSAMLphp, CAS, OIDC y OAuth2** habilitan los grupos de perfiles SSO correspondientes.
- **El inicio de sesión local de emergencia** mantiene disponible una ruta de administrador directa separada cuando el inicio de sesión local normal está deshabilitado. Proteja esa cuenta con una contraseña única y segura y úsela solo para recuperación.
- **Duración del inicio de sesión** es un límite de inactividad en días (1 a 365), que se renueva mientras el usuario que inició sesión permanece activo. Utilice un período más corto en dispositivos administrativos y compartidos.

<div class="alert alert-warning">

**“Perfil activo” no es lo mismo que la marca de verificación verde.** Activo significa que se puede utilizar el perfil. La marca verde y el estado *Probado y operativo* aparecen solo después de una prueba de proveedor exitosa y asignaciones requeridas válidas. Es posible que un perfil activo aún no esté operativo.

</div>

<a id="attributes"></a>

<a id="2-user-attributes"></a>

## 2. Atributos del usuario

Abra **Atributos de usuario**. El modelo normalmente incluye correo electrónico, nombre y apellido. Agregue un atributo cuando la aplicación deba almacenar un valor fuera del perfil básico, como una institución, departamento, número de empleado o una función de directorio externo.

<span class="figure editor-html-media-figure editor-html-media-image d-inline-block align-top me-2 mb-2 w-100"><img src="images/f1ea79f6-6612-4b72-bb14-29ff71f2a9d8.png" class="figure-img img-fluid rounded mb-1" alt="Configuraciones para atributos de usuario predeterminados y personalizados." /><span class="figure-caption d-block">Atributos de usuario</span></span>

<figure class="my-4">

<figcaption>Atributos predeterminados y personalizados en la interfaz en inglés.</figcaption>
</figure>

<a id="adding-an-attribute"></a>

### Agregar un atributo

1.  Ingrese un nombre de máquina estable en minúsculas y sin espacios, como `affiliation`.
2.  Proporcione etiquetas separadas en croata e inglés.
3.  Elija el tipo de datos y configure **Obligatorio**, **Registro**, **Perfil** y **Activo** de forma independiente.
4.  Guárdelo. El nuevo campo pasa a estar disponible en los perfiles de usuario y en cada tabla de asignación de perfiles SSO.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/6e88b4f0-77b9-4da0-8e7a-e84f74f86e7e.png" class="img-fluid rounded border" alt="Ejemplo de atributo personalizado bilingüe" /> <span class="figure-caption d-block">Ejemplo de atributo personalizado bilingüe</span> </span>
<figcaption>El campo del modelo de afiliación tiene la etiqueta Ustanova/Institución, permanece disponible para perfiles y asignaciones SSO y está excluido del autorregistro.</figcaption>
</figure>

**Asignación** conecta un campo de modelo Simbioza con el nombre de atributo exacto devuelto por el proveedor. Los nombres de los atributos son exactos: `mail` , `givenName` y `given_name` son campos diferentes. Pruebe primero, inspeccione los nombres devueltos y solo luego ingrese las asignaciones. Una asignación SSO no agrega automáticamente el campo al formulario de registro público.

<div class="alert alert-info">

**¿Qué campos se incluyen en el autorregistro?** El administrador elige esto por atributo. **Registro** controla si un campo aparece en el formulario público, mientras que **Obligatorio** rechaza un valor vacío en contextos donde ese campo participa. Cuando ambas configuraciones están habilitadas, el campo es obligatorio durante el autorregistro. Un atributo requerido también debe tener una asignación SSO válida para que una prueba de perfil de proveedor entre en funcionamiento. En este ejemplo, `affiliation` almacena la institución proporcionada por un proveedor y aparece en el perfil, por lo que se excluye intencionalmente del registro.

</div>

<a id="groups"></a>

<a id="3-groups"></a>

## 3. Grupos

Los grupos recopilan usuarios y otorgan permisos. El grupo Administrador es un grupo de sistema protegido y no debe utilizarse como un grupo empresarial normal. Cree grupos separados con privilegios mínimos para departamentos, proyectos o roles.

<span class="figure editor-html-media-figure editor-html-media-image d-inline-block align-top me-2 mb-2 w-100"><img src="assets/group-list_en.jpg" class="figure-img img-fluid rounded mb-1" alt="Listado de sistemas y grupos de ejemplo con acciones de gestión." /><span class="figure-caption d-block">Grupos</span></span>

Sistema y grupos de ejemplo en la interfaz en inglés.\

1.  Elija **Agregar grupo** (el botón más), ingrese un **Nombre de grupo** reconocible y marque **Activo**.
2.  Elija **Guardar grupos**. Esta pantalla no contiene descripción ni permisos de contenido.
3.  Agregue o elimine membresía de grupo de los detalles de cada usuario.
4.  Otorgue al grupo los permisos necesarios en la configuración de un espacio de trabajo, página, calendario o encuesta. La membresía SSO también puede seguir reglas basadas en un atributo asignado.

**Resultado:** el grupo existe y se puede seleccionar al asignar permisos. Crearlo por sí solo no abre contenido a sus miembros.

Para desactivar temporalmente un grupo que no sea del sistema, desmarque **Activo** y guarde. Para eliminarlo definitivamente, elija **Eliminar grupo**. En la pantalla de confirmación, compruebe el nombre y las consecuencias indicadas para las membresías y las reglas de asignación; después confirme la eliminación o cancele. Las cuentas de usuario se conservan. Un nombre vacío ya no elimina un grupo. Antes de confirmar, compruebe dónde se usa el grupo para acceder al contenido. Un grupo nuevo con el mismo nombre tiene otra identidad y no recupera las membresías ni los permisos anteriores. Los grupos del sistema no se pueden eliminar.

<figure class="my-4">
<img src="assets/group-deletion_en.jpg" class="img-fluid rounded border" alt="Compruebe el nombre y las consecuencias antes de confirmar. Cancelar vuelve a la lista sin eliminar nada." />
<figcaption>Compruebe el nombre y las consecuencias antes de confirmar. Cancelar vuelve a la lista sin eliminar nada.</figcaption>
</figure>

<a id="users"></a>

<a id="4-users-and-local-accounts"></a>

## 4. Usuarios y cuentas locales

Abra **Configuración → Usuarios → Usuarios** para buscar cuentas, inspeccionar su estado y administrar la membresía y el acceso.

<span class="figure editor-html-media-figure editor-html-media-image d-inline-block align-top me-2 mb-2 w-100"><img src="images/a00666a6-9521-4f7d-bd23-8c116dfc53a8.png" class="figure-img img-fluid rounded mb-1" alt="Lista responsiva de cuentas de usuario con acciones de gestión." /><span class="figure-caption d-block">Tabla de usuario responsiva</span></span>

<figure class="my-4">
<span>Columnas equilibradas de la tabla de usuarios en la interfaz en inglés.</span>
</figure>

<a id="creating-a-local-user"></a>

### Creando un usuario local

1.  Seleccione **Nuevo usuario**.
2.  Ingrese un identificador de inicio de sesión único, un nombre para mostrar y un correo electrónico.
3.  Elija la fuente de autenticación local y asigne una contraseña temporal segura.
4.  Requerir un cambio de contraseña en el primer inicio de sesión.
5.  Asigne sólo los grupos requeridos; el acceso de administrador no debe ser el predeterminado.

<span class="figure editor-html-media-figure editor-html-media-image d-inline-block align-top me-2 mb-2 w-100"><img src="images/8eb23938-b2cd-4e3f-b7f2-2f19aa91a301.png" class="figure-img img-fluid rounded mb-1" alt="Formulario para crear una cuenta de usuario local." /><span class="figure-caption d-block">Crear formulario de usuario local</span></span>

<figure class="my-4">
<span>Ejemplo de formulario de cuenta local sin contraseña.</span>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/80bea9cb-0777-45c4-96ce-3ee97e33ceab.png" class="img-fluid rounded border" alt="Nuevo usuario en la lista" /> <span class="figure-caption d-block">Nuevo usuario en la lista</span> </span>
<figcaption>El usuario de la documentación después de la creación.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/8caff857-27dd-4bcd-9492-82641074e399.png" class="img-fluid rounded border" alt="Detalles del usuario" /> <span class="figure-caption d-block">Detalles del usuario</span> </span>
<figcaption>Estado de la cuenta, proveedores vinculados y membresías de grupos.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/46024a32-147e-49bb-9097-4a925d195440.png" class="img-fluid rounded border" alt="Cambio de contraseña obligatorio en el primer inicio de sesión" /> <span class="figure-caption d-block">Cambio de contraseña obligatorio en el primer inicio de sesión</span> </span>
<figcaption>Una contraseña temporal obliga al usuario a elegir una nueva antes de continuar.</figcaption>
</figure>

En la página de detalles, verifique la actividad de la cuenta, la fuente de autenticación, las identidades externas vinculadas y las membresías de grupos. La desactivación suele ser más segura que la eliminación porque preserva el rastro de auditoría. Nunca reemplace manualmente el identificador de sujeto estable de un proveedor externo.

<a id="local-login"></a>

<a id="5-local-login-and-registration"></a>

## 5. Inicio de sesión y registro local

**El inicio de sesión local** controla el autorregistro. Manténgalo deshabilitado para sistemas cerrados y crea cuentas administrativamente o a través de SSO de confianza. Si está habilitado, el registro crea una cuenta ordinaria activa e inicia la sesión del usuario. No envíe un correo electrónico de verificación ni prueba la propiedad de la dirección ingresada. Para un sistema cerrado, mantenga el registro desactivado y utilice cuentas creadas por el administrador o un proveedor SSO de confianza. Publicar un aviso de privacidad adecuado antes de aceptar registros. El formulario público incluye solo atributos de usuario cuya opción **Registro** está habilitada; por lo tanto, el campo *Institución* exclusivo de SSO no se muestra aquí.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/d88655d6-11ff-40c9-aef9-82a7e31d919b.png" class="img-fluid rounded border" alt="Registro deshabilitado" /> <span class="figure-caption d-block">Registro deshabilitado</span> </span>
<figcaption>Estado inicial recomendado para una aplicación cerrada.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/71149ada-dd68-4f4f-8db6-7705ba261edd.png" class="img-fluid rounded border" alt="Registro habilitado" /> <span class="figure-caption d-block">Registro habilitado</span> </span>
<figcaption>Registro habilitado solo para un ejemplo controlado.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/d18a4b43-a1b2-4078-a810-b18a02cc0793.png" class="img-fluid rounded border" alt="Enlace de registro" /> <span class="figure-caption d-block">Enlace de registro</span> </span>
<figcaption>La pantalla de inicio de sesión muestra el registro solo después de que esté habilitado.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/4f5652ca-62a4-4b3a-857c-6fccf29b2e76.png" class="img-fluid rounded border" alt="Formulario de inscripción" /> <span class="figure-caption d-block">Formulario de inscripción</span> </span>
<figcaption>Formulario público con los campos de registro seleccionados; el atributo Institución utilizado para el mapeo SSO está oculto intencionalmente.</figcaption>
</figure>

Después de la prueba, restablezca el estado de producción previsto. Las contraseñas deben ser únicas, suficientemente largas y almacenadas en un administrador de contraseñas. Los administradores nunca deben pedir a los usuarios su contraseña actual.

<a id="impersonation"></a>

<a id="6-impersonation"></a>

## 6. Suplantación

**Suplantar usuario** abre temporalmente la aplicación en el contexto del usuario seleccionado. Úselo para obtener permisos y mostrar diagnósticos, no para realizar acciones comerciales en nombre del usuario.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/08b8a426-17a9-4fcd-b69c-1d9f877eb742.png" class="img-fluid rounded border" alt="Pantalla de suplantación" /> <span class="figure-caption d-block">Pantalla de suplantación</span> </span>
<figcaption>Seleccionar una cuenta para diagnósticos de soporte controlados. <span>Seleccione solo la cuenta necesaria para el caso de soporte.</span></figcaption>
</figure>

<figure class="my-4">
<span>La interfaz indica claramente la sesión suplantada activa.</span>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/a0e76efd-bc62-4268-b508-d7de6e12fce8.png" class="img-fluid rounded border" alt="Volver al administrador" /> <span class="figure-caption d-block">Volver al administrador</span> </span>
<figcaption>Finalice la tarea regresando a su cuenta de administrador.</figcaption>
</figure>

<div class="alert alert-warning">

La suplantación no prueba la autenticación SSO real, MFA ni la política del proveedor. Utilice la prueba de perfil o una cuenta de prueba dedicada para ese fin.

</div>

<a id="api-keys"></a>

<a id="7-api-keys"></a>

## 7. Teclas API

Estas pantallas están disponibles cuando el módulo API está instalado y activado. Una clave de API actúa con los derechos de su propietario. Los ámbitos solo pueden limitar los permisos existentes; no conceden permisos que el propietario no tenga. Use una clave distinta para cada integración, una etiqueta descriptiva, la menor duración práctica y el mínimo conjunto de ámbitos necesario.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/33c88c2c-3956-4eaf-840d-5f236fb052a1.png" class="img-fluid rounded border" alt="API administración de claves" /> <span class="figure-caption d-block">API administración de claves</span> </span>
<figcaption>API administración de claves en la interfaz en inglés.</figcaption>
</figure>

<a id="direct-key-issuance"></a>

### Emisión de clave directa

1.  Seleccione el propietario y solo los ámbitos requeridos.
2.  Establezca una fecha de vencimiento y, si es necesario, una dirección IP permitida o una restricción de red.
3.  Cree la clave y copie inmediatamente el secreto para un almacenamiento seguro.
4.  El secreto se muestra sólo una vez. Si se pierde, revoque la clave y emita una nueva.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/89146be8-0fe9-4bdd-a406-d92bc09e00bc.png" class="img-fluid rounded border" alt="Clave de API de privilegios mínimos" /> <span class="figure-caption d-block">Clave de API de privilegios mínimos</span> </span>
<figcaption>Una clave restringida intencionalmente antes de la creación.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/4f972d0e-66b0-4085-8234-5e3b53fdb175.png" class="img-fluid rounded border" alt="Secreto único API" /> <span class="figure-caption d-block">Secreto único API</span> </span>
<figcaption>El secreto ha sido eliminado de la captura de pantalla.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/39bcd07d-03f4-4f31-8085-0b34a0604698.png" class="img-fluid rounded border" alt="Teclas API activas" /> <span class="figure-caption d-block">Teclas API activas</span> </span>
<figcaption>Revise y revoque las claves activas de esta lista.</figcaption>
</figure>

<a id="user-request-and-administrator-approval"></a>

### Solicitud de usuario y aprobación del administrador.

Un usuario puede solicitar una clave desde su perfil personal y explicar su finalidad. El administrador revisa el propietario, los alcances, la duración y la justificación y luego aprueba o rechaza la solicitud. Después de la aprobación, el usuario puede revelar el secreto una vez en el perfil personal. Cuando las Notificaciones están habilitadas, una notificación dentro de la aplicación también apunta a esa pantalla. Ni la notificación ni un correo electrónico deben contener el secreto.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/fcdeaf36-1804-4d2f-8639-78774d1f3580.png" class="img-fluid rounded border" alt="API formulario de solicitud de clave" /> <span class="figure-caption d-block">API formulario de solicitud de clave</span> </span>
<figcaption>El usuario solicita una clave restringida y declara su finalidad.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/e20ced95-af39-44d6-ad45-775b198caabb.png" class="img-fluid rounded border" alt="Solicitud enviada" /> <span class="figure-caption d-block">Solicitud enviada</span> </span>
<figcaption>La solicitud espera la revisión del administrador.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/7f412246-9d37-4f65-a008-49c42fd15f68.png" class="img-fluid rounded border" alt="Solicitud pendiente API" /> <span class="figure-caption d-block">Solicitud pendiente API</span> </span>
<figcaption>El administrador verifica la solicitud antes de aprobarla.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/1b731910-dbde-43d7-8e72-416157977d5a.png" class="img-fluid rounded border" alt="Solicitud API aprobada" /> <span class="figure-caption d-block">Solicitud API aprobada</span> </span>
<figcaption>La aprobación crea la clave sin revelar el secreto al administrador.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/36020798-5696-4756-a707-3a463d582f49.png" class="img-fluid rounded border" alt="API notificación clave" /> <span class="figure-caption d-block">API notificación clave</span> </span>
<figcaption>Se notifica al usuario sobre la decisión.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/be701281-d85e-40a8-ada2-2668a2c212c0.png" class="img-fluid rounded border" alt="Notificación de solicitud aprobada" /> <span class="figure-caption d-block">Notificación de solicitud aprobada</span> </span>
<figcaption>La notificación conduce a la recuperación secreta por única vez.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/140963e5-a521-4a24-83df-d8a549fb49da.png" class="img-fluid rounded border" alt="Recuperación secreta única API" /> <span class="figure-caption d-block">Recuperación secreta única API</span> </span>
<figcaption>El secreto se elimina y no se puede volver a mostrar después del cierre.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/0c3b8928-1587-4071-b847-2d66589c6c03.png" class="img-fluid rounded border" alt="Solicitud API completada" /> <span class="figure-caption d-block">Solicitud API completada</span> </span>
<figcaption>La solicitud completada sigue siendo auditable sin exponer el secreto.</figcaption>
</figure>

<a id="sso"></a>

<a id="8-common-sso-workflow"></a>

## 8. Flujo de trabajo común SSO

1.  Registre la aplicación con el proveedor e ingrese solo los puntos finales, la identificación del cliente y el secreto necesarios.
2.  Guarde el perfil activo. Los secretos existentes nunca se muestran durante la edición; dejar el campo secreto vacío conserva el valor del lado del servidor.
3.  Seleccione **Probar perfil** antes de adivinar las asignaciones. Complete un inicio de sesión real para recopilar los atributos reales del proveedor.
4.  Asigne los campos de identificador de sujeto, inicio de sesión, nombre para mostrar y usuario de los nombres devueltos.
5.  Prueba de nuevo. Solo **Probado y operativo** junto con una marca verde confirma un perfil completo.

<a id="allowed-domains"></a>

### Dominios permitidos

La lista restringe el inicio de sesión a dominios organizacionales. Ingrese un dominio por línea o una lista separada por comas. `example.org` permite ese dominio y sus subdominios; un sufijo como `hr` permite identificadores cuyo dominio termina en `.hr`. Una lista vacía no impone ninguna restricción de dominio.

**El atributo de origen del dominio** debe ser el atributo del proveedor real que contiene un valor como `user@example.org` o un dominio. Sólo es necesario cuando la lista de dominios permitidos está completa. No utilice un nombre para mostrar ni otro valor mutable.

<a id="public-callback-addresses"></a>

### Direcciones públicas de devolución de llamadas

El proveedor deberá devolver el navegador a la URL pública de la misma instalación. Incluya un subdirectorio cuando se utilice uno: para una aplicación en `https://simbioza.example.org/simbioza`, ingrese ese valor completo como la URL base pública. No es la dirección interna PHP-FPM, contenedor o base de datos. Registre las URL de devolución de llamada exactas que muestra el perfil.

<a id="saml"></a>

## 9. SAML

Un perfil SAML depende de un **SimpleSAMLphp** instalado por separado y configurado correctamente. Su cargador automático, fuente de autenticación de SP, certificados, metadatos y URL de retorno ya deben funcionar. La ruta a `vendor/autoload.php` y el nombre de la fuente de autenticación deben coincidir con esa instalación.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/d33dc6ed-ec34-4a5f-b54b-94354364bd31.png" class="img-fluid rounded border" alt="Configuración inicial SAML" /> <span class="figure-caption d-block">Configuración inicial SAML</span> </span>
<figcaption>El perfil se guarda, pero las asignaciones aún no están confirmadas.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/fc09510d-0f1e-44ed-88b5-22d64f4d151f.png" class="img-fluid rounded border" alt="Primera prueba SAML" /> <span class="figure-caption d-block">Primera prueba SAML</span> </span>
<figcaption>Se recogieron obras y atributos de conectividad; los valores están redactados.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/21676ed7-d1a6-4008-8ab6-8885b087e737.png" class="img-fluid rounded border" alt="Iniciar el inicio de sesión de AAI" /> <span class="figure-caption d-block">Iniciar el inicio de sesión de AAI</span> </span>
<figcaption>La pantalla de inicio de sesión en inglés Simbioza inicia el flujo AAI@EduHr SSO configurado sin exponer las credenciales.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/a92bb0e8-0687-4f28-88c7-e02fa8f36b42.png" class="img-fluid rounded border" alt="SAML asignaciones" /> <span class="figure-caption d-block">SAML asignaciones</span> </span>
<figcaption>Asignaciones requeridas ingresadas a partir de los atributos devueltos reales.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/31d0a1e9-cc0a-479b-afbc-9aadcd604748.png" class="img-fluid rounded border" alt="Perfil operativo SAML" /> <span class="figure-caption d-block">Perfil operativo SAML</span> </span>
<figcaption>La prueba repetida confirma un perfil SAML operativo.</figcaption>
</figure>

<a id="saml-proxy"></a>

<a id="10-saml-proxy-and-child-profiles"></a>

## 10. SAML perfiles secundarios y de proxy

Una instancia de proxy representa una fuente de autenticación SimpleSAMLphp que puede exponer varios proveedores ascendentes. La pestaña **Predeterminada** define la ruta de carga automática compartida, la fuente de autenticación y la asignación base. Las pestañas secundarias adicionales heredan la configuración de conexión compartida y mantienen sus propias asignaciones de atributos.

**Un proxy no tiene campos de atributos de origen de dominio ni dominios permitidos.** Esto es deliberado: los proveedores ascendentes pueden devolver identificadores y dominios diferentes. Aplique restricciones en el proxy o en el proveedor ascendente, no a través de una regla de dominio compartida Simbioza.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/4f8e5ae8-57d5-4e8a-878c-ac5e78821030.png" class="img-fluid rounded border" alt="Selección de proveedor de proxy" /> <span class="figure-caption d-block">Selección de proveedor de proxy</span> </span>
<figcaption>El inicio de sesión de proxy real ofrece AAI@EduHR, Facebook y Google, lo que explica los tres perfiles infantiles.</figcaption>
</figure>

<a id="default--aaieduhr"></a>

### Predeterminado / AAI@EduHR

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/faa2088e-6d03-4385-852f-0bab107c5ae1.png" class="img-fluid rounded border" alt="Primera prueba de proxy predeterminada" /> <span class="figure-caption d-block">Primera prueba de proxy predeterminada</span> </span>
<figcaption>Atributos recopilados antes del mapeo, con valores eliminados.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/3cf0d969-0b78-4ba6-ae53-89391e530d7d.png" class="img-fluid rounded border" alt="Asignaciones de proxy predeterminadas" /> <span class="figure-caption d-block">Asignaciones de proxy predeterminadas</span> </span>
<figcaption>Asignaciones para la fuente de AAI predeterminada.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/d731b742-31b9-447a-9bf7-b481b3417240.png" class="img-fluid rounded border" alt="Perfil de proxy predeterminado operativo" /> <span class="figure-caption d-block">Perfil de proxy predeterminado operativo</span> </span>
<figcaption>Pestaña de proxy predeterminada después de la prueba repetida exitosa.</figcaption>
</figure>

### Facebook

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/a44f01cb-48ba-4b0d-bdb4-f7257ede99c5.png" class="img-fluid rounded border" alt="Primera prueba de proxy de Facebook" /> <span class="figure-caption d-block">Primera prueba de proxy de Facebook</span> </span>
<figcaption>Atributos de Facebook recopilados antes del mapeo, con valores eliminados.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/b8477a91-99c7-474b-bdd0-62f141c956e0.png" class="img-fluid rounded border" alt="Mapeos infantiles de Facebook" /> <span class="figure-caption d-block">Mapeos infantiles de Facebook</span> </span>
<figcaption>Campos estables de ID de Facebook, inicio de sesión y usuario asignados de forma independiente.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/cd8a17cd-616a-4826-8caa-85ace9446401.png" class="img-fluid rounded border" alt="Perfil infantil de Facebook operativo" /> <span class="figure-caption d-block">Perfil infantil de Facebook operativo</span> </span>
<figcaption>Pestaña secundaria de Facebook después de un mapeo y una prueba exitosos.</figcaption>
</figure>

### Google

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/c0338f0d-59e3-4a6b-ab0b-fdd3a1ee8f0c.png" class="img-fluid rounded border" alt="Primera prueba de proxy de Google" /> <span class="figure-caption d-block">Primera prueba de proxy de Google</span> </span>
<figcaption>Atributos de Google recopilados antes del mapeo, con valores eliminados.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/da95a7fc-0699-4fb3-b8f3-874cdfd0f370.png" class="img-fluid rounded border" alt="Asignaciones infantiles de Google" /> <span class="figure-caption d-block">Asignaciones infantiles de Google</span> </span>
<figcaption>El UID de Google es el identificador de sujeto, el correo electrónico de Google es el inicio de sesión y los campos de nombre se asignan al modelo.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/4290218a-cbf8-4cd3-b989-0c4c2700a349.png" class="img-fluid rounded border" alt="Perfil infantil de Google operativo" /> <span class="figure-caption d-block">Perfil infantil de Google operativo</span> </span>
<figcaption>Pestaña secundaria de Google después de la prueba repetida exitosa.</figcaption>
</figure>

<a id="cas"></a>

## 11. CAS

Un perfil CAS necesita la URL base del servidor y sus puntos finales de inicio de sesión, validación y cierre de sesión. Si se utiliza una cadena de CA privada, proporcione una ruta de certificado legible. Nunca deshabilite la validación TLS para solucionar una configuración de certificado incorrecta.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/aa4de493-9953-4a64-a76c-548b7aa92c9d.png" class="img-fluid rounded border" alt="Configuración inicial CAS" /> <span class="figure-caption d-block">Configuración inicial CAS</span> </span>
<figcaption>La conexión CAS está preparada pero las asignaciones no están completas.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/417fd550-238d-4b9b-b008-280738935d04.png" class="img-fluid rounded border" alt="Primera prueba CAS" /> <span class="figure-caption d-block">Primera prueba CAS</span> </span>
<figcaption>CAS la autenticación devuelve atributos; los valores están redactados.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/1e2d14d5-7702-4326-8bca-73f266673fa9.png" class="img-fluid rounded border" alt="CAS asignaciones" /> <span class="figure-caption d-block">CAS asignaciones</span> </span>
<figcaption>Campos estables de identificador de sujeto, inicio de sesión, nombre para mostrar y modelo asignados a partir de la prueba.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/c4341d65-56fa-4c42-82cf-28db9b05e563.png" class="img-fluid rounded border" alt="Perfil operativo CAS" /> <span class="figure-caption d-block">Perfil operativo CAS</span> </span>
<figcaption>La prueba repetida confirma el perfil CAS.</figcaption>
</figure>

<a id="oidc"></a>

## 12. OIDC

OIDC utiliza un ID de cliente y un secreto, autorización, token, información de usuario y, opcionalmente, puntos finales de finalización de sesión. El alcance debe incluir `openid` , seguido únicamente de datos obligatorios como `profile` y `email` . **URL base de redireccionamiento público** es la dirección base pública completa de esta instalación, incluido un subdirectorio cuando esté presente.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/a8ba39ed-751e-4586-9199-a5cdf7b80c8c.png" class="img-fluid rounded border" alt="Configuración inicial OIDC" /> <span class="figure-caption d-block">Configuración inicial OIDC</span> </span>
<figcaption>OIDC puntos finales y URL base pública antes del mapeo.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/7dd1a0ab-0d45-48f9-a529-7fb5016ba951.png" class="img-fluid rounded border" alt="Primera prueba OIDC" /> <span class="figure-caption d-block">Primera prueba OIDC</span> </span>
<figcaption>OIDC atributos recibidos recopiladas antes del mapeo, con valores eliminados.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/ff56adb1-ea0d-4d0a-bf0e-02b358a71c05.png" class="img-fluid rounded border" alt="OIDC mapeo y dominio" /> <span class="figure-caption d-block">OIDC mapeo y dominio</span> </span>
<figcaption>Identificador de sujeto, inicio de sesión, nombre para mostrar, campos de usuario y origen del dominio mapeados a partir de atributos recibidos reales.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/0603f7ff-a5e7-4f13-a6e6-b7b144b992a5.png" class="img-fluid rounded border" alt="Perfil operativo OIDC" /> <span class="figure-caption d-block">Perfil operativo OIDC</span> </span>
<figcaption>La prueba repetida confirma OIDC y muestra la marca de verificación verde.</figcaption>
</figure>

Utilice un parámetro de inicio de sesión forzado como `max_age=0` solo cuando el proveedor lo admita y **Forzar inicio de sesión** esté habilitado. De lo contrario, déjelo en blanco para conservar una sesión SSO existente válida.

<a id="oauth2"></a>

## 13. OAuth2

OAuth2 utiliza puntos finales de autorización, token e información de usuario, además de los ámbitos necesarios para los datos del usuario. Si el proveedor admite OpenID Connect, prefiera OIDC porque estandariza la validación de identidades y tokens. OAuth2 cerrar sesión puede requerir un punto final dedicado, un nombre de parámetro de redireccionamiento o una sugerencia; introduzca sólo los valores requeridos por la documentación del proveedor.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/c8024292-d7ab-4926-82fc-3ae3135df40e.png" class="img-fluid rounded border" alt="Configuración inicial OAuth2" /> <span class="figure-caption d-block">Configuración inicial OAuth2</span> </span>
<figcaption>OAuth2 perfil antes de la asignación de atributos.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/5a358208-3572-4641-970a-278bf2b22082.png" class="img-fluid rounded border" alt="Primera prueba OAuth2" /> <span class="figure-caption d-block">Primera prueba OAuth2</span> </span>
<figcaption>Atributos de información de usuario recopilados a través de un inicio de sesión real, con valores eliminados.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/0df4572c-6616-4522-939e-f825f70504d2.png" class="img-fluid rounded border" alt="OAuth2 mapeo y dominio" /> <span class="figure-caption d-block">OAuth2 mapeo y dominio</span> </span>
<figcaption>Asignaciones y restricción de dominio ingresadas a partir del resultado de la prueba.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/d3faabdb-f174-4a6b-9f00-45f56e11eb3b.png" class="img-fluid rounded border" alt="Perfil operativo OAuth2" /> <span class="figure-caption d-block">Perfil operativo OAuth2</span> </span>
<figcaption>La prueba repetida confirma el perfil OAuth2.</figcaption>
</figure>

<a id="personal-profile"></a>

<a id="14-personal-profile-security-and-access"></a>

## 14. Perfil personal: seguridad y acceso

Abra **Perfil personal** desde el menú de usuario. La sección principal muestra identidad y atributos personalizados. **Seguridad y acceso** muestra la membresía del grupo, el estado de la contraseña local, los inicios de sesión externos vinculados y las solicitudes de clave de API.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/52960239-b877-4349-bd2a-016f5837e8f7.png" class="img-fluid rounded border" alt="perfil personal" /> <span class="figure-caption d-block">perfil personal</span> </span>
<figcaption>Datos personales y atributos personalizados en la interfaz en inglés.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/12c871d9-5263-4a65-93f0-43ed0980d549.png" class="img-fluid rounded border" alt="Seguridad y acceso ampliados" /> <span class="figure-caption d-block">Seguridad y acceso ampliados</span> </span>
<figcaption>Grupos, inicio de sesión local, proveedores vinculados y solicitudes API en un solo lugar.</figcaption>
</figure>

Vincula una cuenta externa solo mientras controlas ambas identidades. Elimine los enlaces desconocidos o no utilizados, pero primero confirme que haya otro método de inicio de sesión disponible. Cambie las contraseñas locales a través de este formulario seguro, nunca a través de un correo electrónico o un mensaje de administrador.

<a id="verification"></a>

<a id="15-verification-and-security-checklist"></a>

## 15. Lista de verificación y seguridad

- Mantenga disponible al menos un inicio de sesión de administrador controlado; guarde la cuenta de acceso de emergencia fuera del uso diario normal.
- Para cada perfil SSO, verifique las URL de devolución de llamada exactas, los certificados TLS, los alcances mínimos y la rotación de secretos.
- No acepte una casilla de verificación activa como prueba. Requiere el estado operativo verde después de la prueba repetida.
- El identificador de sujeto debe ser un identificador de proveedor inmutable. El inicio de sesión y el correo electrónico pueden cambiar y no siempre son identificadores primarios adecuados.
- Pruebe las restricciones de dominio con una cuenta de prueba permitida y denegada.
- Revise periódicamente los administradores, las membresías de grupos, los inicios de sesión externos vinculados, las claves API activas y las solicitudes pendientes.
- Nunca incluya contraseñas, secretos de cliente, tokens, secretos API ni atributos personales sin procesar en capturas de pantalla, documentación, tickets o control de fuente.
- Después de la prueba, elimine o desactive las cuentas de documentación y las claves que ya no sean necesarias.

<div class="alert alert-success">

Después de guardar, pruebe un inicio de sesión normal y una cuenta denegada. Confirme que cada usuario vea solo el contenido y las acciones asignadas a sus grupos.

</div>
