import React, { useEffect, useState } from 'react';
import api from '../../services/api';

const camposIniciales = {
  ci: '',
  codigoUniversitario: '',
  nombre: '',
  apellido: '',
  correo: '',
  estado: 'ACTIVO',
};

export default function EditarEstudianteModal({ mostrar, estudiante, onCerrar, onActualizado }) {
  const [formulario, setFormulario] = useState(camposIniciales);
  const [errores, setErrores]       = useState({});
  const [errorGeneral, setErrorGeneral] = useState('');
  const [guardando, setGuardando]   = useState(false);

  // Pre-cargar datos del estudiante cuando se abre el modal
  useEffect(() => {
    if (mostrar && estudiante) {
      setFormulario({
        ci:                  estudiante.ci ?? '',
        codigoUniversitario: estudiante.codigo_universitario ?? '',
        nombre:              estudiante.nombre ?? '',
        apellido:            estudiante.apellido ?? '',
        correo:              estudiante.correo ?? '',
        estado:              estudiante.estado ?? 'ACTIVO',
      });
      setErrores({});
      setErrorGeneral('');
    }
  }, [mostrar, estudiante]);

  if (!mostrar || !estudiante) return null;

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormulario((prev) => ({ ...prev, [name]: value }));
    setErrores((prev)    => ({ ...prev, [name]: '' }));
    setErrorGeneral('');
  };

  const validar = () => {
    const err = {};
    const regId    = /^[A-Za-z0-9\-]+$/;
    const regNombre = /^[A-Za-zÁÉÍÓÚáéíóúÑñÜü\s'\-]+$/;
    const regCorreo = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (!formulario.ci.trim())
      err.ci = 'El CI es obligatorio.';
    else if (formulario.ci.length > 20)
      err.ci = 'El CI no puede superar los 20 caracteres.';
    else if (!regId.test(formulario.ci))
      err.ci = 'Ingrese un CI válido (solo letras, números y guiones).';

    if (!formulario.codigoUniversitario.trim())
      err.codigoUniversitario = 'El código universitario es obligatorio.';
    else if (formulario.codigoUniversitario.length > 50)
      err.codigoUniversitario = 'No puede superar los 50 caracteres.';
    else if (!regId.test(formulario.codigoUniversitario))
      err.codigoUniversitario = 'Código universitario inválido.';

    if (!formulario.nombre.trim())
      err.nombre = 'El nombre es obligatorio.';
    else if (formulario.nombre.length > 100)
      err.nombre = 'No puede superar los 100 caracteres.';
    else if (!regNombre.test(formulario.nombre))
      err.nombre = 'Ingrese un nombre válido.';

    if (!formulario.apellido.trim())
      err.apellido = 'El apellido es obligatorio.';
    else if (formulario.apellido.length > 100)
      err.apellido = 'No puede superar los 100 caracteres.';
    else if (!regNombre.test(formulario.apellido))
      err.apellido = 'Ingrese un apellido válido.';

    if (formulario.correo.trim() && !regCorreo.test(formulario.correo))
      err.correo = 'Ingrese un correo electrónico válido.';
    if (formulario.correo.length > 150)
      err.correo = 'El correo no puede superar los 150 caracteres.';

    if (!['ACTIVO', 'INACTIVO'].includes(formulario.estado))
      err.estado = 'Seleccione un estado válido.';

    setErrores(err);
    return Object.keys(err).length === 0;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!validar()) return;

    setGuardando(true);
    setErrorGeneral('');

    try {
      const payload = {
        ci:                   formulario.ci.trim().toUpperCase(),
        codigo_universitario: formulario.codigoUniversitario.trim().toUpperCase(),
        nombre:               formulario.nombre.trim(),
        apellido:             formulario.apellido.trim(),
        correo:               formulario.correo.trim() || null,
        estado:               formulario.estado,
      };

      const res = await api.put(`/estudiantes/${estudiante.id_estudiante}`, payload);
      onActualizado(res.data.estudiante);
      onCerrar();
    } catch (err) {
      const validaciones = err.response?.data?.errors;

      if (validaciones) {
        const nuevosErr = {};
        if (validaciones.ci)
          nuevosErr.ci = 'Ya existe otro estudiante con este CI.';
        if (validaciones.codigo_universitario)
          nuevosErr.codigoUniversitario = 'Ya existe otro estudiante con este código universitario.';
        if (validaciones.nombre)   nuevosErr.nombre   = validaciones.nombre[0];
        if (validaciones.apellido) nuevosErr.apellido = validaciones.apellido[0];
        if (validaciones.correo)   nuevosErr.correo   = validaciones.correo[0];
        if (validaciones.estado)   nuevosErr.estado   = validaciones.estado[0];
        setErrores((prev) => ({ ...prev, ...nuevosErr }));
      } else {
        setErrorGeneral(
          err.response?.data?.message || 'No se pudo actualizar el estudiante.'
        );
      }
    } finally {
      setGuardando(false);
    }
  };

  return (
    <>
      <div className="modal fade show d-block" tabIndex="-1" role="dialog" aria-modal="true">
        <div
          className="modal-dialog modal-lg modal-dialog-centered mx-auto"
          style={{ maxHeight: 'calc(100vh - 1rem)', width: 'calc(100% - 1rem)' }}
        >
          <div className="modal-content border-0 shadow" style={{ maxHeight: 'calc(100vh - 1rem)' }}>

            {/* CABECERA */}
            <div className="modal-header px-3 px-md-4 py-3 bg-warning text-dark">
              <div className="pe-3">
                <h5 className="modal-title fw-bold">
                  <i className="bi bi-pencil-square me-2"></i>Editar estudiante
                </h5>
                <p className="small mb-0 mt-1 opacity-75">
                  Modificando: <strong>{estudiante.nombre} {estudiante.apellido}</strong> — CI: {estudiante.ci}
                </p>
              </div>
              <button
                type="button"
                className="btn-close"
                onClick={onCerrar}
                disabled={guardando}
                aria-label="Cerrar"
              />
            </div>

            <form onSubmit={handleSubmit} noValidate className="d-flex flex-column overflow-hidden">
              <div className="modal-body px-3 px-md-4 py-4" style={{ overflowY: 'auto' }}>

                {errorGeneral && (
                  <div className="alert alert-danger d-flex align-items-center" role="alert">
                    <i className="bi bi-exclamation-triangle-fill me-2"></i>
                    {errorGeneral}
                  </div>
                )}

                <div className="d-flex align-items-center gap-2 text-muted small mb-4">
                  <i className="bi bi-info-circle text-warning"></i>
                  <span>Los campos marcados con <span className="text-danger fw-semibold">*</span> son obligatorios.</span>
                </div>

                {/* IDENTIFICADORES */}
                <div className="mb-4">
                  <h6 className="fw-bold mb-1">Identificadores únicos</h6>
                  <p className="text-muted small mb-3">CI y código SIS son identificadores únicos en el sistema.</p>

                  <div className="row g-3">
                    {/* CI */}
                    <div className="col-12 col-md-6">
                      <label className="form-label fw-semibold">CI <span className="text-danger">*</span></label>
                      <input
                        type="text"
                        name="ci"
                        maxLength="20"
                        className={`form-control ${errores.ci ? 'is-invalid' : ''}`}
                        placeholder="Ej. 12345678"
                        value={formulario.ci}
                        onChange={handleChange}
                      />
                      {errores.ci && <div className="invalid-feedback">{errores.ci}</div>}
                    </div>

                    {/* Código universitario */}
                    <div className="col-12 col-md-6">
                      <label className="form-label fw-semibold">
                        Código universitario (SIS) <span className="text-danger">*</span>
                      </label>
                      <input
                        type="text"
                        name="codigoUniversitario"
                        maxLength="50"
                        className={`form-control ${errores.codigoUniversitario ? 'is-invalid' : ''}`}
                        placeholder="Ej. 202012345"
                        value={formulario.codigoUniversitario}
                        onChange={handleChange}
                      />
                      {errores.codigoUniversitario && (
                        <div className="invalid-feedback">{errores.codigoUniversitario}</div>
                      )}
                    </div>
                  </div>
                </div>

                <hr className="my-1 mb-4" />

                {/* INFORMACIÓN PERSONAL */}
                <div className="mb-4">
                  <h6 className="fw-bold mb-1">Información personal</h6>
                  <p className="text-muted small mb-3">Datos del estudiante.</p>

                  <div className="row g-3">
                    <div className="col-12 col-md-6">
                      <label className="form-label fw-semibold">Nombre <span className="text-danger">*</span></label>
                      <input
                        type="text"
                        name="nombre"
                        maxLength="100"
                        className={`form-control ${errores.nombre ? 'is-invalid' : ''}`}
                        placeholder="Ej. Juan"
                        value={formulario.nombre}
                        onChange={handleChange}
                      />
                      {errores.nombre && <div className="invalid-feedback">{errores.nombre}</div>}
                    </div>

                    <div className="col-12 col-md-6">
                      <label className="form-label fw-semibold">Apellido <span className="text-danger">*</span></label>
                      <input
                        type="text"
                        name="apellido"
                        maxLength="100"
                        className={`form-control ${errores.apellido ? 'is-invalid' : ''}`}
                        placeholder="Ej. Pérez"
                        value={formulario.apellido}
                        onChange={handleChange}
                      />
                      {errores.apellido && <div className="invalid-feedback">{errores.apellido}</div>}
                    </div>

                    <div className="col-12">
                      <label className="form-label fw-semibold">Correo electrónico</label>
                      <input
                        type="email"
                        name="correo"
                        maxLength="150"
                        className={`form-control ${errores.correo ? 'is-invalid' : ''}`}
                        placeholder="estudiante@universidad.edu"
                        value={formulario.correo}
                        onChange={handleChange}
                      />
                      {errores.correo && <div className="invalid-feedback">{errores.correo}</div>}
                    </div>
                  </div>
                </div>

                <hr className="my-1 mb-4" />

                {/* ESTADO */}
                <div className="mb-2">
                  <h6 className="fw-bold mb-1">Estado del estudiante</h6>
                  <p className="text-muted small mb-3">Define si el estudiante puede rendir exámenes.</p>

                  <div className="row g-3">
                    <div className="col-12 col-md-6">
                      <label className="form-label fw-semibold">
                        Estado <span className="text-danger">*</span>
                      </label>
                      <select
                        name="estado"
                        className={`form-select ${errores.estado ? 'is-invalid' : ''}`}
                        value={formulario.estado}
                        onChange={handleChange}
                      >
                        <option value="ACTIVO">Activo</option>
                        <option value="INACTIVO">Inactivo</option>
                      </select>
                      {errores.estado && <div className="invalid-feedback">{errores.estado}</div>}
                    </div>
                  </div>
                </div>
              </div>

              {/* FOOTER */}
              <div className="modal-footer px-3 px-md-4 py-3 bg-white">
                <div className="d-flex flex-column flex-sm-row justify-content-end gap-2 w-100">
                  <button
                    type="button"
                    className="btn btn-outline-secondary"
                    onClick={onCerrar}
                    disabled={guardando}
                  >
                    Cancelar
                  </button>
                  <button type="submit" className="btn btn-warning" disabled={guardando}>
                    {guardando ? (
                      <>
                        <span className="spinner-border spinner-border-sm me-2" />
                        Guardando...
                      </>
                    ) : (
                      <>
                        <i className="bi bi-floppy me-2"></i>
                        Guardar cambios
                      </>
                    )}
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>

      <div className="modal-backdrop fade show" />
    </>
  );
}
