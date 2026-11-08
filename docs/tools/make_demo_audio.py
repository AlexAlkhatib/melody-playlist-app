#!/usr/bin/env python3
"""Génère les fichiers audio de démonstration du catalogue Melody.

Aucune dépendance externe : synthèse additive simple (mélodie + basse + kick)
exportée en WAV 8 bits mono 11 025 Hz, volontairement légère (≈130 Ko/titre).

    python3 docs/tools/make_demo_audio.py
"""

from __future__ import annotations

import math
import os
import random
import struct
import wave

RATE = 11025
DURATION = 12.0
OUT_DIR = os.path.join(os.path.dirname(__file__), "..", "..", "public", "media", "morceaux")

# Douze ambiances : (gamme en demi-tons depuis la tonique, tempo BPM, tonique Hz)
TRACKS = [
    ([0, 4, 7, 12, 7, 4], 96, 220.00),    # Rock
    ([0, 2, 4, 7, 9, 7], 104, 261.63),    # Pop
    ([0, 3, 5, 7, 10, 7], 88, 196.00),    # Jazz
    ([0, 7, 12, 7, 3, 10], 124, 174.61),  # Électro
    ([0, 3, 7, 10, 12, 7], 92, 146.83),   # Hip-Hop
    ([0, 4, 7, 11, 7, 4], 72, 329.63),    # Classique
    ([0, 5, 7, 12, 7, 5], 100, 246.94),   # Rock
    ([0, 2, 9, 7, 4, 2], 108, 293.66),    # Pop
    ([0, 3, 6, 10, 6, 3], 84, 220.00),    # Jazz
    ([0, 7, 10, 12, 10, 7], 128, 155.56), # Électro
    ([0, 3, 5, 7, 10, 5], 90, 130.81),    # Hip-Hop
    ([0, 4, 7, 12, 11, 7], 66, 392.00),   # Classique
]


def note(freq: float, t: float, duration: float) -> float:
    """Note enrichie : fondamentale + harmoniques, enveloppe percussive douce."""
    if t < 0 or t > duration:
        return 0.0
    env = math.exp(-3.2 * t / duration)
    value = (
        0.62 * math.sin(2 * math.pi * freq * t)
        + 0.24 * math.sin(2 * math.pi * freq * 2 * t)
        + 0.10 * math.sin(2 * math.pi * freq * 3 * t)
    )
    return value * env


def kick(t: float) -> float:
    """Grosse caisse synthétique (sinus descendant)."""
    if t < 0 or t > 0.18:
        return 0.0
    freq = 120 * math.exp(-18 * t) + 42
    return 0.5 * math.sin(2 * math.pi * freq * t) * math.exp(-14 * t)


def render(index: int, scale: list[int], bpm: float, root: float) -> bytes:
    rng = random.Random(1000 + index)
    beat = 60.0 / bpm
    frames = bytearray()
    total = int(RATE * DURATION)

    for n in range(total):
        t = n / RATE
        beat_index = int(t / beat)
        position = t - beat_index * beat

        step = scale[beat_index % len(scale)]
        freq = root * (2 ** (step / 12))

        sample = 0.42 * note(freq, position, beat * 1.6)
        sample += 0.18 * note(root / 2, position, beat * 2.2)  # basse
        if beat_index % 2 == 0:
            sample += kick(position)

        # Léger vibrato pour éviter la monotonie
        sample *= 1.0 + 0.03 * math.sin(2 * math.pi * 5.5 * t)

        # Fondu d'entrée/sortie
        fade = min(1.0, t / 0.35, (DURATION - t) / 0.8)
        sample *= max(0.0, fade)

        value = int(128 + 110 * max(-1.0, min(1.0, sample)))
        frames.append(max(0, min(255, value)))

    return bytes(frames)


def main() -> None:
    os.makedirs(OUT_DIR, exist_ok=True)
    for i, (scale, bpm, root) in enumerate(TRACKS, start=1):
        path = os.path.abspath(os.path.join(OUT_DIR, f"demo-{i:02d}.wav"))
        with wave.open(path, "wb") as handle:
            handle.setnchannels(1)
            handle.setsampwidth(1)
            handle.setframerate(RATE)
            handle.writeframes(render(i, scale, bpm, root))
        print(f"{path} ({os.path.getsize(path) // 1024} Ko)")


if __name__ == "__main__":
    main()
