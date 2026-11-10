/* LAICO DESIGN — motion system (v1.3.0)
   Scroll reveal, parallax, hero title, header hide/show, progress bar,
   marquee velocity and page transitions.
   - Runs only when <html class="mo-on"> was set by the inline head script
     (i.e. the visitor has not requested reduced motion).
   - Elements are tagged here, never in the HTML, so the site still works without JS.
   - One rAF-throttled scroll handler; only transform/opacity/clip-path are animated. */
(function () {
  "use strict";

  var root = document.documentElement;
  if (!root.classList.contains("mo-on")) return;

  function $$(sel, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(sel)); }
  function clamp(v, a, b) { return Math.max(a, Math.min(b, v)); }
  var vh = window.innerHeight;
  var vw = window.innerWidth;
  var small = vw <= 720;
  var wide = vw > 1000;

  /* ------------------------------------------------------------------
     1. Hero title: split into words (words only; Arabic letters stay joined)
     ------------------------------------------------------------------ */
  function splitWords(el) {
    var n = 0;
    (function walk(node) {
      Array.prototype.slice.call(node.childNodes).forEach(function (c) {
        if (c.nodeType === 3) {
          if (!c.textContent.trim()) return;
          var frag = document.createDocumentFragment();
          c.textContent.split(/(\s+)/).forEach(function (part) {
            if (!part) return;
            if (/^\s+$/.test(part)) { frag.appendChild(document.createTextNode(" ")); return; }
            var outer = document.createElement("span");
            outer.className = "mo-w";
            var inner = document.createElement("span");
            inner.textContent = part;
            inner.style.setProperty("--w", n++);
            outer.appendChild(inner);
            frag.appendChild(outer);
          });
          c.parentNode.replaceChild(frag, c);
        } else if (c.nodeType === 1 && c.tagName !== "BR" && !c.hidden) {
          walk(c);
        }
      });
    })(el);
  }
  $$(".hero h1, .page-hero h1, .pd-top h1").forEach(splitWords);

  /* ------------------------------------------------------------------
     2. Scroll reveal (tag elements, observe once)
     ------------------------------------------------------------------ */
  var tagged = [];
  function covered(el) {
    for (var a = el.parentElement; a; a = a.parentElement) if (a.__mo) return true;
    return false;
  }
  function tag(el, kind, i) {
    if (!el || el.__mo || covered(el) || el.closest(".hero, .site-header, .page-hero, .pd-top")) return;
    el.__mo = kind;
    el.classList.add(kind === "m" ? "mo-m" : "mo-r");
    el.style.setProperty("--mo-i", clamp(i || 0, 0, 7));
    if (kind === "m") {
      var r = window.getComputedStyle(el).borderTopLeftRadius;
      if (r && r !== "0px") el.style.setProperty("--mo-r", r);
    }
    tagged.push(el);
  }
  function group(sel, kind, childSel, mod) {
    $$(sel).forEach(function (g) {
      var kids = childSel ? $$(childSel, g) : Array.prototype.slice.call(g.children);
      kids.forEach(function (k, i) { tag(k, kind, i % (mod || 8)); });
    });
  }
  function each(sel, kind) {
    $$(sel).forEach(function (el, i) { tag(el, kind, 0); });
  }

  // media first (so text rules skip what they contain)
  group(".jr-grid", "m", ".jr-media", 3);
  group(".jr-grid", "r", ".jr-body", 3);
  each(".jr-media, .pd-cover, .ar-cover, .svc-media, .media-frame, .systems-visual, .consult-photo, .svc-why-media, .contact-map img, .about-team img", "m");
  group(".about-collage-stage", "m");
  group(".pd-gallery", "m", null, 2);
  group(".pd-sketches", "r", null, 3);
  // grids / lists: children stagger by column
  group(".collection-grid", "m", null, 4);
  group(".project-grid", "m", null, 2);
  group(".prj-grid", "m", null, 3);
  group(".stats", "r");
  group(".steps", "r");
  group(".points", "r");
  group(".showroom-inner", "r");
  group(".about-values", "r");
  group(".footer-main", "r");
  // text blocks
  $$("main > section:not(.hero):not(.page-hero) , main > div").forEach(function (sec) {
    $$(".section-head, .copy, .eyebrow, .why-eyebrow, .why-quote, h2, h3, .lede, .pd-card, .pd-text, .jr-head, .jr-card--featured .jr-body, .svc-copy, .svc-panel, .svc-info, .svc-form-col, .about-field, .about-consult-panel, .about-consult-form, .about-story > *, .contact-card, .contact-col, .info-row, .field, .prose > *, .ar-body > *, .pd-pager, .cta-band > *, .prj-filters-inner > *, .prj-results, .pager, .btn-line, .systems-copy > *", sec)
      .forEach(function (el, i) { tag(el, "r", i % 4); });
  });

  var hasIO = "IntersectionObserver" in window;
  function finish(el) {
    window.setTimeout(function () {
      el.classList.remove("mo-r", "mo-m", "mo-in");
      el.style.removeProperty("--mo-i");
      el.style.removeProperty("--mo-r");
    }, 1700 + (parseInt(el.style.getPropertyValue("--mo-i"), 10) || 0) * 90);
  }
  function reveal(el) {
    if (el.__in) return;
    el.__in = true;
    el.classList.add("mo-in");
    finish(el);
  }
  if (!hasIO) {
    tagged.forEach(reveal);
  } else {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) { reveal(e.target); io.unobserve(e.target); }
      });
    }, { threshold: 0, rootMargin: "0px 0px -9% 0px" });
    tagged.forEach(function (el) { io.observe(el); });
  }
  window.addEventListener("beforeprint", function () { tagged.forEach(reveal); });

  /* ------------------------------------------------------------------
     3. Scroll-linked effects (single rAF-throttled handler)
     ------------------------------------------------------------------ */
  var heroMedia = document.querySelector(".hero-media");
  var heroCopy = document.querySelector(".hero-copy");
  var hint = document.querySelector(".scroll-hint");
  var hero = document.querySelector(".hero");
  var header = document.querySelector(".site-header");

  // image parallax: the frame clips, the image is scaled (CSS) and slides inside
  var frames = [];
  function addFrame(sel, speed) {
    $$(sel).forEach(function (f) {
      f.classList.add("mo-par");
      frames.push({ el: f, target: f, speed: speed, self: false });
    });
  }
  addFrame(".systems-visual", 1);
  addFrame(".media-frame", 0.9);
  addFrame(".consult-photo", 0.8);
  addFrame(".pd-cover", 0.7);
  addFrame(".ar-cover", 0.7);
  addFrame(".jr-card--featured .jr-media", 0.9);
  addFrame(".svc-why-media", 0.9);
  $$(".page-hero").forEach(function (h) { frames.push({ el: h, target: h, speed: 0.9, self: true }); });

  // depth drift: whole elements move at a slightly different pace (desktop only)
  var drifts = [];
  function addDrift(sel, amp) {
    if (!wide) return;
    $$(sel).forEach(function (el) { drifts.push({ el: el, amp: amp }); });
  }
  addDrift(".jr-grid > :nth-child(3n+2)", 26);
  addDrift(".project-grid > :nth-child(even)", 30);
  addDrift(".pd-gallery > :nth-child(even)", 22);
  addDrift(".about-collage-stage .c-sink, .about-collage-stage .c-glass", 22);
  addDrift(".about-collage-stage .c-run, .about-collage-stage .c-base", -18);
  addDrift(".systems-copy", -16);
  addDrift(".why-quote", -12);

  // progress bar
  var bar = document.createElement("div");
  bar.className = "mo-progress";
  bar.setAttribute("aria-hidden", "true");
  document.body.appendChild(bar);

  // marquee speed follows scroll velocity
  var mqAnim = null;
  var mq = document.querySelector(".marquee-track");
  if (mq && mq.getAnimations) { mqAnim = mq.getAnimations()[0] || null; }
  var vel = 0;

  var lastY = window.scrollY;
  var ticking = false;

  function update() {
    ticking = false;
    var y = window.scrollY;
    var dy = y - lastY;
    lastY = y;

    // hero: image slides slower than the page, copy lifts and fades
    if (hero && heroMedia) {
      var hh = hero.offsetHeight;
      if (y < hh * 1.1) {
        var max = hh * (small ? 0.1 : 0.16);
        heroMedia.style.translate = "0 " + Math.min(y * 0.2, max).toFixed(1) + "px";
        if (heroCopy) {
          heroCopy.style.translate = "0 " + (-y * 0.1).toFixed(1) + "px";
          heroCopy.style.opacity = clamp(1 - y / (hh * 0.72), 0, 1).toFixed(3);
        }
        if (hint) hint.style.opacity = clamp(1 - y / 160, 0, 1).toFixed(3);
      }
    }

    // image parallax
    for (var i = 0; i < frames.length; i++) {
      var f = frames[i];
      var r = f.el.getBoundingClientRect();
      if (r.bottom < -120 || r.top > vh + 120) continue;
      var p = clamp(((r.top + r.height / 2) - vh / 2) / (vh / 2 + r.height / 2), -1, 1);
      var amount = -p * r.height * 0.075 * f.speed * (small ? 0.6 : 1);
      f.target.style.setProperty("--py", amount.toFixed(1) + "px");
    }

    // drift
    for (var j = 0; j < drifts.length; j++) {
      var d = drifts[j];
      var rr = d.el.getBoundingClientRect();
      if (rr.bottom < -200 || rr.top > vh + 200) continue;
      var q = clamp(((rr.top + rr.height / 2) - vh / 2) / (vh / 2 + rr.height / 2), -1, 1);
      d.el.style.translate = "0 " + (q * d.amp).toFixed(1) + "px";
    }

    // progress
    var doc = document.documentElement.scrollHeight - vh;
    bar.style.transform = "scaleX(" + (doc > 0 ? clamp(y / doc, 0, 1) : 0).toFixed(4) + ")";

    // header: hide while reading down, return on any upward move
    if (header && !header.classList.contains("is-open")) {
      if (y < 160 || dy < -4) header.classList.remove("is-tucked");
      else if (dy > 4 && y > 320) header.classList.add("is-tucked");
    }

    // marquee
    vel += (Math.abs(dy) - vel) * 0.14;
    if (mqAnim) {
      mqAnim.playbackRate = 1 + Math.min(vel / 7, 7);
      if (vel > 0.05 && !ticking) { ticking = true; requestAnimationFrame(update); }
      else if (vel <= 0.05) mqAnim.playbackRate = 1;
    }
  }
  function onScroll() {
    if (!ticking) { ticking = true; requestAnimationFrame(update); }
  }
  window.addEventListener("scroll", onScroll, { passive: true });
  window.addEventListener("resize", function () {
    vh = window.innerHeight; vw = window.innerWidth; small = vw <= 720;
    onScroll();
  });
  header && header.addEventListener("focusin", function () { header.classList.remove("is-tucked"); });
  update();

  /* ------------------------------------------------------------------
     4. Mobile menu stagger index
     ------------------------------------------------------------------ */
  $$(".nav-panel > *").forEach(function (el, i) { el.style.setProperty("--n", i); });

  /* ------------------------------------------------------------------
     5. Page transitions: brand-colour curtain between internal pages
     ------------------------------------------------------------------ */
  document.addEventListener("click", function (e) {
    if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
    var a = e.target.closest && e.target.closest("a[href]");
    if (!a || a.target === "_blank" || a.hasAttribute("download")) return;
    var href = a.getAttribute("href");
    if (!href || href.charAt(0) === "#" || /^(mailto:|tel:|javascript:)/i.test(href)) return;
    var url;
    try { url = new URL(a.href, location.href); } catch (err) { return; }
    if (url.origin !== location.origin) return;
    if (url.pathname === location.pathname && url.search === location.search) return;   // same page (hash only)
    e.preventDefault();
    root.classList.add("mo-leave");
    window.setTimeout(function () { location.href = url.href; }, 280);
  });
  window.addEventListener("pageshow", function (e) {
    if (e.persisted) root.classList.remove("mo-leave");
  });
})();
