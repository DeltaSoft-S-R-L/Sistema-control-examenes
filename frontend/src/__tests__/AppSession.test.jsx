import React from 'react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { cleanup, fireEvent, render, screen } from '@testing-library/react';
import { MemoryRouter, Route, Routes } from 'react-router-dom';

const { mockGet, mockPost } = vi.hoisted(() => ({
  mockGet: vi.fn(),
  mockPost: vi.fn(),
}));
vi.mock('../services/api', () => ({
  default: {
    get: mockGet,
    post: mockPost,
  },
}));

import App from '../App';
import ProtectedRoute from '../components/ProtectedRoute';
import { ROLES } from '../utils/auth';

describe('sesión y rutas protegidas', () => {
  beforeEach(() => {
    localStorage.clear();
    mockGet.mockReset();
    mockPost.mockReset();
    window.history.replaceState({}, '', '/');
  });

  afterEach(() => {
    cleanup();
  });

  it('recupera el perfil del servidor y muestra el panel tras una sesión en caché', async () => {
    localStorage.setItem('token', 'token-de-prueba');
    localStorage.setItem('usuario', JSON.stringify({ nombre: 'Usuario antiguo' }));
    mockGet.mockImplementation((path) => {
      if (path === '/me') {
        return Promise.resolve({
          data: {
            id: 10,
            nombre: 'Ana',
            apellido: 'Prueba',
            rol: ROLES.DOCENTE,
          },
        });
      }

      return Promise.resolve({ data: [] });
    });

    render(<App />);

    expect(await screen.findByText('Panel docente')).toBeInTheDocument();
    expect(screen.getAllByText('DOCENTE').length).toBeGreaterThan(0);
    expect(screen.getByRole('link', { name: /control de ingreso/i })).toBeInTheDocument();
    expect(screen.getByRole('link', { name: /incidencias/i })).toBeInTheDocument();
    expect(mockGet).toHaveBeenCalledWith('/me');
    expect(JSON.parse(localStorage.getItem('usuario')).rol).toBe(ROLES.DOCENTE);
  });

  it('traduce el rol OPERADOR de la base local al módulo de control de ingreso', async () => {
    localStorage.setItem('token', 'token-de-prueba');
    mockGet.mockImplementation((path) => {
      if (path === '/me') {
        return Promise.resolve({
          data: { usuario: { nombre: 'Operador', rol: 'OPERADOR' } },
        });
      }

      return Promise.resolve({ data: [] });
    });

    render(<App />);

    expect(await screen.findByText('Panel de control de ingreso')).toBeInTheDocument();
    expect(screen.getAllByText(ROLES.CONTROL_INGRESO).length).toBeGreaterThan(0);
  });

  it('entra al panel administrador con el formato de perfil user/role', async () => {
    window.history.replaceState({}, '', '/login');
    mockPost.mockResolvedValue({
      data: {
        token: 'token-de-prueba',
        user: { id: 3, nombre: 'Admin', role: 'ADMINISTRADOR' },
      },
    });
    mockGet.mockResolvedValue({ data: [] });

    render(<App />);

    fireEvent.change(screen.getByPlaceholderText('nombre@cuentas.umss.edu.bo'), {
      target: { value: 'admin' },
    });
    fireEvent.change(screen.getByPlaceholderText('••••••••'), {
      target: { value: 'clave-de-prueba' },
    });
    fireEvent.click(screen.getByRole('button', { name: /Iniciar sesión/ }));

    expect(await screen.findByText('Panel de administración')).toBeInTheDocument();
    expect(JSON.parse(localStorage.getItem('usuario')).rol).toBe(ROLES.ADMINISTRADOR);
    expect(mockGet).not.toHaveBeenCalledWith('/me');
  });

  it('no pierde el rol en caché si el perfil refrescado no incluye el campo', async () => {
    localStorage.setItem('token', 'token-de-prueba');
    localStorage.setItem(
      'usuario',
      JSON.stringify({ nombre: 'Ana', rol: ROLES.DOCENTE }),
    );
    mockGet.mockImplementation((path) =>
      path === '/me'
        ? Promise.resolve({ data: { data: { usuario: { nombre: 'Ana' } } } })
        : Promise.resolve({ data: [] }),
    );

    render(<App />);

    expect(await screen.findByText('Panel docente')).toBeInTheDocument();
    expect(JSON.parse(localStorage.getItem('usuario')).rol).toBe(ROLES.DOCENTE);
  });

  it('explica el problema cuando el usuario autenticado carece de rol reconocido', () => {
    localStorage.setItem('token', 'token-de-prueba');
    localStorage.setItem('usuario', JSON.stringify({ nombre: 'Ana' }));

    render(
      <MemoryRouter initialEntries={['/']}>
        <Routes>
          <Route
            element={<ProtectedRoute rolesPermitidos={[ROLES.ADMINISTRADOR]} />}
          >
            <Route path="/" element={<div>Panel privado</div>} />
          </Route>
        </Routes>
      </MemoryRouter>,
    );

    expect(screen.getByRole('alert')).toHaveTextContent(
      'No se pudo cargar el rol asociado a tu cuenta.',
    );
    expect(screen.queryByText('Panel privado')).not.toBeInTheDocument();
  });
});
