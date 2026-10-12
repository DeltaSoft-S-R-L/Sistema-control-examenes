export const ROLES = {
  ADMINISTRADOR: 'ADMINISTRADOR',
  DOCENTE: 'DOCENTE',
  CONTROL_INGRESO: 'CONTROL_INGRESO',
};

export function getUsuario() {
  try {
    const usuario = localStorage.getItem('usuario');

    return usuario ? JSON.parse(usuario) : null;
  } catch {
    return null;
  }
}

export function getRol() {
  return getUsuario()?.rol ?? null;
}

export function tieneRol(...roles) {
  const rol = getRol();

  return rol !== null && roles.includes(rol);
}