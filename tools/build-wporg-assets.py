#!/usr/bin/env python3
"""Build deterministic WordPress.org icon, banner, and screenshot assets."""

from pathlib import Path
import argparse
from PIL import Image, ImageDraw, ImageFont

NAVY = "#1d2327"
MUTED = "#646970"
BORDER = "#dcdcde"
PRIMARY = "#3858e9"
PURPLE = "#7345d6"
GREEN = "#00a32a"
WARNING = "#dba617"
RED = "#d63638"
INFO = "#2271b1"
BG = "#f6f7f7"
LIGHTBLUE = "#eef2ff"
LIGHTWARN = "#fff9e8"
FONT_REGULAR = "/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf"
FONT_BOLD = "/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf"


def font(size, bold=False):
    return ImageFont.truetype(FONT_BOLD if bold else FONT_REGULAR, size)


def rgb(value):
    value = value.lstrip("#")
    return tuple(int(value[index:index + 2], 16) for index in (0, 2, 4))


def gradient(size, first, second):
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


def logo_icon(size):
    source = gradient((size, size), PRIMARY, PURPLE).convert("RGBA")
    mask = Image.new("L", (size, size), 0)
    ImageDraw.Draw(mask).rounded_rectangle((0, 0, size - 1, size - 1), radius=int(size * .22), fill=255)
    image = Image.new("RGBA", (size, size), (0, 0, 0, 0))
    image.paste(source, (0, 0), mask)
    draw = ImageDraw.Draw(image)
    white = (255, 255, 255, 255)
    check = (190, 247, 210, 255)
    stroke = max(3, int(size * .05))
    left, right, roof = int(size * .25), int(size * .75), int(size * .35)
    draw.line((left, roof, right, roof), fill=white, width=stroke)
    for index in range(5):
        x = left + index * (right - left) // 4
        draw.line((x, roof, x - int(size * .02), int(size * .45)), fill=white, width=max(2, int(size * .025)))
    draw.rounded_rectangle((int(size * .29), int(size * .46), int(size * .71), int(size * .72)), radius=int(size * .035), outline=white, width=max(2, int(size * .035)))
    draw.line([(int(size * .43), int(size * .62)), (int(size * .50), int(size * .69)), (int(size * .64), int(size * .54))], fill=check, width=max(4, int(size * .055)), joint="curve")
    draw.arc((int(size * .12), int(size * .18), int(size * .88), int(size * .84)), 205, 350, fill=(255, 255, 255, 70), width=max(2, int(size * .03)))
    return image


def text(draw, xy, value, size, bold=False, fill=NAVY, anchor=None):
    draw.text(xy, value, font=font(size, bold), fill=fill, anchor=anchor)


