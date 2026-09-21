"use client";

import { FormEvent, useState } from "react";
import styles from "./page.module.css";

type ErroresFormulario = {
  ci?: string;
  codigoUniversitario?: string;
  nombre?: string;
  apellido?: string;
  correo?: string;
  facultad?: string;
  carrera?: string;
  rol?: string;
  estado?: string;
};

type Facultad = {
  nombre: string;
  carreras: string[];
};

/*
 * Datos temporales para el frontend.
 *
 * Cuando el backend implemente la administración de facultades
 * y carreras, estos datos podrán obtenerse desde la API.
 */
const facultades: Record<string, Facultad> = {
  CIENCIAS_TECNOLOGIA: {
    nombre: "Facultad de Ciencias y Tecnología",
    carreras: [
      "Ingeniería de Sistemas",
      "Ingeniería Informática",
      "Ingeniería Electrónica",
      "Ingeniería Civil",
      "Ingeniería Industrial",
    ],
  },

  CIENCIAS_ECONOMICAS: {
    nombre: "Facultad de Ciencias Económicas",
    carreras: [
      "Administración de Empresas",
      "Economía",
      "Contaduría Pública",
      "Ingeniería Comercial",
      "Ingeniería Financiera",
    ],
  },

  CIENCIAS_SALUD: {
    nombre: "Facultad de Ciencias de la Salud",
    carreras: [
      "Medicina",
      "Enfermería",
      "Nutrición y Dietética",
      "Fisioterapia y Kinesiología",
      "Odontología",
    ],
  },

  HUMANIDADES: {
    nombre: "Facultad de Humanidades",
    carreras: [
      "Psicología",
      "Ciencias de la Educación",
      "Lingüística",
      "Trabajo Social",
      "Comunicación Social",
    ],
  },
};

