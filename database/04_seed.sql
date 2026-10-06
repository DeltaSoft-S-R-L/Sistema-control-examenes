-- 04_seed.sql
-- Sistema de Control de Ingreso a Exámenes Masivos
-- Datos base y datos de prueba.
-- IMPORTANTE: antes de producción, revisar/eliminar los registros de prueba
-- si se desea una base inicial completamente vacía.

SET TIME ZONE 'America/La_Paz';


-- =========================================================
-- ROLES
-- =========================================================

COPY public.rol (id_rol, nombre, descripcion) FROM stdin;
1	ADMINISTRADOR	\N
2	DOCENTE	\N
3	CONTROL_INGRESO	\N
\.


-- =========================================================
-- PERMISOS
-- =========================================================

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


-- =========================================================
-- ROLES - PERMISOS
-- =========================================================

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


-- =========================================================
-- FACULTADES
-- =========================================================

COPY public.facultad (id_facultad, codigo, nombre) FROM stdin;
1	FCYT	Facultad de Ciencias y Tecnología
2	FAYCH	Facultad de Arquitectura y Cs. del Hábitat
3	FCAPYF	Facultad de Cs. Agrícolas Pecuarias y Forestales
4	FCE	    Facultad de Cs. Económicas
5	FCFYB	Facultad de Cs. Farmacéuticas y Bioquímicas
6	FCJYP	Facultad de Cs. Jurídicas y Políticas
7	FACSO	Facultad de Cs. Sociales
8	FCV	    Facultad de Cs. Veterinarias
9	FDRYT	Facultad de Desarrollo Rural y Territorial
10	FE	    Facultad de Enfermería
11	FHYCE	Facultad de Humanidades y Cs. de la Educación
12	MED	    Facultad de Medicina
13	FODO	Facultad de Odontología
14	FPVA	Facultad de Politécnica del Valle Alto
\.


-- =========================================================
-- CARRERAS
-- =========================================================

COPY public.carrera (id_carrera, id_facultad, codigo, nombre) FROM stdin;
1	1	411702	Ingeniería de Sistemas
2	3	103020	Ingeniería Agrícola Tropical y Manejo de Recursos Renovables
3	3	019701	Ingeniería Agrícola
4	3	649701	Ingeniería Fitotecnista
5	3	730602	Ingeniería Forestal
6	3	770201	Ingeniería Agrónomo Zootecnista
7	3	117071	Ingeniería Agroindustrial
8	3	718801	Ingeniería Agronómica
9	3	709701	Técnico Superior en Mecanización Agrícola
10	4	109401	Administración de Empresas
11	4	089801	Contaduría Pública
12	4	059801	Economía
13	4	125091	Ingeniería Comercial
14	4	126091	Ingeniería Financiera
15	5	049001	Licenciatura en Bioquímica y Farmacia
16	6	279901	Ciencias Jurídicas
17	6	280101	Ciencias Políticas
18	7	150802	Sociología
19	1	399501	Licenciatura en Biología
20	1	165221	Ingeniería en Biotecnología
21	1	134111	Ingeniería Informática
22	1	409701	Ingeniería de Alimentos
23	1	299701	Ingeniería Eléctrica
24	1	650001	Ingeniería Electromecánica
25	1	429701	Ingeniería Electrónica
26	1	166231	Ingeniería en Energías
27	1	309801	Ingeniería Industrial
28	1	439801	Ingeniería Matemática
29	1	319801	Ingeniería Mecánica
30	1	339701	Ingeniería Química
31	1	349701	Licenciatura en Matemáticas
32	1	389701	Licenciatura en Química
33	1	320902	Ingeniería Civil
34	8	039503	Licenciatura en Medicina Veterinaria y Zootecnia
35	9	168201	Técnico Superior en Agronomía
36	10	190602	Licenciatura en Enfermería
37	11	240101	Licenciatura en Psicología
38	11	251302	Licenciatura en Ciencias de la Educación
39	11	172261	Licenciatura en Lingüística Aplicada a la Enseñanza de Lenguas
40	11	142603	Licenciatura en Comunicación Social
41	11	108061	Licenciatura en Trabajo Social
42	12	188301	Licenciatura en Medicina
43	12	129091	Licenciatura en Fisioterapia y Kinesiología
44	12	133011	Licenciatura en Nutrición y Dietética
45	13	179901	Licenciatura en Odontología
46	2	202002	Licenciatura en Arquitectura
47	2	122081	Licenciatura en Diseño Gráfico y Comunicación Visual
48	2	156151	Licenciatura en Diseño de Interiores y del Mobiliario
49	2	231802	Licenciatura en Turismo
50	2	127091	Licenciatura en Planificación del Territorio y Medio Ambiente
51	2	229801	Técnico Universitario Superior en Construcciones
52	14	569201	Técnico Universitario Superior en Industria de Alimentos
53	14	720101	Técnico Universitario Superior en Mecánica Automotriz
54	14	589401	Técnico Universitario Superior en Construcción Civil
55	14	489201	Técnico Universitario Superior en Mecánica Industrial
56	14	529801	Técnico Universitario Superior en Química Industrial
57	14	155162	Técnico Universitario Medio en Enfermería
58	7	142131	Licenciatura en Antropología
59	7	164221	Licenciatura en Historia
\.


