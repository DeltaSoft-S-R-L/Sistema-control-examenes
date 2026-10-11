import React, { useEffect, useState } from 'react';
import api from '../../services/api';

export default function AuditoriaExamenModal({ isOpen, examen, onClose }) {
  const [auditorias, setAuditorias] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  useEffect(() => {
    if (isOpen && examen) {
      cargarAuditoria();
    }
  }, [isOpen, examen]);

  const cargarAuditoria = async () => {
    try {
      setLoading(true);
      setError(null);
      const res = await api.get(`/examenes/${examen.id_examen}/auditoria`);
      setAuditorias(res.data || []);
    } catch (err) {
      console.error('Error al cargar auditoría:', err);
      setError('No se pudo cargar el historial de auditoría.');
    } finally {
      setLoading(false);
    }
  };

  if (!isOpen || !examen) return null;

  const getBadgeAccion = (accion) => {
    const ac = (accion || '').toUpperCase();
    switch (ac) {
      case 'CREACION':
        return <span className="badge bg-success-subtle text-success border border-success-subtle">CREACIÓN</span>;
      case 'MODIFICACION':
        return <span className="badge bg-warning-subtle text-warning border border-warning-subtle text-dark">MODIFICACIÓN</span>;
      case 'ELIMINACION':
        return <span className="badge bg-danger-subtle text-danger border border-danger-subtle">ELIMINACIÓN</span>;
      default:
        return <span className="badge bg-light text-dark border">{accion}</span>;
    }
  };

  return (
    <div
      className="modal fade show d-block"
      tabIndex="-1"
      style={{ backgroundColor: 'rgba(0,0,0,0.5)' }}
    >
      <div className="modal-dialog modal-lg modal-dialog-centered">
        <div className="modal-content border-0 rounded-4 shadow-lg">
          <div className="modal-header bg-light border-bottom rounded-top-4">
            <div>
              <h5 className="modal-title fw-bold mb-0">
                <i className="bi bi-clock-history me-2 text-primary"></i>Historial de Auditoría
              </h5>
              <small className="text-muted">
                Examen: <strong>{examen.nombre}</strong> (ID: #{examen.id_examen})
              </small>
            </div>
            <button
              type="button"
              className="btn-close"
              onClick={onClose}
              aria-label="Cerrar"
            ></button>
          </div>

          <div className="modal-body p-4" style={{ maxHeight: '65vh', overflowY: 'auto' }}>
            {error && (
              <div className="alert alert-danger py-2 d-flex align-items-center" role="alert">
                <i className="bi bi-exclamation-triangle-fill me-2"></i>
                <div>{error}</div>
              </div>
            )}

            {loading ? (
              <div className="text-center py-4">
                <div className="spinner-border spinner-border-sm text-primary me-2"></div>
                Cargando registros de auditoría...
              </div>
            ) : auditorias.length === 0 ? (
              <div className="text-center py-4 text-muted">
                <i className="bi bi-shield-check fs-1 d-block mb-2 text-secondary opacity-50"></i>
                <h6 className="fw-semibold">Sin registros de auditoría previos</h6>
                <p className="small text-muted mb-0">
                  Las futuras creaciones, modificaciones o intentos de eliminación quedarán auditados aquí.
                </p>
              </div>
            ) : (
              <div className="timeline">
                <div className="table-responsive">
                  <table className="table table-sm table-hover align-middle mb-0">
                    <thead className="table-light border-bottom">
                      <tr>
                        <th>Fecha y Hora</th>
                        <th>Acción</th>
                        <th>Resultado</th>
                        <th>Usuario</th>
                        <th>Detalle</th>
                      </tr>
                    </thead>
                    <tbody>
                      {auditorias.map((aud) => (
                        <tr key={aud.id_auditoria}>
                          <td className="small text-nowrap fw-semibold">
                            {aud.fecha_hora ? new Date(aud.fecha_hora).toLocaleString('es-ES') : '-'}
                          </td>
                          <td>{getBadgeAccion(aud.accion)}</td>
                          <td>
                            <span
                              className={`badge ${
                                aud.resultado === 'EXITO'
                                  ? 'bg-success-subtle text-success border border-success-subtle'
                                  : 'bg-danger-subtle text-danger border border-danger-subtle'
                              }`}
                            >
                              {aud.resultado}
                            </span>
                          </td>
                          <td className="small">
                            {aud.usuario ? (
                              <span>
                                <strong>{aud.usuario.username}</strong>
                                <small className="text-muted d-block">{aud.usuario.nombre} {aud.usuario.apellido}</small>
                              </span>
                            ) : (
                              <span className="text-muted">ID #{aud.id_usuario}</span>
                            )}
                          </td>
                          <td className="small text-muted" style={{ maxWidth: '280px' }}>
                            {aud.descripcion || '-'}
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </div>
            )}
          </div>

          <div className="modal-footer bg-light rounded-bottom-4">
            <button type="button" className="btn btn-outline-secondary" onClick={onClose}>
              Cerrar
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
