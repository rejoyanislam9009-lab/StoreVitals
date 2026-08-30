#!/usr/bin/env python3
"""Build premium Flow Store Check WordPress.org icon and banner assets."""

from pathlib import Path
import argparse
from PIL import Image, ImageDraw, ImageFont

DEEP = "#0b1020"
NAVY = "#111827"
INDIGO = "#4f46e5"
VIOLET = "#7c3aed"
CYAN = "#38bdf8"
MINT = "#6ee7b7"
WHITE = "#ffffff"
SOFT = "#cbd5e1"
GLASS = (255, 255, 255, 22)
GLASS_BORDER = (255, 255, 255, 50)
FONT_REGULAR = "/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf"
FONT_BOLD = "/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf"


def font(size, bold=False):
    return ImageFont.truetype(FONT_BOLD if bold else FONT_REGULAR, size)


def rgb(value):
    value = value.lstrip("#")
    return tuple(int(value[index:index + 2], 16) for index in (0, 2, 4))


def horizontal_gradient(size, first, second):
    width, height = size
    start = rgb(first)
    end = rgb(second)
    image = Image.new("RGB", size)
    pixels = image.load()
    for x in range(width):
        amount = x / max(1, width - 1)
        color = tuple(round(start[i] * (1 - amount) + end[i] * amount) for i in range(3))
        for y in range(height):
            pixels[x, y] = color
    return image


def text(draw, xy, value, size, bold=False, fill=WHITE, anchor=None):
    draw.text(xy, value, font=font(size, bold), fill=fill, anchor=anchor)


def brand_icon(size):
    image = horizontal_gradient((size, size), DEEP, INDIGO).convert("RGBA")
    mask = Image.new("L", (size, size), 0)
    ImageDraw.Draw(mask).rounded_rectangle(
        (0, 0, size - 1, size - 1), radius=int(size * .235), fill=255
    )
    rounded = Image.new("RGBA", (size, size), (0, 0, 0, 0))
    rounded.paste(image, (0, 0), mask)
    image = rounded
    draw = ImageDraw.Draw(image)

    # Soft branded glows.
    draw.ellipse(
        (-int(size * .25), -int(size * .18), int(size * .72), int(size * .78)),
        fill=(56, 189, 248, 23),
    )
    draw.ellipse(
        (int(size * .38), int(size * .22), int(size * 1.18), int(size * 1.06)),
        fill=(124, 58, 237, 48),
    )

    # Stylized F: strong enough to remain recognizable at 128px.
    stroke = max(6, int(size * .09))
    x = int(size * .29)
    y1 = int(size * .24)
    y2 = int(size * .73)
    draw.line((x, y1, x, y2), fill=WHITE, width=stroke)
    draw.line((x, y1, int(size * .67), y1), fill=WHITE, width=stroke)
    draw.line((x, int(size * .46), int(size * .57), int(size * .46)), fill=WHITE, width=stroke)

    # Flow ribbon cutting through the mark.
    ribbon = [
        (int(size * .18), int(size * .67)),
        (int(size * .30), int(size * .62)),
        (int(size * .43), int(size * .61)),
        (int(size * .56), int(size * .55)),
        (int(size * .69), int(size * .42)),
        (int(size * .79), int(size * .31)),
    ]
    draw.line(ribbon, fill=CYAN, width=max(4, int(size * .042)), joint="curve")

    # Health/check badge.
    badge_box = (
        int(size * .56), int(size * .56), int(size * .84), int(size * .84)
    )
    draw.ellipse(badge_box, fill=(16, 24, 40, 235), outline=(110, 231, 183, 235), width=max(2, int(size * .018)))
    check = [
        (int(size * .62), int(size * .70)),
        (int(size * .68), int(size * .76)),
        (int(size * .78), int(size * .64)),
    ]
    draw.line(check, fill=MINT, width=max(4, int(size * .045)), joint="curve")

    # Very subtle outline to stay crisp on white WordPress.org cards.
    draw.rounded_rectangle(
        (1, 1, size - 2, size - 2),
        radius=int(size * .235),
        outline=(255, 255, 255, 28),
        width=max(1, int(size * .008)),
    )
    return image


