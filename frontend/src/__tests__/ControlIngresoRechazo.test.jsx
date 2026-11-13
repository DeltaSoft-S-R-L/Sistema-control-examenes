import React from 'react';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import ControlIngreso from '../pages/ControlIngreso';
import StudentCard from '../components/estudiantes/StudentCard';
import api from '../services/api';

// Mock de la API de axios
vi.mock('../services/api', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
  },
}));

describe('Pruebas de interfaz de usuario - Respuesta de Examen No Correspondiente', () => {
  const listaExamenesMock = [
    {
      id_examen: 101,
      nombre: 'Primer Parcial - Ingeniería de Software',
      fecha: '2026-11-20',
      hora_inicio: '08:00',
    },
  ];

  beforeEach(() => {
    vi.clearAllMocks();
    window.HTMLMediaElement.prototype.play = vi.fn().mockResolvedValue(undefined);
    api.get.mockImplementation((url) => url === '/examenes'
      ? Promise.resolve({ data: listaExamenesMock })
      : Promise.resolve({ data: { estado: 'NO_ASOCIADO', motivo: 'El estudiante no está asociado a este examen.' } }));
  });

  it('debe rechazar estudiante de examen no correspondiente, omitir botón de autorizar y mostrar advertencia', async () => {
    const user = userEvent.setup();

    // 1. Mockear la respuesta del backend para un estudiante NO asociado al examen
    api.post.mockResolvedValueOnce({
      data: {
        identificado: true,
        estudiante: {
          id: 55,
          nombres: 'Carlos',
          apellidos: 'Fernandez',
          ci: '8877665',
          codigo_sis: '202099999',
        },
        examen: { id: 101, asignatura: 'Ingeniería de Software' },
      },
    });

    render(<ControlIngreso />);

    // Esperar a que se carguen los exámenes
    await waitFor(() => {
      expect(screen.getByText(/Primer Parcial - Ingeniería de Software/i)).toBeInTheDocument();
    });

    // 2. Ingresar C.I. y verificar
    const inputCI = screen.getByPlaceholderText(/Ej. 1234567/i);
    await user.type(inputCI, '8877665');

    const btnVerificar = screen.getByRole('button', { name: /Verificar Estudiante/i });
    await user.click(btnVerificar);

    // 3. Validar presencia del mensaje de advertencia
    await waitFor(() => {
      expect(screen.getByText(/Estudiante No Asociado/i)).toBeInTheDocument();
    });
    expect(screen.getByText(/ACCESO DENEGADO/i)).toBeInTheDocument();
    expect(screen.getByText(/Carlos Fernandez/i)).toBeInTheDocument();

    // 4. Verificar que el botón/indicador de autorizar permanezca ausente
    expect(screen.queryByText(/ACCESO AUTORIZADO/i)).not.toBeInTheDocument();
    expect(screen.queryByRole('button', { name: /Autorizar/i })).not.toBeInTheDocument();

    // 5. Criterio de aceptación: El estado de la interfaz se limpia adecuadamente tras el rechazo
    const btnSiguiente = screen.getByRole('button', { name: /Siguiente estudiante/i });
    expect(btnSiguiente).toBeInTheDocument();
    await user.click(btnSiguiente);

    // La tarjeta de rechazo desaparece y el formulario vuelve al estado inicial limpio
    await waitFor(() => {
      expect(screen.queryByText(/Estudiante No Asociado/i)).not.toBeInTheDocument();
      expect(screen.queryByText(/ACCESO DENEGADO/i)).not.toBeInTheDocument();
    });

    const inputLimpio = screen.getByPlaceholderText(/Ej. 1234567/i);
    expect(inputLimpio).toBeInTheDocument();
    expect(inputLimpio.value).toBe('');
  });

  it('debe mostrar mensaje de alerta cuando el backend responde con error HTTP (ej. 404 o 422)', async () => {
    const user = userEvent.setup();

    // Mockear respuesta errónea del backend
    api.post.mockRejectedValueOnce({
      response: {
        status: 404,
        data: {
          message: 'Estudiante no registrado en esta sesión de examen.',
        },
      },
    });

    render(<ControlIngreso />);

    await waitFor(() => {
      expect(screen.getByText(/Primer Parcial - Ingeniería de Software/i)).toBeInTheDocument();
    });

    const inputCI = screen.getByPlaceholderText(/Ej. 1234567/i);
    await user.type(inputCI, '9999999');

    const btnVerificar = screen.getByRole('button', { name: /Verificar Estudiante/i });
    await user.click(btnVerificar);

    // Criterio de aceptación: La prueba valida la presencia del mensaje de advertencia
    await waitFor(() => {
      expect(
        screen.getByText(/Estudiante no registrado en esta sesión de examen./i)
      ).toBeInTheDocument();
    });

    // Verificar que nunca se autorice el acceso
    expect(screen.queryByText(/ACCESO AUTORIZADO/i)).not.toBeInTheDocument();
  });

  describe('Componente StudentCard - Validación unitaria del estado NO_ASOCIADO', () => {
    it('muestra acceso denegado y no ofrece opción de autorizar', () => {
      const mockOnSiguiente = vi.fn();
      const mockOnVerMotivo = vi.fn();

      render(
        <StudentCard
          estudiante={{
            nombre: 'Ana',
            apellido: 'Gomez',
            ci: '1234567',
            codigo_universitario: '202011111',
            carrera: 'Ing. Sistemas',
          }}
          habilitacion={{
            estado: 'NO_ASOCIADO',
            habilitado: false,
            motivo: 'Materia o examen no asignado',
          }}
          onSiguiente={mockOnSiguiente}
          onVerMotivo={mockOnVerMotivo}
        />
      );

      // Botón autorizar ausente
      expect(screen.queryByText(/ACCESO AUTORIZADO/i)).not.toBeInTheDocument();
      expect(screen.queryByRole('button', { name: /Autorizar/i })).not.toBeInTheDocument();

      // Advertencia y denegación presentes
      expect(screen.getByText(/Estudiante No Asociado/i)).toBeInTheDocument();
      expect(screen.getByText(/ACCESO DENEGADO/i)).toBeInTheDocument();
      expect(screen.getByRole('button', { name: /Ver motivo/i })).toBeInTheDocument();
    });
  });
});
