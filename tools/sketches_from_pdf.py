#!/usr/bin/env python3
"""Render PDF sheets into web-ready project sketches.

For every sheet listed in a spec file this writes

    <slug>-<NN>-<name>.webp            full sheet (lightbox / zoom)
    <slug>-<NN>-<name>-<W>x<H>.webp    thumbnail (grid)

and, for the WordPress theme, a ``set.json`` manifest that
``App\\Services\\BundledSketches`` reads (captions, sizes, order).

Requirements (not part of the repo):  pip install pymupdf pillow

Usage:
    python tools/sketches_from_pdf.py tools/sketch-specs/cafe.json \\
        --pdf-dir /path/to/the/pdf/files \\
        --out assets/images/sketches/cafe \\
        --out laico-sage/resources/sketches/cafe \\
        --manifest laico-sage/resources/sketches/cafe

Spec format (UTF-8 JSON):
    {
      "slug": "cafe",
      "title_fa": "...", "title_en": "...",
      "items": [ {"pdf": "1.pdf", "page": 1, "name": "furniture-plan", "fa": "...", "en": "..."} ]
    }

The static pages (project-*.html) are edited by hand: copy the markup of the
sketch section of project-cafe-amol.html and point it at the new files.
"""

from __future__ import annotations

import argparse
import json
import shutil
import sys
import tempfile
from pathlib import Path


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    parser.add_argument("spec", type=Path, help="sketch spec JSON (see tools/sketch-specs/)")
    parser.add_argument("--pdf-dir", type=Path, required=True, help="folder that holds the PDF files named in the spec")
    parser.add_argument("--out", type=Path, action="append", required=True, help="image output folder (repeatable)")
    parser.add_argument("--manifest", type=Path, action="append", default=[], help="folder that receives set.json (repeatable)")
    parser.add_argument("--zoom", type=float, default=2.0, help="render scale; 2.0 turns an A3 sheet into 1684x2382 px")
    parser.add_argument("--quality", type=int, default=80, help="WebP quality of the full sheets")
    parser.add_argument("--thumb-width", type=int, default=600, help="thumbnail width in px")
    parser.add_argument("--thumb-gamma", type=float, default=1.35, help=">1 darkens thin lines in the thumbnails")
    args = parser.parse_args()

    try:
        import pymupdf  # type: ignore
        from PIL import Image  # type: ignore
    except ImportError:
        print("This tool needs PyMuPDF and Pillow:  pip install pymupdf pillow", file=sys.stderr)
        return 2

    spec = json.loads(args.spec.read_text(encoding="utf-8"))
    slug = spec["slug"]
    lut = [round(255 * ((v / 255) ** args.thumb_gamma)) for v in range(256)] * 3

    docs: dict[str, "pymupdf.Document"] = {}
    manifest_items = []
    staging = Path(tempfile.mkdtemp(prefix="laico-sketches-"))

    for number, item in enumerate(spec["items"], 1):
        pdf_name = item["pdf"]
        if pdf_name not in docs:
            docs[pdf_name] = pymupdf.open(args.pdf_dir / pdf_name)
        page = docs[pdf_name][int(item["page"]) - 1]
        pix = page.get_pixmap(matrix=pymupdf.Matrix(args.zoom, args.zoom), alpha=False, colorspace=pymupdf.csRGB)
        image = Image.frombytes("RGB", (pix.width, pix.height), pix.samples)

        base = f"{slug}-{number:02d}-{item['name']}"
        full_name = f"{base}.webp"
        image.save(staging / full_name, "WEBP", quality=args.quality, method=6, use_sharp_yuv=True)

        thumb_w = args.thumb_width
        thumb_h = round(image.height * thumb_w / image.width)
        thumb = image.resize((thumb_w, thumb_h), Image.LANCZOS).point(lut)
        thumb_name = f"{base}-{thumb_w}x{thumb_h}.webp"
        thumb.save(staging / thumb_name, "WEBP", quality=80, method=6, use_sharp_yuv=True)

        manifest_items.append({
            "file": full_name,
            "width": image.width,
            "height": image.height,
            "thumb": thumb_name,
            "thumb_width": thumb_w,
            "thumb_height": thumb_h,
            "fa": item["fa"],
            "en": item["en"],
        })
        print(f"{number:02d}  {full_name}  {(staging / full_name).stat().st_size // 1024} KB"
              f"  + thumb {(staging / thumb_name).stat().st_size // 1024} KB")

    manifest = {
        "slug": slug,
        "title_fa": spec.get("title_fa", slug),
        "title_en": spec.get("title_en", slug),
        "items": manifest_items,
    }

    for out_dir in args.out:
        out_dir.mkdir(parents=True, exist_ok=True)
        for old in out_dir.glob(f"{slug}-*.webp"):
            old.unlink()
        for produced in sorted(staging.iterdir()):
            shutil.copyfile(produced, out_dir / produced.name)
    for manifest_dir in args.manifest:
        manifest_dir.mkdir(parents=True, exist_ok=True)
        (manifest_dir / "set.json").write_text(
            json.dumps(manifest, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")

    total = sum(f.stat().st_size for f in staging.iterdir())
    print(f"{len(manifest_items)} sheets, {total / 1024 / 1024:.2f} MB per output folder")
    shutil.rmtree(staging, ignore_errors=True)
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
