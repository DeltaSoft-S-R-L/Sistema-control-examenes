import React, { useEffect } from "react";
import { dispararVibracionError } from "../../utils/verificacionErrores";

/**
 * Banner rojo de error para el flujo de verificación (VER-01 / S3-VER01-10).
 *
 * Características:
 * - Accesible con role="alert".
 * - Retroalimentación mediante ícono explícito y texto descriptivo en español (no solo color).
 * - Dispara una vibración corta háptica en dispositivos móviles al mostrarse.
 * - Permite cerrar el banner manualmente y ofrece acciones directas:
 *   - "Reintentar" en errores de conexión o timeout.
 *   - "Cambiar a C.I. / SIS" en fallos de cámara o QR no legible.
 * - Muestra instrucciones específicas por navegador cuando la cámara fue denegada.
 */
export default function BannerErrorVerificacion({
  errorInfo,
  onCerrar,
  onReintentar,
  onCambiarMetodoCisIs,
}) {
  useEffect(() => {
    if (errorInfo) {
      dispararVibracionError();
    }
  }, [errorInfo]);

  if (!errorInfo) return null;

  return (
    <div
      className="alert alert-danger shadow-sm rounded-3 border-danger p-3 mb-3"
      role="alert"
      style={{ borderLeftWidth: "6px" }}
    >
      <div className="d-flex align-items-start">
        {/* Ícono explícito (no solo color) */}
        <div className="flex-shrink-0 me-3 text-danger pt-1">
          <i
            className="bi bi-exclamation-octagon-fill fs-3"
            aria-hidden="true"
          />
        </div>

        {/* Contenido del mensaje */}
        <div className="flex-grow-1">
          <div className="d-flex justify-content-between align-items-center mb-1">
            <h5 className="h6 fw-bold mb-0 text-danger-emphasis">
              {errorInfo.mensaje || "Error en la verificación"}
            </h5>
            {onCerrar && (
              <button
                type="button"
                className="btn-close btn-sm ms-2"
                aria-label="Cerrar alerta"
                onClick={onCerrar}
              />
            )}
          </div>

          {errorInfo.detalle && (
            <p className="small mb-2 text-danger-emphasis">
              {errorInfo.detalle}
            </p>
          )}

          {/* Acciones contextuales */}
          <div className="d-flex flex-wrap gap-2 mt-2">
            {errorInfo.esTimeoutORed && onReintentar && (
              <button
                type="button"
                className="btn btn-danger btn-sm fw-semibold shadow-sm"
                onClick={onReintentar}
              >
                <i className="bi bi-arrow-clockwise me-1" />
                Reintentar
              </button>
            )}

            {errorInfo.ofrecerCisIs && onCambiarMetodoCisIs && (
              <button
                type="button"
                className="btn btn-outline-danger btn-sm fw-semibold bg-white"
                onClick={onCambiarMetodoCisIs}
              >
                <i className="bi bi-person-badge me-1" />
                Cambiar a C.I. / SIS
              </button>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
