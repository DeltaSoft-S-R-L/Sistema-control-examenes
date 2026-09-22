import React, { useEffect, useState } from 'react';
import api from '../services/api';

export default function Ambientes() {
  const [ambientes, setAmbientes] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchAmbientes = async () => {
      try {
        setLoading(true);
        const res = await api.get('/ambientes');
        setAmbientes(res.data.data || res.data || []);
      } catch (err) {
        console.error(err);
      } finally {
        setLoading(false);
      }
    };

    fetchAmbientes();
  }, []);

  return (
    <div>
      <div className="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 className="fw-bold mb-1">Aulas y Ambientes</h2>
          <p className="text-muted mb-0">Control de capacidad y disponibilidad de aulas para exámenes</p>
        </div>
      </div>

      <div className="row g-3">
        {loading ? (
          <div className="col-12 text-center py-5">
            <div className="spinner-border text-primary me-2"></div>
            <span>Cargando ambientes...</span>
          </div>
        ) : ambientes.length === 0 ? (
          <div className="col-12">
            <div className="alert alert-info border-0 shadow-sm">
              No hay ambientes registrados actualmente.
            </div>
          </div>
        ) : (
          ambientes.map((amb) => (
            <div key={amb.id_ambiente} className="col-12 col-md-6 col-lg-4">
              <div className="card border-0 shadow-sm rounded-3 h-100">
                <div className="card-body">
                  <div className="d-flex justify-content-between align-items-start mb-2">
                    <h5 className="card-title fw-bold text-primary mb-0">{amb.nombre}</h5>
                    <span className={`badge ${
                      amb.estado === 'disponible' ? 'bg-success' :
                      amb.estado === 'ocupado' ? 'bg-warning text-dark' : 'bg-secondary'
                    }`}>
                      {amb.estado}
                    </span>
                  </div>
                  <p className="text-muted small mb-3">Código: <span className="fw-semibold text-dark">{amb.codigo}</span></p>

                  <div className="d-flex justify-content-between text-muted small border-top pt-2">
                    <span><i className="bi bi-geo-alt me-1"></i> {amb.ubicacion || 'Sin ubicación'}</span>
                    <span><i className="bi bi-people me-1"></i> {amb.capacidad} asientos</span>
                  </div>
                </div>
              </div>
            </div>
          ))
        )}
      </div>
    </div>
  );
}
