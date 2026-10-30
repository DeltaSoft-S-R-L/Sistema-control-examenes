/**
 * Mapeo y utilidades de errores para el flujo de verificación (VER-01 / S3-VER01-10).
 *
 * Requisitos:
 * - Mapeo de errores en español:
 *   - 'QR_INVALIDO' -> 'El código QR no es válido'
 *   - 'ESTUDIANTE_NO_ENCONTRADO' -> 'Estudiante no encontrado en el sistema'
 *   - 401 -> Sesión expirada (redirigir al login)
 *   - 403 -> Sin permiso ('No tienes permiso para realizar esta acción')
 *   - red/timeout -> Mensaje claro con botón Reintentar
 * - Detección de navegador para instrucciones precisas de permisos de cámara
 * - Vibración corta háptica para alertas visuales y táctiles
 */

/**
 * Dispara una vibración corta en dispositivos móviles si la API está soportada.
 */
export function dispararVibracionError() {
  if (typeof navigator !== "undefined" && typeof navigator.vibrate === "function") {
    try {
      // Patrón de vibración corto y distintivo (200ms)
      navigator.vibrate(200);
    } catch {
      // Ignorar si el navegador o el SO restringe la vibración
    }
  }
}

/**
 * Detecta el navegador o plataforma del usuario para guiar la concesión de permisos de cámara.
 */
export function detectarNavegador() {
  if (typeof navigator === "undefined") {
    return "DESCONOCIDO";
  }

  const ua = navigator.userAgent || "";

  if (/iPhone|iPad|iPod/i.test(ua)) {
    if (/CriOS/i.test(ua)) return "CHROME_IOS";
    if (/FxiOS/i.test(ua)) return "FIREFOX_IOS";
    return "SAFARI_IOS";
  }

  if (/Android/i.test(ua)) {
    if (/Firefox/i.test(ua)) return "FIREFOX_ANDROID";
    if (/Chrome/i.test(ua)) return "CHROME_ANDROID";
    return "ANDROID_GENERIC";
  }

  if (/Chrome/i.test(ua)) return "CHROME_DESKTOP";
  if (/Firefox/i.test(ua)) return "FIREFOX_DESKTOP";
  if (/Safari/i.test(ua)) return "SAFARI_DESKTOP";

  return "GENERICO";
}

/**
 * Retorna instrucciones breves y directas según el navegador para habilitar la cámara.
 */
export function obtenerInstruccionesCamara(tipoNavegador = detectarNavegador()) {
  switch (tipoNavegador) {
    case "CHROME_ANDROID":
    case "ANDROID_GENERIC":
      return "Toca el ícono del candado o ajustes (🔒) junto a la dirección web > Permisos > Cámara > Permitir, y recarga la vista.";
    case "SAFARI_IOS":
      return "Ve a Ajustes de iOS > Safari > Cámara > Permitir, o toca el botón 'aA' en la barra de búsqueda y selecciona 'Configuración del sitio web'.";
    case "CHROME_IOS":
      return "Ve a Ajustes de iOS > Chrome > activa el interruptor de 'Cámara'.";
    case "FIREFOX_ANDROID":
    case "FIREFOX_IOS":
      return "Toca el ícono de candado o escudo en la barra de direcciones y activa los permisos de Cámara.";
    default:
      return "Accede a la configuración o ícono de candado en la barra de direcciones de tu navegador y autoriza el acceso a la cámara.";
  }
}

/**
 * Normaliza y mapea cualquier error (de Axios, DOMException de cámara, o string)
 * a una estructura estandarizada en español para el personal de control.
 */
