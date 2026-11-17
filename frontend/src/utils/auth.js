export const ROLES = {
  ADMINISTRADOR: 'ADMINISTRADOR',
  DOCENTE: 'DOCENTE',
  CONTROL_INGRESO: 'CONTROL_INGRESO',
  ESTUDIANTE: 'ESTUDIANTE',
};

export function getUsuario() {
  try {
    const usuario = localStorage.getItem('usuario');

    return usuario ? normalizarUsuario(JSON.parse(usuario)) : null;
  } catch {
    return null;
  }
}

export function normalizarRol(rol) {
  const nombre = typeof rol === 'string' ? rol.trim().toUpperCase() : '';

  if (!nombre) return null;
  if (['ADMIN', 'ADMINISTRADOR'].includes(nombre)) return ROLES.ADMINISTRADOR;
  if (['CONTROL', 'PERSONAL_CONTROL', 'CONTROL_INGRESO', 'OPERADOR'].includes(nombre)) {
    return ROLES.CONTROL_INGRESO;
  }
  if (nombre === ROLES.DOCENTE) return ROLES.DOCENTE;
  if (nombre === ROLES.ESTUDIANTE) return ROLES.ESTUDIANTE;

  return nombre;
}

export function normalizarUsuario(valor) {
  if (!valor || typeof valor !== 'object' || Array.isArray(valor)) return null;

  let usuario = valor;
  for (let nivel = 0; nivel < 3; nivel += 1) {
    const anidado = usuario.usuario ?? usuario.user ?? usuario.data;
    if (!anidado || typeof anidado !== 'object' || Array.isArray(anidado)) break;
    usuario = anidado;
  }

  const normalizado = { ...usuario };
  const camposRol = ['rol', 'role', 'nombre_rol', 'role_name'];
  const campoRol = camposRol.find((campo) =>
    Object.prototype.hasOwnProperty.call(usuario, campo),
  );

  if (campoRol) {
    const rolCrudo = usuario[campoRol];
    const nombreRol = rolCrudo && typeof rolCrudo === 'object'
      ? rolCrudo.nombre ?? rolCrudo.name ?? rolCrudo.nombre_rol
      : rolCrudo;
    normalizado.rol = normalizarRol(nombreRol);
  }

  return normalizado;
}

export function getRol() {
  return getUsuario()?.rol ?? null;
}

export function tieneRol(...roles) {
  const rol = getRol();

  return rol !== null && roles.includes(rol);
}
