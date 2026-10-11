import React, { useEffect, useState } from "react";
import api from "../../services/api";

const estadoInicial = {
  ci: "",
  codigoUniversitario: "",
  nombre: "",
  apellido: "",
  correo: "",
  estado: "ACTIVO",
  idFacultad: "",
  idCarrera: "",
  asignaturas: [],
};

export default function NuevoEstudianteModal({
  mostrar,
  onCerrar,
  onRegistrado,
}) {
  const [formulario, setFormulario] = useState(estadoInicial);
  const [errores, setErrores] = useState({});
  const [errorGeneral, setErrorGeneral] = useState("");
  const [guardando, setGuardando] = useState(false);
  const [facultades, setFacultades] = useState([]);
  const [carreras, setCarreras] = useState([]);
  const [asignaturasDisponibles, setAsignaturasDisponibles] = useState([]);
  const [cargandoCatalogos, setCargandoCatalogos] = useState(false);

  useEffect(() => {
    if (!mostrar) {
      return;
    }

    setFormulario(estadoInicial);
    setErrores({});
    setErrorGeneral("");
    setCargandoCatalogos(true);

    const cargarCatalogos = async () => {
      try {
        const respuestaFacultades = await api.get("/facultades");

        setFacultades(respuestaFacultades.data);
        setCarreras([]);
        setAsignaturasDisponibles([]);
      } catch (err) {
        setErrorGeneral(
          err.response?.data?.message ||
            "No se pudieron cargar las facultades.",
        );
      } finally {
        setCargandoCatalogos(false);
      }
    };

    cargarCatalogos();
  }, [mostrar]);

  if (!mostrar) {
    return null;
  }

  const handleChange = (e) => {
    const { name, value } = e.target;

    setFormulario((actual) => ({
      ...actual,
      [name]: value,
    }));

    setErrores((actual) => ({
      ...actual,
      [name]: "",
    }));

    setErrorGeneral("");
  };
  const handleFacultadChange = async (e) => {
    const idFacultad = e.target.value;

    setFormulario((actual) => ({
      ...actual,
      idFacultad,
      idCarrera: "",
      asignaturas: [],
    }));

    setCarreras([]);
    setAsignaturasDisponibles([]);

    setErrores((actual) => ({
      ...actual,
      idFacultad: "",
      idCarrera: "",
      asignaturas: "",
    }));

    setErrorGeneral("");

    if (!idFacultad) {
      return;
    }

    try {
      const respuesta = await api.get(`/carreras?id_facultad=${idFacultad}`);

      setCarreras(respuesta.data);
    } catch (err) {
      setErrorGeneral(
        err.response?.data?.message ||
          "No se pudieron cargar las carreras de la facultad.",
      );
    }
  };

  const handleCarreraChange = async (e) => {
    const idCarrera = e.target.value;

    setFormulario((actual) => ({
      ...actual,
      idCarrera,
      asignaturas: [],
    }));

    setAsignaturasDisponibles([]);

    setErrores((actual) => ({
      ...actual,
      idCarrera: "",
      asignaturas: "",
    }));

    setErrorGeneral("");

    if (!idCarrera) {
      return;
    }

    try {
      const respuesta = await api.get(`/asignaturas?id_carrera=${idCarrera}`);

      setAsignaturasDisponibles(respuesta.data);
    } catch (err) {
      setErrorGeneral(
        err.response?.data?.message ||
          "No se pudieron cargar las materias de la carrera.",
      );
    }
  };
  const handleAsignaturaChange = (e) => {
    const idAsignatura = Number(e.target.value);
    const seleccionada = e.target.checked;

    setFormulario((actual) => ({
      ...actual,
      asignaturas: seleccionada
        ? [...actual.asignaturas, idAsignatura]
        : actual.asignaturas.filter((id) => id !== idAsignatura),
    }));

    setErrores((actual) => ({
      ...actual,
      asignaturas: "",
    }));

    setErrorGeneral("");
  };
  const validarFormulario = () => {
    const nuevosErrores = {};

    const formatoIdentificador = /^[A-Za-z0-9-]+$/;
    const formatoNombre = /^[A-Za-zÁÉÍÓÚáéíóúÑñÜü\s'-]+$/;
    const formatoCorreo = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (!formulario.ci.trim()) {
      nuevosErrores.ci = "El CI es obligatorio.";
    } else if (formulario.ci.length > 20) {
      nuevosErrores.ci = "El CI no puede superar los 20 caracteres.";
    } else if (!formatoIdentificador.test(formulario.ci)) {
      nuevosErrores.ci = "Ingrese un CI válido.";
    }

    if (!formulario.codigoUniversitario.trim()) {
      nuevosErrores.codigoUniversitario =
        "El código universitario es obligatorio.";
    } else if (formulario.codigoUniversitario.length > 50) {
      nuevosErrores.codigoUniversitario =
        "El código no puede superar los 50 caracteres.";
    } else if (!formatoIdentificador.test(formulario.codigoUniversitario)) {
      nuevosErrores.codigoUniversitario =
        "Ingrese un código universitario válido.";
    }

    if (!formulario.nombre.trim()) {
      nuevosErrores.nombre = "El nombre es obligatorio.";
    } else if (formulario.nombre.length > 100) {
      nuevosErrores.nombre = "El nombre no puede superar los 100 caracteres.";
    } else if (!formatoNombre.test(formulario.nombre)) {
      nuevosErrores.nombre = "Ingrese un nombre válido.";
    }

    if (!formulario.apellido.trim()) {
      nuevosErrores.apellido = "El apellido es obligatorio.";
    } else if (formulario.apellido.length > 100) {
      nuevosErrores.apellido =
        "El apellido no puede superar los 100 caracteres.";
    } else if (!formatoNombre.test(formulario.apellido)) {
      nuevosErrores.apellido = "Ingrese un apellido válido.";
    }

    if (formulario.correo.trim() && !formatoCorreo.test(formulario.correo)) {
      nuevosErrores.correo = "Ingrese un correo electrónico válido.";
    }

    if (formulario.correo.length > 150) {
      nuevosErrores.correo = "El correo no puede superar los 150 caracteres.";
    }

    if (!formulario.estado) {
      nuevosErrores.estado = "Seleccione un estado.";
    } else if (
      formulario.estado !== "ACTIVO" &&
      formulario.estado !== "INACTIVO"
    ) {
      nuevosErrores.estado = "Seleccione un estado válido.";
    }
    if (!formulario.idFacultad) {
      nuevosErrores.idFacultad = "Seleccione una facultad.";
    }
    if (!formulario.idCarrera) {
      nuevosErrores.idCarrera = "Seleccione una carrera.";
    }

    if (formulario.asignaturas.length === 0) {
      nuevosErrores.asignaturas = "Seleccione al menos una materia.";
    }
    setErrores(nuevosErrores);

    return Object.keys(nuevosErrores).length === 0;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();

    if (!validarFormulario()) {
      return;
    }

    setGuardando(true);
    setErrorGeneral("");

    try {
      const payload = {
        ci: formulario.ci.trim(),
        codigo_universitario: formulario.codigoUniversitario.trim(),
        nombre: formulario.nombre.trim(),
        apellido: formulario.apellido.trim(),
        correo: formulario.correo.trim() || null,
        estado: formulario.estado,
        id_carrera: Number(formulario.idCarrera),
        asignaturas: formulario.asignaturas,
      };

      await api.post("/estudiantes", payload);

      onRegistrado();
      onCerrar();
    } catch (err) {
      const validaciones = err.response?.data?.errors;

      if (validaciones) {
        const nuevosErrores = {};

        if (validaciones.ci) {
          nuevosErrores.ci = "Ya existe un estudiante registrado con este CI.";
        }

        if (validaciones.codigo_universitario) {
          nuevosErrores.codigoUniversitario =
            "Ya existe un estudiante con este código universitario.";
        }

        if (validaciones.nombre) {
          nuevosErrores.nombre = validaciones.nombre[0];
        }

        if (validaciones.apellido) {
          nuevosErrores.apellido = validaciones.apellido[0];
        }

        if (validaciones.correo) {
          nuevosErrores.correo = validaciones.correo[0];
        }

        if (validaciones.estado) {
          nuevosErrores.estado = validaciones.estado[0];
        }
        if (validaciones.id_carrera) {
          nuevosErrores.idCarrera = validaciones.id_carrera[0];
        }

        if (validaciones.asignaturas) {
          nuevosErrores.asignaturas = validaciones.asignaturas[0];
        }

        if (validaciones["asignaturas.0"]) {
          nuevosErrores.asignaturas = validaciones["asignaturas.0"][0];
        }

        setErrores((actual) => ({
          ...actual,
          ...nuevosErrores,
        }));
      } else {
        setErrorGeneral(
          err.response?.data?.message || "No se pudo registrar el estudiante.",
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
        <div
          className="modal-dialog modal-lg modal-dialog-centered mx-auto"
          style={{
            maxHeight: "calc(100vh - 1rem)",
            width: "calc(100% - 1rem)",
          }}
        >
          <div
            className="modal-content border-0 shadow"
            style={{
              maxHeight: "calc(100vh - 1rem)",
            }}
          >
            {/* ENCABEZADO */}
            <div className="modal-header px-3 px-md-4 py-3 bg-primary text-white">
              <div className="pe-3">
                <h5 className="modal-title fw-bold">Registrar estudiante</h5>

                <p className="small mb-0 mt-1 text-white-50">
                  Complete los datos del estudiante que participará en los
                  exámenes.
                </p>
              </div>

              <button
                type="button"
                className="btn-close btn-close-white"
                onClick={onCerrar}
                disabled={guardando}
                aria-label="Cerrar"
              ></button>
            </div>

            <form
              onSubmit={handleSubmit}
              noValidate
              className="d-flex flex-column overflow-hidden"
            >
              <div
                className="modal-body px-3 px-md-4 py-4"
                style={{
                  overflowY: "auto",
                }}
              >
                {errorGeneral && (
                  <div
                    className="alert alert-danger d-flex align-items-center"
                    role="alert"
                  >
                    <i className="bi bi-exclamation-triangle-fill me-2"></i>
                    {errorGeneral}
                  </div>
                )}

                {/* AVISO DE CAMPOS OBLIGATORIOS */}
                <div className="d-flex align-items-center gap-2 text-muted small mb-4">
                  <i className="bi bi-info-circle text-primary"></i>

                  <span>
                    Los campos marcados con{" "}
                    <span className="text-danger fw-semibold">*</span> son
                    obligatorios.
                  </span>
                </div>

                {/* INFORMACIÓN PERSONAL */}
                <div className="mb-4">
                  <h6 className="fw-bold mb-1">Información personal</h6>

                  <p className="text-muted small mb-3">
                    Datos de identificación del estudiante.
                  </p>

                  <div className="row g-3">
                    {/* CI */}
                    <div className="col-12 col-md-6">
                      <label className="form-label fw-semibold">
                        CI <span className="text-danger">*</span>
                      </label>

                      <input
                        type="text"
                        name="ci"
                        maxLength="20"
                        className={`form-control ${
                          errores.ci ? "is-invalid" : ""
                        }`}
                        placeholder="Ej. 12345678"
                        value={formulario.ci}
                        onChange={handleChange}
                      />

                      {errores.ci && (
                        <div className="invalid-feedback">{errores.ci}</div>
                      )}
                    </div>

                    {/* CÓDIGO UNIVERSITARIO */}
                    <div className="col-12 col-md-6">
                      <label className="form-label fw-semibold">
                        Código universitario{" "}
                        <span className="text-danger">*</span>
                      </label>

                      <input
                        type="text"
                        name="codigoUniversitario"
                        maxLength="50"
                        className={`form-control ${
                          errores.codigoUniversitario ? "is-invalid" : ""
                        }`}
                        placeholder="Ej. 202012345"
                        value={formulario.codigoUniversitario}
                        onChange={handleChange}
                      />

                      {errores.codigoUniversitario && (
                        <div className="invalid-feedback">
                          {errores.codigoUniversitario}
                        </div>
                      )}
                    </div>

                    {/* NOMBRE */}
                    <div className="col-12 col-md-6">
                      <label className="form-label fw-semibold">
                        Nombre <span className="text-danger">*</span>
                      </label>

                      <input
                        type="text"
                        name="nombre"
                        maxLength="100"
                        className={`form-control ${
                          errores.nombre ? "is-invalid" : ""
                        }`}
                        placeholder="Ej. Juan"
                        value={formulario.nombre}
                        onChange={handleChange}
                      />

                      {errores.nombre && (
                        <div className="invalid-feedback">{errores.nombre}</div>
                      )}
                    </div>

                    {/* APELLIDO */}
                    <div className="col-12 col-md-6">
                      <label className="form-label fw-semibold">
                        Apellido <span className="text-danger">*</span>
                      </label>

                      <input
                        type="text"
                        name="apellido"
                        maxLength="100"
                        className={`form-control ${
                          errores.apellido ? "is-invalid" : ""
                        }`}
                        placeholder="Ej. Pérez"
                        value={formulario.apellido}
                        onChange={handleChange}
                      />

                      {errores.apellido && (
                        <div className="invalid-feedback">
                          {errores.apellido}
                        </div>
                      )}
                    </div>

                    {/* CORREO */}
                    <div className="col-12">
                      <label className="form-label fw-semibold">
                        Correo electrónico
                      </label>

                      <input
                        type="email"
                        name="correo"
                        maxLength="150"
                        className={`form-control ${
                          errores.correo ? "is-invalid" : ""
                        }`}
                        placeholder="estudiante@universidad.edu"
                        value={formulario.correo}
                        onChange={handleChange}
                      />

                      {errores.correo && (
                        <div className="invalid-feedback">{errores.correo}</div>
                      )}
                    </div>
                  </div>
                </div>

                <hr className="my-4" />

                {/* CONFIGURACIÓN DEL ESTUDIANTE */}
                <div className="mb-2">
                  <h6 className="fw-bold mb-1">Configuración del estudiante</h6>

                  <p className="text-muted small mb-3">
                    Rol y estado dentro del sistema.
                  </p>
                  {/* FACULTAD */}
                  <div className="col-12">
                    <label className="form-label fw-semibold">
                      Facultad <span className="text-danger">*</span>
                    </label>

                    <select
                      name="idFacultad"
                      className={`form-select ${
                        errores.idFacultad ? "is-invalid" : ""
                      }`}
                      value={formulario.idFacultad}
                      onChange={handleFacultadChange}
                      disabled={cargandoCatalogos}
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

                    {errores.idFacultad && (
                      <div className="invalid-feedback">
                        {errores.idFacultad}
                      </div>
                    )}
                  </div>
                  <div className="row g-3">
                    {/* CARRERA */}
                    <div className="col-12">
                      <label className="form-label fw-semibold">
                        Carrera <span className="text-danger">*</span>
                      </label>

                      <select
                        name="idCarrera"
                        className={`form-select ${
                          errores.idCarrera ? "is-invalid" : ""
                        }`}
                        value={formulario.idCarrera}
                        onChange={handleCarreraChange}
                        disabled={cargandoCatalogos || !formulario.idFacultad}
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

                      {errores.idCarrera && (
                        <div className="invalid-feedback">
                          {errores.idCarrera}
                        </div>
                      )}
                    </div>

                    {/* MATERIAS */}
                    <div className="col-12">
                      <label className="form-label fw-semibold">
                        Materias <span className="text-danger">*</span>
                      </label>

                      <div
                        className={`border rounded p-3 ${
                          errores.asignaturas ? "border-danger" : ""
                        }`}
                      >
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
                                    id={`asignatura-${asignatura.id_asignatura}`}
                                    value={asignatura.id_asignatura}
                                    checked={formulario.asignaturas.includes(
                                      Number(asignatura.id_asignatura),
                                    )}
                                    onChange={handleAsignaturaChange}
                                  />

                                  <label
                                    className="form-check-label"
                                    htmlFor={`asignatura-${asignatura.id_asignatura}`}
                                  >
                                    {asignatura.codigo} - {asignatura.nombre}
                                  </label>
                                </div>
                              </div>
                            ))}
                          </div>
                        )}
                      </div>

                      {errores.asignaturas && (
                        <div className="text-danger small mt-1">
                          {errores.asignaturas}
                        </div>
                      )}
                    </div>
                    {/* ROL */}
                    <div className="col-12 col-md-6">
                      <label className="form-label fw-semibold">Rol</label>

                      <input
                        type="text"
                        className="form-control bg-light"
                        value="ESTUDIANTE"
                        disabled
                        readOnly
                      />

                      <div className="form-text">
                        El rol se asigna automáticamente.
                      </div>
                    </div>

                    {/* ESTADO */}
                    <div className="col-12 col-md-6">
                      <label className="form-label fw-semibold">
                        Estado <span className="text-danger">*</span>
                      </label>

                      <select
                        name="estado"
                        className={`form-select ${
                          errores.estado ? "is-invalid" : ""
                        }`}
                        value={formulario.estado}
                        onChange={handleChange}
                      >
                        <option value="ACTIVO">Activo</option>
                        <option value="INACTIVO">Inactivo</option>
                      </select>

                      {errores.estado && (
                        <div className="invalid-feedback">{errores.estado}</div>
                      )}
                    </div>
                  </div>
                </div>
              </div>

              {/* FOOTER RESPONSIVE */}
              <div className="modal-footer px-3 px-md-4 py-3 bg-white">
                <div className="d-flex flex-column flex-sm-row justify-content-end gap-2 w-100">
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
                        <span className="spinner-border spinner-border-sm me-2"></span>
                        Registrando...
                      </>
                    ) : (
                      <>
                        <i className="bi bi-person-plus me-2"></i>
                        Registrar estudiante
                      </>
                    )}
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>

      <div className="modal-backdrop fade show"></div>
    </>
  );
}
