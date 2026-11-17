import { afterEach, describe, expect, it } from 'vitest';
import api from '../services/api';

describe('manejo de respuestas 401', () => {
  afterEach(() => localStorage.clear());

  it('conserva una sesión nueva cuando una petición con el token anterior falla tarde', async () => {
    localStorage.setItem('token', 'token-anterior');
    localStorage.setItem('usuario', JSON.stringify({ rol: 'ADMINISTRADOR' }));

    const solicitud = api.get('/me', {
      adapter: (config) => {
        localStorage.setItem('token', 'token-nuevo');
        localStorage.setItem('usuario', JSON.stringify({ rol: 'ADMINISTRADOR' }));

        const error = new Error('No autorizado');
        error.config = config;
        error.response = { status: 401, config };
        return Promise.reject(error);
      },
    });

    await expect(solicitud).rejects.toThrow('No autorizado');
    expect(localStorage.getItem('token')).toBe('token-nuevo');
    expect(JSON.parse(localStorage.getItem('usuario')).rol).toBe('ADMINISTRADOR');
  });
});
