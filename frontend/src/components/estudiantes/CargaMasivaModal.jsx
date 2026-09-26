import React, { useEffect, useRef, useState } from 'react';
import api from '../../services/api';

/* Plantilla CSV descargable */
const PLANTILLA_CSV =
  'ci,nombre,apellido,codigo_universitario,correo,estado\n' +
  '12345678,Juan,Pérez,202012345,juan.perez@uni.edu,ACTIVO\n' +
  '87654321,María,García,202054321,maria.garcia@uni.edu,ACTIVO\n';

function descargarPlantilla() {
  const blob = new Blob([PLANTILLA_CSV], { type: 'text/csv;charset=utf-8;' });
  const url  = URL.createObjectURL(blob);
  const a    = document.createElement('a');
  a.href     = url;
  a.download = 'plantilla_estudiantes.csv';
  a.click();
  URL.revokeObjectURL(url);
}

const ESTADO_INICIAL = {
  archivo:    null,
  nombreArchivo: '',
  cargando:   false,
  resultado:  null,   // { resumen, insertados, rechazados }
  errorGlobal: '',
};

export default function CargaMasivaModal({ mostrar, onCerrar, onCargaCompletada }) {
  const [estado, setEstado] = useState(ESTADO_INICIAL);
  const [arrastrandoSobre, setArrastrandoSobre] = useState(false);
  const inputRef = useRef(null);

  useEffect(() => {
    if (mostrar) setEstado(ESTADO_INICIAL);
  }, [mostrar]);

  if (!mostrar) return null;

  /* ─── Selección de archivo ─── */
  const procesarArchivo = (file) => {
    if (!file) return;

    const extension = file.name.split('.').pop().toLowerCase();
    if (!['csv', 'txt'].includes(extension)) {
      setEstado((prev) => ({
        ...prev,
        errorGlobal: 'Solo se aceptan archivos .csv. Para Excel, exporte la hoja como CSV.',
      }));
      return;
    }

    setEstado((prev) => ({
      ...prev,
      archivo:      file,
      nombreArchivo: file.name,
      resultado:    null,
      errorGlobal:  '',
    }));
  };

  const handleInputChange = (e) => procesarArchivo(e.target.files[0]);

  const handleDrop = (e) => {
    e.preventDefault();
    setArrastrandoSobre(false);
    procesarArchivo(e.dataTransfer.files[0]);
  };

  /* ─── Enviar al backend ─── */
  const handleEnviar = async () => {
    if (!estado.archivo) return;

    setEstado((prev) => ({ ...prev, cargando: true, errorGlobal: '', resultado: null }));

    const formData = new FormData();
    formData.append('archivo', estado.archivo);

    try {
      const res = await api.post('/estudiantes/carga-masiva', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });

      setEstado((prev) => ({
        ...prev,
        cargando:  false,
        resultado: res.data,
      }));

      if (res.data.resumen?.insertados > 0) {
        onCargaCompletada();
      }
    } catch (err) {
      const msg =
        err.response?.data?.message ||
        'Ocurrió un error al procesar el archivo. Verifique el formato.';

      setEstado((prev) => ({
        ...prev,
        cargando:   false,
        errorGlobal: msg,
      }));
    }
  };

  const handleReset = () => {
    setEstado(ESTADO_INICIAL);
    if (inputRef.current) inputRef.current.value = '';
  };

  const { archivo, nombreArchivo, cargando, resultado, errorGlobal } = estado;

  return (
    <>
      <div className="modal fade show d-block" tabIndex="-1" role="dialog" aria-modal="true">
        <div
          className="modal-dialog modal-xl modal-dialog-centered mx-auto"
          style={{ maxHeight: 'calc(100vh - 1rem)', width: 'calc(100% - 1rem)' }}
        >
          <div
            className="modal-content border-0 shadow"
            style={{ maxHeight: 'calc(100vh - 1rem)', display: 'flex', flexDirection: 'column' }}
          >
            {/* CABECERA */}
            <div className="modal-header px-3 px-md-4 py-3 bg-success text-white">
              <div className="pe-3">
                <h5 className="modal-title fw-bold">
                  <i className="bi bi-cloud-upload me-2"></i>Carga masiva de estudiantes (CSV)
                </h5>
                <p className="small mb-0 mt-1 text-white-50">
                  Suba un archivo CSV con los datos de los estudiantes. Los duplicados serán rechazados con un mensaje claro.
                </p>
              </div>
              <button
                type="button"
                className="btn-close btn-close-white"
                onClick={onCerrar}
                disabled={cargando}
                aria-label="Cerrar"
              />
            </div>

            {/* CUERPO */}
            <div className="modal-body px-3 px-md-4 py-4" style={{ overflowY: 'auto', flex: 1 }}>

              {/* Error global */}
              {errorGlobal && (
                <div className="alert alert-danger d-flex align-items-start gap-2" role="alert">
                  <i className="bi bi-exclamation-triangle-fill mt-1"></i>
                  <div>{errorGlobal}</div>
                </div>
              )}

              {/* Instrucciones + plantilla */}
              {!resultado && (
                <div className="alert alert-info border-0 d-flex align-items-start gap-3 mb-4">
                  <i className="bi bi-lightbulb-fill fs-5 mt-1 text-info"></i>
                  <div>
                    <p className="mb-1 fw-semibold">Formato esperado del archivo CSV:</p>
                    <p className="mb-1 small">
                      Columnas requeridas: <code>ci, nombre, apellido, codigo_universitario</code>
                      &nbsp;| Columnas opcionales: <code>correo, estado</code> (ACTIVO / INACTIVO)
                    </p>
                    <button
                      type="button"
                      className="btn btn-sm btn-outline-info mt-1"
                      onClick={descargarPlantilla}
                    >
                      <i className="bi bi-download me-1"></i>Descargar plantilla CSV
                    </button>
                  </div>
                </div>
              )}

              {/* Zona de arrastre */}
              {!resultado && (
                <div
                  className={`border rounded-3 p-5 text-center mb-4 ${
                    arrastrandoSobre ? 'border-success bg-success-subtle' : 'border-dashed bg-light'
                  }`}
                  style={{ cursor: 'pointer', borderStyle: 'dashed !important' }}
                  onDragOver={(e) => { e.preventDefault(); setArrastrandoSobre(true); }}
                  onDragLeave={() => setArrastrandoSobre(false)}
                  onDrop={handleDrop}
                  onClick={() => inputRef.current?.click()}
                >
                  <input
                    ref={inputRef}
                    type="file"
                    accept=".csv,.txt"
                    className="d-none"
                    onChange={handleInputChange}
                  />
                  {archivo ? (
                    <>
                      <i className="bi bi-file-earmark-check-fill text-success fs-1 mb-2 d-block"></i>
                      <p className="fw-semibold mb-0 text-success">{nombreArchivo}</p>
                      <p className="text-muted small mt-1">
                        {(archivo.size / 1024).toFixed(1)} KB — Haga clic para cambiar el archivo
                      </p>
                    </>
                  ) : (
                    <>
                      <i className="bi bi-cloud-upload fs-1 text-muted mb-2 d-block"></i>
                      <p className="fw-semibold mb-0">Arrastre su archivo CSV aquí</p>
                      <p className="text-muted small mt-1">o haga clic para seleccionarlo</p>
                    </>
                  )}
                </div>
              )}

              {/* Spinner de carga */}
              {cargando && (
                <div className="text-center py-4">
                  <div className="spinner-border text-success mb-3" style={{ width: '3rem', height: '3rem' }} />
                  <p className="fw-semibold">Procesando archivo, por favor espere...</p>
                </div>
              )}

              {/* ─── RESULTADO ─── */}
              {resultado && !cargando && (
                <div>
                  {/* Resumen */}
                  <div className="row g-3 mb-4">
                    <div className="col-12 col-md-4">
                      <div className="card border-0 bg-light text-center py-3">
                        <div className="h3 fw-bold text-secondary mb-0">{resultado.resumen.total_procesadas}</div>
                        <div className="small text-muted">Filas procesadas</div>
                      </div>
                    </div>
                    <div className="col-12 col-md-4">
                      <div className="card border-0 bg-success-subtle text-center py-3">
                        <div className="h3 fw-bold text-success mb-0">{resultado.resumen.insertados}</div>
                        <div className="small text-success">Insertados correctamente</div>
                      </div>
                    </div>
                    <div className="col-12 col-md-4">
                      <div className="card border-0 bg-danger-subtle text-center py-3">
                        <div className="h3 fw-bold text-danger mb-0">{resultado.resumen.rechazados}</div>
                        <div className="small text-danger">Rechazados</div>
                      </div>
                    </div>
                  </div>

                  {/* Insertados */}
                  {resultado.insertados.length > 0 && (
                    <div className="mb-4">
                      <h6 className="fw-bold text-success mb-2">
                        <i className="bi bi-check-circle-fill me-2"></i>
                        Estudiantes insertados ({resultado.insertados.length})
                      </h6>
                      <div className="table-responsive">
                        <table className="table table-sm table-hover align-middle">
                          <thead className="table-success">
                            <tr>
                              <th>Fila</th><th>CI</th><th>Nombre completo</th><th>Código SIS</th>
                            </tr>
                          </thead>
                          <tbody>
                            {resultado.insertados.map((r) => (
                              <tr key={r.id_estudiante}>
                                <td className="text-muted small">#{r.fila}</td>
                                <td>{r.ci}</td>
                                <td>{r.nombre}</td>
                                <td>{r.codigo_universitario}</td>
                              </tr>
                            ))}
                          </tbody>
                        </table>
                      </div>
                    </div>
                  )}

                  {/* Rechazados */}
                  {resultado.rechazados.length > 0 && (
                    <div className="mb-2">
                      <h6 className="fw-bold text-danger mb-2">
                        <i className="bi bi-x-circle-fill me-2"></i>
                        Filas rechazadas ({resultado.rechazados.length})
                      </h6>
                      <div className="table-responsive">
                        <table className="table table-sm table-hover align-middle">
                          <thead className="table-danger">
                            <tr>
                              <th>Fila</th><th>CI</th><th>Nombre</th><th>Motivo(s) del rechazo</th>
                            </tr>
                          </thead>
                          <tbody>
                            {resultado.rechazados.map((r, i) => (
                              <tr key={i}>
                                <td className="text-muted small">#{r.fila}</td>
                                <td>{r.ci}</td>
                                <td>{r.nombre}</td>
                                <td>
                                  <ul className="mb-0 ps-3 small">
                                    {r.motivos.map((m, j) => (
                                      <li key={j}>{m}</li>
                                    ))}
                                  </ul>
                                </td>
                              </tr>
                            ))}
                          </tbody>
                        </table>
                      </div>
                    </div>
                  )}
                </div>
              )}
            </div>

            {/* FOOTER */}
            <div className="modal-footer px-3 px-md-4 py-3 bg-white border-top">
              <div className="d-flex flex-column flex-sm-row justify-content-between w-100 gap-2">
                <div>
                  {resultado && (
                    <button type="button" className="btn btn-outline-secondary" onClick={handleReset}>
                      <i className="bi bi-arrow-clockwise me-1"></i>Nueva carga
                    </button>
                  )}
                </div>
                <div className="d-flex gap-2">
                  <button
                    type="button"
                    className="btn btn-outline-secondary"
                    onClick={onCerrar}
                    disabled={cargando}
                  >
                    {resultado ? 'Cerrar' : 'Cancelar'}
                  </button>
                  {!resultado && (
                    <button
                      type="button"
                      className="btn btn-success"
                      onClick={handleEnviar}
                      disabled={!archivo || cargando}
                    >
                      {cargando ? (
                        <>
                          <span className="spinner-border spinner-border-sm me-2" />
                          Procesando...
                        </>
                      ) : (
                        <>
                          <i className="bi bi-cloud-upload me-2"></i>
                          Cargar estudiantes
                        </>
                      )}
                    </button>
                  )}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div className="modal-backdrop fade show" />
    </>
  );
}
