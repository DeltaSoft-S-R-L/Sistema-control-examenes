import { useEffect, useState } from 'react';
import ModalExpulsionEstudiante from './ModalExpulsionEstudiante';

const etiquetasMotivo = {
  INTENTO_COPIA: 'Intento de copia o fraude académico',
  SUPLANTACION_IDENTIDAD: 'Suplantación de identidad',
  USO_DISPOSITIVO_NO_AUTORIZADO: 'Uso de celular o dispositivo no autorizado',
  CONDUCTA_INAPROPIADA: 'Conducta inapropiada o alteración del orden',
  OTRO: 'Otro motivo disciplinario',
};

// Mapeo amigable de los motivos de rechazo desde intento_ingreso (BUG-SPR4-02)
const etiquetasRechazo = {
  'EXAMEN_NO_CORRESPONDIENTE': 'Examen ajeno',
  'AULA_INCORRECTA': 'Aula incorrecta',
  'ESTUDIANTE_NO_HABILITADO': 'No habilitado',
  'REINGRESO_DUPLICADO': 'Intento de doble ingreso'
};

export default function IngresosSesion({ ingresos = [], examenSeleccionado, onExpulsionActualizada }) {
  const [filas, setFilas] = useState(ingresos);
  const [ingresoSeleccionado, setIngresoSeleccionado] = useState(null);
  
  useEffect(() => setFilas(ingresos), [ingresos]);
  
  const aplicarExpulsion = (data) => {
    setFilas((anteriores) => anteriores.map((item) => Number(item.id_ingreso) === Number(data.id_ingreso) ? { ...item, estado: 'EXPULSADO', expulsion: data } : item));
    onExpulsionActualizada?.(data);
  };

  return (
    <>
      <section className="card shadow-sm mt-4" aria-labelledby="ingresos-sesion-title">
        <div className="card-header bg-white">
          <h2 id="ingresos-sesion-title" className="h5 mb-0">Historial de Accesos a la Sesión</h2>
        </div>
        <div className="card-body">
          {!examenSeleccionado ? (
            <p className="text-muted mb-0">Seleccione un examen para consultar los ingresos.</p>
          ) : filas.length === 0 ? (
            <p className="text-muted mb-0">Aún no hay ingresos ni intentos registrados para este examen.</p>
          ) : (
            <div className="table-responsive">
              <table className="table table-striped align-middle mb-0">
                <thead>
                  <tr>
                    <th>Estudiante</th>
                    <th>CI / SIS</th>
                    <th>Aula</th>
                    <th>Hora del Evento</th>
                    <th>Estado</th>
                    <th>Acción</th>
                  </tr>
                </thead>
                <tbody>
                  {filas.map((ingreso) => {
                    const estudiante = ingreso.habilitacion?.estudiante || ingreso.estudiante || {};
                    const expulsion = ingreso.expulsion;
                    const ambiente = ingreso.asignacion_ambiente?.ambiente || ingreso.asignacionAmbiente?.ambiente;
                    const nombre = `${estudiante.nombre || estudiante.nombres || ''} ${estudiante.apellido || estudiante.apellidos || ''}`.trim();
                    const ciSis = estudiante.ci ? `${estudiante.ci} / ${estudiante.codigo_universitario || estudiante.codigo_sis || ''}` : '—';
                    
                    return (
                      <tr key={ingreso.id_ingreso}>
                        <td>{nombre || '—'}</td>
                        <td>{ciSis}</td>
                        <td>{ambiente?.nombre || ambiente?.codigo || '—'}</td>
                        <td>{ingreso.fecha_hora ? new Date(ingreso.fecha_hora).toLocaleString('es-BO') : '—'}</td>
                        <td>
                          {/* BUG-SPR4-02: Renderizado visual diferenciado según el estado */}
                          {ingreso.estado === 'RECHAZADO' ? (
                            <>
                              <span className="badge text-bg-warning">RECHAZADO</span>
                              <div className="small text-warning-emphasis">
                                {etiquetasRechazo[ingreso.motivo_rechazo] || ingreso.motivo_rechazo}
                              </div>
                            </>
                          ) : expulsion ? (
                            <>
                              <span className="badge text-bg-danger">EXPULSADO</span>
                              <div className="small text-danger-emphasis">
                                {etiquetasMotivo[expulsion.motivo] || expulsion.motivo}
                              </div>
                            </>
                          ) : (
                            <span className="badge text-bg-success">EN SALA</span>
                          )}
                        </td>
                        <td>
                          {(!expulsion && ingreso.estado !== 'RECHAZADO') && (
                            <button type="button" className="btn btn-danger btn-sm" onClick={() => setIngresoSeleccionado(ingreso)}>
                              Registrar Expulsión
                            </button>
                          )}
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
          )}
        </div>
      </section>
      <ModalExpulsionEstudiante 
        isOpen={Boolean(ingresoSeleccionado)} 
        onClose={() => setIngresoSeleccionado(null)} 
        estudiante={ingresoSeleccionado?.habilitacion?.estudiante} 
        idExamen={examenSeleccionado} 
        idIngreso={ingresoSeleccionado?.id_ingreso} 
        onExpulsionExitosa={aplicarExpulsion} 
      />
    </>
  );
}