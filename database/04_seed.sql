-- 04_seed.sql
-- Sistema de Control de Ingreso a Exámenes Masivos
-- Datos base y datos de prueba actuales del respaldo definitivo.
-- IMPORTANTE: antes de producción, revisar/eliminar los registros de prueba
-- si se desea una base inicial completamente vacía.

SET TIME ZONE 'America/La_Paz';

COPY public.ambiente (id_ambiente, codigo, nombre, ubicacion, capacidad, estado) FROM stdin;
1	A101	Laboratorio 101	Bloque A	30	DISPONIBLE
2	A102	Laboratorio 102	Bloque A	30	DISPONIBLE
\.

COPY public.asignacion_ambiente (id_asignacion_ambiente, id_examen, id_ambiente) FROM stdin;
1	1	1
2	1	2
\.

COPY public.asignatura (id_asignatura, codigo, nombre, descripcion) FROM stdin;
1	INF101	Ingeniería de Software	Asignatura de prueba
\.

COPY public.auditoria (id_auditoria, id_usuario, accion, entidad, id_registro, fecha_hora, resultado, descripcion) FROM stdin;
\.


--
-- TOC entry 5099 (class 0 OID 16468)
-- Dependencies: 225
-- Data for Name: estudiante; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.estudiante (id_estudiante, ci, nombre, apellido, codigo_universitario, correo, estado) FROM stdin;
1	1234567	Juan	Perez	EST001	juan.perez@universidad.edu	ACTIVO
2	7654321	Maria	Lopez	EST002	maria.lopez@universidad.edu	ACTIVO
3	9999999	Pedro	Gomez	EST003	pedro.gomez@universidad.edu	INACTIVO
\.

COPY public.examen (id_examen, id_asignatura, nombre, fecha, hora_inicio, duracion_minutos, descripcion, estado) FROM stdin;
1	1	Primer Parcial Ingeniería de Software	2026-09-21	08:00:00	120	Examen de prueba	PROGRAMADO
\.

COPY public.expulsion (id_expulsion, id_ingreso, id_usuario, motivo, fecha_hora) FROM stdin;
\.


--
-- TOC entry 5109 (class 0 OID 16537)
-- Dependencies: 235
-- Data for Name: habilitacion; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.habilitacion (id_habilitacion, id_estudiante, id_examen, estado, motivo) FROM stdin;
1	1	1	HABILITADO	\N
2	2	1	NO_HABILITADO	No cumple requisito
3	3	1	HABILITADO	\N
\.

COPY public.incidencia (id_incidencia, id_estudiante, id_examen, id_usuario, tipo, descripcion, fecha_hora) FROM stdin;
\.


--
-- TOC entry 5115 (class 0 OID 16641)
-- Dependencies: 241
-- Data for Name: ingreso; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.ingreso (id_ingreso, id_examen, id_habilitacion, id_asignacion_ambiente, id_usuario, fecha_hora) FROM stdin;
1	1	1	1	1	2026-09-14 20:19:48.896963+00
\.

COPY public.intento_ingreso (id_intento, id_estudiante, id_examen, id_asignacion_ambiente, id_usuario, fecha_hora, motivo) FROM stdin;
\.


--
-- TOC entry 5094 (class 0 OID 16430)
-- Dependencies: 220
-- Data for Name: permiso; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.permiso (id_permiso, nombre, descripcion) FROM stdin;
1	GESTIONAR_USUARIOS	\N
2	GESTIONAR_ESTUDIANTES	\N
3	GESTIONAR_ASIGNATURAS	\N
4	GESTIONAR_EXAMENES	\N
5	GESTIONAR_AMBIENTES	\N
6	GESTIONAR_HABILITACIONES	\N
7	REGISTRAR_INGRESOS	\N
8	REGISTRAR_INTENTOS	\N
9	REGISTRAR_EXPULSIONES	\N
10	REGISTRAR_INCIDENCIAS	\N
11	CONSULTAR_REPORTES	\N
12	CONSULTAR_AUDITORIA	\N
\.

COPY public.regla_examen (id_regla_examen, id_examen, descripcion, estado) FROM stdin;
\.


--
-- TOC entry 5113 (class 0 OID 16625)
-- Dependencies: 239
-- Data for Name: regla_individual; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.regla_individual (id_regla_individual, id_habilitacion, descripcion, estado) FROM stdin;
\.

COPY public.rol (id_rol, nombre, descripcion) FROM stdin;
1	ADMINISTRADOR	\N
2	DOCENTE	\N
3	CONTROL_INGRESO	\N
\.

COPY public.rol_permiso (id_rol, id_permiso) FROM stdin;
1	1
1	2
1	3
1	4
1	5
1	6
1	7
1	8
1	9
1	10
1	11
1	12
2	2
2	3
2	4
2	6
2	11
3	7
3	8
3	9
3	10
3	11
\.

COPY public.usuario (id_usuario, nombre, apellido, correo, username, password_hash, id_rol, estado) FROM stdin;
1	Carlos	Control	carlos.control@universidad.edu	control1	HASH_DE_PRUEBA	3	ACTIVO
\.

SELECT pg_catalog.setval('public.ambiente_id_ambiente_seq', 2, true);

SELECT pg_catalog.setval('public.asignacion_ambiente_id_asignacion_ambiente_seq', 2, true);

SELECT pg_catalog.setval('public.asignatura_id_asignatura_seq', 1, true);

SELECT pg_catalog.setval('public.auditoria_id_auditoria_seq', 1, false);

SELECT pg_catalog.setval('public.estudiante_id_estudiante_seq', 3, true);

SELECT pg_catalog.setval('public.examen_id_examen_seq', 1, true);

SELECT pg_catalog.setval('public.expulsion_id_expulsion_seq', 1, false);

SELECT pg_catalog.setval('public.habilitacion_id_habilitacion_seq', 3, true);

SELECT pg_catalog.setval('public.incidencia_id_incidencia_seq', 1, false);

SELECT pg_catalog.setval('public.ingreso_id_ingreso_seq', 5, true);

SELECT pg_catalog.setval('public.intento_ingreso_id_intento_seq', 1, false);

SELECT pg_catalog.setval('public.permiso_id_permiso_seq', 12, true);

SELECT pg_catalog.setval('public.regla_examen_id_regla_examen_seq', 1, false);

SELECT pg_catalog.setval('public.regla_individual_id_regla_individual_seq', 1, false);

SELECT pg_catalog.setval('public.rol_id_rol_seq', 3, true);

SELECT pg_catalog.setval('public.usuario_id_usuario_seq', 1, true);

