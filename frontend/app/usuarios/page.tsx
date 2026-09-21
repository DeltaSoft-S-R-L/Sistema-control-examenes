"use client";

import { useMemo, useState } from "react";
import styles from "./page.module.css";

type Usuario = {
  id: number;
  nombre: string;
  correo: string;
  rol: string;
  estado: "ACTIVO" | "INACTIVO";
};

/*
 * Datos temporales para probar la interfaz.
 * Cuando el backend tenga disponible el endpoint de usuarios,
 * estos datos serán reemplazados por información de la API.
 */
const usuariosEjemplo: Usuario[] = [
  {
    id: 1,
    nombre: "María Fernández",
    correo: "maria.fernandez@universidad.edu",
    rol: "Administrador",
    estado: "ACTIVO",
  },
  {
    id: 2,
    nombre: "Carlos Mendoza",
    correo: "carlos.mendoza@universidad.edu",
    rol: "Docente",
    estado: "ACTIVO",
  },
  {
    id: 3,
    nombre: "Ana Rodríguez",
    correo: "ana.rodriguez@universidad.edu",
    rol: "Encargado",
    estado: "ACTIVO",
  },
  {
    id: 4,
    nombre: "Luis Vargas",
    correo: "luis.vargas@universidad.edu",
    rol: "Estudiante",
    estado: "INACTIVO",
  },
  {
    id: 5,
    nombre: "Sofía López",
    correo: "sofia.lopez@universidad.edu",
    rol: "Docente",
    estado: "ACTIVO",
  },
  {
    id: 6,
    nombre: "Diego Morales",
    correo: "diego.morales@universidad.edu",
    rol: "Estudiante",
    estado: "ACTIVO",
  },
];

export default function UsuariosPage() {
  const [busqueda, setBusqueda] = useState("");
  const [rolSeleccionado, setRolSeleccionado] = useState("TODOS");

  /*
   * Filtrado de usuarios.
   *
   * Permite buscar por:
   * - Nombre
   * - Correo electrónico
   *
   * También permite filtrar por rol.
   */
  const usuariosFiltrados = useMemo(() => {
    const textoBusqueda = busqueda.trim().toLowerCase();

    return usuariosEjemplo.filter((usuario) => {
      const coincideBusqueda =
        usuario.nombre.toLowerCase().includes(textoBusqueda) ||
        usuario.correo.toLowerCase().includes(textoBusqueda);

      const coincideRol =
        rolSeleccionado === "TODOS" ||
        usuario.rol === rolSeleccionado;

      return coincideBusqueda && coincideRol;
    });
  }, [busqueda, rolSeleccionado]);

  /*
   * Limpia todos los filtros.
   */
  const limpiarFiltros = () => {
    setBusqueda("");
    setRolSeleccionado("TODOS");
  };

  const hayFiltros =
    busqueda.trim() !== "" || rolSeleccionado !== "TODOS";

  return (
    <main className={styles.page}>
      <div className={styles.container}>
        {/* Encabezado */}
        <header className={styles.header}>
          <div>
            <span className={styles.sectionLabel}>
              USUARIOS
            </span>

            <h1>Gestión de usuarios</h1>

            <p>
              Busca y filtra los usuarios registrados en el sistema.
            </p>
          </div>
        </header>

        {/* Panel de búsqueda y filtros */}
        <section className={styles.filterCard}>
          <div className={styles.filterHeader}>
            <div>
              <h2>Búsqueda y filtros</h2>

              <p>
                Encuentra usuarios por nombre, correo electrónico o rol.
              </p>
            </div>

            {hayFiltros && (
              <button
                type="button"
                className={styles.clearButton}
                onClick={limpiarFiltros}
              >
                Limpiar filtros
              </button>
            )}
          </div>

          <div className={styles.filters}>
            {/* Buscador */}
            <div className={styles.searchField}>
              <label htmlFor="busqueda">
                Nombre o correo electrónico
              </label>

              <div className={styles.searchInputWrapper}>
                <span className={styles.searchIcon}>
                  ⌕
                </span>

                <input
                  id="busqueda"
                  type="search"
                  value={busqueda}
                  onChange={(event) =>
                    setBusqueda(event.target.value)
                  }
                  placeholder="Buscar por nombre o correo..."
                  autoComplete="off"
                />
              </div>
            </div>

            {/* Filtro de rol */}
            <div className={styles.roleField}>
              <label htmlFor="rol">
                Rol
              </label>

              <select
                id="rol"
                value={rolSeleccionado}
                onChange={(event) =>
                  setRolSeleccionado(event.target.value)
                }
              >
                <option value="TODOS">
                  Todos los roles
                </option>

                <option value="Administrador">
                  Administrador
                </option>

                <option value="Docente">
                  Docente
                </option>

                <option value="Encargado">
                  Encargado
                </option>

                <option value="Estudiante">
                  Estudiante
                </option>
              </select>
            </div>
          </div>
        </section>

        {/* Resultados */}
        <section className={styles.resultsCard}>
          <div className={styles.resultsHeader}>
            <div>
              <h2>Usuarios</h2>

              <p>
                {usuariosFiltrados.length === 1
                  ? "1 usuario encontrado"
                  : `${usuariosFiltrados.length} usuarios encontrados`}
              </p>
            </div>
          </div>

          {/* Tabla */}
          {usuariosFiltrados.length > 0 ? (
            <div className={styles.tableWrapper}>
              <table className={styles.table}>
                <thead>
                  <tr>
                    <th>Nombre</th>
                    <th>Correo electrónico</th>
                    <th>Rol</th>
                    <th>Estado</th>
                  </tr>
                </thead>

                <tbody>
                  {usuariosFiltrados.map((usuario) => (
                    <tr key={usuario.id}>
                      <td>
                        <div className={styles.userCell}>
                          <div className={styles.avatar}>
                            {usuario.nombre
                              .split(" ")
                              .slice(0, 2)
                              .map((palabra) => palabra[0])
                              .join("")
                              .toUpperCase()}
                          </div>

                          <span>{usuario.nombre}</span>
                        </div>
                      </td>

                      <td className={styles.email}>
                        {usuario.correo}
                      </td>

                      <td>
                        <span className={styles.roleBadge}>
                          {usuario.rol}
                        </span>
                      </td>

                      <td>
                        <span
                          className={
                            usuario.estado === "ACTIVO"
                              ? styles.statusActive
                              : styles.statusInactive
                          }
                        >
                          <span
                            className={
                              usuario.estado === "ACTIVO"
                                ? styles.activeDot
                                : styles.inactiveDot
                            }
                          />

                          {usuario.estado === "ACTIVO"
                            ? "Activo"
                            : "Inactivo"}
                        </span>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          ) : (
            <div className={styles.emptyState}>
              <div className={styles.emptyIcon}>
                ⌕
              </div>

              <h3>No se encontraron usuarios</h3>

              <p>
                No existen usuarios que coincidan con los filtros
                seleccionados.
              </p>

              <button
                type="button"
                onClick={limpiarFiltros}
                className={styles.emptyButton}
              >
                Limpiar filtros
              </button>
            </div>
          )}
        </section>
      </div>
    </main>
  );
}