import React, { useEffect, useState } from 'react';

function StudentPhoto({ estudiante }) {
  const [mostrarFoto, setMostrarFoto] = useState(false);
  const [fotoFallida, setFotoFallida] = useState(false);
  const urlFoto = estudiante?.url_foto || estudiante?.foto_url || estudiante?.fotografia_url;
  const nombre = estudiante?.nombre || estudiante?.nombres || '';
  const apellido = estudiante?.apellido || estudiante?.apellidos || '';
  const tieneFoto = Boolean(urlFoto) && !fotoFallida;

  useEffect(() => {
    if (!mostrarFoto) return undefined;
    const cerrarConEscape = (evento) => evento.key === 'Escape' && setMostrarFoto(false);
    window.addEventListener('keydown', cerrarConEscape);
    return () => window.removeEventListener('keydown', cerrarConEscape);
  }, [mostrarFoto]);

  return (
    <>
      {tieneFoto ? (
        <button type="button" className="btn p-0 border-0 d-block mx-auto" aria-label={`Ampliar foto de ${nombre} ${apellido}`} onClick={() => setMostrarFoto(true)}>
          <img src={urlFoto} alt={`Fotografía de ${nombre} ${apellido}`} className="rounded-3 border shadow-sm" style={{ width: 112, height: 136, objectFit: 'cover' }} onError={() => setFotoFallida(true)} />
        </button>
      ) : (
        <div className="rounded-3 border bg-light text-secondary d-flex align-items-center justify-content-center mx-auto" role="img" aria-label="Sin fotografía registrada" style={{ width: 112, height: 136 }}>
          <i className="bi bi-person-bounding-box" style={{ fontSize: '3.5rem' }} aria-hidden="true" />
        </div>
      )}
      <small className="text-muted d-block text-center mt-1">{tieneFoto ? 'Ampliar foto' : 'Sin fotografía'}</small>
      {mostrarFoto && tieneFoto && (
        <div className="modal d-block" role="dialog" aria-modal="true" aria-labelledby="student-photo-title" onMouseDown={(evento) => evento.target === evento.currentTarget && setMostrarFoto(false)} style={{ backgroundColor: 'rgba(0, 0, 0, .7)', zIndex: 1080 }}>
          <div className="modal-dialog modal-dialog-centered modal-lg"><div className="modal-content">
            <div className="modal-header"><h2 id="student-photo-title" className="modal-title fs-5">Fotografía del estudiante</h2><button type="button" className="btn-close" aria-label="Cerrar fotografía" onClick={() => setMostrarFoto(false)} /></div>
            <div className="modal-body text-center"><img src={urlFoto} alt={`Fotografía ampliada de ${nombre} ${apellido}`} className="img-fluid rounded-3" style={{ maxHeight: '75vh', objectFit: 'contain' }} onError={() => { setFotoFallida(true); setMostrarFoto(false); }} /></div>
          </div></div>
        </div>
      )}
    </>
  );
}

/**
 * Componente StudentCard para el resultado de verificación de ingreso.
 * Muestra la variante verde para estudiantes habilitados y la variante roja
 * con botón 'Ver motivo' para estudiantes no habilitados o no asociados.
 */
