-- 03_triggers.sql
-- Sistema de Control de Ingreso a Exámenes Masivos
-- Funciones y triggers de reglas de negocio/integridad.

SET TIME ZONE 'America/La_Paz';

CREATE FUNCTION public.fn_fecha_hora_expulsion() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN

    IF NEW.fecha_hora IS NULL THEN
        NEW.fecha_hora := CURRENT_TIMESTAMP;
    END IF;

    RETURN NEW;
END;
$$;


CREATE FUNCTION public.fn_fecha_hora_incidencia() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN

    IF NEW.fecha_hora IS NULL THEN
        NEW.fecha_hora := CURRENT_TIMESTAMP;
    END IF;

    RETURN NEW;
END;
$$;


CREATE FUNCTION public.fn_fecha_hora_ingreso() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    IF NEW.fecha_hora IS NULL THEN
        NEW.fecha_hora := CURRENT_TIMESTAMP;
    END IF;

    RETURN NEW;
END;
$$;


CREATE FUNCTION public.fn_fecha_hora_intento() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    IF NEW.fecha_hora IS NULL THEN
        NEW.fecha_hora := CURRENT_TIMESTAMP;
    END IF;

    RETURN NEW;
END;
$$;


CREATE FUNCTION public.fn_proteger_ingreso() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN

    IF TG_OP = 'DELETE' THEN
        RAISE EXCEPTION
            'Los registros de ingreso no pueden eliminarse.';
    END IF;

    IF TG_OP = 'UPDATE' THEN
        RAISE EXCEPTION
            'Los registros de ingreso no pueden modificarse.';
    END IF;

    RETURN NEW;
END;
$$;


CREATE FUNCTION public.fn_proteger_intento() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN

    IF TG_OP = 'DELETE' THEN
        RAISE EXCEPTION
            'Los intentos de ingreso no pueden eliminarse.';
    END IF;

    IF TG_OP = 'UPDATE' THEN
        RAISE EXCEPTION
            'Los intentos de ingreso no pueden modificarse.';
    END IF;

    RETURN NEW;
END;
$$;


CREATE FUNCTION public.fn_validar_expulsion() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN

    IF NOT EXISTS (
        SELECT 1
        FROM ingreso
        WHERE id_ingreso = NEW.id_ingreso
    ) THEN

        RAISE EXCEPTION
            'No se puede registrar una expulsión sin un ingreso previo.';
    END IF;

    RETURN NEW;
END;
$$;


CREATE FUNCTION public.fn_validar_ingreso() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_estado_habilitacion VARCHAR(20);
    v_estado_estudiante VARCHAR(20);
    v_estado_examen VARCHAR(20);
    v_estado_ambiente VARCHAR(20);
BEGIN

    -- Verificar habilitación
    SELECT estado
    INTO v_estado_habilitacion
    FROM habilitacion
    WHERE id_habilitacion = NEW.id_habilitacion
      AND id_examen = NEW.id_examen;

    IF NOT FOUND THEN
        RAISE EXCEPTION
            'La habilitación no corresponde al examen indicado.';
    END IF;

    IF v_estado_habilitacion <> 'HABILITADO' THEN
        RAISE EXCEPTION
            'El estudiante no está habilitado para este examen.';
    END IF;


    -- Verificar estado del estudiante
    SELECT e.estado
    INTO v_estado_estudiante
    FROM estudiante e
    INNER JOIN habilitacion h
        ON h.id_estudiante = e.id_estudiante
    WHERE h.id_habilitacion = NEW.id_habilitacion;

    IF v_estado_estudiante <> 'ACTIVO' THEN
        RAISE EXCEPTION
            'El estudiante está INACTIVO.';
    END IF;


    -- Verificar estado del examen
    SELECT estado
    INTO v_estado_examen
    FROM examen
    WHERE id_examen = NEW.id_examen;

    IF v_estado_examen NOT IN ('PROGRAMADO', 'EN_CURSO') THEN
        RAISE EXCEPTION
            'El examen no permite registrar ingresos en su estado actual.';
    END IF;


    -- Verificar ambiente
    SELECT a.estado
    INTO v_estado_ambiente
    FROM asignacion_ambiente aa
    INNER JOIN ambiente a
        ON a.id_ambiente = aa.id_ambiente
    WHERE aa.id_asignacion_ambiente = NEW.id_asignacion_ambiente
      AND aa.id_examen = NEW.id_examen;

    IF NOT FOUND THEN
        RAISE EXCEPTION
            'El ambiente no está asignado al examen indicado.';
    END IF;

    IF v_estado_ambiente <> 'DISPONIBLE' THEN
        RAISE EXCEPTION
            'El ambiente no está disponible.';
    END IF;


    RETURN NEW;
