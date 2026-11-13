import React, { useState, useEffect, useRef } from 'react';
import api from '../services/api';
import StudentCard from '../components/estudiantes/StudentCard';
import MotivoInhabilitacionModal from '../components/habilitaciones/MotivoInhabilitacionModal';
import ModalAmbienteIncorrecto from '../components/habilitaciones/ModalAmbienteIncorrecto';
import { getUsuario } from '../utils/auth';

export default function ControlIngreso() {
  const [examenes, setExamenes] = useState([]);
  const [examenSeleccionado, setExamenSeleccionado] = useState('');
  const [metodo, setMetodo] = useState('ci_sis'); // 'ci_sis' o 'qr'
  const [tipoDocumento, setTipoDocumento] = useState('CI');

  // Entradas
  const [identificador, setIdentificador] = useState('');
  const [tokenQr, setTokenQr] = useState('');

  // Estados de verificación
  const [loading, setLoading] = useState(false);
  const [errorAlerta, setErrorAlerta] = useState(null);
  const [resultado, setResultado] = useState(null);
  const [mostrarModalMotivo, setMostrarModalMotivo] = useState(false);
  const [confirmandoIngreso, setConfirmandoIngreso] = useState(false);
  const [ingresoConfirmado, setIngresoConfirmado] = useState(false);

  // HU-180: Estados del Modal Ámbar
  const [mostrarModalAmbiente, setMostrarModalAmbiente] = useState(false);
  const [ambienteAsignado, setAmbienteAsignado] = useState(null);

  // HU-166: Operador logueado
  const [operador, setOperador] = useState(null);

  // Estados de registro de ingreso y doble ingreso
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
  const examenActivo = examenes.find((examen) => Number(examen.id_examen) === Number(examenSeleccionado));

  // Optimización HU-160: Referencias para audio y timeout
  const audioErrorRef = useRef(new Audio('/error.mp3'));
  const timeoutRef = useRef(null);

  // Examen y ambientes seleccionados
  const examenActual = examenes.find(
    (ex) => String(ex.id_examen) === String(examenSeleccionado)
  );

  const asignaciones = examenActual?.asignaciones_ambiente || [];

  // Cargar exámenes
  useEffect(() => {
    setOperador(getUsuario());
    cargarExamenes();

    return () => {
      detenerCamara();
      if (timeoutRef.current) {
        clearTimeout(timeoutRef.current);
      }
    };
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

  // --- HU-160: Función reutilizable para el Feedback Sensorial ---
  const dispararAlertaRechazo = () => {
    if (navigator.vibrate) navigator.vibrate([200, 100, 200]);
    if (audioErrorRef.current) {
      audioErrorRef.current.currentTime = 0;
      audioErrorRef.current.play().catch(e => console.warn('Reproducción bloqueada', e));
    }
    if (timeoutRef.current) clearTimeout(timeoutRef.current);
    timeoutRef.current = setTimeout(() => {
      setLoading(false);
    }, 2000);
  };

  const procesarErrorVerificacion = (err) => {
    console.error('Error en verificación:', err);
    const responseData = err.response?.data;

    // Mostrar el resultado de discrepancia en la tarjeta ámbar del flujo principal.
    if (responseData && (responseData.code === 'AULA_INCORRECTA' || responseData.code === 'AMBIENTE_INCORRECTO')) {
      const aula = responseData.ambiente_asignado || responseData.aula_asignada || {};
      setResultado({
        estudiante: responseData.estudiante || null,
        habilitacion: { estado: 'AULA_INCORRECTA', ambiente_asignado: aula },
      });
      setMostrarModalAmbiente(false);
      if (navigator.vibrate) navigator.vibrate([100, 50, 100]);
      setLoading(false);
      return;
    }

    const msg = responseData?.message || 'Estudiante no encontrado en el sistema.';
    setErrorAlerta(msg);
    dispararAlertaRechazo();
  };

  // Verificar estudiante por CI/SIS
  const handleVerificarDocumento = async (e) => {
    if (e) e.preventDefault();

    // Restableciendo alertas de validación requeridas
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
      const res = await api.post('/verificaciones/documento', {
        tipo: tipoDocumento,
        valor: identificador.trim(),
        examen_id: examenSeleccionado,
      });
      await guardarResultadoVerificacion(res.data);
      const estado = res.data?.habilitacion?.estado || res.data?.estado_verificacion || res.data?.estado;
      if (String(estado).toUpperCase() !== 'HABILITADO') {
        dispararAlertaRechazo();
      } else {
        setLoading(false);
      }
    } catch (err) {
      procesarErrorVerificacion(err);
    }
  };

  // Verificar estudiante por QR
  const handleVerificarQr = async (tokenParaEnviar) => {
    const token = tokenParaEnviar || tokenQr;

    // Restableciendo alertas de validación requeridas
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
      const res = await api.post('/verificaciones/qr', {
        token: token.trim(),
        examen_id: examenSeleccionado,
      });
      await guardarResultadoVerificacion(res.data);
      detenerCamara();

      const estado = res.data?.habilitacion?.estado || res.data?.estado_verificacion || res.data?.estado;
      if (String(estado).toUpperCase() !== 'HABILITADO') {
        dispararAlertaRechazo();
      } else {
        setLoading(false);
      }
    } catch (err) {
      procesarErrorVerificacion(err);
    }
  };

  // Registrar ingreso del estudiante habilitado
  const handleRegistrarIngreso = async () => {
    const habilitacion = resultado?.habilitacion;

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

      const esDobleIngreso =
        status === 409 ||
        (status === 422 && data.code === 'INGRESO_DUPLICADO');

      if (esDobleIngreso) {
        let fechaHora =
          data.fecha_hora ||
          data.ingreso?.fecha_hora ||
          data.primer_ingreso?.fecha_hora ||
          null;

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
    setIngresoConfirmado(false);
    setDobleIngreso(null);
    setIngresoRegistrado(false);

    // HU-180: Limpiar estados del modal ámbar
    setMostrarModalAmbiente(false);
    setAmbienteAsignado(null);

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
      setTimeout(() => {
        iniciarCamara();
      }, 200);
    } else {
      setTimeout(() => {
        inputIdentificadorRef.current?.focus();
      }, 100);
    }
  };

  const handleConfirmarIngreso = async () => {
    if (confirmandoIngreso || ingresoConfirmado) return;

    const idExamen = resultado?.examen?.id_examen || examenSeleccionado;
    const idHabilitacion = resultado?.habilitacion?.id_habilitacion;
    const idAsignacionAmbiente = asignacionSeleccionada
      || resultado?.habilitacion?.id_asignacion_ambiente;

    if (!idExamen || !idHabilitacion || !idAsignacionAmbiente) {
      setErrorAlerta('No se encontraron los datos necesarios para registrar el ingreso.');
      return;
    }

    setConfirmandoIngreso(true);
    setErrorAlerta(null);

    try {
      // El backend obtiene el controlador desde la sesión autenticada.
      await api.post('/ingresos/confirmar', {
        id_examen: idExamen,
        id_habilitacion: idHabilitacion,
        id_asignacion_ambiente: idAsignacionAmbiente,
      });
      setIngresoConfirmado(true);
      setIngresoRegistrado(true);
    } catch (err) {
      const data = err.response?.data || {};
      if (data.code === 'AULA_INCORRECTA' || data.code === 'AMBIENTE_INCORRECTO') {
        const aula = data.ambiente_asignado || data.aula_asignada || {};
        setResultado((actual) => ({
          ...actual,
          estudiante: data.estudiante || actual?.estudiante,
          habilitacion: {
            ...actual?.habilitacion,
            estado: 'AULA_INCORRECTA',
            ambiente_asignado: aula,
          },
        }));
        setMostrarModalAmbiente(false);
        return;
      }

      if (err.response?.status === 409 || data.code === 'REINGRESO_NO_PERMITIDO') {
        setDobleIngreso({
          mensaje: data.message || 'El estudiante ya tiene un ingreso registrado.',
          fechaHora: data.fecha_hora_primer_ingreso || data.primer_ingreso || null,
        });
        return;
      }

      const msg = data.message || 'No se pudo registrar el ingreso. Intente nuevamente.';
      setErrorAlerta(msg);
    } finally {
      setConfirmandoIngreso(false);
    }
  };

  const guardarResultadoVerificacion = async (verificacion) => {
    const estudianteVerificado = verificacion.estudiante;
    const examenVerificado = verificacion.examen;
    const idEstudiante = estudianteVerificado?.id;
    const idExamen = examenVerificado?.id || Number(examenSeleccionado);

    if (!idEstudiante || !idExamen) {
      throw new Error('La respuesta de verificación no contiene los datos del estudiante y del examen.');
    }

    const { data: habilitacion } = await api.get(
      `/examenes/${idExamen}/estudiantes/${idEstudiante}/inhabilitacion`,
    );

    setResultado({
      metodo: verificacion.metodo,
      estudiante: {
        id_estudiante: idEstudiante,
        nombre: estudianteVerificado.nombres,
        apellido: estudianteVerificado.apellidos,
        ci: estudianteVerificado.ci,
        codigo_universitario: estudianteVerificado.codigo_sis,
      },
      examen: {
        id_examen: idExamen,
        nombre: examenActivo?.nombre,
        asignatura: examenVerificado.asignatura,
        fecha: examenVerificado.fecha,
        hora_inicio: examenVerificado.hora,
      },
      habilitacion,
    });
  };

  return (
    <div
      className="container-fluid px-2 py-3 px-md-4"
      style={{ maxWidth: '650px', margin: '0 auto' }}
    >
      <div className="text-center mb-3">
        <h3 className="fw-bold text-primary mb-1">
          <i className="bi bi-shield-check me-2"></i>
          Control de Ingreso
        </h3>

        <p className="text-muted small mb-0">
          Verificación de credenciales en tiempo real
        </p>

        {operador && (
          <div className="mt-2 d-inline-block bg-primary bg-opacity-10 text-primary border border-primary-subtle rounded-pill px-3 py-1 fw-semibold small">
            <i className="bi bi-person-badge me-2"></i>
            Controlador: {operador.nombres || operador.nombre} {operador.apellidos || operador.apellido}
          </div>
        )}
      </div>

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
              setIngresoConfirmado(false);
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

      {ingresoConfirmado && (
        <div className="alert alert-success shadow-sm rounded-3 py-2 px-3 mb-3" role="status" aria-live="polite">
          <i className="bi bi-check-circle-fill me-2" aria-hidden="true"></i>
          Ingreso Registrado Exitosamente
        </div>
      )}

      {/* SI NO HAY RESULTADO: Muestra Selector de Modo y Formulario de Ingreso */}
      {!resultado ? (
        <div className="card border-0 shadow-sm rounded-4 overflow-hidden mb-3">
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
              <form onSubmit={handleVerificarDocumento}>
                <div className="mb-4 text-center">
                  <label className="form-label fw-bold text-dark fs-5 mb-2">
                    Ingrese {tipoDocumento === 'CI' ? 'C.I.' : 'Código SIS'}
                  </label>
                  <select
                    className="form-select form-select-lg mb-2"
                    aria-label="Tipo de documento"
                    value={tipoDocumento}
                    onChange={(e) => setTipoDocumento(e.target.value)}
                    disabled={loading}
                  >
                    <option value="CI">Cédula de identidad</option>
                    <option value="SIS">Código SIS</option>
                  </select>
                  <input
                    ref={inputIdentificadorRef}
                    type="text"
                    className="form-control form-control-lg text-center fs-4 fw-bold border-2"
                    placeholder={tipoDocumento === 'CI' ? 'Ej. 1234567' : 'Ej. 202000001'}
                    value={identificador}
                    onChange={(e) => setIdentificador(e.target.value)}
                    autoFocus
                    inputMode="numeric"
                    disabled={loading}
                  />

                  <small className="text-muted d-block mt-2">
                    Seleccione el tipo de documento para validar la identificación.
                    Admite búsqueda por carnet de identidad o código universitario SIS.
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
        <div>
          {/* Mostramos la tarjeta principal solo si no está activo el modal ámbar */}
          {!mostrarModalAmbiente && (
            <StudentCard
              habilitacion={resultado.habilitacion}
              estudiante={resultado.estudiante}
              fechaHora={
                resultado.ingreso?.fecha_hora ||
                resultado.fecha_hora ||
                resultado.fecha_consulta ||
                resultado.timestamp
              }
              onConfirmarIngreso={handleConfirmarIngreso}
              confirmandoIngreso={confirmandoIngreso}
              ingresoConfirmado={ingresoConfirmado}
              examenSeleccionado={examenActivo}
              examen={resultado.examen}
              onSiguiente={handleSiguienteEstudiante}
              onVerMotivo={() => setMostrarModalMotivo(true)}
            />
          )}

          {dobleIngreso && (
            <div className="alert alert-danger rounded-4 shadow-sm" role="alert">
              <h5 className="fw-bold"><i className="bi bi-exclamation-octagon-fill me-2"></i>DOBLE INGRESO DETECTADO</h5>
              <p className="mb-2">{dobleIngreso.mensaje}</p>
              {dobleIngreso.fechaHora && (
                <p className="fw-bold mb-3">Primer ingreso registrado: {new Date(dobleIngreso.fechaHora).toLocaleString('es-BO')}</p>
              )}
              <button type="button" className="btn btn-danger w-100 fw-bold" onClick={handleContinuarEscaneando}>
                <i className="bi bi-arrow-repeat me-2"></i>Aceptar / Continuar escaneando
              </button>
            </div>
          )}


          <MotivoInhabilitacionModal
            isOpen={mostrarModalMotivo}
            onClose={() => setMostrarModalMotivo(false)}
            motivo={resultado.habilitacion?.motivo}
            estado={resultado.habilitacion?.estado}
            estudiante={resultado.estudiante}
          />

          {/* HU-180: Nuevo Modal Ámbar */}
          <ModalAmbienteIncorrecto
            isOpen={mostrarModalAmbiente}
            onClose={handleSiguienteEstudiante}
            ambienteAsignado={ambienteAsignado}
            estudiante={resultado.estudiante}
          />
        </div>
      )}
    </div>
  );
}
