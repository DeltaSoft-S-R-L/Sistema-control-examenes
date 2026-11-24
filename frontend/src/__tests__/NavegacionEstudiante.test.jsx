import React from 'react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import { MemoryRouter, Route, Routes } from 'react-router-dom';

vi.mock('../services/api', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
  },
}));

import Sidebar from '../components/Sidebar';
import Navbar from '../components/Navbar';
import ProtectedRoute from '../components/ProtectedRoute';
import { ROLES } from '../utils/auth';

describe('NavegacionEstudiante - Menú, navegación y control de acceso por rol (#218)', () => {
  beforeEach(() => {
    localStorage.clear();
    vi.clearAllMocks();
  });

  describe('Restricción en componentes de navegación (Sidebar y Navbar)', () => {
    it('el Sidebar no expone ningún enlace administrativo u operativo al rol Estudiante', () => {
      localStorage.setItem('usuario', JSON.stringify({ rol: ROLES.ESTUDIANTE, nombre: 'Valentina' }));

      render(
        <MemoryRouter>
          <Sidebar />
        </MemoryRouter>
      );

      // Comprobar que ningún módulo administrativo o docente sea visible
      expect(screen.queryByText(/Exámenes/i)).not.toBeInTheDocument();
      expect(screen.queryByText(/Mis exámenes/i)).not.toBeInTheDocument();
      expect(screen.queryByText(/Estudiantes/i)).not.toBeInTheDocument();
      expect(screen.queryByText(/Usuarios/i)).not.toBeInTheDocument();
      expect(screen.queryByText(/Ambientes/i)).not.toBeInTheDocument();
      expect(screen.queryByText(/Habilitaciones/i)).not.toBeInTheDocument();
      expect(screen.queryByText(/Control de ingreso/i)).not.toBeInTheDocument();
      expect(screen.queryByText(/Incidencias/i)).not.toBeInTheDocument();
      expect(screen.queryByText(/Reportes/i)).not.toBeInTheDocument();
    });

    it('el Navbar no expone accesos directos operativos ni administrativos al rol Estudiante', () => {
      localStorage.setItem('usuario', JSON.stringify({ rol: ROLES.ESTUDIANTE, nombre: 'Valentina' }));

      render(
        <MemoryRouter>
          <Navbar />
        </MemoryRouter>
      );

      expect(screen.queryByRole('link', { name: /Control de ingreso/i })).not.toBeInTheDocument();
      expect(screen.queryByRole('link', { name: /Usuarios/i })).not.toBeInTheDocument();
      expect(screen.queryByRole('link', { name: /Ambientes/i })).not.toBeInTheDocument();
      expect(screen.queryByRole('link', { name: /Habilitaciones/i })).not.toBeInTheDocument();
      expect(screen.queryByRole('link', { name: /Reportes/i })).not.toBeInTheDocument();
      expect(screen.queryByRole('link', { name: /Exámenes/i })).not.toBeInTheDocument();
    });
  });

  describe('Control de acceso a páginas mediante ProtectedRoute', () => {
    it('redirige al estudiante a /estudiante/dashboard si intenta acceder al Dashboard general (/)', () => {
      localStorage.setItem('token', 'token-estudiante');
      localStorage.setItem('usuario', JSON.stringify({ rol: ROLES.ESTUDIANTE, nombre: 'Valentina' }));

      render(
        <MemoryRouter initialEntries={['/']}>
          <Routes>
            <Route
              element={
                <ProtectedRoute
                  rolesPermitidos={[ROLES.ADMINISTRADOR, ROLES.DOCENTE, ROLES.CONTROL_INGRESO]}
                />
              }
            >
              <Route path="/" element={<div>Dashboard Administrativo</div>} />
            </Route>
            <Route path="/estudiante/dashboard" element={<div>Dashboard Estudiante</div>} />
          </Routes>
        </MemoryRouter>
      );

      expect(screen.queryByText('Dashboard Administrativo')).not.toBeInTheDocument();
      expect(screen.getByText('Dashboard Estudiante')).toBeInTheDocument();
    });

    it('redirige al estudiante a /estudiante/dashboard si intenta acceder a /examenes o /estudiantes', () => {
      localStorage.setItem('token', 'token-estudiante');
      localStorage.setItem('usuario', JSON.stringify({ rol: ROLES.ESTUDIANTE, nombre: 'Valentina' }));

      render(
        <MemoryRouter initialEntries={['/examenes']}>
          <Routes>
            <Route
              element={
                <ProtectedRoute rolesPermitidos={[ROLES.ADMINISTRADOR, ROLES.DOCENTE]} />
              }
            >
              <Route path="/examenes" element={<div>Gestión de Exámenes</div>} />
            </Route>
            <Route path="/estudiante/dashboard" element={<div>Dashboard Estudiante</div>} />
          </Routes>
        </MemoryRouter>
      );

      expect(screen.queryByText('Gestión de Exámenes')).not.toBeInTheDocument();
      expect(screen.getByText('Dashboard Estudiante')).toBeInTheDocument();
    });

    it('redirige al estudiante a /estudiante/dashboard si intenta acceder a /usuarios o /ambientes', () => {
      localStorage.setItem('token', 'token-estudiante');
      localStorage.setItem('usuario', JSON.stringify({ rol: ROLES.ESTUDIANTE, nombre: 'Valentina' }));

      render(
        <MemoryRouter initialEntries={['/usuarios']}>
          <Routes>
            <Route
              element={
                <ProtectedRoute rolesPermitidos={[ROLES.ADMINISTRADOR]} />
              }
            >
              <Route path="/usuarios" element={<div>Gestión de Usuarios</div>} />
            </Route>
            <Route path="/estudiante/dashboard" element={<div>Dashboard Estudiante</div>} />
          </Routes>
        </MemoryRouter>
      );

      expect(screen.queryByText('Gestión de Usuarios')).not.toBeInTheDocument();
      expect(screen.getByText('Dashboard Estudiante')).toBeInTheDocument();
    });

    it('redirige al estudiante a /estudiante/dashboard si intenta acceder a /control-ingreso o /reportes', () => {
      localStorage.setItem('token', 'token-estudiante');
      localStorage.setItem('usuario', JSON.stringify({ rol: ROLES.ESTUDIANTE, nombre: 'Valentina' }));

      render(
        <MemoryRouter initialEntries={['/control-ingreso']}>
          <Routes>
            <Route
              element={
                <ProtectedRoute
                  rolesPermitidos={[ROLES.ADMINISTRADOR, ROLES.DOCENTE, ROLES.CONTROL_INGRESO]}
                />
              }
            >
              <Route path="/control-ingreso" element={<div>Control de Ingreso</div>} />
            </Route>
            <Route path="/estudiante/dashboard" element={<div>Dashboard Estudiante</div>} />
          </Routes>
        </MemoryRouter>
      );

      expect(screen.queryByText('Control de Ingreso')).not.toBeInTheDocument();
      expect(screen.getByText('Dashboard Estudiante')).toBeInTheDocument();
    });

    it('bloquea el acceso a /estudiante/dashboard a roles distintos de Estudiante y redirige a /', () => {
      localStorage.setItem('token', 'token-docente');
      localStorage.setItem('usuario', JSON.stringify({ rol: ROLES.DOCENTE, nombre: 'Laura' }));

      render(
        <MemoryRouter initialEntries={['/estudiante/dashboard']}>
          <Routes>
            <Route
              element={<ProtectedRoute rolesPermitidos={[ROLES.ESTUDIANTE]} />}
            >
              <Route path="/estudiante/dashboard" element={<div>Dashboard Estudiante</div>} />
            </Route>
            <Route path="/" element={<div>Dashboard General</div>} />
          </Routes>
        </MemoryRouter>
      );

      expect(screen.queryByText('Dashboard Estudiante')).not.toBeInTheDocument();
      expect(screen.getByText('Dashboard General')).toBeInTheDocument();
    });

    it('redirige a /login cuando no existe sesión activa al intentar ingresar a /estudiante/dashboard', () => {
      render(
        <MemoryRouter initialEntries={['/estudiante/dashboard']}>
          <Routes>
            <Route
              element={<ProtectedRoute rolesPermitidos={[ROLES.ESTUDIANTE]} />}
            >
              <Route path="/estudiante/dashboard" element={<div>Dashboard Estudiante</div>} />
            </Route>
            <Route path="/login" element={<div>Página de Login</div>} />
          </Routes>
        </MemoryRouter>
      );

      expect(screen.queryByText('Dashboard Estudiante')).not.toBeInTheDocument();
      expect(screen.getByText('Página de Login')).toBeInTheDocument();
    });
  });
});
