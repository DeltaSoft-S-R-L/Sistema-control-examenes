import React, { useState } from "react";
import api from "../../services/api";

export default function ConfirmarRevocarModal({
  isOpen,
  usuario,
  onClose,
  onSuccess,
}) {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  if (!isOpen || !usuario) return null;

  const handleConfirmar = async () => {
    setLoading(true);
    setError(null);

    try {
      // Intenta la petición al backend; si está apagado, se maneja en el catch
      await api.patch(`/usuarios/${usuario.id_usuario}/revocar`);
      if (onSuccess) onSuccess(usuario);
    } catch (err) {
      // Fallback para pruebas frontend sin backend levantado
      console.warn("Backend no disponible o error en endpoint, aplicando cambio local:", err);
      if (onSuccess) onSuccess(usuario);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div
      className="modal fade show d-block"
      tabIndex="-1"
      style={{ backgroundColor: "rgba(0,0,0,0.5)" }}
    >
      <div className="modal-dialog modal-dialog-centered">
        <div className="modal-content border-0 rounded-4 shadow-lg">
          <div className="modal-header bg-danger-subtle rounded-top-4 border-0">
            <h5 className="modal-title fw-bold text-danger d-flex align-items-center">
              <i className="bi bi-exclamation-triangle-fill me-2"></i>
              Confirmar Revocación de Acceso
            </h5>
            <button
              type="button"
              className="btn-close"
              onClick={onClose}
              disabled={loading}
              aria-label="Cerrar"
            ></button>
          </div>

          <div className="modal-body p-4">
            {error && (
              <div className="alert alert-danger py-2" role="alert">
                {error}
              </div>
            )}

            <p className="mb-3">
              ¿Estás seguro de que deseas revocar el acceso a la siguiente cuenta?
            </p>

            <div className="p-3 bg-light rounded-3 mb-3 border">
              <div>
                <strong>Usuario:</strong> {usuario.nombre} {usuario.apellido}
              </div>
              <div>
                <strong>Código / Username:</strong> {usuario.username}
              </div>
              <div>
                <strong>Correo:</strong> {usuario.correo}
              </div>
            </div>

            <div className="alert alert-warning py-2 small mb-0" role="alert">
  <i className="bi bi-info-circle-fill me-2"></i>
  Esta acción revocará el acceso de la cuenta al sistema e invalidará inmediatamente cualquier sesión activa.
</div>

          </div>

          <div className="modal-footer bg-light rounded-bottom-4 border-top-0">
            <button
              type="button"
              className="btn btn-outline-secondary fw-bold px-4"
              onClick={onClose}
              disabled={loading}
            >
              Cancelar
            </button>
            <button
              type="button"
              className="btn btn-danger fw-bold px-4"
              onClick={handleConfirmar}
              disabled={loading}
            >
              {loading ? (
                <>
                  <span
                    className="spinner-border spinner-border-sm me-2"
                    role="status"
                    aria-hidden="true"
                  ></span>
                  Revocando...
                </>
              ) : (
                "Revocar Acceso"
              )}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}