# SiGeRU — Requisitos funcionales y no funcionales revisados

Versión de trabajo: 12/09/2026. Fuentes: `Ingenieria.pdf` (ERS, pp. 6–10), `Proyecto_ESI_2026.pdf`, rúbricas de ambas entregas, README del equipo y MER compartido en Drive. Esta revisión conserva los identificadores originales. Las aclaraciones propuestas no constituyen una aprobación del docente ni reemplazan la letra oficial. El estado del código se registra por separado en `trazabilidad.md` y `segunda-entrega.md`.

## Alcance y actores

El sistema gestiona residuos urbanos, cuentas, contenedores, flota, instalaciones, cuadrillas, rutas, incidencias, reclamos, recolecciones e informes. No incluye facturación ni liquidación de sueldos. Los roles de cuenta son administrador, vecino, chofer, recolector y operario. Una cuadrilla es un equipo de trabajo, no una cuenta compartida.

El backoffice es la interfaz del administrador. Actualmente está integrado en `Frontend/panel.html`, con controles autorizados por las APIs. Esa integración funcional no acredita la separación de aplicaciones exigida por RNF-04.

## Requisitos funcionales

Cada fila expresa el comportamiento requerido y su aceptación. Los rangos agrupan requisitos que comparten una misma operación sin renumerarlos.

| ID | Actor | Especificación revisada y aceptación |
|---|---|---|
| RF-01 | Administrador | Registrar contenedor con identificador, ubicación, tipo de residuo, estado y condición de servicio. Tras guardar debe aparecer en el inventario correspondiente. |
| RF-02 | Administrador | Modificar el estado del contenedor; el cambio debe reflejarse en ficha, inventario e historial. |
| RF-03 | Administrador | Dar de baja un contenedor indicando motivo. Debe desaparecer de nuevas asignaciones y conservar sus referencias históricas. |
| RF-04 | Administrador | Registrar ubicación por dirección o coordenadas; la ficha debe mostrar la ubicación guardada. El código actual captura dirección; la captura directa de coordenadas del contenedor queda pendiente. |
| RF-05 | Administrador | Guardar un único estado vigente por contenedor. |
| RF-06 | Administrador | Asociar el tipo de residuo al contenedor y mostrarlo al consultarlo. |
| RF-07 | Administrador / cuadrilla asignada | Aceptar únicamente funcional, roto o desbordado. Una cuadrilla solo modifica el estado de contenedores vinculados a sus incidencias en curso. |
| RF-08 | Administrador | Crear cuentas con nombre, correo, cédula, contraseña y rol válido; rechazar correo o cédula duplicados. |
| RF-09 | Usuario | Autenticar mediante correo y contraseña. Solo una cuenta activa y aprobada accede a su panel; las credenciales inválidas no generan sesión. |
| RF-10 | Usuario | Cerrar sesión e invalidarla en el servidor. Una consulta posterior protegida debe exigir autenticación. |
| RF-11 | Administrador | Consultar, modificar y dar de baja cuentas de todos los roles, preservando historial. Los cambios de rol deben actualizar la especialización; el usuario no puede elevar sus propios permisos. |
| RF-11.1 | Administrador | Asociar chofer a cuadrilla; su pertenencia debe poder consultarse y usarse para comprobar asignaciones. |
| RF-11.2 | Administrador | Asociar recolector a cuadrilla; rechazar miembros de otro rol o cuentas inactivas. |
| RF-12 / RF-13 | Administrador | Registrar centros de acopio y vertederos con nombre, ubicación, capacidad máxima y residuos admitidos. Distinguir ambos tipos de instalación. |
| RF-14 / RF-15 | Administrador | Modificar datos de centros y vertederos, conservando la identidad de la instalación. |
| RF-16 / RF-17 | Administrador | Dar de baja centros y vertederos con motivo, conservando recepciones e historial. |
| RF-18 / RF-19 | Administrador | Registrar capacidad máxima de centros y vertederos en m³. No interpretar una capacidad desconocida como cero. |
| RF-20 | Administrador | Habilitar uno o varios tipos de residuo por instalación; rechazar recepciones de tipos no habilitados. |
| RF-20.1 | Operario asignado / administrador | Actualizar capacidad ocupada y estado de recepción. Calcular disponible = máxima − ocupada y porcentaje = ocupada / máxima × 100. Registrar fecha y autor, y alertar cuando se supera el máximo. |
| RF-21 | Administrador | Crear rutas con nombre, zona, frecuencia, horario y contenedores asociados. |
| RF-22 | Administrador | Asignar ruta, cuadrilla y camión en una fecha; comprobar disponibilidad y mostrar la asignación en el calendario operativo. |
| RF-23 | Administrador | Modificar rutas y consultar sus datos vigentes. Su baja debe conservar el historial. |
| RF-24 | Vecino / chofer / recolector / administrador | Reportar incidencia de contenedor o zona con tipo, descripción y ubicación. El servidor registra autor y fecha; el estado inicial es abierta. La interfaz debe ofrecer el reporte a todos estos actores. |
| RF-25 | Administrador / reportante | Clasificar al registrar en roto o desborde; rechazar tipos no contemplados. |
| RF-26 / RF-27 | Administrador / cuadrilla responsable | Mantener el ciclo abierta → en curso → cerrada. La asignación inicia la atención y el cierre exige solución. Registrar autor, fecha y transición; rechazar cierres directos desde abierta. |
| RF-28 | Administrador | Planificar la atención mediante ruta, cuadrilla y camión; la incidencia debe mostrar los recursos asignados. |
| RF-28.1 | Administrador | Rechazar la asignación operativa de una ruta si alguno de sus contenedores carece de incidencia abierta. Distinguir esa asignación de la definición del catálogo de rutas. |
| RF-29 | Vecino | Registrar reclamo con descripción, ubicación, prioridad, autor y fecha, vinculado a una incidencia propia abierta. El administrador debe consultarlo; al cerrar la incidencia se cierran sus reclamos. El vínculo precisa la relación `de` del MER. |
| RF-30 | Administrador | Registrar camión con matrícula única, capacidad y estado. |
| RF-31 | Administrador | Modificar la información del camión, incluido su modelo y asignación de chofer, sin romper referencias. |
| RF-32 | Administrador | Dar de baja camión con motivo, conservando mantenimientos e incidencias; rechazar la baja mientras atiende incidencias en curso. |
| RF-33 | Administrador | Gestionar disponibilidad: Disponible, En Ruta, Mantenimiento o Fuera de servicio. Rechazar nuevas asignaciones incompatibles con el estado. |
| RF-34 | Administrador | Asociar chofer a camión; conservar la cardinalidad del dibujo: un chofer puede manejar varios camiones y cada camión tiene como máximo un chofer vigente. |
| RF-35 / RF-36 / RF-36.1 / RF-36.2 | Recolector / administrador | Registrar recolección con contenedor, fecha, recolector, ruta y cuadrilla. Comprobar pertenencia del recolector y asignación válida para esa fecha. Distinguir datos simulados; conservar el historial. |
| RF-37 | Administrador | Registrar mantenimiento del camión con tipo, fecha, descripción y próxima fecha opcional. Debe poder consultarse por camión. |
| RF-38 | Administrador | Registrar reparación con tipo e inicio/fin, vinculada al contenedor mediante su incidencia. Rechazar fecha final anterior a la inicial. Debe poder consultarse por contenedor. |
| RF-39 | Administrador | Generar reporte de reciclaje a partir de registros existentes del período. No presentar totales de recepción como prueba de reciclaje efectivamente realizado sin registrar ese proceso. |
| RF-40 | Administrador | Reportar cantidades por tipo de residuo y período, separando datos reales y simulados; permitir filtrar por tipo. |
| RF-41 | Administrador | Consultar capacidad máxima, ocupación y disponible de vertederos seleccionados, con histórico del período. |
| RF-42 | Administrador | Estimar llenado de contenedores a partir de mediciones históricas suficientes, mostrando método, fecha y limitaciones. Pendiente; la letra oficial considera opcionales las predicciones. |
| RF-43 | Administrador | Estimar riesgo de sobrecarga por zona con histórico suficiente e identificarlo como estimación. Pendiente; no sustituir una proyección por un conteo histórico. |

