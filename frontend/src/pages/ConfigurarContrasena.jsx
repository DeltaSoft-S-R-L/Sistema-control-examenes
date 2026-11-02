
import React, { useEffect, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';

const API_URL = (import.meta.env.VITE_API_URL || 'http://localhost:8000/api').replace(/\/$/, '');

export default function ConfigurarContrasena() {
  const [searchParams] = useSearchParams();
  const token = searchParams.get('token');

  const [verificando, setVerificando] = useState(true);
  const [enlaceValido, setEnlaceValido] = useState(false);
  const [password, setPassword] = useState('');
  const [confirmacion, setConfirmacion] = useState('');
  const [guardando, setGuardando] = useState(false);
  const [completado, setCompletado] = useState(false);
  const [mensaje, setMensaje] = useState('');

  useEffect(() => {
    const controller = new AbortController();

    async function verificarEnlace() {
      if (!token) {
        setMensaje('El enlace no contiene un token de configuración.');
        setVerificando(false);
        return;
      }

      try {
        const respuesta = await fetch(
          `${API_URL}/configurar-contrasena/verificar`,
          {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
            },
            body: JSON.stringify({ token }),
            signal: controller.signal,
          }
        );

        const datos = await respuesta.json();

        if (!respuesta.ok) {
          throw new Error(datos.message || 'El enlace no es válido.');
        }

        setEnlaceValido(true);
      } catch (error) {
        if (error.name !== 'AbortError') {
          setMensaje(
            error.message || 'No se pudo verificar el enlace.'
          );
        }
      } finally {
        if (!controller.signal.aborted) {
          setVerificando(false);
        }
      }
    }

    verificarEnlace();

    return () => controller.abort();
  }, [token]);

  async function configurarPassword(evento) {
    evento.preventDefault();
    setMensaje('');

    if (password.length < 8) {
      setMensaje('La contraseña debe tener al menos 8 caracteres.');
      return;
    }

    if (!/[a-zA-Z]/.test(password) || !/[0-9]/.test(password)) {
      setMensaje('La contraseña debe contener al menos una letra y un número.');
      return;
    }

    if (password !== confirmacion) {
      setMensaje('Las contraseñas no coinciden.');
      return;
    }

    setGuardando(true);

    try {
      const respuesta = await fetch(
        `${API_URL}/configurar-contrasena`,
        {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          },
          body: JSON.stringify({
            token,
            password,
            password_confirmation: confirmacion,
          }),
        }
      );

      const datos = await respuesta.json();

      if (!respuesta.ok) {
        throw new Error(
          datos.message || 'No se pudo configurar la contraseña.'
        );
      }

      setCompletado(true);
      setPassword('');
      setConfirmacion('');
      setMensaje(
        'Tu contraseña fue configurada correctamente. Ya puedes iniciar sesión.'
      );
    } catch (error) {
      setMensaje(
        error.message || 'Ocurrió un error al guardar la contraseña.'
      );
    } finally {
      setGuardando(false);
    }
  }

  return (
    <main
      className="container d-flex align-items-center justify-content-center"
      style={{ minHeight: '100vh', maxWidth: '480px' }}
    >
      <div className="card shadow-sm w-100">
        <div className="card-body p-4">
          <h2 className="text-center mb-3">
            Configurar contraseña
          </h2>

          <p className="text-muted text-center">
            Sistema de Control de Exámenes
          </p>

          {verificando && (
            <p className="text-center">
              Verificando enlace...
            </p>
          )}

          {!verificando && mensaje && (
            <div
              className={`alert ${
                completado ? 'alert-success' : 'alert-danger'
              }`}
              role="alert"
            >
              {mensaje}
            </div>
          )}

          {!verificando && enlaceValido && !completado && (
            <form onSubmit={configurarPassword}>
              <div className="mb-3">
                <label
                  htmlFor="nueva-password"
                  className="form-label"
                >
                  Nueva contraseña
                </label>

                <input
                  id="nueva-password"
                  type="password"
                  className="form-control"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  autoComplete="new-password"
                  minLength={8}
                  required
                />
              </div>

              <div className="mb-3">
                <label
                  htmlFor="confirmar-password"
                  className="form-label"
                >
                  Confirmar contraseña
                </label>

                <input
                  id="confirmar-password"
                  type="password"
                  className="form-control"
                  value={confirmacion}
                  onChange={(e) => setConfirmacion(e.target.value)}
                  autoComplete="new-password"
                  minLength={8}
                  required
                />
              </div>

              <p className="text-muted small">
                Utiliza al menos 8 caracteres, incluyendo
                una letra y un número.
              </p>

              <button
                type="submit"
                className="btn btn-primary w-100"
                disabled={guardando}
              >
                {guardando
                  ? 'Guardando...'
                  : 'Establecer contraseña'}
              </button>
            </form>
          )}

          <div className="text-center mt-4">
            <Link to="/login">
              Volver al inicio de sesión
            </Link>
          </div>
        </div>
      </div>
    </main>
  );
}