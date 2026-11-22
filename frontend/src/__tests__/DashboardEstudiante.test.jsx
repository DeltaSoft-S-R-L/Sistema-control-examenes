import React from 'react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { fireEvent, render, screen, waitFor } from '@testing-library/react';

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

vi.mock('../components/estudiantes/FotoEstudiante', () => ({
  default: () => <div data-testid="foto-estudiante-mock">FotoEstudiante</div>,
}));

import DashboardEstudiante from '../pages/DashboardEstudiante';

describe('DashboardEstudiante - Validación rol Estudiante (#218)', () => {
  const perfilMock = {
    id_estudiante: 1,
    nombre: 'Valentina',
    apellido: 'Quispe',
    ci: '9876543',
    codigo_universitario: '202612345',
    correo: 'valentina.quispe@est.umss.edu',
    estado: 'ACTIVO',
    carrera: {
      codigo: 'SIS',
      nombre: 'Ingeniería de Sistemas',
      facultad: 'Facultad de Ciencias y Tecnología',
    },
    asignaturas: [
      { codigo: 'TIS-100', nombre: 'Taller de Ingeniería de Software' },
      { codigo: 'BD-200', nombre: 'Base de Datos II' },
    ],
    examenes: [
      {
        id_examen: 10,
        nombre: 'Parcial TIS Sprint 4',
        asignatura: 'Taller de Ingeniería de Software',
        fecha: '2026-12-15',
        hora_inicio: '10:00:00',
        duracion_minutos: 120,
        estado: 'PROGRAMADO',
        estado_habilitacion: 'HABILITADO',
      },
    ],
  };

  const qrMock = {
    token: 'token.firmado.v1',
    qr_svg: '<svg viewBox="0 0 100 100"><rect width="100" height="100"/></svg>',
  };

  beforeEach(() => {
    vi.clearAllMocks();
    localStorage.clear();
    mockGet.mockImplementation((path) => {
      if (path === '/estudiante/perfil') {
        return Promise.resolve({ data: perfilMock });
      }
      if (path === '/estudiante/qr') {
        return Promise.resolve({ data: qrMock });
      }
      return Promise.reject(new Error(`Ruta desconocida: ${path}`));
    });
  });

  it('muestra el estado de carga antes de recibir los datos', () => {
    mockGet.mockReturnValue(new Promise(() => {}));

    render(<DashboardEstudiante />);

    expect(screen.getByText(/Cargando información del estudiante.../i)).toBeInTheDocument();
  });

  it('consulta y muestra perfil, asignaturas y exámenes propios del estudiante', async () => {
    render(<DashboardEstudiante />);

    expect(await screen.findByText('Panel del Estudiante')).toBeInTheDocument();
    expect(screen.getAllByText('Valentina Quispe').length).toBe(2);
    expect(screen.getAllByText(/202612345/).length).toBe(2);
    expect(screen.getByText('valentina.quispe@est.umss.edu')).toBeInTheDocument();
    expect(screen.getByText('9876543')).toBeInTheDocument();
    expect(screen.getAllByText('Ingeniería de Sistemas').length).toBeGreaterThan(0);
    expect(screen.getByText('Facultad de Ciencias y Tecnología')).toBeInTheDocument();

    expect(screen.getAllByText('Taller de Ingeniería de Software').length).toBe(2);
    expect(screen.getByText('Código: TIS-100')).toBeInTheDocument();
    expect(screen.getByText('Base de Datos II')).toBeInTheDocument();
    expect(screen.getByText('Código: BD-200')).toBeInTheDocument();

    expect(screen.getByText('Parcial TIS Sprint 4')).toBeInTheDocument();
    expect(screen.getByText('120 min')).toBeInTheDocument();
    expect(screen.getByText('HABILITADO')).toBeInTheDocument();
  });

  it('muestra el código QR personal y permite su descarga', async () => {
    const revokeSpy = vi.spyOn(URL, 'revokeObjectURL').mockImplementation(() => {});
    const createObjectUrlSpy = vi.spyOn(URL, 'createObjectURL').mockReturnValue('blob:qr-svg');

    render(<DashboardEstudiante />);

    expect(await screen.findByText('Mi código QR de identificación')).toBeInTheDocument();
    expect(document.querySelector('.qr-svg-contenedor')).toBeInTheDocument();

    const botonDescargar = screen.getByRole('button', { name: /Descargar mi QR/i });
    expect(botonDescargar).toBeInTheDocument();

    fireEvent.click(botonDescargar);

    expect(createObjectUrlSpy).toHaveBeenCalled();
    expect(revokeSpy).toHaveBeenCalled();

    createObjectUrlSpy.mockRestore();
    revokeSpy.mockRestore();
  });

  it('muestra estados claros cuando el estudiante no tiene datos académicos', async () => {
    mockGet.mockImplementation((path) => {
      if (path === '/estudiante/perfil') {
        return Promise.resolve({
          data: {
            ...perfilMock,
            carrera: null,
            asignaturas: [],
            examenes: [],
          },
        });
      }
      if (path === '/estudiante/qr') {
        return Promise.resolve({ data: qrMock });
      }
      return Promise.reject(new Error(`Ruta desconocida: ${path}`));
    });

    render(<DashboardEstudiante />);

    expect(await screen.findByText(/No tienes asignaturas inscritas actualmente./i)).toBeInTheDocument();
    expect(screen.getByText(/Todavía no tienes exámenes asignados./i)).toBeInTheDocument();
    expect(screen.getByText(/Carrera no registrada/i)).toBeInTheDocument();
  });

  it('muestra mensaje de error cuando falla la carga del perfil', async () => {
    mockGet.mockImplementation((path) => {
      if (path === '/estudiante/perfil') {
        return Promise.reject({
          response: {
            status: 404,
            data: { message: 'No se encontró un perfil estudiantil asociado a tu cuenta.' },
          },
        });
      }
      return Promise.resolve({ data: qrMock });
    });

    render(<DashboardEstudiante />);

    expect(await screen.findByRole('alert')).toHaveTextContent(
      'No se encontró un perfil estudiantil asociado a tu cuenta.',
    );
    expect(screen.getByRole('button', { name: /Reintentar/i })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /Volver al inicio de sesión/i })).toBeInTheDocument();
  });

  it('muestra mensaje de error cuando no se puede generar o cargar el código QR', async () => {
    mockGet.mockImplementation((path) => {
      if (path === '/estudiante/perfil') {
        return Promise.resolve({ data: perfilMock });
      }
      if (path === '/estudiante/qr') {
        return Promise.reject({
          response: {
            status: 500,
            data: { message: 'No se pudo generar el código QR.' },
          },
        });
      }
      return Promise.reject(new Error(`Ruta desconocida: ${path}`));
    });

    render(<DashboardEstudiante />);

    expect(await screen.findByRole('alert')).toHaveTextContent('No se pudo generar el código QR.');
    expect(screen.queryByRole('button', { name: /Descargar mi QR/i })).not.toBeInTheDocument();
  });

  it('cierra sesión y limpia almacenamiento local', async () => {
    localStorage.setItem('token', 'token-estudiante');
    localStorage.setItem('usuario', JSON.stringify({ rol: 'ESTUDIANTE' }));
    mockPost.mockResolvedValue({ data: { message: 'Sesión cerrada' } });

    delete window.location;
    window.location = { replace: vi.fn(), reload: vi.fn() };

    render(<DashboardEstudiante />);

    const botonCerrar = await screen.findByRole('button', { name: /Cerrar sesión/i });
    fireEvent.click(botonCerrar);

    await waitFor(() => {
      expect(mockPost).toHaveBeenCalledWith('/logout');
      expect(localStorage.getItem('token')).toBeNull();
      expect(localStorage.getItem('usuario')).toBeNull();
      expect(window.location.replace).toHaveBeenCalledWith('/login');
    });
  });
});
