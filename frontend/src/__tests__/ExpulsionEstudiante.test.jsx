import { describe, expect, it, vi, beforeEach } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import api from '../services/api';
import ModalExpulsionEstudiante from '../components/incidencias/ModalExpulsionEstudiante';
import StudentCard from '../components/estudiantes/StudentCard';
import IngresosSesion from '../components/incidencias/IngresosSesion';

vi.mock('../services/api', () => ({ default: { post: vi.fn() } }));

const estudiante = { id_estudiante: 8, nombre: 'Ana', apellido: 'Pérez', ci: '12345', codigo_universitario: '20240001' };
const ingreso = { id_ingreso: 21, habilitacion: { estudiante }, expulsion: null };

describe('flujo de expulsión de estudiante', () => {
  beforeEach(() => vi.clearAllMocks());

  it('muestra los datos y exige motivo y descripción cuando se elige OTRO', async () => {
    const user = userEvent.setup();
    render(<ModalExpulsionEstudiante isOpen estudiante={estudiante} idExamen={3} idIngreso={21} />);
    expect(screen.getByText('Ana Pérez')).toBeInTheDocument();
    expect(screen.getByText(/C\.I\.: 12345/)).toBeInTheDocument();
    const submit = screen.getByRole('button', { name: 'Confirmar expulsión' });
    expect(submit).toBeDisabled();
    await user.selectOptions(screen.getByLabelText(/Motivo tipificado/), 'OTRO');
    expect(screen.getByLabelText(/Observación/)).toBeRequired();
    expect(submit).toBeDisabled();
    await user.type(screen.getByLabelText(/Observación/), 'Uso de material no permitido');
    expect(submit).toBeEnabled();
  });

  it('envía la expulsión al endpoint del examen con el payload esperado', async () => {
    const onExpulsionExitosa = vi.fn();
    api.post.mockResolvedValue({ data: { id_ingreso: 21, motivo: 'INTENTO_COPIA', fecha_hora: '2026-11-16T10:30:00Z' } });
    const user = userEvent.setup();
    render(<ModalExpulsionEstudiante isOpen estudiante={estudiante} idExamen={3} idIngreso={21} onExpulsionExitosa={onExpulsionExitosa} />);
    await user.selectOptions(screen.getByLabelText(/Motivo tipificado/), 'INTENTO_COPIA');
    await user.click(screen.getByRole('button', { name: 'Confirmar expulsión' }));
    await waitFor(() => expect(api.post).toHaveBeenCalledWith('/examenes/3/expulsiones', {
      id_ingreso: 21, id_estudiante: 8, motivo: 'INTENTO_COPIA', descripcion: null,
    }));
    expect(onExpulsionExitosa).toHaveBeenCalledWith(expect.objectContaining({ id_ingreso: 21 }));
  });

  it('actualiza el estado y oculta el botón de expulsión tras el registro', async () => {
    api.post.mockResolvedValue({ data: { id_ingreso: 21, motivo: 'INTENTO_COPIA', fecha_hora: '2026-11-16T10:30:00Z' } });
    const user = userEvent.setup();
    render(<IngresosSesion ingresos={[ingreso]} examenSeleccionado="3" />);
    expect(screen.getByText('EN SALA')).toBeInTheDocument();
    await user.click(screen.getByRole('button', { name: 'Registrar Expulsión' }));
    await user.selectOptions(screen.getByLabelText(/Motivo tipificado/), 'INTENTO_COPIA');
    await user.click(screen.getByRole('button', { name: 'Confirmar expulsión' }));
    await waitFor(() => expect(screen.getByText(/Intento de copia o fraude académico/)).toBeInTheDocument());
    expect(screen.getByText('EXPULSADO')).toBeInTheDocument();
    expect(screen.queryByRole('button', { name: 'Registrar Expulsión' })).not.toBeInTheDocument();
    render(<StudentCard estudiante={estudiante} habilitacion={{ estado: 'EXPULSADO', expulsion: { motivo: 'INTENTO_COPIA', fecha_hora: '2026-11-16T10:30:00Z' } }} />);
    expect(screen.getByText('ESTUDIANTE EXPULSADO')).toBeInTheDocument();
    expect(screen.getAllByText(/Intento de copia o fraude académico/).length).toBeGreaterThanOrEqual(1);
  });
});
