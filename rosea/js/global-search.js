/* ROSÉA — global search
   جست‌وجو در ایندکس کالاهای خارجی، تبدیل ارز، ساخت سفارش واردات،
   تشخیص لینک محصول از دامنهٔ فروشگاه‌های خارجی و لینک جست‌وجوی زنده.

   اتصال به API واقعی:
   ۱) آدرس سرویس را در SEARCH_ENDPOINT بگذارید؛ سرویس باید
      GET <endpoint>?q=... را بپذیرد و آرایه‌ای با ساختار globalIndex
      برگرداند (title, brand, source, price, rating, img, shipDays).
   ۲) اگر SEARCH_ENDPOINT خالی بماند، از ایندکس محلی js/data.js استفاده می‌شود.
   نمونهٔ پیاده‌سازی سمت سرور در DESIGN.md توضیح داده شده است. */
(function () {
  "use strict";
  var R = window.ROSEA;
  if (!R) return;
  var q = R.one, esc = R.esc, fa = R.faNum;

  var SEARCH_ENDPOINT = ""; // مثلاً "/api/global-search"
  var state = { q: "", sources: new Set(), sort: "relevance" };
  var timer = null;

  var resultsEl = q("[data-gs-results]");
  var countEl = q("[data-gs-count]");
  var chipsEl = q("[data-source-chips]");
  var statusEl = q("[data-gs-status]");
  var liveRow = q("[data-gs-live-row]");
  var sortEl = q("[data-gs-sort]");
  var input = q("[data-gs-q]");

  /* ------------------------- text token replacement ----------------------- */
  function fillTokens() {
    var map = {
      "{{USD}}": fa(R.rate.USD) + " تومان",
      "{{EUR}}": fa(R.rate.EUR) + " تومان",
      "{{GBP}}": fa(R.rate.GBP) + " تومان",
      "{{RATE_DATE}}": R.rate.updated,
      "{{FEE}}": fa(Math.round(R.importFee * 100)),
      "{{COUNT}}": fa(R.globalIndex.length)
    };
    var walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT, null);
    var node, batch = [];
    while ((node = walker.nextNode())) {
      if (node.nodeValue.indexOf("{{") > -1) batch.push(node);
    }
    batch.forEach(function (n) {
      var t = n.nodeValue;
      Object.keys(map).forEach(function (k) { t = t.split(k).join(map[k]); });
      n.nodeValue = t;
    });
  }

  function curSign(c) { return c === "USD" ? "$" : c === "EUR" ? "€" : "£"; }
  function rateOf(sourceId) {
    var s = R.getSource(sourceId);
    return R.rate[s.currency] || R.rate.USD;
  }
  function goodsToman(entry) { return Math.round(entry.price * rateOf(entry.source)); }
  function finalToman(entry) {
    var raw = goodsToman(entry) * (1 + (R.importFee || 0));
    return Math.ceil(raw / 10000) * 10000;
  }

  /* ------------------------------ source chips --------------------------- */
  function renderChips() {
    var total = R.globalIndex.length;
    chipsEl.innerHTML =
      '<button class="chip' + (state.sources.size === 0 ? " is-active" : "") + '" type="button" data-src="all">' +
      "همه منابع (" + fa(total) + ")</button>" +
      R.sources.map(function (s) {
        var n = R.globalIndex.filter(function (g) { return g.source === s.id; }).length;
        return '<button class="chip' + (state.sources.has(s.id) ? " is-active" : "") + '" type="button" data-src="' +
          s.id + '"' + (n === 0 ? " disabled" : "") + ">" + esc(s.en) + " (" + fa(n) + ")</button>";
      }).join("");
  }

  function renderSourceList() {
    var host = q("[data-source-list]");
    host.innerHTML = R.sources.map(function (s) {
      var n = R.globalIndex.filter(function (g) { return g.source === s.id; }).length;
      return '<a class="search-result" style="padding:6px 8px" target="_blank" rel="noopener noreferrer" href="' +
        s.search + encodeURIComponent(state.q || "beauty") + '">' +
        '<span aria-hidden="true" style="width:38px;height:38px;border-radius:11px;display:grid;place-items:center;background:' +
        s.tone + ';color:#fff;font-family:var(--ff-en);font-size:.82rem">' + esc(s.en.charAt(0)) + "</span>" +
        "<span><b class=\"en\" style=\"font-size:.78rem\">" + esc(s.en) + "</b><span>" + esc(s.fa) +
        " · " + s.cc + "</span></span><em style=\"font-size:.72rem;color:var(--ink-50)\">" + fa(n) + "</em></a>";
    }).join("");
  }

  /* -------------------------------- render ------------------------------- */
  function filtered() {
    var term = state.q.trim().toLowerCase();
    var list = R.globalIndex.filter(function (g) {
      if (state.sources.size && !state.sources.has(g.source)) return false;
      if (!term) return true;
      return (g.title + " " + g.brand + " " + g.cat).toLowerCase().indexOf(term) > -1;
    });
    if (state.sort === "cheap") list.sort(function (a, b) { return goodsToman(a) - goodsToman(b); });
    if (state.sort === "rating") list.sort(function (a, b) { return b.rating - a.rating; });
    return list;
  }

  function cardHTML(g) {
    var s = R.getSource(g.source);
    var rate = rateOf(g.source);
    var final = finalToman(g);
    return (
      '<article class="gs-result" data-entry="' + esc(g.id) + '">' +
      '<img src="' + esc(g.img) + '" alt="" width="96" height="96" loading="lazy">' +
      '<div class="gs-result__body">' +
      '<span class="gs-result__brand">' + esc(g.brand) + "</span>" +
      '<h3 class="gs-result__title">' + esc(g.title) + "</h3>" +
      '<div class="gs-result__meta">' +
      '<span class="src-badge"><i style="background:' + s.tone + '"></i>' + esc(s.en) + " · " + esc(s.cc) + "</span>" +
      '<span>' + R.icon("star").replace("<svg", '<svg style="width:11px;height:11px;display:inline;vertical-align:-1px;fill:var(--rose-2);stroke:none"') + " " +
      g.rating.toFixed(1).replace(".", "٫") + " (" + fa(g.reviews) + ")</span>" +
      '<span>تحویل ' + esc(g.shipDays) + " روز کاری</span>" +
      "</div></div>" +
      '<div class="gs-result__side">' +
      '<span class="gs-result__price">≈ ' + fa(goodsToman(g)) + " تومان</span>" +
      '<span class="gs-result__fx en">' + curSign(s.currency) + g.price + " · ۱ " + s.currency + " = " + fa(rate) + "</span>" +
      '<button class="btn btn--teal" type="button" data-import="' + esc(g.id) + '">ثبت سفارش واردات</button>' +
      '<span class="gs-result__fx">نهایی با کارمزد: ' + fa(final) + " ت</span>" +
      "</div></article>"
    );
  }

  function render(latency) {
    var list = filtered();
    countEl.textContent = fa(list.length);
    if (statusEl) statusEl.hidden = !latency;
    var paint = function () {
      resultsEl.innerHTML = list.length
        ? list.map(cardHTML).join("")
        : '<div class="gs-empty"><b>در منابع انتخابی پیدا نشد</b><span class="small">عبارت کوتاه‌تری امتحان کنید یا فیلتر منبع را بردارید.</span>' +
          '<div class="search-live">' + ["Vitamin C", "Retinol", "Niacinamide", "Collagen", "Hair Styler"].map(function (s) {
            return '<button class="chip" type="button" data-quick="' + s + '">' + s + "</button>";
          }).join("") + "</div></div>";
      if (statusEl) statusEl.hidden = true;
      renderLive();
    };
    if (latency) setTimeout(paint, latency);
    else paint();
  }

  function renderLive() {
    var term = state.q.trim() || "beauty";
    liveRow.innerHTML = '<span class="small" style="align-self:center">جست‌وجوی زنده در:</span>' +
      R.sources.filter(function (s) {
        return !state.sources.size || state.sources.has(s.id);
      }).map(function (s) {
        return '<a class="chip" target="_blank" rel="noopener noreferrer" href="' + s.search + encodeURIComponent(term) + '">' +
          esc(s.en) + " ↗</a>";
      }).join("");
  }

  /* -------------------------------- events ------------------------------- */
  chipsEl.addEventListener("click", function (e) {
    var b = e.target.closest("[data-src]");
    if (!b) return;
    var id = b.getAttribute("data-src");
    if (id === "all") state.sources.clear();
    else if (state.sources.has(id)) state.sources.delete(id);
    else state.sources.add(id);
    renderChips();
    render(180);
  });

  input.addEventListener("input", function () {
    clearTimeout(timer);
    state.q = input.value;
    timer = setTimeout(function () { render(240); }, 260);
  });

  q("[data-gs-form]").addEventListener("submit", function (e) {
    e.preventDefault();
    state.q = input.value.trim();
    var p = new URLSearchParams(location.search);
    if (state.q) p.set("q", state.q); else p.delete("q");
    try { history.replaceState(null, "", location.pathname + (p.toString() ? "?" + p.toString() : "")); } catch (err) {}
    render(420);
  });

  sortEl.addEventListener("change", function (e) {
    state.sort = e.target.value;
    render(0);
  });

  q("[data-gs-live]").addEventListener("click", function () {
    var term = state.q.trim() || "beauty";
    if (state.sources.size === 1) {
      var s = R.getSource(Array.from(state.sources)[0]);
      window.open(s.search + encodeURIComponent(term), "_blank", "noopener,noreferrer");
    } else {
      liveRow.scrollIntoView({ behavior: "smooth", block: "center" });
      R.toast("یکی از منابع را از فهرست پایین انتخاب کنید");
    }
  });

  document.addEventListener("click", function (e) {
    var el;
    if ((el = e.target.closest("[data-import]"))) {
      var id = el.getAttribute("data-import");
      var entry = R.globalIndex.filter(function (g) { return g.id === id; })[0];
      if (entry) R.addImport(entry, finalToman(entry));
      return;
    }
    if ((el = e.target.closest("[data-quick]"))) {
      input.value = el.getAttribute("data-quick");
      state.q = input.value;
      render(300);
    }
  });

  /* --------------------------- paste foreign link ------------------------ */
  var DOMAINS = {
    "amazon.": "amazon", "sephora.": "sephora", "notino.": "notino", "iherb.": "iherb",
    "cultbeauty.": "cultbeauty", "farfetch.": "farfetch", "lookfantastic.": "lookfantastic", "ulta.": "ulta"
  };
  var pasteForm = q("[data-paste-form]");
  var pasteOut = q("[data-paste-result]");

  pasteForm.addEventListener("submit", function (e) {
    e.preventDefault();
    var url = q("#paste-url").value.trim();
    if (!url) return;
    var lower = url.toLowerCase();
    var hit = null;
    Object.keys(DOMAINS).forEach(function (d) { if (lower.indexOf(d) > -1) hit = DOMAINS[d]; });
    if (!/^https?:\/\//.test(lower) && url.indexOf(".") > -1) url = "https://" + url;

    if (!hit) {
      pasteOut.innerHTML = '<div class="paste-result" style="background:#f6e4de;color:#7d3a4b">دامنه شناسایی نشد. ' +
        "فعلاً آمازون، سفورا، نوتینو، آی‌هرب، کالتی‌بیوتی، فارفچ، لوک‌فنتستیک و آلتا پشتیبانی می‌شوند.</div>";
      return;
    }
    var s = R.getSource(hit);
    var code = "R" + String(Math.floor(100000 + Math.random() * 899999));
    pasteOut.innerHTML =
      '<div class="paste-result"><b>لینک شناسایی شد: ' + esc(s.en) + " · " + s.cc + "</b>" +
      '<span style="font-size:.78rem">در نسخهٔ متصل، قیمت و موجودی از همین لینک خوانده می‌شود. برای نسخهٔ نمایشی، ' +
      "درخواست استعلام ثبت شد؛ کد پیگیری <b class=\"en\">" + code + "</b>.</span>" +
      '<a class="btn btn--teal btn--sm" style="justify-self:start;margin-block-start:4px" href="' +
      (lower.indexOf("http") === 0 ? esc(url) : esc("https://" + url)) + '" target="_blank" rel="noopener noreferrer">دیدن صفحهٔ محصول ↗</a></div>';
    try {
      var list = JSON.parse(localStorage.getItem("rosea-import-requests") || "[]");
      list.push({ code: code, url: url, source: hit, at: new Date().toISOString() });
      localStorage.setItem("rosea-import-requests", JSON.stringify(list));
    } catch (err) {}
    R.toast("درخواست استعلام ثبت شد · کد " + code);
  });

  /* --------------------------------- init -------------------------------- */
  fillTokens();
  state.q = new URLSearchParams(location.search).get("q") || "";
  if (state.q) input.value = state.q;
  renderChips();
  renderSourceList();
  render(0);
})();
