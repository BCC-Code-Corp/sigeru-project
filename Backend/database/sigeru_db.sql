-- ==================================================
--  SiGeRU - Modelo físico de datos (MySQL / MariaDB)
-- ==================================================
-- Actualizado para coincidir con el código PHP del sistema.
-- Respecto del dump original se agregaron:
--   * camiones.estado            (usada por camiones.php / incidencias.php)
--   * incidencias.cuadrilla_id   (usada al asignar logística)
--   * incidencias.matricula_camion
--   * incidencias.comentario_operario
--   * tabla centros_acopio       (CRUD de centros de acopio y maquinaria básica)
--   * incidencias.usuario_id     (vincula cada incidencia con el vecino que la reportó)
--   * tabla notificaciones       (avisos de estado al vecino + anuncios del administrador)
--   * incidencias.latitud / incidencias.longitud (autocompletado Google + mapa)
--   * camiones.cuadrilla_id       (asignación persistente de cuadrilla a camión)
-- Sin estas columnas, el código de asignación/resolución de
-- incidencias fallaba contra la base de datos original.
--
-- IMPORTANTE: este script es IDEMPOTENTE — se puede correr las veces que
-- hagan falta sobre la misma base sin dejar tablas "a medias". Al principio
-- borra (si existen) las tablas que va a recrear, en el orden correcto para
-- no romper las relaciones (FOREIGN KEY), y las vuelve a crear con los datos
-- de prueba. Si algo quedó inconsistente en tu base local (por ejemplo,
-- corriste una versión vieja de este script), simplemente volvé a importar
-- este archivo entero y va a quedar como nuevo.

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET FOREIGN_KEY_CHECKS = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------
-- Limpieza previa (orden inverso a las dependencias FK)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `notificaciones`;
DROP TABLE IF EXISTS `incidencias`;
DROP TABLE IF EXISTS `centros_acopio`;
DROP TABLE IF EXISTS `contenedores`;
DROP TABLE IF EXISTS `camiones`;
DROP TABLE IF EXISTS `usuarios`;

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
('Admin Prueba', 'admin@sigeru.uy', '10000001', '$2b$10$t3DloQv8mEmIeQb.Jzq5NOTQRHv33IGcipQZraC.6r3C34WlLvmgO', 'administrador'),
('Vecino Prueba', 'vecino@sigeru.uy', '10000007', '$2b$10$PWDGKWPX1Pp9wzlDwV5PAuKGubAQZBH180E1QBo74DiZJ0MZy97Sm', 'vecino'),
('Operario Prueba', 'operario@sigeru.uy', '10000004', '$2b$10$9eiyJONfrLI.v1FF/0Rx6.fipIrPAm/7x/J8n3/kzm5q24bGGi5wO', 'operario'),
('Cuadrilla Prueba', 'cuadrilla@sigeru.uy', '10000006', '$2b$10$s1sQ/IpEgrlVE5GbHVuu4OWk.hgS5svE9rGHxaYh8HEzvJYfyDvs6', 'cuadrilla');

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

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;
