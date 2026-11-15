#!/usr/bin/env python3
"""ROSÉA — مونتاژ صفحه‌های ایستا.

شش صفحهٔ فروشگاه از قطعه‌های مشترک ساخته می‌شوند تا هدر/فوتر فقط یک‌جا
نگهداری شود:
    src/partials.py       → HEADER و FOOTER (از روی index.html استخراج شده)
    src/<name>.body.html  → محتوای <main> + اسکریپت‌های همان صفحه
اجرا:
    python3 tools/build-pages.py
خروجی: index.html, shop.html, product.html, global-search.html, cart.html, guide.html
این اسکریپت فقط همان شش فایل را بازنویسی می‌کند؛ فایل دیگری را لمس نمی‌کند.
"""
from __future__ import annotations

import pathlib
import sys

ROOT = pathlib.Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT / "src"))
from partials import HEADER, FOOTER  # noqa: E402

PAGES = {
    "index": {
        "page": "home",
        "title": "رُزآ | زیباییِ وارداتی، اصل و شفاف",
        "desc": "رُزآ، فروشگاه لوازم آرایشی، مراقبت پوست، عطر، لوازم برقی زیبایی، پوشاک و مکمل وارداتی؛ با اصالت قابل بررسی، ترخیص رسمی و جست‌وجو در سایت‌های خارجی.",
        "og": "assets/img/hero.jpg",
        "nav": "",
    },
    "shop": {
        "page": "shop",
        "title": "فروشگاه | رُزآ",
        "desc": "همهٔ محصولات وارداتی رُزآ: مراقبت پوست، آرایش، عطر، لوازم برقی، پوشاک و مکمل — با فیلتر برند، دسته‌بندی و قیمت.",
        "og": "assets/img/cat-skincare.jpg",
        "nav": "shop",
    },
    "product": {
        "page": "shop",
        "title": "جزئیات محصول | رُزآ",
        "desc": "مشخصات، تاریخ انقضا، مدرک اصالت و مقایسهٔ قیمت با منابع خارجی؛ همراه با ضمانت اصالت رُزآ.",
        "og": "assets/img/p-serum.jpg",
        "nav": "shop",
    },
    "global-search": {
        "page": "global",
        "title": "جست‌وجوی جهانی | رُزآ",
        "desc": "محصول را در آمازون، سفورا، نوتینو، آی‌هرب و فارفچ جست‌وجو کنید؛ قیمت تومانی، زمان تحویل و ثبت سفارش واردات در یک صفحه.",
        "og": "assets/img/import-box.jpg",
        "nav": "global",
    },
    "cart": {
        "page": "cart",
        "title": "سبد خرید | رُزآ",
        "desc": "بازبینی سبد خرید، هزینهٔ ارسال و ثبت سفارش؛ شامل کالاهای موجود و سفارش‌های واردات.",
        "og": "assets/img/hero.jpg",
        "nav": "cart",
    },
    "guide": {
        "page": "guide",
        "title": "راهنمای خرید و اصالت کالا | رُزآ",
        "desc": "چطور کالای وارداتی اصل را تشخیص دهیم، مسیر ترخیص چگونه است و چه شرایطی برای مرجوعی وجود دارد.",
        "og": "assets/img/cat-supplements.jpg",
        "nav": "guide",
    },
}

HEAD = """<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{title}</title>
  <meta name="description" content="{desc}">
  <meta name="theme-color" content="#FBF6F4">
  <meta property="og:type" content="website">
  <meta property="og:title" content="{title}">
  <meta property="og:description" content="{desc}">
  <meta property="og:image" content="{og}">
  <link rel="icon" href="assets/favicon.svg" type="image/svg+xml">
  <link rel="preload" href="assets/fonts/Vazirmatn-Regular.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="preload" href="assets/fonts/Vazirmatn-Bold.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="stylesheet" href="css/rosea.css">
</head>
<body data-page="{page}">"""

TAIL = """</body>
</html>
"""


def build() -> list[str]:
    written = []
    for name, cfg in PAGES.items():
        body_path = ROOT / "src" / f"{name}.body.html"
        if not body_path.exists():
            print(f"! missing {body_path}", file=sys.stderr)
            continue
        body = body_path.read_text(encoding="utf-8")

        header = HEADER
        if cfg["nav"]:
            token = 'data-nav="%s"' % cfg["nav"]
            if token in header:
                header = header.replace(token, token + ' aria-current="page"', 1)

        # اسکریپت‌ها بعد از فوتر بارگذاری می‌شوند
        if "<script " in body:
            cut = body.index("<script ")
            main, scripts = body[:cut], body[cut:]
        else:
            main, scripts = body, ""

        html = (
            HEAD.format(**cfg)
            + "\n"
            + header
            + "\n"
            + main.strip()
            + "\n\n  "
            + FOOTER
            + "\n\n"
            + scripts.strip()
            + "\n"
            + TAIL
        )
        out = ROOT / f"{name}.html"
        out.write_text(html, encoding="utf-8")
        written.append(out.name)
        print(f"✓ {out.name}  ({len(html):,} bytes)")
    return written


if __name__ == "__main__":
    build()
