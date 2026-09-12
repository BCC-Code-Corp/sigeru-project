# SiGeRU — Gestión de Residuos Urbanos

PHP 8, PDO y MySQL/MariaDB; interfaz HTML, CSS y JavaScript. Ajustado a `Ingenieria.pdf`, `TablasYMER.docx.pdf`, `MER.drawio.png` y las políticas de `Ciberseguridad.pdf`.

## Tecnologías y arquitectura del equipo

Se mantiene la arquitectura del README original de BCC Code Corp.: frontend HTML5/CSS/JavaScript que consume JSON mediante `fetch`, backend PHP 8 y persistencia MySQL/MariaDB mediante PDO. Los mapas utilizan Leaflet, OpenStreetMap y Nominatim.

El flujo es `Frontend → endpoint → controlador → modelo → base de datos`. Los controladores validan las solicitudes y las reglas de negocio; los modelos contienen las consultas SQL y enlazan los valores mediante PDO. Los componentes comunes de sesión, respuesta y auditoría se encuentran en `Backend/core`.

Las APIs conservan los grupos `usuarios` (autenticación y cuentas), `gestion` (contenedores, centros, maquinaria y recepción de residuos) y `recoleccion` (flota, cuadrillas, rutas e incidencias). El backoffice consume los tres grupos. Docker agrega una opción de despliegue; el desarrollo local sigue disponible en XAMPP.

El inventario distingue contenedores desplegados (`activo=1`, `en_servicio=1`), repuestos (`activo=1`, `en_servicio=0`) y bajas (`activo=0`). El administrador cambia la condición desde el formulario de contenedores y consulta repuestos mediante `GET Backend/api/gestion/contenedores.php?repuestos=1`. Solo los desplegados se incorporan a rutas y reportes de incidencias.

## Instalar en XAMPP

1. Iniciar Apache y MySQL. Crear una base vacía `sigeru_db` con charset `utf8mb4` y collation `utf8mb4_general_ci`.
2. Desde la raíz del proyecto ejecutar `C:\xampp\php\php.exe Backend/database/instalar.php nueva`.
3. Abrir `http://localhost/sigeru-project/Frontend/principal.html`.

La configuración usa variables de entorno `SIGERU_DB_HOST`, `SIGERU_DB_PORT`, `SIGERU_DB_NAME`, `SIGERU_DB_USER` y `SIGERU_DB_PASSWORD`. Los valores locales predeterminados son `127.0.0.1:3306`, `sigeru_db`, `root` y contraseña vacía. `session.save_path` de PHP debe existir y ser escribible por Apache. Los errores se registran en el servidor y no se muestran al cliente.

### Actualizar una instalación existente

Realizar primero un respaldo. Con la aplicación detenida para escrituras, ejecutar:

```powershell
C:\xampp\php\php.exe Backend/database/instalar.php migrar
```

El instalador aplica `migracion_der.sql`, guarda cada paso completado y permite repetir el comando sin duplicar esos pasos. No ejecutar `nueva` sobre una base con datos. Los archivos SQL también pueden importarse manualmente, pero entonces se ejecutan **una sola vez**, sin el registro de pasos del instalador. MySQL hace commit implícito de DDL: si se interrumpe entre un DDL y su registro de paso, hay que revisar ese paso antes de reintentar.

La migración conserva usuarios, camiones, contenedores, centros, incidencias y notificaciones. Convierte cada antigua cuenta `cuadrilla` en un `recolector` miembro de una cuadrilla con el mismo identificador. Convierte estados de contenedores: `vacio → funcional`, `lleno → desbordado`, `mantenimiento → roto`; `incidencia solucionada → cerrada`. Revisar esas equivalencias con los datos reales: describen la conversión del sistema anterior, no una inspección física. Los campos desconocidos (capacidad máxima, tipo de residuo, fechas laborales, etc.) permanecen NULL. La maquinaria anterior se conserva como una entrada de inventario por centro; dividir manualmente los textos que enumeraban varias máquinas.

### Cuentas de demostración

| Email | Contraseña | Rol |
|---|---|---|
| admin@sigeru.uy | admin123 | administrador |
| vecino@sigeru.uy | vecino123 | vecino |
| operario@sigeru.uy | operario123 | operario |
| cuadrilla@sigeru.uy | cuadrilla123 | recolector de la cuadrilla 4 |
| chofer@sigeru.uy | Chofer!2026 | chofer (instalación nueva) |

Son datos de demostración. Las contraseñas nuevas deben tener 8–72 caracteres, mayúscula, minúscula, número y símbolo. Las claves heredadas no se reemplazan automáticamente. Las solicitudes de autorregistro se aprueban en Usuarios y Roles mediante el campo Solicitud. El administrador asigna operarios a instalaciones, choferes a camiones y miembros a cuadrillas desde el panel.

## Flujos disponibles

