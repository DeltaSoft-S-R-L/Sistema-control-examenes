import React, { useEffect, useState } from 'react';
import api from '../../services/api';

const facultades = {
  CIENCIAS_TECNOLOGIA: {
    nombre: 'Facultad de Ciencias y Tecnología',
    carreras: [
      'Ingeniería de Sistemas',
      'Ingeniería Informática',
      'Ingeniería Electrónica',
      'Ingeniería Civil',
      'Ingeniería Industrial',
    ],
  },

  CIENCIAS_ECONOMICAS: {
    nombre: 'Facultad de Ciencias Económicas',
    carreras: [
      'Administración de Empresas',
      'Economía',
      'Contaduría Pública',
      'Ingeniería Comercial',
      'Ingeniería Financiera',
    ],
  },

  CIENCIAS_SALUD: {
    nombre: 'Facultad de Ciencias de la Salud',
    carreras: [
      'Medicina',
      'Enfermería',
      'Nutrición y Dietética',
      'Fisioterapia y Kinesiología',
      'Odontología',
    ],
  },

  HUMANIDADES: {
    nombre: 'Facultad de Humanidades',
    carreras: [
      'Psicología',
      'Ciencias de la Educación',
      'Lingüística',
      'Trabajo Social',
      'Comunicación Social',
    ],
  },
};

const estadoInicial = {
  ci: '',
  codigoUniversitario: '',
  nombre: '',
  apellido: '',
  correo: '',
  facultad: '',
  carrera: '',
  estado: 'activo',
};

