import React from 'react';
import { useNavigate } from 'react-router-dom';
import api from '../services/api';

export default function Navbar() {
  const navigate = useNavigate();
  const usuario = JSON.parse(localStorage.getItem('usuario') || '{}');

  const handleLogout = async () => {
    try {
      await api.post('/logout');
    } catch (e) {
      // Ignorar error si ya expiró el token
    } finally {
      localStorage.removeItem('token');
      localStorage.removeItem('usuario');
      navigate('/login');
    }
  };

  return (
    <nav className="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm px-3">
      <div className="container-fluid">
        <span className="navbar-brand fw-bold">
          <i className="bi bi-mortarboard-fill me-2"></i>
          Control de Exámenes
        </span>

        <div className="d-flex align-items-center text-white">
          <div className="me-3 d-none d-sm-block text-end">
            <div className="fw-semibold">{usuario.nombre} {usuario.apellido}</div>
            <small className="badge bg-light text-primary">{usuario.rol || 'Usuario'}</small>
          </div>
          <button onClick={handleLogout} className="btn btn-outline-light btn-sm">
            <i className="bi bi-box-arrow-right me-1"></i> Salir
          </button>
        </div>
      </div>
    </nav>
  );
}
