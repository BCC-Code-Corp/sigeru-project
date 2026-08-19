-- ==================================================
--  SiGeRU - Modelo físico de datos (MySQL / MariaDB)
-- ==================================================
-- Actualizado para coincidir con el código PHP del sistema.
-- Respecto del dump original se agregaron:
--   * camiones.estado            (usada por camiones.php / incidencias.php)
--   * incidencias.cuadrilla_id   (usada al asignar logística)
--   * incidencias.matricula_camion
--   * incidencias.comentario_operario
-- Sin estas columnas, el código de asignación/resolución de
-- incidencias fallaba contra la base de datos original.

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

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
  PRIMARY KEY (`id`),
  UNIQUE KEY `matricula` (`matricula`)
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
  `estado_contenedor` varchar(100) NOT NULL,
  `tipo_basura` varchar(100) NOT NULL,
  `estado_incidencia` varchar(50) DEFAULT 'abierta',
  `cuadrilla_id` int(11) DEFAULT NULL,
  `matricula_camion` varchar(20) DEFAULT NULL,
  `comentario_operario` varchar(255) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_incidencia_cuadrilla` (`cuadrilla_id`),
  KEY `fk_incidencia_camion` (`matricula_camion`),
  CONSTRAINT `fk_incidencia_cuadrilla` FOREIGN KEY (`cuadrilla_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_incidencia_camion` FOREIGN KEY (`matricula_camion`) REFERENCES `camiones` (`matricula`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Datos de ejemplo
-- --------------------------------------------------------
INSERT INTO `usuarios` (`nombre`, `email`, `cedula`, `password`, `rol`) VALUES
('Admin Prueba', 'admin@sigeru.uy', '10000001', '$2y$10$r29schH8T3XmRUIoKF.CMeOcMu1q5HFV3BnEDbS.GIyGZ/FyvoreG', 'administrador'),
('Vecino Prueba', 'vecino@sigeru.uy', '10000007', '$2y$10$bdo3AYHb.LxZKujD17jYCefWZYmDjG4uqvhj3H7T6Zodpa03qjD4m', 'vecino'),
('Operario Prueba', 'operario@sigeru.uy', '10000004', '$2y$10$3zrkU7XOvQN0cmcw1bTdPutqhO3nKT0MA/CydMTxl9MJTwLrL940W', 'operario'),
('Cuadrilla Prueba', 'cuadrilla@sigeru.uy', '10000006', '$2y$10$tp7YeNl0.tQj3.iqvWqjZ.cOYRh9b1JtVAomt09uqJa1VoOHICWca', 'cuadrilla');

INSERT INTO `camiones` (`matricula`, `capacidad_carga`, `estado`) VALUES
('ABC 1234', 100.00, 'Disponible'),
('SBJ 3422', 73.00, 'Disponible');

INSERT INTO `contenedores` (`ubicacion`, `estado`) VALUES
('Av Italia 1413', 'lleno');

INSERT INTO `incidencias` (`ubicacion`, `estado_contenedor`, `tipo_basura`, `estado_incidencia`) VALUES
('Alejandro Gallinal 1675', 'Lleno', 'Reciclable', 'abierta'),
('Av Italia 1333', 'Desbordado', 'Normal / Orgánica', 'abierta'),
('Camino Cervando y Camino Carrasco', 'Roto / Dañado', 'Reciclable', 'abierta');

COMMIT;
