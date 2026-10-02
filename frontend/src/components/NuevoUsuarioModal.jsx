import React, { useState } from 'react';
import api from '../services/api';

export default function NuevoUsuarioModal({ isOpen, onClose, onSuccess }) {
    // 1. El estado ahora coincide exactamente con las variables de Laravel
    const [formData, setFormData] = useState({
        username: '',
        correo: '',
        nombre: '',
        apellido: '',
        id_rol: '',
        estado: 'ACTIVO' 
    });
    const [error, setError] = useState(null);
    const [loading, setLoading] = useState(false);

    const handleChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({ ...prev, [name]: value }));
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setError(null);
        setLoading(true);

        try {
            // Se enviará el JSON con la estructura exacta que exige Laravel
            await api.post('/usuarios', formData);
            
            setFormData({ username: '', correo: '', nombre: '', apellido: '', id_rol: '', estado: 'ACTIVO' });
            
            if (onSuccess) onSuccess();
            if (onClose) onClose();
        } catch (err) {
            setError(
                err.response?.data?.message ||
                'Error al registrar el usuario. Verifique los datos ingresados.'
            );
        } finally {
            setLoading(false);
        }
    };

    if (!isOpen) return null;

    return (
        <div className="modal fade show d-block" tabIndex="-1" style={{ backgroundColor: 'rgba(0,0,0,0.5)' }}>
            <div className="modal-dialog modal-dialog-centered">
                <div className="modal-content border-0 rounded-4 shadow-lg">
                    <div className="modal-header bg-light rounded-top-4">
                        <h5 className="modal-title fw-bold">Nuevo Usuario</h5>
                        <button type="button" className="btn-close" onClick={onClose} disabled={loading}></button>
                    </div>
                    <div className="modal-body p-4">
                        {error && (
                            <div className="alert alert-danger py-2" role="alert">
                                {error}
                            </div>
                        )}
                        <form id="addUserForm" onSubmit={handleSubmit}>
                            {/* FILA 1: Username y Correo */}
                            <div className="row">
                                <div className="col-md-6 mb-3">
                                    <label className="form-label small fw-bold">Username (Código)</label>
                                    <input type="text" className="form-control bg-light" name="username" value={formData.username} onChange={handleChange} required autoFocus />
                                </div>
                                <div className="col-md-6 mb-3">
                                    <label className="form-label small fw-bold">Correo institucional</label>
                                    <input type="email" className="form-control bg-light" name="correo" value={formData.correo} onChange={handleChange} required />
                                </div>
                            </div>

                            {/* FILA 2: Nombre y Apellido */}
                            <div className="row">
                                <div className="col-md-6 mb-3">
                                    <label className="form-label small fw-bold">Nombre(s)</label>
                                    <input type="text" className="form-control bg-light" name="nombre" value={formData.nombre} onChange={handleChange} required />
                                </div>
                                <div className="col-md-6 mb-3">
                                    <label className="form-label small fw-bold">Apellido(s)</label>
                                    <input type="text" className="form-control bg-light" name="apellido" value={formData.apellido} onChange={handleChange} required />
                                </div>
                            </div>

                            {/* FILA 3: Rol y Estado */}
                            <div className="row">
                                <div className="col-md-6 mb-3">
                                    <label className="form-label small fw-bold">Rol</label>
                                    <select className="form-select bg-light" name="id_rol" value={formData.id_rol} onChange={handleChange} required>
                                        <option value="">Seleccione...</option>
                                        {/* Los values ahora son números, como exige el backend */}
                                        <option value="1">Administrador</option>
                                        <option value="2">Docente</option>
                                        <option value="3">Personal de control</option>
                                    </select>
                                </div>
                                <div className="col-md-6 mb-3">
                                    <label className="form-label small fw-bold">Estado</label>
                                    <select className="form-select bg-light" name="estado" value={formData.estado} onChange={handleChange} required>
                                        {/* Laravel rechaza "Inactivo", exige "REVOCADO" */}
                                        <option value="ACTIVO">Activo</option>
                                        <option value="REVOCADO">Revocado</option>
                                    </select>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div className="modal-footer bg-light rounded-bottom-4 border-top-0">
                        <button type="button" className="btn btn-outline-secondary fw-bold px-4" onClick={onClose} disabled={loading}>
                            Cancelar
                        </button>
                        <button type="submit" form="addUserForm" className="btn text-white fw-bold px-4" style={{ backgroundColor: '#0A3123' }} disabled={loading}>
                            {loading ? 'Guardando...' : 'Guardar'}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}