#!/usr/bin/env python3
"""Generates the abstract gradient images used by seed-content.sh."""
import math
import random
from pathlib import Path

from PIL import Image, ImageDraw, ImageFilter

OUT = Path(__file__).parent / "sample-images"
W, H = 1600, 900

# name: (background, blob colours)
PALETTES = {
    "aurora":   ("#0f172a", ["#38bdf8", "#818cf8", "#34d399"]),
    "sunset":   ("#1f1235", ["#fb7185", "#f59e0b", "#a78bfa"]),
    "meadow":   ("#0b2a22", ["#4ade80", "#fde047", "#22d3ee"]),
    "ember":    ("#2a1208", ["#f97316", "#ef4444", "#fbbf24"]),
    "lagoon":   ("#06222b", ["#2dd4bf", "#60a5fa", "#e0f2fe"]),
    "orchid":   ("#2b1038", ["#e879f9", "#818cf8", "#fda4af"]),
    "slate":    ("#e8eaf0", ["#94a3b8", "#6366f1", "#f0abfc"]),
    "citrus":   ("#fff7e0", ["#fbbf24", "#fb923c", "#a3e635"]),
}


def make(name, bg, colors, seed):
    rnd = random.Random(seed)
    img = Image.new("RGB", (W, H), bg)
    layer = Image.new("RGB", (W, H), bg)
    d = ImageDraw.Draw(layer)
    for i in range(9):
        c = colors[i % len(colors)]
        r = rnd.randint(180, 420)
        x, y = rnd.randint(0, W), rnd.randint(0, H)
        d.ellipse((x - r, y - r, x + r, y + r), fill=c)
    layer = layer.filter(ImageFilter.GaussianBlur(160))
    img = Image.blend(img, layer, 0.92)

    # a few crisp geometric accents so it reads as designed, not just a blur
    d = ImageDraw.Draw(img, "RGBA")
    for _ in range(3):
        cx, cy = rnd.randint(200, W - 200), rnd.randint(150, H - 150)
        r = rnd.randint(60, 160)
        d.ellipse((cx - r, cy - r, cx + r, cy + r), outline=(255, 255, 255, 70), width=3)
    for k in range(5):
        a = rnd.uniform(0, math.pi * 2)
        x1, y1 = rnd.randint(0, W), rnd.randint(0, H)
        d.line((x1, y1, x1 + math.cos(a) * 260, y1 + math.sin(a) * 260), fill=(255, 255, 255, 60), width=2)

    img.save(OUT / f"{name}.jpg", quality=82, optimize=True, progressive=True)


if __name__ == "__main__":
    OUT.mkdir(exist_ok=True)
    for i, (name, (bg, colors)) in enumerate(PALETTES.items()):
        make(name, bg, colors, seed=i + 7)
    print(f"wrote {len(PALETTES)} images to {OUT}")
