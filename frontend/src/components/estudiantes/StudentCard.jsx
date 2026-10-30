import React from 'react';

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
  if (!estudiante) return null;

  const esHabilitado = habilitacion?.estado === 'HABILITADO';
  const esNoAsociado = habilitacion?.estado === 'NO_ASOCIADO';

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
