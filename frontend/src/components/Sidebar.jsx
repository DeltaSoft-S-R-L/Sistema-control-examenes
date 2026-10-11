import React from 'react';
import { NavLink } from 'react-router-dom';
import { getRol, ROLES } from '../utils/auth';

export default function Sidebar() {
  const rol = getRol();

  const links = [
    {
      to: '/',
      label: 'Inicio',
      icon: 'bi-house-door',
      roles: [
        ROLES.ADMINISTRADOR,
        ROLES.DOCENTE,
        ROLES.CONTROL_INGRESO,
      ],
    },
    {
      to: '/examenes',
      label: rol === ROLES.DOCENTE ? 'Mis exámenes' : 'Exámenes',
      icon: 'bi-journal-check',
      roles: [
        ROLES.ADMINISTRADOR,
        ROLES.DOCENTE,
      ],
    },
    {
      to: '/estudiantes',
      label: 'Estudiantes',
      icon: 'bi-people',
      roles: [
        ROLES.ADMINISTRADOR,
        ROLES.DOCENTE,
      ],
    },
    {
      to: '/usuarios',
      label: 'Usuarios',
      icon: 'bi-person-gear',
      roles: [
        ROLES.ADMINISTRADOR,
      ],
    },
    {
      to: '/ambientes',
      label: 'Ambientes',
      icon: 'bi-building',
      roles: [
        ROLES.ADMINISTRADOR,
      ],
    },
    {
      to: '/habilitaciones',
      label: 'Habilitaciones',
      icon: 'bi-person-check',
      roles: [
        ROLES.ADMINISTRADOR,
        ROLES.DOCENTE,
      ],
    },
    {
      to: '/control-ingreso',
      label: 'Control de ingreso',
      icon: 'bi-box-arrow-in-right',
      roles: [
        ROLES.ADMINISTRADOR,
        ROLES.CONTROL_INGRESO,
      ],
    },
    {
      to: '/incidencias',
      label: 'Incidencias',
      icon: 'bi-exclamation-triangle',
      roles: [
        ROLES.ADMINISTRADOR,
        ROLES.CONTROL_INGRESO,
      ],
    },
    {
      to: '/reportes',
      label: 'Reportes',
      icon: 'bi-bar-chart',
      roles: [
        ROLES.ADMINISTRADOR,
        ROLES.DOCENTE,
        ROLES.CONTROL_INGRESO,
      ],
    },
  ];

  const linksVisibles = links.filter((link) =>
    link.roles.includes(rol)
  );

  return (
    <div
      className="bg-light border-end p-3"
      style={{
        minWidth: '240px',
        minHeight: 'calc(100vh - 56px)',
      }}
    >
      <div className="list-group list-group-flush">
        {linksVisibles.map((link) => (
          <NavLink
            key={link.to}
            to={link.to}
            end={link.to === '/'}
            className={({ isActive }) =>
              `list-group-item list-group-item-action d-flex align-items-center rounded mb-1 py-2 ${
                isActive
                  ? 'bg-primary text-white active fw-semibold'
                  : 'text-dark'
              }`
            }
          >
            <i className={`bi ${link.icon} me-2 fs-5`}></i>
            {link.label}
          </NavLink>
        ))}
      </div>
    </div>
  );
}