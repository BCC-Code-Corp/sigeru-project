-- Modelo físico y datos de demostración. Importar una vez en una base VACÍA.
-- No importar sobre una instalación con datos existentes.
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- --------------------------------------------------------
-- Limpieza previa (orden inverso a las dependencias FK)
-- --------------------------------------------------------

-- --------------------------------------------------------
-- Tabla: usuarios
-- --------------------------------------------------------
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `cedula` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('administrador','operario','cuadrilla','vecino') NOT NULL DEFAULT 'vecino',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `cedula` (`cedula`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Tabla: camiones
-- --------------------------------------------------------
CREATE TABLE `camiones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `matricula` varchar(20) NOT NULL,
  `capacidad_carga` decimal(10,2) DEFAULT NULL,
  `estado` varchar(30) NOT NULL DEFAULT 'Disponible',
  `cuadrilla_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `matricula` (`matricula`),
  KEY `fk_camion_cuadrilla` (`cuadrilla_id`),
  CONSTRAINT `fk_camion_cuadrilla` FOREIGN KEY (`cuadrilla_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Tabla: contenedores
-- --------------------------------------------------------
CREATE TABLE `contenedores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ubicacion` varchar(255) NOT NULL,
  `estado` enum('lleno','vacio','mantenimiento') DEFAULT 'vacio',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Tabla: incidencias
-- --------------------------------------------------------
CREATE TABLE `incidencias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ubicacion` varchar(255) NOT NULL,
  `latitud` decimal(10,7) DEFAULT NULL,
  `longitud` decimal(10,7) DEFAULT NULL,
  `estado_contenedor` varchar(100) NOT NULL,
  `tipo_basura` varchar(100) NOT NULL,
  `estado_incidencia` varchar(50) DEFAULT 'abierta',
  `usuario_id` int(11) DEFAULT NULL,
  `cuadrilla_id` int(11) DEFAULT NULL,
  `matricula_camion` varchar(20) DEFAULT NULL,
  `comentario_operario` varchar(255) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_incidencia_usuario` (`usuario_id`),
  KEY `fk_incidencia_cuadrilla` (`cuadrilla_id`),
  KEY `fk_incidencia_camion` (`matricula_camion`),
  CONSTRAINT `fk_incidencia_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_incidencia_cuadrilla` FOREIGN KEY (`cuadrilla_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_incidencia_camion` FOREIGN KEY (`matricula_camion`) REFERENCES `camiones` (`matricula`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Tabla: notificaciones
-- --------------------------------------------------------
-- usuario_id = NULL  ->  anuncio público (visible para todos los usuarios)
-- usuario_id = X     ->  notificación privada para el usuario X
--                        (por ejemplo, el cambio de estado de su incidencia)
CREATE TABLE `notificaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) DEFAULT NULL,
  `tipo` varchar(30) NOT NULL DEFAULT 'sistema',
  `mensaje` varchar(500) NOT NULL,
  `incidencia_id` int(11) DEFAULT NULL,
  `leida` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_notificacion_usuario` (`usuario_id`),
  KEY `fk_notificacion_incidencia` (`incidencia_id`),
  CONSTRAINT `fk_notificacion_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_notificacion_incidencia` FOREIGN KEY (`incidencia_id`) REFERENCES `incidencias` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Tabla: centros_acopio
-- --------------------------------------------------------
CREATE TABLE `centros_acopio` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `ubicacion` varchar(255) NOT NULL,
  `maquinaria` varchar(255) DEFAULT NULL,
  `estado` varchar(30) NOT NULL DEFAULT 'Operativo',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Datos de ejemplo
-- --------------------------------------------------------
-- Contraseñas de prueba (en texto plano, documentadas también en el README):
--   admin@sigeru.uy      -> admin123
--   vecino@sigeru.uy     -> vecino123
--   operario@sigeru.uy   -> operario123
--   cuadrilla@sigeru.uy  -> cuadrilla123
INSERT INTO `usuarios` (`nombre`, `email`, `cedula`, `password`, `rol`) VALUES
('Admin Prueba', 'admin@sigeru.uy', '10000008', '$2b$10$t3DloQv8mEmIeQb.Jzq5NOTQRHv33IGcipQZraC.6r3C34WlLvmgO', 'administrador'),
('Vecino Prueba', 'vecino@sigeru.uy', '10000014', '$2b$10$PWDGKWPX1Pp9wzlDwV5PAuKGubAQZBH180E1QBo74DiZJ0MZy97Sm', 'vecino'),
('Operario Prueba', 'operario@sigeru.uy', '10000020', '$2b$10$9eiyJONfrLI.v1FF/0Rx6.fipIrPAm/7x/J8n3/kzm5q24bGGi5wO', 'operario'),
('Cuadrilla Prueba', 'cuadrilla@sigeru.uy', '10000036', '$2b$10$s1sQ/IpEgrlVE5GbHVuu4OWk.hgS5svE9rGHxaYh8HEzvJYfyDvs6', 'cuadrilla');

-- El camión 'ABC 1234' ya tiene asignada a la Cuadrilla Prueba (id 4)
-- de forma persistente, a modo de ejemplo de la asignación cuadrilla-camión.
INSERT INTO `camiones` (`matricula`, `capacidad_carga`, `estado`, `cuadrilla_id`) VALUES
('ABC 1234', 100.00, 'Disponible', 4),
('SBJ 3422', 73.00, 'Disponible', NULL),
('XYZ 9087', 120.50, 'En Ruta', NULL),
('LMN 5566', 85.00, 'Mantenimiento', NULL);

INSERT INTO `contenedores` (`ubicacion`, `estado`) VALUES
('Av Italia 1413', 'lleno'),
('18 de Julio esq. Ejido', 'vacio'),
('Bulevar Artigas 2340', 'mantenimiento'),
('Rambla Rep. de Chile 1200', 'vacio');

INSERT INTO `incidencias` (`ubicacion`, `estado_contenedor`, `tipo_basura`, `estado_incidencia`, `usuario_id`) VALUES
('Alejandro Gallinal 1675', 'Lleno', 'Reciclable', 'abierta', 2),
('Av Italia 1333', 'Desbordado', 'Normal / Orgánica', 'abierta', 2),
('Camino Cervando y Camino Carrasco', 'Roto / Dañado', 'Reciclable', 'abierta', 2);

INSERT INTO `centros_acopio` (`nombre`, `ubicacion`, `maquinaria`, `estado`) VALUES
('Centro de Acopio Norte', 'Ruta 8 km 17', 'Compactadora, cinta transportadora', 'Operativo'),
('Centro de Acopio Sur', 'Camino Maldonado km 22', 'Cargador frontal, prensa de fardos', 'Operativo'),
('Planta de Clasificación Este', 'Ruta 102 km 5', 'Cinta de selección manual, imán separador', 'Mantenimiento');

INSERT INTO `notificaciones` (`usuario_id`, `tipo`, `mensaje`) VALUES
(NULL, 'anuncio', 'Bienvenido a SiGeRU: ya podés reportar incidencias de contenedores y hacer seguimiento de su estado desde tu panel.');


-- Ejecutar UNA VEZ sobre el esquema anterior, después de realizar un respaldo.
-- No elimina registros. Los datos históricos desconocidos quedan NULL.
CREATE TABLE cuadrillas (
 id int PRIMARY KEY AUTO_INCREMENT, nombre varchar(100) NOT NULL,
 disponibilidad boolean NOT NULL DEFAULT 1,
 fecha_creacion timestamp DEFAULT CURRENT_TIMESTAMP,
 fecha_modificacion timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;
INSERT INTO cuadrillas(id,nombre) SELECT id,nombre FROM usuarios WHERE rol='cuadrilla';
ALTER TABLE usuarios MODIFY rol enum('administrador','operario','cuadrilla','vecino','chofer','recolector') NOT NULL DEFAULT 'vecino',
 ADD activo boolean NOT NULL DEFAULT 1,
 ADD fecha_creacion timestamp DEFAULT CURRENT_TIMESTAMP,
 ADD fecha_modificacion timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
CREATE TABLE funcionarios (
 id int PRIMARY KEY, fecha_contratacion date NULL, direccion varchar(255), email_laboral varchar(100),
 FOREIGN KEY(id) REFERENCES usuarios(id)
) ENGINE=InnoDB;
CREATE TABLE administradores (id int PRIMARY KEY, nombre_municipio varchar(100), FOREIGN KEY(id) REFERENCES funcionarios(id)) ENGINE=InnoDB;
CREATE TABLE choferes (id int PRIMARY KEY, lic_conducir varchar(100), cuadrilla_id int NULL,
 FOREIGN KEY(id) REFERENCES funcionarios(id), FOREIGN KEY(cuadrilla_id) REFERENCES cuadrillas(id)) ENGINE=InnoDB;
CREATE TABLE recolectores (id int PRIMARY KEY, turno varchar(50), cuadrilla_id int NULL,
 FOREIGN KEY(id) REFERENCES funcionarios(id), FOREIGN KEY(cuadrilla_id) REFERENCES cuadrillas(id)) ENGINE=InnoDB;
CREATE TABLE vecinos (id int PRIMARY KEY, direccion varchar(255), FOREIGN KEY(id) REFERENCES usuarios(id)) ENGINE=InnoDB;
INSERT INTO funcionarios(id) SELECT id FROM usuarios WHERE rol<>'vecino';
INSERT INTO administradores(id) SELECT id FROM usuarios WHERE rol='administrador';
INSERT INTO vecinos(id) SELECT id FROM usuarios WHERE rol='vecino';
INSERT INTO recolectores(id,cuadrilla_id) SELECT id,id FROM usuarios WHERE rol='cuadrilla';
UPDATE usuarios SET rol='recolector' WHERE rol='cuadrilla';
ALTER TABLE usuarios MODIFY rol enum('administrador','operario','vecino','chofer','recolector') NOT NULL DEFAULT 'vecino';
ALTER TABLE camiones DROP FOREIGN KEY fk_camion_cuadrilla,
 ADD CONSTRAINT fk_camion_equipo FOREIGN KEY(cuadrilla_id) REFERENCES cuadrillas(id),
 ADD modelo varchar(100), ADD chofer_id int NULL, ADD centro_id int NULL,
 ADD activo boolean NOT NULL DEFAULT 1, ADD motivo_baja varchar(255),
 ADD fecha_creacion timestamp DEFAULT CURRENT_TIMESTAMP,
 ADD fecha_modificacion timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 ADD FOREIGN KEY(chofer_id) REFERENCES choferes(id);
ALTER TABLE contenedores MODIFY estado varchar(30) NOT NULL DEFAULT 'funcional',
 ADD tipo_residuo varchar(100) NULL, ADD tipo_contenedor varchar(100) NULL,
 ADD en_servicio boolean NOT NULL DEFAULT 1, ADD motivo_baja varchar(255),
 ADD fecha_creacion timestamp DEFAULT CURRENT_TIMESTAMP,
 ADD fecha_modificacion timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
UPDATE contenedores SET estado=CASE estado WHEN 'vacio' THEN 'funcional' WHEN 'lleno' THEN 'desbordado' WHEN 'mantenimiento' THEN 'roto' ELSE estado END;
ALTER TABLE contenedores MODIFY estado enum('funcional','roto','desbordado') NOT NULL DEFAULT 'funcional';
ALTER TABLE centros_acopio ADD tipo_centro enum('acopio','vertedero') NOT NULL DEFAULT 'acopio',
 ADD capacidad decimal(12,2) NULL, ADD capacidad_ocupada decimal(12,2) NOT NULL DEFAULT 0,
 ADD administrador_id int NULL, ADD activo boolean NOT NULL DEFAULT 1, ADD motivo_baja varchar(255),
 ADD fecha_creacion timestamp DEFAULT CURRENT_TIMESTAMP,
 ADD fecha_modificacion timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 ADD FOREIGN KEY(administrador_id) REFERENCES administradores(id);
ALTER TABLE camiones ADD FOREIGN KEY(centro_id) REFERENCES centros_acopio(id);
CREATE TABLE operarios (id int PRIMARY KEY, especialidad varchar(100), planta_reciclaje varchar(100), centro_id int NULL,
 FOREIGN KEY(id) REFERENCES funcionarios(id), FOREIGN KEY(centro_id) REFERENCES centros_acopio(id)) ENGINE=InnoDB;
INSERT INTO operarios(id) SELECT id FROM usuarios WHERE rol='operario';
CREATE TABLE maquinaria (id int PRIMARY KEY AUTO_INCREMENT, nombre varchar(255) NOT NULL, estado varchar(30) NOT NULL DEFAULT 'Operativo', centro_id int NOT NULL,
 fecha_creacion timestamp DEFAULT CURRENT_TIMESTAMP, fecha_modificacion timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY(centro_id) REFERENCES centros_acopio(id)) ENGINE=InnoDB;
INSERT INTO maquinaria(nombre,estado,centro_id) SELECT maquinaria,estado,id FROM centros_acopio WHERE maquinaria IS NOT NULL AND maquinaria<>'';
CREATE TABLE residuos (id int PRIMARY KEY AUTO_INCREMENT, tipo_residuo varchar(100) NOT NULL UNIQUE) ENGINE=InnoDB;
CREATE TABLE gestionan (centro_id int NOT NULL, residuo_id int NOT NULL, PRIMARY KEY(centro_id,residuo_id),
 FOREIGN KEY(centro_id) REFERENCES centros_acopio(id), FOREIGN KEY(residuo_id) REFERENCES residuos(id)) ENGINE=InnoDB;
CREATE TABLE rutas (id int PRIMARY KEY AUTO_INCREMENT, nombre varchar(100) NOT NULL, zona varchar(255) NOT NULL,
 frecuencia varchar(100) NOT NULL, horario varchar(100) NOT NULL, activo boolean NOT NULL DEFAULT 1,
 fecha_creacion timestamp DEFAULT CURRENT_TIMESTAMP, fecha_modificacion timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB;
CREATE TABLE sigue (cuadrilla_id int NOT NULL, ruta_id int NOT NULL, PRIMARY KEY(cuadrilla_id,ruta_id),
 FOREIGN KEY(cuadrilla_id) REFERENCES cuadrillas(id), FOREIGN KEY(ruta_id) REFERENCES rutas(id)) ENGINE=InnoDB;
CREATE TABLE realiza (matricula varchar(20) NOT NULL, ruta_id int NOT NULL, PRIMARY KEY(matricula,ruta_id),
 FOREIGN KEY(matricula) REFERENCES camiones(matricula), FOREIGN KEY(ruta_id) REFERENCES rutas(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
CREATE TABLE contiene (ruta_id int NOT NULL, contenedor_id int NOT NULL, PRIMARY KEY(ruta_id,contenedor_id),
 FOREIGN KEY(ruta_id) REFERENCES rutas(id), FOREIGN KEY(contenedor_id) REFERENCES contenedores(id)) ENGINE=InnoDB;
ALTER TABLE incidencias DROP FOREIGN KEY fk_incidencia_cuadrilla,
 ADD CONSTRAINT fk_incidencia_equipo FOREIGN KEY(cuadrilla_id) REFERENCES cuadrillas(id),
 ADD descripcion text NULL, ADD solucion text NULL, ADD fecha_cierre datetime NULL, ADD ruta_id int NULL,
 ADD fecha_modificacion timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 ADD FOREIGN KEY(ruta_id) REFERENCES rutas(id);
UPDATE incidencias SET estado_incidencia='cerrada' WHERE estado_incidencia IN ('incidencia solucionada','resuelta','solucionada');
ALTER TABLE incidencias MODIFY estado_incidencia enum('abierta','en curso','cerrada') NOT NULL DEFAULT 'abierta';
CREATE TABLE sobre (contenedor_id int NOT NULL, incidencia_id int NOT NULL, PRIMARY KEY(contenedor_id,incidencia_id),
 FOREIGN KEY(contenedor_id) REFERENCES contenedores(id), FOREIGN KEY(incidencia_id) REFERENCES incidencias(id)) ENGINE=InnoDB;
CREATE TABLE reclamos (id int PRIMARY KEY AUTO_INCREMENT, usuario_id int NOT NULL, incidencia_id int NOT NULL,
 descripcion text NOT NULL, ubicacion varchar(255) NOT NULL, prioridad enum('baja','media','alta') DEFAULT 'media',
 estado enum('abierto','en curso','cerrado') DEFAULT 'abierto', fecha_creacion timestamp DEFAULT CURRENT_TIMESTAMP,
 fecha_modificacion timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY(usuario_id) REFERENCES vecinos(id), FOREIGN KEY(incidencia_id) REFERENCES incidencias(id)) ENGINE=InnoDB;
CREATE TABLE asignaciones (id int PRIMARY KEY AUTO_INCREMENT, ruta_id int NOT NULL, cuadrilla_id int NOT NULL, matricula varchar(20) NOT NULL,
 fecha date NOT NULL, fecha_creacion timestamp DEFAULT CURRENT_TIMESTAMP,
 UNIQUE(cuadrilla_id,fecha), UNIQUE(matricula,fecha),
 FOREIGN KEY(ruta_id) REFERENCES rutas(id), FOREIGN KEY(cuadrilla_id) REFERENCES cuadrillas(id), FOREIGN KEY(matricula) REFERENCES camiones(matricula)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
CREATE TABLE recolecciones (id int PRIMARY KEY AUTO_INCREMENT, contenedor_id int NOT NULL, recolector_id int NOT NULL,
 ruta_id int NOT NULL, cuadrilla_id int NOT NULL, fecha datetime NOT NULL, volumen decimal(12,2) NULL,
 simulado boolean NOT NULL DEFAULT 0, fecha_creacion timestamp DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(contenedor_id) REFERENCES contenedores(id), FOREIGN KEY(recolector_id) REFERENCES recolectores(id),
 FOREIGN KEY(ruta_id) REFERENCES rutas(id), FOREIGN KEY(cuadrilla_id) REFERENCES cuadrillas(id)) ENGINE=InnoDB;
CREATE TABLE mantenimientos (id int PRIMARY KEY AUTO_INCREMENT, descripcion text NOT NULL, tipo_man varchar(100) NOT NULL,
 fecha_man date NOT NULL, prox_man date NULL, fecha_creacion timestamp DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB;
CREATE TABLE recibe (matricula varchar(20) NOT NULL, mantenimiento_id int NOT NULL, PRIMARY KEY(matricula,mantenimiento_id),
 FOREIGN KEY(matricula) REFERENCES camiones(matricula), FOREIGN KEY(mantenimiento_id) REFERENCES mantenimientos(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
CREATE TABLE reparaciones (id int PRIMARY KEY AUTO_INCREMENT, tipo_reparacion varchar(100) NOT NULL,
 fecha_inicio date NOT NULL, fecha_fin date NULL, fecha_creacion timestamp DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB;
CREATE TABLE necesita (incidencia_id int NOT NULL, reparacion_id int NOT NULL, PRIMARY KEY(incidencia_id,reparacion_id),
 FOREIGN KEY(incidencia_id) REFERENCES incidencias(id), FOREIGN KEY(reparacion_id) REFERENCES reparaciones(id)) ENGINE=InnoDB;
CREATE TABLE recepciones (id int PRIMARY KEY AUTO_INCREMENT, centro_id int NOT NULL, residuo_id int NOT NULL, operario_id int NOT NULL,
 cantidad decimal(12,2) NOT NULL, simulado boolean NOT NULL DEFAULT 0, fecha_creacion timestamp DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(centro_id,residuo_id) REFERENCES gestionan(centro_id,residuo_id), FOREIGN KEY(operario_id) REFERENCES usuarios(id)) ENGINE=InnoDB;
CREATE TABLE historial_incidencias (id int PRIMARY KEY AUTO_INCREMENT, incidencia_id int NOT NULL, usuario_id int NOT NULL,
 estado_anterior varchar(30), estado_nuevo varchar(30) NOT NULL, observacion text NOT NULL, fecha timestamp DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(incidencia_id) REFERENCES incidencias(id), FOREIGN KEY(usuario_id) REFERENCES usuarios(id)) ENGINE=InnoDB;
CREATE TABLE auditoria (id bigint PRIMARY KEY AUTO_INCREMENT, usuario_id int NULL, recurso varchar(100) NOT NULL,
 accion varchar(30) NOT NULL, referencia varchar(100), detalle text, fecha timestamp DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB;
CREATE TABLE notificaciones_leidas (notificacion_id int NOT NULL, usuario_id int NOT NULL, PRIMARY KEY(notificacion_id,usuario_id),
 FOREIGN KEY(notificacion_id) REFERENCES notificaciones(id), FOREIGN KEY(usuario_id) REFERENCES usuarios(id)) ENGINE=InnoDB;

ALTER TABLE usuarios ADD auth_version int NOT NULL DEFAULT 1;
CREATE TABLE intentos_login (email_hash char(64) PRIMARY KEY, intentos int NOT NULL DEFAULT 0, bloqueado_hasta datetime NULL, actualizado timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB;

CREATE TABLE historial_capacidad (id bigint PRIMARY KEY AUTO_INCREMENT, centro_id int NOT NULL, usuario_id int NOT NULL, capacidad decimal(12,2) NOT NULL, capacidad_ocupada decimal(12,2) NOT NULL, estado varchar(30) NOT NULL, fecha timestamp DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(centro_id) REFERENCES centros_acopio(id), FOREIGN KEY(usuario_id) REFERENCES usuarios(id)) ENGINE=InnoDB;

ALTER TABLE usuarios ADD estado_registro enum('pendiente','aprobado','rechazado') NOT NULL DEFAULT 'aprobado';
ALTER TABLE maquinaria ADD activo boolean NOT NULL DEFAULT 1, ADD motivo_baja varchar(255);

ALTER TABLE contenedores ADD activo boolean NOT NULL DEFAULT 1;
UPDATE contenedores SET activo=en_servicio;

-- Datos adicionales de demostración SOLO para instalación nueva.
INSERT INTO usuarios(nombre,email,cedula,password,rol) VALUES
('Chofer Prueba','chofer@sigeru.uy','20000006','$2y$10$qRYWe1Rg4NQa.y4gmqh/teMz9sQbR1LZLmgcfR4UTLoI3ThWp2wQS','chofer');
INSERT INTO funcionarios(id,fecha_contratacion,direccion,email_laboral) SELECT id,'2026-01-01','Buceo, Montevideo',email FROM usuarios WHERE email='chofer@sigeru.uy';
INSERT INTO choferes(id,lic_conducir,cuadrilla_id) SELECT id,'Licencia de ejemplo',4 FROM usuarios WHERE email='chofer@sigeru.uy';
UPDATE camiones SET chofer_id=(SELECT id FROM usuarios WHERE email='chofer@sigeru.uy'),modelo='Recolector de prueba' WHERE matricula='ABC 1234';
UPDATE contenedores SET tipo_residuo='Orgánico',tipo_contenedor='Urbano';
UPDATE centros_acopio SET capacidad=100,capacidad_ocupada=0;
UPDATE operarios SET centro_id=1 WHERE id=3;
INSERT INTO residuos(tipo_residuo) VALUES ('Orgánico'),('Reciclable');
INSERT INTO gestionan(centro_id,residuo_id) SELECT c.id,r.id FROM centros_acopio c CROSS JOIN residuos r;

INSERT INTO contenedores(ubicacion,estado,tipo_residuo,tipo_contenedor,en_servicio) VALUES ('Depósito municipal de prueba','funcional','Orgánico','Urbano',0);

-- --------------------------------------------------------
-- Datos de prueba adicionales: operativa de recolección
-- (rutas, asignaciones, recolecciones, mantenimientos,
--  reparaciones, reclamos y tablas de relación del DER)
-- --------------------------------------------------------

-- Una instalación como vertedero, para dejar de ejemplo la distinción
-- tipo_centro=acopio / vertedero que introduce el modelo físico actual.
UPDATE centros_acopio SET tipo_centro='vertedero' WHERE nombre='Planta de Clasificación Este';

-- Rutas de recolección
INSERT INTO rutas(nombre,zona,frecuencia,horario) VALUES
('Ruta Centro','Ciudad Vieja - Centro','Diaria','06:00 a 10:00'),
('Ruta Buceo','Buceo - Malvín','Lunes, miércoles y viernes','07:00 a 11:00');

-- La cuadrilla de prueba (id 4, ex "Cuadrilla Prueba") sigue la Ruta Centro
INSERT INTO sigue(cuadrilla_id,ruta_id) SELECT 4,id FROM rutas WHERE nombre='Ruta Centro';

-- El camión de prueba realiza esa misma ruta
INSERT INTO realiza(matricula,ruta_id) SELECT 'ABC 1234',id FROM rutas WHERE nombre='Ruta Centro';

-- La Ruta Centro pasa por los dos primeros contenedores de ejemplo
INSERT INTO contiene(ruta_id,contenedor_id)
SELECT r.id,c.id FROM rutas r JOIN contenedores c ON c.id IN (1,2) WHERE r.nombre='Ruta Centro';

-- Cada incidencia de ejemplo queda asociada al contenedor correspondiente
INSERT INTO sobre(contenedor_id,incidencia_id) VALUES (1,1),(2,2),(3,3);

-- Calendario: la cuadrilla 4 y el camión ABC 1234 tienen asignada
-- la Ruta Centro para una fecha de ejemplo
INSERT INTO asignaciones(ruta_id,cuadrilla_id,matricula,fecha)
SELECT id,4,'ABC 1234','2026-09-10' FROM rutas WHERE nombre='Ruta Centro';

-- Recolección registrada por el recolector de prueba (id 4) sobre esa ruta
INSERT INTO recolecciones(contenedor_id,recolector_id,ruta_id,cuadrilla_id,fecha,volumen,simulado)
SELECT 1,4,id,4,'2026-09-10 08:15:00',1.50,0 FROM rutas WHERE nombre='Ruta Centro';

-- Mantenimiento preventivo del camión de prueba
INSERT INTO mantenimientos(descripcion,tipo_man,fecha_man,prox_man) VALUES
('Cambio de aceite y filtros','Preventivo','2026-08-01','2026-11-01');
INSERT INTO recibe(matricula,mantenimiento_id)
SELECT 'ABC 1234',id FROM mantenimientos WHERE descripcion='Cambio de aceite y filtros';

-- Reparación asociada a la incidencia de contenedor roto (id 3)
INSERT INTO reparaciones(tipo_reparacion,fecha_inicio,fecha_fin) VALUES
('Reparación de tapa','2026-08-15','2026-08-16');
INSERT INTO necesita(incidencia_id,reparacion_id)
SELECT 3,id FROM reparaciones WHERE tipo_reparacion='Reparación de tapa';

-- Reclamo de un vecino sobre una de sus incidencias abiertas
INSERT INTO reclamos(usuario_id,incidencia_id,descripcion,ubicacion,prioridad,estado) VALUES
(2,2,'El contenedor sigue desbordado tres días después del reporte.','Av Italia 1333','alta','abierto');

-- Recepción de residuos registrada por el operario de prueba
INSERT INTO recepciones(centro_id,residuo_id,operario_id,cantidad,simulado) VALUES
(1,1,3,25.50,0);
UPDATE centros_acopio SET capacidad_ocupada=25.50 WHERE nombre='Centro de Acopio Norte';
INSERT INTO historial_capacidad(centro_id,usuario_id,capacidad,capacidad_ocupada,estado)
SELECT id,3,100,25.50,'Operativo' FROM centros_acopio WHERE nombre='Centro de Acopio Norte';