export default function StudentCard({
  estudiante,
  habilitacion,
  onConfirmarIngreso,
  confirmandoIngreso = false,
  ingresoConfirmado = false,
  examenSeleccionado,
  examen,
  onSiguiente,
  onVerMotivo,
}) {
  if (!estudiante) return null;

  const esHabilitado = habilitacion?.estado === 'HABILITADO';
  const esNoAsociado = habilitacion?.estado === 'NO_ASOCIADO';
  const nombreCompleto = `${estudiante.nombre || estudiante.nombres || ''} ${estudiante.apellido || estudiante.apellidos || ''}`.trim();
  const codigoSis = estudiante.codigo_universitario || estudiante.codigo_sis || '—';
  const estado = String(habilitacion?.estado || '').toUpperCase();
  const esReingreso = ['YA_INGRESO', 'YA_INGRESÓ', 'REINGRESO', 'INGRESO_PREVIO', 'DUPLICADO'].includes(estado);
  const ingresoPrevio = habilitacion?.ingreso_previo || habilitacion?.ingreso || {};
  const fechaHoraIngreso = ingresoPrevio.fecha_hora || ingresoPrevio.fechaHora || habilitacion?.fecha_hora_ingreso || habilitacion?.hora_primer_ingreso;
  const fechaHoraTexto = fechaHoraIngreso
    ? (Number.isNaN(Date.parse(fechaHoraIngreso)) ? String(fechaHoraIngreso) : new Date(fechaHoraIngreso).toLocaleString('es-BO'))
    : 'Hora no disponible';
  const ambiente = ingresoPrevio.asignacion_ambiente?.ambiente || ingresoPrevio.ambiente || habilitacion?.recinto || habilitacion?.ambiente || examen?.recinto || examen?.ambiente;
  const recinto = typeof ambiente === 'string' ? ambiente : (ambiente?.nombre || ambiente?.nombre_ambiente || ambiente?.codigo || 'Recinto no disponible');

  if (esReingreso) {
    return (
      <div className="card border-danger border-3 shadow-lg rounded-4 p-4 text-center bg-danger-subtle mb-3" role="alert" aria-live="assertive">
        <div className="bg-danger text-white rounded-3 p-3 mb-3">
          <i className="bi bi-hand-stop-fill display-3" aria-hidden="true"></i>
          <h2 className="fw-bold text-uppercase mt-2 mb-0">¡ALERTA! ESTUDIANTE YA INGRESÓ</h2>
        </div>
        <div className="bg-white rounded-3 p-3 text-start shadow-sm mb-3 border border-danger-subtle">
          <p className="fw-bold text-danger text-uppercase mb-3">Primer acceso registrado</p>
          <p className="mb-2"><i className="bi bi-clock-fill text-danger me-2" aria-hidden="true"></i><strong>Hora:</strong> {fechaHoraTexto}</p>
          <p className="mb-0"><i className="bi bi-geo-alt-fill text-danger me-2" aria-hidden="true"></i><strong>Recinto:</strong> {recinto}</p>
        </div>
        <div className="text-start mb-3">
          <StudentPhoto estudiante={estudiante} />
          <small className="text-muted text-uppercase fw-bold d-block">Estudiante</small>
          <span className="fs-5 fw-bold text-dark">{nombreCompleto}</span>
          <div><small className="text-muted">C.I.: </small><span className="fw-bold">{estudiante.ci}</span></div>
        </div>
        {onSiguiente && (
          <button type="button" className="btn btn-outline-danger btn-lg w-100 py-3 fw-bold" onClick={onSiguiente}>
            <i className="bi bi-arrow-right-circle me-2" aria-hidden="true"></i>Siguiente estudiante
          </button>
        )}
      </div>
    );
  }

  if (esNoAsociado) {
    const asignatura = examenSeleccionado?.asignatura?.nombre || examenSeleccionado?.asignatura || 'No especificada';
    const nombreExamen = examenSeleccionado?.nombre || 'No especificado';

    return (
      <section className="card student-card student-card--exam-warning shadow-lg rounded-4 p-3 p-md-4 mb-3" role="alert" aria-live="assertive">
        <div className="student-card__exam-warning-heading">
          <i className="bi bi-exclamation-octagon-fill" aria-hidden="true"></i>
          <div>
            <p className="student-card__exam-warning-kicker mb-1">Estudiante No Asociado · Acceso denegado</p>
            <h2 className="student-card__exam-warning-title">El estudiante no pertenece a este examen</h2>
          </div>
        </div>
        <dl className="student-card__exam-context mb-3">
          <div><dt>Materia seleccionada</dt><dd>{asignatura}</dd></div>
          <div><dt>Examen seleccionado</dt><dd>{nombreExamen}</dd></div>
        </dl>
        <div className="bg-white rounded-3 p-3 border border-danger-subtle">
          <StudentPhoto estudiante={estudiante} />
          <small className="text-muted text-uppercase fw-bold d-block">Estudiante escaneado</small>
          <strong className="fs-5 text-dark">{nombreCompleto}</strong>
          <div className="small mt-1">C.I.: {estudiante.ci || '—'} <span className="mx-1">·</span> Código SIS: {codigoSis}</div>
        </div>
        <button type="button" className="btn btn-light border border-danger-subtle w-100 mt-3 py-2 fw-bold text-danger" onClick={onVerMotivo}>
          <i className="bi bi-info-circle me-2" aria-hidden="true"></i>Ver motivo
        </button>
        <button type="button" className="btn btn-outline-danger w-100 mt-3 py-2 fw-bold" onClick={onSiguiente}>
          <i className="bi bi-arrow-right-circle me-2" aria-hidden="true"></i>Siguiente estudiante
        </button>
      </section>
    );
  }

  if (esHabilitado) {
    return (
      <div className="card border-success border-3 shadow-lg rounded-4 p-4 text-center bg-success-subtle mb-3">
        <div className="mb-2">
          <i className="bi bi-check-circle-fill display-2 text-success"></i>
        </div>
        <h2 className="fw-bold text-success text-uppercase mb-2">
          Estudiante Habilitado
        </h2>
        <div>
          <span className="badge bg-success fs-6 fw-semibold px-3 py-2 mb-4">
            <i className="bi bi-door-open-fill me-1"></i>ACCESO AUTORIZADO
          </span>
        </div>

        {/* Datos del estudiante */}
        <div className="bg-white rounded-3 p-3 text-start shadow-sm mb-4 border border-success-subtle">
          <div className="row g-3 align-items-center">
            <div className="col-12 col-sm-auto"><StudentPhoto estudiante={estudiante} /></div>
            <div className="col"><div className="row g-2">
            <div className="col-12">
              <small className="text-muted text-uppercase fw-bold d-block">Estudiante:</small>
              <span className="fs-5 fw-bold text-dark">
                {nombreCompleto}
              </span>
            </div>
            <div className="col-6">
              <small className="text-muted text-uppercase fw-bold d-block">C.I.:</small>
              <span className="fw-bold text-dark fs-6">{estudiante.ci}</span>
            </div>
            <div className="col-6">
              <small className="text-muted text-uppercase fw-bold d-block">Código SIS:</small>
              <span className="fw-bold text-primary fs-6">
                {codigoSis}
              </span>
            </div>
            {estudiante.carrera && (
              <div className="col-12 pt-2 border-top">
                <small className="text-muted text-uppercase fw-bold d-block">Carrera:</small>
                <span className="fw-semibold text-secondary small">{estudiante.carrera}</span>
              </div>
            )}
          </div></div>
        </div>

        <button
          type="button"
          className="btn btn-primary btn-lg w-100 py-3 fw-bold fs-5 shadow mb-3"
          onClick={onConfirmarIngreso}
          disabled={confirmandoIngreso || ingresoConfirmado}
        >
          {confirmandoIngreso ? (
            <>
              <span className="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
              Registrando ingreso...
            </>
          ) : ingresoConfirmado ? (
            <>
              <i className="bi bi-check-circle-fill me-2" aria-hidden="true"></i>
              Ingreso Registrado Exitosamente
            </>
          ) : (
            <>
              <i className="bi bi-door-open-fill me-2" aria-hidden="true"></i>
              Autorizar e Ingresar
            </>
          )}
        </button>

        {/* Botón Siguiente Estudiante */}
        <button
          type="button"
          className="btn btn-success btn-lg w-100 py-3 fw-bold fs-5 shadow"
          onClick={onSiguiente}
        >
          <i className="bi bi-arrow-right-circle me-2"></i>Siguiente estudiante
        </button>
      </div>
    );
  }

  // Variante ROJA: NO_HABILITADO o NO_ASOCIADO
  return (
    <div className="card border-danger border-3 shadow-lg rounded-4 p-4 text-center bg-danger-subtle mb-3">
      <div className="mb-2">
        <i className="bi bi-x-circle-fill display-2 text-danger"></i>
      </div>
      <h2 className="fw-bold text-danger text-uppercase mb-2">
        {esNoAsociado ? 'Estudiante No Asociado' : 'Estudiante No Habilitado'}
      </h2>
      <div>
        <span className="badge bg-danger fs-6 fw-semibold px-3 py-2 mb-4">
          <i className="bi bi-slash-circle me-1"></i>ACCESO DENEGADO
        </span>
      </div>

      {/* Datos del estudiante */}
      <div className="bg-white rounded-3 p-3 text-start shadow-sm mb-3 border border-danger-subtle">
        <div className="row g-3 align-items-center">
          <div className="col-12 col-sm-auto"><StudentPhoto estudiante={estudiante} /></div>
          <div className="col"><div className="row g-2">
          <div className="col-12">
            <small className="text-muted text-uppercase fw-bold d-block">Estudiante:</small>
            <span className="fs-5 fw-bold text-dark">
              {nombreCompleto}
            </span>
          </div>
          <div className="col-6">
            <small className="text-muted text-uppercase fw-bold d-block">C.I.:</small>
            <span className="fw-bold text-dark">{estudiante.ci}</span>
          </div>
          <div className="col-6">
            <small className="text-muted text-uppercase fw-bold d-block">Código SIS:</small>
            <span className="fw-bold text-primary">
              {codigoSis}
            </span>
          </div>
          {estudiante.carrera && (
            <div className="col-12 pt-2 border-top">
              <small className="text-muted text-uppercase fw-bold d-block">Carrera:</small>
              <span className="fw-semibold text-secondary small">{estudiante.carrera}</span>
            </div>
          )}
        </div></div>
      </div>

      {/* Botón Ver Motivo */}
      <button
        type="button"
        className="btn btn-outline-danger btn-lg w-100 py-2 fw-bold mb-3 bg-white"
        onClick={onVerMotivo}
      >
        <i className="bi bi-info-circle me-2"></i>Ver motivo
      </button>

      {/* Botón Siguiente Estudiante */}
      <button
        type="button"
        className="btn btn-danger btn-lg w-100 py-3 fw-bold fs-5 shadow"
        onClick={onSiguiente}
      >
        <i className="bi bi-arrow-right-circle me-2"></i>Siguiente estudiante
      </button>
    </div>
  );
}