def build_banner(width, height):
    image = gradient((width, height), "#ffffff", LIGHTBLUE).convert("RGBA")
    draw = ImageDraw.Draw(image)
    draw.ellipse((int(width * .72), -int(height * .34), int(width * 1.02), int(height * .9)), fill=(56, 88, 233, 18))
    draw.ellipse((int(width * .83), int(height * .25), int(width * 1.03), int(height * 1.15)), fill=(115, 69, 214, 16))
    icon_size = int(height * .48)
    image.alpha_composite(logo_icon(icon_size), (int(width * .05), int(height * .18)))
    x = int(width * .05) + icon_size + int(width * .03)
    text(draw, (x, int(height * .17)), "Flow Store Check", int(height * .10), True)
    text(draw, (x, int(height * .32)), "for WooCommerce", int(height * .05), True, PRIMARY)
    text(draw, (x, int(height * .48)), "Read-only store health diagnostics", int(height * .038), False, MUTED)
    pill_x = x
    for label in ("Catalog", "Orders", "Checkout", "System"):
        bounds = draw.textbbox((0, 0), label, font=font(int(height * .03), True))
        pill_width = bounds[2] + int(height * .075)
        y = int(height * .65)
        draw.rounded_rectangle((pill_x, y, pill_x + pill_width, y + int(height * .09)), radius=int(height * .045), fill="white", outline="#dfe3f3")
        text(draw, (pill_x + int(height * .035), y + int(height * .025)), label, int(height * .03), True, "#3047b8")
        pill_x += pill_width + int(height * .02)
    card_width = int(width * .25)
    x2 = width - int(width * .045)
    x1 = x2 - card_width
    y1 = int(height * .16)
    y2 = y1 + int(height * .68)
    draw.rounded_rectangle((x1 + 6, y1 + 8, x2 + 6, y2 + 10), radius=int(height * .04), fill=(0, 0, 0, 18))
    draw.rounded_rectangle((x1, y1, x2, y2), radius=int(height * .04), fill="white", outline=BORDER)
    text(draw, (x1 + int(height * .05), y1 + int(height * .05)), "STORE HEALTH SCORE", int(height * .023), True, MUTED)
    text(draw, (x1 + int(height * .05), y1 + int(height * .12)), "88", int(height * .15), True)
    text(draw, (x1 + int(height * .20), y1 + int(height * .185)), "/100", int(height * .036), False, MUTED)
    bar_x1, bar_x2, bar_y = x1 + int(height * .05), x2 - int(height * .05), y1 + int(height * .36)
    draw.rounded_rectangle((bar_x1, bar_y, bar_x2, bar_y + int(height * .023)), radius=6, fill="#e7e9ec")
    draw.rounded_rectangle((bar_x1, bar_y, bar_x1 + int((bar_x2 - bar_x1) * .88), bar_y + int(height * .023)), radius=6, fill=PRIMARY)
    for index, (number, label, color) in enumerate((("0", "Critical", RED), ("4", "Warnings", WARNING), ("31", "Passed", GREEN))):
        y = bar_y + int(height * .085) + index * int(height * .075)
        draw.ellipse((bar_x1, y + 4, bar_x1 + int(height * .022), y + int(height * .022) + 4), fill=color)
        text(draw, (bar_x1 + int(height * .04), y), number, int(height * .035), True)
        text(draw, (bar_x1 + int(height * .095), y + 1), label, int(height * .028), False, MUTED)
    return image.convert("RGB")


def header(draw, width, active):
    draw.rounded_rectangle((50, 40, width - 50, 150), radius=16, fill="white", outline=BORDER)
    draw.rounded_rectangle((72, 62, 120, 110), radius=14, fill=PRIMARY)
    text(draw, (96, 86), "FS", 18, True, "white", "mm")
    text(draw, (140, 62), "Flow Store Check", 26, True)
    text(draw, (140, 99), "Store Health Dashboard for WooCommerce", 13, False, MUTED)
    draw.rounded_rectangle((425, 66, 475, 90), radius=12, fill=LIGHTBLUE)
    text(draw, (450, 78), "v1.0.0", 10, True, "#3047b8", "mm")
    draw.rounded_rectangle((width - 290, 67, width - 205, 102), radius=6, fill="white", outline=BORDER)
    text(draw, (width - 247, 84), "Reports", 12, True, NAVY, "mm")
    draw.rounded_rectangle((width - 195, 67, width - 75, 102), radius=6, fill=PRIMARY)
    text(draw, (width - 135, 84), "Run fresh scan", 12, True, "white", "mm")
    x = 70
    for label in ("Overview", "Diagnostics", "Catalog", "Operations", "System", "History", "Reports"):
        item_width = max(74, draw.textbbox((0, 0), label, font=font(12, True))[2] + 28)
        text(draw, (x, 128), label, 12, True, PRIMARY if label == active else MUTED)
        if label == active:
            draw.rectangle((x, 146, x + item_width - 18, 149), fill=PRIMARY)
        x += item_width


def screen(active):
    image = Image.new("RGB", (1280, 800), BG)
    draw = ImageDraw.Draw(image)
    header(draw, 1280, active)
    return image, draw


