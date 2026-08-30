#!/usr/bin/env python3
"""Build premium Flow Store Check WordPress.org icon and banner assets."""

from pathlib import Path
import argparse
from PIL import Image, ImageDraw, ImageFont

DEEP = "#0b1020"
INDIGO = "#4338ca"
VIOLET = "#7c3aed"
CYAN = "#38bdf8"
MINT = "#6ee7b7"
WHITE = "#ffffff"
SOFT = "#cbd5e1"
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
    """Flow + store + check mark, optimized for WordPress.org 128/256px cards."""
    background = horizontal_gradient((size, size), DEEP, INDIGO).convert("RGBA")
    mask = Image.new("L", (size, size), 0)
    ImageDraw.Draw(mask).rounded_rectangle(
        (0, 0, size - 1, size - 1), radius=int(size * .23), fill=255
    )
    image = Image.new("RGBA", (size, size), (0, 0, 0, 0))
    image.paste(background, (0, 0), mask)
    draw = ImageDraw.Draw(image)

    # Soft depth without visual clutter.
    draw.ellipse(
        (-int(size * .26), -int(size * .20), int(size * .76), int(size * .82)),
        fill=(56, 189, 248, 24),
    )
    draw.ellipse(
        (int(size * .45), int(size * .34), int(size * 1.16), int(size * 1.08)),
        fill=(124, 58, 237, 48),
    )

    # Flow ring around the store symbol.
    ring_box = (
        int(size * .17), int(size * .16), int(size * .83), int(size * .82)
    )
    ring_width = max(5, int(size * .045))
    draw.arc(ring_box, start=205, end=350, fill=CYAN, width=ring_width)
    draw.arc(ring_box, start=20, end=155, fill="#a78bfa", width=ring_width)

    # Direction cue at the end of the cyan flow.
    arrow = [
        (int(size * .78), int(size * .38)),
        (int(size * .84), int(size * .31)),
        (int(size * .76), int(size * .30)),
    ]
    draw.polygon(arrow, fill=CYAN)

    # Minimal shopping bag = Store.
    bag_box = (
        int(size * .31), int(size * .35), int(size * .69), int(size * .69)
    )
    bag_width = max(4, int(size * .042))
    draw.rounded_rectangle(
        bag_box,
        radius=int(size * .045),
        fill=(15, 23, 42, 185),
        outline=WHITE,
        width=bag_width,
    )
    handle_box = (
        int(size * .39), int(size * .25), int(size * .61), int(size * .45)
    )
    draw.arc(
        handle_box,
        start=190,
        end=350,
        fill=WHITE,
        width=max(4, int(size * .038)),
    )

    # Health check = Check.
    check = [
        (int(size * .40), int(size * .54)),
        (int(size * .48), int(size * .62)),
        (int(size * .62), int(size * .46)),
    ]
    draw.line(
        check,
        fill=MINT,
        width=max(5, int(size * .052)),
        joint="curve",
    )

    # Crisp edge on light WordPress.org cards.
    draw.rounded_rectangle(
        (1, 1, size - 2, size - 2),
        radius=int(size * .23),
        outline=(255, 255, 255, 26),
        width=max(1, int(size * .008)),
    )
    return image


def banner(width, height):
    image = horizontal_gradient((width, height), DEEP, "#312e81").convert("RGBA")
    draw = ImageDraw.Draw(image)

    draw.ellipse(
        (int(width * .56), -int(height * .72), int(width * 1.04), int(height * 1.12)),
        fill=(79, 70, 229, 48),
    )
    draw.ellipse(
        (int(width * .78), int(height * .22), int(width * 1.12), int(height * 1.25)),
        fill=(56, 189, 248, 24),
    )

    grid = max(22, int(height * .14))
    for x in range(0, width, grid):
        draw.line((x, 0, x, height), fill=(255, 255, 255, 7), width=1)
    for y in range(0, height, grid):
        draw.line((0, y, width, y), fill=(255, 255, 255, 7), width=1)

    icon_size = int(height * .50)
    icon_x = int(width * .045)
    icon_y = int(height * .17)
    image.alpha_composite(brand_icon(icon_size), (icon_x, icon_y))

    content_x = icon_x + icon_size + int(width * .028)
    text(draw, (content_x, int(height * .17)), "Flow Store Check", int(height * .10), True)
    text(draw, (content_x, int(height * .315)), "for WooCommerce", int(height * .047), True, CYAN)
    text(
        draw,
        (content_x, int(height * .465)),
        "A clear health check for your WooCommerce store.",
        int(height * .034),
        False,
        SOFT,
    )

    pill_y = int(height * .64)
    pill_x = content_x
    for label in ("READ-ONLY", "PRIVATE", "ACTIONABLE"):
        bounds = draw.textbbox((0, 0), label, font=font(int(height * .027), True))
        pill_width = bounds[2] + int(height * .085)
        draw.rounded_rectangle(
            (pill_x, pill_y, pill_x + pill_width, pill_y + int(height * .095)),
            radius=int(height * .048),
            fill=(255, 255, 255, 14),
            outline=(255, 255, 255, 38),
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

    # Right-side store health card.
    card_width = int(width * .285)
    x2 = width - int(width * .04)
    x1 = x2 - card_width
    y1 = int(height * .13)
    y2 = int(height * .87)
    draw.rounded_rectangle(
        (x1, y1, x2, y2),
        radius=int(height * .055),
        fill=(255, 255, 255, 22),
        outline=(255, 255, 255, 50),
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
