import { useEffect, useMemo, useState } from 'react';
import api from '../services/api';

const columnasAsistencia = [
  ['codigo_universitario', 'Código SIS'], ['ci', 'C.I.'], ['apellido', 'Apellidos'], ['nombre', 'Nombres'],
  ['asistencia', 'Estado'], ['fecha_hora_ingreso', 'Hora de ingreso'], ['ambiente', 'Ambiente'],
];
const columnasIntentos = [
  ['fecha_hora', 'Fecha y hora'], ['motivo', 'Tipo'], ['apellido', 'Apellidos'], ['nombre', 'Nombres'],
  ['descripcion', 'Detalle'], ['operador', 'Operador'], ['ambiente', 'Ambiente'],
];

function csvCell(value) {
  const text = value == null ? '' : String(value);
  return `"${text.replaceAll('"', '""')}"`;
}

export default function Reportes() {
  const [examenes, setExamenes] = useState([]);
  const [examenId, setExamenId] = useState('');
  const [tipo, setTipo] = useState('asistencia');
  const [reporte, setReporte] = useState(null);
  const [cargandoLista, setCargandoLista] = useState(true);
  const [cargando, setCargando] = useState(false);
  const [seguimiento, setSeguimiento] = useState(false);
  const [error, setError] = useState('');

  useEffect(() => {
    api.get('/reportes/examenes')
      .then(({ data }) => setExamenes(Array.isArray(data) ? data : []))
      .catch(() => setError('No se pudieron cargar los exámenes disponibles.'))
      .finally(() => setCargandoLista(false));
  }, []);

  useEffect(() => {
    if (!seguimiento || !examenId) return undefined;
    const timer = window.setInterval(async () => {
      try {
        const { data } = await api.get(`/reportes/examenes/${examenId}/${tipo}`);
        setReporte(data);
      } catch {
        setError('No se pudo actualizar el reporte automáticamente.');
      }
    }, 15000);
    return () => window.clearInterval(timer);
  }, [examenId, tipo, seguimiento]);

  const cargarReporte = async (event) => {
    event?.preventDefault();
    if (!examenId) return;
    setCargando(true); setError(''); setReporte(null);
    try {
      const { data } = await api.get(`/reportes/examenes/${examenId}/${tipo}`);
      setReporte(data);
      setSeguimiento(true);
    } catch (err) {
      setError(err.response?.data?.message || 'No se pudo generar el reporte.');
    } finally { setCargando(false); }
  };

  const columnas = tipo === 'asistencia' ? columnasAsistencia : columnasIntentos;
  const filas = useMemo(() => reporte?.data || [], [reporte]);
  const descargarCsv = () => {
    if (!reporte) return;
    const lines = [columnas.map(([, label]) => csvCell(label)).join(';'), ...filas.map((row) => columnas.map(([key]) => csvCell(row[key])).join(';'))];
    const blob = new Blob([`\uFEFF${lines.join('\r\n')}`], { type: 'text/csv;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url; link.download = `reporte-${tipo}-${examenId}.csv`; link.click();
    URL.revokeObjectURL(url);
  };

  return <div className="container-fluid py-4">
    <div className="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
      <div><h2 className="fw-bold mb-1">Reportes de examen</h2><p className="text-muted mb-0">Asistencia, horarios e intentos de ingreso registrados.</p></div>
    </div>
    <form className="card border-0 shadow-sm mb-4" onSubmit={cargarReporte}>
      <div className="card-body row g-3 align-items-end">
        <div className="col-12 col-lg-6"><label className="form-label" htmlFor="reporte-examen">Examen</label>
          <select id="reporte-examen" className="form-select" value={examenId} onChange={(e) => { setExamenId(e.target.value); setReporte(null); setSeguimiento(false); }} required disabled={cargandoLista}>
            <option value="">{cargandoLista ? 'Cargando…' : 'Seleccione un examen'}</option>
            {examenes.map((examen) => <option key={examen.id_examen} value={examen.id_examen}>{examen.nombre} · {examen.asignatura?.nombre || 'Sin asignatura'} · {examen.fecha}</option>)}
          </select>
        </div>
        <div className="col-12 col-lg-3"><label className="form-label" htmlFor="reporte-tipo">Reporte</label>
          <select id="reporte-tipo" className="form-select" value={tipo} onChange={(e) => { setTipo(e.target.value); setReporte(null); setSeguimiento(false); }}>
            <option value="asistencia">Asistencia y ausentes</option><option value="intentos">Intentos y novedades</option>
          </select>
        </div>
        <div className="col-12 col-lg-3"><button className="btn btn-primary w-100" disabled={!examenId || cargando}>{cargando ? 'Generando…' : 'Generar reporte'}</button></div>
      </div>
    </form>
    {error && <div className="alert alert-danger" role="alert">{error}</div>}
    {reporte && <section aria-live="polite">
      <div className="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div><h3 className="h5 fw-bold mb-1">{reporte.examen?.nombre}</h3><span className="text-muted">{reporte.examen?.fecha}{reporte.examen?.asignatura ? ` · ${reporte.examen.asignatura}` : ''}</span><span className="d-block small text-success">Actualización automática cada 15 segundos</span></div>
        <div className="d-flex gap-2 report-actions"><button className="btn btn-outline-success" onClick={descargarCsv}><i className="bi bi-filetype-csv me-1" />Descargar CSV</button><button className="btn btn-outline-secondary" onClick={() => window.print()}><i className="bi bi-printer me-1" />Imprimir / PDF</button></div>
      </div>
      <div className="row g-3 mb-3">{Object.entries(reporte.resumen || {}).map(([label, value]) => <div className="col-6 col-lg-3" key={label}><div className="card border-0 shadow-sm h-100"><div className="card-body"><div className="text-muted small text-capitalize">{label.replaceAll('_', ' ')}</div><div className="fs-4 fw-bold">{value}</div></div></div></div>)}</div>
      <div className="card border-0 shadow-sm"><div className="table-responsive"><table className="table table-hover align-middle mb-0"><thead className="table-light"><tr>{columnas.map(([, label]) => <th key={label}>{label}</th>)}</tr></thead><tbody>
        {filas.map((row, index) => <tr key={row.id_habilitacion || row.id_intento || row.id_incidencia || index}>{columnas.map(([key]) => <td key={key}>{row[key] || '—'}</td>)}</tr>)}
        {!filas.length && <tr><td className="text-center text-muted py-4" colSpan={columnas.length}>No hay registros para este examen.</td></tr>}
      </tbody></table></div></div>
    </section>}
    <style>{'@media print { .report-actions, form, .sidebar, nav { display:none !important } .container-fluid { padding:0 !important } .card { box-shadow:none !important; border:1px solid #ddd !important } }'}</style>
  </div>;
}
