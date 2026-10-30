import React, { useState, useEffect } from 'react';
import api from '../../services/api';

export default function NuevaNormaModal({ isOpen, onClose, idExamen, onSuccess }) {
  const [tipo, setTipo] = useState('GENERAL');
  const [descripcion, setDescripcion] = useState('');
  const [idEstudiante, setIdEstudiante] = useState('');
  const [estudiantes, setEstudiantes] = useState([]);
  const [busquedaEstudiante, setBusquedaEstudiante] = useState('');
  const [loadingEstudiantes, setLoadingEstudiantes] = useState(false);
  const [guardando, setGuardando] = useState(false);
  const [errores, setErrores] = useState({});

  useEffect(() => {
    if (!isOpen) {
      setTipo('GENERAL');
      setDescripcion('');
      setIdEstudiante('');
      setBusquedaEstudiante('');
      setErrores({});
      setGuardando(false);
      return;
    }

    if (idExamen) {
      cargarEstudiantesHabilitados();
    }
  }, [isOpen, idExamen]);

  const cargarEstudiantesHabilitados = async () => {
    try {
      setLoadingEstudiantes(true);
      const res = await api.get('/habilitaciones', {
        params: { id_examen: idExamen, estado: 'HABILITADO' },
      });
      const data = res.data?.data || res.data || [];
      // Extraer los estudiantes de las habilitaciones
      const listaEstudiantes = data
        .map((hab) => hab.estudiante)
        .filter((est) => Boolean(est));
      setEstudiantes(listaEstudiantes);
    } catch (err) {
      console.error('Error al cargar estudiantes habilitados:', err);
    } finally {
      setLoadingEstudiantes(false);
    }
  };

  if (!isOpen) return null;

  // Filtrar estudiantes habilitados según búsqueda
  const estudiantesFiltrados = estudiantes.filter((est) => {
    const q = busquedaEstudiante.toLowerCase();
    const nombreCompleto = `${est.nombre} ${est.apellido}`.toLowerCase();
    const ci = String(est.ci || '').toLowerCase();
    const sis = String(est.codigo_universitario || est.codigo_sis || '').toLowerCase();
    return nombreCompleto.includes(q) || ci.includes(q) || sis.includes(q);
  });

  const validarFormulario = () => {
    const nuevosErrores = {};

    if (!tipo) {
      nuevosErrores.tipo = 'El tipo de norma es obligatorio.';
    }

    if (!descripcion.trim()) {
      nuevosErrores.descripcion = 'La descripción de la norma es obligatoria.';
    } else if (descripcion.length > 1000) {
      nuevosErrores.descripcion = 'La descripción no puede superar los 1000 caracteres.';
    }

    if (tipo === 'PARTICULAR' && !idEstudiante) {
      nuevosErrores.id_estudiante = 'Debe seleccionar un estudiante habilitado.';
    }

    setErrores(nuevosErrores);
    return Object.keys(nuevosErrores).length === 0;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!validarFormulario()) return;

    setGuardando(true);
    setErrores({});

    try {
      const payload = {
        tipo,
        descripcion: descripcion.trim(),
      };
      if (tipo === 'PARTICULAR') {
        payload.id_estudiante = Number(idEstudiante);
      }

      await api.post(`/examenes/${idExamen}/normas`, payload);
      onSuccess(tipo === 'GENERAL' ? 'Norma general agregada con éxito.' : 'Norma particular agregada con éxito.');
      onClose();
    } catch (err) {
      console.error('Error al guardar norma:', err);
      if (err.response?.status === 422 && err.response?.data?.errors) {
        const backendErrors = {};
        Object.entries(err.response.data.errors).forEach(([campo, mensajes]) => {
          backendErrors[campo] = Array.isArray(mensajes) ? mensajes[0] : mensajes;
        });
        setErrores(backendErrors);
      } else {
        setErrores({
          general: err.response?.data?.message || 'Error al guardar la norma. Verifique los datos.',
        });
      }
    } finally {
      setGuardando(false);
    }
  };

  return (
    <div
      className="modal fade show d-block"
      tabIndex="-1"
      role="dialog"
      aria-modal="true"
      style={{ backgroundColor: 'rgba(0,0,0,0.5)', zIndex: 1060 }}
    >
      <div className="modal-dialog modal-dialog-centered">
        <div className="modal-content border-0 shadow-lg rounded-4">
          <div className="modal-header border-0 pb-0">
            <h5 className="modal-title fw-bold text-dark d-flex align-items-center">
              <i className="bi bi-file-earmark-plus-fill me-2 text-primary"></i>
              Nueva Norma de Examen
            </h5>
            <button
              type="button"
              className="btn-close"
              aria-label="Cerrar"
              disabled={guardando}
              onClick={onClose}
            ></button>
          </div>

          <form onSubmit={handleSubmit}>
            <div className="modal-body py-3">
              {errores.general && (
                <div className="alert alert-danger py-2 small mb-3">
                  <i className="bi bi-exclamation-triangle-fill me-2"></i>
                  {errores.general}
                </div>
              )}

              {/* Selector de Tipo */}
              <div className="mb-3">
                <label className="form-label fw-semibold small">
                  Tipo de Norma <span className="text-danger">*</span>
                </label>
                <div className="d-flex gap-3">
                  <div className="form-check">
                    <input
                      className="form-check-input"
                      type="radio"
                      name="tipoNorma"
                      id="tipoGeneral"
                      value="GENERAL"
                      checked={tipo === 'GENERAL'}
                      onChange={() => {
                        setTipo('GENERAL');
                        setIdEstudiante('');
                      }}
                      disabled={guardando}
                    />
                    <label className="form-check-label fw-semibold" htmlFor="tipoGeneral">
                      <i className="bi bi-globe me-1 text-primary"></i>General (Todo el Examen)
                    </label>
                  </div>

                  <div className="form-check">
                    <input
                      className="form-check-input"
                      type="radio"
                      name="tipoNorma"
                      id="tipoParticular"
                      value="PARTICULAR"
                      checked={tipo === 'PARTICULAR'}
                      onChange={() => setTipo('PARTICULAR')}
                      disabled={guardando}
                    />
                    <label className="form-check-label fw-semibold" htmlFor="tipoParticular">
                      <i className="bi bi-person-fill me-1 text-info"></i>Particular (Por Estudiante)
                    </label>
                  </div>
                </div>
                {errores.tipo && (
                  <div className="invalid-feedback d-block small mt-1">{errores.tipo}</div>
                )}
              </div>

              {/* Si es Particular: Selector de Estudiante Habilitado */}
              {tipo === 'PARTICULAR' && (
                <div className="mb-3 p-3 bg-light rounded-3 border">
                  <label className="form-label fw-semibold small d-flex justify-content-between align-items-center">
                    <span>
                      Estudiante Habilitado <span className="text-danger">*</span>
                    </span>
                    {loadingEstudiantes && (
                      <span className="spinner-border spinner-border-sm text-primary" role="status"></span>
                    )}
                  </label>

                  {/* Input de filtro de búsqueda rápida */}
                  <input
                    type="text"
                    className="form-control form-control-sm mb-2"
                    placeholder="Filtrar por nombre, CI o SIS..."
                    value={busquedaEstudiante}
                    onChange={(e) => setBusquedaEstudiante(e.target.value)}
                    disabled={guardando || loadingEstudiantes}
                  />

                  {/* Selector del estudiante */}
                  <select
                    className={`form-select ${errores.id_estudiante ? 'is-invalid' : ''}`}
                    value={idEstudiante}
                    onChange={(e) => setIdEstudiante(e.target.value)}
                    disabled={guardando || loadingEstudiantes}
                  >
                    <option value="">-- Seleccione un estudiante --</option>
                    {estudiantesFiltrados.map((est) => (
                      <option key={est.id_estudiante} value={est.id_estudiante}>
                        {est.nombre} {est.apellido} — CI: {est.ci} — SIS: {est.codigo_universitario || est.codigo_sis}
                      </option>
                    ))}
                  </select>

                  {estudiantes.length === 0 && !loadingEstudiantes && (
                    <small className="text-danger d-block mt-1">
                      No hay estudiantes habilitados para este examen todavía.
                    </small>
                  )}

                  {errores.id_estudiante && (
                    <div className="invalid-feedback d-block small mt-1">
                      {errores.id_estudiante}
                    </div>
                  )}
                </div>
              )}

              {/* Textarea de Descripción con contador de caracteres */}
              <div className="mb-3">
                <div className="d-flex justify-content-between align-items-center mb-1">
                  <label className="form-label fw-semibold small mb-0">
                    Descripción de la Norma <span className="text-danger">*</span>
                  </label>
                  <span
                    className={`small ${
                      descripcion.length > 1000
                        ? 'text-danger fw-bold'
                        : descripcion.length > 900
                        ? 'text-warning fw-semibold'
                        : 'text-muted'
                    }`}
                  >
                    {descripcion.length}/1000
                  </span>
                </div>
                <textarea
                  className={`form-control ${errores.descripcion ? 'is-invalid' : ''}`}
                  rows="4"
                  maxLength="1000"
                  placeholder="Escriba aquí la instrucción o regla a cumplir durante el examen..."
                  value={descripcion}
                  onChange={(e) => setDescripcion(e.target.value)}
                  disabled={guardando}
                ></textarea>
                {errores.descripcion && (
                  <div className="invalid-feedback d-block small mt-1">
                    {errores.descripcion}
                  </div>
                )}
              </div>
            </div>

            <div className="modal-footer border-0 pt-0">
              <button
                type="button"
                className="btn btn-outline-secondary rounded-pill px-4"
                disabled={guardando}
                onClick={onClose}
              >
                Cancelar
              </button>
              <button
                type="submit"
                className="btn btn-primary rounded-pill px-4 fw-semibold"
                disabled={guardando}
              >
                {guardando ? (
                  <>
                    <span className="spinner-border spinner-border-sm me-2" role="status"></span>
                    Guardando...
                  </>
                ) : (
                  <>
                    <i className="bi bi-check-lg me-1"></i>Guardar Norma
                  </>
                )}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  );
}
