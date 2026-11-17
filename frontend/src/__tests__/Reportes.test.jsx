import { beforeEach, describe, expect, it, vi } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import api from '../services/api';
import Reportes from '../pages/Reportes';

vi.mock('../services/api', () => ({ default: { get: vi.fn() } }));

describe('reportes de examen', () => {
  beforeEach(() => vi.clearAllMocks());

  it('carga y presenta el reporte de asistencia del examen seleccionado', async () => {
    api.get.mockResolvedValueOnce({ data: [{ id_examen: 7, nombre: 'Parcial 1', fecha: '2026-11-20', asignatura: { nombre: 'Sistemas' } }] });
    api.get.mockResolvedValueOnce({ data: {
      examen: { id_examen: 7, nombre: 'Parcial 1', fecha: '2026-11-20', asignatura: 'Sistemas' },
      resumen: { habilitados: 1, presentes: 1, ausentes: 0, expulsados: 0 },
      data: [{ id_habilitacion: 8, codigo_universitario: '20240001', apellido: 'Pérez', nombre: 'Ana', asistencia: 'PRESENTE', fecha_hora_ingreso: '2026-11-20 08:01', ambiente: 'Aula 1' }],
    } });

    const user = userEvent.setup();
    render(<Reportes />);
    await user.selectOptions(await screen.findByLabelText('Examen'), '7');
    await user.click(screen.getByRole('button', { name: 'Generar reporte' }));

    await waitFor(() => expect(api.get).toHaveBeenLastCalledWith('/reportes/examenes/7/asistencia'));
    expect(await screen.findByText('20240001')).toBeInTheDocument();
    expect(screen.getByText('PRESENTE')).toBeInTheDocument();
    expect(screen.getByText('Aula 1')).toBeInTheDocument();
    expect(screen.getByText('ausentes')).toBeInTheDocument();
  });
});
