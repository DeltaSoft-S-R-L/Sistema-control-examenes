import React, { useState, useEffect } from 'react';
import api from '../../services/api';

/**
 * Modal para visualizar el motivo de inhabilitación o rechazo de un estudiante.
 * - Si recibe el prop `motivo` pre-cargado, lo muestra directamente sin hacer peticiones a la API.
 * - Si `estado === 'NO_ASOCIADO'`, muestra el mensaje explicativo adecuado sin petición.
 * - Si NO recibe `motivo` pero sí `idHabilitacion` (uso en listado HAB-02), consulta `GET /api/habilitaciones/{id}`.
 */
export default function MotivoInhabilitacionModal({
  isOpen,
  onClose,
  idHabilitacion,
  motivo: motivoProp,
  estado,
  estudiante,
}) {
  const [motivoCargado, setMotivoCargado] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  useEffect(() => {
    if (!isOpen) {
      setError(null);
      return;
    }

    // 1. Si ya se recibe el motivo cargado directamente (desde verificación o estado previo)
    if (motivoProp !== undefined && motivoProp !== null) {
      setMotivoCargado(motivoProp);
      setLoading(false);
      setError(null);
      return;
    }

    // 2. Si no viene motivo pero sí idHabilitacion (caso HAB-02 listado)
    if (idHabilitacion) {
      setLoading(true);
      setError(null);
      api
        .get(`/habilitaciones/${idHabilitacion}`)
        .then((res) => {
          const data = res.data?.data || res.data || {};
          setMotivoCargado(data.motivo || '');
        })
        .catch((err) => {
          console.error('Error al obtener motivo de inhabilitación:', err);
          setError('No se pudo cargar el motivo de inhabilitación.');
        })
        .finally(() => {
          setLoading(false);
        });
    } else {
      setMotivoCargado('');
      setLoading(false);
    }
  }, [isOpen, motivoProp, idHabilitacion]);

  if (!isOpen) return null;

  const esNoAsociado = estado === 'NO_ASOCIADO';

  return (
    <div
      className="modal fade show d-block"
      tabIndex="-1"
      role="dialog"
      aria-modal="true"
      style={{ backgroundColor: 'rgba(0, 0, 0, 0.5)', zIndex: 1055 }}
    >
      <div className="modal-dialog modal-dialog-centered">
        <div className="modal-content border-0 shadow rounded-4">
          <div className="modal-header border-0 pb-0">
            <h5 className="modal-title fw-bold text-danger d-flex align-items-center">
              <i className="bi bi-exclamation-octagon-fill me-2 fs-4"></i>
              {esNoAsociado ? 'Estudiante No Asociado' : 'Motivo de Rechazo'}
            </h5>
            <button
              type="button"
              className="btn-close"
              aria-label="Cerrar"
              onClick={onClose}
            ></button>
          </div>

          <div className="modal-body py-3">
            {estudiante && (
              <div className="bg-light p-3 rounded-3 mb-3 border">
                <div className="fw-semibold text-dark">
                  {estudiante.nombre} {estudiante.apellido}
                </div>
                <div className="small text-muted mt-1">
                  {estudiante.ci && <span className="me-3">CI: {estudiante.ci}</span>}
                  {(estudiante.codigo_universitario || estudiante.codigo_sis) && (
                    <span>SIS: {estudiante.codigo_universitario || estudiante.codigo_sis}</span>
                  )}
                </div>
              </div>
            )}

            {loading ? (
              <div className="text-center py-4">
                <div className="spinner-border text-danger spinner-border-sm me-2" role="status"></div>
                <span className="text-muted small">Cargando motivo...</span>
              </div>
            ) : error ? (
              <div className="alert alert-danger py-2 small mb-0">{error}</div>
            ) : esNoAsociado ? (
              <div className="alert alert-warning py-3 mb-0 border border-warning-subtle rounded-3">
                <div className="d-flex align-items-center mb-1">
                  <i className="bi bi-info-circle-fill me-2 text-warning fs-5"></i>
                  <span className="fw-semibold text-dark">Sin habilitación registrada</span>
                </div>
                <p className="mb-0 text-muted small">
                  El estudiante no se encuentra asociado a este examen.
                </p>
              </div>
            ) : (
              <div className="alert alert-danger-subtle border border-danger-subtle p-3 rounded-3 mb-0">
                <span className="fw-semibold text-danger d-block mb-1">
                  <i className="bi bi-shield-x me-1"></i>Razón registrada:
                </span>
                <p className="mb-0 text-dark">
                  {motivoCargado || 'No se especificó un motivo para esta inhabilitación.'}
                </p>
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
  );
}
