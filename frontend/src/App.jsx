import React from 'react';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import Layout from './components/Layout';
import Login from './pages/Login';
import Dashboard from './pages/Dashboard';
import Examenes from './pages/Examenes';
import Estudiantes from './pages/Estudiantes';
import Ambientes from './pages/Ambientes';
import ModuloEnConstruccion from './pages/ModuloEnConstruccion';

export default function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/login" element={<Login />} />

        <Route element={<Layout />}>
          <Route path="/" element={<Dashboard />} />
          <Route path="/examenes" element={<Examenes />} />
          <Route path="/estudiantes" element={<Estudiantes />} />
          <Route path="/ambientes" element={<Ambientes />} />

          <Route
            path="/habilitaciones"
            element={
              <ModuloEnConstruccion
                titulo="Habilitaciones"
                descripcion="El módulo de habilitaciones se encuentra actualmente en construcción."
              />
            }
          />

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

        <Route path="*" element={<Navigate to="/" replace />} />
      </Routes>
    </BrowserRouter>
  );
}