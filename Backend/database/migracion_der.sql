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
