
import json
import math
import sys
from pathlib import Path

import cv2
import mediapipe as mp


BASE = Path(__file__).resolve().parent
MODELO = BASE / "models" / "blaze_face_short_range.tflite"

# Umbrales provisionales: deben calibrarse con más fotografías.
BRILLO_MINIMO = 55
BRILLO_MAXIMO = 220
NITIDEZ_MINIMA = 30.0
INCLINACION_MAXIMA = 10.0
DESVIACION_NARIZ_MAXIMA = 0.15


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

    # Solo indica que superó los controles parciales.
    resultado["aprobada"] = (
        len(resultado["motivos"]) == 0
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
