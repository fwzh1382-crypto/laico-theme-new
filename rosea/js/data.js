/* ROSÉA — data layer
   همهٔ داده‌های فروشگاه در همین فایل است تا اتصال به API/بک‌اند فقط با
   جایگزینی ROSEA.products / ROSEA.globalIndex انجام شود.
   تصاویر: assets/img — نرخ ارز تقریبی و قابل ویرایش در ROSEA.rate. */
(function (global) {
  "use strict";

  var ROSEA = {
    brand: {
      fa: "رُزآ",
      en: "ROSEA",
      tagline: "زیباییِ وارداتی، اصل و شفاف"
    },

    /* نرخ تقریبی برای تبدیل قیمت منابع خارجی (تومان) */
    rate: {
      USD: 118000,
      EUR: 128000,
      GBP: 148000,
      label: "نرخ تقریبی تبدیل",
      updated: "۱۴۰۴/۰۸/۲۴"
    },

    /* کارمزد خدمات واردات و ارسال بین‌المللی، درصدی از قیمت کالا */
    importFee: 0.18,
    freeShipFrom: 5000000,

    categories: [
      {
        id: "skincare",
        fa: "مراقبت پوست",
        en: "Skincare",
        note: "سرم، کرم، ضدآفتاب",
        img: "assets/img/cat-skincare.jpg"
      },
      {
        id: "makeup",
        fa: "آرایش",
        en: "Makeup",
        note: "رژ لب، رژگونه، چشم",
        img: "assets/img/cat-makeup.jpg"
      },
      {
        id: "fragrance",
        fa: "عطر و ادکلن",
        en: "Fragrance",
        note: "نیش، ادوپرفیوم",
        img: "assets/img/cat-fragrance.jpg"
      },
      {
        id: "devices",
        fa: "لوازم برقی",
        en: "Devices",
        note: "اتو مو، برس، LED",
        img: "assets/img/cat-devices.jpg"
      },
      {
        id: "fashion",
        fa: "پوشاک و اکسسوری",
        en: "Fashion",
        note: "شال، زیور، عینک",
        img: "assets/img/cat-fashion.jpg"
      },
      {
        id: "supplements",
        fa: "مکمل و ویتامین",
        en: "Supplements",
        note: "کلاژن، پروبیوتیک",
        img: "assets/img/cat-supplements.jpg"
      }
    ],

    /* منابع خارجی — برای جست‌وجوی جهانی و لینک زنده */
    sources: [
      {
        id: "amazon",
        fa: "آمازون",
        en: "Amazon",
        cc: "US",
        currency: "USD",
        search: "https://www.amazon.com/s?k=",
        tone: "#1E9E96"
      },
      {
        id: "sephora",
        fa: "سفورا",
        en: "Sephora",
        cc: "US",
        currency: "USD",
        search: "https://www.sephora.com/search?keyword=",
        tone: "#A34E63"
      },
      {
        id: "notino",
        fa: "نوتینو",
        en: "Notino",
        cc: "EU",
        currency: "EUR",
        search: "https://www.notino.com/search/?q=",
        tone: "#0F746E"
      },
      {
        id: "iherb",
        fa: "آی‌هرب",
        en: "iHerb",
        cc: "US",
        currency: "USD",
        search: "https://www.iherb.com/search?kw=",
        tone: "#C96F85"
      },
      {
        id: "cultbeauty",
        fa: "کالت بیوتی",
        en: "Cult Beauty",
        cc: "UK",
        currency: "GBP",
        search: "https://www.cultbeauty.co.uk/search?q=",
        tone: "#251B1E"
      },
      {
        id: "farfetch",
        fa: "فارفچ",
        en: "Farfetch",
        cc: "UK",
        currency: "GBP",
        search: "https://www.farfetch.com/uk/shopping/search/items.aspx?q=",
        tone: "#DCA39A"
      },
      {
        id: "lookfantastic",
        fa: "لوک‌فنتستیک",
        en: "Lookfantastic",
        cc: "UK",
        currency: "GBP",
        search: "https://www.lookfantastic.com/search?q=",
        tone: "#1E9E96"
      },
      {
        id: "ulta",
        fa: "آلتا",
        en: "Ulta Beauty",
        cc: "US",
        currency: "USD",
        search: "https://www.ulta.com/search?Ntt=",
        tone: "#C96F85"
      }
    ],

    products: [
      {
        id: "sera-no7-vitamin-c",
        brand: "SERA Nº7",
        title: "سرم ویتامین C ۱۵٪ + فریولیک",
        cat: "skincare",
        price: 3480000,
        oldPrice: 4200000,
        rating: 4.8,
        reviews: 214,
        img: "assets/img/p-serum.jpg",
        badge: "پرفروش",
        origin: "کره جنوبی",
        desc:
          "سرم روشن‌کننده با ۱۵٪ ویتامین C پایدار، اسید فریولیک و ویتامین E. بافت سبک و بدون چربی که برای پوست‌های حساس هم تست شده است.",
        variants: ["۱۵ میلی‌لیتر", "۳۰ میلی‌لیتر"],
        specs: [
          ["حجم", "۳۰ میلی‌لیتر"],
          ["نوع پوست", "همهٔ انواع، حساس"],
          ["کشور تولید", "کره جنوبی"],
          ["تاریخ انقضا", "۲۰۲۷/۰۶"],
          ["مدرک اصالت", "گمرک + بارکد بین‌المللی"]
        ],
        foreign: [
          { source: "sephora", price: 42, note: "Sephora US" },
          { source: "amazon", price: 38, note: "Amazon US" }
        ]
      },
      {
        id: "celeste-night-cream",
        brand: "CÉLESTE",
        title: "کرم شب بازسازنده با پپتید",
        cat: "skincare",
        price: 2950000,
        oldPrice: 0,
        rating: 4.6,
        reviews: 128,
        img: "assets/img/cat-skincare.jpg",
        badge: "",
        origin: "فرانسه",
        desc:
          "کرم شبانهٔ غنی با کمپلکس پپتید و سرامید برای ترمیم سد پوستی در طول خواب. بدون عطر افزوده.",
        variants: ["۵۰ میلی‌لیتر"],
        specs: [
          ["حجم", "۵۰ میلی‌لیتر"],
          ["نوع پوست", "خشک تا نرمال"],
          ["کشور تولید", "فرانسه"],
          ["تاریخ انقضا", "۲۰۲۷/۰۲"],
          ["مدرک اصالت", "بارکد اصالت + فاکتور رسمی"]
        ],
        foreign: [{ source: "notino", price: 31, note: "Notino EU" }]
      },
      {
        id: "maison-rouge-velvet",
        brand: "MAISON ROUGE",
        title: "رژ لب مات مخملی — Velvet Rose",
        cat: "makeup",
        price: 1450000,
        oldPrice: 1690000,
        rating: 4.7,
        reviews: 342,
        img: "assets/img/p-lipstick.jpg",
        badge: "جدید",
        origin: "ایتالیا",
        desc:
          "رژ لب مات با پوشش کامل در یک لایه، حاوی روغن جوجوبا برای جلوگیری از خشکی لب. رنگ Velvet Rose، رز خاکی گرم.",
        variants: ["Velvet Rose", "Dusty Mauve", "Brick"],
        specs: [
          ["وزن", "۳٫۵ گرم"],
          ["فینیش", "مات مخملی"],
          ["کشور تولید", "ایتالیا"],
          ["تاریخ انقضا", "۲۰۲۸/۰۱"],
          ["مدرک اصالت", "گمرک + بارکد بین‌المللی"]
        ],
        foreign: [
          { source: "sephora", price: 24, note: "Sephora US" },
          { source: "cultbeauty", price: 21, note: "Cult Beauty UK" }
        ]
      },
      {
        id: "maison-rouge-blush-duo",
        brand: "MAISON ROUGE",
        title: "پالت رژگونه دوتایی — Blush Duo",
        cat: "makeup",
        price: 1890000,
        oldPrice: 0,
        rating: 4.5,
        reviews: 96,
        img: "assets/img/cat-makeup.jpg",
        badge: "",
        origin: "ایتالیا",
        desc:
          "دو رنگ رز و هلویی با بافت پودری نرم و قابل لایه‌بندی؛ مناسب پوست‌های روشن تا متوسط.",
        variants: ["Rosé / Peach"],
        specs: [
          ["وزن", "۸ گرم"],
          ["فینیش", "مات-ساتن"],
          ["کشور تولید", "ایتالیا"],
          ["تاریخ انقضا", "۲۰۲۸/۰۳"],
          ["مدرک اصالت", "بارکد اصالت"]
        ],
        foreign: [{ source: "ulta", price: 19, note: "Ulta US" }]
      },
      {
        id: "aqua-dore-sel-de-mer",
        brand: "AQUA DORÉ",
        title: "عطر یونیسکس Sel de Mer — ۵۰ml",
        cat: "fragrance",
        price: 5600000,
        oldPrice: 6400000,
        rating: 4.9,
        reviews: 187,
        img: "assets/img/cat-fragrance.jpg",
        badge: "لوکس",
        origin: "فرانسه",
        desc:
          "ادوپرفیوم با آغاز نمکی-مرکباتی و قلبی از رز و یاس، نشسته بر چوب صندل و مشک. ماندگاری ۸ تا ۱۰ ساعت.",
        variants: ["۵۰ میلی‌لیتر", "۱۰۰ میلی‌لیتر"],
        specs: [
          ["حجم", "۵۰ میلی‌لیتر"],
          ["غلظت", "ادوپرفیوم"],
          ["کشور تولید", "فرانسه"],
          ["تاریخ انقضا", "۲۰۲۹/۰۵"],
          ["مدرک اصالت", "بسته‌بندی سلفون + کد سریال"]
        ],
        foreign: [
          { source: "notino", price: 62, note: "Notino EU" },
          { source: "sephora", price: 74, note: "Sephora US" }
        ]
      },
      {
        id: "lumen-pro-styler",
        brand: "LUMEN PRO",
        title: "اتوی مو سرامیکی یونیزه",
        cat: "devices",
        price: 6900000,
        oldPrice: 7800000,
        rating: 4.6,
        reviews: 73,
        img: "assets/img/cat-devices.jpg",
        badge: "گارانتی ۲۴ ماه",
        origin: "آلمان",
        desc:
          "صفحات سرامیکی شناور با گرم‌شدن ۱۵ ثانیه‌ای، تنظیم دما ۱۵۰ تا ۲۳۰ درجه و تولید یون منفی برای کاهش موخوره.",
        variants: ["مشکی مات", "شامپاینی"],
        specs: [
          ["توان", "۶۰ وات"],
          ["ولتاژ", "۲۲۰–۲۴۰ ولت"],
          ["گارانتی", "۲۴ ماه شرکتی"],
          ["کشور تولید", "آلمان"],
          ["مدرک اصالت", "کارت گارانتی + گمرک"]
        ],
        foreign: [{ source: "amazon", price: 89, note: "Amazon DE" }]
      },
      {
        id: "atelier-nord-silk-scarf",
        brand: "ATELIER NORD",
        title: "شال ابریشم — Rosé Nude",
        cat: "fashion",
        price: 4900000,
        oldPrice: 0,
        rating: 4.7,
        reviews: 58,
        img: "assets/img/cat-fashion.jpg",
        badge: "",
        origin: "ترکیه",
        desc:
          "شال ۱۰۰٪ ابریشم با بافت ساتن و لبهٔ دست‌دوز؛ رنگ رز خاکی که با پالت خنثی و فیروزه‌ای هم‌نشین می‌شود.",
        variants: ["۹۰×۹۰ سانتی‌متر"],
        specs: [
          ["جنس", "۱۰۰٪ ابریشم"],
          ["ابعاد", "۹۰×۹۰ سانتی‌متر"],
          ["کشور تولید", "ترکیه"],
          ["مراقبت", "شست‌وشوی خشک"],
          ["مدرک اصالت", "بارکد اصالت"]
        ],
        foreign: [{ source: "farfetch", price: 42, note: "Farfetch UK" }]
      },
      {
        id: "vita-lab-marine-collagen",
        brand: "VITA LAB",
        title: "کلاژن دریایی + ویتامین C — ۳۰ ساشه",
        cat: "supplements",
        price: 2450000,
        oldPrice: 2890000,
        rating: 4.4,
        reviews: 411,
        img: "assets/img/cat-supplements.jpg",
        badge: "پرفروش",
        origin: "آمریکا",
        desc:
          "پپتید کلاژن دریایی نوع I و III همراه ویتامین C و اسید هیالورونیک؛ ساشه‌های تک‌دوز با طعم رز و لیمو.",
        variants: ["۳۰ ساشه"],
        specs: [
          ["مقدار", "۳۰ ساشه × ۵ گرم"],
          ["شکل", "پودر محلول"],
          ["کشور تولید", "آمریکا"],
          ["تاریخ انقضا", "۲۰۲۷/۱۱"],
          ["مدرک اصالت", "گمرک + اصالت اصالت‌سنج"]
        ],
        foreign: [{ source: "iherb", price: 34, note: "iHerb US" }]
      },
      {
        id: "rosea-gift-set-glow",
        brand: "ROSÉA EDIT",
        title: "ست هدیهٔ Glow — سرم + کرم شب",
        cat: "skincare",
        price: 5990000,
        oldPrice: 6430000,
        rating: 4.9,
        reviews: 64,
        img: "assets/img/hero.jpg",
        badge: "ست هدیه",
        origin: "بسته‌بندی در ایران",
        desc:
          "ست منتخب رُزآ شامل سرم ویتامین C و کرم شب پپتید، در جعبهٔ هدیهٔ پارچه‌ای با کارت دست‌نویس.",
        variants: ["ست کامل"],
        specs: [
          ["محتویات", "سرم ۳۰ml + کرم شب ۵۰ml"],
          ["بسته‌بندی", "جعبهٔ هدیه"],
          ["ارسال", "۲۴ ساعته"],
          ["مدرک اصالت", "برای هر دو کالا"],
          ["مناسب", "هدیهٔ تولد و سالگرد"]
        ],
        foreign: [{ source: "sephora", price: 98, note: "معادل ست مشابه" }]
      },
      {
        id: "pure-ritual-import-box",
        brand: "ROSÉA IMPORT",
        title: "باکس وارداتی ۳۰ روزه — روتین پایه",
        cat: "supplements",
        price: 4150000,
        oldPrice: 0,
        rating: 4.5,
        reviews: 39,
        img: "assets/img/import-box.jpg",
        badge: "سفارشی",
        origin: "چند کشور",
        desc:
          "باکس سفارشی مکمل و مراقبت؛ شما فهرست را می‌دهید، ما از منابع معتبر خارجی خرید و ترخیص می‌کنیم. مناسب دورهٔ ۳۰ روزه.",
        variants: ["دورهٔ ۳۰ روزه"],
        specs: [
          ["زمان تحویل", "۷ تا ۱۲ روز کاری"],
          ["منابع", "آمازون / آی‌هرب / نوتینو"],
          ["مستندات", "فاکتور + گمرک"],
          ["بیمه", "اختیاری، ۲٪ ارزش کالا"],
          ["مرجوعی", "فقط کالای پلمب‌نشده"]
        ],
        foreign: [{ source: "iherb", price: 46, note: "چند قلم" }]
      }
    ],

    /* ایندکس جست‌وجوی جهانی — محصولاتی که در سایت‌های خارجی موجودند.
       در نسخهٔ متصل به API، این آرایه با پاسخ زندهٔ سرور جایگزین می‌شود. */
    globalIndex: [
      {
        id: "g-1",
        title: "Vitamin C 15% Brightening Serum",
        brand: "SERA Nº7",
        source: "sephora",
        price: 42,
        rating: 4.8,
        reviews: 2140,
        img: "assets/img/p-serum.jpg",
        shipDays: "۹–۱۲",
        cat: "skincare",
        match: "sera-no7-vitamin-c"
      },
      {
        id: "g-2",
        title: "Peptide Night Repair Cream",
        brand: "CÉLESTE",
        source: "notino",
        price: 31,
        rating: 4.6,
        reviews: 860,
        img: "assets/img/cat-skincare.jpg",
        shipDays: "۸–۱۱",
        cat: "skincare",
        match: "celeste-night-cream"
      },
      {
        id: "g-3",
        title: "Velvet Matte Lipstick — Rose",
        brand: "MAISON ROUGE",
        source: "cultbeauty",
        price: 21,
        rating: 4.7,
        reviews: 1210,
        img: "assets/img/p-lipstick.jpg",
        shipDays: "۱۰–۱۴",
        cat: "makeup",
        match: "maison-rouge-velvet"
      },
      {
        id: "g-4",
        title: "Blush Duo Palette — Rosé Peach",
        brand: "MAISON ROUGE",
        source: "ulta",
        price: 19,
        rating: 4.5,
        reviews: 430,
        img: "assets/img/cat-makeup.jpg",
        shipDays: "۱۱–۱۵",
        cat: "makeup",
        match: "maison-rouge-blush-duo"
      },
      {
        id: "g-5",
        title: "Sel de Mer Eau de Parfum 50ml",
        brand: "AQUA DORÉ",
        source: "notino",
        price: 62,
        rating: 4.9,
        reviews: 3120,
        img: "assets/img/cat-fragrance.jpg",
        shipDays: "۷–۱۰",
        cat: "fragrance",
        match: "aqua-dore-sel-de-mer"
      },
      {
        id: "g-6",
        title: "Ionic Ceramic Hair Straightener",
        brand: "LUMEN PRO",
        source: "amazon",
        price: 89,
        rating: 4.6,
        reviews: 5230,
        img: "assets/img/cat-devices.jpg",
        shipDays: "۸–۱۲",
        cat: "devices",
        match: "lumen-pro-styler"
      },
      {
        id: "g-7",
        title: "Silk Scarf — Rosé Nude 90cm",
        brand: "ATELIER NORD",
        source: "farfetch",
        price: 155,
        rating: 4.7,
        reviews: 210,
        img: "assets/img/cat-fashion.jpg",
        shipDays: "۱۲–۱۶",
        cat: "fashion",
        match: "atelier-nord-silk-scarf"
      },
      {
        id: "g-8",
        title: "Marine Collagen Sachets 30x",
        brand: "VITA LAB",
        source: "iherb",
        price: 34,
        rating: 4.4,
        reviews: 8740,
        img: "assets/img/cat-supplements.jpg",
        shipDays: "۱۰–۱۳",
        cat: "supplements",
        match: "vita-lab-marine-collagen"
      },
      {
        id: "g-9",
        title: "Hydrating Face Mask with Ceramides",
        brand: "CÉLESTE",
        source: "sephora",
        price: 28,
        rating: 4.5,
        reviews: 640,
        img: "assets/img/cat-skincare.jpg",
        shipDays: "۹–۱۳",
        cat: "skincare",
        match: ""
      },
      {
        id: "g-10",
        title: "LED Facial Mask — Pro Light",
        brand: "LUMEN PRO",
        source: "amazon",
        price: 189,
        rating: 4.3,
        reviews: 980,
        img: "assets/img/cat-devices.jpg",
        shipDays: "۱۰–۱۴",
        cat: "devices",
        match: ""
      },
      {
        id: "g-11",
        title: "Niacinamide 10% + Zinc Serum",
        brand: "SERA Nº7",
        source: "lookfantastic",
        price: 16,
        rating: 4.6,
        reviews: 3310,
        img: "assets/img/p-serum.jpg",
        shipDays: "۹–۱۲",
        cat: "skincare",
        match: ""
      },
      {
        id: "g-12",
        title: "Clean Deodorant — Rose & Sage",
        brand: "PURE RITUAL",
        source: "iherb",
        price: 12,
        rating: 4.2,
        reviews: 1540,
        img: "assets/img/cat-supplements.jpg",
        shipDays: "۱۰–۱۴",
        cat: "supplements",
        match: ""
      },
      {
        id: "g-13",
        title: "Cat-Eye Sunglasses — Cream",
        brand: "ATELIER NORD",
        source: "farfetch",
        price: 210,
        rating: 4.8,
        reviews: 96,
        img: "assets/img/cat-fashion.jpg",
        shipDays: "۱۲–۱۶",
        cat: "fashion",
        match: ""
      },
      {
        id: "g-14",
        title: "Eau de Parfum Discovery Set",
        brand: "AQUA DORÉ",
        source: "sephora",
        price: 55,
        rating: 4.7,
        reviews: 1240,
        img: "assets/img/cat-fragrance.jpg",
        shipDays: "۹–۱۳",
        cat: "fragrance",
        match: ""
      }
    ],

    guides: [
      {
        id: "authenticity",
        title: "چگونه اصالت کالای وارداتی را بررسی کنیم؟",
        kicker: "راهنمای خرید",
        read: "۶ دقیقه",
        img: "assets/img/cat-supplements.jpg"
      },
      {
        id: "routine",
        title: "روتین سه‌مرحله‌ای پوست در هوای خشک پاییز",
        kicker: "مراقبت پوست",
        read: "۴ دقیقه",
        img: "assets/img/p-serum.jpg"
      },
      {
        id: "import",
        title: "از سفارش تا ترخیص: مسیر یک کالای وارداتی",
        kicker: "پشت صحنه",
        read: "۵ دقیقه",
        img: "assets/img/import-box.jpg"
      }
    ]
  };

  ROSEA.getProduct = function (id) {
    for (var i = 0; i < ROSEA.products.length; i++) {
      if (ROSEA.products[i].id === id) return ROSEA.products[i];
    }
    return null;
  };

  ROSEA.getSource = function (id) {
    for (var i = 0; i < ROSEA.sources.length; i++) {
      if (ROSEA.sources[i].id === id) return ROSEA.sources[i];
    }
    return { fa: id, en: id, cc: "", currency: "USD", tone: "#251B1E" };
  };

  ROSEA.getCategory = function (id) {
    for (var i = 0; i < ROSEA.categories.length; i++) {
      if (ROSEA.categories[i].id === id) return ROSEA.categories[i];
    }
    return { fa: id, en: id, note: "" };
  };

  global.ROSEA = ROSEA;
})(window);