-- =========================================================
-- ASIGNATURAS (MATERIAS)
-- =========================================================

COPY public.asignatura (id_asignatura, codigo, nombre, descripcion) FROM stdin;
1	2010020	INGENIERIA DE SOFTWARE	UMSS - Plan 411702 - Nivel F
2	1803001	INGLES I	UMSS - Plan 411702 - Nivel A
3	2006063	FISICA GENERAL	UMSS - Plan 411702 - Nivel A
4	2008019	ALGEBRA I	UMSS - Plan 411702 - Nivel A
5	2008054	CALCULO I	UMSS - Plan 411702 - Nivel A
6	2010010	INTRODUCCION A LA PROGRAMACION	UMSS - Plan 411702 - Nivel A
7	2010140	METODOLOGIA INVESTIGACION Y TEC COMUNICACION	UMSS - Plan 411702 - Nivel A
8	2008022	ALGEBRA II	UMSS - Plan 411702 - Nivel B
9	2008056	CALCULO II	UMSS - Plan 411702 - Nivel B
10	2008057	MATEMATICA DISCRETA	UMSS - Plan 411702 - Nivel B
11	2010003	ELEM. DE PROGRAMACION Y ESTRUC. DE DATOS	UMSS - Plan 411702 - Nivel B
12	2010013	ARQUITECTURA DE COMPUTADORAS I	UMSS - Plan 411702 - Nivel B
13	2008058	ECUACIONES DIFERENCIALES	UMSS - Plan 411702 - Nivel C
14	2008059	ESTADISTICA I	UMSS - Plan 411702 - Nivel C
15	2008060	CALCULO NUMERICO	UMSS - Plan 411702 - Nivel C
16	2010012	METODOS TECNICAS Y TALLER DE PROGRAMACION	UMSS - Plan 411702 - Nivel C
17	2010015	BASE DE DATOS I	UMSS - Plan 411702 - Nivel C
18	2010141	CIRCUITOS ELECTRONICOS	UMSS - Plan 411702 - Nivel C
19	2008061	ESTADISTICA II	UMSS - Plan 411702 - Nivel D
20	2010016	BASE DE DATOS II	UMSS - Plan 411702 - Nivel D
21	2010017	TALLER DE SISTEMAS OPERATIVOS	UMSS - Plan 411702 - Nivel D
22	2010018	SISTEMAS DE INFORMACION I	UMSS - Plan 411702 - Nivel D
23	2016046	CONTABILIDAD BASICA	UMSS - Plan 411702 - Nivel D
24	2016048	INVESTIGACION OPERATIVA I	UMSS - Plan 411702 - Nivel D
25	1803002	INGLES II	UMSS - Plan 411702 - Nivel E
26	2010022	SISTEMAS DE INFORMACION II	UMSS - Plan 411702 - Nivel E
27	2010035	APLICACION DE SISTEMAS OPERATIVOS	UMSS - Plan 411702 - Nivel E
28	2010053	TALLER DE BASE DE DATOS	UMSS - Plan 411702 - Nivel E
29	2010142	SISTEMAS I	UMSS - Plan 411702 - Nivel E
30	2016051	INVESTIGACION OPERATIVA II	UMSS - Plan 411702 - Nivel E
31	2016057	MERCADOTECNIA	UMSS - Plan 411702 - Nivel E
32	2010019	SIMULACION DE SISTEMAS	UMSS - Plan 411702 - Nivel F
33	2010027	INTELIGENCIA ARTIFICIAL	UMSS - Plan 411702 - Nivel F
34	2010047	REDES DE COMPUTADORAS	UMSS - Plan 411702 - Nivel F
35	2010143	SISTEMAS II	UMSS - Plan 411702 - Nivel F
36	2010144	SISTEMAS ECONOMICOS	UMSS - Plan 411702 - Nivel F
37	2010024	TALLER DE INGENIERIA DE SOFTWARE	UMSS - Plan 411702 - Nivel G
38	2010145	GESTION DE CALIDAD DE SOFTWARE	UMSS - Plan 411702 - Nivel G
39	2010146	REDES AVANZADAS DE COMPUTADORAS	UMSS - Plan 411702 - Nivel G
40	2010176	PROGRAMACION DE SISTEMAS PARALELOS	UMSS - Plan 411702 - Nivel G
41	2010186	DINAMICA DE SISTEMAS	UMSS - Plan 411702 - Nivel G
42	2010211	APLIC. INTERACTIVAS PARA TELEVISION DIGITAL	UMSS - Plan 411702 - Nivel G
43	2014087	ELECTROTECNIA INDUSTRIAL	UMSS - Plan 411702 - Nivel G
44	2016092	PLANIFICACION Y EVALUACION DE PROYECTOS	UMSS - Plan 411702 - Nivel G
45	1803009	INGLES III	UMSS - Plan 411702 - Nivel H
46	2010102	EVALUACION Y AUDITORIA DE SISTEMAS	UMSS - Plan 411702 - Nivel H
47	2010116	TALLER DE SIMULACION DE SISTEMAS	UMSS - Plan 411702 - Nivel H
48	2010119	METODOL. Y PLANIF. DE PROYECTO DE GRADO	UMSS - Plan 411702 - Nivel H
49	2010209	SEGURIDAD DE SISTEMAS	UMSS - Plan 411702 - Nivel H
50	2010210	INFORMATICA FORENSE	UMSS - Plan 411702 - Nivel H
51	2016059	GESTION ESTRATEGICA DE EMPRESAS	UMSS - Plan 411702 - Nivel H
52	2010032	DATA WAREHOUSE	UMSS - Plan 411702 - Nivel I
53	2010122	PROYECTO FINAL	UMSS - Plan 411702 - Nivel I
54	2010147	PRACTICA EMPRESARIAL	UMSS - Plan 411702 - Nivel I
55	2010178	ENTORNOS VIRTUALES DE APRENDIZAJE	UMSS - Plan 411702 - Nivel I
56	2010188	SERVICIOS TELEMATICOS	UMSS - Plan 411702 - Nivel I
57	2010189	RECONOCIMIENTO DE VOZ	UMSS - Plan 411702 - Nivel I
58	2010218	CIENCIA DE DATOS Y MACHINE LEARNING	UMSS - Plan 411702 - Nivel I
59	2016021	PLANIF. Y CONTROL DE LA PRODUCCION I	UMSS - Plan 411702 - Nivel I
60	2016023	INGENIERIA ECONOMICA	UMSS - Plan 411702 - Nivel I
61	2016027	PLANIF. Y CONTROL DE LA PRODUCCION II	UMSS - Plan 411702 - Nivel I
62	2016049	COSTOS INDUSTRIALES	UMSS - Plan 411702 - Nivel I
63	2016052	INGENIERIA DE METODOS Y REINGENIERIA	UMSS - Plan 411702 - Nivel I
64	2010044	DISEÑO DE COMPILADORES	UMSS - Plan 411702 - Nivel J
65	2010192	TRANSMISION IP	UMSS - Plan 411702 - Nivel J
66	2010200	PROGRAMACION	UMSS - Plan 134111 - Nivel B
67	2008140	LOGICA	UMSS - Plan 134111 - Nivel C
68	2010014	ARQUITECTURA DE COMPUTADORAS II	UMSS - Plan 134111 - Nivel C
69	2010037	TEORIA DE GRAFOS	UMSS - Plan 134111 - Nivel C
70	2010041	ORGANIZACION Y METODOS	UMSS - Plan 134111 - Nivel C
71	2010206	METODOS Y TECNICAS DE PROGRAMACION	UMSS - Plan 134111 - Nivel C
72	2008029	PROBABILIDAD Y ESTADISTICA	UMSS - Plan 134111 - Nivel D
73	2010005	TALLER DE PROGRAMACION EN BAJO NIVEL	UMSS - Plan 134111 - Nivel D
74	2010038	PROGRAMACION FUNCIONAL	UMSS - Plan 134111 - Nivel D
75	2010197	ALGORITMOS AVANZADOS	UMSS - Plan 134111 - Nivel D
76	2010040	TEORIA DE AUTOMATAS Y LENG. FORMALES	UMSS - Plan 134111 - Nivel E
77	2010042	GRAFICACION POR COMPUTADORA	UMSS - Plan 134111 - Nivel E
78	2010201	INTELIGENCIA ARTIFICIAL I	UMSS - Plan 134111 - Nivel E
79	2010049	ESTRUCTURA Y SEMANTICA DE LENGUAJES DE PROGRA	UMSS - Plan 134111 - Nivel F
80	2010202	INTELIGENCIA ARTIFICIAL II	UMSS - Plan 134111 - Nivel F
81	2010203	PROGRAMACION WEB	UMSS - Plan 134111 - Nivel F
82	2010100	ARQUITECTURA DE SOFTWARE	UMSS - Plan 134111 - Nivel G
83	2010204	INTERACCION HUMANO COMPUTADOR	UMSS - Plan 134111 - Nivel G
84	2010205	TECNOLOGIA REDES AVANZADAS	UMSS - Plan 134111 - Nivel G
85	2010214	TALLER DE GRADO I	UMSS - Plan 134111 - Nivel H
86	2010215	TALLER DE GRADO II	UMSS - Plan 134111 - Nivel I
87	1304001	ECONOMIA GENERAL	UMSS - Plan 059801 - Nivel A
88	1304002	TALLER DE LENGUAJE Y REDACCION	UMSS - Plan 059801 - Nivel A
89	1304003	ALGEBRA	UMSS - Plan 059801 - Nivel A
90	1304004	CALCULO	UMSS - Plan 059801 - Nivel A
91	1304005	SOCIOLOGIA ECONOMICA	UMSS - Plan 059801 - Nivel A
92	1304006	HISTORIA ECONOMICA DE AMERICA LATINA	UMSS - Plan 059801 - Nivel A
93	1304007	MICROECONOMIA I	UMSS - Plan 059801 - Nivel B
94	1304012	HISTORIA ECONOMICA DE BOLIVIA	UMSS - Plan 059801 - Nivel B
95	1304017	ADMINISTRACION	UMSS - Plan 059801 - Nivel B
96	1304018	CONTABILIDAD BASICA	UMSS - Plan 059801 - Nivel B
97	1304157	ALGEBRA APLICADA	UMSS - Plan 059801 - Nivel B
98	1304158	CALCULO APLICADO	UMSS - Plan 059801 - Nivel B
99	1304008	MACROECONOMIA I	UMSS - Plan 059801 - Nivel C
100	1304013	MICROECONOMIA II	UMSS - Plan 059801 - Nivel C
101	1304016	ESTADISTICA I	UMSS - Plan 059801 - Nivel C
102	1304021	ECONOMIA FINANCIERA I	UMSS - Plan 059801 - Nivel C
103	1304024	CONTABILIDAD DE GESTION	UMSS - Plan 059801 - Nivel C
104	1803021	INGLES I	UMSS - Plan 059801 - Nivel C
105	1302080	GESTION DE RIESGOS	UMSS - Plan 059801 - Nivel D
106	1304014	MACROECONOMIA II	UMSS - Plan 059801 - Nivel D
107	1304019	TEORIA FISCAL	UMSS - Plan 059801 - Nivel D
108	1304022	ECONOMIA POLITICA I	UMSS - Plan 059801 - Nivel D
109	1304023	ESTADISTICA II	UMSS - Plan 059801 - Nivel D
110	1304027	ECONOMIA FINANCIERA II	UMSS - Plan 059801 - Nivel D
111	1304160	ECONOMIA INDUSTRIAL	UMSS - Plan 059801 - Nivel D
112	1803024	INGLES II	UMSS - Plan 059801 - Nivel D
113	1301031	MERCADOTECNIA I	UMSS - Plan 059801 - Nivel E
114	1304011	ECONOMIA DE LOS RR.NN. Y M.A.	UMSS - Plan 059801 - Nivel E
115	1304015	HISTORIA DEL PENSAMIENTO ECONOMICO	UMSS - Plan 059801 - Nivel E
116	1304020	TEORIA MONETARIA	UMSS - Plan 059801 - Nivel E
117	1304025	EPISTEMOLOGIA DE LA ECONOMIA	UMSS - Plan 059801 - Nivel E
118	1304028	ECONOMIA POLITICA II	UMSS - Plan 059801 - Nivel E
119	1304030	ECONOMETRIA I	UMSS - Plan 059801 - Nivel E
120	1304063	MUESTREO	UMSS - Plan 059801 - Nivel E
121	1304129	DEMOGRAFIA	UMSS - Plan 059801 - Nivel E
122	1304132	ESTADISTICA APLICADA	UMSS - Plan 059801 - Nivel E
123	1304134	FINANZAS DE EMPRESAS	UMSS - Plan 059801 - Nivel E
124	1304139	PRESUPUESTOS EMPRESARIALES	UMSS - Plan 059801 - Nivel E
125	1304142	SEMINARIO DE ESTADISTICA	UMSS - Plan 059801 - Nivel E
126	1304144	TRIBUTACION	UMSS - Plan 059801 - Nivel E
127	1304155	GESTION DE RECURSOS HUMANOS	UMSS - Plan 059801 - Nivel E
128	1304210	INVESTIGACION OPERATIVA	UMSS - Plan 059801 - Nivel E
129	1803027	INGLES III	UMSS - Plan 059801 - Nivel E
130	1304026	POLITICAS MACROECONOMICAS	UMSS - Plan 059801 - Nivel F
131	1304033	TEORIAS ECONOMICAS ACTUALES	UMSS - Plan 059801 - Nivel F
132	1304034	FORMULACION DE PROYECTOS	UMSS - Plan 059801 - Nivel F
133	1304035	TEORIA DE LAS INSTITUCIONES ECONOMICAS	UMSS - Plan 059801 - Nivel F
134	1304036	ECONOMETRIA II	UMSS - Plan 059801 - Nivel F
135	1304131	ECONOMIA AGRARIA	UMSS - Plan 059801 - Nivel F
136	1304141	PROYECTOS SOCIALES	UMSS - Plan 059801 - Nivel F
137	1304159	SEMINARIO COMERCIAL	UMSS - Plan 059801 - Nivel F
138	1304161	GESTION DE PROYECTOS DE INVERSION	UMSS - Plan 059801 - Nivel F
139	1301034	MERCADOTECNIA II	UMSS - Plan 059801 - Nivel G
140	1304032	DESARROLLO ECONOMICO I	UMSS - Plan 059801 - Nivel G
141	1304038	ECONOMIA INTERNACIONAL I	UMSS - Plan 059801 - Nivel G
142	1304039	POLITICA ECONOMICA	UMSS - Plan 059801 - Nivel G
143	1304040	EVALUACION DE PROYECTOS	UMSS - Plan 059801 - Nivel G
144	1304113	CONTABILIDAD NACIONAL E INSUMO-PRODUCTO	UMSS - Plan 059801 - Nivel G
145	1304133	ESTRATEGIA EMPRESARIAL	UMSS - Plan 059801 - Nivel G
146	1302004	DERECHO COMERCIAL	UMSS - Plan 059801 - Nivel H
147	1302006	CONTABILIDAD II	UMSS - Plan 059801 - Nivel H
148	1304041	SEMINARIO DE INVESTIGACION	UMSS - Plan 059801 - Nivel H
149	1304045	ECONOMIA INTERNACIONAL II	UMSS - Plan 059801 - Nivel H
150	1304046	ECONOMIA MUNDIAL CONTEMPORANEA	UMSS - Plan 059801 - Nivel H
151	1304065	SISTEMAS ADMINISTRATIVOS	UMSS - Plan 059801 - Nivel H
152	1304135	MERCADO DE CAPITALES	UMSS - Plan 059801 - Nivel H
153	1304149	DESARROLLO ECONOMICO II	UMSS - Plan 059801 - Nivel H
154	1304154	SEMINARIO DE MENCION	UMSS - Plan 059801 - Nivel H
155	1304156	PLANIFICACION	UMSS - Plan 059801 - Nivel H
156	1304048	TALLER DE TITULACION	UMSS - Plan 059801 - Nivel I
157	1304049	APOYO ESTADISTICO	UMSS - Plan 059801 - Nivel I
158	1304050	APOYO METODOLOGICO	UMSS - Plan 059801 - Nivel I
159	1703007	TALLER DE DISEÑO I	UMSS - Plan 122081 - Nivel 1
160	1703022	DIBUJO I	UMSS - Plan 122081 - Nivel 1
161	1703023	GEOMETRIA DESCRIPTIVA	UMSS - Plan 122081 - Nivel 1
162	1704017	EXPRESION ORAL Y ESCRITA	UMSS - Plan 122081 - Nivel 1
163	1704042	HISTORIA I	UMSS - Plan 122081 - Nivel 1
164	1704044	TEORIA I	UMSS - Plan 122081 - Nivel 1
165	1705042	COMPUTACION BASICA	UMSS - Plan 122081 - Nivel 1
166	1705043	TEORIA Y MANEJO DEL COLOR	UMSS - Plan 122081 - Nivel 1
167	1702028	TECNOLOGIA PARA EL DISEÑO I	UMSS - Plan 122081 - Nivel 2
168	1703008	TALLER DE DISEÑO II	UMSS - Plan 122081 - Nivel 2
169	1704018	TEORIA Y METODOLOGIA APLICADA AL DISEÑO I	UMSS - Plan 122081 - Nivel 2
170	1704019	TEORIA Y COMUNICACION I	UMSS - Plan 122081 - Nivel 2
171	1704043	HISTORIA II	UMSS - Plan 122081 - Nivel 2
172	1705044	GRAFICA COMPUTACIONAL I	UMSS - Plan 122081 - Nivel 2
173	1705045	DIBUJO ARTISTICO	UMSS - Plan 122081 - Nivel 2
174	1705046	FOTOGRAFIA	UMSS - Plan 122081 - Nivel 2
175	1702029	TECNOLOGIA PARA EL DISEÑO II	UMSS - Plan 122081 - Nivel 3
176	1703009	TALLER DE DISEÑO III	UMSS - Plan 122081 - Nivel 3
177	1704020	TEORIA Y METODOLOGIA APLICADA AL DISEÑO II	UMSS - Plan 122081 - Nivel 3
178	1704021	TEORIA Y COMUNICACION II	UMSS - Plan 122081 - Nivel 3
179	1704022	PUBLICIDAD Y MARKETING	UMSS - Plan 122081 - Nivel 3
180	1704023	DISEÑO Y MEDIO AMBIENTE	UMSS - Plan 122081 - Nivel 3
181	1705047	GRAFICA COMPUTACIONAL II	UMSS - Plan 122081 - Nivel 3
182	1703010	TALLER DE DISEÑO IV	UMSS - Plan 122081 - Nivel 4
183	1703024	TIPOGRAFIA	UMSS - Plan 122081 - Nivel 4
184	1704024	TEORIA Y METODOLOGIA APLICADA AL DISEÑO III	UMSS - Plan 122081 - Nivel 4
185	1704025	TEORIA Y COMUNICACION III	UMSS - Plan 122081 - Nivel 4
186	1704026	PSICOLOGIA DEL MENSAJE VISUAL	UMSS - Plan 122081 - Nivel 4
187	1704027	GESTION DE PROYECTOS	UMSS - Plan 122081 - Nivel 4
188	1703011	MODALIDADES DE GRADUACION (TALLER DISEÑO V)	UMSS - Plan 122081 - Nivel 5
\.

