import React from 'react';

export default function ModalAmbienteIncorrecto({ isOpen, onClose, ambienteAsignado, estudiante }) {
  if (!isOpen) return null;

  return (
    <div className="modal fade show d-block" tabIndex="-1" style={{ backgroundColor: 'rgba(0,0,0,0.6)', zIndex: 1055 }}>
      <div className="modal-dialog modal-dialog-centered">
        <div className="modal-content border-warning border-3 shadow-lg">
          <div className="modal-header bg-warning text-dark border-bottom-0">
            <h5 className="modal-title fw-bold">
              <i className="bi bi-exclamation-triangle-fill me-2"></i>
              Ambiente Incorrecto
            </h5>
          </div>
          <div className="modal-body text-center py-4">
            <h4 className="fw-bold text-dark mb-3">
              Estudiante habilitado, pero en el recinto equivocado.
            </h4>
            
            {estudiante && (
              <p className="fs-5 fw-semibold text-secondary mb-3">
                {estudiante.nombre} {estudiante.apellido}
              </p>
            )}

            <div className="alert alert-warning py-3 mb-4 shadow-sm border border-warning-subtle">
              <p className="mb-1 fw-semibold text-dark">El recinto correcto del estudiante es:</p>
              <h3 className="fw-bold text-danger mb-0 text-uppercase">
                {ambienteAsignado || 'No especificado'}
              </h3>
            </div>
            
            <p className="text-danger fw-bold small bg-danger bg-opacity-10 p-2 rounded border border-danger-subtle">
              <i className="bi bi-slash-circle me-1"></i>
              Advertencia: Este control NO habilita el ingreso físico en esta aula.
            </p>
          </div>
          <div className="modal-footer border-top-0 justify-content-center pb-4">
            <button 
              type="button" 
              className="btn btn-warning btn-lg fw-bold w-100 shadow-sm" 
              onClick={onClose}
            >
              <i className="bi bi-check-circle me-2"></i>Entendido / Siguiente estudiante
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}