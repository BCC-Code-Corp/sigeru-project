# SiGeRU (Sistema de Gestión de Residuos Urbanos)

Proyecto desarrollado por **BCC Code Corp.** para el **Proyecto de Pasaje de Grado 2026**
de la Escuela Superior de Informática (ESI) - UTU.

> **Nota:** *BCC Code Corp.* es una entidad independiente constituida para el desarrollo
> de soluciones de software a medida, sin relación con herramientas de infraestructura
> o código abierto de terceros que compartan siglas similares.

---

## 🚀 Descripción del Proyecto
SiGeRU centraliza la gestión de residuos urbanos: contenedores, incidencias reportadas
por vecinos, flota de camiones, rutas y calendario de recolección, cuadrillas de trabajo,
centros de acopio y vertederos con su maquinaria, recepción de residuos, mantenimientos
y reparaciones, reclamos de vecinos, y seis roles de usuario (**vecino, chofer,
recolector, operario, administrador**, y la agrupación operativa de **cuadrillas** que
reúne choferes y recolectores).

## 🛠 Tecnologías
* **Frontend:** HTML5, CSS y JavaScript (módulos ES, sin frameworks), Leaflet + Nominatim
  para geolocalización.
* **Backend:** PHP 8 (API REST, arquitectura MVC).
* **Base de Datos:** MySQL / MariaDB, acceso vía PDO con consultas preparadas.

## 🏗 Arquitectura de APIs

El Backend expone tres grandes grupos de API, cada uno atendiendo a una interfaz
distinta del sistema, todas apoyadas sobre la misma base de datos:

```
 Interfaz          Interfaz          Interfaz
Recolectores       Vertederos     Usuarios (Opcional)
     │                  │                 │
     │ JSON             │ JSON            │ JSON
     ▼                  ▼                 ▼
 API Recolección    API Gestión      API Usuarios
     │                  │                 │
     └──────────────────┼─────────────────┘
                         ▼
                    Base de Datos
                     (MySQL)

  BackOffice Administración → consume las 3 APIs para gestionar todo el sistema
```

* **API Recolección** — la operativa de recolección: **camiones**, **cuadrillas**,
  **rutas**, el **calendario de asignaciones** (cuadrilla + camión + ruta + fecha),
  **recolecciones** registradas en el terreno, **mantenimientos** de camiones,
  **reparaciones** de contenedores, **incidencias** reportadas en la vía pública y los
  **reclamos** que un vecino puede hacer sobre una incidencia propia.
* **API Gestión** — los puntos físicos de residuos: **contenedores**, **centros de
  acopio/vertederos** con su **capacidad** y **maquinaria**, los **tipos de residuo**
  habilitados por instalación, las **recepciones** de residuos, las **relaciones**
  operativas (asignar integrantes a una cuadrilla, chofer a un camión, operario a un
  centro, etc.) y los **reportes** estadísticos por período.
* **API Usuarios** — autenticación (login/registro/logout/sesión) y el CRUD completo de
  usuarios de cualquier rol, más las notificaciones del sistema.

## 🏗 Estructura del proyecto (MVC)

