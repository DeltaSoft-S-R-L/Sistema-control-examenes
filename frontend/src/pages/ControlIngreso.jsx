
import React, { useState, useEffect, useRef } from 'react';
import api from '../services/api';
import StudentCard from '../components/estudiantes/StudentCard';
import MotivoInhabilitacionModal from '../components/habilitaciones/MotivoInhabilitacionModal';

export default function ControlIngreso() {
  const [examenes, setExamenes] = useState([]);
  const [examenSeleccionado, setExamenSeleccionado] = useState('');
  const [metodo, setMetodo] = useState('ci_sis');

  // Entradas
  const [identificador, setIdentificador] = useState('');
  const [tokenQr, setTokenQr] = useState('');

  // Estados de verificación
  const [loading, setLoading] = useState(false);
  const [errorAlerta, setErrorAlerta] = useState(null);
  const [resultado, setResultado] = useState(null);
  const [mostrarModalMotivo, setMostrarModalMotivo] = useState(false);

  // Estados de registro de ingreso
  const [asignacionSeleccionada, setAsignacionSeleccionada] = useState('');
  const [registrando, setRegistrando] = useState(false);
  const [ingresoRegistrado, setIngresoRegistrado] = useState(false);
  const [dobleIngreso, setDobleIngreso] = useState(null);

  // Cámara para QR en celular
  const [camaraActiva, setCamaraActiva] = useState(false);
  const [errorCamara, setErrorCamara] = useState(null);

  const videoRef = useRef(null);
  const streamRef = useRef(null);
  const inputIdentificadorRef = useRef(null);

  // Examen y ambientes seleccionados
  const examenActual = examenes.find(
    (ex) => String(ex.id_examen) === String(examenSeleccionado)
  );

  const asignaciones = examenActual?.asignaciones_ambiente || [];

  // Cargar exámenes
  useEffect(() => {
    cargarExamenes();
  }, []);

  const cargarExamenes = async () => {
    try {
      const res = await api.get('/examenes');
      const lista = res.data.data || res.data || [];

      setExamenes(lista);

      if (lista.length > 0) {
        setExamenSeleccionado(String(lista[0].id_examen));
      }
    } catch (err) {
      console.error('Error al cargar exámenes:', err);
      setErrorAlerta('No se pudieron cargar los exámenes programados.');
    }
  };

  // Seleccionar el primer ambiente asignado al examen
  useEffect(() => {
    const examen = examenes.find(
      (ex) => String(ex.id_examen) === String(examenSeleccionado)
    );

    const asignacionesExamen = examen?.asignaciones_ambiente || [];

    setAsignacionSeleccionada(
      asignacionesExamen.length > 0
        ? String(asignacionesExamen[0].id_asignacion_ambiente)
        : ''
    );
  }, [examenSeleccionado, examenes]);

  // Iniciar cámara
  const iniciarCamara = async () => {
    setErrorCamara(null);

    try {
      if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        throw new Error('Navegador no soporta acceso a la cámara.');
      }

      const stream = await navigator.mediaDevices.getUserMedia({
        video: { facingMode: 'environment' },
      });

      streamRef.current = stream;

      if (videoRef.current) {
        videoRef.current.srcObject = stream;
        await videoRef.current.play();
      }

      setCamaraActiva(true);
    } catch (err) {
      console.error('Error al acceder a la cámara:', err);

      if (
        err.name === 'NotAllowedError' ||
        err.name === 'PermissionDeniedError'
      ) {
        setErrorCamara(
          'Acceso a la cámara denegado. Permita el uso de la cámara en su navegador o utilice verificación por CI/SIS.'
        );
      } else {
        setErrorCamara(
          'No se pudo inicializar la cámara. Verifique que no esté en uso por otra app.'
        );
      }

      setCamaraActiva(false);
    }
  };

  // Detener cámara
  const detenerCamara = () => {
    if (streamRef.current) {
      streamRef.current.getTracks().forEach((track) => track.stop());
      streamRef.current = null;
    }

    setCamaraActiva(false);
  };

  // Liberar cámara al desmontar el componente
  useEffect(() => {
    return () => {
      if (streamRef.current) {
        streamRef.current.getTracks().forEach((track) => track.stop());
        streamRef.current = null;
      }
    };
  }, []);

  // Verificar estudiante por CI/SIS
  const handleVerificarDocumento = async (e) => {
    if (e) e.preventDefault();

    if (!identificador.trim()) {
      setErrorAlerta('Por favor ingrese el número de CI o Código SIS.');
      return;
    }

    if (!examenSeleccionado) {
      setErrorAlerta('Seleccione una sesión de examen activa.');
      return;
    }

    setLoading(true);
    setErrorAlerta(null);
    setResultado(null);
    setDobleIngreso(null);
    setIngresoRegistrado(false);

    try {
      const res = await api.post('/verificar/documento', {
        identificador: identificador.trim(),
        id_examen: examenSeleccionado,
      });

      setResultado(res.data);
    } catch (err) {
      console.error('Error al verificar documento:', err);

      const msg =
        err.response?.data?.message ||
        'Estudiante no encontrado en el sistema.';

      setErrorAlerta(msg);
    } finally {
      setLoading(false);
    }
  };

  // Verificar estudiante por QR
  const handleVerificarQr = async (tokenParaEnviar) => {
    const token = tokenParaEnviar || tokenQr;

    if (!token.trim()) {
      setErrorAlerta('Ingrese o escanee un código QR válido.');
      return;
    }

    if (!examenSeleccionado) {
      setErrorAlerta('Seleccione una sesión de examen activa.');
      return;
    }

    setLoading(true);
    setErrorAlerta(null);
    setResultado(null);
    setDobleIngreso(null);
    setIngresoRegistrado(false);

    try {
      const res = await api.post('/verificar/qr', {
        token: token.trim(),
        id_examen: examenSeleccionado,
      });

      setResultado(res.data);
      detenerCamara();
    } catch (err) {
      console.error('Error al verificar QR:', err);

      const msg =
        err.response?.data?.message ||
        'Código QR no reconocido o estudiante no encontrado.';

      setErrorAlerta(msg);
    } finally {
      setLoading(false);
    }
  };

  // Registrar ingreso del estudiante habilitado
  const handleRegistrarIngreso = async () => {
    const habilitacion = resultado?.habilitacion;

    // Solo se permite registrar estudiantes habilitados
    if (habilitacion?.estado !== 'HABILITADO') {
      return;
    }

    if (!habilitacion.id_habilitacion) {
      setErrorAlerta('No se encontró la habilitación del estudiante.');
      return;
    }

    if (!asignacionSeleccionada) {
      setErrorAlerta('Seleccione un ambiente asignado al examen.');
      return;
    }

    // Evitar solicitudes duplicadas desde el botón
    if (registrando || ingresoRegistrado || dobleIngreso) {
      return;
    }

    setRegistrando(true);
    setErrorAlerta(null);

    try {
      await api.post('/ingresos', {
        id_examen: examenSeleccionado,
        id_habilitacion: habilitacion.id_habilitacion,
        id_asignacion_ambiente: asignacionSeleccionada,
      });

      setIngresoRegistrado(true);
    } catch (err) {
      console.error('Error al registrar ingreso:', err);

      const status = err.response?.status;
      const data = err.response?.data || {};

      // El backend actual utiliza 422 + INGRESO_DUPLICADO.
      // También se contempla el 409 solicitado en la tarea.
      const esDobleIngreso =
        status === 409 ||
        (status === 422 && data.code === 'INGRESO_DUPLICADO');

      if (esDobleIngreso) {
        let fechaHora =
          data.fecha_hora ||
          data.ingreso?.fecha_hora ||
          data.primer_ingreso?.fecha_hora ||
          null;

        // Consultar el primer ingreso si el backend devuelve su identificador
        if (!fechaHora && data.id_ingreso) {
          try {
            const respuesta = await api.get(
              `/ingresos/${data.id_ingreso}`
            );

            const ingreso =
              respuesta.data?.data ||
              respuesta.data?.ingreso ||
              respuesta.data;

            fechaHora = ingreso?.fecha_hora || null;
          } catch (consultaError) {
            console.warn(
              'No se pudo recuperar la hora del primer ingreso:',
              consultaError
            );
          }
        }

        setDobleIngreso({
          mensaje:
            data.message ||
            'El estudiante ya tiene un ingreso registrado.',
          fechaHora,
        });
      } else {
        setErrorAlerta(
          data.message || 'No se pudo registrar el ingreso.'
        );
      }
    } finally {
      setRegistrando(false);
    }
  };

  // Continuar con el siguiente estudiante
  const handleSiguienteEstudiante = () => {
    setResultado(null);
    setErrorAlerta(null);
    setIdentificador('');
    setTokenQr('');
    setMostrarModalMotivo(false);
    setDobleIngreso(null);
    setIngresoRegistrado(false);

    if (metodo === 'ci_sis') {
      setTimeout(() => {
        inputIdentificadorRef.current?.focus();
      }, 100);
    }
  };

  // Continuar después de detectar doble ingreso
  const handleContinuarEscaneando = () => {
    setDobleIngreso(null);
    setIngresoRegistrado(false);
    setResultado(null);
    setErrorAlerta(null);
    setIdentificador('');
    setTokenQr('');

    if (metodo === 'qr') {
      // Esperar a que vuelva a mostrarse el vídeo antes de activar cámara
      setTimeout(() => {
        iniciarCamara();
      }, 200);
    } else {
      setTimeout(() => {
        inputIdentificadorRef.current?.focus();
      }, 100);
    }
  };

  return (
    <div
      className="container-fluid px-2 py-3 px-md-4"
      style={{ maxWidth: '650px', margin: '0 auto' }}
    >
      {/* Título de control móvil */}
      <div className="text-center mb-3">
        <h3 className="fw-bold text-primary mb-1">
          <i className="bi bi-shield-check me-2"></i>
          Control de Ingreso
        </h3>

        <p className="text-muted small mb-0">
          Verificación de credenciales en tiempo real
        </p>
      </div>

      {/* Selector de examen y ambiente */}
      <div className="card border-0 shadow-sm rounded-4 mb-3">
        <div className="card-body p-3">
          <label className="form-label small fw-bold text-uppercase text-muted mb-1">
            <i className="bi bi-journal-text me-1 text-primary"></i>
            Sesión de Examen Activa
          </label>

          <select
            className="form-select fw-semibold"
            value={examenSeleccionado}
            onChange={(e) => {
              setExamenSeleccionado(e.target.value);
              setResultado(null);
              setDobleIngreso(null);
              setIngresoRegistrado(false);
              setErrorAlerta(null);
            }}
            disabled={loading || registrando}
          >
            {examenes.length === 0 ? (
              <option value="">No hay exámenes disponibles</option>
            ) : (
              examenes.map((ex) => (
                <option key={ex.id_examen} value={ex.id_examen}>
                  {ex.nombre} (
                  {ex.fecha ? String(ex.fecha).substring(0, 10) : ''} -{' '}
                  {ex.hora_inicio})
                </option>
              ))
            )}
          </select>

          <div className="mt-3">
            <label className="form-label small fw-bold text-uppercase text-muted">
              Ambiente asignado
            </label>

            <select
              className="form-select"
              value={asignacionSeleccionada}
              onChange={(e) => setAsignacionSeleccionada(e.target.value)}
              disabled={
                loading ||
                registrando ||
                asignaciones.length === 0 ||
                Boolean(resultado)
              }
            >
              {asignaciones.length === 0 ? (
                <option value="">No hay ambientes asignados</option>
              ) : (
                asignaciones.map((asignacion) => (
                  <option
                    key={asignacion.id_asignacion_ambiente}
                    value={asignacion.id_asignacion_ambiente}
                  >
                    {asignacion.ambiente?.nombre || 'Ambiente sin nombre'}
                  </option>
                ))
              )}
            </select>
          </div>
        </div>
      </div>

      {/* Alerta de error general */}
      {errorAlerta && (
        <div
          className="alert alert-danger shadow-sm rounded-3 py-2 px-3 d-flex align-items-center mb-3"
          role="alert"
        >
          <i className="bi bi-exclamation-triangle-fill fs-5 me-2"></i>

          <div className="small fw-semibold flex-fill">
            {errorAlerta}
          </div>

          <button
            type="button"
            className="btn-close btn-sm"
            onClick={() => setErrorAlerta(null)}
            aria-label="Cerrar alerta"
          ></button>
        </div>
      )}

      {/* Formulario de verificación */}
      {!resultado ? (
        <div className="card border-0 shadow-sm rounded-4 overflow-hidden mb-3">
          {/* Pestañas de selección de método */}
          <div
            className="btn-group w-100 p-2 bg-light border-bottom"
            role="group"
          >
            <button
              type="button"
              className={`btn py-2 fw-bold ${
                metodo === 'ci_sis'
                  ? 'btn-primary'
                  : 'btn-outline-secondary'
              }`}
              onClick={() => {
                setMetodo('ci_sis');
                detenerCamara();
              }}
              disabled={loading || registrando}
            >
              <i className="bi bi-person-badge me-2"></i>
              C.I. / SIS
            </button>

            <button
              type="button"
              className={`btn py-2 fw-bold ${
                metodo === 'qr'
                  ? 'btn-primary'
                  : 'btn-outline-secondary'
              }`}
              onClick={() => setMetodo('qr')}
              disabled={loading || registrando}
            >
              <i className="bi bi-qr-code-scan me-2"></i>
              Escanear QR
            </button>
          </div>

          <div className="card-body p-4">
            {metodo === 'ci_sis' ? (
              /* Modo CI / SIS */
              <form onSubmit={handleVerificarDocumento}>
                <div className="mb-4 text-center">
                  <label className="form-label fw-bold text-dark fs-5 mb-2">
                    Ingrese C.I. o Código SIS
                  </label>

                  <input
                    ref={inputIdentificadorRef}
                    type="text"
                    className="form-control form-control-lg text-center fs-4 fw-bold border-2"
                    placeholder="Ej. 1234567 o 202000001"
                    value={identificador}
                    onChange={(e) => setIdentificador(e.target.value)}
                    autoFocus
                    inputMode="numeric"
                    disabled={loading}
                  />

                  <small className="text-muted d-block mt-2">
                    Admite búsqueda por carnet de identidad o código
                    universitario SIS.
                  </small>
                </div>

                <button
                  type="submit"
                  className="btn btn-primary btn-lg w-100 py-3 fw-bold shadow-sm"
                  disabled={loading || !examenSeleccionado}
                >
                  {loading ? (
                    <>
                      <span
                        className="spinner-border spinner-border-sm me-2"
                        role="status"
                      ></span>
                      Verificando...
                    </>
                  ) : (
                    <>
                      <i className="bi bi-search me-2"></i>
                      Verificar Estudiante
                    </>
                  )}
                </button>
              </form>
            ) : (
              /* Modo QR */
              <div>
                {errorCamara && (
                  <div className="alert alert-warning py-2 small mb-3">
                    <i className="bi bi-camera-video-off me-2"></i>
                    {errorCamara}
                  </div>
                )}

                {!camaraActiva ? (
                  <div className="text-center py-4">
                    <div className="mb-3">
                      <i className="bi bi-qr-code fs-1 text-primary opacity-75"></i>
                    </div>

                    <button
                      type="button"
                      className="btn btn-primary btn-lg w-100 py-3 fw-bold mb-3 shadow-sm"
                      onClick={iniciarCamara}
                      disabled={loading}
                    >
                      <i className="bi bi-camera-fill me-2"></i>
                      Activar Cámara del Celular
                    </button>

                    <p className="small text-muted mb-0">
                      O pegue/ingrese el token QR a continuación:
                    </p>
                  </div>
                ) : (
                  <div className="text-center mb-3">
                    <div className="ratio ratio-4x3 bg-dark rounded-3 overflow-hidden position-relative mb-2">
                      <video
                        ref={videoRef}
                        className="w-100 h-100 object-fit-cover"
                        playsInline
                        muted
                      ></video>

                      <div
                        className="position-absolute top-50 start-50 translate-middle border border-2 border-success rounded-3"
                        style={{
                          width: '220px',
                          height: '220px',
                          boxShadow:
                            '0 0 0 9999px rgba(0, 0, 0, 0.4)',
                        }}
                      ></div>
                    </div>

                    <button
                      type="button"
                      className="btn btn-outline-danger btn-sm w-100 fw-semibold"
                      onClick={detenerCamara}
                    >
                      <i className="bi bi-stop-circle me-1"></i>
                      Detener Cámara
                    </button>
                  </div>
                )}

                {/* Entrada manual de token QR */}
                <div className="mt-3 pt-3 border-top">
                  <label className="form-label small fw-semibold text-muted">
                    Token / Código QR:
                  </label>

                  <div className="input-group">
                    <input
                      type="text"
                      className="form-control"
                      placeholder="Pegar token QR o escanear con lector..."
                      value={tokenQr}
                      onChange={(e) => setTokenQr(e.target.value)}
                      disabled={loading}
                    />

                    <button
                      className="btn btn-outline-primary fw-semibold"
                      type="button"
                      onClick={() => handleVerificarQr()}
                      disabled={loading || !examenSeleccionado}
                    >
                      Verificar
                    </button>
                  </div>
                </div>
              </div>
            )}
          </div>
        </div>
      ) : (
        /* Resultado de verificación */
        <div>
          <StudentCard
            estudiante={resultado.estudiante}
            habilitacion={resultado.habilitacion}
            onSiguiente={handleSiguienteEstudiante}
            onVerMotivo={() => setMostrarModalMotivo(true)}
          />

          {/* Registro y control de doble ingreso */}
          {resultado.habilitacion?.estado === 'HABILITADO' && (
            <div className="mt-3">
              {dobleIngreso ? (
                <div
                  className="alert alert-danger rounded-4 shadow-sm"
                  role="alert"
                >
                  <h5 className="fw-bold">
                    <i className="bi bi-exclamation-octagon-fill me-2"></i>
                    DOBLE INGRESO DETECTADO
                  </h5>

                  <p className="mb-2">{dobleIngreso.mensaje}</p>

                  {dobleIngreso.fechaHora && (
                    <p className="fw-bold mb-3">
                      Primer ingreso registrado:{' '}
                      {new Date(
                        dobleIngreso.fechaHora
                      ).toLocaleString()}
                    </p>
                  )}

                  <button
                    type="button"
                    className="btn btn-danger w-100 fw-bold"
                    onClick={handleContinuarEscaneando}
                  >
                    <i className="bi bi-arrow-repeat me-2"></i>
                    Aceptar / Continuar escaneando
                  </button>
                </div>
              ) : ingresoRegistrado ? (
                <div
                  className="alert alert-success rounded-4 fw-bold"
                  role="alert"
                >
                  <i className="bi bi-check-circle-fill me-2"></i>
                  Ingreso registrado correctamente.
                </div>
              ) : (
                <button
                  type="button"
                  className="btn btn-success btn-lg w-100 fw-bold shadow-sm"
                  onClick={handleRegistrarIngreso}
                  disabled={
                    registrando ||
                    !asignacionSeleccionada ||
                    ingresoRegistrado
                  }
                >
                  {registrando ? (
                    <>
                      <span className="spinner-border spinner-border-sm me-2"></span>
                      Registrando ingreso...
                    </>
                  ) : (
                    <>
                      <i className="bi bi-check-circle-fill me-2"></i>
                      Confirmar ingreso
                    </>
                  )}
                </button>
              )}
            </div>
          )}

          {/* Modal de motivo de inhabilitación */}
          <MotivoInhabilitacionModal
            isOpen={mostrarModalMotivo}
            onClose={() => setMostrarModalMotivo(false)}
            motivo={resultado.habilitacion?.motivo}
            estado={resultado.habilitacion?.estado}
            estudiante={resultado.estudiante}
          />
        </div>
      )}
    </div>
  );
}
