-- 05_test.sql
-- Pruebas de integridad y reglas de negocio.
-- Ejecutar DESPUÉS de 01_schema.sql, 02_constraints.sql, 03_triggers.sql y 04_seed.sql.
-- Estas pruebas NO modifican la estructura. Algunas pruebas negativas provocan
-- excepciones intencionalmente; ejecutar cada bloque por separado en pgAdmin
-- si se desea observar el mensaje exacto.

SET TIME ZONE 'America/La_Paz';

-- ============================================================
-- 1. RESUMEN DE ESTRUCTURA
-- ============================================================
SELECT
    (SELECT count(*) FROM information_schema.tables
     WHERE table_schema='public' AND table_type='BASE TABLE') AS tablas,
    (SELECT count(*) FROM information_schema.triggers
     WHERE trigger_schema='public') AS triggers,
    (SELECT count(*) FROM rol) AS roles,
    (SELECT count(*) FROM permiso) AS permisos,
    (SELECT count(*) FROM rol_permiso) AS relaciones_rol_permiso;

-- ============================================================
-- 2. VERIFICAR CLAVES FORÁNEAS
-- ============================================================
SELECT conname AS fk
FROM pg_constraint
WHERE contype = 'f'
  AND connamespace = 'public'::regnamespace
ORDER BY conname;

-- ============================================================
-- 3. VERIFICAR PK / UNIQUE
-- ============================================================
SELECT conname AS restriccion
FROM pg_constraint
WHERE contype IN ('p','u')
  AND connamespace = 'public'::regnamespace
ORDER BY conname;

-- ============================================================
-- 4. VERIFICAR TRIGGERS
-- ============================================================
SELECT
    event_object_table AS tabla,
    trigger_name
FROM information_schema.triggers
WHERE trigger_schema = 'public'
ORDER BY event_object_table, trigger_name;

-- ============================================================
-- 5. PRUEBA POSITIVA: ingreso válido
--    Usa el estudiante EST001, habilitación 1 y ambiente 1.
--    NO ejecutar si ya existe ingreso para la habilitación 1.
-- ============================================================
-- INSERT INTO ingreso
--     (id_examen, id_habilitacion, id_asignacion_ambiente, id_usuario)
-- VALUES
--     (1, 1, 1, 1);

-- ============================================================
-- 6. PRUEBA NEGATIVA: estudiante NO_HABILITADO
--    Debe fallar con:
--    "El estudiante no está habilitado para este examen."
-- ============================================================
-- INSERT INTO ingreso
--     (id_examen, id_habilitacion, id_asignacion_ambiente, id_usuario)
-- VALUES
--     (1, 2, 2, 1);

-- ============================================================
-- 7. PRUEBA NEGATIVA: ingreso duplicado
--    Debe fallar por uq_ingreso_habilitacion.
-- ============================================================
-- INSERT INTO ingreso
--     (id_examen, id_habilitacion, id_asignacion_ambiente, id_usuario)
-- VALUES
--     (1, 1, 2, 1);

-- ============================================================
-- 8. PRUEBA NEGATIVA: modificar ingreso
--    Debe fallar por trg_proteger_ingreso.
-- ============================================================
-- UPDATE ingreso
-- SET id_usuario = 1
-- WHERE id_ingreso = 1;

-- ============================================================
-- 9. PRUEBA NEGATIVA: eliminar ingreso
--    Debe fallar por trg_proteger_ingreso.
-- ============================================================
-- DELETE FROM ingreso WHERE id_ingreso = 1;

-- ============================================================
-- 10. PRUEBA NEGATIVA: expulsión sin ingreso previo
--     Debe fallar por trg_validar_expulsion.
-- ============================================================
-- INSERT INTO expulsion (id_ingreso, id_usuario, motivo)
-- VALUES (999999, 1, 'Prueba');

-- ============================================================
-- 11. PRUEBA NEGATIVA: intento de modificar/eliminar intento
-- ============================================================
-- UPDATE intento_ingreso SET motivo = 'Cambio'
-- WHERE id_intento = 1;

-- DELETE FROM intento_ingreso WHERE id_intento = 1;

-- ============================================================
-- 12. CONSULTAS DE CONTROL
-- ============================================================
SELECT * FROM estudiante ORDER BY id_estudiante;
SELECT * FROM examen ORDER BY id_examen;
SELECT * FROM ambiente ORDER BY id_ambiente;
SELECT * FROM habilitacion ORDER BY id_habilitacion;
SELECT * FROM ingreso ORDER BY id_ingreso;
SELECT * FROM intento_ingreso ORDER BY id_intento;
SELECT * FROM expulsion ORDER BY id_expulsion;
SELECT * FROM incidencia ORDER BY id_incidencia;
SELECT * FROM auditoria ORDER BY id_auditoria;
