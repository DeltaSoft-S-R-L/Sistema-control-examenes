import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, waitFor, fireEvent } from '@testing-library/react';
import React from 'react';
import ControlIngreso from '../pages/ControlIngreso';
import api from '../services/api';

// 1. Mockeamos la API para simular el servidor
vi.mock('../services/api');

describe('HU-181: Pruebas de renderizado de tarjeta ámbar', () => {
  beforeEach(() => {
    vi.clearAllMocks();

    // Evitamos errores de Audio API o Vibrador en Node.js
    global.navigator.vibrate = vi.fn();
    window.HTMLMediaElement.prototype.play = vi.fn().mockImplementation(() => Promise.resolve());
  });

  it('debe renderizar el modal ámbar al recibir respuesta AMBIENTE_INCORRECTO (AULA_INCORRECTA)', async () => {

    // Simulamos la carga inicial de exámenes en el useEffect
    api.get.mockResolvedValueOnce({
      data: { data: [{ id_examen: 1, nombre: 'Examen de Cálculo', fecha: '2026-11-15' }] }
    });

    // Simulamos el rechazo de validación de ambiente por parte del controlador Laravel
    const mockErrorResponse = {
      response: {
        status: 422,
        data: {
          code: 'AULA_INCORRECTA',
          message: 'El estudiante está habilitado, pero le corresponde ingresar en otro ambiente.',
          ambiente_asignado: 'Laboratorio de Cómputo 3',
          estudiante: { nombre: 'María', apellido: 'Gómez' }
        }
      }
    };
    api.post.mockRejectedValueOnce(mockErrorResponse);

    // 2. Renderizamos el componente principal
    render(<ControlIngreso />);

    // 3. Escribimos el CI y lanzamos el submit
    await waitFor(() => expect(api.get).toHaveBeenCalled());

    const inputCi = screen.getByPlaceholderText(/Ej. 1234567/i);
    fireEvent.change(inputCi, { target: { value: '1234567' } });

    const btnVerificar = screen.getByRole('button', { name: /Verificar Estudiante/i });
    fireEvent.click(btnVerificar);

    // 4. Verificamos que se llamó al POST
    await waitFor(() => expect(api.post).toHaveBeenCalled());

    // 5. Criterios de Aceptación (HU-181)
    // Validar título del modal ámbar
    expect(await screen.findByText(/Ambiente Incorrecto/i)).toBeInTheDocument();

    // Validar nombre del estudiante y el ambiente correcto
    expect(screen.getByText(/María Gómez/i)).toBeInTheDocument();
    expect(screen.getByText(/Laboratorio de Cómputo 3/i)).toBeInTheDocument();

    // Validar advertencia restrictiva (HU-180)
    expect(screen.getByText(/Este control NO habilita el ingreso físico en esta aula/i)).toBeInTheDocument();

    // Validar botón de redirección
    const btnEntendido = screen.getByRole('button', { name: /Entendido \/ Siguiente estudiante/i });
    expect(btnEntendido).toBeInTheDocument();
  });
});
