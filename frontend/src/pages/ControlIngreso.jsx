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

  // Entradas
  const [identificador, setIdentificador] = useState('');
  const [tokenQr, setTokenQr] = useState('');

  // Estados de proceso
  const [loading, setLoading] = useState(false);
  const [errorAlerta, setErrorAlerta] = useState(null);
  const [resultado, setResultado] = useState(null); 
  const [mostrarModalMotivo, setMostrarModalMotivo] = useState(false);
  
  // HU-180: Estados del Modal Ámbar
  const [mostrarModalAmbiente, setMostrarModalAmbiente] = useState(false);
  const [ambienteAsignado, setAmbienteAsignado] = useState(null);

  // HU-166: Operador logueado
  const [operador, setOperador] = useState(null);

  // Cámara para QR
  const [camaraActiva, setCamaraActiva] = useState(false);
  const [errorCamara, setErrorCamara] = useState(null);
  const videoRef = useRef(null);
  const streamRef = useRef(null);
  const inputIdentificadorRef = useRef(null);
  const examenActivo = examenes.find((examen) => Number(examen.id_examen) === Number(examenSeleccionado));

  // Optimización HU-160: Referencias para audio y timeout
  const audioErrorRef = useRef(new Audio('/error.mp3'));
  const timeoutRef = useRef(null);

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

    if (responseData && (responseData.code === 'AULA_INCORRECTA' || responseData.code === 'AMBIENTE_INCORRECTO')) {
      setAmbienteAsignado(responseData.ambiente_asignado);
      setResultado({ estudiante: responseData.estudiante || null }); 
      setMostrarModalAmbiente(true);
      if (navigator.vibrate) navigator.vibrate([100, 50, 100]); 
      setLoading(false);
      return;
    }

    const msg = responseData?.message || 'Estudiante no encontrado en el sistema.';
    setErrorAlerta(msg);
    dispararAlertaRechazo();
  };

  const handleVerificarDocumento = async (e) => {
    if (e) e.preventDefault();
    // CORREGIDO: Restaurando alertas de validación
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
      const res = await api.post('/verificar/documento', {
        identificador: identificador.trim(),
        id_examen: examenSeleccionado,
      });
      setResultado(res.data);
      
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

  const handleVerificarQr = async (tokenParaEnviar) => {
    const token = tokenParaEnviar || tokenQr;
    // CORREGIDO: Restaurando alertas de validación
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
      const res = await api.post('/verificar/qr', {
        token: token.trim(),
        id_examen: examenSeleccionado,
      });
      setResultado(res.data);
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

  const handleSiguienteEstudiante = () => {
    setResultado(null);
    setErrorAlerta(null);
    setIdentificador('');
    setTokenQr('');
    setMostrarModalMotivo(false);
    
    // HU-180: Limpiar estados del modal ámbar
    setMostrarModalAmbiente(false);
    setAmbienteAsignado(null);

    setTimeout(() => {
      if (metodo === 'ci_sis' && inputIdentificadorRef.current) {
        inputIdentificadorRef.current.focus();
      }
    }, 100);
  };

  return (
    <div className="container-fluid px-2 py-3 px-md-4" style={{ maxWidth: '650px', margin: '0 auto' }}>
      <div className="text-center mb-3">
        <h3 className="fw-bold text-primary mb-1">
          <i className="bi bi-shield-check me-2"></i>Control de Ingreso
        </h3>
        <p className="text-muted small mb-0">Verificación de credenciales en tiempo real</p>
        
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
            <i className="bi bi-journal-text me-1 text-primary"></i>Sesión de Examen Activa
          </label>
          <select
            className="form-select fw-semibold"
            value={examenSeleccionado}
            onChange={(e) => {
              setExamenSeleccionado(e.target.value);
              setResultado(null);
            }}
            disabled={loading}
          >
            {examenes.map((ex) => (
              <option key={ex.id_examen} value={ex.id_examen}>
                {ex.nombre} ({ex.fecha ? String(ex.fecha).substring(0, 10) : ''} {ex.hora_inicio})
              </option>
            ))}
          </select>
        </div>
      </div>

      {errorAlerta && (
        <div className="alert alert-danger shadow-sm rounded-3 py-2 px-3 d-flex align-items-center mb-3" role="alert">
          <i className="bi bi-exclamation-triangle-fill fs-5 me-2"></i>
          <div className="small fw-semibold flex-fill">{errorAlerta}</div>
          <button type="button" className="btn-close btn-sm" onClick={() => setErrorAlerta(null)}></button>
        </div>
      )}

      {!resultado ? (
        <div className="card border-0 shadow-sm rounded-4 overflow-hidden mb-3">
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
                    Admite búsqueda por carnet de identidad o código universitario SIS.
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
              examenSeleccionado={examenActivo}
              examen={resultado.examen}
              onSiguiente={handleSiguienteEstudiante}
              onVerMotivo={() => setMostrarModalMotivo(true)}
            />
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