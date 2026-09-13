# SiGeRU (Sistema de Gestión de Residuos Urbanos)

Proyecto desarrollado por **BCC Code Corp.** para el **Proyecto de Pasaje de Grado 2026**
de la Escuela Superior de Informática (ESI) - UTU.

> **Nota:** *BCC Code Corp.* es una entidad independiente constituida para el desarrollo
> de soluciones de software a medida, sin relación con herramientas de infraestructura
> o código abierto de terceros que compartan siglas similares.

---

## 🚀 Descripción del Proyecto
SiGeRU centraliza la gestión de residuos urbanos: contenedores, incidencias reportadas
por vecinos, flota de camiones, centros de acopio y roles de usuario (vecino, cuadrilla,
operario, administrador).

## 🛠 Tecnologías
* **Frontend:** HTML5, CSS y JavaScript.
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

* **API Recolección** — todo lo relacionado a la operativa de recolección: la flota
  de **camiones** y las **incidencias** reportadas en la vía pública.
* **API Gestión** — todo lo relacionado a los puntos físicos de residuos: los
  **contenedores** y los **centros de acopio** (con su maquinaria básica).
* **API Usuarios** — autenticación (login/registro) y el CRUD completo de usuarios,
  de cualquier rol.

## 🏗 Estructura del proyecto (MVC)

```
SiGeRU/
├── Frontend/                       # VISTAS (HTML + CSS que consume el usuario)
│   ├── principal.html
│   ├── login.html
│   ├── registro.html
│   ├── panel.html                  # Backoffice: incluye los 4 CRUD (usuarios,
│   │                                # camiones, contenedores, centros de acopio)
│   ├── sobre-nosotros.html
│   └── style.css
│
├── assets/                         # Imágenes del sitio (logo, fotos, etc.)
│
└── Backend/
    ├── config/
    │   └── conexion.php            # Configuración y conexión PDO a MySQL
    ├── core/
    │   └── Respuesta.php           # Helper que estandariza las respuestas JSON
    │
    ├── models/                     # MODELO: acceso a datos (una clase por tabla)
    │   ├── Usuario.php
    │   ├── Camion.php
    │   ├── Contenedor.php
    │   ├── Incidencia.php
    │   └── CentroAcopio.php
    │
    ├── controllers/                # CONTROLADOR: lógica de negocio / validaciones
    │   ├── AuthController.php
    │   ├── UsuarioController.php
    │   ├── CamionController.php
    │   ├── ContenedorController.php
    │   ├── IncidenciaController.php
    │   └── CentroAcopioController.php
    │
    ├── database/
    │   └── sigeru_db.sql           # Modelo físico de datos (script de creación)
    │
    └── api/                        # ENDPOINTS, agrupados igual que el diagrama
        ├── recoleccion/
        │   ├── camiones.php        # CRUD de flota
        │   └── incidencias.php     # GET listar/filtrar, POST crear, PUT asignar/resolver
        ├── gestion/
        │   ├── contenedores.php    # CRUD de contenedores
        │   └── centros_acopio.php  # CRUD de centros de acopio y maquinaria
        ├── usuarios/
        │   ├── login.php
        │   ├── registro.php        # alta pública, siempre rol "vecino"
        │   └── usuarios.php        # CRUD completo, todos los roles
        └── notificaciones/
            └── notificaciones.php  # GET listar, POST publicar anuncio, PUT marcar leída
```

### Cómo se relacionan las capas
`Frontend (fetch)` → `Backend/api/.../archivo.php (endpoint)` → `Controller` → `Model` → `MySQL`

Cada endpoint solo:
1. Decodifica el JSON recibido (o los parámetros GET).
2. Instancia el Controlador correspondiente.
3. Llama al método adecuado.
4. Devuelve el resultado con `Respuesta::enviar(...)`.

Toda la lógica de negocio vive en los **Controladores**, y todo el SQL vive en los
**Modelos** — así ningún archivo mezcla las tres responsabilidades, y los cuatro
endpoints CRUD (usuarios, camiones, contenedores, centros de acopio) siguen
exactamente el mismo esqueleto GET/POST/PUT/DELETE.

## 📡 Endpoints disponibles

| Endpoint | Método | Descripción |
|---|---|---|
| **API Recolección** | | |
| `Backend/api/recoleccion/camiones.php` | GET / POST / PUT / DELETE | CRUD de la flota (`?matricula=` para uno) |
| `Backend/api/recoleccion/incidencias.php` | GET / POST / PUT | GET lista todas o filtra por `?usuario_id=`; POST crea; PUT `?id=&accion=asignar\|resolver` cambia de estado. Sin DELETE: una incidencia no se borra, avanza de estado. |
| **API Gestión** | | |
| `Backend/api/gestion/contenedores.php` | GET / POST / PUT / DELETE | CRUD de contenedores (`?id=` para uno) |
| `Backend/api/gestion/centros_acopio.php` | GET / POST / PUT / DELETE | CRUD de centros de acopio y maquinaria (`?id=` para uno) |
| **API Usuarios** | | |
| `Backend/api/usuarios/login.php` | POST | Inicia sesión |
| `Backend/api/usuarios/registro.php` | POST | Autoregistro público (rol `vecino`) |
| `Backend/api/usuarios/usuarios.php` | GET / POST / PUT / DELETE | CRUD completo de usuarios, todos los roles (`?id=` o `?email=` para uno) |
| **Notificaciones** | | |
| `Backend/api/notificaciones/notificaciones.php` | GET / POST / PUT | GET lista por `?usuario_id=` o `?anuncios=1`; POST publica un anuncio; PUT `?id=` marca como leída. Sin DELETE: no se borran, cambian de estado. |

