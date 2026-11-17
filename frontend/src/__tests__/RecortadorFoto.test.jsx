import { describe, expect, it } from 'vitest';
import { agregarDensidadJpeg } from '../components/estudiantes/RecortadorFoto';

describe('metadatos de impresión de la foto estudiantil', () => {
  it('establece 600 PPP en un encabezado JFIF existente', async () => {
    const jpeg = new Blob([new Uint8Array([
      0xff, 0xd8, 0xff, 0xe0, 0x00, 0x10,
      0x4a, 0x46, 0x49, 0x46, 0x00, 0x01, 0x01, 0x00,
      0x00, 0x48, 0x00, 0x48, 0x00, 0x00,
    ])], { type: 'image/jpeg' });

    const salida = new Uint8Array(await (await agregarDensidadJpeg(jpeg, 600)).arrayBuffer());

    expect(salida[13]).toBe(1);
    expect((salida[14] << 8) | salida[15]).toBe(600);
    expect((salida[16] << 8) | salida[17]).toBe(600);
  });

  it('agrega un encabezado JFIF con 600 PPP si falta', async () => {
    const jpeg = new Blob([new Uint8Array([0xff, 0xd8, 0xff, 0xdb, 0x00, 0x02, 0xff, 0xda])], {
      type: 'image/jpeg',
    });

    const salida = new Uint8Array(await (await agregarDensidadJpeg(jpeg, 600)).arrayBuffer());

    expect(salida.slice(2, 6)).toEqual(new Uint8Array([0xff, 0xe0, 0x00, 0x10]));
    expect((salida[14] << 8) | salida[15]).toBe(600);
    expect((salida[16] << 8) | salida[17]).toBe(600);
  });
});
