import React, { useEffect } from 'react';
import { Navigate, Outlet, useLocation } from 'react-router-dom';
import { getRol } from '../utils/auth';
import api from '../services/api';

export default function ProtectedRoute({ rolesPermitidos = [] }) {
  const token = localStorage.getItem('token');
  const location = useLocation();

  useEffect(() => {
    if (!token) return;

    let cancelado = false;

    const verificarSesion = async () => {
      try {
        await api.get('/me');
      } catch (err) {
        // El interceptor de api.js limpia sesión y redirige si recibe 401/403
      }
    };

    // 1. Verificar inmediatamente al cambiar de ruta o módulo
    verificarSesion();

    // 2. Revalidar cuando la ventana recupera el foco (ej. cambiar entre ventanas en la demo)
    const alGanarFoco = () => {
      if (!cancelado) verificarSesion();
    };
    window.addEventListener('focus', alGanarFoco);

    return () => {
      cancelado = true;
      window.removeEventListener('focus', alGanarFoco);
    };
  }, [token, location.pathname]);

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