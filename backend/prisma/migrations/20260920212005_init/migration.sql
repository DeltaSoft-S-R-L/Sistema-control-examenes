-- CreateTable
CREATE TABLE "ambiente" (
    "id_ambiente" BIGSERIAL NOT NULL,
    "codigo" VARCHAR(30) NOT NULL,
    "nombre" VARCHAR(100) NOT NULL,
    "ubicacion" VARCHAR(150),
    "capacidad" INTEGER NOT NULL,
    "estado" VARCHAR(20) NOT NULL,

    CONSTRAINT "pk_ambiente" PRIMARY KEY ("id_ambiente")
);

-- CreateTable
CREATE TABLE "asignacion_ambiente" (
    "id_asignacion_ambiente" BIGSERIAL NOT NULL,
    "id_examen" BIGINT NOT NULL,
    "id_ambiente" BIGINT NOT NULL,

    CONSTRAINT "pk_asignacion_ambiente" PRIMARY KEY ("id_asignacion_ambiente")
);

-- CreateTable
CREATE TABLE "asignatura" (
    "id_asignatura" BIGSERIAL NOT NULL,
    "codigo" VARCHAR(30) NOT NULL,
    "nombre" VARCHAR(150) NOT NULL,
    "descripcion" TEXT,

    CONSTRAINT "pk_asignatura" PRIMARY KEY ("id_asignatura")
);

-- CreateTable
CREATE TABLE "auditoria" (
    "id_auditoria" BIGSERIAL NOT NULL,
    "id_usuario" BIGINT NOT NULL,
    "accion" VARCHAR(100) NOT NULL,
    "entidad" VARCHAR(100) NOT NULL,
    "id_registro" BIGINT,
    "fecha_hora" TIMESTAMPTZ(6) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "resultado" VARCHAR(20),
    "descripcion" TEXT,

    CONSTRAINT "pk_auditoria" PRIMARY KEY ("id_auditoria")
);

-- CreateTable
CREATE TABLE "estudiante" (
    "id_estudiante" BIGSERIAL NOT NULL,
    "ci" VARCHAR(20) NOT NULL,
    "nombre" VARCHAR(100) NOT NULL,
    "apellido" VARCHAR(100) NOT NULL,
    "codigo_universitario" VARCHAR(50) NOT NULL,
    "correo" VARCHAR(150),
    "estado" VARCHAR(20) NOT NULL,

    CONSTRAINT "pk_estudiante" PRIMARY KEY ("id_estudiante")
);

-- CreateTable
CREATE TABLE "examen" (
    "id_examen" BIGSERIAL NOT NULL,
    "id_asignatura" BIGINT NOT NULL,
    "nombre" VARCHAR(150) NOT NULL,
    "fecha" DATE NOT NULL,
    "hora_inicio" TIME(6) NOT NULL,
    "duracion_minutos" INTEGER NOT NULL,
    "descripcion" TEXT,
    "estado" VARCHAR(20) NOT NULL,

    CONSTRAINT "pk_examen" PRIMARY KEY ("id_examen")
);

-- CreateTable
CREATE TABLE "expulsion" (
    "id_expulsion" BIGSERIAL NOT NULL,
    "id_ingreso" BIGINT NOT NULL,
    "id_usuario" BIGINT NOT NULL,
    "motivo" TEXT NOT NULL,
    "fecha_hora" TIMESTAMPTZ(6) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "pk_expulsion" PRIMARY KEY ("id_expulsion")
);

-- CreateTable
CREATE TABLE "habilitacion" (
    "id_habilitacion" BIGSERIAL NOT NULL,
    "id_estudiante" BIGINT NOT NULL,
    "id_examen" BIGINT NOT NULL,
    "estado" VARCHAR(20) NOT NULL,
    "motivo" TEXT,

    CONSTRAINT "pk_habilitacion" PRIMARY KEY ("id_habilitacion")
);

-- CreateTable
CREATE TABLE "incidencia" (
    "id_incidencia" BIGSERIAL NOT NULL,
    "id_estudiante" BIGINT,
    "id_examen" BIGINT NOT NULL,
    "id_usuario" BIGINT NOT NULL,
    "tipo" VARCHAR(50) NOT NULL,
    "descripcion" TEXT NOT NULL,
    "fecha_hora" TIMESTAMPTZ(6) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "pk_incidencia" PRIMARY KEY ("id_incidencia")
);

