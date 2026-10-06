-- 01_schema.sql
-- Sistema de Control de Ingreso a Exámenes Masivos
-- PostgreSQL 16
-- Estructura de tablas e identidades.
-- Fuente: control_examenes_definitivo.sql

SET TIME ZONE 'America/La_Paz';

CREATE TABLE public.ambiente (
    id_ambiente bigint NOT NULL,
    codigo character varying(30) NOT NULL,
    nombre character varying(100) NOT NULL,
    ubicacion character varying(150),
    capacidad integer NOT NULL,
    estado character varying(20) NOT NULL,
    CONSTRAINT ck_ambiente_capacidad CHECK ((capacidad > 0)),
    CONSTRAINT ck_ambiente_estado CHECK (((estado)::text = ANY ((ARRAY['DISPONIBLE'::character varying, 'NO_DISPONIBLE'::character varying])::text[])))
);


CREATE TABLE public.asignacion_ambiente (
    id_asignacion_ambiente bigint NOT NULL,
    id_examen bigint NOT NULL,
    id_ambiente bigint NOT NULL
);


CREATE TABLE public.asignatura (
    id_asignatura bigint NOT NULL,
    codigo character varying(30) NOT NULL,
    nombre character varying(150) NOT NULL,
    descripcion text
);


CREATE TABLE public.auditoria (
    id_auditoria bigint NOT NULL,
    id_usuario bigint NOT NULL,
    accion character varying(100) NOT NULL,
    entidad character varying(100) NOT NULL,
    id_registro bigint,
    fecha_hora timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    resultado character varying(20),
    descripcion text
);

CREATE TABLE public.facultad (
    id_facultad bigint NOT NULL,
    codigo character varying(30) NOT NULL,
    nombre character varying(150) NOT NULL
);

CREATE TABLE public.carrera (
    id_carrera bigint NOT NULL,
    id_facultad bigint NOT NULL,
    codigo character varying(30) NOT NULL,
    nombre character varying(150) NOT NULL
);

CREATE TABLE public.carrera_asignatura (
    id_carrera bigint NOT NULL,
    id_asignatura bigint NOT NULL
);

CREATE TABLE public.estudiante (
    id_estudiante bigint NOT NULL,
    ci character varying(20) NOT NULL,
    nombre character varying(100) NOT NULL,
    apellido character varying(100) NOT NULL,
    codigo_universitario character varying(50) NOT NULL,
    id_carrera bigint NOT NULL,
    correo character varying(150),
    estado character varying(20) NOT NULL,
    CONSTRAINT ck_estudiante_estado CHECK (((estado)::text = ANY ((ARRAY['ACTIVO'::character varying, 'INACTIVO'::character varying])::text[])))
);

CREATE TABLE public.estudiante_asignatura (
    id_estudiante bigint NOT NULL,
    id_asignatura bigint NOT NULL
);

CREATE TABLE public.examen (
    id_examen bigint NOT NULL,
    id_asignatura bigint NOT NULL,
    nombre character varying(150) NOT NULL,
    fecha date NOT NULL,
    hora_inicio time without time zone NOT NULL,
    duracion_minutos integer NOT NULL,
    descripcion text,
    estado character varying(20) NOT NULL,
    CONSTRAINT ck_examen_duracion CHECK ((duracion_minutos > 0)),
    CONSTRAINT ck_examen_estado CHECK (((estado)::text = ANY ((ARRAY['PROGRAMADO'::character varying, 'EN_CURSO'::character varying, 'FINALIZADO'::character varying, 'CANCELADO'::character varying])::text[])))
);


