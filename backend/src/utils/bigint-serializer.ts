/**
 * Configura la serialización automática de BigInt a String para JSON.stringify.
 * Evita el error "TypeError: Do not know how to serialize a BigInt" en respuestas de Express.
 * Tipado estrictamente sin recurrir a 'any'.
 */
declare global {
  interface BigInt {
    toJSON(): string;
  }
}

BigInt.prototype.toJSON = function (this: bigint): string {
  return this.toString();
};

export {};
