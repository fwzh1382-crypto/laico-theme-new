/* LAICO DESIGN — project detail pages: sketch "show all" toggle + accessible image lightbox */
(function () {
  "use strict";
  var root = document.documentElement;

  /* ---- sketches: a long set starts collapsed (two rows); the toggle shows the rest.
     The collapse is applied here, so without JS every sketch stays visible. ---- */
  Array.prototype.forEach.call(document.querySelectorAll("[data-sketch-collapsible]"), function (grid) {
    var sec = grid.closest ? grid.closest("section") : grid.parentNode;
    var btn = sec ? sec.querySelector("[data-sketch-toggle]") : null;
    if (!btn) return;
    function set(collapsed) {
      grid.classList[collapsed ? "add" : "remove"]("is-collapsed");
      btn.setAttribute("aria-expanded", collapsed ? "false" : "true");
    }
    set(true);
    btn.hidden = false;
    if (btn.parentNode && btn.parentNode !== sec) btn.parentNode.hidden = false;
    btn.addEventListener("click", function () {
      var collapse = !grid.classList.contains("is-collapsed");
      set(collapse);
      if (!collapse) return;
      // back to the top of the section, below the fixed header
      var head = sec.querySelector("h2") || sec;
      var bar = document.querySelector(".site-header");
      var top = head.getBoundingClientRect().top;
      var y = (window.pageYOffset || root.scrollTop || 0) + top - (bar ? bar.offsetHeight : 0) - 16;
      if (top < 0) window.scrollTo(0, Math.max(0, y));
    });
  });

  var all = Array.prototype.slice.call(document.querySelectorAll("[data-gallery-item]"));
  if (!all.length) return;
  // every [data-gallery] block (photo gallery, sketches, …) is browsed as its own set
  var items = all;
  function setOf(a) {
    var box = a.closest ? a.closest("[data-gallery]") : null;
    return box ? Array.prototype.slice.call(box.querySelectorAll("[data-gallery-item]")) : all;
  }

  var overlay = null, index = 0, opener = null;
  function rtl() { return root.dir === "rtl"; }
  function en() { return root.lang === "en"; }
  function flag(name, on) { overlay.classList[on ? "add" : "remove"](name); }

  // zoom: the sheet is shown at reading size inside a scrollable frame (plans, details, small lettering)
  function zoom(on) {
    if (!overlay) return;
    var img = overlay.querySelector("img");
    var btn = overlay.querySelector(".lb-zoom");
    on = !!on && overlay.classList.contains("lb--zoomable");
    flag("is-zoom", on);
    img.style.width = on && img.naturalWidth
      ? Math.min(img.naturalWidth, Math.max(window.innerWidth - 32, 1200)) + "px"
      : "";
    btn.setAttribute("aria-pressed", on ? "true" : "false");
    btn.textContent = on ? "−" : "+";
    if (!on) { var fig = overlay.querySelector(".lb-fig"); fig.scrollTop = 0; fig.scrollLeft = 0; }
  }

  function show(i) {
    index = (i + items.length) % items.length;
    var item = items[index];
    var thumb = item.querySelector("img");
    var cap = item.querySelector(".pd-sketch-cap");
    var img = overlay.querySelector("img");
    zoom(false);
    flag("lb--zoomable", false);
    img.onload = function () {
      if (!overlay) return;
      var shown = img.getBoundingClientRect().width || img.clientWidth || 0;
      flag("lb--zoomable", shown > 0 && img.naturalWidth > shown * 1.2);
    };
    img.src = item.getAttribute("href");
    img.alt = thumb ? thumb.alt : "";
    overlay.querySelector(".lb-cap").innerHTML = cap ? cap.innerHTML : "";
    flag("lb--cap", !!cap);
    overlay.querySelector(".lb-count").textContent = (index + 1) + " / " + items.length;
  }

  function close() {
    if (!overlay) return;
    document.removeEventListener("keydown", onKey);
    overlay.remove();
    overlay = null;
    document.body.style.overflow = "";
    document.documentElement.style.overflow = "";
    if (opener) opener.focus();
  }

  function onKey(e) {
    if (e.key === "Escape") close();
    else if (e.key === "ArrowRight") show(index + (rtl() ? -1 : 1));
    else if (e.key === "ArrowLeft") show(index + (rtl() ? 1 : -1));
    else if (e.key === "Tab") {
      // keep focus inside the dialog: cycle through its visible buttons
      e.preventDefault();
      var btns = Array.prototype.filter.call(overlay.querySelectorAll("button"), function (b) { return b.getClientRects().length > 0; });
      var at = btns.indexOf(document.activeElement);
      var next = e.shiftKey ? (at <= 0 ? btns.length - 1 : at - 1) : (at + 1) % btns.length;
      if (btns[next]) btns[next].focus();
    }
  }

  function open(i, trigger) {
    opener = trigger;
    overlay = document.createElement("div");
    overlay.className = "lb";
    overlay.setAttribute("role", "dialog");
    overlay.setAttribute("aria-modal", "true");
    overlay.setAttribute("aria-label", en() ? "Image gallery" : "گالری تصاویر");
    if (items.length < 2) overlay.className += " lb--single";
    var prevGlyph = rtl() ? "›" : "‹";
    var nextGlyph = rtl() ? "‹" : "›";
    overlay.innerHTML =
      '<button type="button" class="lb-btn lb-close" aria-label="' + (en() ? "Close" : "بستن") + '">×</button>' +
      '<button type="button" class="lb-btn lb-zoom" aria-pressed="false" aria-label="' + (en() ? "Zoom" : "بزرگ‌نمایی") + '">+</button>' +
      '<button type="button" class="lb-btn lb-prev" aria-label="' + (en() ? "Previous image" : "تصویر قبلی") + '">' + prevGlyph + "</button>" +
      '<figure class="lb-fig"><img alt=""><figcaption class="lb-cap"></figcaption></figure>' +
      '<button type="button" class="lb-btn lb-next" aria-label="' + (en() ? "Next image" : "تصویر بعدی") + '">' + nextGlyph + "</button>" +
      '<span class="lb-count" dir="ltr"></span>';
    document.body.appendChild(overlay);
    document.body.style.overflow = "hidden";
    document.documentElement.style.overflow = "hidden";
    var fig = overlay.querySelector(".lb-fig");
    overlay.addEventListener("click", function (e) { if (e.target === overlay || e.target === fig) close(); });
    overlay.querySelector(".lb-close").addEventListener("click", close);
    overlay.querySelector(".lb-zoom").addEventListener("click", function () { zoom(!overlay.classList.contains("is-zoom")); });
    overlay.querySelector("img").addEventListener("click", function () { zoom(!overlay.classList.contains("is-zoom")); });
    overlay.querySelector(".lb-prev").addEventListener("click", function () { show(index - 1); });
    overlay.querySelector(".lb-next").addEventListener("click", function () { show(index + 1); });
    document.addEventListener("keydown", onKey);
    show(i);
    overlay.querySelector(".lb-close").focus();
  }

  all.forEach(function (a) {
    a.addEventListener("click", function (e) {
      e.preventDefault();
      items = setOf(a);
      open(Math.max(0, items.indexOf(a)), a);
    });
  });
})();
