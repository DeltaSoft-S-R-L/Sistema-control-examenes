import React, { useEffect, useState, useMemo } from 'react';
import api from '../services/api';

export default function Ambientes() {
  const [ambientes, setAmbientes] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  // Filtros interactivos
  const [buscar, setBuscar] = useState('');
  const [filtroEstado, setFiltroEstado] = useState('');
  const [filtroCapacidad, setFiltroCapacidad] = useState('');

  const fetchAmbientes = async () => {
    try {
      setLoading(true);
      setError(null);
      const res = await api.get('/ambientes');
      setAmbientes(res.data.data || res.data || []);
    } catch (err) {
      console.error('Error al cargar ambientes:', err);
      setError('No se pudieron cargar los ambientes. Verifique la conexión con el servidor.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchAmbientes();
  }, []);

  // Filtrado reactivo en el frontend
  const ambientesFiltrados = useMemo(() => {
    return ambientes.filter((amb) => {
      const matchTexto =
        !buscar.trim() ||
        (amb.nombre && amb.nombre.toLowerCase().includes(buscar.toLowerCase())) ||
        (amb.codigo && amb.codigo.toLowerCase().includes(buscar.toLowerCase())) ||
        (amb.ubicacion && amb.ubicacion.toLowerCase().includes(buscar.toLowerCase()));

      const estadoNormalizado = (amb.estado || '').toUpperCase();
      const matchEstado =
        !filtroEstado ||
        (filtroEstado === 'DISPONIBLE' && (estadoNormalizado === 'DISPONIBLE' || estadoNormalizado === 'ACTIVO')) ||
        (filtroEstado === 'NO_DISPONIBLE' && (estadoNormalizado === 'NO_DISPONIBLE' || estadoNormalizado === 'MANTENIMIENTO' || estadoNormalizado === 'OCUPADO'));

      const asientos = amb.asientos_disponibles ?? amb.capacidad ?? 0;
      let matchCapacidad = true;
      if (filtroCapacidad === 'pequeno') matchCapacidad = asientos < 30;
      else if (filtroCapacidad === 'mediano') matchCapacidad = asientos >= 30 && asientos <= 60;
      else if (filtroCapacidad === 'grande') matchCapacidad = asientos > 60;

      return matchTexto && matchEstado && matchCapacidad;
    });
  }, [ambientes, buscar, filtroEstado, filtroCapacidad]);

  // Métricas calculadas
  const metricas = useMemo(() => {
    const total = ambientes.length;
    const totalAsientos = ambientes.reduce(
      (sum, a) => sum + (Number(a.asientos_disponibles ?? a.capacidad) || 0),
      0
    );
    const disponibles = ambientes.filter(
      (a) => (a.estado || '').toUpperCase() === 'DISPONIBLE'
    ).length;
    const promedio = total > 0 ? Math.round(totalAsientos / total) : 0;

    return { total, totalAsientos, disponibles, promedio };
  }, [ambientes]);

  const handleLimpiarFiltros = () => {
    setBuscar('');
    setFiltroEstado('');
    setFiltroCapacidad('');
  };

  const getBadgeEstado = (estado) => {
    const st = (estado || '').toUpperCase();
    if (st === 'DISPONIBLE' || st === 'ACTIVO') {
      return (
        <span className="badge bg-success-subtle text-success border border-success-subtle fw-semibold">
          <i className="bi bi-check-circle-fill me-1"></i>DISPONIBLE
        </span>
      );
    }
    return (
      <span className="badge bg-secondary-subtle text-secondary border border-secondary-subtle fw-semibold">
        <i className="bi bi-dash-circle me-1"></i>NO DISPONIBLE
      </span>
    );
  };

  return (
    <div className="container-fluid p-0">
      {/* Encabezado */}
      <div className="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
          <h2 className="fw-bold mb-1">
            <i className="bi bi-building me-2 text-primary"></i>Aulas y Ambientes
          </h2>
          <p className="text-muted mb-0">
            Control de aulas, ubicación y número de asientos disponibles para sesiones de examen.
          </p>
        </div>
      </div>

      {/* Alerta de error si ocurre */}
      {error && (
        <div className="alert alert-danger alert-dismissible fade show" role="alert">
          <i className="bi bi-exclamation-triangle-fill me-2"></i>
          {error}
          <button type="button" className="btn-close" onClick={() => setError(null)}></button>
        </div>
      )}

      {/* Tarjetas de Resumen Métrico */}
      <div className="row g-3 mb-4">
        <div className="col-6 col-lg-3">
          <div className="card border-0 shadow-sm rounded-4 h-100">
            <div className="card-body d-flex align-items-center p-3">
              <div className="rounded-3 bg-primary-subtle text-primary p-3 me-3">
                <i className="bi bi-door-open-fill fs-3"></i>
              </div>
              <div>
                <small className="text-muted fw-semibold d-block">Total Aulas</small>
                <h4 className="fw-bold mb-0">{metricas.total}</h4>
              </div>
            </div>
          </div>
        </div>

        <div className="col-6 col-lg-3">
          <div className="card border-0 shadow-sm rounded-4 h-100">
            <div className="card-body d-flex align-items-center p-3">
              <div className="rounded-3 bg-success-subtle text-success p-3 me-3">
                <i className="bi bi-person-workspace fs-3"></i>
              </div>
              <div>
                <small className="text-muted fw-semibold d-block">Asientos Totales</small>
                <h4 className="fw-bold text-success mb-0">{metricas.totalAsientos}</h4>
              </div>
            </div>
          </div>
        </div>

        <div className="col-6 col-lg-3">
          <div className="card border-0 shadow-sm rounded-4 h-100">
            <div className="card-body d-flex align-items-center p-3">
              <div className="rounded-3 bg-info-subtle text-info p-3 me-3">
                <i className="bi bi-check-all fs-3"></i>
              </div>
              <div>
                <small className="text-muted fw-semibold d-block">Aulas Disponibles</small>
                <h4 className="fw-bold mb-0">{metricas.disponibles}</h4>
              </div>
            </div>
          </div>
        </div>

        <div className="col-6 col-lg-3">
          <div className="card border-0 shadow-sm rounded-4 h-100">
            <div className="card-body d-flex align-items-center p-3">
              <div className="rounded-3 bg-warning-subtle text-warning-emphasis p-3 me-3">
                <i className="bi bi-bar-chart-fill fs-3"></i>
              </div>
              <div>
                <small className="text-muted fw-semibold d-block">Promedio Asientos</small>
                <h4 className="fw-bold mb-0">~{metricas.promedio}</h4>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Barra de Filtros y Búsqueda */}
      <div className="card border-0 shadow-sm rounded-4 mb-4">
        <div className="card-body p-3">
          <div className="row g-2 align-items-center">
            <div className="col-12 col-md-5">
              <div className="input-group">
                <span className="input-group-text bg-light border-end-0">
                  <i className="bi bi-search text-muted"></i>
                </span>
                <input
                  type="text"
                  className="form-control border-start-0"
                  placeholder="Buscar por nombre, código (ej. A101) o ubicación..."
                  value={buscar}
                  onChange={(e) => setBuscar(e.target.value)}
                />
              </div>
            </div>

            <div className="col-6 col-md-3">
              <select
                className="form-select"
                value={filtroCapacidad}
                onChange={(e) => setFiltroCapacidad(e.target.value)}
              >
                <option value="">Cualquier número de asientos</option>
                <option value="pequeno">Menos de 30 asientos</option>
                <option value="mediano">De 30 a 60 asientos</option>
                <option value="grande">Más de 60 asientos</option>
              </select>
            </div>

            <div className="col-6 col-md-3">
              <select
                className="form-select"
                value={filtroEstado}
                onChange={(e) => setFiltroEstado(e.target.value)}
              >
                <option value="">Todos los estados</option>
                <option value="DISPONIBLE">Solo Disponibles</option>
                <option value="NO_DISPONIBLE">No Disponibles</option>
              </select>
            </div>

            <div className="col-12 col-md-1 text-end">
              {(buscar || filtroEstado || filtroCapacidad) && (
                <button
                  type="button"
                  className="btn btn-outline-secondary w-100"
                  onClick={handleLimpiarFiltros}
                  title="Limpiar filtros"
                >
                  <i className="bi bi-x-lg"></i>
                </button>
              )}
            </div>
          </div>
        </div>
      </div>

      {/* Cuadrícula de Ambientes */}
      {loading ? (
        <div className="text-center py-5">
          <div className="spinner-border text-primary me-2"></div>
          <span className="text-muted fw-semibold">Cargando aulas y ambientes...</span>
        </div>
      ) : ambientesFiltrados.length === 0 ? (
        <div className="card border-0 shadow-sm rounded-4 p-5 text-center text-muted">
          <i className="bi bi-door-closed fs-1 d-block mb-2 opacity-50"></i>
          <h5 className="fw-semibold">No se encontraron aulas con los criterios indicados</h5>
          <p className="small text-muted mb-3">
            Intente ajustar los términos de búsqueda o limpie los filtros activos.
          </p>
          <div>
            <button className="btn btn-outline-primary btn-sm" onClick={handleLimpiarFiltros}>
              Restablecer filtros
            </button>
          </div>
        </div>
      ) : (
        <div className="row g-3">
          {ambientesFiltrados.map((amb) => {
            const numeroAsientos = amb.asientos_disponibles ?? amb.capacidad ?? 0;

            return (
              <div key={amb.id_ambiente} className="col-12 col-md-6 col-xl-4">
                <div className="card border-0 shadow-sm rounded-4 h-100 hover-shadow transition-all">
                  <div className="card-body p-4 d-flex flex-column justify-content-between">
                    <div>
                      {/* Cabecera de la tarjeta */}
                      <div className="d-flex justify-content-between align-items-start mb-2">
                        <div>
                          <span className="badge bg-light text-secondary border fw-semibold mb-1">
                            {amb.codigo}
                          </span>
                          <h5 className="fw-bold text-dark mb-0">{amb.nombre}</h5>
                        </div>
                        {getBadgeEstado(amb.estado)}
                      </div>

                      {/* Ubicación */}
                      <p className="text-muted small mb-3">
                        <i className="bi bi-geo-alt-fill text-danger me-1"></i>
                        {amb.ubicacion || 'Ubicación no especificada'}
                      </p>

                      {/* CAMPO DESTACADO: NÚMERO DE ASIENTOS EN EL AULA DISPONIBLE */}
                      <div className="bg-light p-3 rounded-3 border mb-3">
                        <div className="d-flex justify-content-between align-items-center">
                          <div>
                            <span className="text-muted small text-uppercase fw-bold d-block mb-1">
                              <i className="bi bi-person-workspace text-primary me-1"></i>
                              Asientos en el aula disponibles
                            </span>
                            <div className="d-flex align-items-baseline gap-1">
                              <span className="fs-3 fw-bold text-dark">{numeroAsientos}</span>
                              <span className="text-muted small fw-semibold">asientos</span>
                            </div>
                          </div>

                          <div className="text-end">
                            <span
                              className={`badge ${
                                numeroAsientos >= 80
                                  ? 'bg-primary-subtle text-primary border border-primary-subtle'
                                  : numeroAsientos >= 40
                                  ? 'bg-info-subtle text-info-emphasis border border-info-subtle'
                                  : 'bg-secondary-subtle text-secondary border border-secondary-subtle'
                              } fw-semibold`}
                            >
                              {numeroAsientos >= 80
                                ? 'Aula Grande'
                                : numeroAsientos >= 40
                                ? 'Aula Mediana'
                                : 'Aula Estándar'}
                            </span>
                          </div>
                        </div>

                        {/* Barra indicadora de proporción de capacidad */}
                        <div className="progress mt-2" style={{ height: '6px' }}>
                          <div
                            className={`progress-bar ${
                              numeroAsientos >= 80 ? 'bg-primary' : numeroAsientos >= 40 ? 'bg-info' : 'bg-success'
                            }`}
                            role="progressbar"
                            style={{ width: `${Math.min(100, Math.round((numeroAsientos / 150) * 100))}%` }}
                            aria-valuenow={numeroAsientos}
                            aria-valuemin="0"
                            aria-valuemax="150"
                          ></div>
                        </div>
                      </div>
                    </div>

                    {/* Pie de tarjeta informativo */}
                    <div className="d-flex justify-content-between align-items-center pt-2 border-top text-muted small">
                      <span>
                        <i className="bi bi-check2-circle text-success me-1"></i>
                        Listo para exámenes
                      </span>
                      <span className="fw-semibold text-primary">
                        ID #{amb.id_ambiente}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            );
          })}
        </div>
      )}
    </div>
  );
}
