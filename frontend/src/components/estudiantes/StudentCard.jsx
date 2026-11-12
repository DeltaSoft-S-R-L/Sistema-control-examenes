import React from 'react';
import { useEffect, useState } from 'react';

/**
 * Componente StudentCard para el resultado de verificación de ingreso.
 * Muestra la variante verde para estudiantes habilitados y la variante roja
 * con botón 'Ver motivo' para estudiantes no habilitados o no asociados.
 */
export default function StudentCard({
  estudiante,
  habilitacion,
  onSiguiente,
  onVerMotivo,
}) {
  const [mostrarFoto, setMostrarFoto] = useState(false);
  const [fotoNoDisponible, setFotoNoDisponible] = useState(null);
  const urlFoto = estudiante?.url_foto || estudiante?.foto_url || estudiante?.fotografia_url;
  const tieneFoto = Boolean(urlFoto) && fotoNoDisponible !== urlFoto;

  useEffect(() => {
    if (!mostrarFoto) return undefined;
    const cerrarConEscape = (evento) => {
      if (evento.key === 'Escape') setMostrarFoto(false);
    };
    window.addEventListener('keydown', cerrarConEscape);
    return () => window.removeEventListener('keydown', cerrarConEscape);
  }, [mostrarFoto]);

  if (!estudiante) return null;

  const foto = (
    <div className="d-flex flex-column align-items-center gap-2">
      {tieneFoto ? (
        <img
          src={urlFoto}
          alt={`Fotografía de ${estudiante.nombre} ${estudiante.apellido}`}
          className="rounded-3 border shadow-sm"
          style={{ width: '112px', height: '136px', objectFit: 'cover' }}
          onError={() => setFotoNoDisponible(urlFoto)}
        />
      ) : (
        <div
          className="rounded-3 border bg-light text-secondary d-flex align-items-center justify-content-center"
          role="img"
          aria-label="Sin fotografía registrada"
          style={{ width: '112px', height: '136px' }}
        >
          <i className="bi bi-person-bounding-box" style={{ fontSize: '3.5rem' }} aria-hidden="true" />
        </div>
      )}
      {tieneFoto ? (
        <button type="button" className="btn btn-sm btn-outline-secondary" onClick={() => setMostrarFoto(true)}>
          <i className="bi bi-zoom-in me-1" aria-hidden="true" />Ampliar foto
        </button>
      ) : <small className="text-muted">Sin fotografía</small>}
    </div>
  );

  const esHabilitado = habilitacion?.estado === 'HABILITADO';
  const esNoAsociado = habilitacion?.estado === 'NO_ASOCIADO';
  const modalFoto = mostrarFoto && tieneFoto ? (
    <div
      className="modal d-block"
      role="dialog"
      aria-modal="true"
      aria-labelledby="student-photo-title"
      onMouseDown={(evento) => { if (evento.target === evento.currentTarget) setMostrarFoto(false); }}
      style={{ backgroundColor: 'rgba(0, 0, 0, .7)', zIndex: 1080 }}
    >
      <div className="modal-dialog modal-dialog-centered modal-lg">
        <div className="modal-content">
          <div className="modal-header">
            <h2 id="student-photo-title" className="modal-title fs-5">Fotografía del estudiante</h2>
            <button type="button" className="btn-close" aria-label="Cerrar fotografía" onClick={() => setMostrarFoto(false)} />
          </div>
          <div className="modal-body text-center">
            <img
              src={urlFoto}
              alt={`Fotografía ampliada de ${estudiante.nombre} ${estudiante.apellido}`}
              className="img-fluid rounded-3"
              style={{ maxHeight: '75vh', objectFit: 'contain' }}
              onError={() => { setFotoNoDisponible(urlFoto); setMostrarFoto(false); }}
            />
          </div>
        </div>
      </div>
    </div>
  ) : null;

  if (esHabilitado) {
    return (
      <>
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
            <div className="col-12 col-sm-auto">{foto}</div>
            <div className="col">
            <div className="row g-2">
            <div className="col-12">
              <small className="text-muted text-uppercase fw-bold d-block">Estudiante:</small>
              <span className="fs-5 fw-bold text-dark">
                {estudiante.nombre} {estudiante.apellido}
              </span>
            </div>
            <div className="col-6">
              <small className="text-muted text-uppercase fw-bold d-block">C.I.:</small>
              <span className="fw-bold text-dark fs-6">{estudiante.ci}</span>
            </div>
            <div className="col-6">
              <small className="text-muted text-uppercase fw-bold d-block">Código SIS:</small>
              <span className="fw-bold text-primary fs-6">
                {estudiante.codigo_universitario || estudiante.codigo_sis}
              </span>
            </div>
            {estudiante.carrera && (
              <div className="col-12 pt-2 border-top">
                <small className="text-muted text-uppercase fw-bold d-block">Carrera:</small>
                <span className="fw-semibold text-secondary small">{estudiante.carrera}</span>
              </div>
            )}
            </div>
            </div>
          </div>
        </div>

        {/* Botón Siguiente Estudiante */}
        <button
          type="button"
          className="btn btn-success btn-lg w-100 py-3 fw-bold fs-5 shadow"
          onClick={onSiguiente}
        >
          <i className="bi bi-arrow-right-circle me-2"></i>Siguiente estudiante
        </button>
      </div>
      {modalFoto}
      </>
    );
  }

  // Variante ROJA: NO_HABILITADO o NO_ASOCIADO
  return (
    <>
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
          <div className="col-12 col-sm-auto">{foto}</div>
          <div className="col">
          <div className="row g-2">
          <div className="col-12">
            <small className="text-muted text-uppercase fw-bold d-block">Estudiante:</small>
            <span className="fs-5 fw-bold text-dark">
              {estudiante.nombre} {estudiante.apellido}
            </span>
          </div>
          <div className="col-6">
            <small className="text-muted text-uppercase fw-bold d-block">C.I.:</small>
            <span className="fw-bold text-dark">{estudiante.ci}</span>
          </div>
          <div className="col-6">
            <small className="text-muted text-uppercase fw-bold d-block">Código SIS:</small>
            <span className="fw-bold text-primary">
              {estudiante.codigo_universitario || estudiante.codigo_sis}
            </span>
          </div>
          {estudiante.carrera && (
            <div className="col-12 pt-2 border-top">
              <small className="text-muted text-uppercase fw-bold d-block">Carrera:</small>
              <span className="fw-semibold text-secondary small">{estudiante.carrera}</span>
            </div>
          )}
          </div>
          </div>
        </div>
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
    {modalFoto}
    </>
  );
}
