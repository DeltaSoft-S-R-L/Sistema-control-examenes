import React, { useEffect, useState } from 'react';

const roles = [
  {
    id: 1,
    nombre: 'ADMINISTRADOR',
    etiqueta: 'Administrador',
  },
  {
    id: 2,
    nombre: 'DOCENTE',
    etiqueta: 'Docente',
  },
  {
    id: 3,
    nombre: 'CONTROL_INGRESO',
    etiqueta: 'Control de ingreso',
  },
];

const formularioInicial = {
  nombre: '',
  apellido: '',
  correo: '',
  username: '',
  id_rol: '',
  estado: 'ACTIVO',
};

export default function EditarUsuarioModal({
  mostrar,
  usuario,
  onCerrar,
  onActualizado,
}) {
  const [formulario, setFormulario] = useState(formularioInicial);
  const [errores, setErrores] = useState({});
  const [guardando, setGuardando] = useState(false);

  useEffect(() => {
    if (usuario && mostrar) {
      setFormulario({
        nombre: usuario.nombre || '',
        apellido: usuario.apellido || '',
        correo: usuario.correo || '',
        username: usuario.username || '',
        id_rol: String(usuario.id_rol || ''),
        estado: usuario.estado || 'ACTIVO',
      });

      setErrores({});
    }
  }, [usuario, mostrar]);

  if (!mostrar || !usuario) {
    return null;
  }

  const handleChange = (e) => {
    const { name, value } = e.target;

    setFormulario((anterior) => ({
      ...anterior,
      [name]: value,
    }));

    if (errores[name]) {
      setErrores((anteriores) => ({
        ...anteriores,
        [name]: '',
      }));
    }
  };

  const validar = () => {
    const nuevosErrores = {};

    const formatoNombre =
      /^[A-Za-zÁÉÍÓÚáéíóúÑñÜü\s'-]+$/;

    const formatoCorreo =
      /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    const formatoUsername =
      /^[A-Za-z0-9._-]+$/;

    if (!formulario.nombre.trim()) {
      nuevosErrores.nombre =
        'El nombre es obligatorio.';
    } else if (formulario.nombre.trim().length > 100) {
      nuevosErrores.nombre =
        'El nombre no puede superar los 100 caracteres.';
    } else if (
      !formatoNombre.test(formulario.nombre.trim())
    ) {
      nuevosErrores.nombre =
        'El nombre contiene caracteres no válidos.';
    }

    if (!formulario.apellido.trim()) {
      nuevosErrores.apellido =
        'El apellido es obligatorio.';
    } else if (formulario.apellido.trim().length > 100) {
      nuevosErrores.apellido =
        'El apellido no puede superar los 100 caracteres.';
    } else if (
      !formatoNombre.test(formulario.apellido.trim())
    ) {
      nuevosErrores.apellido =
        'El apellido contiene caracteres no válidos.';
    }

    if (!formulario.correo.trim()) {
      nuevosErrores.correo =
        'El correo es obligatorio.';
    } else if (formulario.correo.trim().length > 150) {
      nuevosErrores.correo =
        'El correo no puede superar los 150 caracteres.';
    } else if (
      !formatoCorreo.test(formulario.correo.trim())
    ) {
      nuevosErrores.correo =
        'Ingrese un correo electrónico válido.';
    }

    if (!formulario.username.trim()) {
      nuevosErrores.username =
        'El nombre de usuario es obligatorio.';
    } else if (formulario.username.trim().length > 50) {
      nuevosErrores.username =
        'El usuario no puede superar los 50 caracteres.';
    } else if (
      !formatoUsername.test(formulario.username.trim())
    ) {
      nuevosErrores.username =
        'Use solo letras, números, punto, guion o guion bajo.';
    }

    if (!formulario.id_rol) {
      nuevosErrores.id_rol =
        'Seleccione un rol.';
    }

    if (
      formulario.estado !== 'ACTIVO' &&
      formulario.estado !== 'REVOCADO'
    ) {
      nuevosErrores.estado =
        'Seleccione un estado válido.';
    }

    setErrores(nuevosErrores);

    return Object.keys(nuevosErrores).length === 0;
  };

  const handleSubmit = (e) => {
    e.preventDefault();

    if (!validar()) {
      return;
    }

    setGuardando(true);

    const rolSeleccionado = roles.find(
      (rol) => rol.id === Number(formulario.id_rol)
    );

    const usuarioActualizado = {
      ...usuario,

      nombre: formulario.nombre.trim(),
      apellido: formulario.apellido.trim(),
      correo: formulario.correo.trim(),
      username: formulario.username.trim(),

      id_rol: Number(formulario.id_rol),

      rol: rolSeleccionado?.nombre || '',

      estado: formulario.estado,
    };

    /*
      IMPORTANTE:

      Actualmente el backend todavía no tiene
      UsuarioController ni endpoint para actualizar usuarios.

      Cuando exista el endpoint se reemplazará esta actualización
      temporal por algo como:

      await api.put(`/usuarios/${usuario.id_usuario}`, {
        nombre: usuarioActualizado.nombre,
        apellido: usuarioActualizado.apellido,
        correo: usuarioActualizado.correo,
        username: usuarioActualizado.username,
        id_rol: usuarioActualizado.id_rol,
        estado: usuarioActualizado.estado,
      });
    */

    onActualizado(usuarioActualizado);

    setGuardando(false);
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
            {/* Encabezado */}
            <div className="modal-header bg-primary text-white px-3 px-md-4">
              <div>
                <h5 className="modal-title fw-bold">
                  Editar usuario
                </h5>

                <small className="text-white-50">
                  Actualice la información y permisos del usuario
                </small>
              </div>

              <button
                type="button"
                className="btn-close btn-close-white"
                onClick={onCerrar}
                aria-label="Cerrar"
              ></button>
            </div>

            <form onSubmit={handleSubmit}>
              {/* Cuerpo */}
              <div
                className="modal-body px-3 px-md-4 py-4"
                style={{
                  overflowY: 'auto',
                }}
              >
                <div className="alert alert-light border small mb-4">
                  <i className="bi bi-info-circle me-2 text-primary"></i>
                  Los campos marcados con * son obligatorios.
                </div>

                <h6 className="fw-bold mb-3">
                  Información personal
                </h6>

                <div className="row g-3">
                  {/* Nombre */}
                  <div className="col-12 col-md-6">
                    <label className="form-label fw-semibold">
                      Nombre *
                    </label>

                    <input
                      type="text"
                      name="nombre"
                      className={`form-control ${
                        errores.nombre ? 'is-invalid' : ''
                      }`}
                      value={formulario.nombre}
                      onChange={handleChange}
                      maxLength="100"
                    />

                    {errores.nombre && (
                      <div className="invalid-feedback">
                        {errores.nombre}
                      </div>
                    )}
                  </div>

                  {/* Apellido */}
                  <div className="col-12 col-md-6">
                    <label className="form-label fw-semibold">
                      Apellido *
                    </label>

                    <input
                      type="text"
                      name="apellido"
                      className={`form-control ${
                        errores.apellido ? 'is-invalid' : ''
                      }`}
                      value={formulario.apellido}
                      onChange={handleChange}
                      maxLength="100"
                    />

                    {errores.apellido && (
                      <div className="invalid-feedback">
                        {errores.apellido}
                      </div>
                    )}
                  </div>

                  {/* Correo */}
                  <div className="col-12 col-md-6">
                    <label className="form-label fw-semibold">
                      Correo *
                    </label>

                    <input
                      type="email"
                      name="correo"
                      className={`form-control ${
                        errores.correo ? 'is-invalid' : ''
                      }`}
                      value={formulario.correo}
                      onChange={handleChange}
                      maxLength="150"
                    />

                    {errores.correo && (
                      <div className="invalid-feedback">
                        {errores.correo}
                      </div>
                    )}
                  </div>

                  {/* Username */}
                  <div className="col-12 col-md-6">
                    <label className="form-label fw-semibold">
                      Usuario *
                    </label>

                    <input
                      type="text"
                      name="username"
                      className={`form-control ${
                        errores.username ? 'is-invalid' : ''
                      }`}
                      value={formulario.username}
                      onChange={handleChange}
                      maxLength="50"
                    />

                    {errores.username && (
                      <div className="invalid-feedback">
                        {errores.username}
                      </div>
                    )}
                  </div>
                </div>

                <hr className="my-4" />

                <h6 className="fw-bold mb-3">
                  Acceso al sistema
                </h6>

                <div className="row g-3">
                  {/* Rol */}
                  <div className="col-12 col-md-6">
                    <label className="form-label fw-semibold">
                      Rol *
                    </label>

                    <select
                      name="id_rol"
                      className={`form-select ${
                        errores.id_rol ? 'is-invalid' : ''
                      }`}
                      value={formulario.id_rol}
                      onChange={handleChange}
                    >
                      <option value="">
                        Seleccione un rol
                      </option>

                      {roles.map((rol) => (
                        <option
                          key={rol.id}
                          value={rol.id}
                        >
                          {rol.etiqueta}
                        </option>
                      ))}
                    </select>

                    {errores.id_rol && (
                      <div className="invalid-feedback">
                        {errores.id_rol}
                      </div>
                    )}
                  </div>

                  {/* Estado */}
                  <div className="col-12 col-md-6">
                    <label className="form-label fw-semibold">
                      Estado *
                    </label>

                    <select
                      name="estado"
                      className={`form-select ${
                        errores.estado ? 'is-invalid' : ''
                      }`}
                      value={formulario.estado}
                      onChange={handleChange}
                    >
                      <option value="ACTIVO">
                        Activo
                      </option>

                      <option value="REVOCADO">
                        Revocado
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

              {/* Footer */}
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
                    <i className="bi bi-check-lg me-2"></i>
                    Guardar cambios
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