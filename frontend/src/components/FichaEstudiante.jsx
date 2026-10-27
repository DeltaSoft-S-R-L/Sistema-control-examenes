import { useMemo } from 'react';
import StudentCard from './StudentCard';

const CAMPOS = [
  { etiqueta: 'CI', claves: ['ci', 'documento'] },
  { etiqueta: 'Código SIS', claves: ['codigo_sis', 'codigo_universitario', 'sis'] },
  { etiqueta: 'Correo', claves: ['correo', 'email'] },
  { etiqueta: 'Carrera', claves: ['carrera.nombre', 'carrera'] },
  { etiqueta: 'Estado', claves: ['estado'] },
];

function obtener(objeto, ruta) {
  return ruta.split('.').reduce((valor, clave) => valor?.[clave], objeto);
}

function obtenerEstudiante(respuesta) {
  return respuesta?.estudiante
    ?? respuesta?.data?.estudiante
    ?? respuesta?.data
    ?? respuesta?.resultado?.estudiante
    ?? respuesta?.resultado
    ?? respuesta;
}

function formatearFecha(fecha) {
  const valor = new Date(fecha);
  if (Number.isNaN(valor.getTime())) return String(fecha);
  return new Intl.DateTimeFormat('es-BO', {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(valor);
}

/** Presenta la respuesta de verificación. `children` permite insertar el estado de VER-02. */
export default function FichaEstudiante({
  verificacion,
  metodo,
  fechaConsulta,
  onNuevaVerificacion,
  estadoVerificacion,
  examen,
  onSiguienteEstudiante,
  children,
}) {
  const estudiante = useMemo(() => obtenerEstudiante(verificacion), [verificacion]);

  if (!verificacion || !estudiante) return null;

  const nombre = [estudiante.nombre, estudiante.apellido].filter(Boolean).join(' ').trim();
  const nombreCompleto = nombre || estudiante.nombre_completo || estudiante.nombre_estudiante || 'Estudiante';
  const metodoUsado = metodo
    ?? verificacion.metodo
    ?? verificacion.tipo_documento
    ?? verificacion.data?.metodo
    ?? 'No especificado';
  const consulta = fechaConsulta
    ?? verificacion.fecha_consulta
    ?? verificacion.consultado_en
    ?? verificacion.created_at
    ?? verificacion.data?.fecha_consulta
    ?? new Date().toISOString();

  return (
    <section className="card border-0 shadow-sm" aria-labelledby="ficha-estudiante-titulo">
      <div className="card-body p-4 p-md-5">
        <div className="d-flex flex-column flex-sm-row justify-content-between gap-3 mb-4">
          <div>
            <p className="text-uppercase text-primary fw-semibold small mb-2">Estudiante verificado</p>
            <h2 className="display-6 fw-bold mb-0" id="ficha-estudiante-titulo">{nombreCompleto}</h2>
          </div>
          <div className="text-sm-end text-muted small">
            <div><span className="fw-semibold text-body">Método:</span> {String(metodoUsado).toUpperCase()}</div>
            {consulta && <div className="mt-1"><span className="fw-semibold text-body">Consulta:</span> <time dateTime={consulta}>{formatearFecha(consulta)}</time></div>}
          </div>
        </div>

        <dl className="row g-3 mb-4">
          {CAMPOS.map(({ etiqueta, claves }) => {
            const dato = claves.map((clave) => obtener(estudiante, clave)).find((item) => item !== undefined && item !== null && item !== '');
            const valor = dato && typeof dato === 'object' ? (dato.nombre ?? dato.codigo ?? '—') : dato;
            return (
              <div className="col-12 col-sm-6" key={etiqueta}>
                <dt className="small text-muted fw-semibold">{etiqueta}</dt>
                <dd className="mb-0 fw-medium">{valor ?? '—'}</dd>
              </div>
            );
          })}
        </dl>

        {(estadoVerificacion || verificacion.estado_verificacion || verificacion.estado || verificacion.resultado) && (
          <div className="mb-4">
            <StudentCard
              estado={estadoVerificacion ?? verificacion.estado_verificacion ?? verificacion.estado ?? verificacion.resultado}
              estudiante={estudiante}
              examen={examen ?? verificacion.examen ?? verificacion.data?.examen}
              onSiguienteEstudiante={onSiguienteEstudiante ?? onNuevaVerificacion}
            />
          </div>
        )}
        {children && <div className="mb-4">{children}</div>}

        {!onSiguienteEstudiante && !estadoVerificacion && !verificacion.estado_verificacion && !verificacion.estado && !verificacion.resultado && (
          <button type="button" className="btn btn-outline-primary w-100 w-sm-auto" onClick={onNuevaVerificacion}>
            <i className="bi bi-arrow-repeat me-2" aria-hidden="true" />Nueva verificación
          </button>
        )}
      </div>
    </section>
  );
}
