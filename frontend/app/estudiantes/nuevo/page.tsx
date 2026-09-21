"use client";

import { FormEvent, useState } from "react";
import styles from "./page.module.css";

export default function NuevoEstudiantePage() {
  const [mensaje, setMensaje] = useState("");

  const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();

    const form = event.currentTarget;

    if (!form.checkValidity()) {
      form.reportValidity();
      return;
    }

    setMensaje(
      "Formulario válido. La conexión con el backend se implementará posteriormente."
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

          <form className={styles.form} onSubmit={handleSubmit}>
            <div className={styles.grid}>
              <div className={styles.field}>
                <label htmlFor="ci">
                  Documento de identidad (CI) <span>*</span>
                </label>
                <input
                  id="ci"
                  name="ci"
                  type="text"
                  placeholder="Ej. 12345678"
                  required
                  maxLength={20}
                />
              </div>

              <div className={styles.field}>
                <label htmlFor="codigoUniversitario">
                  Código universitario <span>*</span>
                </label>
                <input
                  id="codigoUniversitario"
                  name="codigoUniversitario"
                  type="text"
                  placeholder="Ej. 202401234"
                  required
                  maxLength={50}
                />
              </div>

              <div className={styles.field}>
                <label htmlFor="nombre">
                  Nombre <span>*</span>
                </label>
                <input
                  id="nombre"
                  name="nombre"
                  type="text"
                  placeholder="Ej. Juan"
                  required
                  maxLength={100}
                />
              </div>

              <div className={styles.field}>
                <label htmlFor="apellido">
                  Apellido <span>*</span>
                </label>
                <input
                  id="apellido"
                  name="apellido"
                  type="text"
                  placeholder="Ej. Pérez"
                  required
                  maxLength={100}
                />
              </div>

              <div className={`${styles.field} ${styles.fullWidth}`}>
                <label htmlFor="correo">Correo electrónico</label>
                <input
                  id="correo"
                  name="correo"
                  type="email"
                  placeholder="Ej. estudiante@universidad.edu"
                  maxLength={150}
                />
              </div>

              <div className={styles.field}>
                <label htmlFor="estado">
                  Estado <span>*</span>
                </label>
                <select id="estado" name="estado" defaultValue="ACTIVO" required>
                  <option value="ACTIVO">Activo</option>
                  <option value="INACTIVO">Inactivo</option>
                </select>
              </div>
            </div>

            {mensaje && <div className={styles.message}>{mensaje}</div>}

            <div className={styles.actions}>
              <button
                type="button"
                className={styles.cancelButton}
                onClick={() => window.history.back()}
              >
                Cancelar
              </button>

              <button type="submit" className={styles.submitButton}>
                Registrar estudiante
              </button>
            </div>
          </form>
        </div>
      </div>
    </main>
  );
}