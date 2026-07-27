-- SiGeRU DDL generado a partir del diagrama ER (Versión Final Sincronizada)
-- MySQL / MariaDB, InnoDB, UTF8MB4

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- BORRAR TABLAS (orden inverso para evitar violaciones de FKs al borrar)
DROP TABLE IF EXISTS ruta_contenedor;
DROP TABLE IF EXISTS cuadrilla_operarios;
DROP TABLE IF EXISTS mantenimientos;
DROP TABLE IF EXISTS reparaciones;
DROP TABLE IF EXISTS incidencias;
DROP TABLE IF EXISTS reclamos;
DROP TABLE IF EXISTS rutas;
DROP TABLE IF EXISTS cuadrillas;
DROP TABLE IF EXISTS camiones;
DROP TABLE IF EXISTS choferes;
DROP TABLE IF EXISTS recolectores;
DROP TABLE IF EXISTS operarios;
DROP TABLE IF EXISTS admin_municipal;
DROP TABLE IF EXISTS funcionarios;
DROP TABLE IF EXISTS vecinos;
DROP TABLE IF EXISTS usuarios;
DROP TABLE IF EXISTS contenedores;
DROP TABLE IF EXISTS centros;
DROP TABLE IF EXISTS maquinarias;
DROP TABLE IF EXISTS residuos;

