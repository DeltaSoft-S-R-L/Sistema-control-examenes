import { fireEvent, render, screen } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import api from '../services/api';
import FotoEstudiante from '../components/estudiantes/FotoEstudiante';

vi.mock('../services/api', () => ({
  default: { get: vi.fn(), post: vi.fn() },
}));

describe('requisitos de la foto estudiantil', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    api.get.mockResolvedValue({
      data: { tiene_foto: false, puede_subir_primera_foto: true },
    });
  });

  it('avisa antes de seleccionar y subir que solo se permite una carga', async () => {
    render(<FotoEstudiante />);

    expect(await screen.findByRole('note')).toHaveTextContent(
      'solo podrás registrar tu fotografía una vez',
    );
    expect(screen.getByText(/exactamente 709 × 709 píxeles/i)).toBeInTheDocument();
    expect(screen.getByText(/30 × 30 mm a 600 PPP/i)).toBeInTheDocument();
    expect(screen.getByText(/Peso del archivo: máximo 5 MB/i)).toBeInTheDocument();
  });

  it('ofrece subir foto en el recuadro y valida el archivo seleccionado', async () => {
    render(<FotoEstudiante />);

    const selector = await screen.findByLabelText('Subir foto');
    expect(selector).toHaveAttribute('type', 'file');
    expect(selector).toHaveAttribute('accept', '.jpg,.jpeg,.png');
    expect(selector.closest('label')).toHaveTextContent('Sin fotografía');

    fireEvent.change(selector, {
      target: { files: [new File(['contenido'], 'documento.txt', { type: 'text/plain' })] },
    });

    expect(await screen.findByRole('alert')).toHaveTextContent(
      'Selecciona una fotografía JPG, JPEG o PNG.',
    );
  });
});
