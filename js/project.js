/* LAICO DESIGN — project detail pages: accessible image lightbox */
(function () {
  "use strict";
  var all = Array.prototype.slice.call(document.querySelectorAll("[data-gallery-item]"));
  if (!all.length) return;
  // every [data-gallery] block (photo gallery, sketches, …) is browsed as its own set
  var items = all;
  function setOf(a) {
    var box = a.closest ? a.closest("[data-gallery]") : null;
    return box ? Array.prototype.slice.call(box.querySelectorAll("[data-gallery-item]")) : all;
  }

  var overlay = null, index = 0, opener = null;
  function rtl() { return document.documentElement.dir === "rtl"; }
  function en() { return document.documentElement.lang === "en"; }

  function show(i) {
    index = (i + items.length) % items.length;
    var src = items[index].getAttribute("href");
    var thumb = items[index].querySelector("img");
    var img = overlay.querySelector("img");
    img.src = src;
    img.alt = thumb ? thumb.alt : "";
    overlay.querySelector(".lb-count").textContent = (index + 1) + " / " + items.length;
  }

  function close() {
    if (!overlay) return;
    document.removeEventListener("keydown", onKey);
    overlay.remove();
    overlay = null;
    document.body.style.overflow = "";
    if (opener) opener.focus();
  }

  function onKey(e) {
    if (e.key === "Escape") close();
    else if (e.key === "ArrowRight") show(index + (rtl() ? -1 : 1));
    else if (e.key === "ArrowLeft") show(index + (rtl() ? 1 : -1));
    else if (e.key === "Tab") { e.preventDefault(); overlay.querySelector(".lb-close").focus(); }
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
      '<button type="button" class="lb-btn lb-prev" aria-label="' + (en() ? "Previous image" : "تصویر قبلی") + '">' + prevGlyph + "</button>" +
      "<img alt=\"\">" +
      '<button type="button" class="lb-btn lb-next" aria-label="' + (en() ? "Next image" : "تصویر بعدی") + '">' + nextGlyph + "</button>" +
      '<span class="lb-count" dir="ltr"></span>';
    document.body.appendChild(overlay);
    document.body.style.overflow = "hidden";
    overlay.addEventListener("click", function (e) { if (e.target === overlay) close(); });
    overlay.querySelector(".lb-close").addEventListener("click", close);
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
