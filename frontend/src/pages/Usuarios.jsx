import React, { useEffect, useState } from "react";
import api from "../services/api";
import EditarUsuarioModal from "../components/usuarios/EditarUsuarioModal";
import NuevoUsuarioModal from "../components/NuevoUsuarioModal";
import ConfirmarRevocarModal from "../components/usuarios/ConfirmarRevocarModal";

export default function Usuarios() {
  const [usuarios, setUsuarios] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [mensaje, setMensaje] = useState("");

  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);

  const [usuarioSeleccionado, setUsuarioSeleccionado] = useState(null);
  const [mostrarModal, setMostrarModal] = useState(false);
  const [mostrarNuevoModal, setMostrarNuevoModal] = useState(false);

  const [usuarioARevocar, setUsuarioARevocar] = useState(null);
  const [mostrarRevocarModal, setMostrarRevocarModal] = useState(false);

  const [buscar, setBuscar] = useState("");
  const [filtroRol, setFiltroRol] = useState("");

  useEffect(() => {
    fetchUsuarios(currentPage);
  }, [currentPage, buscar, filtroRol]);

  const fetchUsuarios = async (page) => {
    setLoading(true);
    setError("");

    try {
      const params = new URLSearchParams({ page });
      if (buscar) params.append("buscar", buscar);
      if (filtroRol) params.append("rol", filtroRol);

      const response = await api.get(`/usuarios?${params.toString()}`);

      const data = response.data.data || response.data;

      setUsuarios(Array.isArray(data) ? data : []);

      if (response.data.last_page) {
        setTotalPages(response.data.last_page);
      } else {
        setTotalPages(1);
      }
    } catch (err) {
      console.error(err);
      setError(
        "Error al cargar la lista de usuarios. Verifique su conexión al servidor.",
      );
    } finally {
      setLoading(false);
    }
  };

  const abrirEdicion = (usuario) => {
    setUsuarioSeleccionado(usuario);
    setMostrarModal(true);
  };

  const cerrarEdicion = () => {
    setMostrarModal(false);
    setUsuarioSeleccionado(null);
  };

  const abrirRevocacion = (usuario) => {
    setUsuarioARevocar(usuario);
    setMostrarRevocarModal(true);
  };

  const handleActualizado = (usuarioActualizado) => {
    setUsuarios((anteriores) =>
      anteriores.map((usuario) =>
        usuario.id_usuario === usuarioActualizado.id_usuario
          ? usuarioActualizado
          : usuario,
      ),
    );

    cerrarEdicion();
    setMensaje("Usuario actualizado correctamente.");

    window.setTimeout(() => {
      setMensaje("");
    }, 4000);
  };

  const handleRevocadoExito = (usuarioRevocado) => {
    setUsuarios((anteriores) =>
      anteriores.map((u) =>
        u.id_usuario === usuarioRevocado.id_usuario
          ? { ...u, estado: "REVOCADO", is_active: false }
          : u,
      ),
    );
    setMostrarRevocarModal(false);
    setUsuarioARevocar(null);
    setMensaje(
      `Acceso revocado correctamente para ${usuarioRevocado.nombre} ${usuarioRevocado.apellido}.`,
    );

    window.setTimeout(() => {
      setMensaje("");
    }, 4000);
  };

  const handleNuevoExito = () => {
    setMostrarNuevoModal(false);
    setMensaje("Usuario registrado correctamente.");
    setCurrentPage(1);
    fetchUsuarios(1);

    window.setTimeout(() => {
      setMensaje("");
    }, 4000);
  };

  const obtenerNombreRol = (usuario) => {
    if (usuario.rol?.nombre) {
      return usuario.rol.nombre;
    }

    if (typeof usuario.rol === "string") {
      return usuario.rol;
    }

    switch (usuario.id_rol) {
      case 1:
        return "ADMINISTRADOR";
      case 2:
        return "DOCENTE";
      case 3:
        return "CONTROL_INGRESO";
      default:
        return "Sin rol";
    }
  };

  const formatearRol = (rol) => {
    switch (rol) {
      case "ADMINISTRADOR":
        return "Administrador";
      case "DOCENTE":
        return "Docente";
      case "CONTROL_INGRESO":
        return "Control de ingreso";
      default:
        return rol;
    }
  };

  return (
    <div className="container-fluid p-4">
      <div className="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 className="fw-bold mb-1">Gestión de Usuarios</h2>
          <p className="text-muted mb-0">
            Administración de usuarios con acceso al sistema
          </p>
        </div>
        <button
          className="btn btn-primary fw-bold px-4"
          onClick={() => setMostrarNuevoModal(true)}
        >
          Nuevo Usuario
        </button>
      </div>

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
            onClick={() => setMensaje("")}
            aria-label="Cerrar"
          ></button>
        </div>
      )}

      {error && (
        <div className="alert alert-danger py-2" role="alert">
          {error}
        </div>
      )}

      <div className="card shadow-sm border-0 rounded-4 mb-4">
        <div className="card-body">
          <div className="row g-3">
            <div className="col-md-6">
              <input
                type="text"
                className="form-control"
                placeholder="Buscar por nombre, correo, usuario..."
                value={buscar}
                onChange={(e) => {
                  setBuscar(e.target.value);
                  setCurrentPage(1);
                }}
              />
            </div>
            <div className="col-md-4">
              <select
                className="form-select"
                value={filtroRol}
                onChange={(e) => {
                  setFiltroRol(e.target.value);
                  setCurrentPage(1);
                }}
              >
                <option value="">Todos los roles</option>
                <option value="1">Administrador</option>
                <option value="2">Docente</option>
                <option value="3">Control de ingreso</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <div className="card shadow-sm border-0 rounded-4">
        <div className="card-header bg-white border-0 px-4 pt-4 pb-3">
          <div className="d-flex justify-content-between align-items-center">
            <div>
              <h5 className="fw-bold mb-1">Usuarios registrados</h5>
              <p className="text-muted small mb-0">
                Usuarios autorizados para acceder al sistema
              </p>
            </div>

            {!loading && !error && (
              <span className="badge text-bg-light border">
                {usuarios.length}{" "}
                {usuarios.length === 1 ? "usuario" : "usuarios"}
              </span>
            )}
          </div>
        </div>

        <div className="card-body p-0">
          <div className="table-responsive">
            <table className="table table-hover align-middle mb-0">
              <thead className="table-light">
                <tr>
                  <th className="px-4 py-3">Nombre</th>
                  <th className="py-3">Correo</th>
                  <th className="py-3">Usuario</th>
                  <th className="py-3">Rol</th>
                  <th className="py-3">Estado</th>
                  <th className="px-4 py-3 text-end">Acciones</th>
                </tr>
              </thead>

              <tbody>
                {loading ? (
                  <tr>
                    <td colSpan="6" className="text-center py-5">
                      <div
                        className="spinner-border text-primary"
                        role="status"
                      >
                        <span className="visually-hidden">Cargando...</span>
                      </div>
                    </td>
                  </tr>
                ) : usuarios.length === 0 ? (
                  <tr>
                    <td colSpan="6" className="text-center py-5 text-muted">
                      No se encontraron usuarios que coincidan con los criterios
                      de búsqueda.
                    </td>
                  </tr>
                ) : (
                  usuarios.map((usuario) => {
                    const rol = obtenerNombreRol(usuario);

                    return (
                      <tr key={usuario.id_usuario}>
                        <td className="px-4">
                          <div className="fw-semibold">
                            {usuario.nombre} {usuario.apellido}
                          </div>
                        </td>

                        <td className="text-muted">{usuario.correo}</td>

                        <td>{usuario.username}</td>

                        <td>
                          <span className="badge bg-primary-subtle text-primary">
                            {formatearRol(rol)}
                          </span>
                        </td>

                        <td>
                          <span
                            className={`badge ${
                              usuario.estado?.toLowerCase() === "activo"
                                ? "bg-success"
                                : "bg-secondary"
                            }`}
                          >
                            {usuario.estado?.toLowerCase() === "activo"
                              ? "Activo"
                              : "Revocado"}
                          </span>
                        </td>

                        <td className="px-4 text-end">
                          <div className="d-flex justify-content-end gap-2">
                            <button
                              type="button"
                              className="btn btn-sm btn-outline-primary"
                              onClick={() => abrirEdicion(usuario)}
                            >
                              <i className="bi bi-pencil-square me-1"></i>
                              Editar
                            </button>

                            {usuario.estado?.toLowerCase() === "activo" && (
                              <button
                                type="button"
                                className="btn btn-sm btn-outline-danger"
                                onClick={() => abrirRevocacion(usuario)}
                                title="Revocar acceso de la cuenta"
                              >
                                <i className="bi bi-person-x-fill me-1"></i>
                                Revocar acceso
                              </button>
                            )}
                          </div>
                        </td>
                      </tr>
                    );
                  })
                )}
              </tbody>
            </table>
          </div>
        </div>

        {!loading && totalPages > 1 && (
          <div className="card-footer bg-white border-0 py-3">
            <nav aria-label="Navegación de páginas de usuarios">
              <ul className="pagination justify-content-center mb-0">
                <li
                  className={`page-item ${currentPage === 1 ? "disabled" : ""}`}
                >
                  <button
                    className="page-link"
                    onClick={() => setCurrentPage((pagina) => pagina - 1)}
                    disabled={currentPage === 1}
                  >
                    Anterior
                  </button>
                </li>

                <li className="page-item disabled">
                  <span className="page-link text-muted">
                    Página {currentPage} de {totalPages}
                  </span>
                </li>

                <li
                  className={`page-item ${
                    currentPage === totalPages ? "disabled" : ""
                  }`}
                >
                  <button
                    className="page-link"
                    onClick={() => setCurrentPage((pagina) => pagina + 1)}
                    disabled={currentPage === totalPages}
                  >
                    Siguiente
                  </button>
                </li>
              </ul>
            </nav>
          </div>
        )}
      </div>

      <EditarUsuarioModal
        mostrar={mostrarModal}
        usuario={usuarioSeleccionado}
        onCerrar={cerrarEdicion}
        onActualizado={handleActualizado}
      />

      <NuevoUsuarioModal
        isOpen={mostrarNuevoModal}
        onClose={() => setMostrarNuevoModal(false)}
        onSuccess={handleNuevoExito}
      />

      <ConfirmarRevocarModal
        isOpen={mostrarRevocarModal}
        usuario={usuarioARevocar}
        onClose={() => {
          setMostrarRevocarModal(false);
          setUsuarioARevocar(null);
        }}
        onSuccess={handleRevocadoExito}
      />
    </div>
  );
}