-- =========================================================
-- CARRERAS - ASIGNATURAS
-- =========================================================

COPY public.carrera_asignatura (id_carrera, id_asignatura) FROM stdin;
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
1	13
1	14
1	15
1	16
1	17
1	18
1	19
1	20
1	21
1	22
1	23
1	24
1	25
1	26
1	27
1	28
1	29
1	30
1	31
1	32
1	33
1	34
1	35
1	36
1	37
1	38
1	39
1	40
1	41
1	42
1	43
1	44
1	45
1	46
1	47
1	48
1	49
1	50
1	51
1	52
1	53
1	54
1	55
1	56
1	57
1	58
1	59
1	60
1	61
1	62
1	63
1	64
1	65
21	2
21	3
21	4
21	5
21	6
21	25
21	8
21	9
21	11
21	12
21	66
21	15
21	67
21	68
21	69
21	70
21	71
21	72
21	73
21	17
21	22
21	74
21	75
21	20
21	21
21	26
21	76
21	77
21	78
21	1
21	34
21	79
21	28
21	80
21	81
21	32
21	37
21	82
21	83
21	84
21	27
21	46
21	85
21	23
21	52
21	64
21	40
21	55
21	56
21	57
21	49
21	42
21	86
21	58
12	87
12	88
12	89
12	90
12	91
12	92
12	93
12	94
12	95
12	96
12	97
12	98
12	99
12	100
12	101
12	102
12	103
12	104
12	105
12	106
12	107
12	108
12	109
12	110
12	111
12	112
12	113
12	114
12	115
12	116
12	117
12	118
12	119
12	120
12	121
12	122
12	123
12	124
12	125
12	126
12	127
12	128
12	129
12	130
12	131
12	132
12	133
12	134
12	135
12	136
12	137
12	138
12	139
12	140
12	141
12	142
12	143
12	144
12	145
12	146
12	147
12	148
12	149
12	150
12	151
12	152
12	153
12	154
12	155
12	156
12	157
12	158
47	159
47	160
47	161
47	162
47	163
47	164
47	165
47	166
47	167
47	168
47	169
47	170
47	171
47	172
47	173
47	174
47	175
47	176
47	177
47	178
47	179
47	180
47	181
47	182
47	183
47	184
47	185
47	186
47	187
47	188
\.


