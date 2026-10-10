import React, { useState, useEffect } from 'react';
import api from '../../services/api';

export default function EditarExamenModal({ isOpen, examen, onClose, onSuccess }) {
  const [formData, setFormData] = useState({
    id_asignatura: '',
    nombre: '',
    fecha: '',
    hora_inicio: '',
    duracion_minutos: 90,
    id_ambiente: '',
    descripcion: '',
    estado: 'PROGRAMADO',
  });

  const [asignaturas, setAsignaturas] = useState([]);
  const [ambientes, setAmbientes] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  useEffect(() => {
    if (isOpen && examen) {
      setError(null);
      cargarCatalogos();

      // Formatear fecha a YYYY-MM-DD
      const fechaFormateada = examen.fecha
        ? String(examen.fecha).substring(0, 10)
        : '';

      // Formatear hora a HH:mm
      const horaFormateada = examen.hora_inicio
        ? String(examen.hora_inicio).substring(0, 5)
        : '';

      // Extraer ID del primer ambiente asignado si existe
      const ambienteId =
        examen.asignaciones_ambiente && examen.asignaciones_ambiente.length > 0
          ? String(examen.asignaciones_ambiente[0].id_ambiente || '')
          : '';

      setFormData({
        id_asignatura: examen.id_asignatura ? String(examen.id_asignatura) : '',
        nombre: examen.nombre || '',
        fecha: fechaFormateada,
        hora_inicio: horaFormateada,
        duracion_minutos: examen.duracion_minutos || 90,
        id_ambiente: ambienteId,
        descripcion: examen.descripcion || '',
        estado: (examen.estado || 'PROGRAMADO').toUpperCase(),
      });
    }
  }, [isOpen, examen]);

  const cargarCatalogos = async () => {
    try {
      const [resAsig, resAmb] = await Promise.all([
        api.get('/asignaturas'),
        api.get('/ambientes'),
      ]);
      setAsignaturas(resAsig.data.data || resAsig.data || []);
      setAmbientes(resAmb.data.data || resAmb.data || []);
    } catch (err) {
      console.error('Error al cargar catálogos:', err);
    }
  };

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!examen) return;

    setError(null);
    setLoading(true);

    try {
      const payload = {
        ...formData,
        id_asignatura: Number(formData.id_asignatura),
        duracion_minutos: Number(formData.duracion_minutos),
        id_ambiente: formData.id_ambiente ? Number(formData.id_ambiente) : null,
      };

      await api.put(`/examenes/${examen.id_examen}`, payload);

      if (onSuccess) onSuccess('Examen actualizado correctamente.');
      if (onClose) onClose();
    } catch (err) {
      setError(
        err.response?.data?.message ||
          (err.response?.data?.errors
            ? Object.values(err.response.data.errors).flat().join(', ')
            : 'Error al actualizar el examen. Verifique los datos.')
      );
    } finally {
      setLoading(false);
    }
  };

  if (!isOpen || !examen) return null;

  return (
    <div
      className="modal fade show d-block"
      tabIndex="-1"
      style={{ backgroundColor: 'rgba(0,0,0,0.5)' }}
    >
      <div className="modal-dialog modal-lg modal-dialog-centered">
        <div className="modal-content border-0 rounded-4 shadow-lg">
          <div className="modal-header bg-warning-subtle text-dark border-bottom rounded-top-4">
            <h5 className="modal-title fw-bold">
              <i className="bi bi-pencil-square me-2 text-warning"></i>Modificar Sesión de Examen
            </h5>
            <button
              type="button"
              className="btn-close"
              onClick={onClose}
              disabled={loading}
              aria-label="Cerrar"
            ></button>
          </div>

          <form onSubmit={handleSubmit}>
            <div className="modal-body p-4">
              {error && (
                <div className="alert alert-danger py-2 d-flex align-items-center" role="alert">
                  <i className="bi bi-exclamation-triangle-fill me-2"></i>
                  <div>{error}</div>
                </div>
              )}

              <div className="row g-3">
                <div className="col-12 col-md-8">
                  <label className="form-label fw-semibold">Nombre del Examen *</label>
                  <input
                    type="text"
                    name="nombre"
                    className="form-control"
                    placeholder="Ej. Primer Parcial - Ingeniería de Software"
                    value={formData.nombre}
                    onChange={handleChange}
                    required
                  />
                </div>

                <div className="col-12 col-md-4">
                  <label className="form-label fw-semibold">Asignatura *</label>
                  <select
                    name="id_asignatura"
                    className="form-select"
                    value={formData.id_asignatura}
                    onChange={handleChange}
                    required
                  >
                    <option value="">Seleccione...</option>
                    {asignaturas.map((asig) => (
                      <option key={asig.id_asignatura} value={asig.id_asignatura}>
                        {asig.codigo} - {asig.nombre}
                      </option>
                    ))}
                  </select>
                </div>

                <div className="col-12 col-md-4">
                  <label className="form-label fw-semibold">Fecha *</label>
                  <input
                    type="date"
                    name="fecha"
                    className="form-control"
                    value={formData.fecha}
                    onChange={handleChange}
                    required
                  />
                </div>

                <div className="col-12 col-md-4">
                  <label className="form-label fw-semibold">Hora de Inicio *</label>
                  <input
                    type="time"
                    name="hora_inicio"
                    className="form-control"
                    value={formData.hora_inicio}
                    onChange={handleChange}
                    required
                  />
                </div>

                <div className="col-12 col-md-4">
                  <label className="form-label fw-semibold">Duración (minutos) *</label>
                  <input
                    type="number"
                    name="duracion_minutos"
                    className="form-control"
                    min="1"
                    placeholder="90"
                    value={formData.duracion_minutos}
                    onChange={handleChange}
                    required
                  />
                </div>

                <div className="col-12 col-md-6">
                  <label className="form-label fw-semibold">Ambiente / Aula</label>
                  <select
                    name="id_ambiente"
                    className="form-select"
                    value={formData.id_ambiente}
                    onChange={handleChange}
                  >
                    <option value="">Sin asignar...</option>
                    {ambientes.map((amb) => (
                      <option key={amb.id_ambiente} value={amb.id_ambiente}>
                        {amb.codigo} - {amb.nombre} (Capacidad: {amb.capacidad})
                      </option>
                    ))}
                  </select>
                </div>

                <div className="col-12 col-md-6">
                  <label className="form-label fw-semibold">Estado del Examen *</label>
                  <select
                    name="estado"
                    className="form-select"
                    value={formData.estado}
                    onChange={handleChange}
                    required
                  >
                    <option value="PROGRAMADO">PROGRAMADO</option>
                    <option value="EN_CURSO">EN CURSO</option>
                    <option value="FINALIZADO">FINALIZADO</option>
                    <option value="CANCELADO">CANCELADO</option>
                  </select>
                </div>

                <div className="col-12">
                  <label className="form-label fw-semibold">Descripción o Instrucciones</label>
                  <textarea
                    name="descripcion"
                    rows="3"
                    className="form-control"
                    placeholder="Detalles sobre el examen..."
                    value={formData.descripcion}
                    onChange={handleChange}
                  ></textarea>
                </div>
              </div>
            </div>

            <div className="modal-footer bg-light rounded-bottom-4">
              <button
                type="button"
                className="btn btn-outline-secondary"
                onClick={onClose}
                disabled={loading}
              >
                Cancelar
              </button>
              <button type="submit" className="btn btn-warning fw-semibold text-dark" disabled={loading}>
                {loading ? (
                  <>
                    <span className="spinner-border spinner-border-sm me-2" role="status"></span>
                    Guardando cambios...
                  </>
                ) : (
                  <>
                    <i className="bi bi-check2-circle me-1"></i>Guardar Cambios
                  </>
                )}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  );
}
