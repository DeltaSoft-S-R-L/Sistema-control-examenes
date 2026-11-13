import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import React from 'react';
import ControlIngreso from '../pages/ControlIngreso';
import StudentCard from '../components/estudiantes/StudentCard';
import api from '../services/api';

vi.mock('../services/api', () => ({ default: { get: vi.fn(), post: vi.fn() } }));

describe('HU-179: tarjeta ámbar por discrepancia de aula', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    global.navigator.vibrate = vi.fn();
    window.HTMLMediaElement.prototype.play = vi.fn().mockResolvedValue(undefined);
    api.get.mockImplementation((url) => {
      if (url === '/examenes') return Promise.resolve({ data: { data: [{
        id_examen: 1,
        nombre: 'Examen de Cálculo',
        fecha: '2026-11-15',
        hora_inicio: '08:00',
        asignaciones_ambiente: [{ id_asignacion_ambiente: 10, ambiente: { nombre: 'Aula B' } }],
      }] } });
      if (url.includes('/inhabilitacion')) return Promise.resolve({ data: {
        estado: 'HABILITADO', id_habilitacion: 50, id_asignacion_ambiente: 10,
      } });
      return Promise.resolve({ data: {} });
    });
  });

  it('muestra la tarjeta ámbar cuando el servidor rechaza el aula al confirmar el ingreso', async () => {
    const user = userEvent.setup();
    api.post
      .mockResolvedValueOnce({ data: {
        estudiante: { id: 7, nombres: 'María', apellidos: 'Gómez', ci: '1234567', codigo_sis: '2020123' },
        examen: { id: 1, asignatura: 'Cálculo', fecha: '2026-11-15' },
      } })
      .mockRejectedValueOnce({ response: { status: 422, data: {
        code: 'AULA_INCORRECTA',
        ambiente_asignado: { nombre: 'Laboratorio 3', bloque: 'B', piso: '2' },
      } } });

    render(<ControlIngreso />);
    await user.type(await screen.findByPlaceholderText(/Ej. 1234567/i), '1234567');
    await user.click(screen.getByRole('button', { name: /Verificar Estudiante/i }));

    await screen.findByRole('button', { name: /Autorizar e Ingresar/i });
    await user.click(screen.getByRole('button', { name: /Autorizar e Ingresar/i }));

    expect(await screen.findByText(/DEBE DIRIGIRSE AL AULA:/i)).toBeInTheDocument();
    expect(screen.getByText('Laboratorio 3')).toBeInTheDocument();
    expect(screen.getByText(/Bloque:/i)).toBeInTheDocument();
    expect(screen.getByText(/Piso:/i)).toBeInTheDocument();
    expect(screen.getByText(/María Gómez/i)).toBeInTheDocument();
    expect(screen.queryByRole('button', { name: /Autorizar e Ingresar/i })).not.toBeInTheDocument();
    expect(screen.queryByText(/Ingreso Registrado Exitosamente/i)).not.toBeInTheDocument();
  });

  it('muestra ubicación genérica cuando el backend no separa bloque y piso', () => {
    render(<StudentCard
      estudiante={{ nombre: 'Ana', apellido: 'Pérez', ci: '1234567' }}
      habilitacion={{ estado: 'AULA_INCORRECTA', ambiente_asignado: { nombre: 'Aula 5', ubicacion: 'Edificio Norte' } }}
      onSiguiente={vi.fn()}
    />);

    expect(screen.getByText(/Aula 5/i)).toBeInTheDocument();
    expect(screen.getByText(/Ubicación:/i)).toBeInTheDocument();
    expect(screen.queryByText(/Bloque:/i)).not.toBeInTheDocument();
  });
});