### Adiciones explícitas

| ID nuevo | Origen | Requisito y aceptación |
|---|---|---|
| RF-44 | Letra oficial, registro de usuarios | Autorregistro como vecino con solicitud pendiente. Un administrador aprueba o rechaza; una solicitud pendiente o rechazada no permite login. |
| RF-45 | MER: en_servicio / repuesto; letra oficial | Distinguir contenedores desplegados, repuestos y bajas. El administrador puede poner un repuesto en servicio; un repuesto no debe asignarse a ruta ni seleccionarse como contenedor afectado hasta desplegarlo. |
| RF-46 | Letra oficial, maquinaria básica; MER | Administrar inventario de maquinaria por centro con nombre y estado. El operario consulta y modifica solo el inventario de su instalación; la baja administrativa conserva el historial. |
| RF-47 | ERS, backoffice; rúbricas | Proveer inicio administrativo y navegación a usuarios/aprobaciones, contenedores/repuestos, flota, centros, cuadrillas, rutas/calendario, incidencias/reclamos, maquinaria, mantenimiento, recepción y reportes. Las APIs deben rechazar el acceso de roles ajenos aunque se invoquen directamente. |
| RF-48 | ERS, recepción y residuos | Registrar recepción con instalación, residuo, cantidad positiva, autor y marca de simulación. Actualizar ocupación e historial en una misma transacción. |

## Requisitos no funcionales