-- =========================================================
-- AMBIENTES
-- =========================================================

COPY public.ambiente (id_ambiente, codigo, nombre, ubicacion, capacidad, estado) FROM stdin;
1	A101	Laboratorio 101	Bloque A	30	DISPONIBLE
2	A102	Laboratorio 102	Bloque A	30	DISPONIBLE
\.


-- =========================================================
-- ESTUDIANTES
-- =========================================================

COPY public.estudiante (id_estudiante, ci, nombre, apellido, codigo_universitario, id_carrera, correo, estado) FROM stdin;
1	1234567	Juan	Perez	EST001	1	juan.perez@universidad.edu	ACTIVO
2	7654321	Maria	Lopez	EST002	1	maria.lopez@universidad.edu	ACTIVO
3	9999999	Pedro	Gomez	EST003	1	pedro.gomez@universidad.edu	INACTIVO
\.


-- =========================================================
-- ESTUDIANTES - ASIGNATURAS
-- =========================================================

COPY public.estudiante_asignatura (id_estudiante, id_asignatura) FROM stdin;
1	1
2	1
3	1
\.


-- =========================================================
-- USUARIOS
-- =========================================================

COPY public.usuario (id_usuario, nombre, apellido, correo, username, password_hash, id_rol, estado) FROM stdin;
1	Carlos	Control	carlos.control@universidad.edu	control1	HASH_DE_PRUEBA	3	ACTIVO
\.