-- CreateTable
CREATE TABLE "ingreso" (
    "id_ingreso" BIGSERIAL NOT NULL,
    "id_examen" BIGINT NOT NULL,
    "id_habilitacion" BIGINT NOT NULL,
    "id_asignacion_ambiente" BIGINT NOT NULL,
    "id_usuario" BIGINT NOT NULL,
    "fecha_hora" TIMESTAMPTZ(6) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "pk_ingreso" PRIMARY KEY ("id_ingreso")
);

-- CreateTable
CREATE TABLE "intento_ingreso" (
    "id_intento" BIGSERIAL NOT NULL,
    "id_estudiante" BIGINT,
    "id_examen" BIGINT NOT NULL,
    "id_asignacion_ambiente" BIGINT NOT NULL,
    "id_usuario" BIGINT NOT NULL,
    "fecha_hora" TIMESTAMPTZ(6) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "motivo" TEXT NOT NULL,

    CONSTRAINT "pk_intento_ingreso" PRIMARY KEY ("id_intento")
);

-- CreateTable
CREATE TABLE "permiso" (
    "id_permiso" BIGSERIAL NOT NULL,
    "nombre" VARCHAR(100) NOT NULL,
    "descripcion" VARCHAR(255),

    CONSTRAINT "pk_permiso" PRIMARY KEY ("id_permiso")
);

-- CreateTable
CREATE TABLE "regla_examen" (
    "id_regla_examen" BIGSERIAL NOT NULL,
    "id_examen" BIGINT NOT NULL,
    "descripcion" TEXT NOT NULL,
    "estado" VARCHAR(20) NOT NULL,

    CONSTRAINT "pk_regla_examen" PRIMARY KEY ("id_regla_examen")
);

-- CreateTable
CREATE TABLE "regla_individual" (
    "id_regla_individual" BIGSERIAL NOT NULL,
    "id_habilitacion" BIGINT NOT NULL,
    "descripcion" TEXT NOT NULL,
    "estado" VARCHAR(20) NOT NULL,

    CONSTRAINT "pk_regla_individual" PRIMARY KEY ("id_regla_individual")
);

-- CreateTable
CREATE TABLE "rol" (
    "id_rol" BIGSERIAL NOT NULL,
    "nombre" VARCHAR(50) NOT NULL,
    "descripcion" VARCHAR(255),

    CONSTRAINT "pk_rol" PRIMARY KEY ("id_rol")
);

-- CreateTable
CREATE TABLE "rol_permiso" (
    "id_rol" BIGINT NOT NULL,
    "id_permiso" BIGINT NOT NULL,

    CONSTRAINT "pk_rol_permiso" PRIMARY KEY ("id_rol","id_permiso")
);

-- CreateTable
CREATE TABLE "usuario" (
    "id_usuario" BIGSERIAL NOT NULL,
    "nombre" VARCHAR(100) NOT NULL,
    "apellido" VARCHAR(100) NOT NULL,
    "correo" VARCHAR(150) NOT NULL,
    "username" VARCHAR(50) NOT NULL,
    "password_hash" VARCHAR(255) NOT NULL,
    "id_rol" BIGINT NOT NULL,
    "estado" VARCHAR(20) NOT NULL,

    CONSTRAINT "pk_usuario" PRIMARY KEY ("id_usuario")
);

-- CreateIndex
CREATE UNIQUE INDEX "uq_ambiente_codigo" ON "ambiente"("codigo");

-- CreateIndex
CREATE INDEX "idx_ambiente_estado" ON "ambiente"("estado");

-- CreateIndex
CREATE INDEX "idx_asignacion_ambiente_examen" ON "asignacion_ambiente"("id_examen", "id_ambiente");

-- CreateIndex
CREATE UNIQUE INDEX "uq_asignacion_ambiente_examen" ON "asignacion_ambiente"("id_asignacion_ambiente", "id_examen");

-- CreateIndex
CREATE UNIQUE INDEX "uq_asignacion_examen_ambiente" ON "asignacion_ambiente"("id_examen", "id_ambiente");

-- CreateIndex
CREATE UNIQUE INDEX "uq_asignatura_codigo" ON "asignatura"("codigo");

-- CreateIndex
CREATE UNIQUE INDEX "uq_estudiante_ci" ON "estudiante"("ci");

-- CreateIndex
CREATE UNIQUE INDEX "uq_estudiante_codigo" ON "estudiante"("codigo_universitario");

-- CreateIndex
CREATE INDEX "idx_estudiante_estado" ON "estudiante"("estado");

-- CreateIndex
CREATE INDEX "idx_examen_estado" ON "examen"("estado");

