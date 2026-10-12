import React from 'react';

/**
 * Componente de presentación para mostrar errores de traslape de ambientes.
 * Renderiza una alerta visual cuando el backend detecta un conflicto de horarios.
 * 
 * @param {string} mensaje - Detalle del error devuelto por la API.
 * @param {function} onClose - Función para ocultar la alerta de la vista.
 */
export default function AlertaTraslape({ mensaje, onClose }) {
    if (!mensaje) return null;

    return (
        <div className="bg-red-50 border-l-4 border-red-500 p-4 mb-4 rounded-md shadow-sm flex justify-between items-start">
            <div className="flex items-start">
                {/* Ícono de advertencia */}
                <svg 
                    className="w-6 h-6 text-red-500 mr-3 flex-shrink-0 mt-0.5" 
                    fill="none" 
                    stroke="currentColor" 
                    viewBox="0 0 24 24" 
                    xmlns="http://www.w3.org/2000/svg"
                    aria-hidden="true"
                >
                    <path 
                        strokeLinecap="round" 
                        strokeLinejoin="round" 
                        strokeWidth="2" 
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                    />
                </svg>
                
                <div>
                    <h4 className="text-red-800 font-bold text-sm m-0">Conflicto de Horario y Ambiente</h4>
                    <p className="text-red-700 text-sm mt-1 mb-0">
                        {mensaje || 'El ambiente seleccionado ya está ocupado en la fecha y hora indicadas.'}
                    </p>
                </div>
            </div>
            
            {onClose && (
                <button 
                    onClick={onClose} 
                    className="text-red-400 hover:text-red-600 font-bold px-2 flex-shrink-0"
                    aria-label="Cerrar alerta"
                >
                    {/* Ícono de cierre */}
                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            )}
        </div>
    );
}