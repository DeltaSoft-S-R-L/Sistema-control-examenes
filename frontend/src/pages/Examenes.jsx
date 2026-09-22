import React, { useEffect, useState } from 'react';
import api from '../services/api';

export default function Examenes() {
  const [examenes, setExamenes] = useState([]);
  const [loading, setLoading] = useState(true);

  const fetchExamenes = async () => {
    try {
      setLoading(true);
      const res = await api.get('/examenes');
      setExamenes(res.data.data || res.data || []);
    } catch (err) {
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchExamenes();
  }, []);

  return (
    <div>
      <div className="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 className="fw-bold mb-1">Gestión de Exámenes</h2>
          <p className="text-muted mb-0">Listado y control de exámenes programados</p>
        </div>
      </div>

      <div className="card border-0 shadow-sm rounded-3">
        <div className="card-body p-0">
          <div className="table-responsive">
            <table className="table table-hover align-middle mb-0">
              <thead className="table-light">
                <tr>
                  <th>ID</th>
                  <th>Nombre</th>
                  <th>Asignatura</th>
                  <th>Fecha</th>
                  <th>Hora</th>
                  <th>Duración</th>
                  <th>Estado</th>
                </tr>
              </thead>
              <tbody>
                {loading ? (
                  <tr>
                    <td colSpan="7" className="text-center py-4">
                      <div className="spinner-border spinner-border-sm text-primary me-2"></div>
                      Cargando exámenes...
                    </td>
                  </tr>
                ) : examenes.length === 0 ? (
                  <tr>
                    <td colSpan="7" className="text-center py-4 text-muted">
                      No hay exámenes registrados.
                    </td>
                  </tr>
                ) : (
                  examenes.map((ex) => (
                    <tr key={ex.id_examen}>
                      <td className="fw-bold">#{ex.id_examen}</td>
                      <td>{ex.nombre}</td>
                      <td>{ex.asignatura?.nombre || '-'}</td>
                      <td>{ex.fecha}</td>
                      <td>{ex.hora_inicio}</td>
                      <td>{ex.duracion_minutos} min</td>
                      <td>
                        <span className={`badge ${
                          ex.estado === 'en_curso' ? 'bg-success' :
                          ex.estado === 'programado' ? 'bg-primary' :
                          ex.estado === 'cancelado' ? 'bg-danger' : 'bg-secondary'
                        }`}>
                          {ex.estado}
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
