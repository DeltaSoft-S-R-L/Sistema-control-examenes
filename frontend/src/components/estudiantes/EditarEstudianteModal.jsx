import React, { useEffect, useState } from "react";
import api from "../../services/api";

const formularioInicial = {
  ci: "",
  codigo_universitario: "",
  nombre: "",
  apellido: "",
  correo: "",
  estado: "ACTIVO",
};

export default function EditarEstudianteModal({
  mostrar,
  estudiante,
  onCerrar,
  onActualizado,
}) {
  const [formulario, setFormulario] = useState(formularioInicial);
  const [guardando, setGuardando] = useState(false);
  const [error, setError] = useState("");

  useEffect(() => {
    if (estudiante) {
      setFormulario({
        ci: estudiante.ci || "",
        codigo_universitario: estudiante.codigo_universitario || "",
        nombre: estudiante.nombre || "",
        apellido: estudiante.apellido || "",
        correo: estudiante.correo || "",
        estado: estudiante.estado?.toUpperCase() || "ACTIVO",
      });

      setError("");
    }
  }, [estudiante]);

  if (!mostrar || !estudiante) {
    return null;
  }

  const handleChange = (e) => {
    const { name, value } = e.target;

    setFormulario((anterior) => ({
      ...anterior,
      [name]: value,
    }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();

    try {
      setGuardando(true);
      setError("");

      const datos = {
        ci: formulario.ci.trim(),
        codigo_universitario: formulario.codigo_universitario.trim(),
        nombre: formulario.nombre.trim(),
        apellido: formulario.apellido.trim(),
        correo: formulario.correo.trim() || null,

        // El backend valida estados en mayúsculas (ACTIVO/INACTIVO).
        estado: formulario.estado.toUpperCase(),
      };

      const respuesta = await api.put(
        `/estudiantes/${estudiante.id_estudiante}`,
        datos,
      );

      onActualizado(respuesta.data);
      onCerrar();
    } catch (err) {
      console.error("Error al actualizar estudiante:", err);

      const erroresValidacion = err.response?.data?.errors;

      if (erroresValidacion) {
        const primerError = Object.values(erroresValidacion)[0];

        setError(
          Array.isArray(primerError)
            ? primerError[0]
            : "Revise los datos ingresados.",
        );
      } else {
        setError(
          err.response?.data?.message ||
            "No se pudo actualizar el estudiante.",
        );
      }
    } finally {
      setGuardando(false);
    }
  };

  return (
    <>
      <div
        className="modal fade show d-block"
        tabIndex="-1"
        role="dialog"
        aria-modal="true"
      >
        <div className="modal-dialog modal-lg modal-dialog-centered">
          <div className="modal-content border-0 shadow">
            <div className="modal-header bg-primary text-white">
              <div>
                <h5 className="modal-title fw-bold">
                  Editar estudiante
                </h5>

                <small className="text-white-50">
                  Modifique los datos del estudiante seleccionado.
                </small>
              </div>

              <button
                type="button"
                className="btn-close btn-close-white"
                onClick={onCerrar}
                disabled={guardando}
                aria-label="Cerrar"
              ></button>
            </div>

            <form onSubmit={handleSubmit}>
              <div className="modal-body p-4">
                {error && (
                  <div className="alert alert-danger" role="alert">
                    <i className="bi bi-exclamation-triangle-fill me-2"></i>
                    {error}
                  </div>
                )}

                <div className="row g-3">
                  <div className="col-md-6">
                    <label className="form-label fw-semibold">
                      CI *
                    </label>

                    <input
                      type="text"
                      className="form-control"
                      name="ci"
                      value={formulario.ci}
                      onChange={handleChange}
                      required
                      maxLength={20}
                    />
                  </div>

                  <div className="col-md-6">
                    <label className="form-label fw-semibold">
                      Código universitario *
                    </label>

                    <input
                      type="text"
                      className="form-control"
                      name="codigo_universitario"
                      value={formulario.codigo_universitario}
                      onChange={handleChange}
                      required
                      maxLength={50}
                    />
                  </div>

                  <div className="col-md-6">
                    <label className="form-label fw-semibold">
                      Nombre *
                    </label>

                    <input
                      type="text"
                      className="form-control"
                      name="nombre"
                      value={formulario.nombre}
                      onChange={handleChange}
                      required
                      maxLength={100}
                    />
                  </div>

                  <div className="col-md-6">
                    <label className="form-label fw-semibold">
                      Apellido *
                    </label>

                    <input
                      type="text"
                      className="form-control"
                      name="apellido"
                      value={formulario.apellido}
                      onChange={handleChange}
                      required
                      maxLength={100}
                    />
                  </div>

                  <div className="col-md-8">
                    <label className="form-label fw-semibold">
                      Correo
                    </label>

                    <input
                      type="email"
                      className="form-control"
                      name="correo"
                      value={formulario.correo}
                      onChange={handleChange}
                      maxLength={150}
                    />
                  </div>

                  <div className="col-md-4">
                    <label className="form-label fw-semibold">
                      Estado *
                    </label>

                    <select
                      className="form-select"
                      name="estado"
                      value={formulario.estado}
                      onChange={handleChange}
                      required
                    >
                      <option value="ACTIVO">Activo</option>
                      <option value="INACTIVO">Inactivo</option>
                    </select>
                  </div>
                </div>
              </div>

              <div className="modal-footer">
                <button
                  type="button"
                  className="btn btn-outline-secondary"
                  onClick={onCerrar}
                  disabled={guardando}
                >
                  Cancelar
                </button>

                <button
                  type="submit"
                  className="btn btn-primary"
                  disabled={guardando}
                >
                  {guardando ? (
                    <>
                      <span
                        className="spinner-border spinner-border-sm me-2"
                        role="status"
                      ></span>
                      Guardando...
                    </>
                  ) : (
                    <>
                      <i className="bi bi-floppy me-2"></i>
                      Guardar cambios
                    </>
                  )}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <div className="modal-backdrop fade show"></div>
    </>
  );
}