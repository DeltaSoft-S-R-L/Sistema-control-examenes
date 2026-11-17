
import { useCallback, useEffect, useState } from "react";
import Cropper from "react-easy-crop";

const ANCHO_SALIDA = 709;
const ALTO_SALIDA = 709;
const DPI_SALIDA = 600;
const MINIMO_PIXELES = 709;
const TAMANO_MAXIMO = 5 * 1024 * 1024;

function cargarImagen(url) {
  return new Promise((resolve, reject) => {
    const imagen = new Image();
    imagen.onload = () => resolve(imagen);
    imagen.onerror = () => reject(new Error("No se pudo leer la imagen."));
    imagen.src = url;
  });
}

async function generarRecorte(url, area) {
  const imagen = await cargarImagen(url);

  // Comprobamos la resolución real del área seleccionada.
  if (
    area.width < MINIMO_PIXELES ||
    area.height < MINIMO_PIXELES
  ) {
    throw new Error(
      "El recorte debe contener al menos 709 × 709 píxeles originales. " +
      "Reduce el zoom o selecciona una fotografía de mayor resolución."
    );
  }

  const canvas = document.createElement("canvas");
  canvas.width = ANCHO_SALIDA;
  canvas.height = ALTO_SALIDA;

  const contexto = canvas.getContext("2d");
  if (!contexto) {
    throw new Error("No se pudo preparar el recorte.");
  }

  contexto.imageSmoothingEnabled = true;
  contexto.imageSmoothingQuality = "high";

  contexto.drawImage(
    imagen,
    area.x,
    area.y,
    area.width,
    area.height,
    0,
    0,
    ANCHO_SALIDA,
    ALTO_SALIDA
  );

  const blob = await new Promise((resolve) => {
    canvas.toBlob(resolve, "image/jpeg", 0.92);
  });

  if (!blob) {
    throw new Error("No se pudo generar la fotografía recortada.");
  }

  const foto600Ppp = await agregarDensidadJpeg(blob, DPI_SALIDA);

  if (foto600Ppp.size > TAMANO_MAXIMO) {
    throw new Error("La fotografía recortada supera los 5 MB.");
  }

  return new File([foto600Ppp], "fotografia-carnet.jpg", {
    type: "image/jpeg",
    lastModified: Date.now(),
  });
}

export async function agregarDensidadJpeg(blob, dpi) {
  const bytes = new Uint8Array(await blob.arrayBuffer());
  let posicion = 2;
  let encontrado = false;

  while (posicion + 4 < bytes.length && bytes[posicion] === 0xff) {
    const marcador = bytes[posicion + 1];
    if (marcador === 0xda || marcador === 0xd9) break;

    const longitud = (bytes[posicion + 2] << 8) | bytes[posicion + 3];
    if (longitud < 2 || posicion + 2 + longitud > bytes.length) break;

    const esJfif = marcador === 0xe0
      && longitud >= 16
      && bytes[posicion + 4] === 0x4a
      && bytes[posicion + 5] === 0x46
      && bytes[posicion + 6] === 0x49
      && bytes[posicion + 7] === 0x46
      && bytes[posicion + 8] === 0x00;

    if (esJfif) {
      bytes[posicion + 11] = 1;
      bytes[posicion + 12] = (dpi >> 8) & 0xff;
      bytes[posicion + 13] = dpi & 0xff;
      bytes[posicion + 14] = (dpi >> 8) & 0xff;
      bytes[posicion + 15] = dpi & 0xff;
      encontrado = true;
      break;
    }

    posicion += 2 + longitud;
  }

  if (encontrado) return new Blob([bytes], { type: "image/jpeg" });

  const jfif = new Uint8Array([
    0xff, 0xe0, 0x00, 0x10,
    0x4a, 0x46, 0x49, 0x46, 0x00, 0x01, 0x01, 0x01,
    (dpi >> 8) & 0xff, dpi & 0xff,
    (dpi >> 8) & 0xff, dpi & 0xff,
    0x00, 0x00,
  ]);
  const jpeg = new Uint8Array(bytes.length + jfif.length);
  jpeg.set(bytes.slice(0, 2), 0);
  jpeg.set(jfif, 2);
  jpeg.set(bytes.slice(2), 2 + jfif.length);
  return new Blob([jpeg], { type: "image/jpeg" });
}

