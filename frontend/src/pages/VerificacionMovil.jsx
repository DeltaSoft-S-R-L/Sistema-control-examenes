import React, { useEffect, useState, useRef, useCallback } from "react";
import api from "../services/api";
import QrScanner from "../components/qr/QrScanner";
import BannerErrorVerificacion from "../components/verificacion/BannerErrorVerificacion";
import { formatearErrorVerificacion } from "../utils/verificacionErrores";

export default function VerificacionMovil() {
  // Lista y selección de exámenes
  const [examenes, setExamenes] = useState([]);
  const [seleccionado, setSeleccionado] = useState(null);
  const [cargando, setCargando] = useState(true);
  const [cargandoMas, setCargandoMas] = useState(false);
  const [pagina, setPagina] = useState(1);
  const [ultimaPagina, setUltimaPagina] = useState(1);
  const [errorCarga, setErrorCarga] = useState("");

  // Modo de verificación: 'qr' o 'cisis' (teclado de contingencia)
  const [modo, setModo] = useState("qr");
  const [identificador, setIdentificador] = useState("");
  const [verificando, setVerificando] = useState(false);

  // Estados de retroalimentación
  const [resultado, setResultado] = useState(null);
  const [errorVerificacion, setErrorVerificacion] = useState(null);

  // Clave para reiniciar el escáner QR tras error sin recargar la página
  const [scannerKey, setScannerKey] = useState(0);

  // Referencias para accesibilidad y foco
  const inputIdentificadorRef = useRef(null);
  const ultimoTokenEscaneadoRef = useRef("");

  const cargarExamenes = useCallback(async (numeroPagina = 1) => {
    try {
      if (numeroPagina === 1) {
        setCargando(true);
      } else {
        setCargandoMas(true);
      }

      setErrorCarga("");

      const respuesta = await api.get("/examenes", {
        params: { page: numeroPagina },
      });

      const datos = respuesta.data;
      const lista = Array.isArray(datos.data)
        ? datos.data
        : Array.isArray(datos)
          ? datos
          : [];

      setExamenes((anteriores) =>
        numeroPagina === 1 ? lista : [...anteriores, ...lista],
      );

      setPagina(numeroPagina);
      setUltimaPagina(datos.last_page ?? 1);
    } catch (err) {
      setErrorCarga(
        err.response?.data?.message || "No se pudieron cargar los exámenes programados.",
      );
    } finally {
      setCargando(false);
      setCargandoMas(false);
    }
  }, []);

  useEffect(() => {
    cargarExamenes(1);
  }, [cargarExamenes]);

  const formatearFecha = (fecha) => {
    if (!fecha) return "Sin fecha";
    return String(fecha).slice(0, 10);
  };

  // Limpia cualquier resultado o error y prepara la siguiente verificación sin recargar la página
  const reiniciarParaSiguienteEstudiante = () => {
    setResultado(null);
    setErrorVerificacion(null);
    setIdentificador("");
    ultimoTokenEscaneadoRef.current = "";
    setScannerKey((prev) => prev + 1);

    if (modo === "cisis") {
      setTimeout(() => {
        inputIdentificadorRef.current?.focus();
      }, 50);
    }
  };

  // 1. Verificación por escaneo QR
  const handleScanQr = async (codigoQr) => {
    if (!codigoQr || verificando) return;

    // Descartar banner de error anterior al iniciar una nueva verificación
    setErrorVerificacion(null);
    setResultado(null);
    setVerificando(true);
    ultimoTokenEscaneadoRef.current = codigoQr;

    try {
      const res = await api.post("/verificar/qr", {
        qr_token: codigoQr,
        token: codigoQr,
        id_examen: seleccionado.id_examen,
      });

      setResultado(res.data);
    } catch (err) {
      const errorFormateado = formatearErrorVerificacion(err);
      setErrorVerificacion(errorFormateado);

      // Si el error fue 401, el interceptor de api.js redirige al login
      if (errorFormateado.codigo === "401") {
        return;
      }

      // Reiniciar escáner para permitir escanear de nuevo sin recargar la página
      setScannerKey((prev) => prev + 1);
    } finally {
      setVerificando(false);
    }
  };

  // 2. Error originado por la cámara del dispositivo
  const handleCameraError = (err) => {
    const errorFormateado = formatearErrorVerificacion(err);
    setErrorVerificacion(errorFormateado);
  };

  // 3. Verificación por CI / Código SIS (teclado de contingencia)
  const handleVerificarDocumento = async (e) => {
    if (e) e.preventDefault();

    const valorLimpio = identificador.trim();
    if (!valorLimpio) {
      setErrorVerificacion({
        codigo: "CAMPO_VACIO",
        mensaje: "Por favor ingrese el número de C.I. o Código SIS.",
        detalle: "El campo no puede quedar en blanco para realizar la búsqueda.",
        esCamara: false,
        esTimeoutORed: false,
        ofrecerCisIs: false,
        ofrecerReintento: false,
      });
      return;
    }

    // Desaparece el banner rojo al iniciar una nueva verificación
    setErrorVerificacion(null);
    setResultado(null);
    setVerificando(true);

    try {
      const res = await api.post("/verificar/documento", {
        identificador: valorLimpio,
        id_examen: seleccionado.id_examen,
      });

      setResultado(res.data);
    } catch (err) {
      const errorFormateado = formatearErrorVerificacion(err);
      setErrorVerificacion(errorFormateado);

      // Mantener el campo accesible para corrección inmediata sin recargar
      setTimeout(() => {
        inputIdentificadorRef.current?.focus();
        inputIdentificadorRef.current?.select();
      }, 50);
    } finally {
      setVerificando(false);
    }
  };

  // Reintento en caso de error de conexión/timeout
  const handleReintentar = () => {
    if (modo === "qr" && ultimoTokenEscaneadoRef.current) {
      handleScanQr(ultimoTokenEscaneadoRef.current);
    } else if (modo === "cisis" && identificador.trim()) {
      handleVerificarDocumento();
    } else {
      reiniciarParaSiguienteEstudiante();
    }
  };

  // Cambiar a pestaña de contingencia C.I. / SIS
  const handleCambiarACisIs = () => {
    setModo("cisis");
    setErrorVerificacion(null);
    setTimeout(() => {
      inputIdentificadorRef.current?.focus();
    }, 100);
  };

  // VISTA 1: PANTALLA DE CONTROL / VERIFICACIÓN DEL EXAMEN SELECCIONADO
  if (seleccionado) {
    const habilitado = resultado?.habilitacion?.habilitado;
    const estadoHabilitacion = resultado?.habilitacion?.estado;
    const motivo = resultado?.habilitacion?.motivo;

    return (
      <div className="container-fluid px-2 py-3 px-md-3">
        <div className="mx-auto" style={{ maxWidth: 650 }}>
          {/* Botón de regreso */}
          <button
            type="button"
            className="btn btn-outline-secondary btn-sm mb-3"
            onClick={() => {
              setSeleccionado(null);
              setResultado(null);
              setErrorVerificacion(null);
            }}
          >
            <i className="bi bi-arrow-left me-2" />
            Cambiar de examen
          </button>

          {/* Encabezado del examen activo */}
          <div className="card shadow-sm border-0 rounded-3 mb-3 bg-light">
            <div className="card-body p-3">
              <span className="badge bg-primary mb-1">Sesión Activa</span>
              <h2 className="h5 fw-bold mb-1 text-dark text-break">
                {seleccionado.nombre}
              </h2>
              <p className="small text-muted mb-0">
                <i className="bi bi-calendar3 me-1" />
                {formatearFecha(seleccionado.fecha)}
                {seleccionado.hora_inicio && (
                  <span className="ms-2">
                    <i className="bi bi-clock me-1" />
                    {seleccionado.hora_inicio}
                  </span>
                )}
              </p>
            </div>
          </div>

          {/* Banner rojo de error (con ícono, texto, vibración y recuperación) */}
          <BannerErrorVerificacion
            errorInfo={errorVerificacion}
            onCerrar={() => setErrorVerificacion(null)}
            onReintentar={handleReintentar}
            onCambiarMetodoCisIs={handleCambiarACisIs}
          />

          {/* ESTADO CON RESULTADO DE VERIFICACIÓN */}
          {resultado ? (
            <div className="card shadow border-0 rounded-4 overflow-hidden mb-3">
              <div
                className={`card-header text-white text-center py-4 ${
                  habilitado ? "bg-success" : "bg-danger"
                }`}
              >
                <i
                  className={`bi ${
                    habilitado ? "bi-check-circle-fill" : "bi-x-circle-fill"
                  } display-3 d-block mb-2`}
                />
                <h3 className="h4 fw-bold mb-0">
                  {habilitado ? "ESTUDIANTE HABILITADO" : "ACCESO DENEGADO"}
                </h3>
                <span className="badge bg-white text-dark mt-2 px-3 py-2 fs-6">
                  Estado: {estadoHabilitacion}
                </span>
              </div>

              <div className="card-body p-4">
                {/* Datos del estudiante identificado */}
                <div className="mb-3">
                  <h4 className="h6 text-uppercase text-muted fw-bold mb-3">
                    Datos del Estudiante
                  </h4>
                  <div className="p-3 bg-light rounded-3">
                    <p className="mb-1 fs-5 fw-bold text-dark">
                      {resultado.estudiante?.nombre}{" "}
                      {resultado.estudiante?.apellido}
                    </p>
                    <p className="mb-1 text-muted">
                      <strong>C.I.:</strong> {resultado.estudiante?.ci}
                    </p>
                    <p className="mb-1 text-muted">
                      <strong>Código SIS:</strong>{" "}
                      {resultado.estudiante?.codigo_universitario}
                    </p>
                    {resultado.estudiante?.carrera && (
                      <p className="mb-0 text-muted">
                        <strong>Carrera:</strong> {resultado.estudiante?.carrera}
                      </p>
                    )}
                  </div>
                </div>

                {/* Motivo de rechazo cuando no está habilitado */}
                {!habilitado && (
                  <div className="alert alert-warning border-warning d-flex align-items-start mb-3">
                    <i className="bi bi-info-circle-fill fs-4 text-warning me-2" />
                    <div>
                      <strong className="d-block">Motivo de no habilitación:</strong>
                      <span>
                        {motivo ||
                          (estadoHabilitacion === "NO_ASOCIADO"
                            ? "El estudiante no está registrado en este examen."
                            : "Inhabilitado para rendir este examen.")}
                      </span>
                    </div>
                  </div>
                )}

                {/* Botón para continuar sin recargar */}
                <button
                  type="button"
                  className="btn btn-primary btn-lg w-100 py-3 fw-bold shadow-sm"
                  onClick={reiniciarParaSiguienteEstudiante}
                >
                  <i className="bi bi-arrow-repeat me-2" />
                  Verificar siguiente estudiante
                </button>
              </div>
            </div>
          ) : (
            /* SELECTOR DE MÉTODO Y FORMULARIO (QR o CI/SIS) */
            <div className="card shadow-sm border-0 rounded-4 overflow-hidden mb-3">
              {/* Pestañas de modo */}
              <div className="btn-group w-100 p-2 bg-light border-bottom" role="group">
                <button
                  type="button"
                  className={`btn py-2 fw-bold ${
                    modo === "qr" ? "btn-primary shadow-sm" : "btn-outline-secondary"
                  }`}
                  onClick={() => {
                    setModo("qr");
                    setErrorVerificacion(null);
                  }}
                  disabled={verificando}
                >
                  <i className="bi bi-qr-code-scan me-2" />
                  Escanear QR
                </button>
                <button
                  type="button"
                  className={`btn py-2 fw-bold ${
                    modo === "cisis" ? "btn-primary shadow-sm" : "btn-outline-secondary"
                  }`}
                  onClick={() => {
                    setModo("cisis");
                    setErrorVerificacion(null);
                    setTimeout(() => inputIdentificadorRef.current?.focus(), 50);
                  }}
                  disabled={verificando}
                >
                  <i className="bi bi-person-badge me-2" />
                  C.I. / SIS (Contingencia)
                </button>
              </div>

              <div className="card-body p-3 p-md-4">
                {modo === "qr" ? (
                  /* MODO ESCÁNER QR */
                  <div>
                    {verificando && (
                      <div className="alert alert-info d-flex align-items-center mb-3">
                        <div
                          className="spinner-border spinner-border-sm me-2 text-primary"
                          role="status"
                        />
                        <span>Verificando credencial QR con el servidor...</span>
                      </div>
                    )}

                    <QrScanner
                      key={`${seleccionado.id_examen}-${scannerKey}`}
                      onResult={handleScanQr}
                      onError={handleCameraError}
                    />
                  </div>
                ) : (
                  /* MODO C.I. / CÓDIGO SIS (Teclado de Contingencia) */
                  <form onSubmit={handleVerificarDocumento}>
                    <div className="mb-4 text-center">
                      <label
                        htmlFor="input-identificador-contingencia"
                        className="form-label fw-bold text-dark fs-5 mb-2"
                      >
                        Número de C.I. o Código SIS
                      </label>
                      <input
                        id="input-identificador-contingencia"
                        ref={inputIdentificadorRef}
                        type="text"
                        className="form-control form-control-lg text-center fs-4 fw-bold border-2"
                        placeholder="Ej. 1234567 o 202000001"
                        value={identificador}
                        onChange={(e) => setIdentificador(e.target.value)}
                        autoFocus
                        inputMode="numeric"
                        disabled={verificando}
                        autoComplete="off"
                      />
                      <small className="text-muted d-block mt-2">
                        Ingrese el documento sin espacios ni puntos. Búsqueda insensible a mayúsculas.
                      </small>
                    </div>

                    <button
                      type="submit"
                      className="btn btn-primary btn-lg w-100 fw-bold py-3 shadow-sm"
                      disabled={verificando || !identificador.trim()}
                    >
                      {verificando ? (
                        <>
                          <span
                            className="spinner-border spinner-border-sm me-2"
                            role="status"
                            aria-hidden="true"
                          />
                          Comprobando habilitación...
                        </>
                      ) : (
                        <>
                          <i className="bi bi-search me-2" />
                          Verificar Estudiante
                        </>
                      )}
                    </button>
                  </form>
                )}
              </div>
            </div>
          )}
        </div>
      </div>
    );
  }

  // VISTA 2: LISTADO Y SELECCIÓN DE EXAMEN
  return (
    <div className="container-fluid px-0">
      <div className="mx-auto" style={{ maxWidth: 650 }}>
        <header className="mb-4">
          <h1 className="h3 fw-bold">Control de ingreso móvil</h1>
          <p className="text-muted">
            Selecciona la sesión de examen activa para verificar el ingreso de estudiantes.
          </p>
        </header>

        {errorCarga && (
          <div className="alert alert-danger" role="alert">
            <i className="bi bi-exclamation-triangle-fill me-2" />
            {errorCarga}
            <button
              type="button"
              className="btn btn-outline-danger btn-sm d-block mt-2"
              onClick={() => cargarExamenes(1)}
            >
              <i className="bi bi-arrow-clockwise me-1" />
              Reintentar carga
            </button>
          </div>
        )}

        {cargando ? (
          <div className="text-center py-5" role="status">
            <div className="spinner-border text-primary mb-2" />
            <p className="text-muted">Cargando exámenes programados...</p>
          </div>
        ) : (
          <>
            {examenes.length === 0 && !errorCarga && (
              <div className="alert alert-info">
                No hay exámenes disponibles en este momento.
              </div>
            )}

            <div className="d-grid gap-3">
              {examenes.map((examen) => {
                const activo = seleccionado?.id_examen === examen.id_examen;

                return (
                  <button
                    key={examen.id_examen}
                    type="button"
                    className={`card text-start shadow-sm w-100 ${
                      activo ? "border-primary border-2" : ""
                    }`}
                    onClick={() => {
                      setSeleccionado(examen);
                      setResultado(null);
                      setErrorVerificacion(null);
                    }}
                    aria-pressed={activo}
                  >
                    <div className="card-body">
                      <div className="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                        <h2 className="h6 fw-bold mb-0 text-break">
                          {examen.nombre}
                        </h2>

                        <span className="badge bg-secondary">
                          {examen.estado}
                        </span>
                      </div>

                      <p className="mb-1 small">
                        <strong>Asignatura:</strong>{" "}
                        {examen.asignatura?.nombre ?? "No especificada"}
                      </p>

                      <p className="mb-1 small">
                        <strong>Fecha:</strong> {formatearFecha(examen.fecha)}
                      </p>

                      <p className="mb-0 small">
                        <strong>Hora:</strong>{" "}
                        {examen.hora_inicio ?? "No especificada"}
                      </p>

                      {activo && (
                        <div className="text-primary fw-semibold mt-3">
                          <i className="bi bi-check-circle-fill me-2" />
                          Examen seleccionado
                        </div>
                      )}
                    </div>
                  </button>
                );
              })}
            </div>

            {pagina < ultimaPagina && (
              <button
                type="button"
                className="btn btn-outline-primary w-100 mt-3"
                disabled={cargandoMas}
                onClick={() => cargarExamenes(pagina + 1)}
              >
                {cargandoMas ? "Cargando..." : "Cargar más exámenes"}
              </button>
            )}
          </>
        )}
      </div>
    </div>
  );
}
