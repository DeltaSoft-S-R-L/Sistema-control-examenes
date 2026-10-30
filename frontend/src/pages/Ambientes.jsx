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

  // Rango máximo dinámico para la barra de progreso
  const maxCapacidad = useMemo(() => {
    if (!ambientes || ambientes.length === 0) return 100;
    const maxVal = Math.max(...ambientes.map((a) => Number(a.capacidad) || 0));
    return maxVal > 0 ? maxVal : 100;
  }, [ambientes]);

  // Filtrado reactivo en el frontend con rangos unificados
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
        (filtroEstado === 'DISPONIBLE' && estadoNormalizado === 'DISPONIBLE') ||
        (filtroEstado === 'NO_DISPONIBLE' && estadoNormalizado === 'NO_DISPONIBLE');

      // Rangos unificados: Pequeña (<35), Mediana (35-60), Grande (>60)
      const capacidad = Number(amb.capacidad) || 0;
      let matchCapacidad = true;
      if (filtroCapacidad === 'pequeno') matchCapacidad = capacidad < 35;
      else if (filtroCapacidad === 'mediano') matchCapacidad = capacidad >= 35 && capacidad <= 60;
      else if (filtroCapacidad === 'grande') matchCapacidad = capacidad > 60;

      return matchTexto && matchEstado && matchCapacidad;
    });
  }, [ambientes, buscar, filtroEstado, filtroCapacidad]);

  // Métricas calculadas con estado oficial DISPONIBLE
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
    if (st === 'DISPONIBLE') {
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
            Control de aulas, ubicación y capacidad de asientos para sesiones de examen.
          </p>
        </div>
      </div>

      {/* Alerta de error de conexión */}
      {error && (
        <div className="alert alert-danger shadow-sm border-0 rounded-4 p-4 mb-4 d-flex align-items-center justify-content-between" role="alert">
          <div className="d-flex align-items-center">
            <i className="bi bi-wifi-off fs-2 me-3 text-danger"></i>
            <div>
              <h5 className="fw-bold mb-1">Error de conexión</h5>
              <p className="mb-0 small">{error}</p>
            </div>
          </div>
          <button className="btn btn-outline-danger btn-sm fw-semibold" onClick={fetchAmbientes}>
            <i className="bi bi-arrow-clockwise me-1"></i> Reintentar
          </button>
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
                <small className="text-muted fw-semibold d-block">Asientos Disponibles</small>
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
                <option value="">Cualquier capacidad</option>
                <option value="pequeno">Aulas pequeñas (&lt; 35 asientos)</option>
                <option value="mediano">Aulas medianas (35 a 60 asientos)</option>
                <option value="grande">Aulas grandes (&gt; 60 asientos)</option>
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
      ) : error && ambientes.length === 0 ? (
        // Estado visual de error de conexión sin datos
        <div className="card border-0 shadow-sm rounded-4 p-5 text-center text-muted">
          <i className="bi bi-exclamation-triangle-fill fs-1 d-block mb-3 text-warning"></i>
          <h5 className="fw-bold text-dark">No se pudo cargar la información</h5>
          <p className="small text-muted mb-3">
            Ocurrió un inconveniente al comunicarse con el servidor.
          </p>
          <div>
            <button className="btn btn-primary btn-sm px-4 fw-semibold" onClick={fetchAmbientes}>
              <i className="bi bi-arrow-clockwise me-1"></i> Reintentar
            </button>
          </div>
        </div>
      ) : ambientes.length === 0 ? (
        // Estado visual de base de datos vacía
        <div className="card border-0 shadow-sm rounded-4 p-5 text-center text-muted">
          <i className="bi bi-inbox fs-1 d-block mb-3 opacity-50"></i>
          <h5 className="fw-semibold">No hay ambientes registrados en el sistema</h5>
          <p className="small text-muted mb-0">
            Aún no se han configurado aulas o laboratorios en la base de datos.
          </p>
        </div>
      ) : ambientesFiltrados.length === 0 ? (
        // Estado visual de búsqueda/filtros sin coincidencias
        <div className="card border-0 shadow-sm rounded-4 p-5 text-center text-muted">
          <i className="bi bi-search fs-1 d-block mb-2 opacity-50 text-secondary"></i>
          <h5 className="fw-semibold">No se encontraron aulas con los criterios seleccionados</h5>
          <p className="small text-muted mb-3">
            Modifique los términos de búsqueda o restablezca los filtros aplicados.
          </p>
          <div>
            <button className="btn btn-outline-primary btn-sm px-4" onClick={handleLimpiarFiltros}>
              Restablecer filtros
            </button>
          </div>
        </div>
      ) : (
        <div className="row g-3">
          {ambientesFiltrados.map((amb) => {
            const isDisponible = (amb.estado || '').toUpperCase() === 'DISPONIBLE';
            const capacidadTotal = Number(amb.capacidad) || 0;
            const numeroAsientos = isDisponible ? (amb.asientos_disponibles ?? capacidadTotal) : 0;

            // Clasificación unificada
            const clasificacion =
              capacidadTotal > 60
                ? { label: 'Aula Grande', badgeClass: 'bg-primary-subtle text-primary border border-primary-subtle' }
                : capacidadTotal >= 35
                ? { label: 'Aula Mediana', badgeClass: 'bg-info-subtle text-info-emphasis border border-info-subtle' }
                : { label: 'Aula Pequeña', badgeClass: 'bg-secondary-subtle text-secondary border border-secondary-subtle' };

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

                      {/* CAMPO DESTACADO: NÚMERO DE ASIENTOS EN EL AULA DISPONIBLES */}
                      <div className="bg-light p-3 rounded-3 border mb-3">
                        <div className="d-flex justify-content-between align-items-center">
                          <div>
                            <span className="text-muted small text-uppercase fw-bold d-block mb-1">
                              <i className="bi bi-person-workspace text-primary me-1"></i>
                              Asientos en el aula disponibles
                            </span>
                            <div className="d-flex align-items-baseline gap-1">
                              <span className={`fs-3 fw-bold ${isDisponible ? 'text-dark' : 'text-muted'}`}>
                                {numeroAsientos}
                              </span>
                              <span className="text-muted small fw-semibold">
                                {isDisponible ? `de ${capacidadTotal} asientos` : '(No disponible)'}
                              </span>
                            </div>
                          </div>

                          <div className="text-end">
                            <span className={`badge ${clasificacion.badgeClass} fw-semibold`}>
                              {clasificacion.label}
                            </span>
                          </div>
                        </div>

                        {/* Barra indicadora con escala dinámica basada en maxCapacidad */}
                        <div className="progress mt-2" style={{ height: '6px' }}>
                          <div
                            className={`progress-bar ${
                              !isDisponible
                                ? 'bg-secondary'
                                : capacidadTotal > 60
                                ? 'bg-primary'
                                : capacidadTotal >= 35
                                ? 'bg-info'
                                : 'bg-success'
                            }`}
                            role="progressbar"
                            style={{
                              width: isDisponible
                                ? `${Math.min(100, Math.max(5, Math.round((capacidadTotal / maxCapacidad) * 100)))}%`
                                : '0%',
                            }}
                            aria-valuenow={numeroAsientos}
                            aria-valuemin="0"
                            aria-valuemax={maxCapacidad}
                          ></div>
                        </div>
                      </div>
                    </div>

                    {/* Pie de tarjeta condicionado al estado */}
                    <div className="d-flex justify-content-between align-items-center pt-2 border-top text-muted small">
                      {isDisponible ? (
                        <span className="text-success fw-semibold">
                          <i className="bi bi-check2-circle me-1"></i>
                          Listo para exámenes
                        </span>
                      ) : (
                        <span className="text-danger fw-semibold">
                          <i className="bi bi-x-circle me-1"></i>
                          No disponible para exámenes
                        </span>
                      )}
                      <span className="fw-semibold text-secondary">
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