## 🗄 Base de datos
Importar `Backend/database/sigeru_db.sql` en MySQL/MariaDB. Incluye la tabla
`centros_acopio` y las columnas que el código PHP ya utilizaba y no estaban en el
dump original (`camiones.estado`, `incidencias.cuadrilla_id`,
`incidencias.matricula_camion`, `incidencias.comentario_operario`,
`incidencias.latitud`/`longitud`), además de datos de prueba para las 6 tablas
(usuarios de los 4 roles, camiones, contenedores, incidencias, centros de acopio y
una notificación).

**El script es idempotente:** al principio borra (`DROP TABLE IF EXISTS`) y recrea
las 6 tablas, así que se puede volver a importar las veces que haga falta sin
dejar nunca una base a medio migrar. Si ya tenés datos cargados que no querés
perder, usá en cambio las migraciones incrementales de
`Backend/database/migracion_usuario_notificaciones.sql` y
`Backend/database/migracion_latitud_longitud.sql`.

### 🔑 Usuarios de prueba (ya cargados por el script SQL)

| Email | Contraseña | Rol |
|---|---|---|
| admin@sigeru.uy | admin123 | administrador |
| vecino@sigeru.uy | vecino123 | vecino |
| operario@sigeru.uy | operario123 | operario |
| cuadrilla@sigeru.uy | cuadrilla123 | cuadrilla |

## 🐞 Modo debug
En `Backend/config/conexion.php` hay una constante `SIGERU_DEBUG`:

* `true` (valor actual) → si algo tira una excepción real de PHP/MySQL, el JSON de
  error devuelve el mensaje real (por ejemplo "columna X no existe" o "tabla Y no
  existe"), para poder diagnosticar rápido durante el desarrollo.
* `false` → devuelve siempre un mensaje genérico ("Error interno del servidor"),
  sin exponer detalles internos.

`SIGERU_DEBUG` está en `false`: los errores no se muestran en la respuesta de la
API (solo un mensaje genérico), para no exponer detalles internos del servidor.
Si necesitás depurar un error 500 durante el desarrollo, cambiala a `true`
temporalmente en `Backend/config/conexion.php` y volvela a `false` antes de subir
cambios.

## 🗺️ Ubicación de incidencias en el mapa
Al reportar una incidencia, el vecino confirma la ubicación exacta con un pin en
un mapa (Leaflet + OpenStreetMap): el pin se ubica solo buscando la dirección
escrita (geocodificación con Nominatim, gratuita y sin API Key) y se puede
arrastrar o reposicionar con un click para ajustarlo. La Latitud/Longitud de ese
pin viaja junto con el reporte, así el mapa general la ubica al instante. Los
contenedores y centros de acopio, que no pasan por este paso, se geolocalizan
por dirección de texto contra Nominatim al momento de mostrarlos en el mapa.

## ✅ Validación de datos (Frontend + Backend)
Los 4 CRUD (usuarios, camiones, contenedores, centros de acopio) validan los datos
en **ambas** capas:

* **Frontend** (`panel.html`, `registro.html`): campos obligatorios, largo mínimo,
  formato de email, validación de Cédula de Identidad uruguaya (dígito
  verificador), números positivos (capacidad de carga) — antes de disparar el
  `fetch`. Los mensajes de error se muestran en el propio formulario.
* **Backend** (Controladores): repite las mismas validaciones — nunca confía en lo
  que mande el Frontend — y agrega las reglas que solo el servidor puede resolver
  (unicidad de email/cédula/matrícula contra la base de datos, existencia del
  recurso antes de actualizar/eliminar).

## 🔒 Seguridad
* **SQL Injection:** todos los Modelos usan PDO con *prepared statements*
  (`PDO::prepare` + parámetros bindeados); en ningún punto del código se concatena
  input del usuario dentro de una consulta SQL.
* **Contraseñas:** se guardan con `password_hash()` (bcrypt), nunca en texto plano.
* **XSS:** el Frontend escapa (`textContent`/`innerHTML` seguro) todo dato que
  proviene de la base de datos antes de insertarlo en las tablas del panel.
* **Manejo de errores:** los 6 endpoints (los 4 CRUD + notificaciones +
  incidencias) envuelven su lógica en `try/catch`; con `SIGERU_DEBUG = false`
  ninguna excepción de PHP/PDO se muestra cruda al cliente (evita fuga de
  información como rutas del servidor o estructura de la base de datos).

## 🧪 Testing de las APIs (Postman)
En `Backend/tests/SiGeRU.postman_collection.json` hay una colección de Postman
lista para importar, con un request por cada caso relevante de cada CRUD (alta
correcta, datos inválidos, duplicados, recurso inexistente, método no soportado) y
un test por request que valida el código HTTP de respuesta esperado.

Pasos:
1. Levantar el proyecto en XAMPP/WAMP (o similar) — por defecto se asume
   `http://localhost/SiGeRU2/Backend`.
2. Abrir Postman → **File → Import** → seleccionar el archivo `.json`.
3. Si la URL base es otra, editar la variable de colección `base_url`.
4. Ejecutar la colección completa con el **Collection Runner** — todos los tests
   deberían pasar en verde.

### Códigos de estado HTTP devueltos por la API REST

| Código | Cuándo se usa |
|---|---|
| `200 OK` | Lectura (GET) o actualización/eliminación exitosa |
| `201 Created` | Alta exitosa (POST) |
| `400 Bad Request` | Datos faltantes o que no pasan la validación |
| `401 Unauthorized` | Login con credenciales incorrectas |
| `404 Not Found` | Se pide, actualiza o elimina un recurso que no existe |
| `405 Method Not Allowed` | El verbo HTTP usado no está soportado por ese endpoint |
| `409 Conflict` | Email, cédula o matrícula ya registrados |
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