```
SiGeRU/
├── Frontend/                       # VISTAS (HTML + CSS + JS que consume el usuario)
│   ├── principal.html
│   ├── login.html
│   ├── registro.html
│   ├── panel.html                  # Panel operativo: vecino, chofer, recolector, operario
│   ├── backoffice.html             # Backoffice de Administración: gestiona todo el sistema
│   ├── impacto-social.html
│   ├── sobre-nosotros.html
│   ├── validacion.js               # Validaciones de formularios (cédula UY, password, etc.)
│   ├── operaciones.js              # CRUD genérico del panel/backoffice contra la API Recolección/Gestión
│   ├── confirmaciones.js
│   ├── config.js
│   ├── agente-socializador.js
│   ├── panel.css / style.css
│
├── assets/                         # Imágenes del sitio (logo, fotos, etc.)
│
└── Backend/
    ├── config/
    │   └── conexion.php            # Configuración y conexión PDO a MySQL
    ├── core/
    │   ├── Respuesta.php           # Helper que estandariza las respuestas JSON y códigos HTTP
    │   ├── Sesion.php              # Autenticación, CSRF, transacciones, control de roles
    │   └── Validacion.php          # Validación de datos de entrada compartida por los endpoints
    │
    ├── models/                     # MODELO: acceso a datos (una clase por entidad/grupo)
    │   ├── Usuario.php
    │   ├── Camion.php
    │   ├── Contenedor.php
    │   ├── Incidencia.php
    │   ├── CentroAcopio.php
    │   ├── Notificacion.php
    │   └── Operacion.php           # Acceso genérico para cuadrillas, rutas, asignaciones,
    │                                # recolecciones, mantenimientos, reparaciones, reclamos,
    │                                # recepciones, maquinaria, residuos y relaciones
    │
    ├── controllers/                # CONTROLADOR: lógica de negocio / validaciones
    │   ├── AuthController.php
    │   ├── UsuarioController.php
    │   ├── CamionController.php
    │   ├── ContenedorController.php
    │   ├── CentroAcopioController.php
    │   ├── IncidenciaController.php
    │   ├── NotificacionController.php
    │   └── OperacionController.php
    │
    ├── database/
    │   └── sigeru_db.sql           # Modelo físico completo + datos de prueba (importar una vez)
    │
    ├── tests/
    │   ├── SiGeRU.postman_collection.json  # Colección Postman
    │   ├── integracion.php                 # Test de integración HTTP end-to-end (PHP CLI)
    │   └── validacion_frontend.html
    │
    └── api/                        # ENDPOINTS, agrupados igual que el diagrama
        ├── recoleccion/
        │   ├── camiones.php        # CRUD de flota
        │   ├── cuadrillas.php      # CRUD de cuadrillas
        │   ├── rutas.php           # CRUD de rutas
        │   ├── asignaciones.php    # Calendario: cuadrilla + camión + ruta + fecha
        │   ├── recolecciones.php   # Alta de recolecciones registradas en el terreno
        │   ├── mantenimientos.php  # Mantenimientos de camiones
        │   ├── reparaciones.php    # Reparaciones de contenedores
        │   ├── reclamos.php        # Reclamos de vecinos sobre incidencias propias
        │   └── incidencias.php     # GET listar/filtrar, POST crear, PUT asignar/resolver
        ├── gestion/
        │   ├── contenedores.php    # CRUD de contenedores
        │   ├── centros_acopio.php  # CRUD de centros de acopio / vertederos
        │   ├── maquinaria.php      # CRUD de maquinaria por instalación
        │   ├── capacidad.php       # Actualización de capacidad ocupada (operario)
        │   ├── residuos.php        # CRUD de tipos de residuo
        │   ├── recepciones.php     # Alta de recepciones de residuos
        │   ├── relaciones.php      # Vínculos operativos (integrante↔cuadrilla, chofer↔camión, etc.)
        │   └── reportes.php        # Reportes estadísticos por período (solo administrador)
        └── usuarios/
            ├── login.php
            ├── logout.php
            ├── sesion.php          # Perfil de la sesión activa + token CSRF
            ├── registro.php        # Alta pública, siempre rol "vecino" (queda pendiente de aprobación)
            ├── usuarios.php        # CRUD completo, todos los roles
            └── notificaciones.php  # GET listar, POST publicar anuncio, PUT marcar leída
```

### Cómo se relacionan las capas
`Frontend (fetch)` → `Backend/api/.../archivo.php (endpoint)` → `Controller` → `Model` → `MySQL`

Cada endpoint solo:
1. Protege la ruta y decodifica el JSON recibido (o los parámetros GET) vía `Sesion::proteger`.
2. Instancia el Controlador correspondiente.
3. Llama al método adecuado.
4. Devuelve el resultado con `Respuesta::enviarResultado(...)`, que traduce el `_code`
   definido por el Controlador al código HTTP real de la respuesta.

Toda la lógica de negocio vive en los **Controladores**, y todo el SQL vive en los
**Modelos**. Los recursos operativos (cuadrillas, rutas, asignaciones, recolecciones,
mantenimientos, reparaciones, reclamos, recepciones, maquinaria, residuos) comparten un
único Controlador genérico (`OperacionController`) y un único Modelo genérico
(`Operacion`), parametrizados por una tabla de campos permitidos por recurso — así se
evita repetir el mismo esqueleto CRUD en ocho archivos distintos sin perder la
validación específica de cada uno.

## 📡 Endpoints disponibles