| ID | Especificación / criterio de verificación | Situación |
|---|---|---|
| RNF-01 | Backend PHP 8, APIs HTTP/JSON con métodos y códigos acordes a cada operación. | Implementado y probado localmente. |
| RNF-02 | MySQL 8 en entrega; PDO y consultas preparadas. MariaDB de XAMPP sirve para desarrollo, sin sustituir la validación MySQL 8. | MySQL 8 pendiente de ejecución. |
| RNF-03 | Separar presentación, controladores de negocio y modelos con SQL; endpoints coordinan la petición. | Separación implementada; clases documentadas. |
| RNF-04 | Mantener Landing, frontend, backoffice y APIs de Usuarios/Gestión/Recolección como componentes identificables, con separación de aplicaciones y bases de código conforme a la letra. | Parcial: panel compartido y módulos fuente compartidos. No se declara microservicios independientes. |
| RNF-05 | Ejecutar la entrega en Linux y Docker con volúmenes persistentes y configuración externa. | Archivos disponibles; despliegue pendiente. |
| RNF-06 | Autenticación en servidor, permisos por rol/recurso y protección CSRF en escrituras. Inactividad de 30 minutos invalida sesión; cinco fallos bloquean temporalmente el login durante 15 minutos. | Pruebas de API; políticas tomadas de Ciberseguridad. |
| RNF-07 | Hash mediante `password_hash` / `password_verify`. Nuevas claves: 8–72 bytes, mayúscula, minúscula, número y símbolo. Las claves heredadas requieren una política de transición. | Implementado; alinear descripción y validación de longitud frontend por bytes. |
| RNF-08 | Enlazar valores de entrada en PDO y permitir solo identificadores SQL controlados por el programa. | Implementado; probado con entradas malformadas. |
| RNF-09 | Auditar operaciones críticas con autor, fecha, recurso y acción, dentro de la transacción de escritura. No registrar contraseñas ni tokens. | Implementado. |
| RNF-10 | Restringir datos y acciones a cada actor; aplicar filtros en el servidor. Probar solicitudes directas con otro rol e identificadores ajenos. | Cobertura HTTP existente; ampliar para cada nueva pantalla. |
| RNF-11 | Respaldo periódico y restauración verificada. Registrar fecha, resultado, retención y responsables en el manual operativo. | Dump y ensayo local disponibles; periodicidad y retención reales pendientes. |
| RNF-12 | Consultas en menos de tres segundos bajo condiciones normales. Antes de medir, fijar hardware, volumen de datos, concurrencia y escenario representativo. | Sin ensayo de carga; esos parámetros deben acordarse. |
| RNF-13 | Etiquetas comprensibles, campos obligatorios, errores visibles y conservación de datos del formulario tras un rechazo. Distinguir alta y edición. | Implementación parcial; requiere revisión de todas las pantallas. |
| RNF-14 | Navegación por teclado, foco visible, etiquetas asociadas y contraste suficiente en escritorio y móvil. | Falta auditoría integral de accesibilidad. |
| RNF-15 | Módulos coherentes, responsabilidades documentadas y cambios comprobables sin introducir frameworks ajenos al README. | Clases y módulos documentados; panel aún extenso. |
| RNF-16 | Instalar desde una base vacía o migrar una instalación anterior siguiendo README; no borrar datos para actualizar. | Instalación y migración repetidas verificadas. |
| RNF-17 | Idioma base español configurable; parametrización completa de textos si se habilitan otros idiomas. | es-UY configurado; traducciones pendientes. |
| RNF-18 | Conservar fecha de creación/modificación e historial relevante, manteniendo autor y referencia después de una baja. | Parcial: no todas las entidades exponen ambas fechas. |
| RNF-19 | Mantener el requisito legal de protección de datos de la ERS. Documentar finalidad, acceso, retención y ejercicio de derechos con revisión competente. | No se certifica cumplimiento legal mediante pruebas de software. |
| RNF-20 | Identificar registros reales y simulados y distinguirlos en reportes. | Recepciones y recolecciones implementadas; no implica certificación legal. |
| RNF-21 | Pruebas de API y flujos funcionales con datos descartables, incluyendo errores, roles, historial y transacciones. | 155 comprobaciones HTTP y 15 validaciones frontend en la última ejecución completa. |
| RNF-22 | Versionar código y documentación con Git y publicar el historial en GitHub. | Cambios locales; publicación de esta revisión pendiente. |

## Correcciones y límites de esta revisión

- RF-26 se armoniza con RF-27: existen tres estados, no solamente abierta/cerrada.
- RN-13 contiene un error de redacción («Varias»); se propone: «Una asignación de ruta solo puede realizarse para contenedores en servicio con una incidencia abierta asociada». Mantiene RF-28.1.
- La relación Chofer–Camión se documenta según el dibujo 1:N. Chofer–Cuadrilla aparece 1:1 en el MER, mientras la ERS habla de agrupar choferes: debe acordarse si se admite más de un chofer vigente por cuadrilla. El código actual los admite; esta revisión no oculta la discrepancia.
- La limitación actual de una asignación por camión/cuadrilla/día debe revisarse si se requieren varios turnos. No es una restricción expresada por la ERS original.
- El backoffice está integrado; separarlo en una aplicación independiente sigue siendo un trabajo de arquitectura, no una corrección de terminología.
- Reportes de reciclaje efectivo, filtros específicos por instalación/residuo, fichas de historial por recurso y proyecciones necesitan completar o ampliar su implementación. Los listados existentes no acreditan por sí solos todos esos criterios.
- Se conserva el alcance exigido aun donde hay pendientes. Los PDF originales y los documentos compartidos no fueron sobrescritos.
