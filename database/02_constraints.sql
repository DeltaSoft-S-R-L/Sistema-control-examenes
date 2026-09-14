-- 02_constraints.sql
-- Sistema de Control de Ingreso a Exámenes Masivos
-- Claves primarias, UNIQUE, índices y claves foráneas.
-- Los CHECK definidos dentro de CREATE TABLE están en 01_schema.sql.

ALTER TABLE ONLY public.ambiente
    ADD CONSTRAINT pk_ambiente PRIMARY KEY (id_ambiente);

ALTER TABLE ONLY public.asignacion_ambiente
    ADD CONSTRAINT pk_asignacion_ambiente PRIMARY KEY (id_asignacion_ambiente);

ALTER TABLE ONLY public.asignatura
    ADD CONSTRAINT pk_asignatura PRIMARY KEY (id_asignatura);

ALTER TABLE ONLY public.auditoria
    ADD CONSTRAINT pk_auditoria PRIMARY KEY (id_auditoria);

ALTER TABLE ONLY public.estudiante
    ADD CONSTRAINT pk_estudiante PRIMARY KEY (id_estudiante);

ALTER TABLE ONLY public.examen
    ADD CONSTRAINT pk_examen PRIMARY KEY (id_examen);

ALTER TABLE ONLY public.expulsion
    ADD CONSTRAINT pk_expulsion PRIMARY KEY (id_expulsion);

ALTER TABLE ONLY public.habilitacion
    ADD CONSTRAINT pk_habilitacion PRIMARY KEY (id_habilitacion);

ALTER TABLE ONLY public.incidencia
    ADD CONSTRAINT pk_incidencia PRIMARY KEY (id_incidencia);

ALTER TABLE ONLY public.ingreso
    ADD CONSTRAINT pk_ingreso PRIMARY KEY (id_ingreso);

ALTER TABLE ONLY public.intento_ingreso
    ADD CONSTRAINT pk_intento_ingreso PRIMARY KEY (id_intento);

ALTER TABLE ONLY public.permiso
    ADD CONSTRAINT pk_permiso PRIMARY KEY (id_permiso);

ALTER TABLE ONLY public.regla_examen
    ADD CONSTRAINT pk_regla_examen PRIMARY KEY (id_regla_examen);

ALTER TABLE ONLY public.regla_individual
    ADD CONSTRAINT pk_regla_individual PRIMARY KEY (id_regla_individual);

ALTER TABLE ONLY public.rol
    ADD CONSTRAINT pk_rol PRIMARY KEY (id_rol);

ALTER TABLE ONLY public.rol_permiso
    ADD CONSTRAINT pk_rol_permiso PRIMARY KEY (id_rol, id_permiso);

ALTER TABLE ONLY public.usuario
    ADD CONSTRAINT pk_usuario PRIMARY KEY (id_usuario);

ALTER TABLE ONLY public.ambiente
    ADD CONSTRAINT uq_ambiente_codigo UNIQUE (codigo);

ALTER TABLE ONLY public.asignacion_ambiente
    ADD CONSTRAINT uq_asignacion_ambiente_examen UNIQUE (id_asignacion_ambiente, id_examen);

ALTER TABLE ONLY public.asignacion_ambiente
    ADD CONSTRAINT uq_asignacion_examen_ambiente UNIQUE (id_examen, id_ambiente);

ALTER TABLE ONLY public.asignatura
    ADD CONSTRAINT uq_asignatura_codigo UNIQUE (codigo);

ALTER TABLE ONLY public.estudiante
    ADD CONSTRAINT uq_estudiante_ci UNIQUE (ci);

ALTER TABLE ONLY public.estudiante
    ADD CONSTRAINT uq_estudiante_codigo UNIQUE (codigo_universitario);

ALTER TABLE ONLY public.expulsion
    ADD CONSTRAINT uq_expulsion_ingreso UNIQUE (id_ingreso);

ALTER TABLE ONLY public.habilitacion
    ADD CONSTRAINT uq_habilitacion_estudiante_examen UNIQUE (id_estudiante, id_examen);

ALTER TABLE ONLY public.habilitacion
    ADD CONSTRAINT uq_habilitacion_id_examen UNIQUE (id_habilitacion, id_examen);

ALTER TABLE ONLY public.ingreso
    ADD CONSTRAINT uq_ingreso_habilitacion UNIQUE (id_habilitacion);

ALTER TABLE ONLY public.permiso
    ADD CONSTRAINT uq_permiso_nombre UNIQUE (nombre);

ALTER TABLE ONLY public.rol
    ADD CONSTRAINT uq_rol_nombre UNIQUE (nombre);

ALTER TABLE ONLY public.usuario
    ADD CONSTRAINT uq_usuario_correo UNIQUE (correo);

ALTER TABLE ONLY public.usuario
    ADD CONSTRAINT uq_usuario_username UNIQUE (username);

CREATE INDEX idx_ambiente_estado ON public.ambiente USING btree (estado);

CREATE INDEX idx_asignacion_ambiente_examen ON public.asignacion_ambiente USING btree (id_examen, id_ambiente);

CREATE INDEX idx_estudiante_estado ON public.estudiante USING btree (estado);

CREATE INDEX idx_examen_estado ON public.examen USING btree (estado);

CREATE INDEX idx_examen_fecha_hora ON public.examen USING btree (fecha, hora_inicio);

CREATE INDEX idx_habilitacion_estado ON public.habilitacion USING btree (estado);

CREATE INDEX idx_habilitacion_examen ON public.habilitacion USING btree (id_examen);

