import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
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
        const [resExamenes, resEstudiantes, resAmbientes] =
          await Promise.allSettled([
            api.get('/examenes'),
            api.get('/estudiantes'),
            api.get('/ambientes'),
          ]);

        setStats({
          examenes:
            resExamenes.status === 'fulfilled'
              ? (resExamenes.value.data.total ??
                resExamenes.value.data.length ??
                0)
              : 0,

          estudiantes:
            resEstudiantes.status === 'fulfilled'
              ? (resEstudiantes.value.data.total ??
                resEstudiantes.value.data.length ??
                0)
              : 0,

          ambientes:
            resAmbientes.status === 'fulfilled'
              ? (resAmbientes.value.data.total ??
                resAmbientes.value.data.length ??
                0)
              : 0,
        });
      } catch (err) {
        console.error('Error fetching dashboard stats', err);
      } finally {
        setLoading(false);
      }
    };

    fetchStats();
  }, []);

  const tarjetas = [
    {
      titulo: 'Exámenes',
      valor: stats.examenes,
      icono: 'bi-journal-check',
      color: 'primary',
      detalle: 'Registrados',
    },
    {
      titulo: 'Estudiantes',
      valor: stats.estudiantes,
      icono: 'bi-people',
      color: 'success',
      detalle: 'Registrados',
    },
    {
      titulo: 'Ambientes',
      valor: stats.ambientes,
      icono: 'bi-building',
      color: 'warning',
      detalle: 'Disponibles',
    },
    {
      titulo: 'Ingresos',
      valor: 0,
      icono: 'bi-box-arrow-in-right',
      color: 'info',
      detalle: 'Registrados',
    },
    {
      titulo: 'Incidencias',
      valor: 0,
      icono: 'bi-exclamation-circle',
      color: 'danger',
      detalle: 'Registradas',
    },
  ];

  return (
    <div>
      {/* Encabezado */}
      <div className="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
          <h2 className="fw-bold mb-1">Panel Principal</h2>

          <p className="text-muted mb-0">
            Resumen y estado general del sistema de exámenes
          </p>
        </div>

        <div className="border rounded-3 bg-white px-3 py-2 small text-muted shadow-sm">
          <i className="bi bi-calendar3 me-2"></i>
          Sistema de Control de Exámenes
        </div>
      </div>

      {/* Tarjetas de resumen */}
      <div className="row row-cols-1 row-cols-sm-2 row-cols-lg-5 g-3 mb-4">
        {tarjetas.map((tarjeta) => (
          <div className="col" key={tarjeta.titulo}>
            <div className="card h-100 border-0 shadow-sm rounded-3">
              <div className="card-body p-3">
                <div className="d-flex justify-content-between align-items-start mb-3">
                  <span className="text-muted small fw-semibold">
                    {tarjeta.titulo}
                  </span>

                  <div
                    className={`bg-${tarjeta.color}-subtle text-${tarjeta.color} rounded-3 d-flex align-items-center justify-content-center`}
                    style={{
                      width: '34px',
                      height: '34px',
                    }}
                  >
                    <i className={`bi ${tarjeta.icono}`}></i>
                  </div>
                </div>

                <h3 className="fw-bold mb-1">
                  {loading ? '...' : tarjeta.valor}
                </h3>

                <span className="text-muted small">
                  {tarjeta.detalle}
                </span>
              </div>
            </div>
          </div>
        ))}
      </div>

      {/* Contenido principal */}
      <div className="row g-4">
        {/* Próximos exámenes */}
        <div className="col-12 col-xl-8">
          <div className="card border-0 shadow-sm rounded-3 h-100">
            <div className="card-header bg-white border-0 pt-4 px-4 pb-2">
              <div className="d-flex justify-content-between align-items-center">
                <div>
                  <h5 className="fw-bold mb-1">
                    Próximos exámenes
                  </h5>

                  <p className="text-muted small mb-0">
                    Exámenes programados próximamente
                  </p>
                </div>

                <Link
                  to="/examenes"
                  className="btn btn-sm btn-outline-primary"
                >
                  Ver todos
                  <i className="bi bi-arrow-right ms-2"></i>
                </Link>
              </div>
            </div>

            <div className="card-body px-4 pb-4">
              <div
                className="d-flex flex-column justify-content-center align-items-center text-center"
                style={{ minHeight: '220px' }}
              >
                <div
                  className="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center mb-3"
                  style={{
                    width: '48px',
                    height: '48px',
                  }}
                >
                  <i className="bi bi-calendar-event fs-5"></i>
                </div>

                <h6 className="fw-semibold">
                  No hay exámenes para mostrar
                </h6>

                <p
                  className="text-muted small mb-0"
                  style={{ maxWidth: '350px' }}
                >
                  Los próximos exámenes aparecerán aquí cuando sean
                  registrados en el sistema.
                </p>
              </div>
            </div>
          </div>
        </div>

        {/* Actividad reciente */}
        <div className="col-12 col-xl-4">
          <div className="card border-0 shadow-sm rounded-3 h-100">
            <div className="card-header bg-white border-0 pt-4 px-4 pb-2">
              <h5 className="fw-bold mb-1">
                Actividad reciente
              </h5>

              <p className="text-muted small mb-0">
                Últimos movimientos del sistema
              </p>
            </div>

            <div className="card-body px-4 pb-4">
              <div
                className="d-flex flex-column justify-content-center align-items-center text-center"
                style={{ minHeight: '220px' }}
              >
                <div
                  className="bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center mb-3"
                  style={{
                    width: '48px',
                    height: '48px',
                  }}
                >
                  <i className="bi bi-clock-history fs-5"></i>
                </div>

                <h6 className="fw-semibold">
                  Sin actividad reciente
                </h6>

                <p className="text-muted small mb-0">
                  La actividad registrada en el sistema aparecerá aquí.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Acceso rápido */}
      <div className="d-flex justify-content-end mt-4">
        <Link
          to="/control-ingreso"
          className="btn btn-primary px-4"
        >
          <i className="bi bi-box-arrow-in-right me-2"></i>
          Ir a control de ingreso
        </Link>
      </div>
    </div>
  );
}