import { useEffect, useState } from 'react';
import api from '../../services/api';

const formularioInicial = {
  idAsignatura: '',
  fecha: '',
  horaInicio: '',
  duracionMinutos: '',
  idAmbiente: '',
};

const obtenerDatos = (respuesta) => respuesta.data.data || respuesta.data || [];

export default function NuevoExamenModal({ mostrar, onCerrar, onRegistrado }) {
  const [formulario, setFormulario] = useState(formularioInicial);
  const [asignaturas, setAsignaturas] = useState([]);
  const [ambientes, setAmbientes] = useState([]);
  const [errores, setErrores] = useState({});
  const [errorGeneral, setErrorGeneral] = useState('');
  const [cargandoCatalogos, setCargandoCatalogos] = useState(false);
  const [guardando, setGuardando] = useState(false);

  useEffect(() => {
    if (!mostrar) return;

    let activo = true;
    setFormulario(formularioInicial);
    setErrores({});
    setErrorGeneral('');
    setCargandoCatalogos(true);

    Promise.all([api.get('/asignaturas'), api.get('/ambientes')])
      .then(([respuestaAsignaturas, respuestaAmbientes]) => {
        if (!activo) return;
        setAsignaturas(obtenerDatos(respuestaAsignaturas));
        setAmbientes(obtenerDatos(respuestaAmbientes));
      })
      .catch((error) => {
        if (!activo) return;
        setErrorGeneral(error.response?.data?.message || 'No se pudieron cargar las asignaturas y los ambientes.');
      })
      .finally(() => {
        if (activo) setCargandoCatalogos(false);
      });

    return () => {
      activo = false;
    };
  }, [mostrar]);

  if (!mostrar) return null;

  const cambiarCampo = (event) => {
    const { name, value } = event.target;
    setFormulario((actual) => ({ ...actual, [name]: value }));
    setErrores((actual) => ({ ...actual, [name]: '' }));
    setErrorGeneral('');
  };

  const validarFormulario = () => {
    const nuevosErrores = {};

    if (!formulario.idAsignatura) nuevosErrores.idAsignatura = 'Seleccione una asignatura.';
    if (!formulario.fecha) nuevosErrores.fecha = 'Ingrese la fecha del examen.';
    if (!formulario.horaInicio) nuevosErrores.horaInicio = 'Ingrese la hora de inicio.';
    if (!formulario.duracionMinutos) {
      nuevosErrores.duracionMinutos = 'Ingrese la duración.';
    } else if (!Number.isInteger(Number(formulario.duracionMinutos)) || Number(formulario.duracionMinutos) < 1) {
      nuevosErrores.duracionMinutos = 'La duración debe ser un número entero mayor a cero.';
    }
    if (!formulario.idAmbiente) nuevosErrores.idAmbiente = 'Seleccione un ambiente.';

    setErrores(nuevosErrores);
    return Object.keys(nuevosErrores).length === 0;
  };

  const enviarFormulario = async (event) => {
    event.preventDefault();
    if (!validarFormulario()) return;

    const asignatura = asignaturas.find((item) => String(item.id_asignatura) === formulario.idAsignatura);
    if (!asignatura) {
      setErrores({ idAsignatura: 'Seleccione una asignatura válida.' });
      return;
    }

    setGuardando(true);
    setErrorGeneral('');
    try {
      await api.post('/examenes', {
        id_asignatura: Number(formulario.idAsignatura),
        nombre: `Examen de ${asignatura.nombre}`,
        fecha: formulario.fecha,
        hora_inicio: formulario.horaInicio,
        duracion_minutos: Number(formulario.duracionMinutos),
        id_ambiente: Number(formulario.idAmbiente),
        estado: 'programado',
      });
      onRegistrado();
      onCerrar();
    } catch (error) {
      const validaciones = error.response?.data?.errors;
      if (validaciones) {
        setErrores({
          idAsignatura: validaciones.id_asignatura?.[0],
          fecha: validaciones.fecha?.[0],
          horaInicio: validaciones.hora_inicio?.[0],
          duracionMinutos: validaciones.duracion_minutos?.[0],
          idAmbiente: validaciones.id_ambiente?.[0],
        });
      } else {
        setErrorGeneral(error.response?.data?.message || 'No se pudo registrar el examen.');
      }
    } finally {
      setGuardando(false);
    }
  };

  const campoInvalido = (campo) => (errores[campo] ? 'is-invalid' : '');

  return (
    <>
      <div className="modal fade show d-block" tabIndex="-1" role="dialog" aria-modal="true" aria-labelledby="titulo-nuevo-examen">
        <div className="modal-dialog modal-dialog-centered">
          <div className="modal-content border-0 shadow">
            <div className="modal-header bg-primary text-white">
              <div>
                <h5 id="titulo-nuevo-examen" className="modal-title fw-bold">Registrar examen</h5>
                <p className="small mb-0 mt-1 text-white-50">Programe los datos y el ambiente del examen.</p>
              </div>
              <button type="button" className="btn-close btn-close-white" onClick={onCerrar} disabled={guardando} aria-label="Cerrar" />
            </div>

            <form onSubmit={enviarFormulario} noValidate>
              <div className="modal-body p-4">
                {errorGeneral && <div className="alert alert-danger" role="alert">{errorGeneral}</div>}
                <p className="small text-muted mb-4"><span className="text-danger">*</span> Campos obligatorios</p>

                <div className="mb-3">
                  <label htmlFor="idAsignatura" className="form-label fw-semibold">Asignatura <span className="text-danger">*</span></label>
                  <select id="idAsignatura" name="idAsignatura" className={`form-select ${campoInvalido('idAsignatura')}`} value={formulario.idAsignatura} onChange={cambiarCampo} disabled={cargandoCatalogos || guardando}>
                    <option value="">Seleccione una asignatura</option>
                    {asignaturas.map((asignatura) => <option key={asignatura.id_asignatura} value={asignatura.id_asignatura}>{asignatura.codigo} — {asignatura.nombre}</option>)}
                  </select>
                  {errores.idAsignatura && <div className="invalid-feedback">{errores.idAsignatura}</div>}
                </div>

                <div className="row g-3 mb-3">
                  <div className="col-12 col-sm-6">
                    <label htmlFor="fecha" className="form-label fw-semibold">Fecha <span className="text-danger">*</span></label>
                    <input id="fecha" name="fecha" type="date" className={`form-control ${campoInvalido('fecha')}`} value={formulario.fecha} onChange={cambiarCampo} disabled={guardando} />
                    {errores.fecha && <div className="invalid-feedback">{errores.fecha}</div>}
                  </div>
                  <div className="col-12 col-sm-6">
                    <label htmlFor="horaInicio" className="form-label fw-semibold">Hora de inicio <span className="text-danger">*</span></label>
                    <input id="horaInicio" name="horaInicio" type="time" className={`form-control ${campoInvalido('horaInicio')}`} value={formulario.horaInicio} onChange={cambiarCampo} disabled={guardando} />
                    {errores.horaInicio && <div className="invalid-feedback">{errores.horaInicio}</div>}
                  </div>
                </div>

                <div className="mb-3">
                  <label htmlFor="duracionMinutos" className="form-label fw-semibold">Duración (minutos) <span className="text-danger">*</span></label>
                  <input id="duracionMinutos" name="duracionMinutos" type="number" min="1" step="1" className={`form-control ${campoInvalido('duracionMinutos')}`} value={formulario.duracionMinutos} onChange={cambiarCampo} disabled={guardando} placeholder="Ej. 90" />
                  {errores.duracionMinutos && <div className="invalid-feedback">{errores.duracionMinutos}</div>}
                </div>

                <div>
                  <label htmlFor="idAmbiente" className="form-label fw-semibold">Ambiente <span className="text-danger">*</span></label>
                  <select id="idAmbiente" name="idAmbiente" className={`form-select ${campoInvalido('idAmbiente')}`} value={formulario.idAmbiente} onChange={cambiarCampo} disabled={cargandoCatalogos || guardando}>
                    <option value="">Seleccione un ambiente</option>
                    {ambientes.map((ambiente) => <option key={ambiente.id_ambiente} value={ambiente.id_ambiente}>{ambiente.codigo} — {ambiente.nombre} ({ambiente.capacidad} asientos)</option>)}
                  </select>
                  {errores.idAmbiente && <div className="invalid-feedback">{errores.idAmbiente}</div>}
                </div>
              </div>
              <div className="modal-footer">
                <button type="button" className="btn btn-outline-secondary" onClick={onCerrar} disabled={guardando}>Cancelar</button>
                <button type="submit" className="btn btn-primary" disabled={cargandoCatalogos || guardando}>{guardando ? 'Registrando...' : 'Registrar examen'}</button>
              </div>
            </form>
          </div>
        </div>
      </div>
      <div className="modal-backdrop fade show" />
    </>
  );
}
