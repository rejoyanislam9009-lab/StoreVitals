#!/usr/bin/env python3
"""Add clear, non-obstructive callouts to WordPress.org screenshots."""

from pathlib import Path
import argparse
from PIL import Image, ImageDraw, ImageFont

FONT_REGULAR = "/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf"
FONT_BOLD = "/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf"
PRIMARY = "#3858e9"
NAVY = "#111827"
WHITE = "#ffffff"
HIGHLIGHT = (56, 88, 233, 36)


def font(size, bold=False):
    return ImageFont.truetype(FONT_BOLD if bold else FONT_REGULAR, size)


def draw_arrow(draw, start, end, color=PRIMARY, width=5):
    draw.line((start, end), fill=color, width=width)
    sx, sy = start
    ex, ey = end
    dx, dy = ex - sx, ey - sy
    length = max((dx * dx + dy * dy) ** 0.5, 1)
    ux, uy = dx / length, dy / length
    px, py = -uy, ux
    size = 14
    base_x = ex - ux * size
    base_y = ey - uy * size
    left = (base_x + px * size * 0.55, base_y + py * size * 0.55)
    right = (base_x - px * size * 0.55, base_y - py * size * 0.55)
    draw.polygon((end, left, right), fill=color)


def callout(image, number, label, focus_box, label_box, arrow_start, arrow_end):
    overlay = Image.new("RGBA", image.size, (0, 0, 0, 0))
    draw = ImageDraw.Draw(overlay)

    x1, y1, x2, y2 = focus_box
    draw.rounded_rectangle((x1, y1, x2, y2), radius=14, fill=HIGHLIGHT, outline=PRIMARY, width=4)

    lx1, ly1, lx2, ly2 = label_box
    draw.rounded_rectangle((lx1 + 4, ly1 + 5, lx2 + 4, ly2 + 5), radius=12, fill=(0, 0, 0, 45))
    draw.rounded_rectangle((lx1, ly1, lx2, ly2), radius=12, fill=NAVY)

    badge_size = min(36, ly2 - ly1 - 12)
    bx = lx1 + 10
    by = ly1 + (ly2 - ly1 - badge_size) // 2
    draw.ellipse((bx, by, bx + badge_size, by + badge_size), fill=PRIMARY)
    draw.text((bx + badge_size / 2, by + badge_size / 2), str(number), font=font(17, True), fill=WHITE, anchor="mm")

    draw.text((bx + badge_size + 10, ly1 + (ly2 - ly1) / 2), label, font=font(18, True), fill=WHITE, anchor="lm")
    draw_arrow(draw, arrow_start, arrow_end)
    return Image.alpha_composite(image.convert("RGBA"), overlay).convert("RGB")


def annotate_overview(image):
    image = callout(image, 1, "Overall health score", (58, 188, 310, 382), (70, 176, 300, 228), (300, 214), (260, 245))
    image = callout(image, 2, "Status summary", (342, 318, 1136, 390), (825, 198, 1115, 250), (930, 250), (930, 330))
    image = callout(image, 3, "Area-by-area scores", (42, 478, 1238, 718), (870, 724, 1195, 776), (960, 724), (960, 680))
    return image


def annotate_diagnostics(image):
    image = callout(image, 1, "Search & filters", (42, 262, 1092, 338), (80, 180, 305, 232), (210, 232), (210, 280))
    image = callout(image, 2, "Severity at a glance", (44, 342, 205, 790), (870, 190, 1155, 242), (920, 242), (170, 385))
    image = callout(image, 3, "Review each finding", (198, 342, 1238, 790), (840, 724, 1160, 776), (970, 724), (1120, 675))
    return image


def annotate_reports(image):
    image = callout(image, 1, "CSV, JSON & PDF exports", (42, 272, 1210, 480), (85, 180, 390, 232), (260, 232), (260, 300))
    image = callout(image, 2, "Current scan metadata", (42, 568, 1238, 650), (825, 500, 1155, 552), (965, 552), (965, 590))
    image = callout(image, 3, "Area score preview", (42, 646, 1238, 730), (830, 734, 1155, 786), (970, 734), (970, 700))
    return image


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--directory", default="build/wporg-assets")
    args = parser.parse_args()
    root = Path(args.directory)

    jobs = {
        "screenshot-1.png": annotate_overview,
        "screenshot-2.png": annotate_diagnostics,
        "screenshot-3.png": annotate_reports,
    }
    for name, annotate in jobs.items():
        path = root / name
        if not path.is_file():
            raise SystemExit(f"Missing screenshot: {path}")
        with Image.open(path) as source:
            annotated = annotate(source.convert("RGB"))
        annotated.save(path, optimize=True)
        print(f"Annotated {name}")


if __name__ == "__main__":
    main()
