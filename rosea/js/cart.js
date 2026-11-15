/* ROSÉA — cart page
   رندر سبد، محاسبهٔ ارسال، اعتبارسنجی فرم و پیام موفقیت.
   داده‌ها همان localStorage سبد است که app.js مدیریت می‌کند. */
(function () {
  "use strict";
  var R = window.ROSEA;
  if (!R) return;
  var q = R.one, esc = R.esc, fa = R.faNum;

  var listEl = q("[data-cart-list]");
  var emptyEl = q("[data-cart-empty]");
  var shipCost = 89000;
  var order = { done: false };

  function toFaDigits(s) {
    return String(s).replace(/[۰-۹]/g, function (d) { return "۰۱۲۳۴۵۶۷۸۹".indexOf(d); });
  }

  function render() {
    var items = R.cart.items();
    emptyEl.hidden = items.length > 0;
    listEl.innerHTML = items.map(function (it) {
      var kind = it.kind === "import"
        ? '<span class="src-badge"><i style="background:#1E9E96"></i>واردات · ' + esc(it.source) + "</span>"
        : "";
      return (
        '<div class="cart-line">' +
        '<img src="' + esc(it.img) + '" alt="' + esc(it.title) + '" width="84" height="96" loading="lazy">' +
        '<div class="cart-line__body">' +
        '<a class="cart-line__title" href="product.html?id=' + esc(it.id) + '">' + esc(it.title) + "</a>" +
        '<span class="small">' + esc(it.brand) + (it.variant ? " · " + esc(it.variant) : "") + "</span>" +
        kind +
        (it.note ? '<span class="small">' + esc(it.note) + "</span>" : "") +
        '<button class="cart-item__remove" type="button" data-cart-remove="' + esc(it.id) + '" data-variant="' +
        esc(it.variant) + '" style="justify-self:start">حذف از سبد</button>' +
        "</div>" +
        '<div class="cart-line__side">' +
        '<span class="cart-line__price">' + fa(it.price * it.qty) + " تومان</span>" +
        '<span class="qty"><button type="button" data-cart-qty="-1" data-id="' + esc(it.id) + '" data-variant="' +
        esc(it.variant) + '" aria-label="کاهش">−</button><span>' + fa(it.qty) +
        '</span><button type="button" data-cart-qty="1" data-id="' + esc(it.id) + '" data-variant="' +
        esc(it.variant) + '" aria-label="افزایش">+</button></span>' +
        "</div></div>"
      );
    }).join("");

    var sub = R.cart.subtotal();
    var hasImport = items.some(function (it) { return it.kind === "import"; });
    var freeFrom = R.freeShipFrom || 0;
    var ship = items.length === 0 ? 0 : sub >= freeFrom ? 0 : shipCost;
    q("[data-sum-sub]").textContent = fa(sub) + " تومان";
    q("[data-sum-ship]").textContent = items.length === 0 ? "—" : ship === 0 ? "رایگان" : fa(ship) + " تومان";
    q("[data-sum-import-row]").hidden = !hasImport;
    q("[data-sum-import]").textContent = hasImport ? "شامل کارمزد و ارسال بین‌المللی" : "";
    q("[data-sum-total]").textContent = fa(sub + ship) + " تومان";
    q("[data-ship-note]").textContent = items.length && ship > 0
      ? "با " + fa(freeFrom - sub) + " تومان خرید بیشتر، ارسال رایگان می‌شود."
      : items.length ? "ارسال این سفارش رایگان است." : "";
    if (order.done) q("[data-checkout]").hidden = true;
  }

  function setErr(name, on) {
    var err = q('[data-err="' + name + '"]');
    var field = err ? err.closest(".field") : null;
    if (err) err.hidden = !on;
    if (field) field.classList.toggle("is-invalid", on);
  }

  q("[data-checkout]").addEventListener("submit", function (e) {
    e.preventDefault();
    if (!R.cart.count()) { R.toast("سبد خرید خالی است"); return; }
    var f = e.target;
    var name = q("#co-name").value.trim();
    var phone = toFaDigits(q("#co-phone").value).replace(/[\s-]/g, "");
    var city = q("#co-city").value.trim();
    var addr = q("#co-address").value.trim();

    var bad = {
      name: name.length < 3,
      phone: !/^09\d{9}$/.test(phone),
      city: city.length < 2,
      address: addr.length < 8
    };
    Object.keys(bad).forEach(function (k) { setErr(k, bad[k]); });
    if (bad.name || bad.phone || bad.city || bad.address) {
      R.toast("چند فیلد را کامل کنید");
      var first = q(".field.is-invalid input, .field.is-invalid textarea");
      if (first) first.focus();
      return;
    }

    order.done = true;
    var code = "RS" + String(Math.floor(100000 + Math.random() * 899999));
    try {
      localStorage.setItem("rosea-last-order", JSON.stringify({
        code: code, name: name, phone: phone, city: city, address: addr,
        items: R.cart.items(), total: R.cart.subtotal(), at: new Date().toISOString()
      }));
    } catch (err) {}
    R.cart.clear();
    f.hidden = true;
    var ok = q("[data-success]");
    ok.hidden = false;
    q("[data-order-code]").textContent = code;
    R.toast("سفارش شما ثبت شد · " + code, "check");
    ok.scrollIntoView({ behavior: "smooth", block: "center" });
  });

  document.addEventListener("rosea:cart", render);
  render();
})();
