import React, { useEffect, useState } from "react";
import { BrowserRouter, Routes, Route, Navigate } from "react-router-dom";

import Layout from "./components/Layout";
import ProtectedRoute from "./components/ProtectedRoute";
import api from "./services/api";

// Páginas
import Login from "./pages/Login";
import ConfigurarContrasena from "./pages/ConfigurarContrasena";
import Dashboard from "./pages/Dashboard";
import DashboardEstudiante from "./pages/DashboardEstudiante";
import Examenes from "./pages/Examenes";
import Estudiantes from "./pages/Estudiantes";
import Ambientes from "./pages/Ambientes";
import Usuarios from "./pages/Usuarios";
import Habilitaciones from "./pages/Habilitaciones";
import ControlIngreso from "./pages/ControlIngreso";
import VerificacionMovil from "./pages/VerificacionMovil";
import Incidencias from "./pages/Incidencias";
import Reportes from "./pages/Reportes";

import { getUsuario, normalizarUsuario, ROLES } from "./utils/auth";

function RutasConSesion() {
  const [sesionVerificada, setSesionVerificada] = useState(
    () => !localStorage.getItem("token"),
  );

  useEffect(() => {
    if (!localStorage.getItem("token")) return undefined;

    let activa = true;

    api
      .get("/me")
      .then(({ data }) => {
        const perfil = normalizarUsuario(data);
        if (activa && perfil) {
          localStorage.setItem(
            "usuario",
            JSON.stringify({ ...(getUsuario() || {}), ...perfil }),
          );
        }
      })
      .catch(() => {
        // La sesión en caché sigue disponible si el servidor no responde.
        // El interceptor de API ya limpia las credenciales cuando recibe 401.
      })
      .finally(() => {
        if (activa) setSesionVerificada(true);
      });

    return () => {
      activa = false;
    };
  }, []);

  if (!sesionVerificada) {
    return (
      <div className="container py-5" role="status" aria-live="polite">
        Cargando sesión…
      </div>
    );
  }

  return (
    <Routes>
        {/* Rutas públicas */}
        <Route path="/login" element={<Login />} />
        <Route
          path="/configurar-contrasena"
          element={<ConfigurarContrasena />}
        />

        {/* Cualquier usuario autenticado */}
        <Route element={<ProtectedRoute />}>
          {/* Dashboard exclusivo para estudiantes */}
          <Route
            element={<ProtectedRoute rolesPermitidos={[ROLES.ESTUDIANTE]} />}
          >
            <Route
              path="/estudiante/dashboard"
              element={<DashboardEstudiante />}
            />
          </Route>
          <Route element={<Layout />}>
            <Route
              element={
                <ProtectedRoute
                  rolesPermitidos={[
                    ROLES.ADMINISTRADOR,
                    ROLES.DOCENTE,
                    ROLES.CONTROL_INGRESO,
                  ]}
                />
              }
            >
              <Route path="/" element={<Dashboard />} />
            </Route>

            {/* Administrador y docente */}
            <Route
              element={
                <ProtectedRoute
                  rolesPermitidos={[ROLES.ADMINISTRADOR, ROLES.DOCENTE]}
                />
              }
            >
              <Route path="/examenes" element={<Examenes />} />
              <Route path="/estudiantes" element={<Estudiantes />} />
              <Route path="/habilitaciones" element={<Habilitaciones />} />
            </Route>

            {/* Solo administrador */}
            <Route
              element={
                <ProtectedRoute rolesPermitidos={[ROLES.ADMINISTRADOR]} />
              }
            >
              <Route path="/usuarios" element={<Usuarios />} />
              <Route path="/ambientes" element={<Ambientes />} />
            </Route>

            {/* Administrador y personal de control */}
            <Route
              element={
                <ProtectedRoute
                  rolesPermitidos={[ROLES.ADMINISTRADOR, ROLES.DOCENTE, ROLES.CONTROL_INGRESO]}
                />
              }
            >
              <Route path="/control-ingreso" element={<ControlIngreso />} />

              <Route
                path="/verificacion-movil"
                element={<VerificacionMovil />}
              />

              <Route
                path="/incidencias"
                element={<Incidencias />}
              />
            </Route>

            {/* Reportes: usuarios administrativos y operativos */}
            <Route
              element={
                <ProtectedRoute
                  rolesPermitidos={[
                    ROLES.ADMINISTRADOR,
                    ROLES.DOCENTE,
                    ROLES.CONTROL_INGRESO,
                  ]}
                />
              }
            >
              <Route path="/reportes" element={<Reportes />} />
            </Route>
          </Route>
        </Route>

        {/* Ruta no encontrada */}
        <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  );
}

export default function App() {
  return (
    <BrowserRouter>
      <RutasConSesion />
    </BrowserRouter>
  );
}
