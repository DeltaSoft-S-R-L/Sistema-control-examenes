import React, { useState, useEffect, useRef } from 'react';
import api from '../services/api';
import StudentCard from '../components/estudiantes/StudentCard';
import MotivoInhabilitacionModal from '../components/habilitaciones/MotivoInhabilitacionModal';

export default function ControlIngreso() {
  const [examenes, setExamenes] = useState([]);
  const [examenSeleccionado, setExamenSeleccionado] = useState('');
  const [metodo, setMetodo] = useState('ci_sis'); // 'ci_sis' o 'qr'
  const [tipoDocumento, setTipoDocumento] = useState('CI');

  // Entradas
  const [identificador, setIdentificador] = useState('');
  const [tokenQr, setTokenQr] = useState('');

  // Estados de proceso
  const [loading, setLoading] = useState(false);
  const [errorAlerta, setErrorAlerta] = useState(null);
  const [resultado, setResultado] = useState(null); // Objeto de respuesta
  const [mostrarModalMotivo, setMostrarModalMotivo] = useState(false);
  const [confirmandoIngreso, setConfirmandoIngreso] = useState(false);
  const [ingresoConfirmado, setIngresoConfirmado] = useState(false);

  // Cámara para QR en celular
  const [camaraActiva, setCamaraActiva] = useState(false);
  const [errorCamara, setErrorCamara] = useState(null);
  const videoRef = useRef(null);
  const streamRef = useRef(null);
  const inputIdentificadorRef = useRef(null);
  const examenActivo = examenes.find((examen) => Number(examen.id_examen) === Number(examenSeleccionado));

  useEffect(() => {
    cargarExamenes();
  }, []);

  const cargarExamenes = async () => {
    try {
      const res = await api.get('/examenes');
      const lista = res.data.data || res.data || [];
      setExamenes(lista);
      if (lista.length > 0) {
        setExamenSeleccionado(lista[0].id_examen);
      }
    } catch (err) {
      console.error('Error al cargar exámenes:', err);
      setErrorAlerta('No se pudieron cargar los exámenes programados.');
    }
  };

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
        videoRef.current.play();
      }
      setCamaraActiva(true);
    } catch (err) {
      console.error('Error al acceder a la cámara:', err);
      if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
        setErrorCamara('Acceso a la cámara denegado. Permita el uso de la cámara en su navegador o utilice verificación por CI/SIS.');
      } else {
        setErrorCamara('No se pudo inicializar la cámara. Verifique que no esté en uso por otra app.');
      }
      setCamaraActiva(false);
    }
  };

  const detenerCamara = () => {
    if (streamRef.current) {
      streamRef.current.getTracks().forEach((t) => t.stop());
      streamRef.current = null;
    }
    setCamaraActiva(false);
  };

  useEffect(() => {
    return () => {
      detenerCamara();
    };
  }, []);

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

    try {
      const res = await api.post('/verificaciones/documento', {
        tipo: tipoDocumento,
        valor: identificador.trim(),
        examen_id: examenSeleccionado,
      });
      await guardarResultadoVerificacion(res.data);
    } catch (err) {
      console.error('Error al verificar documento:', err);
      const msg = err.response?.data?.message || 'Estudiante no encontrado en el sistema.';
      setErrorAlerta(msg);
    } finally {
      setLoading(false);
    }
  };

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

    try {
      const res = await api.post('/verificaciones/qr', {
        token: token.trim(),
        examen_id: examenSeleccionado,
      });
      await guardarResultadoVerificacion(res.data);
      detenerCamara();
    } catch (err) {
      console.error('Error al verificar QR:', err);
      const msg = err.response?.data?.message || 'Código QR no reconocido o estudiante no encontrado.';
      setErrorAlerta(msg);
    } finally {
      setLoading(false);
    }
  };

  const handleSiguienteEstudiante = () => {
    setResultado(null);
    setErrorAlerta(null);
    setIdentificador('');
    setTokenQr('');
    setMostrarModalMotivo(false);
    setIngresoConfirmado(false);

    // Retornar foco inmediatamente
    setTimeout(() => {
      if (inputIdentificadorRef.current) {
        inputIdentificadorRef.current.focus();
      }
    }, 100);
  };

  const handleConfirmarIngreso = async () => {
    if (confirmandoIngreso || ingresoConfirmado) return;

    const idExamen = resultado?.examen?.id_examen || examenSeleccionado;
    const idHabilitacion = resultado?.habilitacion?.id_habilitacion;
    const idAsignacionAmbiente = examenActivo?.asignaciones_ambiente?.[0]?.id_asignacion_ambiente
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
    } catch (err) {
      const msg = err.response?.data?.message || 'No se pudo registrar el ingreso. Intente nuevamente.';
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
    <div className="container-fluid px-2 py-3 px-md-4" style={{ maxWidth: '650px', margin: '0 auto' }}>
      {/* Título de control móvil */}
      <div className="text-center mb-3">
        <h3 className="fw-bold text-primary mb-1">
          <i className="bi bi-shield-check me-2"></i>Control de Ingreso
        </h3>
        <p className="text-muted small mb-0">Verificación de credenciales en tiempo real</p>
      </div>

      {/* Selector de Examen Activo */}
      <div className="card border-0 shadow-sm rounded-4 mb-3">
        <div className="card-body p-3">
          <label className="form-label small fw-bold text-uppercase text-muted mb-1">
            <i className="bi bi-journal-text me-1 text-primary"></i>Sesión de Examen Activa
          </label>
          <select
            className="form-select fw-semibold"
            value={examenSeleccionado}
            onChange={(e) => {
              setExamenSeleccionado(e.target.value);
              setResultado(null);
              setIngresoConfirmado(false);
            }}
            disabled={loading}
          >
            {examenes.map((ex) => (
              <option key={ex.id_examen} value={ex.id_examen}>
                {ex.nombre} ({ex.fecha ? String(ex.fecha).substring(0, 10) : ''} - {ex.hora_inicio})
              </option>
            ))}
          </select>
        </div>
      </div>

      {/* Alerta de Error si ocurre */}
      {errorAlerta && (
        <div className="alert alert-danger shadow-sm rounded-3 py-2 px-3 d-flex align-items-center mb-3" role="alert">
          <i className="bi bi-exclamation-triangle-fill fs-5 me-2"></i>
          <div className="small fw-semibold flex-fill">{errorAlerta}</div>
          <button type="button" className="btn-close btn-sm" onClick={() => setErrorAlerta(null)}></button>
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
          {/* Pestañas de Selección de Modo */}
          <div className="btn-group w-100 p-2 bg-light border-bottom" role="group">
            <button
              type="button"
              className={`btn py-2 fw-bold ${metodo === 'ci_sis' ? 'btn-primary' : 'btn-outline-secondary'}`}
              onClick={() => {
                setMetodo('ci_sis');
                detenerCamara();
              }}
            >
              <i className="bi bi-person-badge me-2"></i>C.I. / SIS
            </button>
            <button
              type="button"
              className={`btn py-2 fw-bold ${metodo === 'qr' ? 'btn-primary' : 'btn-outline-secondary'}`}
              onClick={() => {
                setMetodo('qr');
              }}
            >
              <i className="bi bi-qr-code-scan me-2"></i>Escanear QR
            </button>
          </div>

          <div className="card-body p-4">
            {metodo === 'ci_sis' ? (
              /* MODO CI / SIS */
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
                  </small>
                </div>

                <button
                  type="submit"
                  className="btn btn-primary btn-lg w-100 py-3 fw-bold shadow-sm"
                  disabled={loading}
                >
                  {loading ? (
                    <>
                      <span className="spinner-border spinner-border-sm me-2" role="status"></span>
                      Verificando...
                    </>
                  ) : (
                    <>
                      <i className="bi bi-search me-2"></i>Verificar Estudiante
                    </>
                  )}
                </button>
              </form>
            ) : (
              /* MODO QR */
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
                      <i className="bi bi-camera-fill me-2"></i>Activar Cámara del Celular
                    </button>
                    <p className="small text-muted mb-0">
                      O pegue/ingrese el token QR a continuación:
                    </p>
                  </div>
                ) : (
                  <div className="text-center mb-3">
                    <div className="ratio ratio-4x3 bg-dark rounded-3 overflow-hidden position-relative mb-2">
                      <video ref={videoRef} className="w-100 h-100 object-fit-cover" playsInline muted></video>
                      <div
                        className="position-absolute top-50 start-50 translate-middle border border-2 border-success rounded-3"
                        style={{ width: '220px', height: '220px', boxShadow: '0 0 0 9999px rgba(0, 0, 0, 0.4)' }}
                      ></div>
                    </div>
                    <button
                      type="button"
                      className="btn btn-outline-danger btn-sm w-100 fw-semibold"
                      onClick={detenerCamara}
                    >
                      <i className="bi bi-stop-circle me-1"></i>Detener Cámara
                    </button>
                  </div>
                )}

                {/* Input manual de Token QR para pruebas o escáner externo */}
                <div className="mt-3 pt-3 border-top">
                  <label className="form-label small fw-semibold text-muted">Token / Código QR:</label>
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
                      disabled={loading}
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
        /* ========================================================================= */
        /* TARJETA DE RESULTADO (StudentCard) Y MODAL DE MOTIVO */
        /* ========================================================================= */
        <div>
          <StudentCard
            estudiante={resultado.estudiante}
            habilitacion={resultado.habilitacion}
            onConfirmarIngreso={handleConfirmarIngreso}
            confirmandoIngreso={confirmandoIngreso}
            ingresoConfirmado={ingresoConfirmado}
            examen={resultado.examen}
            onSiguiente={handleSiguienteEstudiante}
            onVerMotivo={() => setMostrarModalMotivo(true)}
          />

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
