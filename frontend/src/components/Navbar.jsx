
import React, { useState } from 'react';
import { NavLink, useNavigate } from 'react-router-dom';
import api from '../services/api';
import { getUsuario, ROLES } from '../utils/auth';

export default function Navbar() {
  const navigate = useNavigate();
  const usuario = getUsuario() || {};
  const [menuAbierto, setMenuAbierto] = useState(false);

  const enlaces = [
    {
      ruta: '/',
      nombre: 'Inicio',
      icono: 'bi-house-door',
      roles: [
        ROLES.ADMINISTRADOR,
        ROLES.DOCENTE,
        ROLES.CONTROL_INGRESO,
      ],
    },
    {
      ruta: '/examenes',
      nombre: 'Exámenes',
      icono: 'bi-journal-check',
      roles: [ROLES.ADMINISTRADOR, ROLES.DOCENTE],
    },
    {
      ruta: '/estudiantes',
      nombre: 'Estudiantes',
      icono: 'bi-people',
      roles: [ROLES.ADMINISTRADOR, ROLES.DOCENTE],
    },
    {
      ruta: '/usuarios',
      nombre: 'Usuarios',
      icono: 'bi-person-gear',
      roles: [ROLES.ADMINISTRADOR],
    },
    {
      ruta: '/ambientes',
      nombre: 'Ambientes',
      icono: 'bi-building',
      roles: [ROLES.ADMINISTRADOR],
    },
    {
      ruta: '/habilitaciones',
      nombre: 'Habilitaciones',
      icono: 'bi-person-check',
      roles: [ROLES.ADMINISTRADOR, ROLES.DOCENTE],
    },
    {
      ruta: '/control-ingreso',
      nombre: 'Control de ingreso',
      icono: 'bi-box-arrow-in-right',
      roles: [ROLES.ADMINISTRADOR, ROLES.CONTROL_INGRESO],
    },
    {
      ruta: '/incidencias',
      nombre: 'Incidencias',
      icono: 'bi-exclamation-triangle',
      roles: [ROLES.ADMINISTRADOR, ROLES.CONTROL_INGRESO],
    },
    {
      ruta: '/reportes',
      nombre: 'Reportes',
      icono: 'bi-bar-chart',
      roles: [
        ROLES.ADMINISTRADOR,
        ROLES.DOCENTE,
        ROLES.CONTROL_INGRESO,
      ],
    },
  ];

  const enlacesVisibles = enlaces.filter((enlace) =>
    enlace.roles.includes(usuario.rol)
  );

  const handleLogout = async () => {
    try {
      await api.post('/logout');
    } catch {
      // Continuar con el cierre de sesión local.
    } finally {
      localStorage.removeItem('token');
      localStorage.removeItem('usuario');
      setMenuAbierto(false);
      navigate('/login');
    }
  };

  return (
    <nav className="navbar navbar-dark bg-primary shadow-sm px-2 px-md-3">
      <div className="container-fluid gap-2">
        <div className="d-flex align-items-center gap-2">
          <button
            type="button"
            className="btn btn-outline-light btn-sm d-md-none"
            onClick={() => setMenuAbierto(!menuAbierto)}
            aria-label="Abrir o cerrar menú"
            aria-expanded={menuAbierto}
            aria-controls="menu-movil"
          >
            <i className={`bi ${menuAbierto ? 'bi-x-lg' : 'bi-list'} fs-5`} />
          </button>

          <span className="navbar-brand fw-bold mb-0 me-0">
            <i className="bi bi-mortarboard-fill me-2" />
            <span className="d-none d-sm-inline">
              Control de Exámenes
            </span>
            <span className="d-sm-none">Exámenes</span>
          </span>
        </div>

        <div className="d-flex align-items-center text-white">
          <div className="me-3 d-none d-sm-block text-end">
            <div className="fw-semibold">
              {usuario.nombre} {usuario.apellido}
            </div>
            <small className="badge bg-light text-primary">
              {usuario.rol || 'Usuario'}
            </small>
          </div>

          <button
            type="button"
            onClick={handleLogout}
            className="btn btn-outline-light btn-sm"
          >
            <i className="bi bi-box-arrow-right me-1" />
            Salir
          </button>
        </div>

        {menuAbierto && (
          <div
            id="menu-movil"
            className="w-100 d-md-none bg-white rounded p-2 mt-2"
          >
            {enlacesVisibles.map((enlace) => (
              <NavLink
                key={enlace.ruta}
                to={enlace.ruta}
                end={enlace.ruta === '/'}
                onClick={() => setMenuAbierto(false)}
                className={({ isActive }) =>
                  `d-flex align-items-center gap-2 p-3 rounded text-decoration-none ${
                    isActive
                      ? 'bg-primary text-white'
                      : 'text-dark'
                  }`
                }
              >
                <i className={`bi ${enlace.icono}`} />
                {enlace.nombre}
              </NavLink>
            ))}
          </div>
        )}
      </div>
    </nav>
  );
}
