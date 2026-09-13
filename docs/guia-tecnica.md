# Tecnologías y métodos de SiGeRU

Revisión: 13/09/2026. Referencias: README original, letra ESI 2026 y rúbricas aportadas. Esta guía explica la aplicación y los formatos de documentación y despliegue.

## Tecnologías

| Tecnología | Uso concreto | Justificación |
|---|---|---|
| HTML5 | Páginas, formularios, tablas y enlaces. | README y letra. |
| CSS | Colores, tamaños, distribución y adaptación visual. | README y letra. |
| JavaScript | Eventos, validación, llamadas a APIs, actualización de vistas y pruebas del navegador/Postman. | README y letra; sin Node.js. |
| PHP 8 | Endpoints, clases, sesiones, reglas, persistencia y pruebas HTTP. | README y letra. |
| SQL / MySQL 8 | Modelo físico, relaciones, consultas y almacenamiento persistente. | Letra y rúbricas. |
| MariaDB | Motor de desarrollo incluido en XAMPP. | README; queda pendiente verificar MySQL 8. |
| PDO / pdo_mysql | Extensión PHP para conexión, parámetros preparados y transacciones. | README. |
| Apache | Sirve páginas y ejecuta PHP; en Docker reenvía peticiones a las APIs. | Servidor de XAMPP, entorno del README. |
| XAMPP | Entorno local que reúne Apache, PHP, MariaDB y phpMyAdmin. | README. |
| phpMyAdmin | Herramienta opcional para importar SQL y consultar la base. | Incluida en XAMPP; no es lógica del producto. |
| Leaflet | Biblioteca JavaScript para los mapas y marcadores. | README original. |
| OpenStreetMap | Cartografía mostrada por Leaflet. | README original. |
| Nominatim | Busca coordenadas a partir de una dirección. | README original; necesita conexión al servicio. |
| Git / GitHub | Git registra versiones; GitHub aloja los repositorios. | Ambas rúbricas y letra. |
| Postman | Ejecuta peticiones HTTP y comprueba respuestas. | README y rúbrica. |
| Docker / Compose | Define imágenes, servicios, puertos y volúmenes. | Segunda entrega. |
| Linux / Shell | Servidor final y scripts de administración y respaldo. | Letra de Sistemas Operativos. |
| JSON | Intercambio de datos y colección Postman. | Rúbrica y REST; es un formato, no otro lenguaje del backend. |
| YAML / Dockerfile | Configuración de servicios e imágenes Docker. | Archivos de despliegue pedidos por la letra. |
| Markdown / SVG | Documentación y dibujo vectorial de clases. | Diagramas y documentación solicitados; no son lógica ejecutable. |

Se retiraron Python, el generador de diagramas y las pruebas dependientes de Node.js. Nginx fue reemplazado por Apache. No se incorporan React, TypeScript ni Laravel.

## APIs y métodos HTTP

Hay tres APIs: Usuarios, Gestión y Recolección. Cada una agrupa varios endpoints. Por ejemplo, `gestion/contenedores.php` pertenece a Gestión. Notificaciones pertenece a Usuarios. Los grupos aún comparten modelos, controladores y base de datos; los servicios Docker separados no prueban por sí solos aplicaciones independientes.

| Método | Función | Ejemplo |
|---|---|---|
| GET | Consulta sin modificar. | Listar contenedores o consultar `?id=1`. |
| POST | Crear o ejecutar una acción de sesión. | Registrar usuario; login; logout. |
| PUT | Actualizar un recurso identificado. | Modificar usuario; asignar o resolver incidencia. |
| DELETE | Dar de baja donde corresponda. | Contenedor con ID y motivo; conserva historial. |

No todos los recursos admiten todos los métodos. Una incidencia se resuelve, no se borra. Los históricos no se sobrescriben libremente. La respuesta JSON contiene `status` y, según la operación, `message`, `data`, `id` o `csrf`.

| Código | Significado |
|---|---|
| 200 / 201 | Consulta o cambio correcto / creación correcta. |
| 400 | Datos inválidos. |
| 401 | Sin sesión o credenciales válidas. |
| 403 | Sin permiso, solicitud no aprobada o CSRF inválido. |
| 404 | Recurso inexistente. |
| 405 | Método no admitido. |
| 409 | Conflicto, duplicado o estado incompatible. |
| 415 | Tipo de contenido no admitido. |
| 429 | Bloqueo temporal del login. |
| 500 | Fallo interno; no expone detalles del servidor. |

## MVC y métodos de clases

Vista: HTML/CSS/JavaScript. Controladores: validan y coordinan negocio. Modelos: contienen SQL y persistencia. Endpoints: reciben peticiones y llaman al controlador. `core` concentra sesión, validación y respuesta.

