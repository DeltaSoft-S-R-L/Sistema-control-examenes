import { describe, expect, it, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import RevocarHabilitacionModal from '../components/habilitaciones/RevocarHabilitacionModal';

describe('modal de revocación de habilitación', () => {
  it('requiere un tipo y un motivo antes de confirmar', async () => {
    const user = userEvent.setup();
    const onConfirm = vi.fn();
    render(
      <RevocarHabilitacionModal
        habilitacion={{ estudiante: { nombre: 'Ana', apellido: 'Pérez' } }}
        onConfirm={onConfirm}
      />,
    );

    await user.click(screen.getByRole('button', { name: 'Confirmar revocación' }));
    expect(screen.getByRole('alert')).toHaveTextContent('Seleccione el tipo de motivo.');
    expect(onConfirm).not.toHaveBeenCalled();

    await user.selectOptions(screen.getByLabelText('Tipo de motivo *'), 'ACADEMICO');
    await user.type(screen.getByLabelText('Motivo *'), ' Falta de documentación ');
    await user.click(screen.getByRole('button', { name: 'Confirmar revocación' }));

    expect(onConfirm).toHaveBeenCalledWith({ tipo: 'ACADEMICO', detalle: 'Falta de documentación' });
  });
});
