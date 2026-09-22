import React from 'react';
import { Link } from 'react-router-dom';

export default function ModuloEnConstruccion({ titulo, descripcion }) {
  return (
    <div className="container-fluid py-4">
      <div className="card border-0 shadow-sm">
        <div className="card-body text-center py-5 px-4">
          <div
            className="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle mb-3"
            style={{
              width: '64px',
              height: '64px',
            }}
          >
            <i className="bi bi-tools fs-3"></i>
          </div>

          <h3 className="fw-bold mb-2">{titulo}</h3>

          <p className="text-muted mb-4">
            {descripcion ||
              'Este módulo se encuentra actualmente en construcción.'}
          </p>

          <span className="badge bg-warning text-dark mb-4">
            Próximamente
          </span>

          <div>
            <Link to="/" className="btn btn-primary">
              <i className="bi bi-house-door me-2"></i>
              Volver al inicio
            </Link>
          </div>
        </div>
      </div>
    </div>
  );
}