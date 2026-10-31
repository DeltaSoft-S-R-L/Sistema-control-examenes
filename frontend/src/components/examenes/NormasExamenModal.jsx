import React, { useState, useEffect, useCallback } from 'react';
import api from '../../services/api';
import { tieneRol, ROLES } from '../../utils/auth';
import NuevaNormaModal from './NuevaNormaModal';

export default function NormasExamenModal({ isOpen, onClose, examen }) {
  const [normas, setNormas] = useState([]);
  const [loading, setLoading] = useState(false);
  const [mensaje, setMensaje] = useState(null);
  const [error, setError] = useState(null);
  const [isNuevaNormaOpen, setIsNuevaNormaOpen] = useState(false);
  const [eliminandoId, setEliminandoId] = useState(null);

  // Comprobar rol: solo ADMIN y DOCENTE pueden crear o eliminar normas.
  // Usuarios con rol CONTROL_INGRESO sólo pueden consultar la lista de normas.
  const puedeEditar = tieneRol(ROLES.ADMINISTRADOR, ROLES.DOCENTE);

  const cargarNormas = useCallback(async () => {
    if (!examen?.id_examen) return;
    setLoading(true);
    setError(null);
    try {
      const res = await api.get(`/examenes/${examen.id_examen}/normas`);
      setNormas(res.data || []);
    } catch (err) {
      console.error('Error al cargar normas del examen:', err);
      setError('No se pudieron cargar las normas del examen.');
    } finally {
      setLoading(false);
    }
  }, [examen?.id_examen]);

  useEffect(() => {
    if (isOpen && examen) {
      setMensaje(null);
      setError(null);
      cargarNormas();
    }
  }, [isOpen, examen, cargarNormas]);

  if (!isOpen || !examen) return null;

  const handleEliminarNorma = async (norma) => {
    const descCorta = norma.descripcion.length > 50
      ? `${norma.descripcion.substring(0, 50)}...`
      : norma.descripcion;

    if (!window.confirm(`¿Está seguro de que desea eliminar esta norma?\n"${descCorta}"`)) {
      return;
    }

    setEliminandoId(norma.id_norma);
    setMensaje(null);
    setError(null);

    try {
      await api.delete(`/examenes/${examen.id_examen}/normas/${norma.id_norma}`, {
        params: { tipo: norma.tipo },
      });
      setMensaje('Norma eliminada correctamente.');
      await cargarNormas();
      setTimeout(() => setMensaje(null), 4000);
    } catch (err) {
      console.error('Error al eliminar norma:', err);
      setError(err.response?.data?.message || 'Error al eliminar la norma.');
    } finally {
      setEliminandoId(null);
    }
  };

  return (
    <>
      <div
        className="modal fade show d-block"
        tabIndex="-1"
        role="dialog"
        aria-modal="true"
        style={{ backgroundColor: 'rgba(0,0,0,0.5)', zIndex: 1050 }}
      >
        <div className="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
          <div className="modal-content border-0 shadow-lg rounded-4">
            <div className="modal-header border-0 pb-0">
              <div>
                <h5 className="modal-title fw-bold text-dark d-flex align-items-center">
                  <i className="bi bi-shield-check me-2 text-primary fs-4"></i>
                  Normas del Examen
                </h5>
                <p className="text-muted small mb-0">
                  {examen.nombre} — {examen.materia?.nombre || examen.materia || ''}
                </p>
              </div>
              <button
                type="button"
                className="btn-close"
                aria-label="Cerrar"
                onClick={onClose}
              ></button>
            </div>

            <div className="modal-body py-3">
              {mensaje && (
                <div className="alert alert-success alert-dismissible fade show py-2 small" role="alert">
                  <i className="bi bi-check-circle-fill me-2"></i>
                  {mensaje}
                  <button type="button" className="btn-close btn-sm" onClick={() => setMensaje(null)}></button>
                </div>
              )}

              {error && (
                <div className="alert alert-danger alert-dismissible fade show py-2 small" role="alert">
                  <i className="bi bi-exclamation-triangle-fill me-2"></i>
                  {error}
                  <button type="button" className="btn-close btn-sm" onClick={() => setError(null)}></button>
                </div>
              )}

              <div className="d-flex justify-content-between align-items-center mb-3">
                <span className="text-muted small fw-semibold">
                  Total registradas: {normas.length}
                </span>

                {/* Botón 'Agregar norma': Visible solo para admin y docente según el rol */}
                {puedeEditar && (
                  <button
                    type="button"
                    className="btn btn-primary btn-sm rounded-pill px-3 fw-semibold shadow-sm"
                    onClick={() => setIsNuevaNormaOpen(true)}
                  >
                    <i className="bi bi-plus-circle me-1"></i>Agregar norma
                  </button>
                )}
              </div>

              {loading ? (
                <div className="text-center py-5">
                  <div className="spinner-border text-primary spinner-border-sm me-2" role="status"></div>
                  <span className="text-muted small">Cargando normas...</span>
                </div>
              ) : normas.length === 0 ? (
                <div className="text-center py-5 bg-light rounded-4 border border-dashed">
                  <i className="bi bi-journal-text display-4 text-muted opacity-50 mb-2"></i>
                  <p className="text-muted mb-0">No se han registrado normas para esta sesión de examen.</p>
                  {puedeEditar && (
                    <button
                      type="button"
                      className="btn btn-outline-primary btn-sm mt-3 rounded-pill"
                      onClick={() => setIsNuevaNormaOpen(true)}
                    >
                      <i className="bi bi-plus me-1"></i>Crear la primera norma
                    </button>
                  )}
                </div>
              ) : (
                <div className="table-responsive">
                  <table className="table table-hover align-middle mb-0">
                    <thead className="table-light">
                      <tr>
                        <th style={{ width: '110px' }}>Tipo</th>
                        <th style={{ width: '220px' }}>Aplica a</th>
                        <th>Descripción</th>
                        {puedeEditar && <th className="text-end pe-3" style={{ width: '90px' }}>Acción</th>}
                      </tr>
                    </thead>
                    <tbody>
                      {normas.map((norma) => (
                        <tr key={`${norma.tipo}-${norma.id_norma}`}>
                          <td>
                            {norma.tipo === 'GENERAL' ? (
                              <span className="badge bg-primary-subtle text-primary border border-primary-subtle fw-semibold">
                                <i className="bi bi-globe me-1"></i>General
                              </span>
                            ) : (
                              <span className="badge bg-info-subtle text-info-emphasis border border-info-subtle fw-semibold">
                                <i className="bi bi-person-fill me-1"></i>Particular
                              </span>
                            )}
                          </td>
                          <td>
                            {norma.tipo === 'GENERAL' ? (
                              <span className="text-muted small">
                                <i className="bi bi-people me-1"></i>Todos los postulantes
                              </span>
                            ) : norma.estudiante ? (
                              <div>
                                <span className="fw-semibold text-dark small d-block">
                                  {norma.estudiante.nombre_completo || `${norma.estudiante.nombre} ${norma.estudiante.apellido}`}
                                </span>
                                <span className="text-muted" style={{ fontSize: '0.75rem' }}>
                                  CI: {norma.estudiante.ci} | SIS: {norma.estudiante.codigo_universitario || '-'}
                                </span>
                              </div>
                            ) : (
                              <span className="text-muted small">Estudiante asignado</span>
                            )}
                          </td>
                          <td>
                            <p className="mb-0 text-dark small text-break" style={{ whiteSpace: 'pre-wrap' }}>
                              {norma.descripcion}
                            </p>
                          </td>
                          {puedeEditar && (
                            <td className="text-end pe-3">
                              <button
                                type="button"
                                className="btn btn-sm btn-outline-danger"
                                title="Eliminar norma"
                                disabled={eliminandoId === norma.id_norma}
                                onClick={() => handleEliminarNorma(norma)}
                              >
                                {eliminandoId === norma.id_norma ? (
                                  <span className="spinner-border spinner-border-sm" role="status"></span>
                                ) : (
                                  <i className="bi bi-trash"></i>
                                )}
                              </button>
                            </td>
                          )}
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              )}
            </div>

            <div className="modal-footer border-0 pt-0">
              <button
                type="button"
                className="btn btn-secondary rounded-pill px-4"
                onClick={onClose}
              >
                Cerrar
              </button>
            </div>
          </div>
        </div>
      </div>

      {/* Modal para Crear Nueva Norma */}
      <NuevaNormaModal
        isOpen={isNuevaNormaOpen}
        onClose={() => setIsNuevaNormaOpen(false)}
        idExamen={examen.id_examen}
        onSuccess={(msg) => {
          setMensaje(msg || 'Norma guardada exitosamente.');
          cargarNormas();
          setTimeout(() => setMensaje(null), 4000);
        }}
      />
    </>
  );
}
