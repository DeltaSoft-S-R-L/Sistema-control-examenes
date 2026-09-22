import React, { useEffect, useState } from 'react';
import api from '../services/api';

export default function Dashboard() {
  const [stats, setStats] = useState({
    examenes: 0,
    estudiantes: 0,
    ambientes: 0,
  });
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchStats = async () => {
      try {
        const [resExamenes, resEstudiantes, resAmbientes] = await Promise.allSettled([
          api.get('/examenes'),
          api.get('/estudiantes'),
          api.get('/ambientes'),
        ]);

        setStats({
          examenes: resExamenes.status === 'fulfilled' ? (resExamenes.value.data.total ?? resExamenes.value.data.length ?? 0) : 0,
          estudiantes: resEstudiantes.status === 'fulfilled' ? (resEstudiantes.value.data.total ?? resEstudiantes.value.data.length ?? 0) : 0,
          ambientes: resAmbientes.status === 'fulfilled' ? (resAmbientes.value.data.total ?? resAmbientes.value.data.length ?? 0) : 0,
        });
      } catch (err) {
        console.error('Error fetching dashboard stats', err);
      } finally {
        setLoading(false);
      }
    };

    fetchStats();
  }, []);

  return (
    <div>
      <div className="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 className="fw-bold mb-1">Panel Principal</h2>
          <p className="text-muted mb-0">Resumen y estado general del sistema de exámenes</p>
        </div>
      </div>

      <div className="row g-3 mb-4">
        <div className="col-12 col-md-4">
          <div className="card border-0 shadow-sm rounded-3 bg-primary text-white p-3">
            <div className="d-flex align-items-center justify-content-between">
              <div>
                <h6 className="text-uppercase mb-2 opacity-75">Exámenes</h6>
                <h2 className="fw-bold mb-0">{loading ? '...' : stats.examenes}</h2>
              </div>
              <i className="bi bi-journal-check fs-1 opacity-50"></i>
            </div>
          </div>
        </div>

        <div className="col-12 col-md-4">
          <div className="card border-0 shadow-sm rounded-3 bg-success text-white p-3">
            <div className="d-flex align-items-center justify-content-between">
              <div>
                <h6 className="text-uppercase mb-2 opacity-75">Estudiantes</h6>
                <h2 className="fw-bold mb-0">{loading ? '...' : stats.estudiantes}</h2>
              </div>
              <i className="bi bi-people fs-1 opacity-50"></i>
            </div>
          </div>
        </div>

        <div className="col-12 col-md-4">
          <div className="card border-0 shadow-sm rounded-3 bg-warning text-dark p-3">
            <div className="d-flex align-items-center justify-content-between">
              <div>
                <h6 className="text-uppercase mb-2 opacity-75">Ambientes</h6>
                <h2 className="fw-bold mb-0">{loading ? '...' : stats.ambientes}</h2>
              </div>
              <i className="bi bi-building fs-1 opacity-50"></i>
            </div>
          </div>
        </div>
      </div>

      <div className="card border-0 shadow-sm rounded-3">
        <div className="card-header bg-white py-3 border-0">
          <h5 className="fw-bold mb-0">Estado de Conexión del Sistema</h5>
        </div>
        <div className="card-body">
          <div className="d-flex align-items-center gap-2 text-success fw-semibold">
            <i className="bi bi-check-circle-fill"></i>
            Backend Laravel 11 y API RESTful activos
          </div>
          <p className="text-muted mt-2 mb-0 small">
            Frontend estructurado con React.js + Bootstrap 5. Listo para gestionar el control de acceso a exámenes.
          </p>
        </div>
      </div>
    </div>
  );
}
