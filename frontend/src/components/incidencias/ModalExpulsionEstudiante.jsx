import { useEffect, useState } from 'react';
import api from '../../services/api';

const motivos = [
  ['INTENTO_COPIA', 'Intento de copia o fraude académico'],
  ['SUPLANTACION_IDENTIDAD', 'Suplantación de identidad'],
  ['USO_DISPOSITIVO_NO_AUTORIZADO', 'Uso de celular o dispositivo no autorizado'],
  ['CONDUCTA_INAPROPIADA', 'Conducta inapropiada o alteración del orden'],
  ['OTRO', 'Otro motivo disciplinario'],
];

export default function ModalExpulsionEstudiante({ isOpen, onClose, estudiante, idExamen, idIngreso, onExpulsionExitosa }) {
  const [motivo, setMotivo] = useState('');
  const [descripcion, setDescripcion] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState('');
  useEffect(() => { if (isOpen) { setMotivo(''); setDescripcion(''); setError(''); } }, [isOpen, idIngreso]);
  if (!isOpen) return null;
  const nombre = `${estudiante?.nombre || estudiante?.nombres || ''} ${estudiante?.apellido || estudiante?.apellidos || ''}`.trim();
  const valido = Boolean(motivo) && (motivo !== 'OTRO' || descripcion.trim().length > 0);
  const enviar = async (event) => {
    event.preventDefault();
    if (!valido || submitting) return;
    setSubmitting(true); setError('');
    try {
      const response = await api.post(`/examenes/${idExamen}/expulsiones`, { id_ingreso: idIngreso, id_estudiante: estudiante?.id_estudiante, motivo, descripcion: descripcion.trim() || null });
      onExpulsionExitosa?.(response.data);
      onClose?.();
    } catch (err) {
      const data = err.response?.data;
      setError(data?.message || (err.response?.status === 409 ? 'El estudiante ya fue expulsado.' : err.response?.status === 422 ? 'Verifique el motivo y los datos obligatorios.' : 'No se pudo registrar la expulsión. Intente nuevamente.'));
    } finally { setSubmitting(false); }
  };
  return <div className="modal d-block" role="dialog" aria-modal="true" aria-labelledby="expulsion-title" style={{ background: 'rgba(0,0,0,.55)', zIndex: 1070 }}>
    <div className="modal-dialog modal-dialog-centered"><div className="modal-content">
      <form onSubmit={enviar}>
        <div className="modal-header"><h2 id="expulsion-title" className="modal-title fs-5">Registrar expulsión</h2><button type="button" className="btn-close" aria-label="Cerrar" onClick={onClose} disabled={submitting} /></div>
        <div className="modal-body">
          <div className="mb-3"><strong>{nombre}</strong><div>C.I.: {estudiante?.ci || '—'} · Código SIS: {estudiante?.codigo_universitario || estudiante?.codigo_sis || '—'}</div></div>
          <label className="form-label" htmlFor="motivo-expulsion">Motivo tipificado <span className="text-danger">*</span></label>
          <select id="motivo-expulsion" className="form-select mb-3" required value={motivo} onChange={(e) => setMotivo(e.target.value)}><option value="">Seleccione un motivo</option>{motivos.map(([value, label]) => <option key={value} value={value}>{label}</option>)}</select>
          <label className="form-label" htmlFor="descripcion-expulsion">Observación {motivo === 'OTRO' && <span className="text-danger">*</span>}</label>
          <textarea id="descripcion-expulsion" className="form-control" rows="3" maxLength={1000} required={motivo === 'OTRO'} value={descripcion} onChange={(e) => setDescripcion(e.target.value)} />
          <div className="alert alert-danger mt-3 mb-0"><strong>Atención:</strong> La expulsión cambiará el estado del postulante a EXPULSADO y es irreversible.</div>
          {error && <div role="alert" className="alert alert-warning mt-3 mb-0">{error}</div>}
        </div>
        <div className="modal-footer"><button type="button" className="btn btn-secondary" onClick={onClose} disabled={submitting}>Cancelar</button><button type="submit" className="btn btn-danger" disabled={!valido || submitting}>{submitting ? 'Registrando…' : 'Confirmar expulsión'}</button></div>
      </form>
    </div></div>
  </div>;
}
