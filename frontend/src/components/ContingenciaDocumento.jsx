import { useRef, useState } from 'react';
import api from '../services/api';

const LONGITUD_MINIMA = 4;

function obtenerMensajeError(error) {
  return error.response?.data?.message || 'No se pudo verificar el documento. Inténtalo nuevamente.';
}

/**
 * Formulario manual de contingencia para verificar al estudiante por CI o SIS.
 * `idExamen` debe corresponder al examen seleccionado por la pantalla que lo use.
 */
export default function ContingenciaDocumento({ idExamen, onResult, onError }) {
  const inputRef = useRef(null);
  const enviandoRef = useRef(false);
  const [tipo, setTipo] = useState('CI');
  const [valor, setValor] = useState('');
  const [cargando, setCargando] = useState(false);
  const [errorLocal, setErrorLocal] = useState('');

  const verificar = async (event) => {
    event.preventDefault();
    if (enviandoRef.current) return;

    const documento = valor.trim();
    if (!documento) {
      setErrorLocal('Ingresa el documento del estudiante.');
      inputRef.current?.focus();
      return;
    }
    if (documento.length < LONGITUD_MINIMA) {
      setErrorLocal(`El documento debe tener al menos ${LONGITUD_MINIMA} caracteres.`);
      inputRef.current?.focus();
      return;
    }
    if (!idExamen) {
      const error = new Error('Selecciona un examen antes de verificar.');
      setErrorLocal(error.message);
      onError?.(error);
      return;
    }

    enviandoRef.current = true;
    setCargando(true);
    setErrorLocal('');
    try {
      const response = await api.post('/verificaciones/documento', {
        id_examen: idExamen,
        tipo_documento: tipo,
        documento,
      });
      onResult?.(response.data);
    } catch (error) {
      setErrorLocal(obtenerMensajeError(error));
      onError?.(error);
    } finally {
      setValor('');
      setCargando(false);
      enviandoRef.current = false;
      requestAnimationFrame(() => inputRef.current?.focus());
    }
  };

  return (
    <section className="card border-0 shadow-sm" aria-labelledby="contingencia-documento-titulo">
      <div className="card-body p-4">
        <div className="d-flex align-items-center gap-3 mb-3">
          <span className="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary bg-opacity-10 text-primary" style={{ width: 44, height: 44 }} aria-hidden="true">
            <i className="bi bi-keyboard fs-5" />
          </span>
          <div>
            <h2 className="h5 mb-1" id="contingencia-documento-titulo">Verificación manual</h2>
            <p className="text-muted mb-0">Usa esta opción si no puedes leer el código QR.</p>
          </div>
        </div>

        <form onSubmit={verificar} noValidate>
          <label className="form-label fw-semibold" htmlFor="tipo-documento">Tipo de documento</label>
          <select
            id="tipo-documento"
            className="form-select mb-3"
            value={tipo}
            onChange={(event) => setTipo(event.target.value)}
            disabled={cargando}
          >
            <option value="CI">Carnet de identidad (CI)</option>
            <option value="SIS">Código SIS</option>
          </select>

          <label className="form-label fw-semibold" htmlFor="documento-estudiante">
            {tipo === 'CI' ? 'Número de CI' : 'Código SIS'}
          </label>
          <input
            ref={inputRef}
            id="documento-estudiante"
            className="form-control form-control-lg mb-3"
            type="text"
            value={valor}
            onChange={(event) => {
              setValor(event.target.value);
              if (errorLocal) setErrorLocal('');
            }}
            placeholder={tipo === 'CI' ? 'Ingresa el CI' : 'Ingresa el código SIS'}
            inputMode={tipo === 'SIS' ? 'numeric' : 'text'}
            autoComplete="off"
            autoFocus
            disabled={cargando}
            aria-describedby={errorLocal ? 'documento-error' : undefined}
          />

          {errorLocal && <div className="alert alert-danger py-2" id="documento-error" role="alert">{errorLocal}</div>}

          <button type="submit" className="btn btn-primary btn-lg w-100" style={{ minHeight: 48 }} disabled={cargando}>
            {cargando ? (
              <><span className="spinner-border spinner-border-sm me-2" aria-hidden="true" />Verificando…</>
            ) : (
              <><i className="bi bi-search me-2" aria-hidden="true" />Verificar estudiante</>
            )}
          </button>
        </form>
      </div>
    </section>
  );
}
