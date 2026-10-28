import React, { useEffect, useState } from "react";
import api from "../services/api";

export default function VerificacionMovil() {
  const [examenes, setExamenes] = useState([]);
  const [seleccionado, setSeleccionado] = useState(null);
  const [cargando, setCargando] = useState(true);
  const [cargandoMas, setCargandoMas] = useState(false);
  const [pagina, setPagina] = useState(1);
  const [ultimaPagina, setUltimaPagina] = useState(1);
  const [error, setError] = useState("");

  const cargarExamenes = async (numeroPagina = 1) => {
    try {
      numeroPagina === 1 ? setCargando(true) : setCargandoMas(true);

      setError("");

      const respuesta = await api.get("/examenes", {
        params: { page: numeroPagina },
      });

      const datos = respuesta.data;
      const lista = Array.isArray(datos.data)
        ? datos.data
        : Array.isArray(datos)
          ? datos
          : [];

      setExamenes((anteriores) =>
        numeroPagina === 1 ? lista : [...anteriores, ...lista],
      );

      setPagina(numeroPagina);
      setUltimaPagina(datos.last_page ?? 1);
    } catch (err) {
      setError(
        err.response?.data?.message || "No se pudieron cargar los exámenes.",
      );
    } finally {
      setCargando(false);
      setCargandoMas(false);
    }
  };

  useEffect(() => {
    cargarExamenes();
  }, []);

  const formatearFecha = (fecha) => {
    if (!fecha) return "Sin fecha";
    return String(fecha).slice(0, 10);
  };

  return (
    <div className="container-fluid px-0">
      <div className="mx-auto" style={{ maxWidth: 650 }}>
        <header className="mb-4">
          <h1 className="h3 fw-bold">Control de ingreso</h1>
          <p className="text-muted">
            Selecciona el examen que deseas verificar.
          </p>
        </header>

        {error && (
          <div className="alert alert-danger" role="alert">
            {error}
            <button
              type="button"
              className="btn btn-outline-danger btn-sm d-block mt-2"
              onClick={() => cargarExamenes(1)}
            >
              Reintentar
            </button>
          </div>
        )}

        {cargando ? (
          <p role="status">Cargando exámenes...</p>
        ) : (
          <>
            {examenes.length === 0 && !error && (
              <div className="alert alert-info">
                No hay exámenes disponibles.
              </div>
            )}

            <div className="d-grid gap-3">
              {examenes.map((examen) => {
                const activo = seleccionado?.id_examen === examen.id_examen;

                return (
                  <button
                    key={examen.id_examen}
                    type="button"
                    className={`card text-start shadow-sm w-100 ${
                      activo ? "border-primary border-2" : ""
                    }`}
                    onClick={() => setSeleccionado(examen)}
                    aria-pressed={activo}
                  >
                    <div className="card-body">
                      <div className="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                        <h2 className="h6 fw-bold mb-0 text-break">
                          {examen.nombre}
                        </h2>

                        <span className="badge bg-secondary">
                          {examen.estado}
                        </span>
                      </div>

                      <p className="mb-1 small">
                        <strong>Asignatura:</strong>{" "}
                        {examen.asignatura?.nombre ?? "No especificada"}
                      </p>

                      <p className="mb-1 small">
                        <strong>Fecha:</strong> {formatearFecha(examen.fecha)}
                      </p>

                      <p className="mb-0 small">
                        <strong>Hora:</strong>{" "}
                        {examen.hora_inicio ?? "No especificada"}
                      </p>

                      {activo && (
                        <div className="text-primary fw-semibold mt-3">
                          <i className="bi bi-check-circle-fill me-2" />
                          Examen seleccionado
                        </div>
                      )}
                    </div>
                  </button>
                );
              })}
            </div>

            {pagina < ultimaPagina && (
              <button
                type="button"
                className="btn btn-outline-primary w-100 mt-3"
                disabled={cargandoMas}
                onClick={() => cargarExamenes(pagina + 1)}
              >
                {cargandoMas ? "Cargando..." : "Cargar más exámenes"}
              </button>
            )}
          </>
        )}

        {seleccionado && (
          <div className="card border-primary mt-4">
            <div className="card-body">
              <h2 className="h5">Examen seleccionado</h2>
              <p className="mb-2">{seleccionado.nombre}</p>
              <p className="text-muted small mb-3">
                El lector QR se integrará en la tarea #130.
              </p>
              <button
                type="button"
                className="btn btn-outline-secondary w-100"
                onClick={() => setSeleccionado(null)}
              >
                Cambiar selección
              </button>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
