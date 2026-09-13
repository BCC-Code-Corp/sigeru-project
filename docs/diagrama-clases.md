# Diagrama de clases de SiGeRU

Revisión del 12/09/2026, obtenida de las declaraciones PHP del proyecto. Representa las clases implementadas, no las entidades del MER. Los métodos se muestran por nombre; los parámetros y tipos completos se consultan en el código. Las flechas indican uso de otra clase, no herencia ni cardinalidades de tablas.

Los roles del MER (Funcionario, Vecino, Chofer, Recolector, Operario y Administrador) se persisten mediante tablas y `Usuario`; no existen como subclases PHP. `Operacion` concentra la persistencia de rutas, cuadrillas, maquinaria, residuos y sus relaciones. No se dibuja una jerarquía de clases que el programa no implementa.

El frontend usa funciones JavaScript y consume endpoints; por eso no aparece como una clase PHP. Los endpoints aplican sesión, validación y respuesta mediante `Sesion`, `Validacion` y `Respuesta`. La conexión PDO se inyecta en modelos y controladores.

## Vista resumida

![Clases por capa](diagrama-clases.svg)

La imagen muestra las operaciones principales por capa. Las relaciones son de uso: cada controlador utiliza su modelo; IncidenciaController también utiliza Camion, Contenedor y Notificacion. Todos los modelos acceden a PDO. Los endpoints utilizan Sesion, Validacion y Respuesta.

## Índice de código

| Clase | Capa | Archivo |
|---|---|---|
| AuthController | Controladores | [Backend/controllers/AuthController.php](../Backend/controllers/AuthController.php) |
| CamionController | Controladores | [Backend/controllers/CamionController.php](../Backend/controllers/CamionController.php) |
| CentroAcopioController | Controladores | [Backend/controllers/CentroAcopioController.php](../Backend/controllers/CentroAcopioController.php) |
| ContenedorController | Controladores | [Backend/controllers/ContenedorController.php](../Backend/controllers/ContenedorController.php) |
| IncidenciaController | Controladores | [Backend/controllers/IncidenciaController.php](../Backend/controllers/IncidenciaController.php) |
| NotificacionController | Controladores | [Backend/controllers/NotificacionController.php](../Backend/controllers/NotificacionController.php) |
| OperacionController | Controladores | [Backend/controllers/OperacionController.php](../Backend/controllers/OperacionController.php) |
| UsuarioController | Controladores | [Backend/controllers/UsuarioController.php](../Backend/controllers/UsuarioController.php) |
| Camion | Modelos | [Backend/models/Camion.php](../Backend/models/Camion.php) |
| CentroAcopio | Modelos | [Backend/models/CentroAcopio.php](../Backend/models/CentroAcopio.php) |
| Contenedor | Modelos | [Backend/models/Contenedor.php](../Backend/models/Contenedor.php) |
| Incidencia | Modelos | [Backend/models/Incidencia.php](../Backend/models/Incidencia.php) |
| Notificacion | Modelos | [Backend/models/Notificacion.php](../Backend/models/Notificacion.php) |
| Operacion | Modelos | [Backend/models/Operacion.php](../Backend/models/Operacion.php) |
| Usuario | Modelos | [Backend/models/Usuario.php](../Backend/models/Usuario.php) |
| Respuesta | Servicios comunes | [Backend/core/Respuesta.php](../Backend/core/Respuesta.php) |
| Sesion | Servicios comunes | [Backend/core/Sesion.php](../Backend/core/Sesion.php) |
| Validacion | Servicios comunes | [Backend/core/Validacion.php](../Backend/core/Validacion.php) |

## Flujo de una operación

`panel.html` / `operaciones.js` → endpoint de la API → sesión y validación → controlador → modelo → PDO. La respuesta confirma la transacción y registra la auditoría cuando corresponde; las operaciones fallidas se revierten. Las tablas de historial forman parte de la persistencia y no son clases adicionales.
