const VARIANTES = {
  HABILITADO: {
    titulo: 'HABILITADO',
    descripcion: 'El estudiante puede ingresar al examen.',
    icono: 'bi-check-circle-fill',
    clase: 'student-card--enabled',
    tipo: 'success',
  },
  NO_HABILITADO: {
    titulo: 'NO HABILITADO',
    descripcion: 'El estudiante no está habilitado para este examen.',
    icono: 'bi-x-circle-fill',
    clase: 'student-card--rejected',
    tipo: 'danger',
  },
  NO_ASOCIADO: {
    titulo: 'NO REGISTRADO EN ESTE EXAMEN',
    descripcion: 'El estudiante no está asociado al examen seleccionado.',
    icono: 'bi-x-circle-fill',
    clase: 'student-card--rejected',
    tipo: 'danger',
  },
};

function normalizarEstado(estado) {
  const valor = String(estado ?? '').trim().toUpperCase().replaceAll('-', '_').replaceAll(' ', '_');
  if (['HABILITADO', 'AUTORIZADO', 'APROBADO', 'ENABLED'].includes(valor)) return 'HABILITADO';
  if (['NO_ASOCIADO', 'NO_ASIGNADO', 'NOT_ASSOCIATED'].includes(valor)) return 'NO_ASOCIADO';
  if (['NO_HABILITADO', 'INHABILITADO', 'RECHAZADO', 'DENEGADO', 'DISABLED'].includes(valor)) return 'NO_HABILITADO';
  return null;
}

function valorEstudiante(estudiante, claves) {
  for (const clave of claves) {
    const valor = estudiante?.[clave];
    if (valor !== undefined && valor !== null && valor !== '') return valor;
  }
  return '—';
}

export default function StudentCard({ estado, estudiante, examen, onSiguienteEstudiante, fechaHora }) {
  const variante = VARIANTES[normalizarEstado(estado)];
  if (!variante) return null;

  // Función para formatear la fecha a DD/MM/AAAA HH:MM
  const formatearFechaHora = (isoString) => {
    if (!isoString) return '—';
    const fecha = new Date(isoString);
    return fecha.toLocaleString('es-BO', {
      day: '2-digit', month: '2-digit', year: 'numeric',
      hour: '2-digit', minute: '2-digit', hour12: false
    });
  };

  const nombre = valorEstudiante(estudiante, ['nombre_completo', 'nombre_estudiante', 'nombre']);
  const ci = valorEstudiante(estudiante, ['ci', 'documento']);
  const sis = valorEstudiante(estudiante, ['codigo_sis', 'codigo_universitario', 'sis']);
  const nombreExamen = typeof examen === 'string' ? examen : examen?.nombre;

  return (
    <section className={`student-card ${variante.clase}`} aria-labelledby="student-card-status">
      <div className="student-card__status" role="status" aria-live="polite">
        <i className={`bi ${variante.icono}`} aria-hidden="true" />
        <div>
          <h3 id="student-card-status" className="student-card__title">{variante.titulo}</h3>
          <p className="student-card__description">{variante.descripcion}</p>
        </div>
      </div>
      <dl className="student-card__details">
        <div><dt>Estudiante</dt><dd>{nombre}</dd></div>
        <div><dt>CI</dt><dd>{ci}</dd></div>
        <div><dt>Código SIS</dt><dd>{sis}</dd></div>
        <div><dt>Examen</dt><dd>{nombreExamen || '—'}</dd></div>
        
        {/* Nuevo campo de Fecha y Hora de Ingreso */}
        {fechaHora && (
          <div className="bg-light rounded p-1 mt-1 text-primary">
            <dt><i className="bi bi-clock-history me-1"></i>Hora de Ingreso</dt>
            <dd className="fw-bold m-0 ps-1">{formatearFechaHora(fechaHora)}</dd>
          </div>
        )}
      </dl>
      {onSiguienteEstudiante && (
        <button type="button" className={`btn btn-${variante.tipo} w-100 student-card__next mt-3`} onClick={onSiguienteEstudiante}>
          <i className="bi bi-arrow-right-circle me-2" aria-hidden="true" />Siguiente estudiante
        </button>
      )}
    </section>
  );
}