import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import api from '../services/api';

export default function Usuarios() {
    const [usuarios, setUsuarios] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    
    // Estados para la paginación de Laravel
    const [currentPage, setCurrentPage] = useState(1);
    const [totalPages, setTotalPages] = useState(1);
    
    const navigate = useNavigate();

    useEffect(() => {
        fetchUsuarios(currentPage);
    }, [currentPage]);

    const fetchUsuarios = async (page) => {
        setLoading(true);
        setError(null);
        try {
            const response = await api.get(`/usuarios?page=${page}`);
            // Manejo de la estructura de datos: asume paginación estándar de Laravel
            const data = response.data.data || response.data;
            setUsuarios(data);
            
            if (response.data.last_page) {
                setTotalPages(response.data.last_page);
            }
        } catch (err) {
            setError('Error al cargar la lista de usuarios. Verifique su conexión al servidor.');
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    const handleViewDetails = (id) => {
        // Criterio de aceptación: navega al detalle de cada uno
        navigate(`/usuarios/${id}`);
    };

    return (
        <div className="container-fluid p-4">
            <div className="d-flex justify-content-between align-items-center mb-4">
                <h2 className="fw-bold">Gestión de Usuarios</h2>
                {/* Nota: Aquí se integrará el modal del Issue #22 en una tarea posterior */}
            </div>

            {error && (
                <div className="alert alert-danger py-2" role="alert">
                    {error}
                </div>
            )}

            <div className="card shadow-sm border-0 rounded-4">
                <div className="card-body p-0">
                    <div className="table-responsive">
                        <table className="table table-hover align-middle mb-0">
                            <thead className="table-light">
                                <tr>
                                    <th className="px-4 py-3 text-secondary small fw-bold uppercase">Código</th>
                                    <th className="py-3 text-secondary small fw-bold uppercase">Nombre Completo</th>
                                    <th className="py-3 text-secondary small fw-bold uppercase">Rol</th>
                                    <th className="py-3 text-secondary small fw-bold uppercase">Estado</th>
                                    <th className="px-4 py-3 text-end text-secondary small fw-bold uppercase">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                {loading ? (
                                    <tr>
                                        <td colSpan="5" className="text-center py-5">
                                            <div className="spinner-border text-success" role="status">
                                                <span className="visually-hidden">Cargando...</span>
                                            </div>
                                        </td>
                                    </tr>
                                ) : usuarios.length === 0 ? (
                                    <tr>
                                        <td colSpan="5" className="text-center py-5 text-muted">
                                            No se encontraron usuarios registrados en la base de datos.
                                        </td>
                                    </tr>
                                ) : (
                                    usuarios.map((usuario) => (
                                        <tr key={usuario.id}>
                                            <td className="px-4 fw-semibold">{usuario.codigo}</td>
                                            <td>{usuario.nombre}</td>
                                            <td>{usuario.rol}</td>
                                            <td>
                                                <span className={`badge ${usuario.estado === 'Activo' ? 'bg-success' : 'bg-secondary'}`}>
                                                    {usuario.estado}
                                                </span>
                                            </td>
                                            <td className="px-4 text-end">
                                                <button 
                                                    className="btn btn-outline-secondary btn-sm fw-bold"
                                                    onClick={() => handleViewDetails(usuario.id)}
                                                    title="Ver detalle del usuario"
                                                >
                                                    <i className="bi bi-eye"></i> Detalle
                                                </button>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
                
                {/* Controles de paginación renderizados condicionalmente */}
                {!loading && totalPages > 1 && (
                    <div className="card-footer bg-white border-0 py-3">
                        <nav aria-label="Navegación de páginas de usuarios">
                            <ul className="pagination justify-content-center mb-0">
                                <li className={`page-item ${currentPage === 1 ? 'disabled' : ''}`}>
                                    <button className="page-link text-success" onClick={() => setCurrentPage(p => p - 1)}>
                                        Anterior
                                    </button>
                                </li>
                                <li className="page-item disabled">
                                    <span className="page-link text-muted">
                                        Página {currentPage} de {totalPages}
                                    </span>
                                </li>
                                <li className={`page-item ${currentPage === totalPages ? 'disabled' : ''}`}>
                                    <button className="page-link text-success" onClick={() => setCurrentPage(p => p + 1)}>
                                        Siguiente
                                    </button>
                                </li>
                            </ul>
                        </nav>
                    </div>
                )}
            </div>
        </div>
    );
}