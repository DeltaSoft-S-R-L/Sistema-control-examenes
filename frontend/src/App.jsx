import React from 'react';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import Layout from './components/Layout';
import Login from './pages/Login';
import Dashboard from './pages/Dashboard';
import Examenes from './pages/Examenes';
import Estudiantes from './pages/Estudiantes';
import Ambientes from './pages/Ambientes';

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
        </Route>
        <Route path="*" element={<Navigate to="/" replace />} />
      </Routes>
    </BrowserRouter>
  );
}
