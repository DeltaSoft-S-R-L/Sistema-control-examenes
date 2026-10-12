import React, { useState, useEffect } from 'react';
import api from '../../services/api';

export default function ModalHabilitacion({ isOpen, onClose, estudiante, idExamen, onSuccess }) {
    const [estado, setEstado] = useState('HABILITADO');
    const [motivo, setMotivo] = useState('');
    const [error, setError] = useState(null);
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        if (isOpen) {
            setEstado('HABILITADO');
            setMotivo('');
            setError(null);
        }
    }, [isOpen]);

    if (!isOpen) return null;

    const handleSubmit = async (e) => {
        e.preventDefault();
        setError(null);

        if (!idExamen) {
            setError('No hay un examen seleccionado.');
            return;
        }

        setLoading(true);

        try {
            await api.post('/habilitaciones', {
                id_estudiante: estudiante?.id_estudiante,
                id_examen: idExamen,
                estado: estado,
                motivo: motivo
            });

            onSuccess();
            onClose();
        } catch (err) {
            if (err.response?.status === 422) {
                setError(err.response.data.message || 'Error de validación en los datos.');
            } else {
                setError('Ocurrió un error al intentar habilitar al estudiante.');
            }
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div className="bg-white rounded-lg shadow-lg w-full max-w-md p-6">
                <div className="flex justify-between items-center border-b pb-3 mb-4">
                    <h3 className="text-lg font-semibold text-gray-800">
                        Habilitar Estudiante
                    </h3>
                    <button onClick={onClose} className="text-gray-400 hover:text-gray-600">
                        ✕
                    </button>
                </div>

                {error && (
                    <div className="mb-4 p-3 bg-red-100 text-red-700 rounded-md text-sm">
                        ⚠️ {error}
                    </div>
                )}

                <div className="mb-4 p-3 bg-blue-50 text-blue-800 rounded-md text-sm">
                    <strong>Estudiante:</strong> {estudiante?.nombre} {estudiante?.apellido} <br/>
                    <strong>Código:</strong> {estudiante?.codigo_sis || estudiante?.id_estudiante}
                </div>

                <form onSubmit={handleSubmit}>
                    <div className="mb-4">
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                            Estado
                        </label>
                        <select
                            className="w-full border border-gray-300 rounded-md px-3 py-2 outline-none focus:border-blue-500"
                            value={estado}
                            onChange={(e) => setEstado(e.target.value)}
                        >
                            <option value="HABILITADO">Habilitado</option>
                            <option value="NO_HABILITADO">No Habilitado</option>
                        </select>
                    </div>

                    <div className="mb-6">
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                            Motivo (Opcional)
                        </label>
                        <textarea
                            className="w-full border border-gray-300 rounded-md px-3 py-2 outline-none focus:border-blue-500"
                            rows="2"
                            value={motivo}
                            onChange={(e) => setMotivo(e.target.value)}
                        ></textarea>
                    </div>

                    <div className="flex justify-end space-x-3">
                        <button
                            type="button"
                            onClick={onClose}
                            className="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            disabled={loading}
                            className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
                        >
                            {loading ? 'Guardando...' : 'Habilitar'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}