-- CreateIndex
CREATE INDEX "idx_examen_fecha_hora" ON "examen"("fecha", "hora_inicio");

-- CreateIndex
CREATE UNIQUE INDEX "uq_expulsion_ingreso" ON "expulsion"("id_ingreso");

-- CreateIndex
CREATE INDEX "idx_habilitacion_estado" ON "habilitacion"("estado");

-- CreateIndex
CREATE INDEX "idx_habilitacion_examen" ON "habilitacion"("id_examen");

-- CreateIndex
CREATE UNIQUE INDEX "uq_habilitacion_estudiante_examen" ON "habilitacion"("id_estudiante", "id_examen");

-- CreateIndex
CREATE UNIQUE INDEX "uq_habilitacion_id_examen" ON "habilitacion"("id_habilitacion", "id_examen");

-- CreateIndex
CREATE INDEX "idx_incidencia_examen" ON "incidencia"("id_examen");

-- CreateIndex
CREATE INDEX "idx_incidencia_tipo" ON "incidencia"("tipo");

-- CreateIndex
CREATE UNIQUE INDEX "uq_ingreso_habilitacion" ON "ingreso"("id_habilitacion");

-- CreateIndex
CREATE UNIQUE INDEX "uq_ingreso_habilitacion_examen" ON "ingreso"("id_habilitacion", "id_examen");

-- CreateIndex
CREATE UNIQUE INDEX "uq_permiso_nombre" ON "permiso"("nombre");

-- CreateIndex
CREATE INDEX "idx_regla_examen_estado" ON "regla_examen"("estado");

-- CreateIndex
CREATE INDEX "idx_regla_examen_examen" ON "regla_examen"("id_examen");

-- CreateIndex
CREATE INDEX "idx_regla_individual_estado" ON "regla_individual"("estado");

-- CreateIndex
CREATE INDEX "idx_regla_individual_habilitacion" ON "regla_individual"("id_habilitacion");

-- CreateIndex
CREATE UNIQUE INDEX "uq_rol_nombre" ON "rol"("nombre");

-- CreateIndex
CREATE UNIQUE INDEX "uq_usuario_correo" ON "usuario"("correo");

-- CreateIndex
CREATE UNIQUE INDEX "uq_usuario_username" ON "usuario"("username");

-- CreateIndex
CREATE INDEX "idx_usuario_estado" ON "usuario"("estado");

-- AddForeignKey
ALTER TABLE "asignacion_ambiente" ADD CONSTRAINT "fk_asignacion_ambiente" FOREIGN KEY ("id_ambiente") REFERENCES "ambiente"("id_ambiente") ON DELETE NO ACTION ON UPDATE NO ACTION;

-- AddForeignKey
ALTER TABLE "asignacion_ambiente" ADD CONSTRAINT "fk_asignacion_examen" FOREIGN KEY ("id_examen") REFERENCES "examen"("id_examen") ON DELETE NO ACTION ON UPDATE NO ACTION;

-- AddForeignKey
ALTER TABLE "auditoria" ADD CONSTRAINT "fk_auditoria_usuario" FOREIGN KEY ("id_usuario") REFERENCES "usuario"("id_usuario") ON DELETE NO ACTION ON UPDATE NO ACTION;

-- AddForeignKey
ALTER TABLE "examen" ADD CONSTRAINT "fk_examen_asignatura" FOREIGN KEY ("id_asignatura") REFERENCES "asignatura"("id_asignatura") ON DELETE NO ACTION ON UPDATE NO ACTION;

-- AddForeignKey
ALTER TABLE "expulsion" ADD CONSTRAINT "fk_expulsion_ingreso" FOREIGN KEY ("id_ingreso") REFERENCES "ingreso"("id_ingreso") ON DELETE NO ACTION ON UPDATE NO ACTION;

-- AddForeignKey
ALTER TABLE "expulsion" ADD CONSTRAINT "fk_expulsion_usuario" FOREIGN KEY ("id_usuario") REFERENCES "usuario"("id_usuario") ON DELETE NO ACTION ON UPDATE NO ACTION;

-- AddForeignKey
ALTER TABLE "habilitacion" ADD CONSTRAINT "fk_habilitacion_estudiante" FOREIGN KEY ("id_estudiante") REFERENCES "estudiante"("id_estudiante") ON DELETE NO ACTION ON UPDATE NO ACTION;

-- AddForeignKey
ALTER TABLE "habilitacion" ADD CONSTRAINT "fk_habilitacion_examen" FOREIGN KEY ("id_examen") REFERENCES "examen"("id_examen") ON DELETE NO ACTION ON UPDATE NO ACTION;