-- =========================================================
-- EXÁMENES
-- =========================================================

COPY public.examen (id_examen, id_asignatura, nombre, fecha, hora_inicio, duracion_minutos, descripcion, estado) FROM stdin;
1	1	Primer Parcial Ingeniería de Software	2026-09-21	08:00:00	120	Examen de prueba	PROGRAMADO
\.


-- =========================================================
-- ASIGNACIÓN DE AMBIENTES
-- =========================================================

COPY public.asignacion_ambiente (id_asignacion_ambiente, id_examen, id_ambiente) FROM stdin;
1	1	1
2	1	2
\.


-- =========================================================
-- HABILITACIONES
-- =========================================================

COPY public.habilitacion (id_habilitacion, id_estudiante, id_examen, estado, motivo) FROM stdin;
1	1	1	HABILITADO	\N
2	2	1	NO_HABILITADO	No cumple requisito
3	3	1	HABILITADO	\N
\.


-- =========================================================
-- REGLAS DE EXAMEN
-- =========================================================

COPY public.regla_examen (id_regla_examen, id_examen, descripcion, estado) FROM stdin;
\.


-- =========================================================
-- REGLAS INDIVIDUALES
-- =========================================================

COPY public.regla_individual (id_regla_individual, id_habilitacion, descripcion, estado) FROM stdin;
\.


