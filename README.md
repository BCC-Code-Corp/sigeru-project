# SiGeRU (Sistema de Gestión de Residuos Urbanos)

Proyecto desarrollado por **BCC Code Corp.** para el **Proyecto de Pasaje de Grado 2026**
de la Escuela Superior de Informática (ESI) - UTU.

> **Nota:** *BCC Code Corp.* es una entidad independiente constituida para el desarrollo
> de soluciones de software a medida, sin relación con herramientas de infraestructura
> o código abierto de terceros que compartan siglas similares.

---

## Descripción del Proyecto
SiGeRU centraliza la gestión de residuos urbanos: contenedores, incidencias reportadas
por vecinos, flota de camiones y roles de usuario (vecino, cuadrilla, operario, administrador).

## Tecnologías
* **Frontend:** HTML5, CSS y JavaScript.
* **Backend:** PHP 8 (API REST, arquitectura MVC).
* **Base de Datos:** MySQL / MariaDB, acceso vía PDO con consultas preparadas.

## Estructura del proyecto (MVC)

```
SiGeRU/
├── Frontend/                  # VISTAS (HTML + CSS que consume el usuario)
│   ├── principal.html
│   ├── login.html
│   ├── registro.html
│   ├── panel.html
│   ├── sobre-nosotros.html
│   └── style.css
│
├── assets/                    # Imágenes del sitio (logo, fotos, etc.)
│
└── Backend/                   # MODELO + CONTROLADOR + API
    ├── config/
    │   └── conexion.php       # Configuración y conexión PDO a MySQL
    ├── core/
    │   └── Respuesta.php      # Helper que estandariza las respuestas JSON
    ├── models/                # MODELOS: acceso a datos (una clase por tabla)
    │   ├── Usuario.php
    │   ├── Camion.php
    │   ├── Contenedor.php
    │   └── Incidencia.php
    ├── controllers/           # CONTROLADORES: lógica de negocio / validaciones
    │   ├── AuthController.php
    │   ├── UsuarioController.php
    │   ├── CamionController.php
    │   ├── ContenedorController.php
    │   └── IncidenciaController.php
    ├── database/
    │   └── sigeru_db.sql      # Modelo físico de datos (script de creación)
    │
    └── login.php               ─┐
        registro.php             │  ENDPOINTS DE API: archivos finos que reciben
        obtener_usuario.php      │  la solicitud, llaman al Controlador
        actualizar_rol.php       │  correspondiente y devuelven JSON con
        camiones.php             │  Respuesta::enviar(...). Todos siguen el
        contenedores.php         │  mismo formato para que sean fáciles de leer.
        incidencias.php         ─┘
```

### Cómo se relacionan las capas
`Frontend (fetch)` → `Backend/*.php (endpoint)` → `Controller` → `Model` → `MySQL`

Cada endpoint (`Backend/login.php`, `Backend/camiones.php`, etc.) solo:
1. Decodifica el JSON recibido.
2. Instancia el Controlador correspondiente.
3. Llama al método adecuado.
4. Devuelve el resultado con `Respuesta::enviar(...)`.

Toda la lógica de negocio vive en los **Controladores**, y todo el SQL vive en los
**Modelos** — así ningún archivo mezcla las tres responsabilidades.

## Endpoints disponibles

| Endpoint | Método | Descripción |
|---|---|---|
| `Backend/login.php` | POST | Inicia sesión |
| `Backend/registro.php` | POST | Registra un vecino nuevo |
| `Backend/obtener_usuario.php` | POST | Trae el perfil de un usuario |
| `Backend/actualizar_rol.php` | POST | Cambia el rol de un usuario (admin) |
| `Backend/camiones.php` | GET / POST | Lista / crea camiones |
| `Backend/contenedores.php` | GET / POST | Lista / crea contenedores |
| `Backend/incidencias.php` | POST (`accion`) | crear / listar / asignar / resolver |

## Base de datos
Importar `Backend/database/sigeru_db.sql` en MySQL/MariaDB. El script fue corregido
respecto al dump original: se agregaron columnas que el código PHP ya utilizaba
(`camiones.estado`, `incidencias.cuadrilla_id`, `incidencias.matricula_camion`,
`incidencias.comentario_operario`) y que no existían en la tabla original.

## Equipo de Desarrollo
* **Francisco Barreto** - Coordinador
* **Mateo Cortizo** - SubCoordinador
* **Giuliano Crotti** - Miembro

TODOS LOS DERECHOS RESERVADOS
