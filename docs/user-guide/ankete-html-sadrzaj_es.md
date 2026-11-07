# Contenido HTML y seguridad

<a id="html-content-and-safety"></a>


> Las capturas de pantalla muestran la interfaz en inglés.

[Para desarrolladores: API](ankete-developeri_es.md)

El HTML integrado pasa a través del mismo desinfectante del lado del servidor como entrada del editor. La edición de código fuente no es una excepción a la política de seguridad.

<div class="table-responsive">

| Contenido | Comportamiento |
|----|----|
| Texto ordinario, b/strong, i/em, encabezados, listas, tablas y medios seguros | Permitido dentro de la política del editor HTML. |
| script, estilo y controladores como onclick/onerror | Remoto; nunca los utilice para la lógica de la encuesta. |
| javascript:, datos: y URL ejecutables similares | Rechazado. |
| Atributos y CSS no admitidos | Puede eliminarse incluso cuando no sea peligroso. |
| Gráficos, hojas de ruta, pestañas, acordeones, menús desplegables y tablas interactivas | Las marcas y configuraciones del Editor admitidas se conservan y utilizan la pantalla del Editor compartida. |
| Bloques de calendario, tareas, páginas incluidas y espacios de trabajo | Se conservan las referencias y configuraciones seguras. Sin un servicio de host que resuelva el bloqueo, sigue siendo un marcador de posición inerte. |
| incrustaciones de iframe | Solo se conservan las incrustaciones aceptadas por la política del Editor compartido, incluida su configuración de protección requerida. |

</div>

Utilice las estructuras canónicas creadas por el Editor en lugar de marcas ejecutables arbitrarias. Aplicar y guardar conserva el contenido compatible; la visualización del gráfico o la hoja de ruta generada no reemplaza su fuente editable. Survey no convierte las referencias de registros de scripts en scripts ejecutables ni otorga acceso a los objetivos de los bloques dinámicos. Proporcione condiciones de preguntas como definiciones declarativas admitidas, no JavaScript.

Respetar la autorización de archivos adjuntos al integrar sistemas. Un enlace no otorga acceso a contenido privado. Trate siempre las respuestas de los participantes como datos, no como HTML ejecutables.
