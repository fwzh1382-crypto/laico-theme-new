/* ROSÉA — product detail
   اطلاعات محصول از js/data.js خوانده می‌شود (?id=slug). گالری با برش‌های
   همان تصویر ساخته می‌شود، مقایسهٔ قیمت با منابع خارجی محاسبه و آکاردئون‌ها
   و انتخاب تنوع/تعداد به سبد متصل می‌شوند. */
(function () {
  "use strict";
  var R = window.ROSEA;
  if (!R) return;
  var q = R.one, all = R.all, esc = R.esc, fa = R.faNum;

  var id = new URLSearchParams(location.search).get("id");
  var p = R.getProduct(id) || R.products[0];
  var state = { variant: (p.variants && p.variants[0]) || "", qty: 1, crop: 0 };

  var CROPS = [
    { pos: "50% 50%", label: "نمای اصلی" },
    { pos: "50% 18%", label: "نمای نزدیک" },
    { pos: "50% 82%", label: "جزئیات پایین" },
    { pos: "26% 50%", label: "برش کناری" }
  ];

  function currencySign(cur) {
    return cur === "USD" ? "$" : cur === "EUR" ? "€" : "£";
  }

  function foreignRow(f) {
    var src = R.getSource(f.source);
    var rate = R.rate[src.currency] || R.rate.USD;
    var goods = Math.round(f.price * rate);
    return (
      '<div class="import-row"><span class="src"><i></i>' + esc(src.en) + " · " + esc(src.cc) +
      '</span><span class="fx">' + currencySign(src.currency) + fa(f.price) + '</span>' +
      '<span class="t">≈ ' + fa(goods) + " ت</span></div>"
    );
  }

  function render() {
    var off = p.oldPrice && p.oldPrice > p.price ? Math.round((1 - p.price / p.oldPrice) * 100) : 0;
    var cat = R.getCategory(p.cat);
    var cmpRows = (p.foreign || []).map(foreignRow).join("");
    var specs = (p.specs || []).map(function (s) {
      return "<tr><th>" + esc(s[0]) + "</th><td>" + esc(s[1]) + "</td></tr>";
    }).join("");
    var variants = (p.variants || []).map(function (v) {
      return '<button class="variant" type="button" data-variant="' + esc(v) + '" aria-pressed="' +
        (v === state.variant ? "true" : "false") + '">' + esc(v) + "</button>";
    }).join("");

    var root = q("[data-pdp-root]");
    root.innerHTML =
      '<div class="gallery">' +
      '<div class="gallery__main"><img data-main-img src="' + esc(p.img) + '" alt="' + esc(p.title + " — " + p.brand) +
      '" width="800" height="920" style="object-position:' + CROPS[0].pos + '"></div>' +
      '<div class="gallery__thumbs">' + CROPS.map(function (c, i) {
        return '<button class="gallery__thumb" type="button" data-crop="' + i + '" aria-current="' + (i === 0) +
          '" aria-label="' + esc(c.label) + '"><img src="' + esc(p.img) + '" alt="" width="160" height="160" ' +
          'loading="lazy" style="object-position:' + c.pos + '"></button>';
      }).join("") + "</div></div>" +

      '<div class="pdp__info">' +
      '<div class="pdp__top"><span class="pdp__brand">' + esc(p.brand) + "</span>" +
      '<p class="pdp__title">' + esc(p.title) + "</p>" +
      '<div class="pdp__rating"><span class="stars">' + R.icon("star") + "</span><b>" +
      p.rating.toFixed(1).replace(".", "٫") + "</b><span>" + fa(p.reviews) + " نظر ثبت‌شده</span>" +
      '<span class="src-badge"><i style="background:#1E9E96"></i>' + esc(p.origin) + "</span></div></div>" +

      '<div class="pdp__price"><span class="now">' + fa(p.price) + " <small>تومان</small></span>" +
      (p.oldPrice ? '<span class="old">' + fa(p.oldPrice) + "</span>" : "") +
      (off ? '<span class="discount">' + fa(off) + "٪ تخفیف</span>" : "") + "</div>" +

      '<p class="pdp__desc">' + esc(p.desc) + "</p>" +

      '<div class="variants"><span class="variants__label">انتخاب تنوع</span>' +
      '<div class="variants__list" data-variants>' + variants + "</div></div>" +

      '<div class="buy-row">' +
      '<div class="qty" data-qty><button type="button" data-step="-1" aria-label="کاهش تعداد">−</button>' +
      "<span data-qty-val>۱</span>" +
      '<button type="button" data-step="1" aria-label="افزایش تعداد">+</button></div>' +
      '<button class="btn btn--rose" type="button" data-add-pdp>افزودن به سبد خرید</button>' +
      '<button class="btn btn--light" type="button" data-open-cart aria-label="دیدن سبد خرید">سبد</button>' +
      "</div>" +

      '<div class="pdp__perks">' +
      '<span>' + R.icon("shield") + " اصالت تضمینی · کد رهگیری و فاکتور رسمی</span>" +
      '<span>' + R.icon("truck") + " ارسال ۲۴ ساعته در تهران، ۴۸ ساعت در شهرها</span>" +
      '<span>' + R.icon("box") + " ۷ روز مهلت مرجوعی برای کالای پلمب‌نشده</span>" +
      "</div>" +

      '<section class="import-compare"><div class="import-compare__head">' +
      '<b>همین کالا در منابع خارجی</b><span>' + R.rate.label + " · " + R.rate.updated + "</span></div>" +
      (cmpRows || '<p class="import-compare__note">برای این کالا منبع خارجی ثبت نشده است.</p>') +
      '<p class="import-compare__note">قیمت‌های بالا فقط ارزش کالا در فروشگاه مبدأست و هزینهٔ ارسال و ترخیص را ندارد. ' +
      'سفارش واردات از <a href="global-search.html" style="color:#DCF2F0;text-decoration:underline">جست‌وجوی جهانی</a> انجام می‌شود.</p></section>' +

      '<div class="acc">' +
      '<div class="acc__item"><button class="acc__btn" type="button" data-acc aria-expanded="true" aria-controls="acc-specs">' +
      "مشخصات و اصالت" + R.icon("plus") + '</button><div class="acc__panel" id="acc-specs"><table><tbody>' +
      specs + "</tbody></table></div></div>" +
      '<div class="acc__item"><button class="acc__btn" type="button" data-acc aria-expanded="false" aria-controls="acc-ship">' +
      "ارسال، بسته‌بندی و مرجوعی" + R.icon("plus") + '</button><div class="acc__panel" id="acc-ship" hidden>' +
      "<p>سفارش‌های تهران تا ۲۴ ساعت کاری و شهرستان‌ها تا ۴۸ ساعت با پست پیشتاز یا تیپاکس ارسال می‌شوند. " +
      "همهٔ کالاها در جعبهٔ محافظ با پلمب ارسال می‌شوند؛ برای مرجوعی کافی است پلمب سالم باشد و تا ۷ روز درخواست ثبت کنید.</p></div></div>" +
      '<div class="acc__item"><button class="acc__btn" type="button" data-acc aria-expanded="false" aria-controls="acc-auth">' +
      "بررسی اصالت — چه چیزی را می‌بینید؟" + R.icon("plus") + '</button><div class="acc__panel" id="acc-auth" hidden>' +
      "<p>کارت اصالت با کد رهگیری، فاکتور رسمی خرید و تصویر بارنامه/اظهارنامهٔ گمرکی. کد را می‌توانید در " +
      '<a href="guide.html#authenticity" style="text-decoration:underline">راهنمای اصالت</a> استعلام کنید. ' +
      "اگر کالا با توضیحات نخواند، هزینهٔ ارسال برگشت با ماست.</p></div></div>" +
      "</div></div>";

    /* breadcrumb + head */
    var crumb = q("[data-crumb]");
    crumb.insertAdjacentHTML("beforeend",
      '<span>/</span><a href="shop.html?cat=' + p.cat + '">' + esc(cat.fa) + "</a>" +
      "<span>/</span><span>" + esc(p.title) + "</span>");
    var h1 = q("[data-pdp-h1]");
    if (h1) h1.textContent = p.title + " — " + p.brand;
    document.title = p.title + " | " + p.brand + " — رُزآ";
    var meta = document.querySelector('meta[name="description"]');
    if (meta) meta.setAttribute("content", p.desc.slice(0, 150));

    /* related */
    var rel = R.products.filter(function (x) { return x.cat === p.cat && x.id !== p.id; });
    R.products.forEach(function (x) { if (rel.length < 4 && x.id !== p.id && rel.indexOf(x) < 0) rel.push(x); });
    R.renderProducts(q("[data-related-grid]"), rel.slice(0, 4));
  }

  function updateQty() {
    q("[data-qty-val]").textContent = fa(state.qty);
  }

  document.addEventListener("click", function (e) {
    var el;
    if ((el = e.target.closest("[data-crop]"))) {
      state.crop = parseInt(el.getAttribute("data-crop"), 10);
      all(".gallery__thumb").forEach(function (b) { b.setAttribute("aria-current", "false"); });
      el.setAttribute("aria-current", "true");
      var img = q("[data-main-img]");
      img.style.objectPosition = CROPS[state.crop].pos;
      return;
    }
    if ((el = e.target.closest("[data-variant]"))) {
      state.variant = el.getAttribute("data-variant");
      all("[data-variant]").forEach(function (b) { b.setAttribute("aria-pressed", "false"); });
      el.setAttribute("aria-pressed", "true");
      return;
    }
    if ((el = e.target.closest("[data-step]"))) {
      state.qty = Math.max(1, Math.min(20, state.qty + parseInt(el.getAttribute("data-step"), 10)));
      updateQty();
      return;
    }
    if ((el = e.target.closest("[data-add-pdp]"))) {
      R.cart.add({
        id: p.id, title: p.title, brand: p.brand, img: p.img,
        price: p.price, qty: state.qty, variant: state.variant, kind: "shop"
      });
      R.toast("به سبد اضافه شد: " + p.title);
      R.openCart();
    }
  });

  render();
})();
