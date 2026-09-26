import React, { useEffect, useState } from "react";
import api from "../services/api";
import ImportarEstudiantesModal from "../components/estudiantes/ImportarEstudiantesModal";
import EditarEstudianteModal from "../components/estudiantes/EditarEstudianteModal";
import NuevoEstudianteModal from "../components/estudiantes/NuevoEstudianteModal";

export default function Estudiantes() {
  const [estudiantes, setEstudiantes] = useState([]);
  const [totalEstudiantes, setTotalEstudiantes] = useState(0);
  const [loading, setLoading] = useState(true);
  const [buscar, setBuscar] = useState("");
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);

  const [mostrarModal, setMostrarModal] = useState(false);
  const [mostrarNuevoModal, setMostrarNuevoModal] = useState(false);

  const [mostrarEditarModal, setMostrarEditarModal] = useState(false);
  const [estudianteSeleccionado, setEstudianteSeleccionado] = useState(null);

  const [mensaje, setMensaje] = useState("");

  const fetchEstudiantes = async (search = "", page = 1) => {
    try {
      setLoading(true);

      const params = { page };
      if (search) params.buscar = search;

      const res = await api.get("/estudiantes", { params });

      const datos = res.data.data || res.data || [];

      setEstudiantes(datos);
      setTotalEstudiantes(res.data.total ?? datos.length);
      setTotalPages(res.data.last_page ?? 1);
    } catch (err) {
      console.error("Error al obtener estudiantes:", err);
      setEstudiantes([]);
      setTotalEstudiantes(0);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchEstudiantes(buscar, currentPage);
  }, [currentPage]);

  const handleSearch = (e) => {
    e.preventDefault();
    setCurrentPage(1);
    fetchEstudiantes(buscar, 1);
  };

  const limpiarBusqueda = () => {
    setBuscar("");
    setCurrentPage(1);
    fetchEstudiantes("", 1);
  };

  const handleImportado = (resultado) => {
    setMensaje(
      `${resultado.importados} ${
        resultado.importados === 1
          ? "estudiante importado"
          : "estudiantes importados"
      } correctamente.`,
    );

    fetchEstudiantes(buscar, currentPage);

    window.setTimeout(() => {
      setMensaje("");
    }, 4000);
  };

  const handleRegistrado = () => {
    setMensaje("Estudiante registrado correctamente.");
    fetchEstudiantes(buscar, currentPage);
    window.setTimeout(() => {
      setMensaje("");
    }, 4000);
  };

  const abrirEditar = (estudiante) => {
    setEstudianteSeleccionado(estudiante);
    setMostrarEditarModal(true);
  };

  const cerrarEditar = () => {
    setMostrarEditarModal(false);
    setEstudianteSeleccionado(null);
  };

  const handleActualizado = () => {
    setMensaje("Estudiante actualizado correctamente.");

    fetchEstudiantes(buscar, currentPage);

    window.setTimeout(() => {
      setMensaje("");
    }, 4000);
  };

  const obtenerClaseEstado = (estado) => {
    switch (estado?.toUpperCase()) {
      case "ACTIVO":
        return "bg-success";

      case "INACTIVO":
        return "bg-secondary";

      default:
        return "bg-secondary";
    }
  };

  return (
    <div>
      {/* Encabezado */}
      <div className="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
          <h2 className="fw-bold mb-1">Estudiantes</h2>

          <p className="text-muted mb-0">
            Directorio de estudiantes habilitados para rendir exámenes
          </p>
        </div>

        <div className="d-flex gap-2">
          <button
            type="button"
            className="btn btn-primary"
            onClick={() => setMostrarNuevoModal(true)}
          >
            <i className="bi bi-person-plus me-2"></i>
            Nuevo estudiante
          </button>

          <button
            type="button"
            className="btn btn-outline-primary"
            onClick={() => setMostrarModal(true)}
          >
            <i className="bi bi-upload me-2"></i>
            Importar CSV
          </button>
        </div>
      </div>

      {/* Mensaje de éxito */}
      {mensaje && (
        <div
          className="alert alert-success alert-dismissible fade show"
          role="alert"
        >
          <i className="bi bi-check-circle-fill me-2"></i>

          {mensaje}

          <button
            type="button"
            className="btn-close"
            onClick={() => setMensaje("")}
            aria-label="Cerrar"
          ></button>
        </div>
      )}

      {/* Buscador */}
      <div className="card border-0 shadow-sm rounded-3 mb-4">
        <div className="card-body">
          <form onSubmit={handleSearch} className="row g-2 align-items-center">
            <div className="col-12 col-lg-6">
              <div className="input-group">
                <span className="input-group-text bg-light">
                  <i className="bi bi-search"></i>
                </span>

                <input
                  type="text"
                  className="form-control"
                  placeholder="Buscar por nombre, CI o código..."
                  value={buscar}
                  onChange={(e) => setBuscar(e.target.value)}
                />
              </div>
            </div>

            <div className="col-auto">
              <button type="submit" className="btn btn-primary">
                Buscar
              </button>
            </div>

            {buscar && (
              <div className="col-auto">
                <button
                  type="button"
                  className="btn btn-outline-secondary"
                  onClick={limpiarBusqueda}
                >
                  Limpiar
                </button>
              </div>
            )}
          </form>
        </div>
      </div>

      {/* Tabla */}
      <div className="card border-0 shadow-sm rounded-3">
        <div className="card-header bg-white border-0 px-4 pt-4 pb-3">
          <div className="d-flex justify-content-between align-items-center">
            <div>
              <h5 className="fw-bold mb-1">Estudiantes registrados</h5>

              <p className="text-muted small mb-0">
                Información de estudiantes disponibles en el sistema
              </p>
            </div>

            {!loading && (
              <span className="badge text-bg-light border">
                {totalEstudiantes}{" "}
                {totalEstudiantes === 1 ? "estudiante" : "estudiantes"}
              </span>
            )}
          </div>
        </div>

        <div className="card-body p-0">
          <div className="table-responsive">
            <table className="table table-hover align-middle mb-0">
              <thead className="table-light">
                <tr>
                  <th className="ps-4">Código</th>
                  <th>CI</th>
                  <th>Nombre completo</th>
                  <th>Correo</th>
                  <th>Estado</th>
                  <th className="text-center">Acciones</th>
                </tr>
              </thead>

              <tbody>
                {loading ? (
                  <tr>
                    <td colSpan="6" className="text-center py-5">
                      <div className="spinner-border spinner-border-sm text-primary me-2"></div>
                      Cargando estudiantes...
                    </td>
                  </tr>
                ) : estudiantes.length === 0 ? (
                  <tr>
                    <td colSpan="6" className="text-center py-5">
                      <div
                        className="bg-primary-subtle text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                        style={{
                          width: "48px",
                          height: "48px",
                        }}
                      >
                        <i className="bi bi-people fs-5"></i>
                      </div>

                      <h6 className="fw-semibold">
                        No se encontraron estudiantes
                      </h6>

                      <p className="text-muted small mb-0">
                        Importe estudiantes o cambie los criterios de búsqueda.
                      </p>
                    </td>
                  </tr>
                ) : (
                  estudiantes.map((est) => (
                    <tr key={est.id_estudiante}>
                      <td className="ps-4 fw-semibold text-primary">
                        {est.codigo_universitario}
                      </td>

                      <td>{est.ci}</td>

                      <td>
                        <div className="fw-semibold">
                          {est.nombre} {est.apellido}
                        </div>
                      </td>

                      <td className="text-muted">
                        {est.correo || "-"}
                      </td>

                      <td>
                        <span
                          className={`badge ${obtenerClaseEstado(est.estado)}`}
                        >
                          {est.estado || "Sin estado"}
                        </span>
                      </td>

                      <td className="text-center">
                        <button
                          type="button"
                          className="btn btn-sm btn-outline-primary"
                          onClick={() => abrirEditar(est)}
                          title="Editar estudiante"
                        >
                          <i className="bi bi-pencil-square me-1"></i>
                          Editar
                        </button>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </div>

        {!loading && totalPages > 1 && (
          <div className="card-footer bg-white border-0 py-3">
            <nav aria-label="Navegación de páginas de estudiantes">
              <ul className="pagination justify-content-center mb-0">
                <li className={`page-item ${currentPage === 1 ? 'disabled' : ''}`}>
                  <button
                    className="page-link"
                    onClick={() => setCurrentPage((p) => p - 1)}
                    disabled={currentPage === 1}
                  >
                    Anterior
                  </button>
                </li>
                <li className="page-item disabled">
                  <span className="page-link text-muted">
                    Página {currentPage} de {totalPages}
                  </span>
                </li>
                <li className={`page-item ${currentPage === totalPages ? 'disabled' : ''}`}>
                  <button
                    className="page-link"
                    onClick={() => setCurrentPage((p) => p + 1)}
                    disabled={currentPage === totalPages}
                  >
                    Siguiente
                  </button>
                </li>
              </ul>
            </nav>
          </div>
        )}
      </div>

      {/* Modal para importar estudiantes */}
      <ImportarEstudiantesModal
        mostrar={mostrarModal}
        onCerrar={() => setMostrarModal(false)}
        onImportado={handleImportado}
      />

      {/* Modal para editar estudiantes */}
      <EditarEstudianteModal
        mostrar={mostrarEditarModal}
        estudiante={estudianteSeleccionado}
        onCerrar={cerrarEditar}
        onActualizado={handleActualizado}
      />

      {/* Modal para registrar estudiante individual */}
      <NuevoEstudianteModal
        mostrar={mostrarNuevoModal}
        onCerrar={() => setMostrarNuevoModal(false)}
        onRegistrado={handleRegistrado}
      />
    </div>
  );
}