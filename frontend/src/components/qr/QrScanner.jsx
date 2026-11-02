import { useEffect, useRef, useState } from "react";
import { BrowserCodeReader, BrowserQRCodeReader } from "@zxing/browser";

export default function QrScanner({ onResult, onError }) {
  const videoRef = useRef(null);
  const controlsRef = useRef(null);
  const onResultRef = useRef(onResult);
  const onErrorRef = useRef(onError);

  const bloqueadoRef = useRef(false);
  const ultimaLecturaRef = useRef({ codigo: "", tiempo: 0 });
  const iniciarRef = useRef(null);
  const cambiarRef = useRef(null);
  const linternaRef = useRef(null);

  const dispositivosRef = useRef([]);
  const dispositivoActualRef = useRef("");
  const encendidaRef = useRef(false);

  const [estado, setEstado] = useState("Iniciando cámara...");
  const [pausado, setPausado] = useState(false);
  const [iniciando, setIniciando] = useState(true);
  const [cantidadCamaras, setCantidadCamaras] = useState(0);
  const [tieneLinterna, setTieneLinterna] = useState(false);
  const [linternaEncendida, setLinternaEncendida] = useState(false);

  useEffect(() => {
    onResultRef.current = onResult;
    onErrorRef.current = onError;
  }, [onResult, onError]);

  useEffect(() => {
    let cancelado = false;
    let solicitudActual = 0;

    const detenerCamara = () => {
      controlsRef.current?.stop();
      controlsRef.current = null;

      const video = videoRef.current;

      if (video?.srcObject) {
        video.srcObject.getTracks().forEach((track) => track.stop());
        video.srcObject = null;
      }

      encendidaRef.current = false;
      setLinternaEncendida(false);
      setTieneLinterna(false);
    };

    const iniciarCamara = async (deviceId = "") => {
      const solicitud = ++solicitudActual;

      detenerCamara();
      bloqueadoRef.current = false;
      setPausado(false);
      setIniciando(true);

      if (!navigator.mediaDevices?.getUserMedia) {
        const error = new Error("La cámara requiere HTTPS o localhost.");
        setEstado(error.message);
        setIniciando(false);
        setPausado(true);
        onErrorRef.current?.(error);
        return;
      }

      setEstado("Solicitando acceso a la cámara...");

      try {
        const dispositivos = await BrowserCodeReader.listVideoInputDevices();

        if (cancelado || solicitud !== solicitudActual) return;

        dispositivosRef.current = dispositivos;
        setCantidadCamaras(dispositivos.length);

        const camaraTrasera = dispositivos.find((dispositivo) =>
          /back|rear|environment|trasera/i.test(dispositivo.label),
        );

        const seleccionada = deviceId
          ? dispositivos.find(
              (dispositivo) => dispositivo.deviceId === deviceId,
            )
          : camaraTrasera;

        const lector = new BrowserQRCodeReader();

        const controles = await lector.decodeFromConstraints(
          {
            audio: false,
            video: seleccionada
              ? { deviceId: { exact: seleccionada.deviceId } }
              : { facingMode: { ideal: "environment" } },
          },
          videoRef.current,
          (resultado) => {
            if (
              cancelado ||
              solicitud !== solicitudActual ||
              !resultado ||
              bloqueadoRef.current
            ) {
              return;
            }

            const codigo = resultado.getText();
            const ahora = Date.now();
            const ultima = ultimaLecturaRef.current;

            if (codigo === ultima.codigo && ahora - ultima.tiempo < 2000) {
              return;
            }

            ultimaLecturaRef.current = { codigo, tiempo: ahora };
            bloqueadoRef.current = true;

            if (navigator.vibrate) {
              navigator.vibrate(100);
            }

            controles.stop();
            controlsRef.current = null;

            const video = videoRef.current;

            if (video?.srcObject) {
              video.srcObject.getTracks().forEach((track) => track.stop());
              video.srcObject = null;
            }

            encendidaRef.current = false;
            setLinternaEncendida(false);
            setTieneLinterna(false);
            setPausado(true);
            setIniciando(false);
            setEstado("QR detectado. Escáner en pausa.");

            onResultRef.current?.(codigo);
          },
        );

        if (
          cancelado ||
          solicitud !== solicitudActual ||
          bloqueadoRef.current
        ) {
          controles.stop();
          return;
        }

        controlsRef.current = controles;

        const track = videoRef.current?.srcObject?.getVideoTracks()[0];

        dispositivoActualRef.current =
          track?.getSettings?.().deviceId || seleccionada?.deviceId || "";

        const capacidades = track?.getCapabilities?.();

        setTieneLinterna(capacidades?.torch === true);
        setIniciando(false);
        setEstado("Cámara activa. Coloca el QR dentro del marco.");
      } catch (error) {
        if (cancelado || solicitud !== solicitudActual) return;

        detenerCamara();
        setIniciando(false);
        setPausado(true);

        if (
          error.name === "NotAllowedError" ||
          error.name === "PermissionDeniedError"
        ) {
          setEstado(
            "Acceso a la cámara denegado. Permite el uso de la cámara en la configuración del navegador.",
          );
        } else if (
          error.name === "NotFoundError" ||
          error.name === "DevicesNotFoundError"
        ) {
          setEstado("No se encontró ninguna cámara disponible.");
        } else if (
          error.name === "NotReadableError" ||
          error.name === "TrackStartError"
        ) {
          setEstado(
            "No se pudo utilizar la cámara. Comprueba que no esté ocupada por otra aplicación.",
          );
        } else {
          setEstado("No se pudo iniciar la cámara.");
        }

        onErrorRef.current?.(error);
      }
    };

    const cambiarCamara = () => {
      const dispositivos = dispositivosRef.current;

      if (dispositivos.length < 2) return;

      const indice = dispositivos.findIndex(
        (dispositivo) => dispositivo.deviceId === dispositivoActualRef.current,
      );

      const siguienteIndice =
        indice < 0 ? 0 : (indice + 1) % dispositivos.length;

      iniciarCamara(dispositivos[siguienteIndice].deviceId);
    };

    const alternarLinterna = async () => {
      const track = videoRef.current?.srcObject?.getVideoTracks()[0];

      if (!track || track.readyState !== "live") return;
      if (track.getCapabilities?.().torch !== true) return;

      const nuevoEstado = !encendidaRef.current;

      try {
        await track.applyConstraints({
          advanced: [{ torch: nuevoEstado }],
        });

        encendidaRef.current = nuevoEstado;
        setLinternaEncendida(nuevoEstado);
      } catch (error) {
        onErrorRef.current?.(error);
      }
    };

    iniciarRef.current = iniciarCamara;
    cambiarRef.current = cambiarCamara;
    linternaRef.current = alternarLinterna;

    iniciarCamara();

    const manejarVisibilidad = () => {
      if (document.hidden) {
        ++solicitudActual;
        detenerCamara();
        bloqueadoRef.current = true;
        setIniciando(false);
        setPausado(true);
        setEstado("Cámara detenida al cambiar de pestaña.");
      }
    };

    document.addEventListener("visibilitychange", manejarVisibilidad);

    return () => {
      cancelado = true;
      ++solicitudActual;

      iniciarRef.current = null;
      cambiarRef.current = null;
      linternaRef.current = null;

      document.removeEventListener("visibilitychange", manejarVisibilidad);
      detenerCamara();
    };
  }, []);

  return (
    <div className="mx-auto" style={{ maxWidth: 480 }}>
      <div
        className="position-relative overflow-hidden rounded bg-dark"
        style={{ aspectRatio: "4 / 3" }}
      >
        <video
          ref={videoRef}
          autoPlay
          muted
          playsInline
          className="w-100 h-100"
          style={{ objectFit: "cover" }}
        />

        <div
          className="position-absolute top-50 start-50 translate-middle"
          style={{
            width: "70%",
            aspectRatio: "1",
            border: "3px solid #28a745",
            borderRadius: 16,
            boxShadow: "0 0 0 999px rgba(0, 0, 0, 0.15)",
            pointerEvents: "none",
          }}
        />
      </div>

      <p className="text-center text-muted mt-3">{estado}</p>

      <div className="d-flex justify-content-center flex-wrap gap-2">
        {pausado && (
          <button
            type="button"
            className="btn btn-primary"
            onClick={() => iniciarRef.current?.()}
            disabled={iniciando}
          >
            <i className="bi bi-arrow-clockwise me-2" />
            Reactivar cámara
          </button>
        )}

        {cantidadCamaras > 1 && !pausado && (
          <button
            type="button"
            className="btn btn-outline-primary"
            onClick={() => cambiarRef.current?.()}
            disabled={iniciando}
          >
            <i className="bi bi-arrow-repeat me-2" />
            Cambiar cámara
          </button>
        )}

        {tieneLinterna && !pausado && (
          <button
            type="button"
            className={
              linternaEncendida ? "btn btn-warning" : "btn btn-outline-warning"
            }
            onClick={() => linternaRef.current?.()}
            disabled={iniciando}
          >
            <i className="bi bi-lightbulb me-2" />
            {linternaEncendida ? "Apagar linterna" : "Encender linterna"}
          </button>
        )}
      </div>
    </div>
  );
}
