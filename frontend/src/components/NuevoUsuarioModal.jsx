import React, { useState } from 'react';
import api from '../services/api';

export default function NuevoUsuarioModal({ isOpen, onClose, onSuccess }) {
    const [formData, setFormData] = useState({
        codigo: '',
        nombre: '',
        rol: '',
        estado: 'Activo'
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
            await api.post('/usuarios', formData);
            
            setFormData({ codigo: '', nombre: '', rol: '', estado: 'Activo' });
            
            if (onSuccess) onSuccess();
            if (onClose) onClose();
        } catch (err) {
            setError(
                err.response?.data?.message ||
                err.response?.data?.errors?.codigo?.[0] ||
                'Error al registrar el usuario. Verifique los datos ingresados.'
            );
        } finally {
            setLoading(false);
        }
    };

    if (!isOpen) return null;

    return (
        <>
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
                                <div className="mb-3">
                                    <label className="form-label small fw-bold">Código</label>
                                    <input 
                                        type="text" 
                                        className="form-control bg-light" 
                                        name="codigo"
                                        value={formData.codigo}
                                        onChange={handleChange}
                                        required 
                                        autoFocus
                                    />
                                </div>
                                <div className="mb-3">
                                    <label className="form-label small fw-bold">Nombre completo</label>
                                    <input 
                                        type="text" 
                                        className="form-control bg-light" 
                                        name="nombre"
                                        value={formData.nombre}
                                        onChange={handleChange}
                                        required 
                                    />
                                </div>
                                <div className="row">
                                    <div className="col-md-6 mb-3">
                                        <label className="form-label small fw-bold">Rol</label>
                                        <select 
                                            className="form-select bg-light" 
                                            name="rol"
                                            value={formData.rol}
                                            onChange={handleChange}
                                            required
                                        >
                                            <option value="">Seleccione...</option>
                                            <option value="Administrador">Administrador</option>
                                            <option value="Docente">Docente</option>
                                            <option value="Personal de control de ingreso">Personal de control de ingreso</option>
                                        </select>
                                    </div>
                                    <div className="col-md-6 mb-3">
                                        <label className="form-label small fw-bold">Estado</label>
                                        <select 
                                            className="form-select bg-light" 
                                            name="estado"
                                            value={formData.estado}
                                            onChange={handleChange}
                                            required
                                        >
                                            <option value="Activo">Activo</option>
                                            <option value="Inactivo">Inactivo</option>
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
        </>
    );
}