CREATE INDEX idx_incidencia_examen ON public.incidencia USING btree (id_examen);

CREATE INDEX idx_incidencia_tipo ON public.incidencia USING btree (tipo);

CREATE INDEX idx_regla_examen_estado ON public.regla_examen USING btree (estado);

CREATE INDEX idx_regla_examen_examen ON public.regla_examen USING btree (id_examen);

CREATE INDEX idx_regla_individual_estado ON public.regla_individual USING btree (estado);

CREATE INDEX idx_regla_individual_habilitacion ON public.regla_individual USING btree (id_habilitacion);

CREATE INDEX idx_usuario_estado ON public.usuario USING btree (estado);

ALTER TABLE ONLY public.asignacion_ambiente
    ADD CONSTRAINT fk_asignacion_ambiente FOREIGN KEY (id_ambiente) REFERENCES public.ambiente(id_ambiente);

ALTER TABLE ONLY public.asignacion_ambiente
    ADD CONSTRAINT fk_asignacion_examen FOREIGN KEY (id_examen) REFERENCES public.examen(id_examen);

ALTER TABLE ONLY public.auditoria
    ADD CONSTRAINT fk_auditoria_usuario FOREIGN KEY (id_usuario) REFERENCES public.usuario(id_usuario);

ALTER TABLE ONLY public.examen
    ADD CONSTRAINT fk_examen_asignatura FOREIGN KEY (id_asignatura) REFERENCES public.asignatura(id_asignatura);

ALTER TABLE ONLY public.expulsion
    ADD CONSTRAINT fk_expulsion_ingreso FOREIGN KEY (id_ingreso) REFERENCES public.ingreso(id_ingreso);

ALTER TABLE ONLY public.expulsion
    ADD CONSTRAINT fk_expulsion_usuario FOREIGN KEY (id_usuario) REFERENCES public.usuario(id_usuario);

ALTER TABLE ONLY public.habilitacion
    ADD CONSTRAINT fk_habilitacion_estudiante FOREIGN KEY (id_estudiante) REFERENCES public.estudiante(id_estudiante);

ALTER TABLE ONLY public.habilitacion
    ADD CONSTRAINT fk_habilitacion_examen FOREIGN KEY (id_examen) REFERENCES public.examen(id_examen);

ALTER TABLE ONLY public.incidencia
    ADD CONSTRAINT fk_incidencia_estudiante FOREIGN KEY (id_estudiante) REFERENCES public.estudiante(id_estudiante);

ALTER TABLE ONLY public.incidencia
    ADD CONSTRAINT fk_incidencia_examen FOREIGN KEY (id_examen) REFERENCES public.examen(id_examen);

ALTER TABLE ONLY public.incidencia
    ADD CONSTRAINT fk_incidencia_usuario FOREIGN KEY (id_usuario) REFERENCES public.usuario(id_usuario);

ALTER TABLE ONLY public.ingreso
    ADD CONSTRAINT fk_ingreso_asignacion_ambiente FOREIGN KEY (id_asignacion_ambiente, id_examen) REFERENCES public.asignacion_ambiente(id_asignacion_ambiente, id_examen);

ALTER TABLE ONLY public.ingreso
    ADD CONSTRAINT fk_ingreso_habilitacion_examen FOREIGN KEY (id_habilitacion, id_examen) REFERENCES public.habilitacion(id_habilitacion, id_examen);

ALTER TABLE ONLY public.ingreso
    ADD CONSTRAINT fk_ingreso_usuario FOREIGN KEY (id_usuario) REFERENCES public.usuario(id_usuario);

ALTER TABLE ONLY public.intento_ingreso
    ADD CONSTRAINT fk_intento_estudiante FOREIGN KEY (id_estudiante) REFERENCES public.estudiante(id_estudiante);

ALTER TABLE ONLY public.intento_ingreso
    ADD CONSTRAINT fk_intento_examen FOREIGN KEY (id_examen) REFERENCES public.examen(id_examen);

ALTER TABLE ONLY public.intento_ingreso
    ADD CONSTRAINT fk_intento_ingreso_asignacion_ambiente FOREIGN KEY (id_asignacion_ambiente, id_examen) REFERENCES public.asignacion_ambiente(id_asignacion_ambiente, id_examen);

ALTER TABLE ONLY public.intento_ingreso
    ADD CONSTRAINT fk_intento_usuario FOREIGN KEY (id_usuario) REFERENCES public.usuario(id_usuario);

ALTER TABLE ONLY public.regla_examen
    ADD CONSTRAINT fk_regla_examen_examen FOREIGN KEY (id_examen) REFERENCES public.examen(id_examen);

ALTER TABLE ONLY public.regla_individual
    ADD CONSTRAINT fk_regla_individual_habilitacion FOREIGN KEY (id_habilitacion) REFERENCES public.habilitacion(id_habilitacion);

ALTER TABLE ONLY public.rol_permiso
    ADD CONSTRAINT fk_rol_permiso_permiso FOREIGN KEY (id_permiso) REFERENCES public.permiso(id_permiso) ON DELETE CASCADE;

ALTER TABLE ONLY public.rol_permiso
    ADD CONSTRAINT fk_rol_permiso_rol FOREIGN KEY (id_rol) REFERENCES public.rol(id_rol) ON DELETE CASCADE;

ALTER TABLE ONLY public.usuario
    ADD CONSTRAINT fk_usuario_rol FOREIGN KEY (id_rol) REFERENCES public.rol(id_rol);