END;
$$;


CREATE FUNCTION public.fn_validar_solapamiento_ambiente() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_inicio_nuevo TIMESTAMP;
    v_fin_nuevo TIMESTAMP;
BEGIN

    SELECT
        (fecha + hora_inicio),
        (fecha + hora_inicio
         + (duracion_minutos * INTERVAL '1 minute'))
    INTO v_inicio_nuevo, v_fin_nuevo
    FROM examen
    WHERE id_examen = NEW.id_examen;


    IF EXISTS (
        SELECT 1
        FROM asignacion_ambiente aa
        INNER JOIN examen e
            ON e.id_examen = aa.id_examen
        WHERE aa.id_ambiente = NEW.id_ambiente
          AND aa.id_asignacion_ambiente <> NEW.id_asignacion_ambiente

          AND (e.fecha + e.hora_inicio) < v_fin_nuevo

          AND (
              e.fecha + e.hora_inicio
              + (e.duracion_minutos * INTERVAL '1 minute')
          ) > v_inicio_nuevo
    ) THEN

        RAISE EXCEPTION
            'El ambiente ya está asignado a otro examen en un horario que se superpone.';
    END IF;

    RETURN NEW;
END;
$$;


CREATE TRIGGER trg_fecha_hora_expulsion BEFORE INSERT ON public.expulsion FOR EACH ROW EXECUTE FUNCTION public.fn_fecha_hora_expulsion();


CREATE TRIGGER trg_fecha_hora_incidencia BEFORE INSERT ON public.incidencia FOR EACH ROW EXECUTE FUNCTION public.fn_fecha_hora_incidencia();


CREATE TRIGGER trg_fecha_hora_ingreso BEFORE INSERT ON public.ingreso FOR EACH ROW EXECUTE FUNCTION public.fn_fecha_hora_ingreso();


CREATE TRIGGER trg_fecha_hora_intento BEFORE INSERT ON public.intento_ingreso FOR EACH ROW EXECUTE FUNCTION public.fn_fecha_hora_intento();


CREATE TRIGGER trg_proteger_ingreso BEFORE DELETE OR UPDATE ON public.ingreso FOR EACH ROW EXECUTE FUNCTION public.fn_proteger_ingreso();


CREATE TRIGGER trg_proteger_intento BEFORE DELETE OR UPDATE ON public.intento_ingreso FOR EACH ROW EXECUTE FUNCTION public.fn_proteger_intento();


CREATE TRIGGER trg_validar_expulsion BEFORE INSERT ON public.expulsion FOR EACH ROW EXECUTE FUNCTION public.fn_validar_expulsion();


CREATE TRIGGER trg_validar_ingreso BEFORE INSERT ON public.ingreso FOR EACH ROW EXECUTE FUNCTION public.fn_validar_ingreso();


CREATE TRIGGER trg_validar_solapamiento_ambiente BEFORE INSERT OR UPDATE ON public.asignacion_ambiente FOR EACH ROW EXECUTE FUNCTION public.fn_validar_solapamiento_ambiente();