export default function RecortadorFoto({
  imagen,
  onConfirmar,
  onCancelar,
}) {
  const [recorte, setRecorte] = useState({ x: 0, y: 0 });
  const [zoom, setZoom] = useState(1);
  const [areaPixeles, setAreaPixeles] = useState(null);
  const [procesando, setProcesando] = useState(false);
  const [error, setError] = useState("");

  const alCompletarRecorte = useCallback((_, area) => {
    setAreaPixeles(area);
  }, []);

  useEffect(() => {
    function manejarTecla(evento) {
      if (evento.key === "Escape" && !procesando) {
        onCancelar();
      }
    }

    window.addEventListener("keydown", manejarTecla);
    return () => window.removeEventListener("keydown", manejarTecla);
  }, [onCancelar, procesando]);

  async function confirmar() {
    if (!areaPixeles || procesando) return;

    setProcesando(true);
    setError("");

    try {
      const archivo = await generarRecorte(imagen, areaPixeles);
      onConfirmar(archivo);
    } catch (e) {
      setError(e.message || "No se pudo recortar la fotografía.");
    } finally {
      setProcesando(false);
    }
  }

  return (
    <div
      role="dialog"
      aria-modal="true"
      aria-label="Recortar fotografía de identificación"
      style={{
        position: "fixed",
        inset: 0,
        zIndex: 1000,
        background: "rgba(15, 23, 42, 0.8)",
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        padding: "16px",
      }}
    >
      <div
        style={{
          background: "#fff",
          borderRadius: "16px",
          width: "100%",
          maxWidth: "520px",
          padding: "20px",
          boxSizing: "border-box",
        }}
      >
        <h2 style={{ marginTop: 0, fontSize: "20px" }}>
          Ajustar fotografía de identificación
        </h2>

        <p style={{ color: "#64748b", fontSize: "14px" }}>
          Centra tu rostro dentro del marco. Puedes mover la imagen
          y ajustar el zoom antes de confirmar.
        </p>

        <div
          style={{
            position: "relative",
            width: "100%",
            height: "350px",
            background: "#111827",
            borderRadius: "10px",
            overflow: "hidden",
          }}
        >
          <Cropper
            image={imagen}
            crop={recorte}
            zoom={zoom}
            aspect={1}
            onCropChange={setRecorte}
            onZoomChange={setZoom}
            onCropComplete={alCompletarRecorte}
            minZoom={1}
            maxZoom={3}
            showGrid
          />
        </div>

        <label
          htmlFor="zoom-fotografia"
          style={{ display: "block", marginTop: "20px" }}
        >
          Zoom: {zoom.toFixed(1)}×
        </label>

        <input
          id="zoom-fotografia"
          type="range"
          min="1"
          max="3"
          step="0.1"
          value={zoom}
          onChange={(e) => setZoom(Number(e.target.value))}
          style={{ width: "100%", marginTop: "10px" }}
        />

        {error && (
          <p role="alert" style={{ color: "#b91c1c" }}>
            {error}
          </p>
        )}

        <p style={{ fontSize: "13px", color: "#64748b" }}>
          La fotografía final será de 709 × 709 píxeles (3 × 3 cm a 600 PPP).
          El recorte debe conservar al menos esa resolución original.
        </p>

        <div
          style={{
            display: "flex",
            justifyContent: "flex-end",
            gap: "12px",
            marginTop: "20px",
            flexWrap: "wrap",
          }}
        >
          <button
            type="button"
            onClick={onCancelar}
            disabled={procesando}
            style={{
              padding: "10px 16px",
              borderRadius: "8px",
              border: "1px solid #cbd5e1",
              background: "#fff",
              cursor: "pointer",
            }}
          >
            Cancelar
          </button>

          <button
            type="button"
            onClick={confirmar}
            disabled={procesando || !areaPixeles}
            style={{
              padding: "10px 16px",
              borderRadius: "8px",
              border: "none",
              background: "#1d4ed8",
              color: "#fff",
              fontWeight: 600,
              cursor: procesando ? "wait" : "pointer",
            }}
          >
            {procesando ? "Preparando..." : "Confirmar recorte"}
          </button>
        </div>
      </div>
    </div>
  );
}