CREATE TABLE public.expulsion (
    id_expulsion bigint NOT NULL,
    id_ingreso bigint NOT NULL,
    id_usuario bigint NOT NULL,
    motivo text NOT NULL,
    fecha_hora timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


CREATE TABLE public.habilitacion (
    id_habilitacion bigint NOT NULL,
    id_estudiante bigint NOT NULL,
    id_examen bigint NOT NULL,
    estado character varying(20) NOT NULL,
    motivo text,
    CONSTRAINT ck_habilitacion_estado CHECK (((estado)::text = ANY ((ARRAY['HABILITADO'::character varying, 'NO_HABILITADO'::character varying])::text[]))),
    CONSTRAINT ck_habilitacion_motivo CHECK ((((estado)::text = 'HABILITADO'::text) OR (motivo IS NOT NULL)))
);


CREATE TABLE public.incidencia (
    id_incidencia bigint NOT NULL,
    id_estudiante bigint,
    id_examen bigint NOT NULL,
    id_usuario bigint NOT NULL,
    tipo character varying(50) NOT NULL,
    descripcion text NOT NULL,
    fecha_hora timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


CREATE TABLE public.ingreso (
    id_ingreso bigint NOT NULL,
    id_examen bigint NOT NULL,
    id_habilitacion bigint NOT NULL,
    id_asignacion_ambiente bigint NOT NULL,
    id_usuario bigint NOT NULL,
    fecha_hora timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


CREATE TABLE public.intento_ingreso (
    id_intento bigint NOT NULL,
    id_estudiante bigint,
    id_examen bigint NOT NULL,
    id_asignacion_ambiente bigint NOT NULL,
    id_usuario bigint NOT NULL,
    fecha_hora timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    motivo text NOT NULL
);


CREATE TABLE public.permiso (
    id_permiso bigint NOT NULL,
    nombre character varying(100) NOT NULL,
    descripcion character varying(255)
);


CREATE TABLE public.regla_examen (
    id_regla_examen bigint NOT NULL,
    id_examen bigint NOT NULL,
    descripcion text NOT NULL,
    estado character varying(20) NOT NULL,
    CONSTRAINT ck_regla_examen_estado CHECK (((estado)::text = ANY ((ARRAY['ACTIVA'::character varying, 'INACTIVA'::character varying])::text[])))
);


CREATE TABLE public.regla_individual (
    id_regla_individual bigint NOT NULL,
    id_habilitacion bigint NOT NULL,
    descripcion text NOT NULL,
    estado character varying(20) NOT NULL,
    CONSTRAINT ck_regla_individual_estado CHECK (((estado)::text = ANY ((ARRAY['ACTIVA'::character varying, 'INACTIVA'::character varying])::text[])))
);


CREATE TABLE public.rol (
    id_rol bigint NOT NULL,
    nombre character varying(50) NOT NULL,
    descripcion character varying(255)
);


CREATE TABLE public.rol_permiso (
    id_rol bigint NOT NULL,
    id_permiso bigint NOT NULL
);


CREATE TABLE public.usuario (
    id_usuario bigint NOT NULL,
    nombre character varying(100) NOT NULL,
    apellido character varying(100) NOT NULL,
    correo character varying(150) NOT NULL,
    username character varying(50) NOT NULL,
    password_hash character varying(255) NOT NULL,
    id_rol bigint NOT NULL,
    estado character varying(20) NOT NULL,
    CONSTRAINT ck_usuario_estado CHECK (((estado)::text = ANY ((ARRAY['ACTIVO'::character varying, 'REVOCADO'::character varying])::text[])))
);

CREATE TABLE public.personal_access_tokens (
    id bigint NOT NULL,
    tokenable_type character varying(255) NOT NULL,
    tokenable_id bigint NOT NULL,
    name text NOT NULL,
    token character varying(64) NOT NULL,
    abilities text,
    last_used_at timestamp without time zone,
    expires_at timestamp without time zone,
    created_at timestamp without time zone,
    updated_at timestamp without time zone
);

ALTER TABLE public.ambiente ALTER COLUMN id_ambiente ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.ambiente_id_ambiente_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);

ALTER TABLE public.asignacion_ambiente ALTER COLUMN id_asignacion_ambiente ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.asignacion_ambiente_id_asignacion_ambiente_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);

ALTER TABLE public.asignatura ALTER COLUMN id_asignatura ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.asignatura_id_asignatura_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);

ALTER TABLE public.auditoria ALTER COLUMN id_auditoria ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.auditoria_id_auditoria_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);

ALTER TABLE public.facultad ALTER COLUMN id_facultad ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.facultad_id_facultad_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);

ALTER TABLE public.carrera ALTER COLUMN id_carrera ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.carrera_id_carrera_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);

ALTER TABLE public.estudiante ALTER COLUMN id_estudiante ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.estudiante_id_estudiante_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);

ALTER TABLE public.examen ALTER COLUMN id_examen ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.examen_id_examen_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);

ALTER TABLE public.expulsion ALTER COLUMN id_expulsion ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.expulsion_id_expulsion_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);

ALTER TABLE public.habilitacion ALTER COLUMN id_habilitacion ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.habilitacion_id_habilitacion_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);

ALTER TABLE public.incidencia ALTER COLUMN id_incidencia ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.incidencia_id_incidencia_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);

ALTER TABLE public.ingreso ALTER COLUMN id_ingreso ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.ingreso_id_ingreso_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);

ALTER TABLE public.intento_ingreso ALTER COLUMN id_intento ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.intento_ingreso_id_intento_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);

ALTER TABLE public.permiso ALTER COLUMN id_permiso ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.permiso_id_permiso_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);

ALTER TABLE public.regla_examen ALTER COLUMN id_regla_examen ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.regla_examen_id_regla_examen_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);

ALTER TABLE public.regla_individual ALTER COLUMN id_regla_individual ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.regla_individual_id_regla_individual_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);

ALTER TABLE public.rol ALTER COLUMN id_rol ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.rol_id_rol_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);

ALTER TABLE public.usuario ALTER COLUMN id_usuario ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.usuario_id_usuario_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);

ALTER TABLE public.personal_access_tokens ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.personal_access_tokens_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);
