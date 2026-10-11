import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import api from '../services/api';
import { getRol, getUsuario, ROLES } from '../utils/auth';

function obtenerTotal(response) {
  const data = response?.data;

  if (typeof data?.total === 'number') {
    return data.total;
  }

  if (Array.isArray(data)) {
    return data.length;
  }

  if (Array.isArray(data?.data)) {
    return data.data.length;
  }

  return 0;
}

export default function Dashboard() {
  const rol = getRol();
  const usuario = getUsuario();

  const [stats, setStats] = useState({
    examenes: 0,
    estudiantes: 0,
    ambientes: 0,
    ingresos: 0,
    incidencias: 0,
  });

  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchStats = async () => {
      try {
        let peticiones = [];

        if (rol === ROLES.ADMINISTRADOR) {
          peticiones = [
            ['examenes', api.get('/examenes')],
            ['estudiantes', api.get('/estudiantes')],
            ['ambientes', api.get('/ambientes')],
            ['ingresos', api.get('/ingresos')],
            ['incidencias', api.get('/incidencias')],
          ];
        }

        if (rol === ROLES.DOCENTE) {
          peticiones = [
            ['examenes', api.get('/examenes')],
            ['estudiantes', api.get('/estudiantes')],
          ];
        }

        if (rol === ROLES.CONTROL_INGRESO) {
          peticiones = [
            ['ingresos', api.get('/ingresos')],
            ['incidencias', api.get('/incidencias')],
          ];
        }

        const resultados = await Promise.allSettled(
          peticiones.map(([, peticion]) => peticion)
        );

        const nuevosStats = {
          examenes: 0,
          estudiantes: 0,
          ambientes: 0,
          ingresos: 0,
          incidencias: 0,
        };

        resultados.forEach((resultado, index) => {
          if (resultado.status === 'fulfilled') {
            const [nombre] = peticiones[index];
            nuevosStats[nombre] = obtenerTotal(resultado.value);
          }
        });

        setStats(nuevosStats);
      } catch (error) {
        console.error('Error al cargar el panel:', error);
      } finally {
        setLoading(false);
      }
    };

    fetchStats();
  }, [rol]);

  const configuracion = {
    [ROLES.ADMINISTRADOR]: {
      titulo: 'Panel de administración',
      descripcion: 'Resumen y estado general del sistema de control de exámenes.',
      tarjetas: [
        {
          titulo: 'Exámenes',
          valor: stats.examenes,
          icono: 'bi-journal-check',
          color: 'primary',
        },
        {
          titulo: 'Estudiantes',
          valor: stats.estudiantes,
          icono: 'bi-people',
          color: 'success',
        },
        {
          titulo: 'Ambientes',
          valor: stats.ambientes,
          icono: 'bi-building',
          color: 'warning',
        },
        {
          titulo: 'Ingresos',
          valor: stats.ingresos,
          icono: 'bi-box-arrow-in-right',
          color: 'info',
        },
        {
          titulo: 'Incidencias',
          valor: stats.incidencias,
          icono: 'bi-exclamation-circle',
          color: 'danger',
        },
      ],
      accesos: [
        {
          to: '/usuarios',
          label: 'Gestionar usuarios',
          icono: 'bi-person-gear',
        },
        {
          to: '/examenes',
          label: 'Gestionar exámenes',
          icono: 'bi-journal-check',
        },
        {
          to: '/ambientes',
          label: 'Gestionar ambientes',
          icono: 'bi-building',
        },
        {
          to: '/reportes',
          label: 'Consultar reportes',
          icono: 'bi-bar-chart',
        },
      ],
    },

    [ROLES.DOCENTE]: {
      titulo: 'Panel docente',
      descripcion: `Bienvenido${usuario?.nombre ? `, ${usuario.nombre}` : ''}. Accede a la gestión académica de tus exámenes.`,
      tarjetas: [
        {
          titulo: 'Mis exámenes',
          valor: stats.examenes,
          icono: 'bi-journal-check',
          color: 'primary',
        },
        {
          titulo: 'Estudiantes',
          valor: stats.estudiantes,
          icono: 'bi-people',
          color: 'success',
        },
      ],
      accesos: [
        {
          to: '/examenes',
          label: 'Mis exámenes',
          icono: 'bi-journal-check',
        },
        {
          to: '/estudiantes',
          label: 'Estudiantes',
          icono: 'bi-people',
        },
        {
          to: '/habilitaciones',
          label: 'Habilitaciones',
          icono: 'bi-person-check',
        },
        {
          to: '/reportes',
          label: 'Reportes',
          icono: 'bi-bar-chart',
        },
      ],
    },

    [ROLES.CONTROL_INGRESO]: {
      titulo: 'Panel de control de ingreso',
      descripcion: `Bienvenido${usuario?.nombre ? `, ${usuario.nombre}` : ''}. Gestiona el ingreso y las incidencias de los estudiantes.`,
      tarjetas: [
        {
          titulo: 'Ingresos',
          valor: stats.ingresos,
          icono: 'bi-box-arrow-in-right',
          color: 'success',
        },
        {
          titulo: 'Incidencias',
          valor: stats.incidencias,
          icono: 'bi-exclamation-triangle',
          color: 'danger',
        },
      ],
      accesos: [
        {
          to: '/control-ingreso',
          label: 'Control de ingreso',
          icono: 'bi-box-arrow-in-right',
        },
        {
          to: '/incidencias',
          label: 'Registrar incidencia',
          icono: 'bi-exclamation-triangle',
        },
        {
          to: '/reportes',
          label: 'Consultar reportes',
          icono: 'bi-bar-chart',
        },
      ],
    },
  };

  const panel = configuracion[rol];

  if (!panel) {
    return (
      <div className="alert alert-warning">
        No se pudo determinar el rol del usuario.
      </div>
    );
  }

  return (
    <div>
      <div className="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
          <h2 className="fw-bold mb-1">{panel.titulo}</h2>
          <p className="text-muted mb-0">{panel.descripcion}</p>
        </div>

        <div className="border rounded-3 bg-white px-3 py-2 small shadow-sm">
          <i className="bi bi-shield-check me-2 text-primary"></i>
          <span className="text-muted">Rol: </span>
          <span className="fw-semibold">{rol}</span>
        </div>
      </div>

      <div className="row g-3 mb-4">
        {panel.tarjetas.map((tarjeta) => (
          <div
            className="col-12 col-sm-6 col-lg-4 col-xl"
            key={tarjeta.titulo}
          >
            <div className="card h-100 border-0 shadow-sm rounded-3">
              <div className="card-body p-3">
                <div className="d-flex justify-content-between align-items-start mb-3">
                  <span className="text-muted small fw-semibold">
                    {tarjeta.titulo}
                  </span>

                  <div
                    className={`bg-${tarjeta.color}-subtle text-${tarjeta.color} rounded-3 d-flex align-items-center justify-content-center`}
                    style={{
                      width: '38px',
                      height: '38px',
                    }}
                  >
                    <i className={`bi ${tarjeta.icono}`}></i>
                  </div>
                </div>

                <h3 className="fw-bold mb-1">
                  {loading ? '...' : tarjeta.valor}
                </h3>

                <span className="text-muted small">
                  Registros disponibles
                </span>
              </div>
            </div>
          </div>
        ))}
      </div>

      <div className="card border-0 shadow-sm rounded-3">
        <div className="card-header bg-white border-0 pt-4 px-4">
          <h5 className="fw-bold mb-1">Accesos rápidos</h5>
          <p className="text-muted small mb-0">
            Opciones disponibles para tu rol
          </p>
        </div>

        <div className="card-body p-4">
          <div className="row g-3">
            {panel.accesos.map((acceso) => (
              <div
                className="col-12 col-md-6 col-xl-3"
                key={acceso.to}
              >
                <Link
                  to={acceso.to}
                  className="btn btn-outline-primary w-100 h-100 py-3 d-flex align-items-center justify-content-center"
                >
                  <i className={`bi ${acceso.icono} me-2`}></i>
                  {acceso.label}
                </Link>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
}