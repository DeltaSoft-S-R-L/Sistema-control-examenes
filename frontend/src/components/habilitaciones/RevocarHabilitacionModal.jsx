import React, { useId, useState } from 'react';

const TIPOS_MOTIVO = [
  ['ACADEMICO', 'Académico'],
  ['MORA', 'Mora'],
  ['DOCUMENTACION', 'Documentación'],
  ['OTRO', 'Otro'],
];

export default function RevocarHabilitacionModal({ habilitacion, guardando = false, onClose, onConfirm }) {
  const [tipo, setTipo] = useState('');
  const [detalle, setDetalle] = useState('');
  const [error, setError] = useState('');
  const tituloId = useId();
  const estudiante = habilitacion?.estudiante || {};
  const nombre = `${estudiante.nombre || ''} ${estudiante.apellido || ''}`.trim();

  if (!habilitacion) return null;

  const enviar = (event) => {
    event.preventDefault();
    const detalleLimpio = detalle.trim();
    if (!tipo) {
      setError('Seleccione el tipo de motivo.');
      return;
    }
    if (!detalleLimpio) {
      setError('El motivo de inhabilitación es obligatorio.');
      return;
    }
    if (detalleLimpio.length > 500) {
      setError('El motivo no puede superar los 500 caracteres.');
      return;
    }
    setError('');
    onConfirm({ tipo, detalle: detalleLimpio });
  };

  return (
    <div className="modal d-block bg-dark bg-opacity-50" role="presentation" style={{ zIndex: 1055 }}>
      <div className="modal-dialog modal-dialog-centered">
        <form className="modal-content rounded-4 shadow" onSubmit={enviar} noValidate role="dialog" aria-modal="true" aria-labelledby={tituloId}>
          <div className="modal-header">
            <h2 className="modal-title fs-5 fw-bold" id={tituloId}>Revocar habilitación</h2>
            <button type="button" className="btn-close" onClick={onClose} disabled={guardando} aria-label="Cerrar" />
          </div>
          <div className="modal-body">
            <p>Indique el motivo para inhabilitar a <strong>{nombre || 'este estudiante'}</strong>.</p>
            <label className="form-label fw-semibold" htmlFor={`${tituloId}-tipo`}>Tipo de motivo *</label>
            <select
              id={`${tituloId}-tipo`}
              className={`form-select mb-3 ${error && !tipo ? 'is-invalid' : ''}`}
              value={tipo}
              onChange={(event) => { setTipo(event.target.value); setError(''); }}
              required
              disabled={guardando}
            >
              <option value="">Seleccione un tipo</option>
              {TIPOS_MOTIVO.map(([value, label]) => <option key={value} value={value}>{label}</option>)}
            </select>
            <label className="form-label fw-semibold" htmlFor={`${tituloId}-detalle`}>Motivo *</label>
            <textarea
              id={`${tituloId}-detalle`}
              className={`form-control ${error && !detalle.trim() ? 'is-invalid' : ''}`}
              rows="4"
              maxLength={500}
              value={detalle}
              onChange={(event) => { setDetalle(event.target.value); setError(''); }}
              placeholder="Describa el motivo de la inhabilitación"
              required
              disabled={guardando}
            />
            <div className="form-text text-end">{detalle.length}/500</div>
            {error && <div className="alert alert-danger py-2 mt-3 mb-0" role="alert">{error}</div>}
          </div>
          <div className="modal-footer">
            <button type="button" className="btn btn-outline-secondary" onClick={onClose} disabled={guardando}>Cancelar</button>
            <button type="submit" className="btn btn-danger" disabled={guardando}>
              {guardando ? 'Guardando…' : 'Confirmar revocación'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
