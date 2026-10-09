import React from 'react';
import { Navigate, Outlet } from 'react-router-dom';
import { getRol } from '../utils/auth';

export default function ProtectedRoute({ rolesPermitidos = [] }) {
  const token = localStorage.getItem('token');

  // Sin sesión: volver al login
  if (!token) {
    return <Navigate to="/login" replace />;
  }

  // Si la ruta no especifica roles, basta con estar autenticado
  if (rolesPermitidos.length === 0) {
    return <Outlet />;
  }

  const rol = getRol();

  // Usuario autenticado, pero sin autorización para esta ruta
  if (!rol || !rolesPermitidos.includes(rol)) {
    return <Navigate to="/" replace />;
  }

  return <Outlet />;
}