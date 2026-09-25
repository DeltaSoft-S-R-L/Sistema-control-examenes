import React, { useState } from 'react';
import EditarUsuarioModal from '../components/usuarios/EditarUsuarioModal';

// Datos temporales mientras el backend no tenga el endpoint de usuarios.
// Los roles y estados corresponden a los definidos actualmente en la BD.
const usuariosIniciales = [
  {
    id_usuario: 1,
    nombre: 'Carlos',
    apellido: 'Control',
    correo: 'carlos.control@universidad.edu',
    username: 'control1',
    id_rol: 3,
    rol: 'CONTROL_INGRESO',
    estado: 'ACTIVO',
  },
  {
    id_usuario: 2,
    nombre: 'Ana',
    apellido: 'Administrador',
    correo: 'ana.admin@universidad.edu',
    username: 'admin1',
    id_rol: 1,
    rol: 'ADMINISTRADOR',
    estado: 'ACTIVO',
  },
  {
    id_usuario: 3,
    nombre: 'Luis',
    apellido: 'Docente',
    correo: 'luis.docente@universidad.edu',
    username: 'docente1',
    id_rol: 2,
    rol: 'DOCENTE',
    estado: 'REVOCADO',
  },
];

export default function Usuarios() {
  const [usuarios, setUsuarios] = useState(usuariosIniciales);
  const [usuarioSeleccionado, setUsuarioSeleccionado] = useState(null);
  const [mostrarModal, setMostrarModal] = useState(false);
  const [mensaje, setMensaje] = useState('');

  const abrirEdicion = (usuario) => {
    setUsuarioSeleccionado(usuario);
    setMostrarModal(true);
  };

  const cerrarEdicion = () => {
    setMostrarModal(false);
    setUsuarioSeleccionado(null);
  };

  const handleActualizado = (usuarioActualizado) => {
    setUsuarios((anteriores) =>
      anteriores.map((usuario) =>
        usuario.id_usuario === usuarioActualizado.id_usuario
          ? usuarioActualizado
          : usuario
      )
    );

    cerrarEdicion();
    setMensaje('Usuario actualizado correctamente.');

    window.setTimeout(() => {
      setMensaje('');
    }, 4000);
  };

  const obtenerNombreRol = (rol) => {
    switch (rol) {
      case 'ADMINISTRADOR':
        return 'Administrador';

      case 'DOCENTE':
        return 'Docente';

      case 'CONTROL_INGRESO':
        return 'Control de ingreso';

      default:
        return rol || 'Sin rol';
    }
  };

  return (
    <div>
      {/* Encabezado */}
      <div className="mb-4">
        <h2 className="fw-bold mb-1">Usuarios</h2>

        <p className="text-muted mb-0">
          Administración de usuarios con acceso al sistema
        </p>
      </div>

      {/* Mensaje de éxito */}
      {mensaje && (
        <div
          className="alert alert-success alert-dismissible fade show"
          role="alert"
        >
          <i className="bi bi-check-circle-fill me-2"></i>

          {mensaje}

          <button
            type="button"
            className="btn-close"
            onClick={() => setMensaje('')}
            aria-label="Cerrar"
          ></button>
        </div>
      )}

      {/* Tabla */}
      <div className="card border-0 shadow-sm rounded-3">
        <div className="card-header bg-white border-0 px-4 pt-4 pb-3">
          <div className="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
              <h5 className="fw-bold mb-1">
                Usuarios registrados
              </h5>

              <p className="text-muted small mb-0">
                Usuarios autorizados para acceder al sistema
              </p>
            </div>

            <span className="badge text-bg-light border">
              {usuarios.length}{' '}
              {usuarios.length === 1 ? 'usuario' : 'usuarios'}
            </span>
          </div>
        </div>

        <div className="card-body p-0">
          <div className="table-responsive">
            <table className="table table-hover align-middle mb-0">
              <thead className="table-light">
                <tr>
                  <th className="ps-4">Nombre</th>
                  <th>Correo</th>
                  <th>Usuario</th>
                  <th>Rol</th>
                  <th>Estado</th>
                  <th className="text-end pe-4">Acciones</th>
                </tr>
              </thead>

              <tbody>
                {usuarios.map((usuario) => (
                  <tr key={usuario.id_usuario}>
                    <td className="ps-4">
                      <div className="fw-semibold">
                        {usuario.nombre} {usuario.apellido}
                      </div>
                    </td>

                    <td className="text-muted">
                      {usuario.correo}
                    </td>

                    <td>{usuario.username}</td>

                    <td>
                      <span className="badge bg-primary-subtle text-primary">
                        {obtenerNombreRol(usuario.rol)}
                      </span>
                    </td>

                    <td>
                      <span
                        className={`badge ${
                          usuario.estado === 'ACTIVO'
                            ? 'bg-success'
                            : 'bg-secondary'
                        }`}
                      >
                        {usuario.estado === 'ACTIVO'
                          ? 'Activo'
                          : 'Revocado'}
                      </span>
                    </td>

                    <td className="text-end pe-4">
                      <button
                        type="button"
                        className="btn btn-sm btn-outline-primary"
                        onClick={() => abrirEdicion(usuario)}
                      >
                        <i className="bi bi-pencil-square me-1"></i>
                        Editar
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </div>

      {/* Modal de edición */}
      <EditarUsuarioModal
        mostrar={mostrarModal}
        usuario={usuarioSeleccionado}
        onCerrar={cerrarEdicion}
        onActualizado={handleActualizado}
      />
    </div>
  );
}