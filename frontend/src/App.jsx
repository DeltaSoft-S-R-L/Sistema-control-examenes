
import React from "react";
import { BrowserRouter, Routes, Route, Navigate } from "react-router-dom";

import Layout from "./components/Layout";
import ProtectedRoute from "./components/ProtectedRoute";

// Páginas
import Login from "./pages/Login";
import ConfigurarContrasena from "./pages/ConfigurarContrasena";
import Dashboard from "./pages/Dashboard";
import Examenes from "./pages/Examenes";
import Estudiantes from "./pages/Estudiantes";
import Ambientes from "./pages/Ambientes";
import Usuarios from "./pages/Usuarios";
import Habilitaciones from "./pages/Habilitaciones";
import ControlIngreso from "./pages/ControlIngreso";
import VerificacionMovil from "./pages/VerificacionMovil";
import ModuloEnConstruccion from "./pages/ModuloEnConstruccion";

import { ROLES } from "./utils/auth";

export default function App() {
  return (
    <BrowserRouter>
      <Routes>
        {/* Rutas públicas */}
        <Route path="/login" element={<Login />} />
        <Route
          path="/configurar-contrasena"
          element={<ConfigurarContrasena />}
        />

        {/* Cualquier usuario autenticado */}
        <Route element={<ProtectedRoute />}>
          <Route element={<Layout />}>
            <Route path="/" element={<Dashboard />} />

            {/* Administrador y docente */}
            <Route
              element={
                <ProtectedRoute
                  rolesPermitidos={[
                    ROLES.ADMINISTRADOR,
                    ROLES.DOCENTE,
                  ]}
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
                <ProtectedRoute
                  rolesPermitidos={[ROLES.ADMINISTRADOR]}
                />
              }
            >
              <Route path="/usuarios" element={<Usuarios />} />
              <Route path="/ambientes" element={<Ambientes />} />
            </Route>

            {/* Administrador y personal de control */}
            <Route
              element={
                <ProtectedRoute
                  rolesPermitidos={[
                    ROLES.ADMINISTRADOR,
                    ROLES.CONTROL_INGRESO,
                  ]}
                />
              }
            >
              <Route
                path="/control-ingreso"
                element={<ControlIngreso />}
              />

              <Route
                path="/verificacion-movil"
                element={<VerificacionMovil />}
              />

              <Route
                path="/incidencias"
                element={
                  <ModuloEnConstruccion
                    titulo="Incidencias"
                    descripcion="El módulo de incidencias se encuentra actualmente en construcción."
                  />
                }
              />
            </Route>

            {/* Reportes */}
            <Route
              path="/reportes"
              element={
                <ModuloEnConstruccion
                  titulo="Reportes"
                  descripcion="El módulo de reportes se encuentra actualmente en construcción."
                />
              }
            />
          </Route>
        </Route>

        {/* Ruta no encontrada */}
        <Route path="*" element={<Navigate to="/" replace />} />
      </Routes>
    </BrowserRouter>
  );
}
