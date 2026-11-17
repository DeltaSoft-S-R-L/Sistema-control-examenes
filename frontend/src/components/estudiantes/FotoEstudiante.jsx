import React, { useEffect, useState } from "react";
import api from "../../services/api";
import RecortadorFoto from "./RecortadorFoto";

const TAMANO_MAXIMO = 5 * 1024 * 1024;
const TIPOS_PERMITIDOS = ["image/jpeg", "image/png"];

export default function FotoEstudiante() {
  const [archivo, setArchivo] = useState(null);
  const [vistaPrevia, setVistaPrevia] = useState("");
  const [imagenParaRecortar, setImagenParaRecortar] = useState("");
  const [nombreArchivoOriginal, setNombreArchivoOriginal] = useState("");
  const [error, setError] = useState("");
  const [estadoFoto, setEstadoFoto] = useState(null);
  const [cargandoFoto, setCargandoFoto] = useState(true);
  const [errorConsulta, setErrorConsulta] = useState("");
  const [enviandoFoto, setEnviandoFoto] = useState(false);
  const [mensajeExito, setMensajeExito] = useState("");
  const [confirmarCarga, setConfirmarCarga] = useState(false);

  useEffect(() => {
    let activo = true;

    async function consultarEstadoFoto() {
      try {
        const respuesta = await api.get("/estudiante/foto");

        if (activo) {
          setEstadoFoto(respuesta.data);
          setErrorConsulta("");
        }
      } catch (error) {
        console.error("ERROR AL CONSULTAR FOTOGRAFÍA:", {
          mensaje: error.message,
          codigo: error.code,
          estadoHTTP: error.response?.status,
          respuesta: error.response?.data,
          url: error.config?.url,
        });

        if (activo) {
          setErrorConsulta("No se pudo consultar el estado de tu fotografía.");
        }
      } finally {
        if (activo) {
          setCargandoFoto(false);
        }
      }
    }

    consultarEstadoFoto();

    return () => {
      activo = false;
    };
  }, []);
  useEffect(() => {
    if (!archivo) {
      setVistaPrevia("");
      return;
    }

    const url = URL.createObjectURL(archivo);
    setVistaPrevia(url);

    return () => {
      URL.revokeObjectURL(url);
    };
  }, [archivo]);

  useEffect(() => {
    return () => {
      if (imagenParaRecortar) {
        URL.revokeObjectURL(imagenParaRecortar);
      }
    };
  }, [imagenParaRecortar]);

  async function seleccionarFoto(evento) {
    const seleccionado = evento.target.files?.[0];

    setArchivo(null);
    setError("");

    if (!seleccionado) return;

    if (!TIPOS_PERMITIDOS.includes(seleccionado.type)) {
      setError("Selecciona una fotografía JPG, JPEG o PNG.");
      evento.target.value = "";
      return;
    }

    if (seleccionado.size > TAMANO_MAXIMO) {
      setError("La fotografía no debe superar los 5 MB.");
      evento.target.value = "";
      return;
    }

    const url = URL.createObjectURL(seleccionado);

    try {
      const dimensiones = await new Promise((resolve, reject) => {
        const imagen = new Image();

        imagen.onload = () =>
          resolve({
            ancho: imagen.naturalWidth,
            alto: imagen.naturalHeight,
          });

        imagen.onerror = () =>
          reject(new Error("No se pudo leer la fotografía."));

        imagen.src = url;
      });

      if (dimensiones.ancho < 709 || dimensiones.alto < 709) {
        setError("La fotografía debe medir al menos 709 × 709 píxeles para conservar 600 PPP.");
        return;
      }

      if (dimensiones.ancho > 4000 || dimensiones.alto > 4000) {
        setError("La fotografía no debe superar 4000 × 4000 píxeles.");
        return;
      }

      setNombreArchivoOriginal(seleccionado.name);
      setImagenParaRecortar((anterior) => {
        if (anterior) URL.revokeObjectURL(anterior);
        return URL.createObjectURL(seleccionado);
      });
    } catch {
      setError("No se pudo procesar la fotografía seleccionada.");
    } finally {
      URL.revokeObjectURL(url);
      evento.target.value = "";
    }
  }

  function confirmarRecorte(archivoRecortado) {
    setArchivo(archivoRecortado);
    setImagenParaRecortar("");
    setNombreArchivoOriginal("");
    setError("");
  }

  function cancelarRecorte() {
    setImagenParaRecortar("");
    setNombreArchivoOriginal("");
  }
  function solicitarConfirmacionCarga() {
    if (!archivo || enviandoFoto) return;
    setError("");
    setConfirmarCarga(true);
  }

  async function registrarFoto() {
    if (
      !archivo ||
      enviandoFoto ||
      cargandoFoto ||
      errorConsulta ||
      estadoFoto?.puede_subir_primera_foto !== true
    ) {
      return;
    }

    setError("");
    setMensajeExito("");
    setConfirmarCarga(false);
    setEnviandoFoto(true);

    try {
      const datos = new FormData();
      datos.append("foto", archivo);

      const respuesta = await api.post("/estudiante/foto", datos, {
        headers: {
          "Content-Type": "multipart/form-data",
        },
      });

      setMensajeExito(
        respuesta.data.message || "Fotografía registrada correctamente.",
      );

      setArchivo(null);

      const estadoActualizado = await api.get("/estudiante/foto");
      setEstadoFoto(estadoActualizado.data);
      setErrorConsulta("");
    } catch (error) {
      const datosError = error.response?.data;

      const motivos = Array.isArray(datosError?.motivos)
        ? datosError.motivos
        : [];

      const erroresValidacion = datosError?.errors
        ? Object.values(datosError.errors).flat()
        : [];

      setError(
        [...motivos, ...erroresValidacion].join(" ") ||
          datosError?.message ||
          "No fue posible registrar la fotografía.",
      );

      if (error.response?.status === 409) {
        setArchivo(null);

        try {
          const estadoActualizado = await api.get("/estudiante/foto");
          setEstadoFoto(estadoActualizado.data);
        } catch {
          setErrorConsulta("No se pudo actualizar el estado de la fotografía.");
        }
      }
    } finally {
      setEnviandoFoto(false);
    }
  }

  const estilos = {
    tarjeta: {
      background: "#ffffff",
      border: "1px solid #e2e8f0",
      borderRadius: "16px",
      padding: "24px",
      marginBottom: "24px",
      boxShadow: "0 4px 14px rgba(15, 23, 42, 0.04)",
    },
    contenido: {
      display: "flex",
      flexWrap: "wrap",
      gap: "24px",
      alignItems: "flex-start",
      marginTop: "20px",
    },
    foto: {
      width: "180px",
      height: "180px",
      borderRadius: "12px",
      border: "2px dashed #cbd5e1",
      background: "#f8fafc",
      objectFit: "cover",
    },
    boton: {
      display: "inline-block",
      background: "#1d4ed8",
      color: "#ffffff",
      padding: "11px 18px",
      borderRadius: "9px",
      fontWeight: 600,
      cursor: "pointer",
      marginTop: "12px",
    },
  };

  return (
    <section style={estilos.tarjeta}>
      <h2 style={{ fontSize: "21px", marginTop: 0 }}>
        Mi fotografía de identificación
      </h2>

      <p style={{ color: "#64748b" }}>
        Selecciona una fotografía tipo carnet para tu identificación durante los
        exámenes.
      </p>

      <div style={estilos.contenido}>
        {vistaPrevia || estadoFoto?.url_foto ? (
          <img
            src={vistaPrevia || estadoFoto.url_foto}
            alt="Vista previa de la fotografía seleccionada"
            style={estilos.foto}
          />
        ) : !cargandoFoto && estadoFoto?.puede_subir_primera_foto === true ? (
          <label
            htmlFor="foto-estudiante-archivo"
            style={{
              ...estilos.foto,
              display: "flex",
              flexDirection: "column",
              alignItems: "center",
              justifyContent: "center",
              gap: "8px",
              color: "#1d4ed8",
              textAlign: "center",
              boxSizing: "border-box",
              padding: "16px",
              cursor: "pointer",
              fontWeight: 600,
            }}
          >
            <span style={{ color: "#64748b", fontWeight: 400 }}>Sin fotografía</span>
            <span>Subir foto</span>
            <input
              id="foto-estudiante-archivo"
              aria-label="Subir foto"
              type="file"
              accept=".jpg,.jpeg,.png"
              onChange={seleccionarFoto}
              style={{ display: "none" }}
            />
          </label>
        ) : (
          <div
            style={{
              ...estilos.foto,
              display: "flex",
              alignItems: "center",
              justifyContent: "center",
              color: "#64748b",
              textAlign: "center",
              boxSizing: "border-box",
              padding: "16px",
            }}
          >
            Sin fotografía
          </div>
        )}

        <div style={{ flex: 1, minWidth: "220px" }}>
          <p style={{ fontWeight: 600, marginTop: 0 }}>
            Requisitos de la fotografía
          </p>

          <ul
            style={{
              color: "#475569",
              lineHeight: 1.9,
              paddingLeft: "20px",
            }}
          >
            <li>Fotografía individual y de frente.</li>
            <li>Rostro centrado y completamente visible.</li>
            <li>Buena iluminación y enfoque.</li>
            <li>Fondo uniforme, preferentemente claro.</li>
            <li>Formato JPG, JPEG o PNG.</li>
            <li>Foto final cuadrada: exactamente 709 × 709 píxeles.</li>
            <li>Impresión: 30 × 30 mm a 600 PPP.</li>
            <li>Peso del archivo: máximo 5 MB.</li>
          </ul>

          {!cargandoFoto && !estadoFoto?.tiene_foto && estadoFoto?.puede_subir_primera_foto === true && (
            <p role="note" style={{
              color: "#854d0e",
              background: "#fef3c7",
              border: "1px solid #fcd34d",
              borderRadius: "8px",
              padding: "12px",
              fontSize: "14px",
            }}>
              Atención: solo podrás registrar tu fotografía una vez. Después de
              subirla no podrás cambiarla ni reemplazarla; revisa que cumpla las
              medidas y que sea la foto correcta.
            </p>
          )}

          {cargandoFoto ? (
            <p style={{ color: "#64748b", fontSize: "14px" }}>
              Consultando estado de la fotografía...
            </p>
          ) : errorConsulta ? (
            <p role="alert" style={{ color: "#b91c1c", fontSize: "14px" }}>
              {errorConsulta}
            </p>
          ) : estadoFoto?.tiene_foto ? (
            <div>
              <p style={{ color: "#15803d", fontWeight: 600 }}>
                Fotografía registrada
              </p>

              <p style={{ color: "#475569", fontSize: "14px" }}>
                Estado de validación:{" "}
                {estadoFoto.estado_validacion || "Sin información"}
              </p>

              <p style={{ color: "#64748b", fontSize: "13px" }}>
                La fotografía registrada no puede modificarse ni reemplazarse.
              </p>
            </div>
          ) : estadoFoto?.puede_subir_primera_foto === true ? (
            <p style={{ color: "#475569", fontSize: "14px" }}>
              Haz clic en “Subir foto” para elegir un archivo.
            </p>
          ) : (
            <p style={{ color: "#b91c1c", fontSize: "14px" }}>
              No está permitido seleccionar una fotografía en este momento.
            </p>
          )}

          {archivo && (
            <p style={{ color: "#15803d", fontSize: "14px" }}>
              Archivo seleccionado: {archivo.name}
            </p>
          )}

          {archivo &&
            !estadoFoto?.tiene_foto &&
            estadoFoto?.puede_subir_primera_foto === true && (
              <button
                type="button"
                onClick={solicitarConfirmacionCarga}
                disabled={enviandoFoto}
                style={{
                  ...estilos.boton,
                  border: "none",
                  opacity: enviandoFoto ? 0.6 : 1,
                  cursor: enviandoFoto ? "not-allowed" : "pointer",
                }}
              >
                {enviandoFoto
                  ? "Validando y registrando fotografía..."
                  : "Registrar fotografía"}
              </button>
            )}

          {mensajeExito && (
            <p role="status" style={{ color: "#15803d", fontWeight: 600 }}>
              {mensajeExito}
            </p>
          )}

          {error && (
            <p role="alert" style={{ color: "#b91c1c", fontSize: "14px" }}>
              {error}
            </p>
          )}
        </div>
      </div>
      {imagenParaRecortar && (
        <RecortadorFoto
          imagen={imagenParaRecortar}
          onConfirmar={confirmarRecorte}
          onCancelar={cancelarRecorte}
        />
      )}
      {confirmarCarga && archivo && (
        <div
          role="dialog"
          aria-modal="true"
          aria-labelledby="confirmar-foto-titulo"
          style={{
            position: "fixed",
            inset: 0,
            zIndex: 1100,
            display: "flex",
            alignItems: "center",
            justifyContent: "center",
            background: "rgba(15, 23, 42, 0.65)",
            padding: "16px",
          }}
        >
          <div style={{ background: "#fff", borderRadius: "12px", padding: "24px", maxWidth: "460px", width: "100%" }}>
            <h3 id="confirmar-foto-titulo" style={{ marginTop: 0 }}>Confirma tu fotografía</h3>
            <p>La foto se subirá una sola vez y luego no se podrá modificar ni reemplazar.</p>
            <p>Verifica que sea la correcta: 709 × 709 píxeles, 30 × 30 mm a 600 PPP, y un archivo de hasta 5 MB.</p>
            <p>Al continuar, confirmas que revisaste la foto y sus especificaciones.</p>
            <div style={{ display: "flex", justifyContent: "flex-end", gap: "10px", marginTop: "20px" }}>
              <button type="button" onClick={() => setConfirmarCarga(false)} disabled={enviandoFoto}>
                Revisar foto
              </button>
              <button type="button" onClick={registrarFoto} disabled={enviandoFoto}>
                {enviandoFoto ? "Subiendo..." : "Confirmar y subir"}
              </button>
            </div>
          </div>
        </div>
      )}
    </section>
  );
}