| Endpoint | Método | Descripción |
|---|---|---|
| **API Recolección** | | |
| `Backend/api/recoleccion/camiones.php` | GET / POST / PUT / DELETE | CRUD de la flota (`?matricula=`); `PUT ?accion=asignar_cuadrilla\|desasignar_cuadrilla` |
| `Backend/api/recoleccion/cuadrillas.php` | GET / POST / PUT | CRUD de cuadrillas (no se eliminan: quedan como historial) |
| `Backend/api/recoleccion/rutas.php` | GET / POST / PUT / DELETE | CRUD de rutas |
| `Backend/api/recoleccion/asignaciones.php` | GET / POST | Calendario: asigna cuadrilla + camión a una ruta en una fecha |
| `Backend/api/recoleccion/recolecciones.php` | GET / POST | Registro de recolecciones hechas en el terreno |
| `Backend/api/recoleccion/mantenimientos.php` | GET / POST | Mantenimientos preventivos/correctivos de camiones |
| `Backend/api/recoleccion/reparaciones.php` | GET / POST | Reparaciones asociadas a incidencias de contenedores |
| `Backend/api/recoleccion/reclamos.php` | GET / POST | Reclamos de un vecino sobre una incidencia propia abierta |
| `Backend/api/recoleccion/incidencias.php` | GET / POST / PUT | GET lista todas o filtra por `?usuario_id=`; POST crea; `PUT ?id=&accion=asignar\|resolver` cambia de estado. Sin DELETE: una incidencia no se borra, avanza de estado. |
| **API Gestión** | | |
| `Backend/api/gestion/contenedores.php` | GET / POST / PUT / DELETE | CRUD de contenedores (`?id=`) |
| `Backend/api/gestion/centros_acopio.php` | GET / POST / PUT / DELETE | CRUD de centros de acopio / vertederos (`?id=`) |
| `Backend/api/gestion/maquinaria.php` | GET / POST / PUT / DELETE | CRUD de maquinaria por instalación |
| `Backend/api/gestion/capacidad.php` | PUT | El operario actualiza la capacidad ocupada y el estado de su centro |
| `Backend/api/gestion/residuos.php` | GET / POST / PUT | CRUD de tipos de residuo |
| `Backend/api/gestion/recepciones.php` | GET / POST | Alta de recepciones de residuos en un centro |
| `Backend/api/gestion/relaciones.php` | POST | Vínculos operativos: integrante↔cuadrilla, chofer↔camión, operario↔centro, contenedor↔ruta, residuo↔centro |
| `Backend/api/gestion/reportes.php` | GET | Reportes estadísticos por período (solo administrador) |
| **API Usuarios** | | |
| `Backend/api/usuarios/login.php` | POST | Inicia sesión |
| `Backend/api/usuarios/logout.php` | POST | Cierra sesión |
| `Backend/api/usuarios/sesion.php` | GET | Perfil de la sesión activa + token CSRF |
| `Backend/api/usuarios/registro.php` | POST | Autoregistro público (rol `vecino`, queda pendiente de aprobación) |
| `Backend/api/usuarios/usuarios.php` | GET / POST / PUT / DELETE | CRUD completo de usuarios, todos los roles (`?id=` o `?email=`) |
| `Backend/api/usuarios/notificaciones.php` | GET / POST / PUT | GET lista por `?usuario_id=` o `?anuncios=1`; POST publica un anuncio; PUT `?id=` marca como leída |

En los recursos genéricos de `OperacionController` (cuadrillas, rutas, residuos,
maquinaria), `PUT` solo está habilitado si el recurso admite corrección de datos, y
`DELETE` (baja lógica) solo en `rutas` y `maquinaria`: el resto conserva su historial y
no se sobrescribe ni se elimina.

## 🗄 Base de datos
Importar `Backend/database/sigeru_db.sql` en MySQL/MariaDB. **Es un único script que se
ejecuta una sola vez sobre una base vacía**: crea el modelo físico completo (todas las
tablas del DER, incluyendo `usuarios`, `camiones`, `contenedores`, `incidencias`,
`centros_acopio`, `cuadrillas`, `choferes`, `recolectores`, `operarios`,
`administradores`, `vecinos`, `maquinaria`, `residuos`, `rutas`, `asignaciones`,
`recolecciones`, `mantenimientos`, `reparaciones`, `reclamos`, `recepciones`, tablas de
relación (`sigue`, `realiza`, `contiene`, `sobre`, `gestionan`, `recibe`, `necesita`,
`notificaciones_leidas`) y de soporte (`auditoria`, `intentos_login`,
`historial_incidencias`, `historial_capacidad`)) y carga datos de prueba coherentes
entre sí para **todas** las entidades del sistema, no solo para las tablas base.

**No importar sobre una instalación con datos existentes** (el propio script lo indica):
si necesitás reiniciar la base, hacelo sobre un esquema vacío.

