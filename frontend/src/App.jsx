import React from 'react';
import {
  BrowserRouter,
  Routes,
  Route,
  Navigate,
} from 'react-router-dom';

import Layout from './components/Layout';
import ProtectedRoute from './components/ProtectedRoute';

import Login from './pages/Login';
import Dashboard from './pages/Dashboard';
import Examenes from './pages/Examenes';
import Estudiantes from './pages/Estudiantes';
import Ambientes from './pages/Ambientes';
import Usuarios from './pages/Usuarios';
import ModuloEnConstruccion from './pages/ModuloEnConstruccion';

import { ROLES } from './utils/auth';

export default function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/login" element={<Login />} />

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

              <Route
                path="/habilitaciones"
                element={
                  <ModuloEnConstruccion
                    titulo="Habilitaciones"
                    descripcion="El módulo de habilitaciones se encuentra actualmente en construcción."
                  />
                }
              />
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
                element={
                  <ModuloEnConstruccion
                    titulo="Control de ingreso"
                    descripcion="El módulo de control de ingreso se encuentra actualmente en construcción."
                  />
                }
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

            {/* Los tres roles pueden consultar reportes */}
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

        <Route path="*" element={<Navigate to="/" replace />} />
      </Routes>
    </BrowserRouter>
  );
}