export function formatearErrorVerificacion(err) {
  // 1. Error de cámara denegada (NotAllowedError / PermissionDeniedError)
  if (
    err?.name === "NotAllowedError" ||
    err?.name === "PermissionDeniedError" ||
    err?.tipo === "CAMARA_DENEGADA"
  ) {
    return {
      codigo: "CAMARA_DENEGADA",
      mensaje: "Acceso a la cámara denegado por el navegador.",
      detalle: obtenerInstruccionesCamara(),
      esCamara: true,
      esTimeoutORed: false,
      ofrecerCisIs: true,
      ofrecerReintento: false,
    };
  }

  // 2. Error de cámara no disponible o sin HTTPS
  if (
    err?.name === "NotFoundError" ||
    err?.name === "DevicesNotFoundError" ||
    err?.name === "NotReadableError" ||
    err?.name === "TrackStartError" ||
    err?.tipo === "CAMARA_NO_DISPONIBLE" ||
    (typeof err?.message === "string" && err.message.includes("HTTPS"))
  ) {
    return {
      codigo: "CAMARA_NO_DISPONIBLE",
      mensaje: "La cámara no está disponible o la conexión no es segura (requiere HTTPS o localhost).",
      detalle: "Utilice la pestaña de C.I. / SIS como método de contingencia.",
      esCamara: true,
      esTimeoutORed: false,
      ofrecerCisIs: true,
      ofrecerReintento: false,
    };
  }

  // 3. Errores de respuesta HTTP (Axios)
  const status = err?.response?.status;
  const data = err?.response?.data;
  const codigoErrorBackend = data?.error || "";

  // 3.1 Error 401: Sesión expirada
  if (status === 401) {
    return {
      codigo: "401",
      mensaje: "Tu sesión ha expirado. Por favor inicia sesión nuevamente.",
      detalle: null,
      esCamara: false,
      esTimeoutORed: false,
      esSesionExpirada: true,
      ofrecerCisIs: false,
      ofrecerReintento: false,
    };
  }

  // 3.2 Error 403: Sin permiso
  if (status === 403) {
    return {
      codigo: "403",
      mensaje: "No tienes permiso para realizar esta acción.",
      detalle: data?.message || "Tu rol no tiene autorización para realizar el control de ingreso.",
      esCamara: false,
      esTimeoutORed: false,
      ofrecerCisIs: false,
      ofrecerReintento: false,
    };
  }

  // 3.3 Error 404: Estudiante no encontrado
  if (status === 404 || codigoErrorBackend === "ESTUDIANTE_NO_ENCONTRADO") {
    return {
      codigo: "ESTUDIANTE_NO_ENCONTRADO",
      mensaje: "Estudiante no encontrado en el sistema.",
      detalle: "Verifique que el número de CI o Código SIS ingresado sea correcto.",
      esCamara: false,
      esTimeoutORed: false,
      ofrecerCisIs: false,
      ofrecerReintento: false,
    };
  }

  // 3.4 Código QR inválido o alterado (422)
  if (
    codigoErrorBackend === "QR_INVALIDO" ||
    codigoErrorBackend === "FORMATO_INVALIDO" ||
    codigoErrorBackend === "QR_ALTERADO" ||
    status === 422
  ) {
    const esAlterado = codigoErrorBackend === "QR_ALTERADO";
    return {
      codigo: "QR_INVALIDO",
      mensaje: esAlterado
        ? "El código QR ha sido alterado o la firma digital no es válida."
        : "El código QR no es válido.",
      detalle: esAlterado
        ? "Posible intento de duplicación o alteración del token QR."
        : (data?.message || "Asegúrese de escanear la credencial universitaria oficial."),
      esCamara: false,
      esTimeoutORed: false,
      ofrecerCisIs: true,
      ofrecerReintento: false,
    };
  }

  // 4. Errores de Red o Timeout
  if (
    err?.code === "ECONNABORTED" ||
    err?.message === "Network Error" ||
    !err?.response
  ) {
    return {
      codigo: "RED_TIMEOUT",
      mensaje: "No se pudo conectar con el servidor (tiempo de espera agotado o sin red).",
      detalle: "Verifique su conexión Wi-Fi o datos móviles y presione 'Reintentar'.",
      esCamara: false,
      esTimeoutORed: true,
      ofrecerCisIs: false,
      ofrecerReintento: true,
    };
  }

  // 5. Error general por defecto
  return {
    codigo: "ERROR_GENERAL",
    mensaje: data?.message || err?.message || "Ocurrió un error inesperado al procesar la verificación.",
    detalle: null,
    esCamara: false,
    esTimeoutORed: false,
    ofrecerCisIs: false,
    ofrecerReintento: true,
  };
}
