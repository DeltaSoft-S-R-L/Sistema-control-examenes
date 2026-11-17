import { useCallback, useEffect, useState } from 'react';
import api from '../services/api';

const tipos = [
  ['CAMBIO_AMBIENTE', 'Cambio de ambiente'],
  ['INGRESO_CONDICIONAL', 'Ingreso condicional'],
  ['IDENTIFICACION', 'Problema de identificación'],
  ['OTRO', 'Otra situación'],
];

export default function Incidencias() {
  const [examenes, setExamenes] = useState([]);
  const [incidencias, setIncidencias] = useState([]);
  const [examenFiltro, setExamenFiltro] = useState('');
  const [buscar, setBuscar] = useState('');
  const [cargando, setCargando] = useState(true);
  const [guardando, setGuardando] = useState(false);
  const [error, setError] = useState('');
  const [mensaje, setMensaje] = useState('');
  const [form, setForm] = useState({ id_examen: '', id_estudiante: '', id_asignacion_ambiente: '', tipo: '', descripcion: '' });
  const examenActual = examenes.find((examen) => String(examen.id_examen) === form.id_examen);
  const ambientesExamen = examenActual?.asignaciones_ambiente || [];

  const cargar = useCallback(async () => {
    setCargando(true); setError('');
    try {
      const params = {};
      if (examenFiltro) params.id_examen = examenFiltro;
      if (buscar.trim()) params.buscar = buscar.trim();
      const { data } = await api.get('/incidencias', { params });
      setIncidencias(data.data || []);
    } catch (err) {
      setError(err.response?.data?.message || 'No se pudieron cargar las incidencias.');
    } finally { setCargando(false); }
  }, [examenFiltro, buscar]);

  useEffect(() => { api.get('/examenes').then(({ data }) => setExamenes(data.data || data || [])).catch(() => setError('No se pudieron cargar los exámenes.')); }, []);
  useEffect(() => { cargar(); }, [cargar]);

  const enviar = async (event) => {
    event.preventDefault(); setGuardando(true); setError(''); setMensaje('');
    try {
      const cambioAmbiente = form.tipo === 'CAMBIO_AMBIENTE';
      await api.post(cambioAmbiente ? '/incidencias/cambio-ambiente' : '/incidencias', {
        ...form,
        id_estudiante: form.id_estudiante ? Number(form.id_estudiante) : null,
        id_examen: Number(form.id_examen),
        id_asignacion_ambiente: form.id_asignacion_ambiente ? Number(form.id_asignacion_ambiente) : null,
        descripcion: form.descripcion.trim(),
      });
      setForm({ id_examen: '', id_estudiante: '', id_asignacion_ambiente: '', tipo: '', descripcion: '' });
      setMensaje(cambioAmbiente ? 'El ambiente fue actualizado y el cambio quedó registrado.' : 'La incidencia quedó registrada.');
      await cargar();
    } catch (err) {
      setError(err.response?.data?.message || 'No se pudo guardar la incidencia. Revise los datos y sus permisos.');
    } finally { setGuardando(false); }
  };

  return <div className="container-fluid py-4">
    <div className="mb-4"><h2 className="fw-bold mb-1">Incidencias</h2><p className="text-muted mb-0">Registro de novedades del examen y consulta de eventos reportados.</p></div>
    {error && <div className="alert alert-danger" role="alert">{error}</div>}{mensaje && <div className="alert alert-success" role="status">{mensaje}</div>}
    <form className="card border-0 shadow-sm mb-4" onSubmit={enviar}><div className="card-header bg-white fw-semibold">Registrar novedad</div><div className="card-body row g-3">
      <div className="col-12 col-md-6"><label className="form-label" htmlFor="inc-examen">Examen *</label><select id="inc-examen" className="form-select" required value={form.id_examen} onChange={(e) => setForm({ ...form, id_examen: e.target.value, id_asignacion_ambiente: '' })}><option value="">Seleccione un examen</option>{examenes.map((e) => <option key={e.id_examen} value={e.id_examen}>{e.nombre} · {e.fecha}</option>)}</select></div>
      <div className="col-12 col-md-3"><label className="form-label" htmlFor="inc-tipo">Tipo *</label><select id="inc-tipo" className="form-select" required value={form.tipo} onChange={(e) => setForm({ ...form, tipo: e.target.value })}><option value="">Seleccione</option>{tipos.map(([value, label]) => <option key={value} value={value}>{label}</option>)}</select></div>
      {form.tipo === 'CAMBIO_AMBIENTE' && <>
        <div className="col-12 col-md-4"><label className="form-label" htmlFor="inc-estudiante">ID estudiante *</label><input id="inc-estudiante" className="form-control" type="number" min="1" required value={form.id_estudiante} onChange={(e) => setForm({ ...form, id_estudiante: e.target.value })} /></div>
        <div className="col-12 col-md-8"><label className="form-label" htmlFor="inc-ambiente">Nuevo ambiente *</label><select id="inc-ambiente" className="form-select" required value={form.id_asignacion_ambiente} onChange={(e) => setForm({ ...form, id_asignacion_ambiente: e.target.value })}><option value="">Seleccione un ambiente del examen</option>{ambientesExamen.map((asignacion) => <option key={asignacion.id_asignacion_ambiente} value={asignacion.id_asignacion_ambiente}>{asignacion.ambiente?.nombre || 'Ambiente'} · {asignacion.ambiente?.codigo || `#${asignacion.id_ambiente}`}</option>)}</select></div>
      </>}
      {form.tipo !== 'CAMBIO_AMBIENTE' && <div className="col-12 col-md-3"><label className="form-label" htmlFor="inc-estudiante-opcional">ID estudiante (opcional)</label><input id="inc-estudiante-opcional" className="form-control" type="number" min="1" value={form.id_estudiante} onChange={(e) => setForm({ ...form, id_estudiante: e.target.value })} /></div>}
      <div className="col-12"><label className="form-label" htmlFor="inc-descripcion">Motivo y respaldo / autoridad que autorizó *</label><textarea id="inc-descripcion" className="form-control" rows="3" maxLength="2000" required value={form.descripcion} onChange={(e) => setForm({ ...form, descripcion: e.target.value })} placeholder="Describa lo ocurrido y la autorización recibida, si corresponde." /></div>
      <div className="col-12 d-flex justify-content-end"><button className="btn btn-primary" disabled={guardando}>{guardando ? 'Guardando…' : 'Registrar incidencia'}</button></div>
    </div></form>
    <section className="card border-0 shadow-sm"><div className="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2"><h3 className="h5 fw-semibold mb-0">Eventos registrados</h3><div className="d-flex gap-2"><select className="form-select form-select-sm" aria-label="Filtrar por examen" value={examenFiltro} onChange={(e) => setExamenFiltro(e.target.value)}><option value="">Todos los exámenes</option>{examenes.map((e) => <option key={e.id_examen} value={e.id_examen}>{e.nombre}</option>)}</select><input className="form-control form-control-sm" aria-label="Buscar incidencias" placeholder="Buscar detalle o estudiante" value={buscar} onChange={(e) => setBuscar(e.target.value)} /></div></div>
      <div className="table-responsive"><table className="table table-hover align-middle mb-0"><thead className="table-light"><tr><th>Fecha y hora</th><th>Examen</th><th>Estudiante</th><th>Tipo</th><th>Descripción</th><th>Registrado por</th></tr></thead><tbody>
        {incidencias.map((i) => <tr key={i.id_incidencia}><td>{i.fecha_hora ? new Date(i.fecha_hora).toLocaleString() : '—'}</td><td>{i.examen?.nombre || `#${i.id_examen}`}</td><td>{i.estudiante ? `${i.estudiante.apellido || ''} ${i.estudiante.nombre || ''}`.trim() : '—'}</td><td>{i.tipo}</td><td className="text-break">{i.descripcion}</td><td>{i.usuario?.nombre || '—'}</td></tr>)}
        {!cargando && incidencias.length === 0 && <tr><td colSpan="6" className="text-center text-muted py-4">No hay incidencias para los filtros seleccionados.</td></tr>}
        {cargando && <tr><td colSpan="6" className="text-center py-4">Cargando…</td></tr>}
      </tbody></table></div></section>
  </div>;
}
