import React, { useEffect, useState } from 'react';
import api from '../services/api';
import NuevoExamenModal from '../components/examenes/NuevoExamenModal';
import EditarExamenModal from '../components/examenes/EditarExamenModal';
import AuditoriaExamenModal from '../components/examenes/AuditoriaExamenModal';

export default function Examenes() {
  const [examenes, setExamenes] = useState([]);
  const [loading, setLoading] = useState(true);
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [isEditModalOpen, setIsEditModalOpen] = useState(false);
  const [isAuditModalOpen, setIsAuditModalOpen] = useState(false);
  const [examenAEditar, setExamenAEditar] = useState(null);
  const [examenAuditando, setExamenAuditando] = useState(null);
  const [mensaje, setMensaje] = useState(null);
  const [error, setError] = useState(null);

  // Filtros
  const [buscar, setBuscar] = useState('');
  const [filtroEstado, setFiltroEstado] = useState('');
  const [filtroFecha, setFiltroFecha] = useState('');

  const fetchExamenes = async (params = {}) => {
    try {
      setLoading(true);
      setError(null);
      const res = await api.get('/examenes', { params });
      setExamenes(res.data.data || res.data || []);
    } catch (err) {
      console.error('Error al cargar exámenes:', err);
      setError('No se pudieron cargar los exámenes. Verifique la conexión con el servidor.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchExamenes();
  }, []);

  const handleFiltrar = (e) => {
    e.preventDefault();
    const params = {};
    if (buscar.trim()) params.buscar = buscar.trim();
    if (filtroEstado) params.estado = filtroEstado;
    if (filtroFecha) params.fecha = filtroFecha;
    fetchExamenes(params);
  };

  const handleLimpiarFiltros = () => {
    setBuscar('');
    setFiltroEstado('');
    setFiltroFecha('');
    fetchExamenes();
  };

  const handleAbrirEditar = (examen) => {
    setExamenAEditar(examen);
    setIsEditModalOpen(true);
  };

  const handleAbrirAuditoria = (examen) => {
    setExamenAuditando(examen);
    setIsAuditModalOpen(true);
  };

  const handleEliminar = async (id, nombre) => {
    if (!window.confirm(`¿Está seguro de que desea eliminar la sesión de examen "${nombre}"?`)) {
      return;
    }

    try {
      setError(null);
      const res = await api.delete(`/examenes/${id}`);
      setMensaje(res.data?.message || 'Examen eliminado correctamente.');
      fetchExamenes();
      setTimeout(() => setMensaje(null), 4000);
    } catch (err) {
      setError(
        err.response?.data?.message || 'Error al eliminar el examen. Verifique el estado del mismo.'
      );
    }
  };

  const getBadgeEstado = (estado) => {
    const st = (estado || '').toUpperCase();
    switch (st) {
      case 'EN_CURSO':
        return (
          <span className="badge bg-success-subtle text-success border border-success-subtle fw-semibold">
            <i className="bi bi-play-circle me-1"></i>EN CURSO
          </span>
        );
      case 'PROGRAMADO':
        return (
          <span className="badge bg-primary-subtle text-primary border border-primary-subtle fw-semibold">
            <i className="bi bi-calendar-event me-1"></i>PROGRAMADO
          </span>
        );
      case 'FINALIZADO':
        return (
          <span className="badge bg-secondary-subtle text-secondary border border-secondary-subtle fw-semibold">
            <i className="bi bi-check2-circle me-1"></i>FINALIZADO
          </span>
        );
      case 'CANCELADO':
        return (
          <span className="badge bg-danger-subtle text-danger border border-danger-subtle fw-semibold">
            <i className="bi bi-x-circle me-1"></i>CANCELADO
          </span>
        );
      default:
        return <span className="badge bg-light text-dark border">{estado || 'DESCONOCIDO'}</span>;
    }
  };

  return (
    <div className="container-fluid p-0">
      {/* Encabezado */}
      <div className="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
          <h2 className="fw-bold mb-1">
            <i className="bi bi-journal-check me-2 text-primary"></i>Gestión de Exámenes
          </h2>
          <p className="text-muted mb-0">
            Listado, modificación, control y auditoría de sesiones de examen.
          </p>
        </div>

        <button
          type="button"
          className="btn btn-primary shadow-sm fw-semibold"
          onClick={() => setIsModalOpen(true)}
        >
          <i className="bi bi-plus-circle me-2"></i>Nuevo Examen
        </button>
      </div>

      {/* Alertas */}
      {mensaje && (
        <div className="alert alert-success alert-dismissible fade show" role="alert">
          <i className="bi bi-check-circle-fill me-2"></i>
          {mensaje}
          <button type="button" className="btn-close" onClick={() => setMensaje(null)}></button>
        </div>
      )}

      {error && (
        <div className="alert alert-danger alert-dismissible fade show" role="alert">
          <i className="bi bi-exclamation-triangle-fill me-2"></i>
          {error}
          <button type="button" className="btn-close" onClick={() => setError(null)}></button>
        </div>
      )}

      {/* Barra de Filtros */}
      <div className="card border-0 shadow-sm rounded-4 mb-4">
        <div className="card-body p-3">
          <form onSubmit={handleFiltrar} className="row g-2 align-items-end">
            <div className="col-12 col-md-4">
              <label className="form-label small fw-semibold text-muted mb-1">Buscar por nombre o descripción</label>
              <div className="input-group">
                <span className="input-group-text bg-light border-end-0">
                  <i className="bi bi-search text-muted"></i>
                </span>
                <input
                  type="text"
                  className="form-control border-start-0"
                  placeholder="Ej. Parcial de Software..."
                  value={buscar}
                  onChange={(e) => setBuscar(e.target.value)}
                />
              </div>
            </div>

            <div className="col-12 col-sm-6 col-md-3">
              <label className="form-label small fw-semibold text-muted mb-1">Filtrar por Estado</label>
              <select
                className="form-select"
                value={filtroEstado}
                onChange={(e) => setFiltroEstado(e.target.value)}
              >
                <option value="">Todos los estados</option>
                <option value="PROGRAMADO">PROGRAMADO</option>
                <option value="EN_CURSO">EN CURSO</option>
                <option value="FINALIZADO">FINALIZADO</option>
                <option value="CANCELADO">CANCELADO</option>
              </select>
            </div>

            <div className="col-12 col-sm-6 col-md-3">
              <label className="form-label small fw-semibold text-muted mb-1">Filtrar por Fecha</label>
              <input
                type="date"
                className="form-control"
                value={filtroFecha}
                onChange={(e) => setFiltroFecha(e.target.value)}
              />
            </div>

            <div className="col-12 col-md-2 d-flex gap-2">
              <button type="submit" className="btn btn-primary flex-fill">
                <i className="bi bi-filter me-1"></i>Filtrar
              </button>
              {(buscar || filtroEstado || filtroFecha) && (
                <button
                  type="button"
                  className="btn btn-outline-secondary"
                  onClick={handleLimpiarFiltros}
                  title="Limpiar filtros"
                >
                  <i className="bi bi-x-lg"></i>
                </button>
              )}
            </div>
          </form>
        </div>
      </div>

      {/* Tabla de Exámenes */}
      <div className="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div className="card-header bg-white border-0 px-4 pt-4 pb-2 d-flex justify-content-between align-items-center">
          <div>
            <h5 className="fw-bold mb-0">Sesiones Registradas</h5>
            <small className="text-muted">Consulta y acciones auditadas del sistema</small>
          </div>
          {!loading && (
            <span className="badge text-bg-light border">
              {examenes.length} {examenes.length === 1 ? 'examen' : 'exámenes'}
            </span>
          )}
        </div>

        <div className="card-body p-0">
          <div className="table-responsive">
            <table className="table table-hover align-middle mb-0">
              <thead className="table-light border-bottom">
                <tr>
                  <th className="ps-4">ID</th>
                  <th>Examen</th>
                  <th>Asignatura</th>
                  <th>Fecha</th>
                  <th>Hora</th>
                  <th>Duración</th>
                  <th>Ambiente(s)</th>
                  <th>Estado</th>
                  <th className="text-center pe-4" style={{ minWidth: '150px' }}>Acciones</th>
                </tr>
              </thead>
              <tbody>
                {loading ? (
                  <tr>
                    <td colSpan="9" className="text-center py-5">
                      <div className="spinner-border spinner-border-sm text-primary me-2"></div>
                      Cargando listado de exámenes...
                    </td>
                  </tr>
                ) : examenes.length === 0 ? (
                  <tr>
                    <td colSpan="9" className="text-center py-5 text-muted">
                      <i className="bi bi-journal-x fs-1 d-block mb-2 text-secondary opacity-50"></i>
                      <h6 className="fw-semibold">No se encontraron exámenes registrados</h6>
                      <p className="small text-muted mb-0">
                        Haga clic en <strong>"Nuevo Examen"</strong> para crear y programar una sesión.
                      </p>
                    </td>
                  </tr>
                ) : (
                  examenes.map((ex) => {
                    const ambientesList =
                      ex.asignaciones_ambiente && ex.asignaciones_ambiente.length > 0
                        ? ex.asignaciones_ambiente
                            .map((a) => a.ambiente?.nombre || a.ambiente?.codigo)
                            .filter(Boolean)
                            .join(', ')
                        : null;

                    return (
                      <tr key={ex.id_examen}>
                        <td className="ps-4 fw-bold text-secondary">#{ex.id_examen}</td>
                        <td>
                          <div className="fw-bold text-dark">{ex.nombre}</div>
                          {ex.descripcion && (
                            <small className="text-muted d-block text-truncate" style={{ maxWidth: '220px' }}>
                              {ex.descripcion}
                            </small>
                          )}
                        </td>
                        <td>
                          {ex.asignatura ? (
                            <div>
                              <span className="fw-semibold text-primary">{ex.asignatura.nombre}</span>
                              <small className="text-muted d-block">{ex.asignatura.codigo}</small>
                            </div>
                          ) : (
                            <span className="text-muted fst-italic">Sin asignatura</span>
                          )}
                        </td>
                        <td className="fw-semibold text-dark">{ex.fecha ? String(ex.fecha).substring(0, 10) : '-'}</td>
                        <td>
                          <span className="badge bg-light text-dark border">
                            <i className="bi bi-clock me-1 text-primary"></i>
                            {ex.hora_inicio ? String(ex.hora_inicio).substring(0, 5) : '-'}
                          </span>
                        </td>
                        <td>{ex.duracion_minutos} min</td>
                        <td>
                          {ambientesList ? (
                            <span className="badge bg-light text-dark border">
                              <i className="bi bi-building me-1 text-success"></i>
                              {ambientesList}
                            </span>
                          ) : (
                            <span className="badge bg-light text-muted border border-dashed">
                              Sin asignar
                            </span>
                          )}
                        </td>
                        <td>{getBadgeEstado(ex.estado)}</td>
                        <td className="text-center pe-4">
                          <div className="btn-group btn-group-sm" role="group">
                            <button
                              type="button"
                              className="btn btn-outline-warning"
                              title="Modificar sesión de examen"
                              onClick={() => handleAbrirEditar(ex)}
                            >
                              <i className="bi bi-pencil-square"></i>
                            </button>
                            <button
                              type="button"
                              className="btn btn-outline-info"
                              title="Ver historial de auditoría"
                              onClick={() => handleAbrirAuditoria(ex)}
                            >
                              <i className="bi bi-clock-history"></i>
                            </button>
                            <button
                              type="button"
                              className="btn btn-outline-danger"
                              title="Eliminar sesión de examen"
                              onClick={() => handleEliminar(ex.id_examen, ex.nombre)}
                            >
                              <i className="bi bi-trash"></i>
                            </button>
                          </div>
                        </td>
                      </tr>
                    );
                  })
                )}
              </tbody>
            </table>
          </div>
        </div>
      </div>

      {/* Modal Nuevo Examen */}
      <NuevoExamenModal
        isOpen={isModalOpen}
        onClose={() => setIsModalOpen(false)}
        onSuccess={() => {
          setMensaje('Examen creado con éxito.');
          fetchExamenes();
          setTimeout(() => setMensaje(null), 4000);
        }}
      />

      {/* Modal Editar Examen */}
      <EditarExamenModal
        isOpen={isEditModalOpen}
        examen={examenAEditar}
        onClose={() => {
          setIsEditModalOpen(false);
          setExamenAEditar(null);
        }}
        onSuccess={(msg) => {
          setMensaje(msg || 'Examen actualizado con éxito.');
          fetchExamenes();
          setTimeout(() => setMensaje(null), 4000);
        }}
      />

      {/* Modal Historial de Auditoría */}
      <AuditoriaExamenModal
        isOpen={isAuditModalOpen}
        examen={examenAuditando}
        onClose={() => {
          setIsAuditModalOpen(false);
          setExamenAuditando(null);
        }}
      />
    </div>
  );
}
