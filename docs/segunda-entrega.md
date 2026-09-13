# Criterios de la segunda entrega FullStack

Actualización de entrega del 13/09/2026: tres carpetas API; notificaciones en Usuarios. Se entrega un solo SQL para instalación vacía. Migración y herramientas históricas archivadas fuera del proyecto. Pruebas vigentes: 79 comprobaciones HTTP en PHP y 15 en JavaScript del navegador; no se atribuye a esta suite toda la cobertura operativa histórica. Backoffice disponible en Frontend/backoffice.html. Docker usa Apache y continúa pendiente de ejecución. Las menciones a migraciones anteriores describen evidencia histórica, no archivos que deban ejecutar en la entrega.

Revisión contra `rubricaPrimerEntrega.txt`, `rubricaSegundaEntrega.txt` y `Proyecto_ESI_2026.pdf` (pp. 8–13, 17–18), aportados por el usuario. Este documento registra evidencia técnica; la calificación corresponde al docente.

| Criterio | Evidencia disponible |
|---|---|
| Landing, registro y login | Páginas y API existentes, registro validado en ambas capas. Solicitudes nuevas pendientes de aprobación administrativa, conforme al apartado 6.3 de la letra. Login autentica contra PDO y crea sesión. |
| Persistencia y MVC | MySQL/MariaDB, modelos/controladores/endpoints. Instalador para base vacía y migración conservadora. |
| CRUD de todos los roles | Administrador, vecino, chofer, recolector, operario; cuadrilla es el equipo del DER. Creación, lectura, modificación y baja verificadas para cada rol. |
| CRUD flota | Matrícula, capacidad y estado; consultas y actualización verificadas; baja conserva relaciones. |
| CRUD contenedores | Ubicación, tipo de residuo y estado; baja con motivo. Inventario separado entre desplegados y repuestos; solo los desplegados pueden incorporarse a rutas. |
| CRUD centros y maquinaria | Acopio/vertedero, capacidad y estado; maquinaria independiente con alta, consulta, edición y baja. |
| Validación frontend/backend | Cédula, email, nombre, contraseña, opciones de estado, longitudes, números e identificadores; rechazos de JSON inválido y campos compuestos. |
| Seguridad | Consultas preparadas, escape HTML al renderizar, permisos por sesión/rol, CSRF, no persistencia de perfiles en localStorage/sessionStorage. |
| Datos de prueba | Cinco roles en instalación nueva, flota, contenedores, instalaciones, maquinaria, residuos y relaciones demostrativas. Migración de instalaciones previas no inventa capacidades ni inserta usuarios de demostración. |
| Testing y HTTP | `Backend/tests/integracion.php`: 79 comprobaciones HTTP; `Backend/tests/validacion_frontend.html`: 15 validaciones. Colección Postman adicional. Respuestas 200/201/400/401/403/404/405/409/415/429/500 según caso. |
| Docker | Dockerfiles de APIs y frontend, proxy y Compose con MySQL 8 y volúmenes. No ejecutado en este equipo por ausencia de Docker. |
| Git/GitHub | Existe historial Git y remoto BCC-Code-Corp/sigeru-project. El envío de esta corrección al remoto requiere una publicación explícita; este documento no lo da por realizado. |

## Guion de demostración

1. Instalar una base vacía y abrir la landing.
2. Registrar un vecino: comprobar validaciones y mensaje de solicitud pendiente.
3. Ingresar como administrador, editar el usuario y pasar solicitud a `aprobado`.
4. Ingresar con esa cuenta y comprobar que no accede a gestión administrativa.
5. Mostrar alta, edición, consulta y baja de usuario, camión, contenedor, centro y maquinaria.
6. Ejecutar la suite HTTP en una base descartable. Mostrar rechazos de permisos, validaciones, métodos no soportados y conservación de historial.
7. Mostrar esquema relacional y migración; explicar cuadrilla vs. usuario y chofer vs. recolector.
8. Mostrar los archivos Docker y distinguir configuración escrita de despliegue efectivamente probado.

## Obligaciones que no se resuelven con estos CRUD

La letra exige aplicaciones con bases de código independientes, infraestructura Linux/MySQL 8, publicación accesible y GitHub. Las imágenes se separan por API pero aún comparten módulos fuente en este repositorio; esto no equivale a repositorios totalmente independientes. La demostración local se hizo en PHP 8.0/MariaDB 10.4. La comprobación final en Docker/MySQL 8, los tres servidores (aplicación, BD y respaldo), SSH/red/firewall y la publicación se deben realizar en la infraestructura destinada a la entrega.

La letra ubica la API de recolección completa, incidencias, mapa, informes y alertas en tercera entrega; parte ya está adelantada. Las predicciones son opcionales. No corresponde relegar la corrección de CRUD por desarrollar predicciones. El inventario de contenedores de repuesto está implementado y validado junto con su puesta en servicio.

## Verificación del 12 de septiembre de 2026

Pasaron 79 comprobaciones HTTP y 15 validaciones frontend después de trasladar las consultas de los controladores a los modelos. Se verificó la sintaxis de todos los archivos PHP. En bases aisladas del puerto 3308 se comprobó tanto la instalación nueva como la migración desde el esquema original, repitiendo cada comando sin duplicar pasos. La instalación nueva contiene cinco usuarios y un contenedor de repuesto; la migración conserva los cuatro usuarios originales sin insertar cuentas de demostración.

Se contrastaron las tecnologías con el README original del repositorio (`f848cf8`). El archivo `Downloads/README.md` aportado posteriormente contiene una respuesta HTTP 503, por lo que no se utilizó como documentación técnica. Se verificó posteriormente el acceso al archivo «MER» mediante el acceso directo «EL MER REAL DEL PROYECTO FINAL» de Drive. La página Página-1 coincide visualmente en entidades, atributos y relaciones con MER.drawio.png, utilizado para las correcciones. La vista indica que el último cambio fue hace tres días.

Las otras asignaturas piden entregables y validación con personas: no se inventan entrevistas, feedback de docentes, retrospectivas ni evidencia de infraestructura. Deben completarse con trabajo y evidencias reales del equipo.
