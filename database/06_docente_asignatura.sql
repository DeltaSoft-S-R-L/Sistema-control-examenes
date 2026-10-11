
-- Asignación de materias a docentes
-- Actualización incremental: no modifica las tablas existentes.

BEGIN;

CREATE TABLE IF NOT EXISTS public.docente_asignatura (
    id_usuario BIGINT NOT NULL,
    id_carrera BIGINT NOT NULL,
    id_asignatura BIGINT NOT NULL,
    fecha_asignacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT pk_docente_asignatura
        PRIMARY KEY (id_usuario, id_carrera, id_asignatura),

    CONSTRAINT fk_docente_asignatura_usuario
        FOREIGN KEY (id_usuario)
        REFERENCES public.usuario(id_usuario),

    CONSTRAINT fk_docente_asignatura_carrera_materia
        FOREIGN KEY (id_carrera, id_asignatura)
        REFERENCES public.carrera_asignatura(id_carrera, id_asignatura)
);

CREATE INDEX IF NOT EXISTS idx_docente_asignatura_carrera
    ON public.docente_asignatura(id_carrera);

CREATE INDEX IF NOT EXISTS idx_docente_asignatura_materia
    ON public.docente_asignatura(id_asignatura);

COMMIT;
