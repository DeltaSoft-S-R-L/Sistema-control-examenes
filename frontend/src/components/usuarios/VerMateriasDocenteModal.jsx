
import React, { useEffect, useState } from "react";
import api from "../../services/api";

export default function VerMateriasDocenteModal({
  mostrar,
  docente,
  onCerrar,
}) {
  const [materias, setMaterias] = useState([]);
  const [cargando, setCargando] = useState(false);
  const [error, setError] = useState("");

  useEffect(() => {
    if (!mostrar || !docente) return;

    let vigente = true;

    setMaterias([]);
    setError("");
    setCargando(true);

    api
      .get(`/usuarios/${docente.id_usuario}/materias`)
      .then((response) => {
        if (vigente) {
          setMaterias(response.data.materias || []);
        }
      })
      .catch(() => {
        if (vigente) {
          setError("No se pudieron consultar las materias del docente.");
        }
      })
      .finally(() => {
        if (vigente) setCargando(false);
      });

    return () => {
      vigente = false;
    };
  }, [mostrar, docente?.id_usuario]);

  if (!mostrar || !docente) return null;

  const materiasPorCarrera = materias.reduce((grupos, materia) => {
    const idCarrera = materia.id_carrera;

    if (!grupos[idCarrera]) {
      grupos[idCarrera] = {
        nombre: materia.carrera,
        materias: [],
      };
    }

    grupos[idCarrera].materias.push(materia);
    return grupos;
  }, {});

  const carreras = Object.values(materiasPorCarrera);

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
              <i className="bi bi-eye me-2"></i>
              Materias del docente
            </h5>

            <button
              type="button"
              className="btn-close"
              onClick={onCerrar}
              aria-label="Cerrar"
            />
          </div>

          <div className="modal-body">
            <div className="alert alert-light border">
              <div className="text-muted small mb-1">
                DOCENTE
              </div>

              <strong>
                {docente.nombre} {docente.apellido}
              </strong>
            </div>

            {cargando ? (
              <div className="text-center py-4">
                <div
                  className="spinner-border text-primary"
                  role="status"
                >
                  <span className="visually-hidden">
                    Cargando...
                  </span>
                </div>
                <p className="text-muted mt-2">
                  Consultando materias asignadas...
                </p>
              </div>
            ) : error ? (
              <div className="alert alert-danger">
                {error}
              </div>
            ) : materias.length === 0 ? (
              <div className="alert alert-info">
                Este docente todavía no tiene materias asignadas.
              </div>
            ) : (
              <>
                {carreras.map((carrera) => (
                  <div
                    className="card border mb-3"
                    key={carrera.nombre}
                  >
                    <div className="card-header bg-light d-flex justify-content-between align-items-center">
                      <strong>{carrera.nombre}</strong>
                      <span className="badge text-bg-secondary">
                        {carrera.materias.length}{" "}
                        {carrera.materias.length === 1
                          ? "materia"
                          : "materias"}
                      </span>
                    </div>

                    <ul className="list-group list-group-flush">
                      {carrera.materias.map((materia) => (
                        <li
                          className="list-group-item"
                          key={`${materia.id_carrera}-${materia.id_asignatura}`}
                        >
                          <i className="bi bi-book me-2 text-primary"></i>
                          <span className="text-muted me-2">
                            {materia.codigo}
                          </span>
                          {materia.asignatura}
                        </li>
                      ))}
                    </ul>
                  </div>
                ))}

                <div className="d-flex justify-content-between border-top pt-3">
                  <span className="fw-semibold">
                    Total de asignaciones
                  </span>
                  <strong>{materias.length}</strong>
                </div>
              </>
            )}
          </div>

          <div className="modal-footer">
            <button
              type="button"
              className="btn btn-secondary"
              onClick={onCerrar}
            >
              Cerrar
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
