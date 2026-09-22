import React, { useEffect, useState } from 'react';
import api from '../services/api';

export default function Estudiantes() {
  const [estudiantes, setEstudiantes] = useState([]);
  const [loading, setLoading] = useState(true);
  const [buscar, setBuscar] = useState('');

  const fetchEstudiantes = async (search = '') => {
    try {
      setLoading(true);
      const res = await api.get('/estudiantes', {
        params: search ? { buscar: search } : {},
      });
      setEstudiantes(res.data.data || res.data || []);
    } catch (err) {
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchEstudiantes();
  }, []);

  const handleSearch = (e) => {
    e.preventDefault();
    fetchEstudiantes(buscar);
  };

  return (
    <div>
      <div className="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 className="fw-bold mb-1">Estudiantes</h2>
          <p className="text-muted mb-0">Directorio de estudiantes habilitados para rendir exámenes</p>
        </div>
      </div>

      <div className="card border-0 shadow-sm rounded-3 mb-4">
        <div className="card-body">
          <form onSubmit={handleSearch} className="row g-2">
            <div className="col-12 col-md-4">
              <div className="input-group">
                <span className="input-group-text bg-light"><i className="bi bi-search"></i></span>
                <input
                  type="text"
                  className="form-control"
                  placeholder="Buscar por nombre, CI o código..."
                  value={buscar}
                  onChange={(e) => setBuscar(e.target.value)}
                />
              </div>
            </div>
            <div className="col-auto">
              <button type="submit" className="btn btn-primary">Buscar</button>
            </div>
          </form>
        </div>
      </div>

      <div className="card border-0 shadow-sm rounded-3">
        <div className="card-body p-0">
          <div className="table-responsive">
            <table className="table table-hover align-middle mb-0">
              <thead className="table-light">
                <tr>
                  <th>Código</th>
                  <th>CI</th>
                  <th>Nombre Completo</th>
                  <th>Correo</th>
                  <th>Estado</th>
                </tr>
              </thead>
              <tbody>
                {loading ? (
                  <tr>
                    <td colSpan="5" className="text-center py-4">
                      <div className="spinner-border spinner-border-sm text-primary me-2"></div>
                      Cargando estudiantes...
                    </td>
                  </tr>
                ) : estudiantes.length === 0 ? (
                  <tr>
                    <td colSpan="5" className="text-center py-4 text-muted">
                      No se encontraron estudiantes.
                    </td>
                  </tr>
                ) : (
                  estudiantes.map((est) => (
                    <tr key={est.id_estudiante}>
                      <td className="fw-bold text-primary">{est.codigo_universitario}</td>
                      <td>{est.ci}</td>
                      <td>{est.nombre} {est.apellido}</td>
                      <td>{est.correo || '-'}</td>
                      <td>
                        <span className={`badge ${est.estado === 'activo' ? 'bg-success' : 'bg-secondary'}`}>
                          {est.estado}
                        </span>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  );
}