-- 1. TABLA PADRE: usuarios
CREATE TABLE usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre_completo VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  telefono VARCHAR(30) NULL,
  cedula VARCHAR(50) NULL,
  rol ENUM('administrador','operario','cuadrilla','vecino','chofer','recolector','funcionario') NOT NULL DEFAULT 'vecino',
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. SUBTIPOS DE USUARIO
CREATE TABLE funcionarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL UNIQUE,
  fecha_contratacion DATE NULL,
  direccion VARCHAR(255) NULL,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE vecinos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL UNIQUE,
  direccion VARCHAR(255) NULL,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE admin_municipal (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL UNIQUE,
  nombre_municipio VARCHAR(150) NULL,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE operarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL UNIQUE,
  especialidad VARCHAR(100) NULL,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE choferes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL UNIQUE,
  licencia_conducir VARCHAR(100) NULL,
  turno VARCHAR(50) NULL,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE recolectores (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL UNIQUE,
  disponibilidad ENUM('disponible','no disponible') DEFAULT 'disponible',
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. RECURSOS Y OPERATIVA BÁSICA (CENTROS, CAMIONES, RESIDUOS)
CREATE TABLE centros (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre_centro VARCHAR(150) NOT NULL,
  tipo_centro VARCHAR(100) NULL,
  capacidad INT NULL,
  tipo_residuo VARCHAR(100) NULL,
  direccion VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE camiones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  matricula VARCHAR(20) NOT NULL UNIQUE,
  modelo VARCHAR(100) NULL,
  capacidad_carga DECIMAL(10,2) NULL,
  estado ENUM('Disponible','En Ruta','Mantenimiento') DEFAULT 'Disponible',
  disponibilidad BOOLEAN DEFAULT TRUE,
  ultimo_mantenimiento DATE NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE residuos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tipo_residuo VARCHAR(100) NOT NULL,
  descripcion TEXT NULL,
  tratamiento_recomendado VARCHAR(200) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 4. CUADRILLAS Y OPERARIOS ASIGNADOS
CREATE TABLE cuadrillas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre_cuadrilla VARCHAR(150) NOT NULL,
  disponibilidad ENUM('disponible','en servicio','no disponible') DEFAULT 'disponible',
  id_camion INT NULL,
  id_chofer INT NULL,
  FOREIGN KEY (id_camion) REFERENCES camiones(id) ON DELETE SET NULL,
  FOREIGN KEY (id_chofer) REFERENCES choferes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE cuadrilla_operarios (
  cuadrilla_id INT NOT NULL,
  operario_id INT NOT NULL,
  rol_en_cuadrilla VARCHAR(100) NULL,
  PRIMARY KEY (cuadrilla_id, operario_id),
  FOREIGN KEY (cuadrilla_id) REFERENCES cuadrillas(id) ON DELETE CASCADE,
  FOREIGN KEY (operario_id) REFERENCES operarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 5. RUTAS Y CONTENEDORES
CREATE TABLE rutas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre_ruta VARCHAR(150) NULL,
  zona VARCHAR(150) NULL,
  frecuencia VARCHAR(100) NULL,
  horario VARCHAR(100) NULL,
  id_cuadrilla INT NULL,
  activo BOOLEAN DEFAULT TRUE,
  FOREIGN KEY (id_cuadrilla) REFERENCES cuadrillas(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE contenedores (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ubicacion VARCHAR(255) NOT NULL,
  estado ENUM('lleno','vacio','mantenimiento','desbordado','roto','en servicio') DEFAULT 'vacio',
  tipo_contenedor VARCHAR(100) DEFAULT NULL,
  tipo_residuo VARCHAR(100) DEFAULT NULL,
  id_centro INT NULL,
  en_servicio BOOLEAN DEFAULT TRUE,
  clasifica VARCHAR(100) NULL,
  fecha_ultimo_servicio DATE NULL,
  FOREIGN KEY (id_centro) REFERENCES centros(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE ruta_contenedor (
  ruta_id INT NOT NULL,
  contenedor_id INT NOT NULL,
  orden INT DEFAULT 0,
  PRIMARY KEY (ruta_id, contenedor_id),
  FOREIGN KEY (ruta_id) REFERENCES rutas(id) ON DELETE CASCADE,
  FOREIGN KEY (contenedor_id) REFERENCES contenedores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 6. GESTIÓN DE INCIDENCIAS, RECLAMOS Y REPARACIONES
CREATE TABLE reclamos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  vecino_id INT NOT NULL,
  descripcion TEXT NOT NULL,
  prioridad ENUM('baja','media','alta') DEFAULT 'media',
  estado_reclamo ENUM('abierto','en proceso','resuelto','cerrado') DEFAULT 'abierto',
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (vecino_id) REFERENCES vecinos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE incidencias (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_contenedor INT NULL,
  reclamo_id INT NULL,
  ubicacion VARCHAR(255) NULL,
  estado_contenedor VARCHAR(50) NULL,
  tipo_incidencia VARCHAR(100) NULL,
  tipo_basura VARCHAR(50) NULL,
  estado_incidencia ENUM('abierta','en curso','incidencia solucionada') DEFAULT 'abierta',
  cuadrilla_id INT NULL,
  matricula_camion VARCHAR(20) NULL,
  comentario_operario TEXT NULL,
  fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  fecha_resolucion TIMESTAMP NULL,
  FOREIGN KEY (id_contenedor) REFERENCES contenedores(id) ON DELETE SET NULL,
  FOREIGN KEY (reclamo_id) REFERENCES reclamos(id) ON DELETE SET NULL,
  FOREIGN KEY (cuadrilla_id) REFERENCES cuadrillas(id) ON DELETE SET NULL,
  FOREIGN KEY (matricula_camion) REFERENCES camiones(matricula) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE reparaciones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_contenedor INT NULL,
  tipo_reparacion VARCHAR(150) NULL,
  fecha_inicio DATE NULL,
  fecha_fin DATE NULL,
  estado_reparacion ENUM('pendiente','en curso','finalizada') DEFAULT 'pendiente',
  necesita_pieza BOOLEAN DEFAULT FALSE,
  descripcion TEXT NULL,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_contenedor) REFERENCES contenedores(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 7. MAQUINARIAS Y MANTENIMIENTOS
CREATE TABLE maquinarias (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre_maquina VARCHAR(150) NOT NULL,
  id_centro INT NULL,
  estado ENUM('operativo','fuera servicio','mantenimiento') DEFAULT 'operativo',
  id_maquinaria_padre INT NULL,
  FOREIGN KEY (id_centro) REFERENCES centros(id) ON DELETE SET NULL,
  FOREIGN KEY (id_maquinaria_padre) REFERENCES maquinarias(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE mantenimientos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tipo_mantenimiento VARCHAR(100) NULL,
  descripcion TEXT NULL,
  fecha_mantenimiento DATE NULL,
  prox_mantenimiento DATE NULL,
  id_maquinaria INT NULL,
  id_camion INT NULL,
  asignado_por INT NULL,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_maquinaria) REFERENCES maquinarias(id) ON DELETE SET NULL,
  FOREIGN KEY (id_camion) REFERENCES camiones(id) ON DELETE SET NULL,
  FOREIGN KEY (asignado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Índices útiles para optimización de consultas
CREATE INDEX idx_contenedor_tipo ON contenedores(tipo_residuo);
CREATE INDEX idx_ruta_zona ON rutas(zona);
CREATE INDEX idx_incidencia_estado ON incidencias(estado_incidencia);

SET FOREIGN_KEY_CHECKS = 1;
