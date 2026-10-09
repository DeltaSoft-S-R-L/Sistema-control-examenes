import React, { useState, useEffect } from 'react';
import axios from 'axios';

export default function ConfirmarRevocarModal({ isOpen, onClose, usuario, onSuccess }) {
  const [loading, setLoading] = useState(false);
  const [errorMessage, setErrorMessage] = useState(null);

  // EFECTO: Limpia el mensaje de error cada vez que el modal se abre o cambia de usuario
  useEffect(() => {
    if (isOpen) {
      setErrorMessage(null);
    }
  }, [isOpen, usuario]);

  if (!isOpen || !usuario) return null;

  // Función para cerrar limpiando estados
  const handleCerrar = () => {
    setErrorMessage(null);
    onClose();
  };

  const handleRevocar = async () => {
    setLoading(true);
    setErrorMessage(null);

    try {
      const token = localStorage.getItem('token');
      
      await axios.put(
        `http://localhost:8000/api/usuarios/${usuario.id_usuario || usuario.id}`,
        {
          nombre: usuario.nombre,
          apellido: usuario.apellido,
          correo: usuario.correo,
          username: usuario.username,
          id_rol: usuario.id_rol || usuario.rol?.id_rol,
          estado: 'REVOCADO',
        },
        {
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: 'application/json',
          },
        }
      );

      setLoading(false);
      onSuccess(usuario);
      handleCerrar();
    } catch (err) {
      console.error("Error al revocar usuario:", err);
      setLoading(false);
      
      let mensajeServidor = "No se pudo completar la revocación. Verifique su conexión o intente nuevamente.";

      // Traductor de errores de validación de Laravel a Español
      if (err.response?.data?.errors) {
        const errores = Object.values(err.response.data.errors);
        const primerError = errores[0][0].toLowerCase(); // Convertimos a minúsculas para no fallar
        
        if (primerError.includes("format is invalid") || primerError.includes("correo field format")) {
          mensajeServidor = "El correo de este usuario tiene un formato inválido (debe terminar en @umss.edu.bo). No se puede revocar.";
        } else if (primerError.includes("has already been taken")) {
          mensajeServidor = "Uno de los datos (correo o username) ya está en uso por otra cuenta.";
        } else {
          mensajeServidor = errores[0][0]; // Muestra el original si es otro error
        }
      } 
      // Error general del servidor
      else if (err.response?.data?.message) {
        mensajeServidor = err.response.data.message;
      }
      
      setErrorMessage(mensajeServidor);
    }
  };

  return (
    <div className="modal show d-block" tabIndex="-1" style={{ backgroundColor: 'rgba(0,0,0,0.5)' }}>
      <div className="modal-dialog modal-dialog-centered">
        <div className="modal-content shadow-lg border-0">
          
          <div className="modal-header bg-danger text-white">
            <h5 className="modal-title">
              <i className="bi bi-exclamation-triangle-fill me-2"></i>
              Confirmar Revocación de Acceso
            </h5>
            <button 
              type="button" 
              className="btn-close btn-close-white" 
              onClick={handleCerrar}
              disabled={loading}
            ></button>
          </div>

          <div className="modal-body p-4">
            <p className="text-secondary mb-3">
              ¿Estás seguro de que deseas revocar el acceso a la siguiente cuenta?
            </p>

            <div className="bg-light p-3 rounded border mb-3">
              <p className="mb-1"><strong>Usuario:</strong> {usuario.nombre} {usuario.apellido}</p>
              <p className="mb-1"><strong>Código / Username:</strong> {usuario.username}</p>
              <p className="mb-0"><strong>Correo:</strong> {usuario.correo}</p>
            </div>

            <div className="alert alert-warning py-2 small mb-3" role="alert">
              <i className="bi bi-info-circle-fill me-2"></i>
              Esta acción revocará el acceso de la cuenta al sistema e invalidará inmediatamente cualquier sesión activa.
            </div>

            {errorMessage && (
              <div className="alert alert-danger py-2 small mb-0 d-flex align-items-center" role="alert">
                <i className="bi bi-exclamation-octagon-fill me-2 fs-5"></i>
                <div>{errorMessage}</div>
              </div>
            )}
          </div>

          <div className="modal-footer bg-light px-4 py-3">
            <button 
              type="button" 
              className="btn btn-outline-secondary px-4" 
              onClick={handleCerrar}
              disabled={loading}
            >
              Cancelar
            </button>
            <button 
              type="button" 
              className="btn btn-danger px-4" 
              onClick={handleRevocar}
              disabled={loading}
            >
              {loading ? (
                <>
                  <span className="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                  Revocando...
                </>
              ) : (
                'Revocar Acceso'
              )}
            </button>
          </div>

        </div>
      </div>
    </div>
  );
}