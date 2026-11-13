
import json
import math
import sys
from pathlib import Path

import cv2

import sys
import types

# El analizador trabaja exclusivamente con imágenes.
# Evita inicializar dispositivos de audio durante la carga de MediaPipe.
if "sounddevice" not in sys.modules:
    sys.modules["sounddevice"] = types.ModuleType("sounddevice")

import mediapipe as mp


BASE = Path(__file__).resolve().parent
MODELO = BASE / "models" / "blaze_face_short_range.tflite"
MODELO_LANDMARKER = BASE / "models" / "face_landmarker.task"

# Umbrales provisionales: deben calibrarse con más fotografías.
BRILLO_MINIMO = 55
BRILLO_MAXIMO = 220
NITIDEZ_MINIMA = 30.0
INCLINACION_MAXIMA = 10.0
DESVIACION_NARIZ_MAXIMA = 0.15
# Margen mínimo entre el rostro detectado y los bordes.
MARGEN_ROSTRO_MINIMO = 0.03
# Límites provisionales de orientación tridimensional.
YAW_MAXIMO = 15.0
PITCH_MAXIMO = 15.0
ROLL_MAXIMO = 10.0


def analizar_fotografia(ruta_imagen):
    resultado = {
        "aprobada": False,
        "validacion_completa": False,
        "motivos": [],
        "cantidad_rostros": 0,
    }

    ruta = Path(ruta_imagen)

    if not ruta.is_file():
        resultado["motivos"].append(
            "No se encontró la fotografía indicada."
        )
        return resultado

    imagen = cv2.imread(str(ruta))

    if imagen is None:
        resultado["motivos"].append(
            "No se pudo procesar la fotografía."
        )
        return resultado

    alto, ancho = imagen.shape[:2]

    if ancho < 400 or alto < 400:
        resultado["motivos"].append(
            "La fotografía debe medir al menos 400 x 400 píxeles."
        )

    imagen_rgb = cv2.cvtColor(
        imagen, cv2.COLOR_BGR2RGB
    )

    imagen_mp = mp.Image(
        image_format=mp.ImageFormat.SRGB,
        data=imagen_rgb
    )

    opciones = mp.tasks.vision.FaceDetectorOptions(
        base_options=mp.tasks.BaseOptions(
            model_asset_path=str(MODELO)
        ),
        min_detection_confidence=0.5
    )

    with mp.tasks.vision.FaceDetector.create_from_options(
        opciones
    ) as detector:
        detecciones = detector.detect(imagen_mp)

    rostros = detecciones.detections
    resultado["cantidad_rostros"] = len(rostros)

    if len(rostros) == 0:
        resultado["motivos"].append(
            "No se detectó ningún rostro en la fotografía."
        )
        return resultado

    if len(rostros) > 1:
        resultado["motivos"].append(
            "Se detectó más de un rostro en la fotografía."
        )
        return resultado

    rostro = rostros[0]
    caja = rostro.bounding_box

    # Posición y tamaño del rostro
    centro_x = (
        caja.origin_x + caja.width / 2
    ) / ancho

    centro_y = (
        caja.origin_y + caja.height / 2
    ) / alto

    proporcion_ancho = caja.width / ancho
    proporcion_alto = caja.height / alto

    resultado["metricas"] = {
        "centro_x": round(centro_x, 3),
        "centro_y": round(centro_y, 3),
        "proporcion_ancho": round(proporcion_ancho, 3),
        "proporcion_alto": round(proporcion_alto, 3)
    }
        # Comprobar que la caja facial no toque los bordes.
    margen_izquierdo = caja.origin_x / ancho
    margen_superior = caja.origin_y / alto

    margen_derecho = (
        ancho - (caja.origin_x + caja.width)
    ) / ancho

    margen_inferior = (
        alto - (caja.origin_y + caja.height)
    ) / alto

    resultado["metricas"]["margenes_rostro"] = {
        "izquierdo": round(margen_izquierdo, 3),
        "derecho": round(margen_derecho, 3),
        "superior": round(margen_superior, 3),
        "inferior": round(margen_inferior, 3)
    }

    if min(
        margen_izquierdo,
        margen_derecho,
        margen_superior,
        margen_inferior
    ) < MARGEN_ROSTRO_MINIMO:
        resultado["motivos"].append(
            "El rostro está demasiado cerca de los bordes "
            "de la fotografía. Aléjate un poco de la cámara."
        )

    if not (0.30 <= centro_x <= 0.70):
        resultado["motivos"].append(
            "El rostro debe estar centrado horizontalmente."
        )

    if not (0.25 <= centro_y <= 0.65):
        resultado["motivos"].append(
            "El rostro debe estar ubicado correctamente."
        )

    if proporcion_ancho < 0.20:
        resultado["motivos"].append(
            "El rostro aparece demasiado pequeño."
        )

    if proporcion_alto < 0.25:
        resultado["motivos"].append(
            "El rostro aparece demasiado alejado."
        )
            # Evitar encuadres excesivamente cercanos.
    if proporcion_ancho > 0.65:
        resultado["motivos"].append(
            "El rostro ocupa demasiado ancho de la fotografía. "
            "Aléjate un poco de la cámara."
        )

    if proporcion_alto > 0.70:
        resultado["motivos"].append(
            "El rostro ocupa demasiado alto de la fotografía. "
            "Debe quedar espacio alrededor de la cabeza."
        )

    # Orientación aproximada mediante puntos faciales
    puntos = rostro.keypoints

    if len(puntos) < 3:
        resultado["motivos"].append(
            "No se pudo evaluar la orientación del rostro."
        )
    else:
        ojo1 = puntos[0]
        ojo2 = puntos[1]
        nariz = puntos[2]

        dx = (ojo2.x - ojo1.x) * ancho
        dy = (ojo2.y - ojo1.y) * alto

        distancia_ojos = math.hypot(dx, dy)

        if distancia_ojos <= 1 or dx <= 0:
            resultado["motivos"].append(
                "No se pudo evaluar correctamente la posición de los ojos."
            )
        else:
            inclinacion = abs(
                math.degrees(math.atan2(dy, dx))
            )

            # Centro de los ojos en coordenadas de píxeles
            centro_ojos_x = (
                (ojo1.x + ojo2.x) / 2
            ) * ancho

            centro_ojos_y = (
                (ojo1.y + ojo2.y) / 2
            ) * alto

            # Posición de la nariz en píxeles
            nariz_x = nariz.x * ancho
            nariz_y = nariz.y * alto

            # Desviación respecto al eje de los ojos,
            # compensando la inclinación lateral de la cabeza.
            desviacion_nariz = abs(
                (nariz_x - centro_ojos_x) * dx
                + (nariz_y - centro_ojos_y) * dy
            ) / (distancia_ojos ** 2)

            resultado["orientacion"] = {
                "inclinacion_grados": round(
                    inclinacion, 2
                ),
                "desviacion_nariz_porcentaje": round(
                    desviacion_nariz * 100, 2
                )
            }

            if inclinacion > INCLINACION_MAXIMA:
                resultado["motivos"].append(
                    "La cabeza está demasiado inclinada."
                )

            if desviacion_nariz > DESVIACION_NARIZ_MAXIMA:
                resultado["motivos"].append(
                    "El rostro parece estar girado. "
                    "Mira de frente a la cámara."
                )
    # Analizar orientación tridimensional con Face Landmarker.
    opciones_landmarker = mp.tasks.vision.FaceLandmarkerOptions(
        base_options=mp.tasks.BaseOptions(
            model_asset_path=str(MODELO_LANDMARKER)
        ),
        running_mode=mp.tasks.vision.RunningMode.IMAGE,
        num_faces=2,
        output_facial_transformation_matrixes=True
    )

    with mp.tasks.vision.FaceLandmarker.create_from_options(
        opciones_landmarker
    ) as landmarker:
        resultado_landmarker = landmarker.detect(imagen_mp)

    cantidad_landmarker = len(resultado_landmarker.face_landmarks)

    resultado["cantidad_rostros_landmarker"] = cantidad_landmarker

    if cantidad_landmarker != 1:
        resultado["motivos"].append(
            "No se pudo confirmar un único rostro con el análisis facial detallado."
        )
    elif not resultado_landmarker.facial_transformation_matrixes:
        resultado["motivos"].append(
            "No se pudo calcular la orientación tridimensional del rostro."
        )
    else:
        matriz = resultado_landmarker.facial_transformation_matrixes[0]

        pitch = math.degrees(
            math.atan2(matriz[2, 1], matriz[2, 2])
        )

        yaw = math.degrees(
            math.asin(
                max(-1.0, min(1.0, -matriz[2, 0]))
            )
        )

        roll = math.degrees(
            math.atan2(matriz[1, 0], matriz[0, 0])
        )

        resultado["orientacion_3d"] = {
            "pitch_grados": round(pitch, 2),
            "yaw_grados": round(yaw, 2),
            "roll_grados": round(roll, 2)
        }
                # Comprobar orientación tridimensional del rostro.
        if abs(yaw) > YAW_MAXIMO:
            resultado["motivos"].append(
                "El rostro está demasiado girado hacia un lado. "
                "Mira directamente a la cámara."
            )

        if abs(pitch) > PITCH_MAXIMO:
            resultado["motivos"].append(
                "La cabeza está demasiado inclinada hacia "
                "arriba o abajo. Mira de frente a la cámara."
            )

        if abs(roll) > ROLL_MAXIMO:
            resultado["motivos"].append(
                "La cabeza está demasiado inclinada lateralmente. "
                "Mantén la cabeza recta."
            )

    # Extraer la región facial
    x1 = max(0, caja.origin_x)
    y1 = max(0, caja.origin_y)
    x2 = min(ancho, caja.origin_x + caja.width)
    y2 = min(alto, caja.origin_y + caja.height)

    if x2 <= x1 or y2 <= y1:
        resultado["motivos"].append(
            "No se pudo obtener la región del rostro."
        )
        return resultado

    region_rostro = imagen[y1:y2, x1:x2]

    if region_rostro.size == 0:
        resultado["motivos"].append(
            "La región del rostro está vacía."
        )
        return resultado

    gris = cv2.cvtColor(
        region_rostro, cv2.COLOR_BGR2GRAY
    )

    # Iluminación del rostro
    iluminacion = float(gris.mean())

    # Nitidez mediante varianza del Laplaciano
    nitidez = float(
        cv2.Laplacian(
            gris, cv2.CV_64F
        ).var()
    )

    resultado["calidad"] = {
        "iluminacion_rostro": round(iluminacion, 2),
        "nitidez_rostro": round(nitidez, 2)
    }

    if iluminacion < BRILLO_MINIMO:
        resultado["motivos"].append(
            "La fotografía está demasiado oscura."
        )

    if iluminacion > BRILLO_MAXIMO:
        resultado["motivos"].append(
            "La fotografía tiene demasiada iluminación."
        )

    if nitidez < NITIDEZ_MINIMA:
        resultado["motivos"].append(
            "La fotografía parece estar borrosa o desenfocada."
        )

    
    # El análisis llegó hasta el final de los controles.
    resultado["validacion_completa"] = True

    # La fotografía se aprueba únicamente si no hay rechazos.
    resultado["aprobada"] = (
        resultado["validacion_completa"]
        and len(resultado["motivos"]) == 0
    )

    return resultado



def main():
    if len(sys.argv) != 2:
        print(json.dumps({
            "aprobada": False,
            "validacion_completa": False,
            "motivos": [
                "Debes proporcionar la ruta de una fotografía."
            ],
            "cantidad_rostros": 0
        }, ensure_ascii=False))
        sys.exit(1)

    try:
        resultado = analizar_fotografia(sys.argv[1])

        print(json.dumps(
            resultado,
            ensure_ascii=False
        ))

    except Exception:
        print(json.dumps({
            "aprobada": False,
            "validacion_completa": False,
            "motivos": [
                "Ocurrió un error al analizar la fotografía."
            ],
            "cantidad_rostros": 0
        }, ensure_ascii=False))
        sys.exit(1)


if __name__ == "__main__":
    main()