-- =========================================================
-- INGRESOS
-- =========================================================

COPY public.ingreso (id_ingreso, id_examen, id_habilitacion, id_asignacion_ambiente, id_usuario, fecha_hora) FROM stdin;
1	1	1	1	1	2026-09-14 20:19:48.896963+00
\.


-- =========================================================
-- INTENTOS DE INGRESO
-- =========================================================

COPY public.intento_ingreso (id_intento, id_estudiante, id_examen, id_asignacion_ambiente, id_usuario, fecha_hora, motivo) FROM stdin;
\.


-- =========================================================
-- INCIDENCIAS
-- =========================================================

COPY public.incidencia (id_incidencia, id_estudiante, id_examen, id_usuario, tipo, descripcion, fecha_hora) FROM stdin;
\.


-- =========================================================
-- EXPULSIONES
-- =========================================================

COPY public.expulsion (id_expulsion, id_ingreso, id_usuario, motivo, fecha_hora) FROM stdin;
\.


-- =========================================================
-- AUDITORÍA
-- =========================================================

COPY public.auditoria (id_auditoria, id_usuario, accion, entidad, id_registro, fecha_hora, resultado, descripcion) FROM stdin;
\.


-- =========================================================
-- SECUENCIAS
-- =========================================================

SELECT pg_catalog.setval('public.ambiente_id_ambiente_seq', 2, true);