### 🔑 Usuarios de prueba (ya cargados por el script SQL)

| Email | Contraseña | Rol |
|---|---|---|
| admin@sigeru.uy | admin123 | administrador |
| vecino@sigeru.uy | vecino123 | vecino |
| operario@sigeru.uy | operario123 | operario (asignado al Centro de Acopio Norte) |
| cuadrilla@sigeru.uy | cuadrilla123 | recolector (integrante de la cuadrilla de prueba) |

Además se carga una cuenta `chofer@sigeru.uy` (rol `chofer`, asignada a la misma
cuadrilla y al camión `ABC 1234`) para poder ver el rol completo en el panel; su
contraseña no quedó documentada como texto plano en el script, así que para
probarla hay que restablecerla desde el backoffice (editar usuario → nueva
contraseña) con la cuenta de administrador.

## 🐞 Modo debug
En `Backend/config/conexion.php` hay una constante `SIGERU_DEBUG`:

* `true` → si algo tira una excepción real de PHP/MySQL, el JSON de error devuelve el
  mensaje real (por ejemplo "columna X no existe" o "tabla Y no existe"), para poder
  diagnosticar rápido durante el desarrollo.
* `false` → devuelve siempre un mensaje genérico ("Error interno del servidor"), sin
  exponer detalles internos.

`SIGERU_DEBUG` está en `false` para la entrega: los errores no se muestran en la
respuesta de la API (solo un mensaje genérico), para no exponer detalles internos del
servidor. Si necesitás depurar un error 500 durante el desarrollo, cambiala a `true`
temporalmente en `Backend/config/conexion.php` y volvela a `false` antes de subir
cambios.

## 🗺️ Ubicación de incidencias en el mapa
Al reportar una incidencia, el vecino confirma la ubicación exacta con un pin en un
mapa (Leaflet + OpenStreetMap): el pin se ubica solo buscando la dirección escrita
(geocodificación con Nominatim, gratuita y sin API Key) y se puede arrastrar o
reposicionar con un click para ajustarlo. La Latitud/Longitud de ese pin viaja junto
con el reporte, así el mapa general la ubica al instante. Los contenedores y centros de
acopio, que no pasan por este paso, se geolocalizan por dirección de texto contra
Nominatim al momento de mostrarlos en el mapa.

## ✅ Validación de datos (Frontend + Backend)
Todos los CRUD (usuarios, camiones, contenedores, centros de acopio/vertederos,
maquinaria, cuadrillas, rutas, residuos, y los formularios de registro operativo:
asignaciones, recolecciones, mantenimientos, reparaciones, reclamos, recepciones)
validan los datos en **ambas** capas:

* **Frontend** (`registro.html`, `panel.html`, `backoffice.html` vía `validacion.js` y
  `operaciones.js`): campos obligatorios, largo mínimo/máximo, formato de email,
  validación de Cédula de Identidad uruguaya (dígito verificador), formato y fuerza de
  contraseña, números positivos, consistencia de fechas — antes de disparar el `fetch`.
  Los mensajes de error se muestran en el propio formulario.
