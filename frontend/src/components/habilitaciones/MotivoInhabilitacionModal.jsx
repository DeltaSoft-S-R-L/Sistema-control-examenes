import React, { useEffect, useId, useRef, useState } from 'react';
import api from '../../services/api';

const valor = (...valores) => valores.find((dato) => dato !== undefined && dato !== null && dato !== '');

const etiquetasTipo = {
  ACADEMICO: 'Académico',
  ADMINISTRATIVO: 'Administrativo',
  DOCUMENTAL: 'Documental',
  DISCIPLINARIO: 'Disciplinario',
  MORA: 'Mora',
  DOCUMENTACION: 'Documentación',
  OTRO: 'Otro',
  GENERAL: 'General',
};

const claseTipo = (tipo) => ({
  ACADEMICO: 'text-bg-primary',
  ADMINISTRATIVO: 'text-bg-warning',
  DOCUMENTAL: 'text-bg-info',
  DISCIPLINARIO: 'text-bg-danger',
  MORA: 'text-bg-warning',
  DOCUMENTACION: 'text-bg-info',
  OTRO: 'text-bg-secondary',
  GENERAL: 'text-bg-secondary',
}[tipo] || 'text-bg-dark');

export default function MotivoInhabilitacionModal({
  isOpen = true,
  onClose,
  idHabilitacion,
  motivo: motivoDato,
  estado,
  estudiante: estudianteDato,
}) {
  const [motivoCargado, setMotivoCargado] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const dialogRef = useRef(null);
  const closeRef = useRef(null);
  const tituloId = useId();

  useEffect(() => {
    if (!isOpen || motivoDato != null || !idHabilitacion) {
      setMotivoCargado(null);
      setLoading(false);
      setError(null);
      return undefined;
    }

    let activo = true;
    setLoading(true);
    api.get(`/habilitaciones/${idHabilitacion}`)
      .then((res) => {
        if (activo) setMotivoCargado(res.data?.data || res.data || null);
      })
      .catch((err) => {
        console.error('Error al obtener motivo de inhabilitación:', err);
        if (activo) setError('No se pudo cargar el motivo de inhabilitación.');
      })
      .finally(() => {
        if (activo) setLoading(false);
      });

    return () => { activo = false; };
  }, [isOpen, motivoDato, idHabilitacion]);

  useEffect(() => {
    if (!isOpen) return undefined;
    const dialog = dialogRef.current;
    const anterior = document.activeElement;
    closeRef.current?.focus();
    const atraparFoco = (event) => {
      if (event.key === 'Escape') {
        event.preventDefault();
        onClose?.();
      } else if (event.key === 'Tab' && dialog) {
        const focuseables = [...dialog.querySelectorAll('button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])')];
        if (!focuseables.length) { event.preventDefault(); dialog.focus(); return; }
        const primero = focuseables[0];
        const ultimo = focuseables[focuseables.length - 1];
        if (event.shiftKey && document.activeElement === primero) { event.preventDefault(); ultimo.focus(); }
        else if (!event.shiftKey && document.activeElement === ultimo) { event.preventDefault(); primero.focus(); }
      }
    };
    document.addEventListener('keydown', atraparFoco);
    return () => {
      document.removeEventListener('keydown', atraparFoco);
      anterior?.focus?.();
    };
  }, [isOpen, onClose]);

  if (!isOpen) return null;

  const estudiante = estudianteDato || {};
  const registro = motivoDato ?? motivoCargado ?? {};
  const motivo = typeof registro === 'string' ? { motivo: registro } : (registro || {});
  const tipo = String(valor(motivo.motivo_tipo, motivo.tipo_motivo, motivo.tipo, motivo.categoria, 'OTRO')).toUpperCase();
  const detalle = valor(motivo.motivo_detalle, motivo.detalle, motivo.motivo, motivo.descripcion, motivo.observacion);
  const inhabilitadoPor = valor(motivo.inhabilitado_por?.nombre, motivo.inhabilitado_por, motivo.usuario?.nombre, motivo.usuario?.name, motivo.creado_por);
  const fecha = valor(motivo.inhabilitado_at, motivo.inhabilitado_en, motivo.fecha_inhabilitacion, motivo.created_at, motivo.fecha);
  const nombre = `${valor(estudiante.nombre, '')} ${valor(estudiante.apellido, '')}`.trim();
  const esNoAsociado = estado === 'NO_ASOCIADO';

  return (
    <div className="modal d-block bg-dark bg-opacity-50" role="presentation" style={{ zIndex: 1055 }}>
      <div className="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <section ref={dialogRef} className="modal-content rounded-4 shadow" role="dialog" aria-modal="true" aria-labelledby={tituloId} tabIndex={-1}>
          <header className="modal-header">
            <h2 className="modal-title fs-5 fw-bold" id={tituloId}>{esNoAsociado ? 'Estudiante no asociado' : 'Motivo de inhabilitación'}</h2>
            <button ref={closeRef} type="button" className="btn-close" onClick={onClose} aria-label="Cerrar" />
          </header>
          <div className="modal-body">
            {estudianteDato && <div className="bg-light p-3 rounded-3 mb-3 border">
              <div className="fw-semibold">{nombre || 'Estudiante'}</div>
              <div className="small text-muted">{estudiante.ci && <span className="me-3">CI: {estudiante.ci}</span>}{(estudiante.codigo_universitario || estudiante.codigo_sis) && <span>SIS: {estudiante.codigo_universitario || estudiante.codigo_sis}</span>}</div>
            </div>}
            {loading ? <div className="text-center py-4"><span className="spinner-border spinner-border-sm me-2" role="status" />Cargando motivo...</div>
              : error ? <div className="alert alert-danger mb-0" role="alert">{error}</div>
                : esNoAsociado ? <div className="alert alert-warning mb-0">El estudiante no se encuentra asociado a este examen.</div>
                  : !detalle ? <p className="text-center text-muted py-3 mb-0">Sin motivo registrado</p> : <>
                    <span className={`badge ${claseTipo(tipo)} mb-3`}>{etiquetasTipo[tipo] || tipo.replaceAll('_', ' ')}</span>
                    <h3 className="h6 fw-bold">Detalle</h3>
                    <p className="mb-4 text-break">{detalle}</p>
                    <dl className="row small mb-0">
                      <dt className="col-sm-4">Inhabilitó</dt><dd className="col-sm-8">{inhabilitadoPor || '—'}</dd>
                      <dt className="col-sm-4">Fecha</dt><dd className="col-sm-8">{fecha ? new Date(fecha).toLocaleString() : '—'}</dd>
                    </dl>
                  </>}
          </div>
          <footer className="modal-footer"><button type="button" className="btn btn-secondary" onClick={onClose}>Cerrar</button></footer>
        </section>
      </div>
    </div>
  );
}
