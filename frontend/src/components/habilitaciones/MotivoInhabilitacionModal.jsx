import { useEffect, useId, useRef } from 'react';

const valor = (...valores) => valores.find((dato) => dato !== undefined && dato !== null && dato !== '');

const tipoMotivo = (motivo) => String(valor(motivo?.tipo_motivo, motivo?.tipo, motivo?.categoria, 'GENERAL')).toUpperCase();

const etiquetasTipo = {
  ACADEMICO: 'Académico',
  ADMINISTRATIVO: 'Administrativo',
  DOCUMENTAL: 'Documental',
  DISCIPLINARIO: 'Disciplinario',
  GENERAL: 'General',
};

const claseTipo = (tipo) => ({
  ACADEMICO: 'text-bg-primary',
  ADMINISTRATIVO: 'text-bg-warning',
  DOCUMENTAL: 'text-bg-info',
  DISCIPLINARIO: 'text-bg-danger',
  GENERAL: 'text-bg-secondary',
}[tipo] || 'text-bg-dark');

export default function MotivoInhabilitacionModal({ estudiante: estudianteDato, motivo: motivoDato, onClose }) {
  const dialogRef = useRef(null);
  const closeRef = useRef(null);
  const tituloId = useId();

  useEffect(() => {
    const dialog = dialogRef.current;
    if (!dialog) return undefined;
    const anterior = document.activeElement;
    closeRef.current?.focus();

    const atraparFoco = (event) => {
      if (event.key === 'Escape') {
        event.preventDefault();
        onClose();
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
  }, [onClose]);

  const estudiante = estudianteDato ?? {};
  const motivo = motivoDato ? { motivo: motivoDato } : {};
  const tipo = tipoMotivo(motivo);
  const detalle = valor(motivo.detalle, motivo.motivo, motivo.descripcion, motivo.observacion);
  const inhabilitadoPor = valor(motivo.inhabilitado_por?.nombre, motivo.inhabilitado_por, motivo.usuario?.nombre, motivo.usuario?.name, motivo.creado_por);
  const fecha = valor(motivo.inhabilitado_en, motivo.fecha_inhabilitacion, motivo.created_at, motivo.fecha);
  const nombre = `${valor(estudiante.nombre, '')} ${valor(estudiante.apellido, '')}`.trim();

  return (
    <div className="modal d-block bg-dark bg-opacity-50" role="presentation">
      <div className="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <section ref={dialogRef} className="modal-content" role="dialog" aria-modal="true" aria-labelledby={tituloId} tabIndex={-1}>
          <header className="modal-header">
            <h2 className="modal-title fs-5 fw-bold" id={tituloId}>Motivo de inhabilitación</h2>
            <button ref={closeRef} type="button" className="btn-close" onClick={onClose} aria-label="Cerrar" />
          </header>
          <div className="modal-body">
            {!detalle && <p className="text-center text-muted py-4 mb-0">Sin motivo registrado</p>}
            {detalle && (
              <>
                <dl className="row mb-4">
                  <dt className="col-sm-4">Estudiante</dt><dd className="col-sm-8">{nombre || '—'}</dd>
                  <dt className="col-sm-4">CI</dt><dd className="col-sm-8">{valor(estudiante?.ci, motivo?.ci, '—')}</dd>
                  <dt className="col-sm-4">Código SIS</dt><dd className="col-sm-8">{valor(estudiante?.codigo_sis, estudiante?.codigo_universitario, motivo?.codigo_sis, '—')}</dd>
                </dl>
                <div className="mb-3"><span className={`badge ${claseTipo(tipo)}`}>{etiquetasTipo[tipo] || tipo.replaceAll('_', ' ')}</span></div>
                <h3 className="h6 fw-bold">Detalle</h3>
                <p className="mb-4 text-break">{detalle || 'Sin detalle disponible.'}</p>
                <dl className="row small mb-0">
                  <dt className="col-sm-4">Inhabilitó</dt><dd className="col-sm-8">{inhabilitadoPor || '—'}</dd>
                  <dt className="col-sm-4">Fecha</dt><dd className="col-sm-8">{fecha ? new Date(fecha).toLocaleString() : '—'}</dd>
                </dl>
              </>
            )}
          </div>
          <footer className="modal-footer"><button type="button" className="btn btn-secondary" onClick={onClose}>Cerrar</button></footer>
        </section>
      </div>
    </div>
  );
}
