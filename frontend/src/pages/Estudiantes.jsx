import React, { useEffect, useState } from 'react';
import api from '../services/api';
import NuevoEstudianteModal from '../components/estudiantes/NuevoEstudianteModal';

export default function Estudiantes() {
  const [estudiantes, setEstudiantes] = useState([]);
  const [totalEstudiantes, setTotalEstudiantes] = useState(0);
  const [loading, setLoading] = useState(true);
  const [buscar, setBuscar] = useState('');
  const [mostrarModal, setMostrarModal] = useState(false);
  const [mensaje, setMensaje] = useState('');

  const fetchEstudiantes = async (search = '') => {
    try {
      setLoading(true);

      const res = await api.get('/estudiantes', {
        params: search ? { buscar: search } : {},
      });

      const datos = res.data.data || res.data || [];

      setEstudiantes(datos);
      setTotalEstudiantes(res.data.total ?? datos.length);
    } catch (err) {
      console.error('Error al obtener estudiantes:', err);
      setEstudiantes([]);
      setTotalEstudiantes(0);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchEstudiantes();
  }, []);

  const handleSearch = (e) => {
    e.preventDefault();
    fetchEstudiantes(buscar);
  };

  const limpiarBusqueda = () => {
    setBuscar('');
    fetchEstudiantes();
  };

  const handleRegistrado = () => {
    setMensaje('Estudiante registrado correctamente.');
    fetchEstudiantes(buscar);

    window.setTimeout(() => {
      setMensaje('');
    }, 4000);
  };

  const obtenerClaseEstado = (estado) => {
    switch (estado?.toUpperCase()) {
      case 'ACTIVO':
        return 'bg-success';

      case 'INACTIVO':
        return 'bg-secondary';

      default:
        return 'bg-secondary';
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

        <button
          type="button"
          className="btn btn-primary"
          onClick={() => setMostrarModal(true)}
        >
          <i className="bi bi-person-plus me-2"></i>
          Nuevo estudiante
        </button>
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
            onClick={() => setMensaje('')}
            aria-label="Cerrar"
          ></button>
        </div>
      )}

      {/* Buscador */}
      <div className="card border-0 shadow-sm rounded-3 mb-4">
        <div className="card-body">
          <form
            onSubmit={handleSearch}
            className="row g-2 align-items-center"
          >
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
              <button
                type="submit"
                className="btn btn-primary"
              >
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
              <h5 className="fw-bold mb-1">
                Estudiantes registrados
              </h5>

              <p className="text-muted small mb-0">
                Información de estudiantes disponibles en el sistema
              </p>
            </div>

            {!loading && (
              <span className="badge text-bg-light border">
                {totalEstudiantes}{' '}
                {totalEstudiantes === 1
                  ? 'estudiante'
                  : 'estudiantes'}
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
                </tr>
              </thead>

              <tbody>
                {loading ? (
                  <tr>
                    <td
                      colSpan="5"
                      className="text-center py-5"
                    >
                      <div className="spinner-border spinner-border-sm text-primary me-2"></div>
                      Cargando estudiantes...
                    </td>
                  </tr>
                ) : estudiantes.length === 0 ? (
                  <tr>
                    <td
                      colSpan="5"
                      className="text-center py-5"
                    >
                      <div
                        className="bg-primary-subtle text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                        style={{
                          width: '48px',
                          height: '48px',
                        }}
                      >
                        <i className="bi bi-people fs-5"></i>
                      </div>

                      <h6 className="fw-semibold">
                        No se encontraron estudiantes
                      </h6>

                      <p className="text-muted small mb-0">
                        Registre un estudiante o cambie los criterios
                        de búsqueda.
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
                        {est.correo || '-'}
                      </td>

                      <td>
                        <span
                          className={`badge ${obtenerClaseEstado(
                            est.estado
                          )}`}
                        >
                          {est.estado || 'Sin estado'}
                        </span>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </div>
      </div>

      {/* Modal para registrar estudiante */}
      <NuevoEstudianteModal
        mostrar={mostrarModal}
        onCerrar={() => setMostrarModal(false)}
        onRegistrado={handleRegistrado}
      />
    </div>
  );
}