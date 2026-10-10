import React, { useCallback, useEffect, useMemo, useState } from 'react';
import api from '../services/api';

const obtenerDatos = (respuesta) => respuesta.data.data || respuesta.data || [];

export default function Habilitaciones() {
  const [examenes, setExamenes] = useState([]);
  const [estudiantes, setEstudiantes] = useState([]);
  const [habilitaciones, setHabilitaciones] = useState([]);
  const [idExamen, setIdExamen] = useState('');
  const [seleccionados, setSeleccionados] = useState([]);
  const [buscar, setBuscar] = useState('');
  const [terminoAplicado, setTerminoAplicado] = useState('');
  const [pagina, setPagina] = useState(1);
  const [totalPaginas, setTotalPaginas] = useState(1);
  const [loading, setLoading] = useState(true);
  const [guardando, setGuardando] = useState(false);
  const [revocando, setRevocando] = useState(null);
  const [mensaje, setMensaje] = useState(null);

  const cargarExamenes = async () => {
    try {
      const respuesta = await api.get('/examenes');
      setExamenes(obtenerDatos(respuesta));
    } catch (error) {
      console.error('Error al obtener exámenes:', error);
      setMensaje({ tipo: 'danger', texto: 'No se pudieron cargar los exámenes.' });
    }
  };

  const cargarEstudiantes = async (termino = '', paginaActual = 1) => {
    try {
      setLoading(true);
      const params = { page: paginaActual, estado: 'ACTIVO' };
      if (termino) params.buscar = termino;

      const respuesta = await api.get('/estudiantes', { params });
      setEstudiantes(obtenerDatos(respuesta));
      setTotalPaginas(respuesta.data.last_page ?? 1);
    } catch (error) {
      console.error('Error al obtener estudiantes:', error);
      setEstudiantes([]);
      setMensaje({ tipo: 'danger', texto: 'No se pudieron cargar los estudiantes.' });
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    cargarExamenes();
  }, []);

  useEffect(() => {
    cargarEstudiantes(terminoAplicado, pagina);
  }, [pagina, terminoAplicado]);

  const cargarHabilitaciones = useCallback(async () => {
    if (!idExamen) {
      setHabilitaciones([]);
      return;
    }

    try {
      const respuesta = await api.get('/habilitaciones', {
        params: { id_examen: idExamen, estado: 'HABILITADO' },
      });
      setHabilitaciones(obtenerDatos(respuesta));
    } catch (error) {
      console.error('Error al obtener habilitaciones:', error);
      setMensaje({ tipo: 'danger', texto: 'No se pudieron cargar las habilitaciones del examen.' });
    }
  }, [idExamen]);

  useEffect(() => {
    cargarHabilitaciones();
  }, [cargarHabilitaciones]);

  const idsPagina = useMemo(
    () => estudiantes.map((estudiante) => estudiante.id_estudiante),
    [estudiantes],
  );
  const paginaSeleccionada = idsPagina.length > 0 && idsPagina.every((id) => seleccionados.includes(id));

  const cambiarExamen = (event) => {
    setIdExamen(event.target.value);
    setSeleccionados([]);
    setMensaje(null);
  };

  const alternarEstudiante = (id) => {
    setSeleccionados((actuales) => (
      actuales.includes(id)
        ? actuales.filter((seleccionado) => seleccionado !== id)
        : [...actuales, id]
    ));
  };

  const alternarPagina = () => {
    setSeleccionados((actuales) => (
      paginaSeleccionada
        ? actuales.filter((id) => !idsPagina.includes(id))
        : [...new Set([...actuales, ...idsPagina])]
    ));
  };

  const buscarEstudiantes = (event) => {
    event.preventDefault();
    setPagina(1);
    setTerminoAplicado(buscar);
  };

  const habilitarSeleccionados = async () => {
    if (!idExamen || seleccionados.length === 0) return;

    setGuardando(true);
    setMensaje(null);

    const resultados = await Promise.allSettled(
      seleccionados.map((id_estudiante) => api.post('/habilitaciones', {
        id_estudiante,
        id_examen: Number(idExamen),
        estado: 'HABILITADO',
      })),
    );

    const creadas = resultados.filter((resultado) => resultado.status === 'fulfilled').length;
    const fallidas = resultados.length - creadas;

    if (creadas > 0) {
      setSeleccionados([]);
    }

    setMensaje({
      tipo: fallidas ? 'warning' : 'success',
      texto: fallidas
        ? `Se habilitaron ${creadas} estudiante(s). ${fallidas} no se pudieron habilitar; posiblemente ya están registrados para este examen.`
        : `${creadas} estudiante(s) habilitado(s) correctamente.`,
    });
    setGuardando(false);
    cargarHabilitaciones();
  };

  const revocarHabilitacion = async (habilitacion) => {
    const nombre = `${habilitacion.estudiante?.nombre ?? ''} ${habilitacion.estudiante?.apellido ?? ''}`.trim();
    if (!window.confirm(`¿Revocar la habilitación de ${nombre || 'este estudiante'} para el examen seleccionado?`)) return;

    setRevocando(habilitacion.id_habilitacion);
    setMensaje(null);
    try {
      await api.patch(`/habilitaciones/${habilitacion.id_habilitacion}`, {
        estado: 'NO_HABILITADO',
      });
      setHabilitaciones((actuales) => actuales.filter((actual) => actual.id_habilitacion !== habilitacion.id_habilitacion));
      setMensaje({ tipo: 'success', texto: `Se revocó la habilitación de ${nombre || 'el estudiante'}.` });
    } catch (error) {
      console.error('Error al revocar habilitación:', error);
      setMensaje({ tipo: 'danger', texto: 'No se pudo revocar la habilitación. Intente nuevamente.' });
    } finally {
      setRevocando(null);
    }
  };

  return (
    <div>
      <div className="mb-4">
        <h2 className="fw-bold mb-1">Habilitaciones</h2>
        <p className="text-muted mb-0">Seleccione los estudiantes que podrán rendir un examen.</p>
      </div>

      {mensaje && (
        <div className={`alert alert-${mensaje.tipo} alert-dismissible fade show`} role="alert">
          {mensaje.texto}
          <button type="button" className="btn-close" aria-label="Cerrar" onClick={() => setMensaje(null)} />
        </div>
      )}

      <div className="card border-0 shadow-sm rounded-3 mb-4">
        <div className="card-body row g-3 align-items-end">
          <div className="col-12 col-md-7">
            <label htmlFor="examen" className="form-label fw-semibold">Examen</label>
            <select id="examen" className="form-select" value={idExamen} onChange={cambiarExamen}>
              <option value="">Seleccione un examen</option>
              {examenes.map((examen) => (
                <option key={examen.id_examen} value={examen.id_examen}>
                  {examen.nombre} — {examen.fecha}
                </option>
              ))}
            </select>
          </div>
          <div className="col-12 col-md-5">
            <button
              type="button"
              className="btn btn-primary w-100"
              disabled={!idExamen || seleccionados.length === 0 || guardando}
              onClick={habilitarSeleccionados}
            >
              {guardando ? 'Habilitando...' : `Habilitar seleccionados${seleccionados.length ? ` (${seleccionados.length})` : ''}`}
            </button>
          </div>
        </div>
      </div>

      <div className="card border-0 shadow-sm rounded-3">
        <div className="card-header bg-white border-0 px-4 pt-4">
          <div className="d-flex flex-wrap justify-content-between gap-3 align-items-center">
            <div>
              <h5 className="fw-bold mb-1">Estudiantes activos</h5>
              <p className="text-muted small mb-0">Marque uno o varios estudiantes para habilitarlos.</p>
            </div>
            <form className="d-flex gap-2" onSubmit={buscarEstudiantes}>
              <input
                className="form-control"
                value={buscar}
                onChange={(event) => setBuscar(event.target.value)}
                placeholder="Nombre, CI o código"
                aria-label="Buscar estudiantes"
              />
              <button className="btn btn-outline-primary" type="submit">Buscar</button>
            </form>
          </div>
        </div>

        <div className="card-body p-0">
          <div className="table-responsive">
            <table className="table table-hover align-middle mb-0">
              <thead className="table-light">
                <tr>
                  <th className="ps-4" style={{ width: '48px' }}>
                    <input
                      className="form-check-input"
                      type="checkbox"
                      checked={paginaSeleccionada}
                      onChange={alternarPagina}
                      disabled={loading || idsPagina.length === 0}
                      aria-label="Seleccionar todos los estudiantes de esta página"
                    />
                  </th>
                  <th>Código</th>
                  <th>CI</th>
                  <th>Nombre completo</th>
                  <th>Correo</th>
                </tr>
              </thead>
              <tbody>
                {loading ? (
                  <tr><td colSpan="5" className="text-center py-5">Cargando estudiantes...</td></tr>
                ) : estudiantes.length === 0 ? (
                  <tr><td colSpan="5" className="text-center py-5 text-muted">No se encontraron estudiantes activos.</td></tr>
                ) : estudiantes.map((estudiante) => (
                  <tr key={estudiante.id_estudiante}>
                    <td className="ps-4">
                      <input
                        className="form-check-input"
                        type="checkbox"
                        checked={seleccionados.includes(estudiante.id_estudiante)}
                        onChange={() => alternarEstudiante(estudiante.id_estudiante)}
                        aria-label={`Seleccionar a ${estudiante.nombre} ${estudiante.apellido}`}
                      />
                    </td>
                    <td className="fw-semibold text-primary">{estudiante.codigo_universitario}</td>
                    <td>{estudiante.ci}</td>
                    <td>{estudiante.nombre} {estudiante.apellido}</td>
                    <td className="text-muted">{estudiante.correo || '-'}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>

        {!loading && totalPaginas > 1 && (
          <div className="card-footer bg-white border-0 d-flex justify-content-center gap-2 py-3">
            <button className="btn btn-sm btn-outline-secondary" disabled={pagina === 1} onClick={() => setPagina((actual) => actual - 1)}>Anterior</button>
            <span className="align-self-center small text-muted">Página {pagina} de {totalPaginas}</span>
            <button className="btn btn-sm btn-outline-secondary" disabled={pagina === totalPaginas} onClick={() => setPagina((actual) => actual + 1)}>Siguiente</button>
          </div>
        )}
      </div>

      <div className="card border-0 shadow-sm rounded-3 mt-4">
        <div className="card-header bg-white border-0 px-4 pt-4">
          <h5 className="fw-bold mb-1">Habilitaciones vigentes</h5>
          <p className="text-muted small mb-0">Revoque el acceso de un estudiante al examen seleccionado.</p>
        </div>
        <div className="card-body p-0">
          {!idExamen ? (
            <p className="text-center text-muted py-4 mb-0">Seleccione un examen para consultar sus habilitaciones.</p>
          ) : habilitaciones.length === 0 ? (
            <p className="text-center text-muted py-4 mb-0">No hay habilitaciones vigentes para este examen.</p>
          ) : (
            <div className="table-responsive">
              <table className="table table-hover align-middle mb-0">
                <thead className="table-light">
                  <tr><th className="ps-4">Código</th><th>CI</th><th>Estudiante</th><th>Correo</th><th className="text-end pe-4">Acción</th></tr>
                </thead>
                <tbody>
                  {habilitaciones.map((habilitacion) => {
                    const estudiante = habilitacion.estudiante || {};
                    return (
                      <tr key={habilitacion.id_habilitacion}>
                        <td className="ps-4 fw-semibold text-primary">{estudiante.codigo_universitario || '-'}</td>
                        <td>{estudiante.ci || '-'}</td>
                        <td>{estudiante.nombre} {estudiante.apellido}</td>
                        <td className="text-muted">{estudiante.correo || '-'}</td>
                        <td className="text-end pe-4">
                          <button
                            type="button"
                            className="btn btn-sm btn-outline-danger"
                            disabled={revocando !== null}
                            onClick={() => revocarHabilitacion(habilitacion)}
                          >
                            {revocando === habilitacion.id_habilitacion ? 'Revocando...' : 'Revocar'}
                          </button>
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