* **Backend** (`Validacion.php` + cada Controlador): repite las mismas validaciones —
  nunca confía en lo que mande el Frontend — y agrega las reglas que solo el servidor
  puede resolver (unicidad de email/cédula/matrícula contra la base de datos,
  existencia y estado del recurso relacionado antes de crear/actualizar/eliminar,
  reglas de negocio del DER como "un contenedor de una ruta debe tener una incidencia
  abierta" o "no se puede reasignar una cuadrilla con una incidencia en curso").

## 🔒 Seguridad
* **SQL Injection:** todos los Modelos usan PDO con *prepared statements* reales
  (`PDO::ATTR_EMULATE_PREPARES = false`); en ningún punto del código se concatena input
  del usuario dentro de una consulta SQL. Donde el nombre de tabla/recurso es dinámico
  (`OperacionController`), siempre se valida antes contra una whitelist fija de
  recursos permitidos.
* **Contraseñas:** se guardan con `password_hash()` (bcrypt), nunca en texto plano.
* **Fuerza bruta:** `login.php` bloquea un email 15 minutos después de 5 intentos
  fallidos (tabla `intentos_login`).
* **CSRF:** toda operación que modifica datos (`POST`/`PUT`/`DELETE`) exige el header
  `X-CSRF-Token` con el token entregado al iniciar sesión.
* **Sesión:** cookies `HttpOnly` + `SameSite=Strict`, expiración por inactividad (30
  minutos) e invalidación de sesiones activas cuando cambian el rol o la contraseña de
  un usuario (`auth_version`).
* **Autorización por rol:** cada endpoint valida el rol del usuario autenticado antes
  de permitir la operación (`Sesion::exigir`), y los listados se filtran por alcance
  (un operario solo ve su centro, una cuadrilla solo sus rutas, un vecino solo sus
  propios reclamos/incidencias).
* **Eliminación lógica:** usuarios, camiones, contenedores, centros de acopio y
  maquinaria no se borran físicamente: se marcan `activo = 0` con motivo de baja,
  conservando el historial e integridad referencial.
* **XSS:** el Frontend escapa (`escaparHtml`) todo dato que proviene de la base de
  datos antes de insertarlo en las tablas del panel/backoffice.
* **Auditoría:** cada operación exitosa que modifica datos queda registrada en la
  tabla `auditoria` (usuario, recurso, acción, referencia y detalle).
* **Manejo de errores:** todos los endpoints envuelven su lógica en `try/catch`; con
  `SIGERU_DEBUG = false` ninguna excepción de PHP/PDO se muestra cruda al cliente
  (evita fuga de información como rutas del servidor o estructura de la base de
  datos).

## 🧪 Testing de las APIs
Hay dos formas de probar la API, complementarias:

1. **Postman** — `Backend/tests/SiGeRU.postman_collection.json`: colección lista para
   importar con un request de consulta por cada recurso, más una serie de requests
   sobre el CRUD de camiones que cubren explícitamente los distintos códigos HTTP de
   respuesta (alta correcta, alta duplicada, datos inválidos, actualización de un
   recurso inexistente, método no soportado, baja), y un test por request que valida
   el código HTTP esperado.

   Pasos:
   1. Levantar el proyecto en XAMPP/WAMP (o similar).
   2. Abrir Postman → **File → Import** → seleccionar el archivo `.json`.
   3. Si la URL base es otra, editar la variable de colección `base_url`.
   4. Ejecutar la colección completa con el **Collection Runner** — todos los tests
      deberían pasar en verde.

2. **Script de integración en PHP** — `Backend/tests/integracion.php`: recorre un
   flujo real (alta de un usuario por rol, login, CRUD de centros/maquinaria/
   contenedores/camiones, bajas, cierre de sesión) contra una instalación de prueba y
   verifica en cada paso el código HTTP exacto esperado. Se ejecuta por línea de
   comandos:
   ```bash
   php Backend/tests/integracion.php http://127.0.0.1:8092 --base-de-prueba
   ```
   (usar solo contra una base con los datos de demostración, ya que crea y elimina
   registros).

### Códigos de estado HTTP devueltos por la API REST

| Código | Cuándo se usa |
|---|---|
| `200 OK` | Lectura (GET) o actualización/eliminación exitosa |
| `201 Created` | Alta exitosa (POST) |
| `400 Bad Request` | Datos faltantes o que no pasan la validación |
| `401 Unauthorized` | No autenticado, credenciales incorrectas o sesión vencida |
| `403 Forbidden` | Autenticado pero sin permiso para esa operación |
| `404 Not Found` | Se pide, actualiza o elimina un recurso que no existe |
| `405 Method Not Allowed` | El verbo HTTP usado no está soportado por ese endpoint |
| `409 Conflict` | Datos duplicados (email/cédula/matrícula) o conflicto de estado del recurso |
| `415 Unsupported Media Type` | El cuerpo de la petición no vino como `application/json` |
| `429 Too Many Requests` | Demasiados intentos de login fallidos para ese email |
| `500 Internal Server Error` | Error inesperado del servidor (nunca expone detalles internos) |

## 🗂 Control de versiones (Git)
El proyecto se entrega versionado con Git. Para continuar trabajando y que el
historial de commits quede reflejado en la nota:

```bash
git add .
git commit -m "mensaje descriptivo del cambio"
git push origin main   # subido a un repositorio remoto (GitHub/GitLab)
```

Se recomienda commitear con frecuencia (por función o por CRUD terminado) en lugar
de un único commit al final, y subir el repositorio a GitHub/GitLab antes de la
entrega para que quede constancia del historial real de desarrollo.

## 👤 Equipo de Desarrollo
* **Francisco Barreto** - Coordinador
* **Mateo Cortizo** - SubCoordinador
* **Giuliano Crotti** - Miembro

TODOS LOS DERECHOS RESERVADOS
