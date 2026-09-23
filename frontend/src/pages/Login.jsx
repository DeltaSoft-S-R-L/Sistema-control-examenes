import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import api from '../services/api';

export default function Login() {
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const navigate = useNavigate();

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setLoading(true);

    try {
      const response = await api.post('/login', { username, password });
      localStorage.setItem('token', response.data.token);
      localStorage.setItem('usuario', JSON.stringify(response.data.usuario));
      navigate('/');
    } catch (err) {
      setError(
        err.response?.data?.message ||
        err.response?.data?.errors?.username?.[0] ||
        'Error al iniciar sesión. Verifique sus credenciales.'
      );
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="container-fluid vh-100 d-flex flex-column flex-md-row p-0">
      {/* Sección Izquierda: Branding CampusGate */}
      <div className="col-12 col-md-7 text-white d-flex flex-column justify-content-center p-5" style={{ backgroundColor: '#0A3123' }}>
        <h1 className="display-4 fw-bold mb-3">
          Cada estudiante.<br />
          En el aula correcta.<br />
          A tiempo.
        </h1>
        <p className="lead mb-5">
          Control rápido y seguro para exámenes universitarios masivos. Verifica identidades, habilitaciones y registra ingresos en tiempo real.
        </p>
        
        {/* Stats*/}
        <div className="d-flex gap-4">
          <div className="p-3 border rounded border-success border-opacity-50" style={{ backgroundColor: 'rgba(255,255,255,0.05)' }}>
            <small className="d-block text-uppercase text-muted">Aula Magna A</small>
            <span className="fs-3 fw-bold">412 / 412</span>
            <small className="d-block text-muted">Ingreso completado</small>
          </div>
          <div className="p-3 border rounded border-success border-opacity-50" style={{ backgroundColor: 'rgba(255,255,255,0.05)' }}>
            <small className="d-block text-uppercase text-muted">Centro de Cómputo</small>
            <span className="fs-3 fw-bold">184 / 200</span>
            <small className="d-block text-muted">Ingreso en proceso</small>
          </div>
        </div>
      </div>

      {/* Sección Derecha: Formulario */}
      <div className="col-12 col-md-5 d-flex align-items-center justify-content-center" style={{ backgroundColor: '#072419' }}>
        <div className="card shadow-lg w-100 mx-4 mx-md-5 border-0 rounded-4" style={{ maxWidth: '450px' }}>
          <div className="card-body p-5">
            <small className="text-muted text-uppercase fw-bold">Acceso Seguro</small>
            <h2 className="card-title fw-bold mb-4">Bienvenido</h2>
            <p className="text-muted small mb-4">Inicia sesión para gestionar el control de este recinto.</p>

            {error && (
              <div className="alert alert-danger py-2 d-flex align-items-center" role="alert">
                <i className="bi bi-exclamation-triangle-fill me-2"></i>
                <div>{error}</div>
              </div>
            )}

            <form onSubmit={handleSubmit}>
              <div className="mb-3">
                <label className="form-label small fw-bold">Correo institucional o usuario</label>
                <input 
                  type="text" 
                  className="form-control bg-light" 
                  placeholder="nombre@cuentas.umss.edu.bo"
                  value={username}
                  onChange={(e) => setUsername(e.target.value)}
                  required 
                  autoFocus
                />
              </div>
              <div className="mb-4">
                <label className="form-label small fw-bold">Contraseña</label>
                <input 
                  type="password" 
                  className="form-control bg-light" 
                  placeholder="••••••••"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  required 
                />
              </div>
              <button 
                type="submit" 
                className="btn w-100 text-white fw-bold py-2 shadow-sm" 
                style={{ backgroundColor: '#0A3123' }}
                disabled={loading}
              >
                {loading ? (
                  <>
                    <span className="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                    Verificando...
                  </>
                ) : (
                  'Iniciar sesión →'
                )}
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  );
}