export default function NuevoEstudiantePage() {
  const [errores, setErrores] = useState<ErroresFormulario>({});
  const [mensaje, setMensaje] = useState("");

  const [estadoSeleccionado, setEstadoSeleccionado] =
    useState("ACTIVO");

  const [facultadSeleccionada, setFacultadSeleccionada] =
    useState("");

  const [carreraSeleccionada, setCarreraSeleccionada] =
    useState("");

  /*
   * Obtiene las carreras correspondientes
   * a la facultad seleccionada.
   */
  const carrerasDisponibles = facultadSeleccionada
    ? facultades[facultadSeleccionada]?.carreras ?? []
    : [];

  /*
   * Cuando cambia la facultad, se limpia la carrera
   * anteriormente seleccionada.
   */
  const handleFacultadChange = (
    event: React.ChangeEvent<HTMLSelectElement>
  ) => {
    setFacultadSeleccionada(event.target.value);
    setCarreraSeleccionada("");

    setErrores((erroresActuales) => ({
      ...erroresActuales,
      facultad: undefined,
      carrera: undefined,
    }));
  };

  const handleCarreraChange = (
    event: React.ChangeEvent<HTMLSelectElement>
  ) => {
    setCarreraSeleccionada(event.target.value);

    setErrores((erroresActuales) => ({
      ...erroresActuales,
      carrera: undefined,
    }));
  };

  const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();

    const formData = new FormData(event.currentTarget);

    const ci = String(
      formData.get("ci") ?? ""
    ).trim();

    const codigoUniversitario = String(
      formData.get("codigoUniversitario") ?? ""
    ).trim();

    const nombre = String(
      formData.get("nombre") ?? ""
    ).trim();

    const apellido = String(
      formData.get("apellido") ?? ""
    ).trim();

    const correo = String(
      formData.get("correo") ?? ""
    ).trim();

    const facultad = String(
      formData.get("facultad") ?? ""
    ).trim();

    const carrera = String(
      formData.get("carrera") ?? ""
    ).trim();

    const rol = String(
      formData.get("rol") ?? ""
    ).trim();

    const estado = String(
      formData.get("estado") ?? ""
    ).trim();

    const nuevosErrores: ErroresFormulario = {};

    /*
     * Formatos permitidos
     */
    const formatoIdentificador = /^[A-Za-z0-9-]+$/;

    const formatoNombre =
      /^[A-Za-zÁÉÍÓÚáéíóúÑñÜü\s'-]+$/;

    const formatoCorreo =
      /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    /*
     * Código universitario
     */
    if (!codigoUniversitario) {
      nuevosErrores.codigoUniversitario =
        "El código universitario es obligatorio.";
    } else if (codigoUniversitario.length > 15) {
      nuevosErrores.codigoUniversitario =
        "El código universitario no puede superar los 15 caracteres.";
    } else if (
      !formatoIdentificador.test(codigoUniversitario)
    ) {
      nuevosErrores.codigoUniversitario =
        "El código solo puede contener letras, números y guiones.";
    }

    /*
     * Documento de identidad
     */
    if (!ci) {
      nuevosErrores.ci =
        "El documento de identidad es obligatorio.";
    } else if (ci.length > 15) {
      nuevosErrores.ci =
        "El CI no puede superar los 15 caracteres.";
    } else if (!formatoIdentificador.test(ci)) {
      nuevosErrores.ci =
        "El CI solo puede contener letras, números y guiones.";
    }

    /*
     * Nombre
     */
    if (!nombre) {
      nuevosErrores.nombre =
        "El nombre es obligatorio.";
    } else if (!formatoNombre.test(nombre)) {
      nuevosErrores.nombre =
        "El nombre solo puede contener letras, espacios, apóstrofes y guiones.";
    }

    /*
     * Apellido
     */
    if (!apellido) {
      nuevosErrores.apellido =
        "El apellido es obligatorio.";
    } else if (!formatoNombre.test(apellido)) {
      nuevosErrores.apellido =
        "El apellido solo puede contener letras, espacios, apóstrofes y guiones.";
    }

    /*
     * Correo electrónico
     *
     * Es opcional, pero si se ingresa
     * debe tener un formato válido.
     */
    if (correo && !formatoCorreo.test(correo)) {
      nuevosErrores.correo =
        "Ingresa un correo electrónico válido.";
    }

    /*
     * Facultad
     */
    if (!facultad) {
      nuevosErrores.facultad =
        "Debes seleccionar una facultad.";
    }

    /*
     * Carrera
     */
    if (!carrera) {
      nuevosErrores.carrera =
        "Debes seleccionar una carrera.";
    }

    /*
     * Rol
     *
     * En esta HU el registro corresponde
     * exclusivamente a estudiantes.
     */
    if (!rol) {
      nuevosErrores.rol =
        "El rol del estudiante es obligatorio.";
    }

    /*
     * Estado
     */
    if (!estado) {
      nuevosErrores.estado =
        "Debes seleccionar un estado.";
    }

    setErrores(nuevosErrores);
    setMensaje("");

    /*
     * Si existe algún error,
     * detenemos el proceso.
     */
    if (Object.keys(nuevosErrores).length > 0) {
      return;
    }

    /*
     * Por ahora el frontend solamente valida.
     *
     * Cuando exista el endpoint correspondiente,
     * aquí se enviarán los datos al backend.
     */
    setMensaje(
      "Datos validados correctamente. El registro aún no se ha enviado al servidor."
    );
  };

  return (
    <main className={styles.page}>
      {/* Fondo provisional */}
      <div className={styles.backgroundContent}>
        <div className={styles.fakeHeader}>
          <div>
            <span className={styles.fakeSection}>
              ESTUDIANTES
            </span>

            <h1>Gestión de estudiantes</h1>

            <p>
              Administra los estudiantes registrados en el sistema.
            </p>
          </div>

          <div className={styles.fakeButton}>
            + Nuevo estudiante
          </div>
        </div>

        <div className={styles.fakeTable}>
          <div />
          <div />
          <div />
          <div />
        </div>
      </div>

      {/* Modal */}
      <div className={styles.overlay}>
        <section
          className={styles.modal}
          role="dialog"
          aria-modal="true"
          aria-labelledby="modal-title"
        >
          {/* Encabezado */}
          <div className={styles.modalHeader}>
            <div className={styles.title}>
              <div className={styles.icon}>
                ♙
              </div>

              <h1 id="modal-title">
                Nuevo Estudiante
              </h1>
            </div>

            <button
              type="button"
              className={styles.closeButton}
              onClick={() => window.history.back()}
              aria-label="Cerrar formulario"
            >
              ×
            </button>
          </div>

          {/* Formulario */}
          <form
            className={styles.form}
            onSubmit={handleSubmit}
            noValidate
          >
            <div className={styles.grid}>
              {/* Código universitario */}
              <div className={styles.field}>
                <label htmlFor="codigoUniversitario">
                  Código universitario <span>*</span>
                </label>

                <input
                  id="codigoUniversitario"
                  name="codigoUniversitario"
                  type="text"
                  placeholder="Ej. 202401234"
                  maxLength={15}
                  autoComplete="off"
                  className={
                    errores.codigoUniversitario
                      ? styles.inputError
                      : ""
                  }
                />

                {errores.codigoUniversitario && (
                  <p className={styles.errorMessage}>
                    {errores.codigoUniversitario}
                  </p>
                )}
              </div>

              {/* CI */}
              <div className={styles.field}>
                <label htmlFor="ci">
                  Documento de identidad (CI){" "}
                  <span>*</span>
                </label>

                <input
                  id="ci"
                  name="ci"
                  type="text"
                  placeholder="Ej. 12345678"
                  maxLength={15}
                  autoComplete="off"
                  className={
                    errores.ci
                      ? styles.inputError
                      : ""
                  }
                />

                {errores.ci && (
                  <p className={styles.errorMessage}>
                    {errores.ci}
                  </p>
                )}
              </div>

              {/* Nombre */}
              <div className={styles.field}>
                <label htmlFor="nombre">
                  Nombre <span>*</span>
                </label>

                <input
                  id="nombre"
                  name="nombre"
                  type="text"
                  placeholder="Ej. Juan"
                  maxLength={100}
                  autoComplete="given-name"
                  className={
                    errores.nombre
                      ? styles.inputError
                      : ""
                  }
                />

                {errores.nombre && (
                  <p className={styles.errorMessage}>
                    {errores.nombre}
                  </p>
                )}
              </div>

              {/* Apellido */}
              <div className={styles.field}>
                <label htmlFor="apellido">
                  Apellido <span>*</span>
                </label>

                <input
                  id="apellido"
                  name="apellido"
                  type="text"
                  placeholder="Ej. Pérez"
                  maxLength={100}
                  autoComplete="family-name"
                  className={
                    errores.apellido
                      ? styles.inputError
                      : ""
                  }
                />

                {errores.apellido && (
                  <p className={styles.errorMessage}>
                    {errores.apellido}
                  </p>
                )}
              </div>

              {/* Correo */}
              <div
                className={`${styles.field} ${styles.fullWidth}`}
              >
                <label htmlFor="correo">
                  Correo electrónico
                </label>

                <input
                  id="correo"
                  name="correo"
                  type="email"
                  placeholder="Ej. estudiante@universidad.edu"
                  maxLength={150}
                  autoComplete="email"
                  className={
                    errores.correo
                      ? styles.inputError
                      : ""
                  }
                />

                {errores.correo && (
                  <p className={styles.errorMessage}>
                    {errores.correo}
                  </p>
                )}
              </div>

              {/* Facultad */}
              <div className={styles.field}>
                <label htmlFor="facultad">
                  Facultad <span>*</span>
                </label>

                <select
                  id="facultad"
                  name="facultad"
                  value={facultadSeleccionada}
                  onChange={handleFacultadChange}
                  className={
                    errores.facultad
                      ? styles.inputError
                      : ""
                  }
                >
                  <option value="">
                    Seleccionar facultad...
                  </option>

                  {Object.entries(facultades).map(
                    ([codigo, facultad]) => (
                      <option
                        key={codigo}
                        value={codigo}
                      >
                        {facultad.nombre}
                      </option>
                    )
                  )}
                </select>

                {errores.facultad && (
                  <p className={styles.errorMessage}>
                    {errores.facultad}
                  </p>
                )}
              </div>

              {/* Carrera */}
              <div className={styles.field}>
                <label htmlFor="carrera">
                  Carrera <span>*</span>
                </label>

                <select
                  id="carrera"
                  name="carrera"
                  value={carreraSeleccionada}
                  onChange={handleCarreraChange}
                  disabled={!facultadSeleccionada}
                  className={
                    errores.carrera
                      ? styles.inputError
                      : ""
                  }
                >
                  <option value="">
                    {facultadSeleccionada
                      ? "Seleccionar carrera..."
                      : "Primero selecciona una facultad"}
                  </option>

                  {carrerasDisponibles.map(
                    (carrera) => (
                      <option
                        key={carrera}
                        value={carrera}
                      >
                        {carrera}
                      </option>
                    )
                  )}
                </select>

                {errores.carrera && (
                  <p className={styles.errorMessage}>
                    {errores.carrera}
                  </p>
                )}
              </div>

              {/* Rol */}
              <div
                className={`${styles.field} ${styles.fullWidth}`}
              >
                <label htmlFor="rol">
                  Rol <span>*</span>
                </label>

                <select
                  id="rol"
                  name="rol"
                  value="ESTUDIANTE"
                  disabled
                  className={styles.rolSelect}
                >
                  <option value="ESTUDIANTE">
                    Estudiante
                  </option>
                </select>

                {/*
                  Los controles disabled no forman parte de FormData.
                  Este input oculto permite conservar ESTUDIANTE
                  como valor del formulario.
                */}
                <input
                  type="hidden"
                  name="rol"
                  value="ESTUDIANTE"
                />

                <p className={styles.helperText}>
                  El rol se asigna automáticamente al registrar
                  un estudiante.
                </p>

                {errores.rol && (
                  <p className={styles.errorMessage}>
                    {errores.rol}
                  </p>
                )}
              </div>

              {/* Estado */}
              <div
                className={`${styles.field} ${styles.fullWidth}`}
              >
                <label htmlFor="estado">
                  Estado <span>*</span>
                </label>

                <select
                  id="estado"
                  name="estado"
                  value={estadoSeleccionado}
                  onChange={(event) =>
                    setEstadoSeleccionado(
                      event.target.value
                    )
                  }
                  className={`${styles.estadoSelect} ${
                    estadoSeleccionado === "ACTIVO"
                      ? styles.estadoActivo
                      : styles.estadoInactivo
                  } ${
                    errores.estado
                      ? styles.inputError
                      : ""
                  }`}
                >
                  <option
                    value="ACTIVO"
                    className={styles.opcionActiva}
                  >
                    ● Activo
                  </option>

                  <option
                    value="INACTIVO"
                    className={styles.opcionInactiva}
                  >
                    ● Inactivo
                  </option>
                </select>

                {errores.estado && (
                  <p className={styles.errorMessage}>
                    {errores.estado}
                  </p>
                )}
              </div>
            </div>

            {/* Información */}
            <div className={styles.infoBox}>
              <span className={styles.infoIcon}>
                ⓘ
              </span>

              <p>
                El estudiante quedará disponible en el
                sistema una vez completado el registro.
              </p>
            </div>

            {/* Mensaje temporal */}
            {mensaje && (
              <div className={styles.message}>
                {mensaje}
              </div>
            )}

            {/* Acciones */}
            <div className={styles.actions}>
              <button
                type="button"
                className={styles.cancelButton}
                onClick={() => window.history.back()}
              >
                Cancelar
              </button>

              <button
                type="submit"
                className={styles.submitButton}
              >
                Guardar estudiante
              </button>
            </div>
          </form>
        </section>
      </div>
    </main>
  );
}