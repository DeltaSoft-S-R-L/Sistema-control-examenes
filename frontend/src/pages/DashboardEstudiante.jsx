
import React, { useEffect, useState } from 'react';
import api from '../services/api';

export default function DashboardEstudiante() {
  const [perfil, setPerfil] = useState(null);
  const [cargando, setCargando] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    let activo = true;

    async function cargarPerfil() {
      try {
        const respuesta = await api.get('/estudiante/perfil');

        if (activo) {
          setPerfil(respuesta.data);
        }
      } catch (err) {
        if (activo) {
          setError(
            err.response?.data?.message ||
              'No se pudo cargar tu perfil estudiantil.'
          );
        }
      } finally {
        if (activo) setCargando(false);
      }
    }

    cargarPerfil();

    return () => {
      activo = false;
    };
  }, []);

  const estilos = {
    pagina: {
      minHeight: '100vh',
      background: '#f4f7fb',
      padding: '32px 24px',
      fontFamily: 'inherit',
      color: '#1e293b',
    },
    contenedor: {
      maxWidth: '1100px',
      margin: '0 auto',
    },
    tarjeta: {
      background: '#ffffff',
      borderRadius: '16px',
      padding: '24px',
      border: '1px solid #e2e8f0',
      boxShadow: '0 4px 14px rgba(15, 23, 42, 0.04)',
    },
    encabezado: {
      background: 'linear-gradient(120deg, #173b75, #2563a6)',
      color: '#ffffff',
      borderRadius: '18px',
      padding: '32px',
      marginBottom: '24px',
    },
    cuadricula: {
      display: 'grid',
      gridTemplateColumns: 'repeat(auto-fit, minmax(230px, 1fr))',
      gap: '18px',
      marginBottom: '24px',
    },
    etiqueta: {
      color: '#64748b',
      fontSize: '13px',
      marginBottom: '6px',
    },
    valor: {
      fontWeight: 600,
      overflowWrap: 'anywhere',
    },
  };

  if (cargando) {
    return (
      <div style={estilos.pagina}>
        <div style={estilos.contenedor}>
          <p>Cargando información del estudiante...</p>
        </div>
      </div>
    );
  }

  if (error || !perfil) {
    return (
      <div style={estilos.pagina}>
        <div style={estilos.contenedor}>
          <h1>Panel del Estudiante</h1>
          <p role="alert" style={{ color: '#b91c1c' }}>
            {error || 'No se encontró información del estudiante.'}
          </p>
        </div>
      </div>
    );
  }

  const asignaturas = perfil.asignaturas || [];

  return (
    <main style={estilos.pagina}>
      <div style={estilos.contenedor}>
        <div style={{ marginBottom: '24px' }}>
          <h1 style={{ fontSize: '28px', margin: 0 }}>
            Panel del Estudiante
          </h1>
          <p style={{ color: '#64748b', marginTop: '6px' }}>
            Sistema de Control de Exámenes
          </p>
        </div>

        <section style={estilos.encabezado}>
          <p style={{
            fontSize: '13px',
            letterSpacing: '1px',
            opacity: 0.85,
            marginBottom: '12px',
          }}>
            BIENVENIDO A TU ESPACIO ACADÉMICO
          </p>

          <h2 style={{
            fontSize: 'clamp(24px, 4vw, 34px)',
            fontWeight: 700,
            marginBottom: '10px',
          }}>
            {perfil.nombre} {perfil.apellido}
          </h2>

          <p style={{ margin: 0, opacity: 0.9 }}>
            {perfil.carrera?.nombre || 'Carrera no registrada'}
            {' · SIS '}
            {perfil.codigo_universitario}
          </p>
        </section>

        <div style={estilos.cuadricula}>
          <section style={estilos.tarjeta}>
            <p style={estilos.etiqueta}>Asignaturas inscritas</p>
            <div style={{
              fontSize: '38px',
              fontWeight: 700,
              color: '#1d4ed8',
            }}>
              {asignaturas.length}
            </div>
            <p style={{ color: '#64748b', marginBottom: 0 }}>
              Materias registradas
            </p>
          </section>

          <section style={estilos.tarjeta}>
            <p style={estilos.etiqueta}>Mi carrera</p>
            <h3 style={{ fontSize: '19px', margin: '8px 0' }}>
              {perfil.carrera?.nombre || 'No registrada'}
            </h3>
            <p style={{ color: '#64748b', marginBottom: 0 }}>
              Código: {perfil.carrera?.codigo || '—'}
            </p>
          </section>
        </div>

        <section style={{
          ...estilos.tarjeta,
          marginBottom: '24px',
        }}>
          <h2 style={{ fontSize: '21px', marginTop: 0 }}>
            Mis datos personales
          </h2>

          <div style={{
            display: 'grid',
            gridTemplateColumns:
              'repeat(auto-fit, minmax(220px, 1fr))',
            gap: '24px',
            marginTop: '22px',
          }}>
            <div>
              <p style={estilos.etiqueta}>Nombre completo</p>
              <div style={estilos.valor}>
                {perfil.nombre} {perfil.apellido}
              </div>
            </div>

            <div>
              <p style={estilos.etiqueta}>Código SIS</p>
              <div style={estilos.valor}>
                {perfil.codigo_universitario}
              </div>
            </div>

            <div>
              <p style={estilos.etiqueta}>Correo electrónico</p>
              <div style={estilos.valor}>
                {perfil.correo || 'No registrado'}
              </div>
            </div>

            <div>
              <p style={estilos.etiqueta}>Carrera</p>
              <div style={estilos.valor}>
                {perfil.carrera?.nombre || 'No registrada'}
              </div>
            </div>
          </div>
        </section>

        <section style={estilos.tarjeta}>
          <div style={{
            display: 'flex',
            justifyContent: 'space-between',
            alignItems: 'center',
            flexWrap: 'wrap',
            gap: '12px',
            marginBottom: '20px',
          }}>
            <h2 style={{ fontSize: '21px', margin: 0 }}>
              Mis asignaturas
            </h2>

            <span style={{
              background: '#eff6ff',
              color: '#1d4ed8',
              padding: '6px 12px',
              borderRadius: '20px',
              fontSize: '13px',
              fontWeight: 600,
            }}>
              {asignaturas.length} inscritas
            </span>
          </div>

          {asignaturas.length > 0 ? (
            <div style={{
              display: 'grid',
              gap: '12px',
            }}>
              {asignaturas.map((asignatura) => (
                <div
                  key={asignatura.codigo}
                  style={{
                    display: 'flex',
                    alignItems: 'center',
                    gap: '16px',
                    padding: '16px',
                    border: '1px solid #e2e8f0',
                    borderRadius: '12px',
                    background: '#f8fafc',
                  }}
                >
                  <div style={{
                    width: '44px',
                    height: '44px',
                    minWidth: '44px',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    background: '#dbeafe',
                    color: '#1d4ed8',
                    borderRadius: '10px',
                    fontSize: '22px',
                  }}>
                    📘
                  </div>

                  <div>
                    <div style={{
                      fontWeight: 600,
                      marginBottom: '4px',
                    }}>
                      {asignatura.nombre}
                    </div>
                    <div style={{
                      color: '#64748b',
                      fontSize: '13px',
                    }}>
                      Código: {asignatura.codigo}
                    </div>
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <p style={{ color: '#64748b' }}>
              No tienes asignaturas inscritas actualmente.
            </p>
          )}
        </section>
      </div>
    </main>
  );
}
