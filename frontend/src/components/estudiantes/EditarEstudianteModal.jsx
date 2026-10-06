import React, { useEffect, useState } from "react";
import api from "../../services/api";

const formularioInicial = {
  ci: "",
  codigo_universitario: "",
  nombre: "",
  apellido: "",
  correo: "",
  estado: "ACTIVO",
  id_facultad: "",
  id_carrera: "",
  asignaturas: [],
};

export default function EditarEstudianteModal({
  mostrar,
  estudiante,
  onCerrar,
  onActualizado,
}) {
  const [formulario, setFormulario] = useState(formularioInicial);
  const [guardando, setGuardando] = useState(false);
  const [error, setError] = useState("");
  const [facultades, setFacultades] = useState([]);
  const [carreras, setCarreras] = useState([]);
  const [asignaturasDisponibles, setAsignaturasDisponibles] = useState([]);
  const [cargandoCatalogos, setCargandoCatalogos] = useState(false);

  useEffect(() => {
    if (!mostrar || !estudiante) {
      return;
    }

    setFormulario({
      ci: estudiante.ci || "",
      codigo_universitario: estudiante.codigo_universitario || "",
      nombre: estudiante.nombre || "",
      apellido: estudiante.apellido || "",
      correo: estudiante.correo || "",
      estado: estudiante.estado?.toUpperCase() || "ACTIVO",
      id_carrera: estudiante.id_carrera ? String(estudiante.id_carrera) : "",
      asignaturas: (estudiante.asignaturas || []).map((asignatura) =>
        Number(asignatura.id_asignatura),
      ),
    });

    setError("");
    setCargandoCatalogos(true);

    const cargarCatalogos = async () => {
      try {
        const respuestaFacultades = await api.get("/facultades");

        const idCarreraActual = estudiante.id_carrera;

        if (!idCarreraActual) {
          setFacultades(respuestaFacultades.data);
          setCarreras([]);
          setAsignaturasDisponibles([]);
          return;
        }

        const carreraActual = await api.get(`/carreras/${idCarreraActual}`);

        const idFacultadActual = carreraActual.data.id_facultad;

        const [respuestaCarreras, respuestaAsignaturas] = await Promise.all([
          api.get(`/carreras?id_facultad=${idFacultadActual}`),
          api.get(`/asignaturas?id_carrera=${idCarreraActual}`),
        ]);

        setFacultades(respuestaFacultades.data);
        setCarreras(respuestaCarreras.data);
        setAsignaturasDisponibles(respuestaAsignaturas.data);

        setFormulario((anterior) => ({
          ...anterior,
          id_facultad: String(idFacultadActual),
        }));
      } catch (err) {
        setError(
          err.response?.data?.message ||
            "No se pudieron cargar los datos académicos del estudiante.",
        );
      } finally {
        setCargandoCatalogos(false);
      }
    };

    cargarCatalogos();
  }, [mostrar, estudiante]);

  if (!mostrar || !estudiante) {
    return null;
  }

  const handleChange = (e) => {
    const { name, value } = e.target;

    setFormulario((anterior) => ({
      ...anterior,
      [name]: value,
    }));
  };

  const handleFacultadChange = async (e) => {
    const idFacultad = e.target.value;

    setFormulario((anterior) => ({
      ...anterior,
      id_facultad: idFacultad,
      id_carrera: "",
      asignaturas: [],
    }));

    setCarreras([]);
    setAsignaturasDisponibles([]);
    setError("");

    if (!idFacultad) {
      return;
    }

    try {
      const respuesta = await api.get(`/carreras?id_facultad=${idFacultad}`);

      setCarreras(respuesta.data);
    } catch (err) {
      setError(
        err.response?.data?.message ||
          "No se pudieron cargar las carreras de la facultad.",
      );
    }
  };

  const handleCarreraChange = async (e) => {
    const idCarrera = e.target.value;

    setFormulario((anterior) => ({
      ...anterior,
      id_carrera: idCarrera,
      asignaturas: [],
    }));

    setAsignaturasDisponibles([]);
    setError("");

    if (!idCarrera) {
      return;
    }

    try {
      const respuesta = await api.get(`/asignaturas?id_carrera=${idCarrera}`);

      setAsignaturasDisponibles(respuesta.data);
    } catch (err) {
      setError(
        err.response?.data?.message ||
          "No se pudieron cargar las materias de la carrera.",
      );
    }
  };

  const handleAsignaturaChange = (e) => {
    const idAsignatura = Number(e.target.value);
    const seleccionada = e.target.checked;

    setFormulario((anterior) => ({
      ...anterior,
      asignaturas: seleccionada
        ? [...anterior.asignaturas, idAsignatura]
        : anterior.asignaturas.filter((id) => id !== idAsignatura),
    }));

    setError("");
  };

  const handleSubmit = async (e) => {
    e.preventDefault();

    if (!formulario.id_facultad) {
      setError("Seleccione una facultad.");
      return;
    }

    if (!formulario.id_carrera) {
      setError("Seleccione una carrera.");
      return;
    }

    if (formulario.asignaturas.length === 0) {
      setError("Seleccione al menos una materia.");
      return;
    }

    try {
      setGuardando(true);
      setError("");

      const datos = {
        ci: formulario.ci.trim(),
        codigo_universitario: formulario.codigo_universitario.trim(),
        nombre: formulario.nombre.trim(),
        apellido: formulario.apellido.trim(),
        correo: formulario.correo.trim() || null,

        // El backend valida estados en mayúsculas (ACTIVO/INACTIVO).
        estado: formulario.estado.toUpperCase(),

        id_carrera: Number(formulario.id_carrera),
        asignaturas: formulario.asignaturas,
      };

      const respuesta = await api.put(
        `/estudiantes/${estudiante.id_estudiante}`,
        datos,
      );

      onActualizado(respuesta.data);
      onCerrar();
    } catch (err) {
      console.error("Error al actualizar estudiante:", err);

      const erroresValidacion = err.response?.data?.errors;

      if (erroresValidacion) {
        const primerError = Object.values(erroresValidacion)[0];

        setError(
          Array.isArray(primerError)
            ? primerError[0]
            : "Revise los datos ingresados.",
        );
      } else {
        setError(
          err.response?.data?.message || "No se pudo actualizar el estudiante.",
        );
      }
    } finally {
      setGuardando(false);
    }
  };

  return (
    <>
      <div
        className="modal fade show d-block"
        tabIndex="-1"
        role="dialog"
        aria-modal="true"
      >
        <div className="modal-dialog modal-lg modal-dialog-centered">
          <div className="modal-content border-0 shadow">
            <div className="modal-header bg-primary text-white">
              <div>
                <h5 className="modal-title fw-bold">Editar estudiante</h5>

                <small className="text-white-50">
                  Modifique los datos del estudiante seleccionado.
                </small>
              </div>

              <button
                type="button"
                className="btn-close btn-close-white"
                onClick={onCerrar}
                disabled={guardando}
                aria-label="Cerrar"
              ></button>
            </div>

            <form onSubmit={handleSubmit}>
              <div className="modal-body p-4">
                {error && (
                  <div className="alert alert-danger" role="alert">
                    <i className="bi bi-exclamation-triangle-fill me-2"></i>
                    {error}
                  </div>
                )}

                <div className="row g-3">
                  <div className="col-md-6">
                    <label className="form-label fw-semibold">CI *</label>

                    <input
                      type="text"
                      className="form-control"
                      name="ci"
                      value={formulario.ci}
                      onChange={handleChange}
                      required
                      maxLength={20}
                    />
                  </div>

                  <div className="col-md-6">
                    <label className="form-label fw-semibold">
                      Código universitario *
                    </label>

                    <input
                      type="text"
                      className="form-control"
                      name="codigo_universitario"
                      value={formulario.codigo_universitario}
                      onChange={handleChange}
                      required
                      maxLength={50}
                    />
                  </div>

                  <div className="col-md-6">
                    <label className="form-label fw-semibold">Nombre *</label>

                    <input
                      type="text"
                      className="form-control"
                      name="nombre"
                      value={formulario.nombre}
                      onChange={handleChange}
                      required
                      maxLength={100}
                    />
                  </div>

                  <div className="col-md-6">
                    <label className="form-label fw-semibold">Apellido *</label>

                    <input
                      type="text"
                      className="form-control"
                      name="apellido"
                      value={formulario.apellido}
                      onChange={handleChange}
                      required
                      maxLength={100}
                    />
                  </div>

                  {/* FACULTAD */}
                  <div className="col-12">
                    <label className="form-label fw-semibold">Facultad *</label>

                    <select
                      className="form-select"
                      name="id_facultad"
                      value={formulario.id_facultad}
                      onChange={handleFacultadChange}
                      disabled={cargandoCatalogos}
                      required
                    >
                      <option value="">
                        {cargandoCatalogos
                          ? "Cargando facultades..."
                          : "Seleccione una facultad"}
                      </option>

                      {facultades.map((facultad) => (
                        <option
                          key={facultad.id_facultad}
                          value={facultad.id_facultad}
                        >
                          {facultad.codigo} - {facultad.nombre}
                        </option>
                      ))}
                    </select>
                  </div>

                  {/* CARRERA */}
                  <div className="col-12">
                    <label className="form-label fw-semibold">Carrera *</label>

                    <select
                      className="form-select"
                      name="id_carrera"
                      value={formulario.id_carrera}
                      onChange={handleCarreraChange}
                      disabled={cargandoCatalogos || !formulario.id_facultad}
                      required
                    >
                      <option value="">
                        {cargandoCatalogos
                          ? "Cargando carreras..."
                          : "Seleccione una carrera"}
                      </option>

                      {carreras.map((carrera) => (
                        <option
                          key={carrera.id_carrera}
                          value={carrera.id_carrera}
                        >
                          {carrera.codigo} - {carrera.nombre}
                        </option>
                      ))}
                    </select>
                  </div>

                  {/* MATERIAS */}
                  <div className="col-12">
                    <label className="form-label fw-semibold">Materias *</label>

                    <div className="border rounded p-3">
                      {cargandoCatalogos ? (
                        <div className="text-muted small">
                          Cargando materias...
                        </div>
                      ) : asignaturasDisponibles.length === 0 ? (
                        <div className="text-muted small">
                          No hay materias disponibles.
                        </div>
                      ) : (
                        <div className="row g-2">
                          {asignaturasDisponibles.map((asignatura) => (
                            <div
                              className="col-12 col-md-6"
                              key={asignatura.id_asignatura}
                            >
                              <div className="form-check">
                                <input
                                  className="form-check-input"
                                  type="checkbox"
                                  id={`editar-asignatura-${asignatura.id_asignatura}`}
                                  value={asignatura.id_asignatura}
                                  checked={formulario.asignaturas.includes(
                                    Number(asignatura.id_asignatura),
                                  )}
                                  onChange={handleAsignaturaChange}
                                />

                                <label
                                  className="form-check-label"
                                  htmlFor={`editar-asignatura-${asignatura.id_asignatura}`}
                                >
                                  {asignatura.codigo} - {asignatura.nombre}
                                </label>
                              </div>
                            </div>
                          ))}
                        </div>
                      )}
                    </div>
                  </div>

                  <div className="col-md-8">
                    <label className="form-label fw-semibold">Correo</label>

                    <input
                      type="email"
                      className="form-control"
                      name="correo"
                      value={formulario.correo}
                      onChange={handleChange}
                      maxLength={150}
                    />
                  </div>

                  <div className="col-md-4">
                    <label className="form-label fw-semibold">Estado *</label>

                    <select
                      className="form-select"
                      name="estado"
                      value={formulario.estado}
                      onChange={handleChange}
                      required
                    >
                      <option value="ACTIVO">ACTIVO</option>
                      <option value="INACTIVO">INACTIVO</option>
                    </select>
                  </div>
                </div>
              </div>

              <div className="modal-footer">
                <button
                  type="button"
                  className="btn btn-outline-secondary"
                  onClick={onCerrar}
                  disabled={guardando}
                >
                  Cancelar
                </button>

                <button
                  type="submit"
                  className="btn btn-primary"
                  disabled={guardando}
                >
                  {guardando ? (
                    <>
                      <span
                        className="spinner-border spinner-border-sm me-2"
                        role="status"
                      ></span>
                      Guardando...
                    </>
                  ) : (
                    <>
                      <i className="bi bi-floppy me-2"></i>
                      Guardar cambios
                    </>
                  )}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <div className="modal-backdrop fade show"></div>
    </>
  );
}