| Clase o grupo | Responsabilidad de sus métodos |
|---|---|
| AuthController | `login` autentica y crea sesión; `registro` recibe solicitudes de vecino. |
| UsuarioController / Usuario | Listar, obtener, crear, actualizar y dar de baja cuentas; sincronizar especializaciones del rol. |
| ContenedorController / Contenedor | CRUD, condición de servicio y comprobación de cuadrilla asignada. |
| CamionController / Camion | CRUD, disponibilidad y asignación/desasignación de cuadrilla. |
| CentroAcopioController / CentroAcopio | CRUD, capacidad y baja de instalaciones. |
| IncidenciaController / Incidencia | Crear, listar, asignar, resolver, vincular contenedor, registrar historial y cerrar reclamos asociados. |
| NotificacionController / Notificacion | Consultar mensajes propios/anuncios, publicar y registrar lectura. |
| OperacionController | `atender` selecciona operaciones permitidas y valida actor, campos, fechas y relaciones. |
| Operacion | Persistencia de rutas, cuadrillas, maquinaria, residuos, recepción, mantenimiento y relaciones; consultas e informes. |
| Sesion | Recupera actor vigente, comprueba rol/token/inactividad y prepara transacciones. |
| Validacion | Comprueba tipos, longitudes, identificadores, formatos y contraseñas. |
| Respuesta | Envía JSON/código HTTP; confirma escrituras con auditoría o revierte fallos. |

La conexión PDO se entrega al constructor para reutilizarla y compartir transacciones. Los roles del MER se guardan en tablas relacionadas; no son subclases PHP ficticias.

## Persistencia y seguridad

- `prepare()` define SQL con marcadores; `execute()` envía valores separados. `fetch()` obtiene una fila y `fetchAll()` una colección. Los nombres dinámicos de tablas/columnas deben ser opciones internas controladas.
- `beginTransaction()`, `commit()` y `rollBack()` agrupan operaciones para evitar guardar una parte del cambio. MySQL puede confirmar cambios DDL implícitamente: modificar tablas no tiene exactamente la misma garantía.
- Claves primarias identifican filas; foráneas preservan relaciones; únicas evitan duplicados. Tablas intermedias representan relaciones muchos a muchos.
- Baja lógica: se guarda actividad y motivo en lugar de borrar el histórico. `activo=0` es baja; un repuesto conserva `activo=1` y tiene `en_servicio=0`.
- `password_hash()` genera hashes y `password_verify()` comprueba contraseñas sin almacenarlas en texto plano.
- Sesión PHP: la cookie identifica una sesión del servidor; el servidor consulta el rol vigente y no confía en el rol enviado por el navegador.
- HttpOnly limita acceso JavaScript a cookies; SameSite reduce envíos entre sitios; Secure se aplica con HTTPS. No reemplazan permisos por recurso.
- CSRF: escrituras requieren `X-CSRF-Token`; `hash_equals()` compara el token. La autorización por rol se comprueba además del token.
- Validación doble: HTML/JavaScript ayudan al usuario; PHP repite los controles porque es posible saltarse el formulario.
- `textContent` y escape HTML presentan datos como texto y evitan interpretar etiquetas/scripts. Esto es distinto de prevenir inyección SQL.
- Auditoría registra actor, fecha, recurso y operación; el historial conserva cambios de estado. No debe registrar contraseñas ni tokens.
- Cinco fallos de login producen bloqueo de 15 minutos; 30 minutos de inactividad vencen la sesión. Son políticas del documento de Ciberseguridad.

## Métodos JavaScript

`addEventListener` atiende eventos; `preventDefault` evita recargar al enviar por JavaScript; `FormData` recoge campos. `JSON.stringify` prepara el cuerpo y `response.json` interpreta la respuesta. `fetch`, promesas y `async/await` esperan a la API sin bloquear toda la página. `Promise.all` agrupa consultas independientes del dashboard.

`apiFetch` centraliza cookies, CSRF y tratamiento de sesión. `URLSearchParams` construye filtros. `filter`, `map` y `forEach` seleccionan y presentan resultados. La identidad no se guarda en localStorage/sessionStorage. Leaflet aporta eventos para situar el marcador y obtener coordenadas.

## Por qué se conservan estos archivos

| Ubicación | Relación con la entrega |
|---|---|
| Frontend / assets | Landing, registro, login, panel, backoffice y recursos visuales. |
| Backend/api, controllers, models, core, config | APIs, MVC, persistencia y seguridad. |
| Backend/database/sigeru_db.sql | Único SQL: modelo físico y datos de demostración. |
| Backend/tests | Testing PHP, JavaScript de navegador y colección Postman. |
| compose.yaml / deploy | Docker y soporte de infraestructura solicitado. |
| docs / documentación del equipo | Requisitos, diagrama, trazabilidad y material de las asignaturas. |
| .gitignore / .dockerignore | Excluyen credenciales y archivos ajenos de Git e imágenes. |

Antes había un SQL de instalación y otro de migración. La migración conservaba datos de bases anteriores. Ahora se entrega uno solo para instalar; migración histórica y respaldos están archivados fuera del proyecto. No se debe reimportar el SQL de instalación sobre la base existente.

## Terminación

Las pruebas locales no acreditan una nota. Quedan separación completa de aplicaciones, ejecución Linux/Docker/MySQL 8, publicación y pendientes funcionales/documentales registrados en la trazabilidad. No se rebajan requisitos para hacerlos coincidir con el código.
