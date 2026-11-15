/* ROSÉA — shop page
   فیلتر دسته/برند/قیمت، مرتب‌سازی، چیپ‌های فیلتر فعال، بارگذاری تدریجی.
   وضعیت در URL نگه داشته می‌شود (?q=&cat=&brand=&sort=) تا لینک قابل اشتراک باشد. */
(function () {
  "use strict";
  var R = window.ROSEA;
  if (!R) return;
  var q = R.one;

  /* وضعیت موجودی/مدرک — در نسخهٔ متصل به API از سرور می‌آید */
  var META = {
    "atelier-nord-silk-scarf": { stock: false },
    "pure-ritual-import-box": { customs: false }
  };
  function meta(p) {
    var m = META[p.id] || {};
    return { stock: m.stock !== false, customs: m.customs !== false };
  }

  function norm(s) {
    return String(s || "")
      .toLowerCase()
      .replace(/ي/g, "ی").replace(/ك/g, "ک")
      .replace(/\u200c/g, " ")
      .replace(/[ًٌٍَُِّْ]/g, "");
  }

  var params = new URLSearchParams(location.search);
  var state = {
    q: params.get("q") || "",
    cats: new Set(params.get("cat") ? params.get("cat").split(",") : []),
    brands: new Set(params.get("brand") ? params.get("brand").split(",") : []),
    min: 0,
    max: 0,
    instock: true,
    customs: true,
    sort: params.get("sort") || "featured",
    shown: 6
  };
  if (state.sort === "new") state.shown = 6;

  var grid = q("[data-grid]");
  var countEl = q("[data-count]");
  var chipsEl = q("[data-chips]");
  var moreBtn = q("[data-more]");
  var moreNote = q("[data-more-note]");
  var minInput = q("[data-min]");
  var maxInput = q("[data-max]");
  var sortSel = q("[data-sort]");
  var instockInput = q("[data-instock]");
  var customsInput = q("[data-customs]");

  /* ---------------------------- filter controls --------------------------- */
  function renderFilters() {
    var catsHost = q("[data-filter-cats]");
    catsHost.innerHTML = R.categories.map(function (c) {
      var n = R.products.filter(function (p) { return p.cat === c.id; }).length;
      return '<label class="check"><input type="checkbox" data-cat="' + c.id + '"' +
        (state.cats.has(c.id) ? " checked" : "") + "><span>" + R.esc(c.fa) +
        '</span><span class="count">' + R.faNum(n) + "</span></label>";
    }).join("");

    var brands = [];
    R.products.forEach(function (p) { if (brands.indexOf(p.brand) < 0) brands.push(p.brand); });
    brands.sort();
    var brandHost = q("[data-filter-brands]");
    brandHost.innerHTML = brands.map(function (b) {
      var n = R.products.filter(function (p) { return p.brand === b; }).length;
      return '<label class="check"><input type="checkbox" data-brand="' + R.esc(b) + '"' +
        (state.brands.has(b) ? " checked" : "") + "><span>" + R.esc(b) +
        '</span><span class="count">' + R.faNum(n) + "</span></label>";
    }).join("");
  }

  function apply(skipUrl) {
    var list = R.products.filter(function (p) {
      var m = meta(p);
      if (state.instock && !m.stock) return false;
      if (state.customs && !m.customs) return false;
      if (state.cats.size && !state.cats.has(p.cat)) return false;
      if (state.brands.size && !state.brands.has(p.brand)) return false;
      if (state.min && p.price < state.min) return false;
      if (state.max && p.price > state.max) return false;
      if (state.q) {
        var hay = norm(p.title + " " + p.brand + " " + R.getCategory(p.cat).fa + " " + p.origin + " " + p.desc);
        if (hay.indexOf(norm(state.q)) < 0) return false;
      }
      return true;
    });

    var sorters = {
      featured: function (a, b) { return (b.badge ? 1 : 0) - (a.badge ? 1 : 0) || b.rating - a.rating; },
      new: function (a, b) { return b.id.localeCompare(a.id); },
      cheap: function (a, b) { return a.price - b.price; },
      expensive: function (a, b) { return b.price - a.price; },
      off: function (a, b) {
        var oa = a.oldPrice ? a.oldPrice - a.price : 0, ob = b.oldPrice ? b.oldPrice - b.price : 0;
        return ob - oa;
      },
      rating: function (a, b) { return b.rating - a.rating; }
    };
    list.sort(sorters[state.sort] || sorters.featured);

    countEl.textContent = R.faNum(list.length);
    R.renderProducts(grid, list.slice(0, state.shown));

    var rest = list.length - state.shown;
    moreBtn.hidden = rest <= 0;
    moreNote.textContent = rest > 0 ? R.faNum(rest) + " محصول دیگر" : "";
    if (!list.length) moreNote.textContent = "";

    renderChips();
    if (!skipUrl) syncUrl();
  }

  function renderChips() {
    var parts = [];
    if (state.q) parts.push({ k: "q", label: "جست‌وجو: " + state.q });
    state.cats.forEach(function (c) { parts.push({ k: "cat", v: c, label: R.getCategory(c).fa }); });
    state.brands.forEach(function (b) { parts.push({ k: "brand", v: b, label: b }); });
    if (state.min) parts.push({ k: "min", label: "از " + R.faNum(state.min) });
    if (state.max) parts.push({ k: "max", label: "تا " + R.faNum(state.max) });
    if (!state.instock) parts.push({ k: "instock", label: "شامل ناموجود" });
    if (!state.customs) parts.push({ k: "customs", label: "بدون مدرک گمرکی" });

    chipsEl.innerHTML = parts.map(function (p) {
      return '<span class="tagx">' + R.esc(p.label) +
        '<button type="button" data-chip="' + p.k + '" data-val="' + R.esc(p.v || "") + '" aria-label="حذف فیلتر ' + R.esc(p.label) + '">×</button></span>';
    }).join("");
  }

  function syncUrl() {
    var p = new URLSearchParams();
    if (state.q) p.set("q", state.q);
    if (state.cats.size) p.set("cat", Array.from(state.cats).join(","));
    if (state.brands.size) p.set("brand", Array.from(state.brands).join(","));
    if (state.sort !== "featured") p.set("sort", state.sort);
    var url = location.pathname + (p.toString() ? "?" + p.toString() : "");
    try { history.replaceState(null, "", url); } catch (e) {}
  }

  function reset() {
    state.q = "";
    state.cats.clear();
    state.brands.clear();
    state.min = 0;
    state.max = 0;
    state.instock = true;
    state.customs = true;
    state.sort = "featured";
    state.shown = 6;
    minInput.value = "";
    maxInput.value = "";
    sortSel.value = "featured";
    instockInput.checked = true;
    customsInput.checked = true;
    renderFilters();
    apply();
  }

  /* ------------------------------- listeners ----------------------------- */
  q("#sort").addEventListener("change", function (e) {
    state.sort = e.target.value;
    state.shown = 6;
    apply();
  });
  q(".shop-layout").addEventListener("change", function (e) {
    var t = e.target;
    if (t.hasAttribute("data-cat")) {
      if (t.checked) state.cats.add(t.getAttribute("data-cat"));
      else state.cats.delete(t.getAttribute("data-cat"));
      state.shown = 6;
      apply();
    }
    if (t.hasAttribute("data-brand")) {
      if (t.checked) state.brands.add(t.getAttribute("data-brand"));
      else state.brands.delete(t.getAttribute("data-brand"));
      state.shown = 6;
      apply();
    }
    if (t.hasAttribute("data-instock")) { state.instock = t.checked; apply(); }
    if (t.hasAttribute("data-customs")) { state.customs = t.checked; apply(); }
  });
  [minInput, maxInput].forEach(function (el) {
    el.addEventListener("input", function () {
      state.min = parseInt(minInput.value, 10) || 0;
      state.max = parseInt(maxInput.value, 10) || 0;
      apply();
    });
  });
  q("[data-reset]").addEventListener("click", reset);
  q("[data-more]").addEventListener("click", function () {
    state.shown += 6;
    apply();
  });
  chipsEl.addEventListener("click", function (e) {
    var b = e.target.closest("[data-chip]");
    if (!b) return;
    var k = b.getAttribute("data-chip"), v = b.getAttribute("data-val");
    if (k === "q") state.q = "";
    if (k === "cat") state.cats.delete(v);
    if (k === "brand") state.brands.delete(v);
    if (k === "min") { state.min = 0; minInput.value = ""; }
    if (k === "max") { state.max = 0; maxInput.value = ""; }
    if (k === "instock") { state.instock = true; instockInput.checked = true; }
    if (k === "customs") { state.customs = true; customsInput.checked = true; }
    renderFilters();
    apply();
  });
  document.addEventListener("rosea:reset-filters", reset);

  /* -------------------------------- start -------------------------------- */
  if (state.q) {
    var hero = q(".page-hero h1");
    if (hero) hero.insertAdjacentHTML("afterend", '<p class="lede" data-q-note>نتایج جست‌وجو برای «' + R.esc(state.q) + '»</p>');
  }
  if (state.sort) sortSel.value = state.sort;
  renderFilters();
  apply(true);
})();