SELECT pg_catalog.setval('public.asignacion_ambiente_id_asignacion_ambiente_seq', 2, true);

SELECT pg_catalog.setval('public.asignatura_id_asignatura_seq', 1, true);

SELECT pg_catalog.setval('public.auditoria_id_auditoria_seq', 1, false);

SELECT pg_catalog.setval('public.facultad_id_facultad_seq', 1, true);

SELECT pg_catalog.setval('public.carrera_id_carrera_seq', 1, true);

SELECT pg_catalog.setval('public.estudiante_id_estudiante_seq', 3, true);

SELECT pg_catalog.setval('public.examen_id_examen_seq', 1, true);

SELECT pg_catalog.setval('public.expulsion_id_expulsion_seq', 1, false);

SELECT pg_catalog.setval('public.habilitacion_id_habilitacion_seq', 3, true);

SELECT pg_catalog.setval('public.incidencia_id_incidencia_seq', 1, false);

SELECT pg_catalog.setval('public.ingreso_id_ingreso_seq', 1, true);

SELECT pg_catalog.setval('public.intento_ingreso_id_intento_seq', 1, false);

SELECT pg_catalog.setval('public.permiso_id_permiso_seq', 12, true);

SELECT pg_catalog.setval('public.regla_examen_id_regla_examen_seq', 1, false);

SELECT pg_catalog.setval('public.regla_individual_id_regla_individual_seq', 1, false);

SELECT pg_catalog.setval('public.rol_id_rol_seq', 3, true);

SELECT pg_catalog.setval('public.usuario_id_usuario_seq', 1, true);