def screenshot_overview():
    image, draw = screen("Overview")
    draw.rounded_rectangle((50, 175, 1230, 400), radius=18, fill=LIGHTWARN, outline=BORDER)
    text(draw, (80, 205), "STORE HEALTH SCORE", 11, True, MUTED)
    text(draw, (80, 235), "68", 58, True)
    text(draw, (153, 270), "/100", 18, False, MUTED)
    text(draw, (80, 310), "Needs attention", 14, True)
    draw.rounded_rectangle((80, 342, 285, 351), radius=5, fill="#e7e9ec")
    draw.rounded_rectangle((80, 342, 220, 351), radius=5, fill=WARNING)
    text(draw, (360, 205), "CURRENT DIAGNOSTIC SNAPSHOT", 11, True, MUTED)
    text(draw, (360, 232), "A few store health signals need attention.", 25, True)
    text(draw, (360, 276), "Flow Store Check reviews high-value catalog, checkout, order and system signals", 14, False, MUTED)
    text(draw, (360, 298), "without modifying store data.", 14, False, MUTED)
    x = 360
    for number, label, color in (("0", "Critical", RED), ("7", "Warnings", WARNING), ("30", "Passed", GREEN), ("12", "Info", INFO)):
        draw.rounded_rectangle((x, 330, x + 180, 380), radius=10, fill="white", outline="#e5e5e5")
        draw.ellipse((x + 14, 348, x + 24, 358), fill=color)
        text(draw, (x + 36, 339), number, 20, True)
        text(draw, (x + 72, 344), label, 12, False, MUTED)
        x += 195
    text(draw, (50, 430), "HEALTH AREAS", 11, True, MUTED)
    text(draw, (50, 451), "Where your store stands", 20, True)
    areas = (("Products", 72), ("Inventory", 84), ("Orders", 91), ("Checkout", 76), ("Payments", 88), ("Shipping", 69), ("System", 82), ("Store", 75))
    for index, (label, score) in enumerate(areas):
        x = 50 + (index % 4) * 295
        y = 488 + (index // 4) * 120
        draw.rounded_rectangle((x, y, x + 275, y + 95), radius=12, fill="white", outline=BORDER)
        text(draw, (x + 16, y + 16), label, 14, True)
        text(draw, (x + 245, y + 15), str(score), 18, True, anchor="ra")
        draw.rounded_rectangle((x + 16, y + 60, x + 259, y + 67), radius=4, fill="#e7e9ec")
        draw.rounded_rectangle((x + 16, y + 60, x + 16 + int(243 * score / 100), y + 67), radius=4, fill=PRIMARY)
    return image


def screenshot_diagnostics():
    image, draw = screen("Diagnostics")
    text(draw, (50, 185), "DIAGNOSTICS", 11, True, MUTED)
    text(draw, (50, 207), "All diagnostics", 22, True)
    text(draw, (50, 239), "Search and filter every diagnostic result from the current bounded scan.", 13, False, MUTED)
    draw.rounded_rectangle((50, 270, 1230, 330), radius=12, fill="white", outline=BORDER)
    for x1, x2, label in ((70, 500, "Search diagnostics..."), (515, 700, "All statuses"), (715, 900, "All areas")):
        draw.rounded_rectangle((x1, 286, x2, 316), radius=5, fill="white", outline="#c3c4c7")
        text(draw, (x1 + 14, 294), label, 12, False, MUTED if "Search" in label else NAVY)
    draw.rounded_rectangle((915, 286, 990, 316), radius=5, fill=PRIMARY)
    text(draw, (952, 301), "Filter", 12, True, "white", "mm")
    draw.rounded_rectangle((1000, 286, 1075, 316), radius=5, fill="white", outline=BORDER)
    text(draw, (1037, 301), "Reset", 12, True, NAVY, "mm")
    text(draw, (1175, 300), "49 results", 11, True, MUTED, "mm")
    rows = (("WARNING", "Products without a featured image", "7 affected items. Product imagery is a major part of catalog usability.", "Products", WARNING), ("WARNING", "Low-stock products", "4 affected items. These products are at or below their configured low-stock threshold.", "Inventory", WARNING), ("PASSED", "Checkout page is published", "Page ID 23 is assigned and published.", "Checkout", GREEN), ("PASSED", "Payment gateways are enabled", "2 gateways are enabled.", "Payments", GREEN), ("INFO", "Persistent object cache was not detected", "This is informational; many stores run correctly without a persistent object cache.", "System", INFO))
    y = 350
    for status, title, description, area, color in rows:
        draw.rounded_rectangle((50, y, 1230, y + 76), radius=10, fill="white", outline=BORDER)
        draw.rectangle((50, y, 55, y + 76), fill=color)
        draw.ellipse((72, y + 29, 82, y + 39), fill=color)
        text(draw, (90, y + 27), status, 10, True, MUTED)
        text(draw, (210, y + 14), title, 14, True)
        text(draw, (210, y + 38), description, 12, False, MUTED)
        draw.rounded_rectangle((1035, y + 24, 1120, y + 50), radius=13, fill="#f0f0f1")
        text(draw, (1077, y + 37), area, 10, True, MUTED, "mm")
        draw.rounded_rectangle((1135, y + 23, 1200, y + 51), radius=5, fill="white", outline=BORDER)
        text(draw, (1167, y + 37), "Review", 10, True, NAVY, "mm")
        y += 86
    return image


def screenshot_reports():
    image, draw = screen("Reports")
    text(draw, (50, 185), "REPORTS", 11, True, MUTED)
    text(draw, (50, 207), "Export and print your health snapshot", 22, True)
    text(draw, (50, 239), "Exports contain diagnostic summaries and configured action URLs, not customer or order record contents.", 13, False, MUTED)
    cards = (("CSV report", "Spreadsheet-friendly diagnostics, counts, areas, details and action URLs.", "Download CSV", "CSV"), ("JSON report", "Structured diagnostic snapshot for technical reviews or internal tooling.", "Download JSON", "{}"), ("Print report", "Clean print layout for PDF export or sharing a review copy.", "Print / Save PDF", "PDF"))
    x = 50
    for title, description, button, mark in cards:
        draw.rounded_rectangle((x, 280, x + 365, 470), radius=14, fill="white", outline=BORDER)
        draw.rounded_rectangle((x + 20, 302, x + 70, 352), radius=12, fill=LIGHTBLUE)
        text(draw, (x + 45, 327), mark, 13, True, PRIMARY, "mm")
        text(draw, (x + 20, 370), title, 17, True)
        text(draw, (x + 20, 401), description, 11, False, MUTED)
        draw.rounded_rectangle((x + 20, 438, x + 145, 464), radius=5, fill=PRIMARY)
        text(draw, (x + 82, 451), button, 10, True, "white", "mm")
        x += 390
    text(draw, (50, 510), "REPORT PREVIEW", 11, True, MUTED)
    text(draw, (50, 532), "Current store health snapshot", 20, True)
    text(draw, (1180, 535), "68/100", 22, True, PRIMARY, "ra")
    draw.rounded_rectangle((50, 575, 1230, 720), radius=12, fill="white", outline=BORDER)
    for index, (label, value) in enumerate((("Scanned", "Latest site scan"), ("Checks", "49"), ("Duration", "184 ms"), ("Mode", "Read-only"))):
        x = 75 + index * 285
        text(draw, (x, 598), label.upper(), 10, True, MUTED)
        text(draw, (x, 620), value, 13, True)
    for index, (label, score) in enumerate((("Products", 72), ("Inventory", 84), ("Checkout", 76))):
        x = 75 + index * 365
        y = 662
        text(draw, (x, y), label, 12, True)
        draw.rounded_rectangle((x + 80, y + 2, x + 250, y + 10), radius=4, fill="#e7e9ec")
        draw.rounded_rectangle((x + 80, y + 2, x + 80 + int(170 * score / 100), y + 10), radius=4, fill=PRIMARY)
        text(draw, (x + 265, y - 2), str(score), 12, True)
    return image


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--output", default="build/wporg-assets")
    args = parser.parse_args()
    output = Path(args.output)
    output.mkdir(parents=True, exist_ok=True)
    for size in (128, 256):
        logo_icon(size).save(output / f"icon-{size}x{size}.png")
    build_banner(772, 250).save(output / "banner-772x250.png")
    build_banner(1544, 500).save(output / "banner-1544x500.png")
    screenshot_overview().save(output / "screenshot-1.png")
    screenshot_diagnostics().save(output / "screenshot-2.png")
    screenshot_reports().save(output / "screenshot-3.png")


if __name__ == "__main__":
    main()
