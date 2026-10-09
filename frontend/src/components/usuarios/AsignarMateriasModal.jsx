
import React, { useEffect, useState } from "react";
import api from "../../services/api";

export default function AsignarMateriasModal({
  mostrar,
  docente,
  onCerrar,
}) {
  const [carreras, setCarreras] = useState([]);
  const [idCarrera, setIdCarrera] = useState("");
  const [materias, setMaterias] = useState([]);
  const [materiasSeleccionadas, setMateriasSeleccionadas] = useState([]);
  const [cargando, setCargando] = useState(false);
  const [cargandoMaterias, setCargandoMaterias] = useState(false);
  const [guardando, setGuardando] = useState(false);
  const [error, setError] = useState("");
  const [mensaje, setMensaje] = useState("");

  useEffect(() => {
    if (!mostrar || !docente) return;

    let vigente = true;

    setIdCarrera("");
    setMaterias([]);
    setMateriasSeleccionadas([]);
    setCarreras([]);
    setError("");
    setMensaje("");
    setCargando(true);

    Promise.all([
      api.get("/carreras"),
      api.get(`/usuarios/${docente.id_usuario}/materias`),
    ])
      .then(([respuestaCarreras, respuestaAsignaciones]) => {
        if (!vigente) return;

        setCarreras(respuestaCarreras.data);

        const asignaciones = respuestaAsignaciones.data.materias.map(
          (materia) => ({
            id_carrera: Number(materia.id_carrera),
            id_asignatura: Number(materia.id_asignatura),
          })
        );

        setMateriasSeleccionadas(asignaciones);
      })
      .catch(() => {
        if (vigente) {
          setError("No se pudieron cargar las asignaciones del docente.");
        }
      })
      .finally(() => {
        if (vigente) setCargando(false);
      });

    return () => {
      vigente = false;
    };
  }, [mostrar, docente?.id_usuario]);

  useEffect(() => {
    if (!mostrar || !idCarrera) {
      setMaterias([]);
      return;
    }

    let vigente = true;

    setCargandoMaterias(true);
    setMaterias([]);
    setError("");

    api.get("/asignaturas", {
      params: { id_carrera: idCarrera },
    })
      .then((response) => {
        if (vigente) setMaterias(response.data);
      })
      .catch(() => {
        if (vigente) {
          setError("No se pudieron cargar las materias de esta carrera.");
        }
      })
      .finally(() => {
        if (vigente) setCargandoMaterias(false);
      });

    return () => {
      vigente = false;
    };
  }, [mostrar, idCarrera]);

  const estaSeleccionada = (idAsignatura) =>
    materiasSeleccionadas.some(
      (materia) =>
        materia.id_carrera === Number(idCarrera) &&
        materia.id_asignatura === Number(idAsignatura)
    );

  const seleccionarMateria = (idAsignatura) => {
    const idCarreraActual = Number(idCarrera);
    const idMateriaActual = Number(idAsignatura);

    setMateriasSeleccionadas((anteriores) => {
      const existe = anteriores.some(
        (materia) =>
          materia.id_carrera === idCarreraActual &&
          materia.id_asignatura === idMateriaActual
      );

      if (existe) {
        return anteriores.filter(
          (materia) =>
            !(
              materia.id_carrera === idCarreraActual &&
              materia.id_asignatura === idMateriaActual
            )
        );
      }

      return [
        ...anteriores,
        {
          id_carrera: idCarreraActual,
          id_asignatura: idMateriaActual,
        },
      ];
    });

    setMensaje("");
  };

  const guardarAsignaciones = async () => {
    if (cargando || cargandoMaterias || guardando) return;

    setGuardando(true);
    setError("");
    setMensaje("");

    try {
      await api.put(
        `/usuarios/${docente.id_usuario}/materias`,
        {
          materias: materiasSeleccionadas,
        }
      );

      setMensaje("Materias asignadas correctamente.");
    } catch (err) {
      setError(
        err.response?.data?.message ||
          "No se pudieron guardar las asignaciones."
      );
    } finally {
      setGuardando(false);
    }
  };

  if (!mostrar || !docente) return null;

  const cantidadCarrera = materiasSeleccionadas.filter(
    (materia) => materia.id_carrera === Number(idCarrera)
  ).length;

  return (
    <div
      className="modal d-block"
      tabIndex="-1"
      role="dialog"
      aria-modal="true"
      style={{ backgroundColor: "rgba(0, 0, 0, 0.5)" }}
    >
      <div className="modal-dialog modal-dialog-centered modal-lg">
        <div className="modal-content">
          <div className="modal-header">
            <h5 className="modal-title">
              <i className="bi bi-book me-2"></i>
              Asignar materias
            </h5>
            <button
              type="button"
              className="btn-close"
              onClick={onCerrar}
              disabled={guardando}
              aria-label="Cerrar"
            />
          </div>

          <div className="modal-body">
            <div className="alert alert-light border">
              <strong>Docente:</strong>{" "}
              {docente.nombre} {docente.apellido}
            </div>

            {error && (
              <div className="alert alert-danger">{error}</div>
            )}

            {mensaje && (
              <div className="alert alert-success">{mensaje}</div>
            )}

            <div className="mb-3">
              <label className="form-label fw-semibold">
                Carrera
              </label>

              <select
                className="form-select"
                value={idCarrera}
                onChange={(event) => {
                  setIdCarrera(event.target.value);
                  setMensaje("");
                }}
                disabled={cargando || guardando}
              >
                <option value="">
                  {cargando
                    ? "Cargando carreras..."
                    : "Seleccionar carrera"}
                </option>

                {carreras.map((carrera) => (
                  <option
                    key={carrera.id_carrera}
                    value={carrera.id_carrera}
                  >
                    {carrera.nombre}
                  </option>
                ))}
              </select>
            </div>

            <div className="mb-3">
              <label className="form-label fw-semibold">
                Materias
              </label>

              <div
                className="border rounded p-3"
                style={{ maxHeight: "260px", overflowY: "auto" }}
              >
                {!idCarrera ? (
                  <span className="text-muted">
                    Selecciona una carrera para ver sus materias.
                  </span>
                ) : cargandoMaterias ? (
                  <span className="text-muted">
                    Cargando materias...
                  </span>
                ) : materias.length === 0 ? (
                  <span className="text-muted">
                    No hay materias disponibles para esta carrera.
                  </span>
                ) : (
                  materias.map((materia) => (
                    <div
                      className="form-check mb-2"
                      key={materia.id_asignatura}
                    >
                      <input
                        className="form-check-input"
                        type="checkbox"
                        id={`materia-${materia.id_asignatura}`}
                        checked={estaSeleccionada(
                          materia.id_asignatura
                        )}
                        disabled={cargando || guardando}
                        onChange={() =>
                          seleccionarMateria(materia.id_asignatura)
                        }
                      />
                      <label
                        className="form-check-label"
                        htmlFor={`materia-${materia.id_asignatura}`}
                      >
                        {materia.codigo} - {materia.nombre}
                      </label>
                    </div>
                  ))
                )}
              </div>
            </div>

            <div className="text-muted small">
              Materias seleccionadas en esta carrera:{" "}
              <strong>{cantidadCarrera}</strong>
            </div>

            <div className="text-muted small mt-1">
              Total de asignaciones en todas las carreras:{" "}
              <strong>{materiasSeleccionadas.length}</strong>
            </div>
          </div>

          <div className="modal-footer">
            <button
              type="button"
              className="btn btn-secondary"
              onClick={onCerrar}
              disabled={guardando}
            >
              Cerrar
            </button>

            <button
              type="button"
              className="btn btn-primary"
              onClick={guardarAsignaciones}
              disabled={cargando || cargandoMaterias || guardando || !!error}
            >
              {guardando
                ? "Guardando..."
                : "Guardar asignaciones"}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
