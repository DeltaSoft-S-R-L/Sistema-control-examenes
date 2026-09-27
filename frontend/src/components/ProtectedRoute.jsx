import React from 'react';
import { Navigate, Outlet } from 'react-router-dom';

export default function ProtectedRoute() {
    // Verificacion de sesion en el almacenamiento local
    const token = localStorage.getItem('token');

    // Redireccion forzada al login si no hay sesion
    if (!token) {
        return <Navigate to="/login" replace />;
    }

    // Renderizacion de la vista solicitada si la sesion es valida
    return <Outlet />;
}