def banner(width, height):
    image = horizontal_gradient((width, height), DEEP, "#312e81").convert("RGBA")
    draw = ImageDraw.Draw(image)

    # Background depth and fine grid.
    draw.ellipse(
        (int(width * .56), -int(height * .72), int(width * 1.04), int(height * 1.12)),
        fill=(79, 70, 229, 50),
    )
    draw.ellipse(
        (int(width * .76), int(height * .20), int(width * 1.12), int(height * 1.25)),
        fill=(56, 189, 248, 25),
    )
    grid = max(22, int(height * .14))
    for x in range(0, width, grid):
        draw.line((x, 0, x, height), fill=(255, 255, 255, 8), width=1)
    for y in range(0, height, grid):
        draw.line((0, y, width, y), fill=(255, 255, 255, 8), width=1)

    # Left brand block.
    icon_size = int(height * .48)
    icon_x = int(width * .045)
    icon_y = int(height * .18)
    image.alpha_composite(brand_icon(icon_size), (icon_x, icon_y))

    content_x = icon_x + icon_size + int(width * .028)
    text(draw, (content_x, int(height * .17)), "Flow Store Check", int(height * .102), True)
    text(draw, (content_x, int(height * .315)), "for WooCommerce", int(height * .047), True, CYAN)
    text(draw, (content_x, int(height * .465)), "Know what needs attention before it becomes a problem.", int(height * .034), False, SOFT)

    pill_y = int(height * .64)
    pill_x = content_x
    for label in ("READ-ONLY", "PRIVATE", "ACTIONABLE"):
        bounds = draw.textbbox((0, 0), label, font=font(int(height * .027), True))
        pill_width = bounds[2] + int(height * .085)
        draw.rounded_rectangle(
            (pill_x, pill_y, pill_x + pill_width, pill_y + int(height * .095)),
            radius=int(height * .048),
            fill=(255, 255, 255, 14),
            outline=(255, 255, 255, 40),
            width=1,
        )
        text(
            draw,
            (pill_x + int(height * .042), pill_y + int(height * .027)),
            label,
            int(height * .027),
            True,
            "#e2e8f0",
        )
        pill_x += pill_width + int(height * .024)

    # Right glass dashboard card.
    card_width = int(width * .285)
    x2 = width - int(width * .04)
    x1 = x2 - card_width
    y1 = int(height * .13)
    y2 = int(height * .87)
    draw.rounded_rectangle(
        (x1, y1, x2, y2),
        radius=int(height * .055),
        fill=GLASS,
        outline=GLASS_BORDER,
        width=max(1, int(height * .004)),
    )

    label_x = x1 + int(height * .055)
    text(draw, (label_x, y1 + int(height * .055)), "STORE HEALTH", int(height * .024), True, "#a5b4fc")
    text(draw, (label_x, y1 + int(height * .13)), "88", int(height * .155), True)
    text(draw, (label_x + int(height * .16), y1 + int(height * .205)), "/100", int(height * .035), False, SOFT)

    bar_x1 = label_x
    bar_x2 = x2 - int(height * .055)
    bar_y = y1 + int(height * .37)
    draw.rounded_rectangle(
        (bar_x1, bar_y, bar_x2, bar_y + int(height * .025)),
        radius=int(height * .013),
        fill=(255, 255, 255, 24),
    )
    draw.rounded_rectangle(
        (bar_x1, bar_y, bar_x1 + int((bar_x2 - bar_x1) * .88), bar_y + int(height * .025)),
        radius=int(height * .013),
        fill=CYAN,
    )

    rows = (
        ("Catalog", "Healthy", MINT),
        ("Checkout", "Passed", MINT),
        ("Orders", "2 notices", "#fbbf24"),
    )
    row_y = bar_y + int(height * .09)
    for title, value, color in rows:
        draw.ellipse(
            (bar_x1, row_y + int(height * .012), bar_x1 + int(height * .026), row_y + int(height * .038)),
            fill=color,
        )
        text(draw, (bar_x1 + int(height * .05), row_y), title, int(height * .029), True, "#f8fafc")
        text(draw, (bar_x2, row_y), value, int(height * .026), False, "#cbd5e1", "ra")
        row_y += int(height * .078)

    return image.convert("RGB")


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--output", default="build/wporg-assets")
    args = parser.parse_args()
    output = Path(args.output)
    output.mkdir(parents=True, exist_ok=True)

    for size in (128, 256):
        brand_icon(size).save(output / f"icon-{size}x{size}.png", optimize=True)
    banner(772, 250).save(output / "banner-772x250.png", optimize=True)
    banner(1544, 500).save(output / "banner-1544x500.png", optimize=True)


if __name__ == "__main__":
    main()
