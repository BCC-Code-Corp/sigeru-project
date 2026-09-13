# Revisión de requerimientos y DER

Actualización de entrega del 13/09/2026: tres carpetas API; notificaciones en Usuarios. Se entrega un solo SQL para instalación vacía. Migración y herramientas históricas archivadas fuera del proyecto. Pruebas vigentes: 79 comprobaciones HTTP en PHP y 15 en JavaScript del navegador; no se atribuye a esta suite toda la cobertura operativa histórica. Backoffice disponible en Frontend/backoffice.html. Docker usa Apache y continúa pendiente de ejecución. Las menciones a migraciones anteriores describen evidencia histórica, no archivos que deban ejecutar en la entrega.

Fuentes: Ingeniería, pp. 5–10 y 13; TablasYMER, pp. 4–10; MER.drawio.png; Ciberseguridad, pp. 5–9. UTULAB aporta contexto de los actores; los documentos de Emprendedurismo, Reglamento y Sistemas Operativos contienen principalmente obligaciones organizativas e infraestructura y no sustituyen la ERS.

El 12 de septiembre se abrió también el [MER compartido en Drive](https://app.diagrams.net/#G1ZcM1holZ0JGQOyNFJiBcAXMiRwOlqcu_), página Página-1, desde el acceso directo «EL MER REAL DEL PROYECTO FINAL». La comparación visual con la imagen local confirma las mismas entidades, atributos y relaciones; no se identificó una nueva versión del modelo que requiera reemplazar el esquema por este motivo. El acceso quedó verificado sin modificar el diagrama compartido.

| Requisito | Implementación / verificación |
|---|---|
| RF-01–07, RN-01/06 | Tipo de residuo, ubicación, estados funcional/roto/desbordado, baja con motivo y preservación de historial; cuadrilla modifica estado solo para incidencias propias en curso. |
| RF-08–11, RN-08 | Sesión real, logout, ABM con baja lógica y autorización de roles del servidor. Autoregistro siempre vecino; perfil propio no permite elevar rol. |
| RF-11.1/11.2 | Cuadrillas, choferes y recolectores separados; relación miembro. |
| RF-12–20.1, RN-09/14 | Acopio/vertedero, capacidad, residuos habilitados, maquinaria, operario asignado, recepciones, alertas e historial de capacidad. |
| RF-21–23/28/28.1 | Rutas, contiene, sigue, realiza y asignaciones con fecha. Asignación registra ruta y recursos sobre incidencias abiertas de sus contenedores. |
| RF-24–27, RN-02/03/05/12 | Incidencia abierta con descripción; asignación a recursos disponibles; cierre con solución por administrador o cuadrilla responsable; cerradas no se editan. |
| RF-29 | Reclamo separado sobre incidencia propia abierta; cierre propagado. El vecino puede reportar una zona sin seleccionar contenedor. |
| RF-30–34 | ABM de flota, disponibilidad y relación chofer/camión; bajas no borran mantenimiento/incidencias. |
| RF-35–36.2 | Recolección con fecha, recolector, contenedor, cuadrilla y asignación de ruta válida para ese día. |
| RF-37/38 | Mantenimientos con relación recibe y reparaciones con necesita/sobre. |
| RF-39–41, RN-10 | Reporte de residuos por tipo/origen simulado y período; capacidad actual e historial del período. No se inventan mediciones ausentes. |
| RF-42/43 | Pendientes: proyecciones de llenado y sobrecarga. El DER no define mediciones de nivel/capacidad de contenedores; hace falta definir y cargar ese histórico antes de estimarlas. |
| RNF-06–10/18 | Sesión, roles, password hash, consultas preparadas, auditoría transaccional, fechas y restricciones por actor. |
| RNF-03/04/05 | MVC y tres imágenes API con módulos compartidos. Compose preparado, no ejecutado; repositorios independientes y validación Linux pendientes. |
| RNF-11 | Script de dump incluido. Programación de backups y ensayo periódico de restauración pendientes en infraestructura real. |
| RNF-12/14/19 | No se certifican SLA, accesibilidad integral ni cumplimiento legal con una prueba local. |
| RNF-17 | Idioma base configurable en es-UY. Traducciones de interfaz pendientes. |
| RNF-20/21 | Recolecciones/recepciones distinguen datos simulados. Suite HTTP y colección Postman actualizadas. |

## Decisiones de modelado

- Se mantienen nombres/plurales del código y claves `id`; equivalen a los identificadores del pasaje a tablas. Matrícula sigue siendo clave única del camión y referencia de sus relaciones.
- El dibujo marca Chofer–Camión 1:N, aunque el texto dice 1:1. Se usa 1:N: un camión tiene un chofer y un chofer puede estar asociado a varios camiones.
- Cuadrilla ya no es un rol ni una FK a Usuario. Las cuentas heredadas pasan a recolectores conservando sus IDs y su pertenencia.
- Se conservan filas de especializaciones anteriores para no romper referencias históricas después de cambiar roles. El rol vigente y la actividad del usuario determinan acceso y nuevas asignaciones.
- No se deduce qué contenedor corresponde a una incidencia antigua a partir de una dirección parecida. La relación sobre se carga explícitamente.
- El pasaje a tablas no incluye la entidad Recolección aunque la ERS la exige; se agrega con sus cuatro FKs. Se agregan además asignaciones fechadas, historial, recepciones y auditoría.
- Maquinaria deja de ser únicamente texto libre. La columna antigua se conserva como dato heredado; el inventario vigente usa la tabla maquinaria.
- El calendario admite una asignación por cuadrilla/camión y día. Para múltiples turnos diarios debe ampliarse la clave con una franja horaria.
- La capacidad usa m³. El exceso se guarda y genera alerta (RN-09); no se descarta la recepción ya realizada.

## Evidencia local

- Importación del esquema completo en base aislada; instalación repetida sin duplicación.
- Migración del SQL original recuperado de Git; conservó 4 usuarios y 3 incidencias. Cuenta 4 pasó a recolector miembro de cuadrilla 4. Migración repetida sin duplicación.
- Suite de integración HTTP: permisos, CSRF, política de password, bloqueo de login, operaciones, alertas, historial, bajas y logout.
- Navegador: login y creación de ruta desde el panel con persistencia visible.
- Los datos reales del usuario no fueron modificados; MySQL local estaba apagado. Las pruebas usaron otra instancia en puerto 3308.
