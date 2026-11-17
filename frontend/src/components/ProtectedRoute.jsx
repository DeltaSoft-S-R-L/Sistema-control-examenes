import React from 'react';
import { Navigate, Outlet } from 'react-router-dom';
import { getRol, ROLES } from '../utils/auth';

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

  // No se debe redirigir un rol desconocido a la misma ruta raíz: eso deja
  // el layout vacío. Conservamos la sesión y mostramos cómo resolverlo.
  if (!rol || !Object.values(ROLES).includes(rol)) {
    return (
      <div className="alert alert-warning m-3" role="alert">
        <h2 className="h5">Tu cuenta no tiene un rol habilitado</h2>
        <p className="mb-0">
          {rol
            ? `El rol “${rol}” no está habilitado en esta aplicación.`
            : 'No se pudo cargar el rol asociado a tu cuenta.'}{' '}
          Solicita al administrador que revise la configuración de tu usuario.
        </p>
      </div>
    );
  }

  // Usuario autenticado, pero sin autorización para esta ruta.
  if (!rolesPermitidos.includes(rol)) {
    const destino = rol === ROLES.ESTUDIANTE
      ? '/estudiante/dashboard'
      : '/';

    return <Navigate to={destino} replace />;
  }

  return <Outlet />;
}
