/* ROSÉA — shared UI runtime
   - سبد خرید و سفارش واردات (localStorage)
   - کشوی سبد، لایهٔ جست‌وجو، منوی موبایل، توست
   - کارت محصول، مبلغ فارسی، آکاردئون، ریویل اسکرول
   بدون وابستگی؛ همهٔ متن‌ها فارسی و اعداد با ارقام فارسی نمایش داده می‌شوند. */
(function () {
  "use strict";
  if (!window.ROSEA) return;

  var R = window.ROSEA;
  var doc = document;
  var root = doc.documentElement;
  root.classList.add("js");

  var CART_KEY = "rosea-cart-v1";
  var FAV_KEY = "rosea-favs-v1";

  var ICON = {
    search: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.6-3.6"/></svg>',
    cart: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5h2l2.2 10.2a2 2 0 0 0 2 1.6h7.4a2 2 0 0 0 2-1.5L21 8H7"/><circle cx="10" cy="20" r="1.2"/><circle cx="18" cy="20" r="1.2"/></svg>',
    close: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg>',
    plus: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>',
    minus: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><path d="M5 12h14"/></svg>',
    check: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m4 12.5 5 5L20 6.5"/></svg>',
    star: '<svg viewBox="0 0 24 24"><path d="M12 3.2l2.6 5.4 5.9.8-4.3 4.1 1 5.9-5.2-2.8-5.2 2.8 1-5.9L3.5 9.4l5.9-.8z"/></svg>',
    heart: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><path d="M12 20s-7.5-4.6-7.5-9.4A4.1 4.1 0 0 1 12 7.6a4.1 4.1 0 0 1 7.5 3c0 4.8-7.5 9.4-7.5 9.4Z"/></svg>',
    shield: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7 3v6c0 4.4-3 7.6-7 9-4-1.4-7-4.6-7-9V6z"/><path d="m9 12 2 2 4-4"/></svg>',
    truck: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7h11v9H3zM14 10h4l3 3v3h-7"/><circle cx="7" cy="18" r="1.5"/><circle cx="17.5" cy="18" r="1.5"/></svg>',
    globe: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M3.5 9h17M3.5 15h17M12 3c2.5 3 2.5 15 0 18M12 3c-2.5 3-2.5 15 0 18"/></svg>',
    box: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="m3 8 9-4 9 4-9 4z"/><path d="M3 8v8l9 4 9-4V8"/><path d="M12 12v8"/></svg>',
    spark: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M12 3v4M12 17v4M3 12h4M17 12h4M6 6l2.5 2.5M15.5 15.5 18 18M18 6l-2.5 2.5M8.5 15.5 6 18"/></svg>',
    arrow: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M11 6l-6 6 6 6"/></svg>',
    menu: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h10"/></svg>'
  };
  R.icon = function (n) { return ICON[n] || ""; };

  /* ------------------------------- helpers ------------------------------- */
  function faNum(v) {
    try { return Number(v).toLocaleString("fa-IR"); }
    catch (e) { return String(v); }
  }
  R.faNum = faNum;
  R.money = faNum;

  function esc(s) {
    return String(s == null ? "" : s).replace(/[&<>"']/g, function (c) {
      return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c];
    });
  }
  R.esc = esc;

  function byId(id) { return doc.getElementById(id); }
  function one(sel, ctx) { return (ctx || doc).querySelector(sel); }
  function all(sel, ctx) { return Array.prototype.slice.call((ctx || doc).querySelectorAll(sel)); }
  R.one = one; R.all = all;

  function moneyHTML(n, extra) {
    return faNum(n) + " <small>تومان</small>" + (extra || "");
  }

  function toast(msg, icon) {
    var host = one(".toasts");
    if (!host) {
      host = doc.createElement("div");
      host.className = "toasts";
      doc.body.appendChild(host);
    }
    var el = doc.createElement("div");
    el.className = "toast";
    el.setAttribute("role", "status");
    el.innerHTML = (ICON[icon || "check"] || "") + "<span>" + esc(msg) + "</span>";
    host.appendChild(el);
    setTimeout(function () {
      el.classList.add("is-out");
      setTimeout(function () { el.remove(); }, 320);
    }, 2600);
  }
  R.toast = toast;

  /* --------------------------------- cart -------------------------------- */
  function readCart() {
    try {
      var raw = JSON.parse(localStorage.getItem(CART_KEY) || "[]");
      return Array.isArray(raw) ? raw : [];
    } catch (e) { return []; }
  }
  function writeCart(items) {
    try { localStorage.setItem(CART_KEY, JSON.stringify(items)); } catch (e) {}
    renderCart();
    doc.dispatchEvent(new CustomEvent("rosea:cart", { detail: items }));
  }
  R.cart = {
    items: readCart,
    count: function () {
      return readCart().reduce(function (n, it) { return n + it.qty; }, 0);
    },
    subtotal: function () {
      return readCart().reduce(function (n, it) { return n + it.price * it.qty; }, 0);
    },
    add: function (item) {
      var items = readCart();
      var key = item.id + "|" + (item.variant || "");
      var found = null;
      items.forEach(function (it) {
        if (it.id + "|" + (it.variant || "") === key) found = it;
      });
      if (found) found.qty += item.qty || 1;
      else items.push({
        id: item.id,
        title: item.title,
        brand: item.brand || "",
        img: item.img,
        price: item.price,
        qty: item.qty || 1,
        variant: item.variant || "",
        kind: item.kind || "shop",
        source: item.source || "",
        note: item.note || ""
      });
      writeCart(items);
      return items;
    },
    setQty: function (id, variant, qty) {
      var items = readCart().map(function (it) {
        if (it.id === id && (it.variant || "") === (variant || "")) it.qty = Math.max(1, Math.min(20, qty));
        return it;
      });
      writeCart(items);
    },
    remove: function (id, variant) {
      writeCart(readCart().filter(function (it) {
        return !(it.id === id && (it.variant || "") === (variant || ""));
      }));
    },
    clear: function () { writeCart([]); }
  };

  function updateBadges() {
    var n = R.cart.count();
    all("[data-cart-count]").forEach(function (el) {
      el.textContent = faNum(n);
      el.hidden = n === 0;
    });
  }

  /* ------------------------------- drawers ------------------------------- */
  var overlay = null, cartDrawer = null, searchSheet = null, navDrawer = null;
  var lastFocus = null;

  function buildChrome() {
    overlay = doc.createElement("div");
    overlay.className = "overlay";
    overlay.setAttribute("data-overlay", "");
    doc.body.appendChild(overlay);

    /* cart */
    cartDrawer = doc.createElement("aside");
    cartDrawer.className = "drawer";
    cartDrawer.id = "cart-drawer";
    cartDrawer.setAttribute("aria-label", "سبد خرید");
    cartDrawer.setAttribute("aria-hidden", "true");
    cartDrawer.innerHTML =
      '<div class="drawer__head"><b>سبد خرید <span class="small" data-cart-count-label></span></b>' +
      '<button class="drawer__close" type="button" data-close-drawer aria-label="بستن سبد">' + ICON.close + "</button></div>" +
      '<div class="drawer__body" data-cart-body></div>' +
      '<div class="drawer__foot" data-cart-foot></div>';
    doc.body.appendChild(cartDrawer);

    /* search sheet */
    searchSheet = doc.createElement("section");
    searchSheet.className = "search-sheet";
    searchSheet.id = "search-sheet";
    searchSheet.setAttribute("aria-label", "جست‌وجو");
    searchSheet.setAttribute("aria-hidden", "true");
    searchSheet.innerHTML =
      '<div class="search-sheet__in"><div class="search-sheet__head"><b class="h3">جست‌وجو</b>' +
      '<button class="drawer__close" type="button" data-close-search aria-label="بستن جست‌وجو">' + ICON.close + "</button></div>" +
      '<form class="search-sheet__field" data-search-form role="search">' + ICON.search +
      '<label class="visually-hidden" for="sheet-q">نام محصول یا برند</label>' +
      '<input id="sheet-q" type="search" autocomplete="off" placeholder="محصول، برند یا لینک از سایت خارجی…">' +
      '<kbd>Esc</kbd></form>' +
      '<p class="search-sheet__label">پیشنهاد سریع</p><div class="search-live" data-search-suggest></div>' +
      '<div data-search-results></div></div>';
    doc.body.appendChild(searchSheet);

    /* mobile nav */
    navDrawer = doc.createElement("aside");
    navDrawer.className = "drawer";
    navDrawer.id = "nav-drawer";
    navDrawer.setAttribute("aria-label", "منوی اصلی");
    navDrawer.setAttribute("aria-hidden", "true");
    navDrawer.innerHTML =
      '<div class="drawer__head"><b>منو</b>' +
      '<button class="drawer__close" type="button" data-close-nav aria-label="بستن منو">' + ICON.close + "</button></div>" +
      '<div class="drawer__body">' +
      navLinks() +
      '<div class="fgroup"><b>دسته‌بندی‌ها</b>' +
      R.categories.map(function (c) {
        return '<a class="mega__link" href="shop.html?cat=' + c.id + '"><span class="mega__dot"></span>' + esc(c.fa) + "</a>";
      }).join("") +
      "</div></div>" +
      '<div class="drawer__foot"><a class="btn btn--rose btn--block" href="global-search.html">' + ICON.globe + " جست‌وجو در سایت‌های خارجی</a></div>";
    doc.body.appendChild(navDrawer);

    [cartDrawer, searchSheet, navDrawer].forEach(function (el) { el.inert = true; });

    var toasts = doc.createElement("div");
    toasts.className = "toasts";
    doc.body.appendChild(toasts);
  }

  function navLinks() {
    var page = doc.body.getAttribute("data-page") || "";
    var links = [
      ["index.html", "خانه", "home"],
      ["shop.html", "فروشگاه", "shop"],
      ["global-search.html", "جست‌وجوی جهانی", "global"],
      ["guide.html", "راهنمای خرید", "guide"],
      ["cart.html", "سبد خرید", "cart"]
    ];
    return links.map(function (l) {
      var cur = l[2] === page ? ' aria-current="page"' : "";
      return '<a class="mega__link"' + cur + ' href="' + l[0] + '">' + esc(l[1]) + "</a>";
    }).join("");
  }

  function openLayer(el) {
    lastFocus = doc.activeElement;
    if (overlay) overlay.classList.add("is-open");
    if (el) {
      el.classList.add("is-open");
      el.setAttribute("aria-hidden", "false");
      el.inert = false;
    }
    doc.body.classList.add("is-locked");
  }
  function closeLayers() {
    [cartDrawer, searchSheet, navDrawer].forEach(function (el) {
      if (el) {
        el.classList.remove("is-open");
        el.setAttribute("aria-hidden", "true");
        el.inert = true;
      }
    });
    if (overlay) overlay.classList.remove("is-open");
    doc.body.classList.remove("is-locked");
    if (lastFocus && lastFocus.focus) lastFocus.focus();
  }
  R.openCart = function () { openLayer(cartDrawer); renderCart(); };
  R.closeLayers = closeLayers;

  /* ------------------------------ cart render ---------------------------- */
  function renderCart() {
    updateBadges();
    if (!cartDrawer) return;
    var body = one("[data-cart-body]", cartDrawer);
    var foot = one("[data-cart-foot]", cartDrawer);
    var items = readCart();
    var label = one("[data-cart-count-label]", cartDrawer);
    var n = R.cart.count();
    label.textContent = n ? "(" + faNum(n) + " قلم)" : "";

    if (!items.length) {
      body.innerHTML =
        '<div class="cart-empty">' + ICON.box +
        "<b>سبد خرید خالی است</b><span class=\"small\">از فروشگاه یا جست‌وجوی جهانی محصول انتخاب کنید.</span>" +
        '<a class="btn btn--light btn--sm" href="shop.html">رفتن به فروشگاه</a></div>';
      foot.innerHTML = '<a class="btn btn--ghost btn--block" href="shop.html">شروع خرید</a>';
      return;
    }

    body.innerHTML = items.map(function (it) {
      var kindTag = it.kind === "import" ? '<span class="src-badge"><i style="background:#1E9E96"></i>واردات · ' + esc(it.source) + "</span>" : "";
      return (
        '<div class="cart-item">' +
        '<img src="' + esc(it.img) + '" alt="' + esc(it.title) + '" width="68" height="82" loading="lazy">' +
        '<div class="cart-item__body">' +
        '<div class="cart-item__top"><span class="cart-item__title">' + esc(it.title) + "</span>" +
        '<button class="cart-item__remove" type="button" data-cart-remove="' + esc(it.id) + '" data-variant="' + esc(it.variant) + '">حذف</button></div>' +
        '<div class="cart-item__meta">' + esc(it.brand) + (it.variant ? " · " + esc(it.variant) : "") + "</div>" +
        kindTag +
        (it.note ? '<div class="cart-item__meta">' + esc(it.note) + "</div>" : "") +
        '<div class="cart-item__row"><span class="qty">' +
        '<button type="button" data-cart-qty="-1" data-id="' + esc(it.id) + '" data-variant="' + esc(it.variant) + '" aria-label="کاهش">−</button>' +
        "<span>" + faNum(it.qty) + "</span>" +
        '<button type="button" data-cart-qty="1" data-id="' + esc(it.id) + '" data-variant="' + esc(it.variant) + '" aria-label="افزایش">+</button>' +
        '</span><span class="cart-item__price">' + moneyHTML(it.price * it.qty) + "</span></div>" +
        "</div></div>"
      );
    }).join("");

    var sub = R.cart.subtotal();
    var freeFrom = R.freeShipFrom || 0;
    var remain = Math.max(0, freeFrom - sub);
    var pct = Math.min(100, Math.round((sub / freeFrom) * 100));
    foot.innerHTML =
      '<div class="ship-bar"><span>' +
      (remain > 0 ? "تا ارسال رایگان " + faNum(remain) + " تومان مانده" : "ارسال این سفارش رایگان است ✓") +
      '</span><div class="ship-bar__track"><div class="ship-bar__fill" style="width:' + pct + '%"></div></div></div>' +
      '<div class="sum-row"><span>جمع کالاها</span><span>' + moneyHTML(sub) + "</span></div>" +
      '<a class="btn btn--rose btn--block" href="cart.html">تکمیل خرید ' + ICON.arrow + "</a>" +
      '<p class="form-note">ارسال از تهران · پرداخت درگاه امن · ۷ روز مهلت مرجوعی</p>';
  }

  /* ------------------------------ add handlers --------------------------- */
  function handleAdd(id, btn) {
    var p = R.getProduct(id);
    if (!p) return;
    var variant = p.variants && p.variants.length ? p.variants[0] : "";
    R.cart.add({ id: p.id, title: p.title, brand: p.brand, img: p.img, price: p.price, variant: variant, kind: "shop" });
    toast("«" + p.title + "» به سبد اضافه شد");
    if (btn) {
      btn.classList.add("is-added");
      btn.innerHTML = ICON.check;
      setTimeout(function () {
        btn.classList.remove("is-added");
        btn.innerHTML = ICON.plus;
      }, 1600);
    }
  }
  R.addProduct = handleAdd;
  R.addImport = function (entry, price) {
    R.cart.add({
      id: "import-" + entry.id,
      title: entry.title,
      brand: entry.brand,
      img: entry.img,
      price: price,
      qty: 1,
      variant: "",
      kind: "import",
      source: (R.getSource(entry.source).fa || ""),
      note: "سفارش از " + (R.getSource(entry.source).en || "") + " · تحویل " + entry.shipDays + " روز کاری"
    });
    toast("سفارش واردات ثبت شد: " + entry.title);
    R.openCart();
  };

  /* ------------------------------- card HTML ----------------------------- */
  R.cardHTML = function (p) {
    var off = p.oldPrice && p.oldPrice > p.price ? Math.round((1 - p.price / p.oldPrice) * 100) : 0;
    return (
      '<article class="card" data-product="' + esc(p.id) + '">' +
      '<div class="card__media">' +
      '<img src="' + esc(p.img) + '" alt="' + esc(p.title + " — " + p.brand) + '" width="400" height="500" loading="lazy">' +
      (p.badge ? '<span class="card__badge' + (/اصل|گارانتی|گمرک/.test(p.badge) ? " card__badge--teal" : "") + '">' + esc(p.badge) + "</span>" : "") +
      '<button class="card__fav" type="button" data-fav="' + esc(p.id) + '" aria-label="افزودن به علاقه‌مندی‌ها" aria-pressed="false">' + ICON.heart + "</button>" +
      "</div>" +
      '<div class="card__body">' +
      '<span class="card__brand">' + esc(p.brand) + "</span>" +
      '<h3 class="card__title"><a href="product.html?id=' + encodeURIComponent(p.id) + '">' + esc(p.title) + "</a></h3>" +
      '<div class="card__meta"><span class="stars">' + ICON.star + "</span><b>" + p.rating.toFixed(1).replace(".", "٫") + "</b><span>· " + faNum(p.reviews) + " نظر</span>" +
      (off ? '<span class="discount">' + faNum(off) + "٪</span>" : "") + "</div>" +
      '<div class="card__foot"><div class="price"><span class="price__now">' + faNum(p.price) + ' <small>تومان</small></span>' +
      (p.oldPrice ? '<span class="price__old">' + faNum(p.oldPrice) + "</span>" : "") + "</div>" +
      '<button class="card__add" type="button" data-add="' + esc(p.id) + '" aria-label="افزودن ' + esc(p.title) + ' به سبد">' + ICON.plus + "</button>" +
      "</div></div></article>"
    );
  };

  R.renderProducts = function (host, list) {
    if (!host) return;
    host.innerHTML = list.length
      ? list.map(R.cardHTML).join("")
      : '<div class="empty-state"><b>محصولی با این فیلترها پیدا نشد</b><span>فیلترها را ساده‌تر کنید یا همهٔ محصولات را ببینید.</span><button class="btn btn--light btn--sm" type="button" data-reset-all>حذف فیلترها</button></div>';
    syncFavs();
  };

  /* ------------------------------ favourites ----------------------------- */
  function favs() {
    try { return JSON.parse(localStorage.getItem(FAV_KEY) || "[]"); } catch (e) { return []; }
  }
  function syncFavs() {
    var list = favs();
    all("[data-fav]").forEach(function (b) {
      var on = list.indexOf(b.getAttribute("data-fav")) > -1;
      b.classList.toggle("is-on", on);
      b.setAttribute("aria-pressed", on ? "true" : "false");
    });
  }
  function toggleFav(id) {
    var list = favs();
    var i = list.indexOf(id);
    if (i > -1) list.splice(i, 1);
    else list.push(id);
    try { localStorage.setItem(FAV_KEY, JSON.stringify(list)); } catch (e) {}
    syncFavs();
    toast(i > -1 ? "از علاقه‌مندی‌ها حذف شد" : "به علاقه‌مندی‌ها اضافه شد", "heart");
  }

  /* -------------------------------- search ------------------------------- */
  function searchAll(q) {
    q = (q || "").trim().toLowerCase();
    var out = { products: [], global: [] };
    if (!q) return out;
    out.products = R.products.filter(function (p) {
      return (p.title + " " + p.brand + " " + R.getCategory(p.cat).fa + " " + p.origin).toLowerCase().indexOf(q) > -1;
    }).slice(0, 5);
    out.global = R.globalIndex.filter(function (g) {
      return (g.title + " " + g.brand).toLowerCase().indexOf(q) > -1;
    }).slice(0, 4);
    return out;
  }
  R.searchAll = searchAll;

  function renderSearch(q) {
    var host = one("[data-search-results]", searchSheet);
    var suggest = one("[data-search-suggest]", searchSheet);
    if (!q) {
      host.innerHTML = "";
      suggest.innerHTML = ["سرم ویتامین C", "عطر", "کلاژن", "اتو مو", "رژ لب"].map(function (s) {
        return '<button class="chip" type="button" data-suggest="' + s + '">' + s + "</button>";
      }).join("");
      return;
    }
    var res = searchAll(q);
    var html = "";
    if (res.products.length) {
      html += '<p class="search-sheet__label">در فروشگاه رُزآ</p><div class="search-results">' +
        res.products.map(function (p) {
          return '<a class="search-result" href="product.html?id=' + encodeURIComponent(p.id) + '">' +
            '<img src="' + esc(p.img) + '" alt="" width="44" height="44" loading="lazy">' +
            "<span><b>" + esc(p.title) + "</b><span>" + esc(p.brand) + " · " + esc(R.getCategory(p.cat).fa) + "</span></span>" +
            "<em>" + faNum(p.price) + " ت</em></a>";
        }).join("") + "</div>";
    }
    if (res.global.length) {
      html += '<p class="search-sheet__label">در سایت‌های خارجی</p><div class="search-results">' +
        res.global.map(function (g) {
          var s = R.getSource(g.source);
          return '<a class="search-result" href="global-search.html?q=' + encodeURIComponent(g.title) + '">' +
            '<img src="' + esc(g.img) + '" alt="" width="44" height="44" loading="lazy">' +
            "<span><b>" + esc(g.title) + "</b><span>" + esc(s.en) + " · " + esc(g.brand) + "</span></span>" +
            "<em>" + (s.currency === "USD" ? "$" : s.currency === "EUR" ? "€" : "£") + g.price + "</em></a>";
        }).join("") + "</div>";
    }
    if (!html) html = '<div class="gs-empty"><b>نتیجه‌ای پیدا نشد</b><span class="small">می‌توانید همین عبارت را در سایت‌های خارجی جست‌وجو کنید.</span></div>';
    html += '<p class="search-sheet__label">جست‌وجوی زنده در منابع خارجی</p><div class="search-live">' +
      R.sources.slice(0, 6).map(function (s) {
        return '<a class="chip" target="_blank" rel="noopener noreferrer" href="' + s.search + encodeURIComponent(q) + '">' + esc(s.en) + " ↗</a>";
      }).join("") + "</div>" +
      '<div style="margin-block-start:14px"><a class="btn btn--teal btn--sm" href="global-search.html?q=' + encodeURIComponent(q) + '">همهٔ نتایج در جست‌وجوی جهانی ' + ICON.arrow + "</a></div>";
    host.innerHTML = html;
  }

  /* --------------------------------- boot -------------------------------- */
  buildChrome();
  renderCart();
  syncFavs();

  /* header stuck */
  var head = one(".site-head");
  if (head) {
    var onScroll = function () { head.classList.toggle("is-stuck", window.scrollY > 8); };
    onScroll();
    window.addEventListener("scroll", onScroll, { passive: true });
  }

  doc.addEventListener("click", function (e) {
    var t = e.target;
    var el;

    if ((el = t.closest("[data-open-cart]"))) { e.preventDefault(); R.openCart(); return; }
    if ((el = t.closest("[data-close-drawer],[data-close-search],[data-close-nav]"))) {
      e.preventDefault(); closeLayers(); return;
    }
    if (t.closest("[data-overlay]")) { closeLayers(); return; }
    if ((el = t.closest("[data-open-search]"))) {
      e.preventDefault();
      openLayer(searchSheet);
      var input = one("#sheet-q", searchSheet);
      renderSearch("");
      setTimeout(function () { if (input) input.focus(); }, 240);
      return;
    }
    if ((el = t.closest("[data-open-nav]"))) { e.preventDefault(); openLayer(navDrawer); return; }
    if ((el = t.closest("[data-add]"))) { e.preventDefault(); handleAdd(el.getAttribute("data-add"), el); return; }
    if ((el = t.closest("[data-fav]"))) { e.preventDefault(); toggleFav(el.getAttribute("data-fav")); return; }
    if ((el = t.closest("[data-cart-qty]"))) {
      var id = el.getAttribute("data-id"), variant = el.getAttribute("data-variant");
      var items = R.cart.items();
      var cur = items.filter(function (it) { return it.id === id && (it.variant || "") === variant; })[0];
      if (cur) R.cart.setQty(id, variant, cur.qty + parseInt(el.getAttribute("data-cart-qty"), 10));
      return;
    }
    if ((el = t.closest("[data-cart-remove]"))) {
      R.cart.remove(el.getAttribute("data-cart-remove"), el.getAttribute("data-variant"));
      toast("کالا از سبد حذف شد");
      return;
    }
    if ((el = t.closest("[data-suggest]"))) {
      var q = el.getAttribute("data-suggest");
      var inp = one("#sheet-q", searchSheet);
      inp.value = q;
      renderSearch(q);
      return;
    }
    if ((el = t.closest("[data-acc]"))) {
      var panel = byId(el.getAttribute("aria-controls"));
      var open = el.getAttribute("aria-expanded") === "true";
      el.setAttribute("aria-expanded", open ? "false" : "true");
      if (panel) panel.hidden = open;
      return;
    }
    if ((el = t.closest("[data-reset-all]"))) {
      doc.dispatchEvent(new CustomEvent("rosea:reset-filters"));
      return;
    }
  });

  doc.addEventListener("input", function (e) {
    if (e.target && e.target.id === "sheet-q") renderSearch(e.target.value);
  });

  doc.addEventListener("submit", function (e) {
    var form = e.target;
    if (form.matches("[data-search-form]")) {
      e.preventDefault();
      var q = (one("#sheet-q", searchSheet).value || "").trim();
      if (q) window.location.href = "global-search.html?q=" + encodeURIComponent(q);
      return;
    }
    if (form.matches("[data-newsletter]")) {
      e.preventDefault();
      var note = one("[data-news-msg]", form.parentNode);
      if (note) {
        note.textContent = "ثبت شد · کد ۱۰٪ تخفیف اولین خرید: ROSEA10";
        note.classList.add("form-note--ok");
      }
      form.reset();
      toast("عضویت شما در خبرنامه ثبت شد");
      return;
    }
  });

  doc.addEventListener("keydown", function (e) {
    if (e.key === "Escape") closeLayers();
    if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === "k") {
      e.preventDefault();
      openLayer(searchSheet);
      setTimeout(function () { var i = one("#sheet-q", searchSheet); if (i) i.focus(); }, 200);
    }
  });

  /* reveal on scroll */
  var items = all(".rv");
  if (items.length && "IntersectionObserver" in window) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting) {
          en.target.classList.add("in");
          io.unobserve(en.target);
        }
      });
    }, { rootMargin: "0px 0px -8% 0px", threshold: 0.08 });
    items.forEach(function (el) { io.observe(el); });
    setTimeout(function () { items.forEach(function (el) { el.classList.add("in"); }); }, 1600);
  } else {
    items.forEach(function (el) { el.classList.add("in"); });
  }

  /* year */
  all("[data-year]").forEach(function (el) { el.textContent = "۱۴۰۴"; });
})();
