import React, { useEffect, useState } from 'react';
import api from '../../services/api';

export default function ImportarEstudiantesModal({
  mostrar,
  onCerrar,
  onImportado,
}) {
  const [archivo, setArchivo] = useState(null);
  const [error, setError] = useState('');
  const [importando, setImportando] = useState(false);
  const [resultado, setResultado] = useState(null);

  useEffect(() => {
    if (mostrar) {
      setArchivo(null);
      setError('');
      setResultado(null);
      setImportando(false);
    }
  }, [mostrar]);

  if (!mostrar) {
    return null;
  }

  const handleArchivo = (e) => {
    const seleccionado = e.target.files?.[0] || null;

    setError('');
    setResultado(null);

    if (!seleccionado) {
      setArchivo(null);
      return;
    }

    const extension = seleccionado.name
      .split('.')
      .pop()
      ?.toLowerCase();

    if (extension !== 'csv') {
      setArchivo(null);
      setError('Seleccione un archivo en formato CSV.');
      e.target.value = '';
      return;
    }

    setArchivo(seleccionado);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();

    if (!archivo) {
      setError('Seleccione un archivo CSV para continuar.');
      return;
    }

    setImportando(true);
    setError('');
    setResultado(null);

    try {
      const formData = new FormData();
      formData.append('archivo', archivo);

      const response = await api.post(
        '/estudiantes/importar',
        formData,
        {
          headers: {
            'Content-Type': 'multipart/form-data',
          },
        }
      );

      setResultado(response.data);

      if (response.data.importados > 0) {
        onImportado(response.data);
      }
    } catch (err) {
      const mensaje =
        err.response?.data?.message ||
        'No se pudo importar el archivo de estudiantes.';

      setError(mensaje);
    } finally {
      setImportando(false);
    }
  };

  const cerrarModal = () => {
    if (!importando) {
      onCerrar();
    }
  };

  return (
    <>
      <div
        className="modal fade show d-block"
        tabIndex="-1"
        role="dialog"
        aria-modal="true"
      >
        <div className="modal-dialog modal-lg modal-dialog-centered mx-auto">
          <div className="modal-content border-0 shadow">
            <div className="modal-header px-3 px-md-4 py-3 bg-primary text-white">
              <div className="pe-3">
                <h5 className="modal-title fw-bold">
                  Importar estudiantes
                </h5>

                <p className="small mb-0 mt-1 text-white-50">
                  Cargue una lista de estudiantes mediante un archivo CSV.
                </p>
              </div>

              <button
                type="button"
                className="btn-close btn-close-white"
                onClick={cerrarModal}
                disabled={importando}
                aria-label="Cerrar"
              ></button>
            </div>

            <form onSubmit={handleSubmit} noValidate>
              <div className="modal-body px-3 px-md-4 py-4">
                {error && (
                  <div
                    className="alert alert-danger d-flex align-items-center"
                    role="alert"
                  >
                    <i className="bi bi-exclamation-triangle-fill me-2"></i>
                    {error}
                  </div>
                )}

                {resultado && (
                  <div
                    className={`alert ${
                      resultado.errores?.length
                        ? 'alert-warning'
                        : 'alert-success'
                    }`}
                    role="alert"
                  >
                    <div className="fw-semibold mb-1">
                      Proceso de importación finalizado
                    </div>

                    <div>
                      Estudiantes importados: {resultado.importados}
                    </div>

                    {resultado.errores?.length > 0 && (
                      <div className="mt-3">
                        <div className="fw-semibold mb-2">
                          Filas con errores:
                        </div>

                        <ul className="mb-0">
                          {resultado.errores.map((item, index) => (
                            <li key={`${item.fila}-${index}`}>
                              Fila {item.fila}: {item.mensaje}
                            </li>
                          ))}
                        </ul>
                      </div>
                    )}
                  </div>
                )}

                <div className="mb-4">
                  <h6 className="fw-bold mb-1">
                    Archivo de estudiantes
                  </h6>

                  <p className="text-muted small mb-3">
                    Seleccione el archivo CSV que contiene la lista de
                    estudiantes que desea registrar.
                  </p>

                  <label
                    htmlFor="archivo-estudiantes"
                    className="form-label fw-semibold"
                  >
                    Archivo CSV
                    <span className="text-danger ms-1">*</span>
                  </label>

                  <input
                    id="archivo-estudiantes"
                    type="file"
                    className="form-control"
                    accept=".csv,text/csv"
                    onChange={handleArchivo}
                    disabled={importando}
                  />

                  <div className="form-text">
                    Tamaño máximo permitido: 2 MB.
                  </div>
                </div>

                {archivo && (
                  <div className="border rounded-3 p-3 mb-4 bg-light">
                    <div className="d-flex align-items-center gap-3">
                      <i className="bi bi-file-earmark-spreadsheet fs-3 text-success"></i>

                      <div className="overflow-hidden">
                        <div className="fw-semibold text-truncate">
                          {archivo.name}
                        </div>

                        <div className="text-muted small">
                          {(archivo.size / 1024).toFixed(1)} KB
                        </div>
                      </div>
                    </div>
                  </div>
                )}

                <div className="border rounded-3 p-3">
                  <div className="d-flex gap-2">
                    <i className="bi bi-info-circle text-primary"></i>

                    <div>
                      <div className="fw-semibold mb-2">
                        Formato requerido
                      </div>

                      <p className="text-muted small mb-2">
                        La primera fila del archivo debe contener estas
                        columnas en el siguiente orden:
                      </p>

                      <code className="small">
                        ci,codigo_universitario,nombre,apellido,correo,estado
                      </code>

                      <p className="text-muted small mt-3 mb-0">
                        El estado debe ser ACTIVO o INACTIVO. El correo
                        electrónico puede quedar vacío.
                      </p>
                    </div>
                  </div>
                </div>
              </div>

              <div className="modal-footer px-3 px-md-4 py-3 bg-white">
                <div className="d-flex flex-column flex-sm-row justify-content-end gap-2 w-100">
                  <button
                    type="button"
                    className="btn btn-outline-secondary"
                    onClick={cerrarModal}
                    disabled={importando}
                  >
                    Cerrar
                  </button>

                  <button
                    type="submit"
                    className="btn btn-primary"
                    disabled={!archivo || importando}
                  >
                    {importando ? (
                      <>
                        <span className="spinner-border spinner-border-sm me-2"></span>
                        Importando...
                      </>
                    ) : (
                      <>
                        <i className="bi bi-upload me-2"></i>
                        Importar estudiantes
                      </>
                    )}
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>

      <div className="modal-backdrop fade show"></div>
    </>
  );
}