export default function NuevoEstudianteModal({
  mostrar,
  onCerrar,
  onRegistrado,
}) {
  const [formulario, setFormulario] = useState(estadoInicial);
  const [errores, setErrores] = useState({});
  const [errorGeneral, setErrorGeneral] = useState('');
  const [guardando, setGuardando] = useState(false);

  useEffect(() => {
    if (mostrar) {
      setFormulario(estadoInicial);
      setErrores({});
      setErrorGeneral('');
    }
  }, [mostrar]);

  if (!mostrar) {
    return null;
  }

  const carrerasDisponibles = formulario.facultad
    ? facultades[formulario.facultad]?.carreras || []
    : [];

  const handleChange = (e) => {
    const { name, value } = e.target;

    setFormulario((actual) => ({
      ...actual,
      [name]: value,
      ...(name === 'facultad' ? { carrera: '' } : {}),
    }));

    setErrores((actual) => ({
      ...actual,
      [name]: '',
    }));

    setErrorGeneral('');
  };

  const validarFormulario = () => {
    const nuevosErrores = {};

    const formatoIdentificador = /^[A-Za-z0-9-]+$/;
    const formatoNombre = /^[A-Za-zÁÉÍÓÚáéíóúÑñÜü\s'-]+$/;
    const formatoCorreo = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (!formulario.ci.trim()) {
      nuevosErrores.ci = 'El CI es obligatorio.';
    } else if (formulario.ci.length > 20) {
      nuevosErrores.ci =
        'El CI no puede superar los 20 caracteres.';
    } else if (!formatoIdentificador.test(formulario.ci)) {
      nuevosErrores.ci = 'Ingrese un CI válido.';
    }

    if (!formulario.codigoUniversitario.trim()) {
      nuevosErrores.codigoUniversitario =
        'El código universitario es obligatorio.';
    } else if (formulario.codigoUniversitario.length > 50) {
      nuevosErrores.codigoUniversitario =
        'El código no puede superar los 50 caracteres.';
    } else if (
      !formatoIdentificador.test(formulario.codigoUniversitario)
    ) {
      nuevosErrores.codigoUniversitario =
        'Ingrese un código universitario válido.';
    }

    if (!formulario.nombre.trim()) {
      nuevosErrores.nombre = 'El nombre es obligatorio.';
    } else if (formulario.nombre.length > 100) {
      nuevosErrores.nombre =
        'El nombre no puede superar los 100 caracteres.';
    } else if (!formatoNombre.test(formulario.nombre)) {
      nuevosErrores.nombre = 'Ingrese un nombre válido.';
    }

    if (!formulario.apellido.trim()) {
      nuevosErrores.apellido = 'El apellido es obligatorio.';
    } else if (formulario.apellido.length > 100) {
      nuevosErrores.apellido =
        'El apellido no puede superar los 100 caracteres.';
    } else if (!formatoNombre.test(formulario.apellido)) {
      nuevosErrores.apellido = 'Ingrese un apellido válido.';
    }

    if (
      formulario.correo.trim() &&
      !formatoCorreo.test(formulario.correo)
    ) {
      nuevosErrores.correo =
        'Ingrese un correo electrónico válido.';
    }

    if (formulario.correo.length > 150) {
      nuevosErrores.correo =
        'El correo no puede superar los 150 caracteres.';
    }

    if (!formulario.facultad) {
      nuevosErrores.facultad = 'Seleccione una facultad.';
    }

    if (!formulario.carrera) {
      nuevosErrores.carrera = 'Seleccione una carrera.';
    }

    if (!formulario.estado) {
      nuevosErrores.estado = 'Seleccione un estado.';
    }

    setErrores(nuevosErrores);

    return Object.keys(nuevosErrores).length === 0;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();

    if (!validarFormulario()) {
      return;
    }

    setGuardando(true);
    setErrorGeneral('');

    try {
      /*
       * Facultad, carrera y rol todavía no existen en el modelo
       * Estudiante del backend actual.
       *
       * Por ahora enviamos únicamente los campos que Laravel
       * acepta para no romper el registro.
       */
      const payload = {
        ci: formulario.ci.trim(),
        codigo_universitario:
          formulario.codigoUniversitario.trim(),
        nombre: formulario.nombre.trim(),
        apellido: formulario.apellido.trim(),
        correo: formulario.correo.trim() || null,
        estado: formulario.estado,
      };

      await api.post('/estudiantes', payload);

      onRegistrado();
      onCerrar();
    } catch (err) {
      const validaciones = err.response?.data?.errors;

      if (validaciones) {
        const nuevosErrores = {};

        if (validaciones.ci) {
          nuevosErrores.ci =
            'Ya existe un estudiante registrado con este CI.';
        }

        if (validaciones.codigo_universitario) {
          nuevosErrores.codigoUniversitario =
            'Ya existe un estudiante con este código universitario.';
        }

        if (validaciones.nombre) {
          nuevosErrores.nombre = validaciones.nombre[0];
        }

        if (validaciones.apellido) {
          nuevosErrores.apellido = validaciones.apellido[0];
        }

        if (validaciones.correo) {
          nuevosErrores.correo = validaciones.correo[0];
        }

        if (validaciones.estado) {
          nuevosErrores.estado = validaciones.estado[0];
        }

        setErrores((actual) => ({
          ...actual,
          ...nuevosErrores,
        }));
      } else {
        setErrorGeneral(
          err.response?.data?.message ||
            'No se pudo registrar el estudiante.'
        );
      }
    } finally {
      setGuardando(false);
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
        <div
          className="modal-dialog modal-lg modal-dialog-centered mx-auto"
          style={{
            maxHeight: 'calc(100vh - 1rem)',
            width: 'calc(100% - 1rem)',
          }}
        >
          <div
            className="modal-content border-0 shadow"
            style={{
              maxHeight: 'calc(100vh - 1rem)',
            }}
          >
            {/* ENCABEZADO AZUL */}
            <div className="modal-header px-3 px-md-4 py-3 bg-primary text-white">
              <div className="pe-3">
                <h5 className="modal-title fw-bold">
                  Registrar estudiante
                </h5>

                <p className="small mb-0 mt-1 text-white-50">
                  Complete los datos del estudiante que participará
                  en los exámenes.
                </p>
              </div>

              <button
                type="button"
                className="btn-close btn-close-white"
                onClick={onCerrar}
                disabled={guardando}
                aria-label="Cerrar"
              ></button>
            </div>

            <form
              onSubmit={handleSubmit}
              noValidate
              className="d-flex flex-column overflow-hidden"
            >
              {/* CONTENIDO CON SCROLL */}
              <div
                className="modal-body px-3 px-md-4 py-4"
                style={{
                  overflowY: 'auto',
                }}
              >
                {errorGeneral && (
                  <div
                    className="alert alert-danger d-flex align-items-center"
                    role="alert"
                  >
                    <i className="bi bi-exclamation-triangle-fill me-2"></i>
                    {errorGeneral}
                  </div>
                )}

                {/* AVISO DE CAMPOS OBLIGATORIOS */}
                <div className="d-flex align-items-center gap-2 text-muted small mb-4">
                  <i className="bi bi-info-circle text-primary"></i>

                  <span>
                    Los campos marcados con{' '}
                    <span className="text-danger fw-semibold">*</span>{' '}
                    son obligatorios.
                  </span>
                </div>

                {/* INFORMACIÓN PERSONAL */}
                <div className="mb-4">
                  <h6 className="fw-bold mb-1">
                    Información personal
                  </h6>

                  <p className="text-muted small mb-3">
                    Datos de identificación del estudiante.
                  </p>

                  <div className="row g-3">
                    {/* CI */}
                    <div className="col-12 col-md-6">
                      <label className="form-label fw-semibold">
                        CI <span className="text-danger">*</span>
                      </label>

                      <input
                        type="text"
                        name="ci"
                        maxLength="20"
                        className={`form-control ${
                          errores.ci ? 'is-invalid' : ''
                        }`}
                        placeholder="Ej. 12345678"
                        value={formulario.ci}
                        onChange={handleChange}
                      />

                      {errores.ci && (
                        <div className="invalid-feedback">
                          {errores.ci}
                        </div>
                      )}
                    </div>

                    {/* CÓDIGO UNIVERSITARIO */}
                    <div className="col-12 col-md-6">
                      <label className="form-label fw-semibold">
                        Código universitario{' '}
                        <span className="text-danger">*</span>
                      </label>

                      <input
                        type="text"
                        name="codigoUniversitario"
                        maxLength="50"
                        className={`form-control ${
                          errores.codigoUniversitario
                            ? 'is-invalid'
                            : ''
                        }`}
                        placeholder="Ej. 202012345"
                        value={formulario.codigoUniversitario}
                        onChange={handleChange}
                      />

                      {errores.codigoUniversitario && (
                        <div className="invalid-feedback">
                          {errores.codigoUniversitario}
                        </div>
                      )}
                    </div>

                    {/* NOMBRE */}
                    <div className="col-12 col-md-6">
                      <label className="form-label fw-semibold">
                        Nombre <span className="text-danger">*</span>
                      </label>

                      <input
                        type="text"
                        name="nombre"
                        maxLength="100"
                        className={`form-control ${
                          errores.nombre ? 'is-invalid' : ''
                        }`}
                        placeholder="Ej. Juan"
                        value={formulario.nombre}
                        onChange={handleChange}
                      />

                      {errores.nombre && (
                        <div className="invalid-feedback">
                          {errores.nombre}
                        </div>
                      )}
                    </div>

                    {/* APELLIDO */}
                    <div className="col-12 col-md-6">
                      <label className="form-label fw-semibold">
                        Apellido{' '}
                        <span className="text-danger">*</span>
                      </label>

                      <input
                        type="text"
                        name="apellido"
                        maxLength="100"
                        className={`form-control ${
                          errores.apellido ? 'is-invalid' : ''
                        }`}
                        placeholder="Ej. Pérez"
                        value={formulario.apellido}
                        onChange={handleChange}
                      />

                      {errores.apellido && (
                        <div className="invalid-feedback">
                          {errores.apellido}
                        </div>
                      )}
                    </div>

                    {/* CORREO */}
                    <div className="col-12">
                      <label className="form-label fw-semibold">
                        Correo electrónico
                      </label>

                      <input
                        type="email"
                        name="correo"
                        maxLength="150"
                        className={`form-control ${
                          errores.correo ? 'is-invalid' : ''
                        }`}
                        placeholder="estudiante@universidad.edu"
                        value={formulario.correo}
                        onChange={handleChange}
                      />

                      {errores.correo && (
                        <div className="invalid-feedback">
                          {errores.correo}
                        </div>
                      )}
                    </div>
                  </div>
                </div>

                <hr className="my-4" />

                {/* INFORMACIÓN ACADÉMICA */}
                <div className="mb-4">
                  <h6 className="fw-bold mb-1">
                    Información académica
                  </h6>

                  <p className="text-muted small mb-3">
                    Facultad y carrera a la que pertenece.
                  </p>

                  <div className="row g-3">
                    {/* FACULTAD */}
                    <div className="col-12 col-md-6">
                      <label className="form-label fw-semibold">
                        Facultad{' '}
                        <span className="text-danger">*</span>
                      </label>

                      <select
                        name="facultad"
                        className={`form-select ${
                          errores.facultad ? 'is-invalid' : ''
                        }`}
                        value={formulario.facultad}
                        onChange={handleChange}
                      >
                        <option value="">
                          Seleccione una facultad
                        </option>

                        {Object.entries(facultades).map(
                          ([clave, facultad]) => (
                            <option key={clave} value={clave}>
                              {facultad.nombre}
                            </option>
                          )
                        )}
                      </select>

                      {errores.facultad && (
                        <div className="invalid-feedback">
                          {errores.facultad}
                        </div>
                      )}
                    </div>

                    {/* CARRERA */}
                    <div className="col-12 col-md-6">
                      <label className="form-label fw-semibold">
                        Carrera{' '}
                        <span className="text-danger">*</span>
                      </label>

                      <select
                        name="carrera"
                        className={`form-select ${
                          errores.carrera ? 'is-invalid' : ''
                        }`}
                        value={formulario.carrera}
                        onChange={handleChange}
                        disabled={!formulario.facultad}
                      >
                        <option value="">
                          {formulario.facultad
                            ? 'Seleccione una carrera'
                            : 'Primero seleccione una facultad'}
                        </option>

                        {carrerasDisponibles.map((carrera) => (
                          <option key={carrera} value={carrera}>
                            {carrera}
                          </option>
                        ))}
                      </select>

                      {errores.carrera && (
                        <div className="invalid-feedback">
                          {errores.carrera}
                        </div>
                      )}
                    </div>

                    {/* ROL */}
                    <div className="col-12 col-md-6">
                      <label className="form-label fw-semibold">
                        Rol
                      </label>

                      <input
                        type="text"
                        className="form-control bg-light"
                        value="ESTUDIANTE"
                        disabled
                        readOnly
                      />

                      <div className="form-text">
                        El rol se asigna automáticamente.
                      </div>
                    </div>

                    {/* ESTADO */}
                    <div className="col-12 col-md-6">
                      <label className="form-label fw-semibold">
                        Estado{' '}
                        <span className="text-danger">*</span>
                      </label>

                      <select
                        name="estado"
                        className={`form-select ${
                          errores.estado ? 'is-invalid' : ''
                        }`}
                        value={formulario.estado}
                        onChange={handleChange}
                      >
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                        <option value="suspendido">
                          Suspendido
                        </option>
                      </select>

                      {errores.estado && (
                        <div className="invalid-feedback">
                          {errores.estado}
                        </div>
                      )}
                    </div>
                  </div>
                </div>
              </div>

              {/* FOOTER RESPONSIVE */}
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

                  <button
                    type="submit"
                    className="btn btn-primary"
                    disabled={guardando}
                  >
                    {guardando ? (
                      <>
                        <span className="spinner-border spinner-border-sm me-2"></span>
                        Registrando...
                      </>
                    ) : (
                      <>
                        <i className="bi bi-person-plus me-2"></i>
                        Registrar estudiante
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