- ABM de usuarios, contenedores, camiones e instalaciones. Las bajas conservan los registros y el motivo; los inventarios operativos excluyen las bajas.
- Cuadrillas como equipos; usuarios con roles administrador, vecino, chofer, recolector y operario, con sus tablas de especialización.
- Rutas con zona, frecuencia, horario y contenedores. Calendario con fecha, cuadrilla y camión. Para asignar una ruta todos sus contenedores deben estar en servicio y tener una incidencia abierta (RF-28.1).
- Incidencias de tipo `roto` o `desborde`, descripción, ubicación y contenedor opcional para reportes de zona. Flujo `abierta → en curso → cerrada`; cierre con solución obligatoria e historial de autor, fecha y estado.
- Recolecciones vinculadas al recolector, contenedor, ruta, cuadrilla y fecha; mantenimientos de camiones, reparaciones vinculadas a incidencias sobre contenedores y reclamos de vecinos sobre incidencias propias.
- Centros de acopio/vertederos con capacidad máxima y ocupada; maquinaria y residuos habilitados separados. Los operarios actualizan su instalación y registran recepciones. Exceso de capacidad genera alerta; un residuo no habilitado se rechaza.
- Reportes por período de incidencias, residuos recibidos, recolecciones e historial. Capacidad actual y capacidad registrada durante el período se muestran por separado. Los reportes permiten consultar inventarios dados de baja.

Las operaciones de escritura, auditoría y notificaciones comparten una transacción. La sesión del servidor determina identidad y permisos; el frontend no persiste perfiles en almacenamiento local y enviar otro `usuario_id` no cambia la autorización. Hay token CSRF, cookies HttpOnly/SameSite, vencimiento a los 30 minutos de inactividad, invalidación al cambiar contraseña/rol y bloqueo de login durante 15 minutos después de cinco fallos. Las notificaciones leídas se registran por usuario.

## API

Cada endpoint devuelve JSON `{status, message?, data?}`. Errores: 400 validación, 401 sesión, 403 permiso/CSRF, 404 inexistente, 405 método, 409 conflicto, 415 formato y 429 bloqueo temporal.

1. `POST Backend/api/usuarios/login.php` con email/password devuelve cookie de sesión y `csrf`.
2. Enviar cookie y cabecera `X-CSRF-Token` en escrituras, con `Content-Type: application/json`.
3. `GET Backend/api/usuarios/sesion.php` obtiene el perfil autenticado y token; `POST .../logout.php` destruye la sesión.

| Grupo | Recursos |
|---|---|
| usuarios | login, registro, sesion, logout, usuarios |
| gestion | contenedores, centros_acopio, residuos, maquinaria, recepciones, capacidad, relaciones, reportes |
| recoleccion | camiones, cuadrillas, rutas, asignaciones, incidencias, recolecciones, mantenimientos, reparaciones, reclamos |
| notificaciones | notificaciones |

Todos terminan en `.php`. Catálogos nuevos: GET/POST/PUT; registros históricos: GET/POST. `capacidad.php` usa PUT. `relaciones.php` usa POST y `accion=miembro|chofer|operario|contenedor|residuo` en JSON. DELETE de contenedores/camiones/centros exige `?motivo=...`. Incidencias usa PUT `?id=...&accion=asignar|resolver`; resolver exige `{solucion}`. Reportes usa GET `?desde=AAAA-MM-DD&hasta=AAAA-MM-DD`.

## Pruebas

El script `Backend/tests/integracion.py` usa la biblioteca estándar de Python y **crea datos**. Ejecutar solo contra una base de prueba que tenga las cuentas de demostración:

```powershell
python Backend/tests/integracion.py http://127.0.0.1:8092
```

La colección Postman incluye login, captura de CSRF, consultas y logout. La suite Python cubre además reglas negativas, permisos, identidad, notificaciones, ciclo operativo y capacidades. La instalación vacía y la migración desde el esquema anterior fueron verificadas en MariaDB 10.4/PHP 8.0; el comando de instalación y migración se repitió para comprobar que no duplicara datos.

## Docker / Linux

`compose.yaml` define MySQL 8, tres contenedores API separados y un servidor web que conserva las URLs del frontend. Los servicios comparten base de datos, módulos PHP y almacenamiento de sesiones. Cada imagen incluye el grupo API que le corresponde; no son repositorios independientes.

Crear `.env` con `SIGERU_DB_PASSWORD` y `SIGERU_ROOT_PASSWORD`, luego ejecutar `docker compose up --build`. Abrir `http://localhost:8080`. El volumen nuevo carga los datos de demostración; un volumen existente no se reinicializa. Para migrarlo, ejecutar el instalador CLI dentro del servicio `usuarios`.

La configuración Docker está preparada pero **no se ejecutó en este equipo**, donde Docker no está instalado. Antes de desplegar, probarla en Linux/MySQL 8. HTTPS y certificados se configuran en el proxy de despliegue.

`sh deploy/respaldo.sh` genera un dump consistente en `output/backups/`. Programarlo en el servidor y probar su restauración en una base separada antes de considerarlo una estrategia operativa de respaldo. No hay ninguna tarea programada en el equipo del usuario.

## Rúbricas de entrega

Ver `docs/segunda-entrega.md` para la comparación con las dos rúbricas y la letra oficial. La suite contiene 155 comprobaciones HTTP y 15 pruebas de validación frontend.

## Alcance pendiente

Ver `docs/trazabilidad.md`. Las proyecciones de llenado y sobrecarga (RF-42/43), traducciones completas, separación en repositorios independientes y validación de infraestructura de producción no están resueltas por esta corrección. `Frontend/config.js` define el idioma base español; cambiar el código de idioma no traduce los textos.
