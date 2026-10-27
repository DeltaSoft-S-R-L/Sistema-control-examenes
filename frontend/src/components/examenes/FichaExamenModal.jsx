import React, { useCallback, useEffect, useState } from 'react';
import api from '../../services/api';

const getLista = (value) => (Array.isArray(value) ? value : []);

function agruparPorEstudiante(normas) {
  const grupos = new Map();
  normas.forEach((norma) => {
    const estudiante = norma.estudiante || {};
    const id = norma.id_estudiante ?? estudiante.id_estudiante ?? estudiante.id ?? 'sin-estudiante';
    if (!grupos.has(id)) {
      grupos.set(id, {
        id,
        nombre: [estudiante.nombre, estudiante.apellido].filter(Boolean).join(' ') || norma.nombre_estudiante || 'Estudiante',
        ci: estudiante.ci || estudiante.documento || norma.ci || 'CI no disponible',
        normas: [],
      });
    }
    grupos.get(id).normas.push(norma);
  });
  return Array.from(grupos.values());
}

function textoNorma(norma) {
  return norma.descripcion || norma.contenido || norma.texto || norma.nombre || 'Norma';
}

function SkeletonNormas() {
  return <div aria-label="Cargando normas" aria-busy="true" className="d-grid gap-2">
    {[1, 2, 3].map((item) => <div key={item} className="norma-skeleton" />)}
  </div>;
}

export default function FichaExamenModal({ isOpen, examen, onClose, renderAccionesNorma }) {
  const [normas, setNormas] = useState({ generales: [], particulares: [] });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const cargarNormas = useCallback(async () => {
    if (!examen) return;
    setLoading(true);
    setError('');
    try {
      const res = await api.get(`/examenes/${examen.id_examen}/normas`);
      const data = res.data?.data ?? res.data ?? {};
      setNormas({
        generales: getLista(data.normas_generales ?? data.generales),
        particulares: getLista(data.normas_particulares ?? data.particulares),
      });
    } catch (err) {
      console.error('Error al cargar normas del examen:', err);
      setError(err.response?.data?.message || 'No se pudieron cargar las normas del examen.');
    } finally {
      setLoading(false);
    }
  }, [examen]);

  useEffect(() => {
    if (isOpen && examen) cargarNormas();
  }, [isOpen, examen, cargarNormas]);

  if (!isOpen || !examen) return null;
  const tieneNormas = normas.generales.length + normas.particulares.length > 0;

  return <div className="modal fade show d-block" tabIndex="-1" role="dialog" aria-modal="true" aria-labelledby="ficha-examen-titulo" style={{ backgroundColor: 'rgba(0,0,0,.5)' }}>
    <div className="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
      <div className="modal-content border-0 rounded-4 shadow-lg">
        <div className="modal-header bg-light">
          <div>
            <h5 className="modal-title fw-bold" id="ficha-examen-titulo"><i className="bi bi-journal-text me-2 text-primary" />Ficha del examen</h5>
            <div className="small text-muted">#{examen.id_examen} · {examen.nombre}</div>
          </div>
          <button type="button" className="btn-close" onClick={onClose} aria-label="Cerrar" />
        </div>
        <div className="modal-body p-3 p-md-4">
          <div className="row g-3 mb-4">
            <div className="col-12 col-sm-6"><span className="small text-muted d-block">Asignatura</span><strong>{examen.asignatura?.nombre || 'Sin asignatura'}</strong></div>
            <div className="col-6 col-sm-3"><span className="small text-muted d-block">Fecha</span><strong>{examen.fecha ? String(examen.fecha).slice(0, 10) : '—'}</strong></div>
            <div className="col-6 col-sm-3"><span className="small text-muted d-block">Hora</span><strong>{examen.hora_inicio ? String(examen.hora_inicio).slice(0, 5) : '—'}</strong></div>
            {examen.descripcion && <div className="col-12"><span className="small text-muted d-block">Descripción</span>{examen.descripcion}</div>}
          </div>

          <section aria-labelledby="normas-titulo">
            <div className="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
              <h6 id="normas-titulo" className="fw-bold mb-0">Normas del examen</h6>
              {renderAccionesNorma?.({ examen, onNormasActualizadas: cargarNormas })}
            </div>
            {loading ? <SkeletonNormas /> : error ? <div className="alert alert-danger mb-0" role="alert">
              <div className="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2">
                <span><i className="bi bi-exclamation-triangle me-2" />{error}</span>
                <button type="button" className="btn btn-outline-danger btn-sm align-self-start align-self-sm-center" onClick={cargarNormas}>Reintentar</button>
              </div>
            </div> : !tieneNormas ? <div className="text-center text-muted border rounded-3 p-4">Este examen no tiene normas registradas</div> : <div className="row g-3">
              <div className="col-12 col-md-6">
                <div className="card h-100 border shadow-none">
                  <div className="card-header bg-light fw-semibold">Normas generales</div>
                  <div className="card-body">{normas.generales.length ? <ul className="list-group list-group-flush">{normas.generales.map((norma, index) => <li key={norma.id_norma || norma.id || index} className="list-group-item px-0 d-flex justify-content-between align-items-start gap-2"><span className="text-break">{textoNorma(norma)}</span>{renderAccionesNorma?.({ norma, examen, onNormasActualizadas: cargarNormas })}</li>)}</ul> : <p className="small text-muted mb-0">No hay normas generales.</p>}</div>
                </div>
              </div>
              <div className="col-12 col-md-6">
                <div className="card h-100 border shadow-none">
                  <div className="card-header bg-light fw-semibold">Normas particulares</div>
                  <div className="card-body">{normas.particulares.length ? <div className="d-grid gap-3">{agruparPorEstudiante(normas.particulares).map((grupo) => <div key={grupo.id} className="border-bottom pb-2"><div className="d-flex flex-wrap justify-content-between gap-1 mb-2"><strong>{grupo.nombre}</strong><span className="small text-muted">CI: {grupo.ci}</span></div><ul className="list-unstyled mb-0">{grupo.normas.map((norma, index) => <li key={norma.id_norma || norma.id || index} className="d-flex justify-content-between align-items-start gap-2 mb-2"><span className="small text-break">{textoNorma(norma)}</span>{renderAccionesNorma?.({ norma, examen, onNormasActualizadas: cargarNormas })}</li>)}</ul></div>)}</div> : <p className="small text-muted mb-0">No hay normas particulares.</p>}</div>
                </div>
              </div>
            </div>}
          </section>
        </div>
        <div className="modal-footer bg-light"><button type="button" className="btn btn-outline-secondary" onClick={onClose}>Cerrar</button></div>
      </div>
    </div>
  </div>;
}