-- AddForeignKey
ALTER TABLE "incidencia" ADD CONSTRAINT "fk_incidencia_estudiante" FOREIGN KEY ("id_estudiante") REFERENCES "estudiante"("id_estudiante") ON DELETE NO ACTION ON UPDATE NO ACTION;

-- AddForeignKey
ALTER TABLE "incidencia" ADD CONSTRAINT "fk_incidencia_examen" FOREIGN KEY ("id_examen") REFERENCES "examen"("id_examen") ON DELETE NO ACTION ON UPDATE NO ACTION;

-- AddForeignKey
ALTER TABLE "incidencia" ADD CONSTRAINT "fk_incidencia_usuario" FOREIGN KEY ("id_usuario") REFERENCES "usuario"("id_usuario") ON DELETE NO ACTION ON UPDATE NO ACTION;

-- AddForeignKey
ALTER TABLE "ingreso" ADD CONSTRAINT "fk_ingreso_asignacion_ambiente" FOREIGN KEY ("id_asignacion_ambiente", "id_examen") REFERENCES "asignacion_ambiente"("id_asignacion_ambiente", "id_examen") ON DELETE NO ACTION ON UPDATE NO ACTION;

-- AddForeignKey
ALTER TABLE "ingreso" ADD CONSTRAINT "fk_ingreso_habilitacion_examen" FOREIGN KEY ("id_habilitacion", "id_examen") REFERENCES "habilitacion"("id_habilitacion", "id_examen") ON DELETE NO ACTION ON UPDATE NO ACTION;

-- AddForeignKey
ALTER TABLE "ingreso" ADD CONSTRAINT "fk_ingreso_usuario" FOREIGN KEY ("id_usuario") REFERENCES "usuario"("id_usuario") ON DELETE NO ACTION ON UPDATE NO ACTION;

-- AddForeignKey
ALTER TABLE "intento_ingreso" ADD CONSTRAINT "fk_intento_estudiante" FOREIGN KEY ("id_estudiante") REFERENCES "estudiante"("id_estudiante") ON DELETE NO ACTION ON UPDATE NO ACTION;

-- AddForeignKey
ALTER TABLE "intento_ingreso" ADD CONSTRAINT "fk_intento_examen" FOREIGN KEY ("id_examen") REFERENCES "examen"("id_examen") ON DELETE NO ACTION ON UPDATE NO ACTION;

-- AddForeignKey
ALTER TABLE "intento_ingreso" ADD CONSTRAINT "fk_intento_ingreso_asignacion_ambiente" FOREIGN KEY ("id_asignacion_ambiente", "id_examen") REFERENCES "asignacion_ambiente"("id_asignacion_ambiente", "id_examen") ON DELETE NO ACTION ON UPDATE NO ACTION;

-- AddForeignKey
ALTER TABLE "intento_ingreso" ADD CONSTRAINT "fk_intento_usuario" FOREIGN KEY ("id_usuario") REFERENCES "usuario"("id_usuario") ON DELETE NO ACTION ON UPDATE NO ACTION;

-- AddForeignKey
ALTER TABLE "regla_examen" ADD CONSTRAINT "fk_regla_examen_examen" FOREIGN KEY ("id_examen") REFERENCES "examen"("id_examen") ON DELETE NO ACTION ON UPDATE NO ACTION;

-- AddForeignKey
ALTER TABLE "regla_individual" ADD CONSTRAINT "fk_regla_individual_habilitacion" FOREIGN KEY ("id_habilitacion") REFERENCES "habilitacion"("id_habilitacion") ON DELETE NO ACTION ON UPDATE NO ACTION;

-- AddForeignKey
ALTER TABLE "rol_permiso" ADD CONSTRAINT "fk_rol_permiso_permiso" FOREIGN KEY ("id_permiso") REFERENCES "permiso"("id_permiso") ON DELETE CASCADE ON UPDATE NO ACTION;

-- AddForeignKey
ALTER TABLE "rol_permiso" ADD CONSTRAINT "fk_rol_permiso_rol" FOREIGN KEY ("id_rol") REFERENCES "rol"("id_rol") ON DELETE CASCADE ON UPDATE NO ACTION;

-- AddForeignKey
ALTER TABLE "usuario" ADD CONSTRAINT "fk_usuario_rol" FOREIGN KEY ("id_rol") REFERENCES "rol"("id_rol") ON DELETE NO ACTION ON UPDATE NO ACTION;
