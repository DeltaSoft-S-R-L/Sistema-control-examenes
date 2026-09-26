import React, { useEffect, useState } from 'react';
import api from '../services/api';
import NuevoEstudianteModal   from '../components/estudiantes/NuevoEstudianteModal';
import EditarEstudianteModal  from '../components/estudiantes/EditarEstudianteModal';
import CargaMasivaModal       from '../components/estudiantes/CargaMasivaModal';

export default function Estudiantes() {
  const [estudiantes,      setEstudiantes]      = useState([]);
  const [totalEstudiantes, setTotalEstudiantes] = useState(0);
  const [loading,          setLoading]          = useState(true);

  // Búsqueda
  const [buscar,     setBuscar]     = useState('');
  const [filtroCi,   setFiltroCi]   = useState('');
  const [filtroCodigo, setFiltroCodigo] = useState('');

  // Modales
  const [mostrarNuevo,        setMostrarNuevo]        = useState(false);
  const [mostrarEditar,       setMostrarEditar]       = useState(false);
  const [mostrarCargaMasiva,  setMostrarCargaMasiva]  = useState(false);
  const [estudianteEditando,  setEstudianteEditando]  = useState(null);

  // Notificaciones
  const [notificacion, setNotificacion] = useState({ visible: false, tipo: '', mensaje: '' });

  // ────────────────────────────────────────────
  const mostrarNotificacion = (tipo, mensaje) => {
    setNotificacion({ visible: true, tipo, mensaje });
    window.setTimeout(() => setNotificacion({ visible: false, tipo: '', mensaje: '' }), 5000);
  };

  const fetchEstudiantes = async (params = {}) => {
    try {
      setLoading(true);
      const res  = await api.get('/estudiantes', { params });
      const datos = res.data.data || res.data || [];
      setEstudiantes(datos);
      setTotalEstudiantes(res.data.total ?? datos.length);
    } catch (err) {
      console.error('Error al obtener estudiantes:', err);
      setEstudiantes([]);
      setTotalEstudiantes(0);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => { fetchEstudiantes(); }, []);

  // ── Búsqueda general ──
  const handleBusquedaGeneral = (e) => {
    e.preventDefault();
    const params = {};
    if (buscar.trim())      params.buscar     = buscar.trim();
    if (filtroCi.trim())    params.ci         = filtroCi.trim();
    if (filtroCodigo.trim()) params.codigo_sis = filtroCodigo.trim();
    fetchEstudiantes(params);
  };

  const limpiarBusqueda = () => {
    setBuscar('');
    setFiltroCi('');
    setFiltroCodigo('');
    fetchEstudiantes();
  };

  // ── Callbacks de modales ──
  const handleRegistrado = () => {
    mostrarNotificacion('success', 'Estudiante registrado correctamente.');
    fetchEstudiantes();
  };

  const handleActualizado = (estudianteActualizado) => {
    setEstudiantes((prev) =>
      prev.map((e) =>
        e.id_estudiante === estudianteActualizado.id_estudiante ? estudianteActualizado : e
      )
    );
    mostrarNotificacion('success', 'Datos del estudiante actualizados correctamente.');
  };

  const handleCargaCompletada = () => {
    fetchEstudiantes();
  };

  const abrirEditar = (est) => {
    setEstudianteEditando(est);
    setMostrarEditar(true);
  };

  // ── Estado → badge ──
  const claseEstado = (estado) =>
    estado?.toUpperCase() === 'ACTIVO' ? 'bg-success' : 'bg-secondary';

  return (
    <div>
      {/* ── Encabezado ── */}
      <div className="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
          <h2 className="fw-bold mb-1">Estudiantes</h2>
          <p className="text-muted mb-0">
            Directorio de estudiantes habilitados para rendir exámenes
          </p>
        </div>

        <div className="d-flex gap-2 flex-wrap">
          <button
            type="button"
            className="btn btn-outline-success"
            onClick={() => setMostrarCargaMasiva(true)}
          >
            <i className="bi bi-cloud-upload me-2"></i>Carga masiva CSV
          </button>
          <button
            type="button"
            className="btn btn-primary"
            onClick={() => setMostrarNuevo(true)}
          >
            <i className="bi bi-person-plus me-2"></i>Nuevo estudiante
          </button>
        </div>
      </div>

      {/* ── Notificación ── */}
      {notificacion.visible && (
        <div
          className={`alert alert-${notificacion.tipo} alert-dismissible fade show`}
          role="alert"
        >
          <i className={`bi ${notificacion.tipo === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'} me-2`}></i>
          {notificacion.mensaje}
          <button
            type="button"
            className="btn-close"
            onClick={() => setNotificacion({ visible: false, tipo: '', mensaje: '' })}
            aria-label="Cerrar"
          />
        </div>
      )}

      {/* ── Buscador ── */}
      <div className="card border-0 shadow-sm rounded-3 mb-4">
        <div className="card-body">
          <form onSubmit={handleBusquedaGeneral} className="row g-2 align-items-end">

            {/* Búsqueda general */}
            <div className="col-12 col-lg-4">
              <label className="form-label small fw-semibold mb-1">Búsqueda general</label>
              <div className="input-group">
                <span className="input-group-text bg-light">
                  <i className="bi bi-search"></i>
                </span>
                <input
                  type="text"
                  className="form-control"
                  placeholder="Nombre, CI o código..."
                  value={buscar}
                  onChange={(e) => setBuscar(e.target.value)}
                />
              </div>
            </div>

            {/* Filtro CI exacto */}
            <div className="col-12 col-md-4 col-lg-3">
              <label className="form-label small fw-semibold mb-1">CI exacto</label>
              <input
                type="text"
                className="form-control"
                placeholder="Ej. 12345678"
                value={filtroCi}
                onChange={(e) => setFiltroCi(e.target.value)}
              />
            </div>

            {/* Filtro código SIS exacto */}
            <div className="col-12 col-md-4 col-lg-3">
              <label className="form-label small fw-semibold mb-1">Código SIS exacto</label>
              <input
                type="text"
                className="form-control"
                placeholder="Ej. 202012345"
                value={filtroCodigo}
                onChange={(e) => setFiltroCodigo(e.target.value)}
              />
            </div>

            {/* Botones */}
            <div className="col-12 col-lg-2 d-flex gap-2">
              <button type="submit" className="btn btn-primary flex-fill">
                Buscar
              </button>
              {(buscar || filtroCi || filtroCodigo) && (
                <button
                  type="button"
                  className="btn btn-outline-secondary"
                  onClick={limpiarBusqueda}
                  title="Limpiar filtros"
                >
                  <i className="bi bi-x-lg"></i>
                </button>
              )}
            </div>
          </form>
        </div>
      </div>

      {/* ── Tabla ── */}
      <div className="card border-0 shadow-sm rounded-3">
        <div className="card-header bg-white border-0 px-4 pt-4 pb-3">
          <div className="d-flex justify-content-between align-items-center">
            <div>
              <h5 className="fw-bold mb-1">Estudiantes registrados</h5>
              <p className="text-muted small mb-0">
                Información de estudiantes disponibles en el sistema
              </p>
            </div>
            {!loading && (
              <span className="badge text-bg-light border">
                {totalEstudiantes}{' '}
                {totalEstudiantes === 1 ? 'estudiante' : 'estudiantes'}
              </span>
            )}
          </div>
        </div>

        <div className="card-body p-0">
          <div className="table-responsive">
            <table className="table table-hover align-middle mb-0">
              <thead className="table-light">
                <tr>
                  <th className="ps-4">Código SIS</th>
                  <th>CI</th>
                  <th>Nombre completo</th>
                  <th>Correo</th>
                  <th>Estado</th>
                  <th className="text-center">Acciones</th>
                </tr>
              </thead>

              <tbody>
                {loading ? (
                  <tr>
                    <td colSpan="6" className="text-center py-5">
                      <div className="spinner-border spinner-border-sm text-primary me-2"></div>
                      Cargando estudiantes...
                    </td>
                  </tr>
                ) : estudiantes.length === 0 ? (
                  <tr>
                    <td colSpan="6" className="text-center py-5">
                      <div
                        className="bg-primary-subtle text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                        style={{ width: 48, height: 48 }}
                      >
                        <i className="bi bi-people fs-5"></i>
                      </div>
                      <h6 className="fw-semibold">No se encontraron estudiantes</h6>
                      <p className="text-muted small mb-0">
                        Registre un estudiante o cambie los criterios de búsqueda.
                      </p>
                    </td>
                  </tr>
                ) : (
                  estudiantes.map((est) => (
                    <tr key={est.id_estudiante}>
                      <td className="ps-4 fw-semibold text-primary">
                        {est.codigo_universitario}
                      </td>
                      <td>{est.ci}</td>
                      <td>
                        <div className="fw-semibold">{est.nombre} {est.apellido}</div>
                      </td>
                      <td className="text-muted">{est.correo || '-'}</td>
                      <td>
                        <span className={`badge ${claseEstado(est.estado)}`}>
                          {est.estado || 'Sin estado'}
                        </span>
                      </td>
                      <td className="text-center">
                        <button
                          type="button"
                          className="btn btn-sm btn-outline-warning"
                          title="Editar estudiante"
                          onClick={() => abrirEditar(est)}
                        >
                          <i className="bi bi-pencil-square me-1"></i>Editar
                        </button>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </div>
      </div>

      {/* ── Modales ── */}
      <NuevoEstudianteModal
        mostrar={mostrarNuevo}
        onCerrar={() => setMostrarNuevo(false)}
        onRegistrado={handleRegistrado}
      />

      <EditarEstudianteModal
        mostrar={mostrarEditar}
        estudiante={estudianteEditando}
        onCerrar={() => { setMostrarEditar(false); setEstudianteEditando(null); }}
        onActualizado={handleActualizado}
      />

      <CargaMasivaModal
        mostrar={mostrarCargaMasiva}
        onCerrar={() => setMostrarCargaMasiva(false)}
        onCargaCompletada={handleCargaCompletada}
      />
    </div>
  );
}