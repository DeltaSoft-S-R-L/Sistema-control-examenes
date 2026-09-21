"use client";

import { FormEvent, useState } from "react";
import styles from "./page.module.css";

type ErroresFormulario = {
  ci?: string;
  codigoUniversitario?: string;
  nombre?: string;
  apellido?: string;
  correo?: string;
  estado?: string;
};

export default function NuevoEstudiantePage() {
  const [errores, setErrores] = useState<ErroresFormulario>({});
  const [mensaje, setMensaje] = useState("");

  const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();

    const form = event.currentTarget;
    const formData = new FormData(form);

    const ci = String(formData.get("ci") ?? "").trim();

    const codigoUniversitario = String(
      formData.get("codigoUniversitario") ?? ""
    ).trim();

    const nombre = String(formData.get("nombre") ?? "").trim();
    const apellido = String(formData.get("apellido") ?? "").trim();
    const correo = String(formData.get("correo") ?? "").trim();
    const estado = String(formData.get("estado") ?? "").trim();

    const nuevosErrores: ErroresFormulario = {};

    /*
     * CI
     */
    if (!ci) {
      nuevosErrores.ci = "El documento de identidad es obligatorio.";
    }

    /*
     * Código universitario
     */
    if (!codigoUniversitario) {
      nuevosErrores.codigoUniversitario =
        "El código universitario es obligatorio.";
    }

    /*
     * Nombre y apellido
     *
     * Permitimos:
     * - Letras
     * - Tildes
     * - Ñ
     * - Espacios
     * - Apóstrofes
     * - Guiones
     */
    const formatoNombre = /^[A-Za-zÁÉÍÓÚáéíóúÑñÜü\s'-]+$/;

    if (!nombre) {
      nuevosErrores.nombre = "El nombre es obligatorio.";
    } else if (!formatoNombre.test(nombre)) {
      nuevosErrores.nombre =
        "El nombre solo puede contener letras, espacios, apóstrofes y guiones.";
    }

    if (!apellido) {
      nuevosErrores.apellido = "El apellido es obligatorio.";
    } else if (!formatoNombre.test(apellido)) {
      nuevosErrores.apellido =
        "El apellido solo puede contener letras, espacios, apóstrofes y guiones.";
    }

    /*
     * Correo
     *
     * Es opcional, pero si el usuario escribe uno
     * debe tener un formato válido.
     */
    if (correo) {
      const formatoCorreo = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

      if (!formatoCorreo.test(correo)) {
        nuevosErrores.correo =
          "Ingresa un correo electrónico válido.";
      }
    }

    /*
     * Estado
     */
    if (!estado) {
      nuevosErrores.estado = "Debes seleccionar un estado.";
    }

    setErrores(nuevosErrores);
    setMensaje("");

    /*
     * Si existe algún error detenemos el envío.
     */
    if (Object.keys(nuevosErrores).length > 0) {
      return;
    }

    /*
     * Por ahora NO se envían datos al backend.
     * Únicamente confirmamos que el formulario
     * pasó las validaciones del frontend.
     */
    setMensaje(
      "Datos validados correctamente. El registro aún no se ha enviado al servidor."
    );
  };

  return (
    <main className={styles.page}>
      <div className={styles.container}>
        <div className={styles.header}>
          <div>
            <p className={styles.section}>ESTUDIANTES</p>

            <h1>Registrar estudiante</h1>

            <p className={styles.description}>
              Ingresa los datos del estudiante que participará en los exámenes.
            </p>
          </div>
        </div>

        <div className={styles.card}>
          <div className={styles.cardHeader}>
            <h2>Datos del estudiante</h2>

            <p>
              Los campos marcados con <span>*</span> son obligatorios.
            </p>
          </div>

          <form
            className={styles.form}
            onSubmit={handleSubmit}
            noValidate
          >
            <div className={styles.grid}>

              {/* CI */}
              <div className={styles.field}>
                <label htmlFor="ci">
                  Documento de identidad (CI) <span>*</span>
                </label>

                <input
                  id="ci"
                  name="ci"
                  type="text"
                  placeholder="Ej. 12345678"
                  maxLength={20}
                  className={errores.ci ? styles.inputError : ""}
                />

                {errores.ci && (
                  <p className={styles.errorMessage}>
                    {errores.ci}
                  </p>
                )}
              </div>

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
                  maxLength={50}
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
                  className={
                    errores.nombre ? styles.inputError : ""
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
                  className={
                    errores.apellido ? styles.inputError : ""
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
                  className={
                    errores.correo ? styles.inputError : ""
                  }
                />

                {errores.correo && (
                  <p className={styles.errorMessage}>
                    {errores.correo}
                  </p>
                )}
              </div>

              {/* Estado */}
              <div className={styles.field}>
                <label htmlFor="estado">
                  Estado <span>*</span>
                </label>

                <select
                  id="estado"
                  name="estado"
                  defaultValue="ACTIVO"
                  className={
                    errores.estado ? styles.inputError : ""
                  }
                >
                  <option value="ACTIVO">
                    Activo
                  </option>

                  <option value="INACTIVO">
                    Inactivo
                  </option>
                </select>

                {errores.estado && (
                  <p className={styles.errorMessage}>
                    {errores.estado}
                  </p>
                )}
              </div>
            </div>

            {/* Mensaje de validación correcta */}
            {mensaje && (
              <div className={styles.message}>
                {mensaje}
              </div>
            )}

            {/* Botones */}
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
                Registrar estudiante
              </button>
            </div>
          </form>
        </div>
      </div>
    </main>
  );
}