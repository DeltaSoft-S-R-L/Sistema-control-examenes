import React from 'react';
import { NavLink } from 'react-router-dom';

export default function Sidebar() {
  const links = [
    { to: '/', label: 'Dashboard', icon: 'bi-speedometer2' },
    { to: '/examenes', label: 'Exámenes', icon: 'bi-journal-check' },
    { to: '/estudiantes', label: 'Estudiantes', icon: 'bi-people' },
    { to: '/ambientes', label: 'Ambientes', icon: 'bi-building' },
  ];

  return (
    <div className="bg-light border-end p-3" style={{ minWidth: '240px', minHeight: 'calc(100vh - 56px)' }}>
      <div className="list-group list-group-flush">
        {links.map((link) => (
          <NavLink
            key={link.to}
            to={link.to}
            end={link.to === '/'}
            className={({ isActive }) =>
              `list-group-item list-group-item-action d-flex align-items-center rounded mb-1 py-2 ${
                isActive ? 'bg-primary text-white active fw-semibold' : 'text-dark'
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
