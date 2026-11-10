/**
 * Laico Design — WordPress Admin Panel & CPT Meta Box Scripts (`resources/js/admin.js`).
 *
 * Powers:
 * - Single Media Library picker buttons (`.laico-pick-media` for Logo, Favicon, OG Image, Video, Poster)
 * - About Page Media Cards (`.laico-pick-media-card` & `.laico-reset-media-card` with live preview + attachment ID)
 * - Multi-image Project Gallery picker (`#laico-pick-gallery`, `#laico-clear-gallery`, `.laico-remove-thumb`)
 * - Multi-image Project Sketch picker (`#laico-pick-sketches`, `#laico-clear-sketches`)
 * - Live English fields toggle (`#laico_enable_english`) & About Page template detection
 */

(function () {
  'use strict';

  function ready(fn) {
    if (document.readyState !== 'loading') {
      fn();
    } else {
      document.addEventListener('DOMContentLoaded', fn);
    }
  }

  ready(function () {
    // 1. Single Media URL Picker (`.laico-pick-media`)
    document.querySelectorAll('.laico-pick-media').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        if (!window.wp || !window.wp.media) return;

        var targetSelector = btn.getAttribute('data-target');
        var mediaType = btn.getAttribute('data-type') || 'image';
        var input = targetSelector ? document.querySelector(targetSelector) : null;
        if (!input) return;

        var frame = window.wp.media({
          title: mediaType === 'video' ? 'انتخاب فایل ویدیو' : 'انتخاب تصویر',
          button: { text: 'انتخاب و جایگذاری' },
          library: { type: mediaType },
          multiple: false,
        });

        frame.on('select', function () {
          var attachment = frame.state().get('selection').first().toJSON();
          if (attachment && attachment.url) {
            input.value = attachment.url;
            input.dispatchEvent(new Event('change', { bubbles: true }));
          }
        });

        frame.open();
      });
    });

    // 2. Delegated Rich Media Cards (`.laico-pick-media-card` & `.laico-reset-media-card` — supports dynamic repeater rows)
    document.addEventListener('click', function (e) {
      var pickBtn = e.target.closest('.laico-pick-media-card');
      if (pickBtn) {
        var card = pickBtn.closest('[data-media-card]');
        if (!card || !window.wp || !window.wp.media) return;
        e.preventDefault();

        var idInput = card.querySelector('[data-media-id]');
        var urlInput = card.querySelector('[data-media-url]');
        var altInput = card.querySelector('[data-media-alt]');
        var previewImg = card.querySelector('.laico-media-card-preview img');

        var frame = window.wp.media({
          title: 'انتخاب تصویر',
          button: { text: 'استفاده از این تصویر' },
          library: { type: 'image' },
          multiple: false,
        });

        frame.on('select', function () {
          var data = frame.state().get('selection').first().toJSON();
          if (!data || !data.url) return;
          if (idInput) idInput.value = data.id ? String(data.id) : '';
          if (urlInput) urlInput.value = data.url;
          if (altInput && !altInput.value.trim() && data.alt) {
            altInput.value = data.alt;
          }
          if (previewImg) {
            var previewSrc =
              (data.sizes && data.sizes.medium_large && data.sizes.medium_large.url) ||
              (data.sizes && data.sizes.large && data.sizes.large.url) ||
              data.url;
            previewImg.src = previewSrc;
          }
        });

        frame.open();
        return;
      }

      var resetBtn = e.target.closest('.laico-reset-media-card');
      if (resetBtn) {
        var resetCard = resetBtn.closest('[data-media-card]');
        if (!resetCard) return;
        e.preventDefault();
        var rId = resetCard.querySelector('[data-media-id]');
        var rUrl = resetCard.querySelector('[data-media-url]');
        var rImg = resetCard.querySelector('.laico-media-card-preview img');
        if (rId) rId.value = '';
        if (rUrl) rUrl.value = '';
        if (rImg) {
          rImg.src = rImg.getAttribute('data-default-src') || '';
        }
      }
    });

    document.addEventListener('change', function (e) {
      var urlInput = e.target.closest('[data-media-card] [data-media-url]');
      if (!urlInput) return;
      var card = urlInput.closest('[data-media-card]');
      if (!card) return;
      var idInput = card.querySelector('[data-media-id]');
      var previewImg = card.querySelector('.laico-media-card-preview img');
      if (idInput) idInput.value = '';
      if (previewImg) {
        previewImg.src = urlInput.value.trim() || previewImg.getAttribute('data-default-src') || '';
      }
    });

    // 3. Live Toggle for English Settings Fields (`#laico_enable_english`)
    var enToggle = document.getElementById('laico_enable_english');
    if (enToggle) {
      var syncEnFields = function () {
        var on = !!enToggle.checked;
        document.querySelectorAll('.laico-en-only-field').forEach(function (el) {
          el.style.display = on ? '' : 'none';
        });
      };
      enToggle.addEventListener('change', syncEnFields);
      syncEnFields();
    }

    // 4. Automatically reveal `#laico_about_page_meta` & `#laico_services_page_meta` when editing or choosing template
    var aboutBox = document.getElementById('laico_about_page_meta');
    var servicesBox = document.getElementById('laico_services_page_meta');
    if (aboutBox || servicesBox) {
      var syncSpecialMetaBoxes = function (tpl, slug) {
        if (aboutBox) {
          var isAbout =
            aboutBox.classList.contains('is-about-page-active') ||
            slug === 'about' ||
            (tpl && String(tpl).indexOf('template-about') !== -1);
          aboutBox.classList.toggle('is-about-page-hidden', !isAbout);
        }
        if (servicesBox) {
          var isServices =
            servicesBox.classList.contains('is-services-page-active') ||
            slug === 'services' ||
            (tpl && String(tpl).indexOf('template-services') !== -1);
          servicesBox.classList.toggle('is-services-page-hidden', !isServices);
        }
      };

      var classicTpl = document.getElementById('page_template');
      if (classicTpl) {
        classicTpl.addEventListener('change', function () {
          syncSpecialMetaBoxes(classicTpl.value, '');
        });
      }

      if (window.wp && window.wp.data && window.wp.data.subscribe && window.wp.data.select) {
        var lastStateKey = '';
        window.wp.data.subscribe(function () {
          var editor = window.wp.data.select('core/editor');
          if (!editor || typeof editor.getEditedPostAttribute !== 'function') return;
          var tpl = editor.getEditedPostAttribute('template') || '';
          var slug = editor.getEditedPostAttribute('slug') || '';
          var key = tpl + '|' + slug;
          if (key !== lastStateKey) {
            lastStateKey = key;
            syncSpecialMetaBoxes(tpl, slug);
          }
        });
      }
    }

    // 4b. Repeatable `svc-row` controls inside `#laico-svc-rows-wrap`
    var svcRowsWrap = document.getElementById('laico-svc-rows-wrap');
    var addSvcRowBtn = document.getElementById('laico-add-svc-row');
    var svcRowTemplate = document.getElementById('laico-svc-row-template');

    function renumberSvcRows() {
      if (!svcRowsWrap) return;
      svcRowsWrap.querySelectorAll('[data-svc-row]').forEach(function (row, idx) {
        var badge = row.querySelector('[data-svc-row-num]');
        if (badge) badge.textContent = String(idx + 1);
      });
    }

    if (svcRowsWrap) {
      renumberSvcRows();

      if (addSvcRowBtn && svcRowTemplate) {
        addSvcRowBtn.addEventListener('click', function () {
          var nextIdx = parseInt(svcRowsWrap.getAttribute('data-next-index') || '0', 10) + 1;
          svcRowsWrap.setAttribute('data-next-index', String(nextIdx));
          var token = 'new_' + nextIdx + '_' + Date.now();
          var html = svcRowTemplate.innerHTML.replace(/__INDEX__/g, token);
          var holder = document.createElement('div');
          holder.innerHTML = html.trim();
          var newRow = holder.firstElementChild;
          if (newRow) {
            svcRowsWrap.appendChild(newRow);
            renumberSvcRows();
            var titleField = newRow.querySelector('[data-svc-title-input]');
            if (titleField) titleField.focus();
          }
        });
      }

      svcRowsWrap.addEventListener('click', function (e) {
        var rmBtn = e.target.closest('[data-svc-remove]');
        if (rmBtn) {
          e.preventDefault();
          var rowToRemove = rmBtn.closest('[data-svc-row]');
          if (rowToRemove) {
            rowToRemove.remove();
            renumberSvcRows();
          }
          return;
        }

        var mvBtn = e.target.closest('[data-svc-move]');
        if (mvBtn) {
          e.preventDefault();
          var rowToMove = mvBtn.closest('[data-svc-row]');
          if (!rowToMove) return;
          var dir = mvBtn.getAttribute('data-svc-move');
          if (dir === 'up' && rowToMove.previousElementSibling) {
            svcRowsWrap.insertBefore(rowToMove, rowToMove.previousElementSibling);
            renumberSvcRows();
          } else if (dir === 'down' && rowToMove.nextElementSibling) {
            svcRowsWrap.insertBefore(rowToMove.nextElementSibling, rowToMove);
            renumberSvcRows();
          }
        }
      });

      svcRowsWrap.addEventListener('input', function (e) {
        var titleInput = e.target.closest('[data-svc-title-input]');
        if (!titleInput) return;
        var row = titleInput.closest('[data-svc-row]');
        var heading = row && row.querySelector('[data-svc-row-heading]');
        if (heading) {
          heading.textContent = titleInput.value.trim() || 'ردیف خدمت جدید';
        }
      });
    }

    // 4c. Live toggle for IPPanel SMS Mode (`pattern` vs `text`) in Theme Settings -> Forms
    document.querySelectorAll('[data-sms-mode-select]').forEach(function (selectEl) {
      selectEl.addEventListener('change', function () {
        var target = selectEl.getAttribute('data-sms-mode-select');
        var mode = selectEl.value;
        document.querySelectorAll('[data-sms-mode-panel^="' + target + ':"]').forEach(function (panel) {
          var parts = (panel.getAttribute('data-sms-mode-panel') || '').split(':');
          panel.style.display = parts[1] === mode ? '' : 'none';
        });
      });
    });

    // 5. Multi-Image Project Gallery Picker (`#laico-pick-gallery`)
    var galleryBtn = document.getElementById('laico-pick-gallery');
    var clearBtn = document.getElementById('laico-clear-gallery');
    var hiddenIds = document.getElementById('laico_gallery_ids');
    var previewBox = document.getElementById('laico-gallery-preview');

    function syncGalleryIdsFromDom() {
      if (!hiddenIds || !previewBox) return;
      var ids = [];
      previewBox.querySelectorAll('.laico-gallery-thumb[data-id]').forEach(function (el) {
        var id = parseInt(el.getAttribute('data-id'), 10);
        if (id > 0 && ids.indexOf(id) === -1) {
          ids.push(id);
        }
      });
      hiddenIds.value = ids.join(',');
    }

    if (previewBox) {
      previewBox.addEventListener('click', function (e) {
        var rm = e.target.closest('.laico-remove-thumb');
        if (!rm) return;
        e.preventDefault();
        var item = rm.closest('.laico-gallery-thumb');
        if (item) {
          item.remove();
          syncGalleryIdsFromDom();
        }
      });
    }

    if (clearBtn && previewBox && hiddenIds) {
      clearBtn.addEventListener('click', function (e) {
        e.preventDefault();
        previewBox.innerHTML = '';
        hiddenIds.value = '';
      });
    }

    if (galleryBtn && previewBox && hiddenIds) {
      galleryBtn.addEventListener('click', function (e) {
        e.preventDefault();
        if (!window.wp || !window.wp.media) return;

        var frame = window.wp.media({
          title: 'انتخاب تصاویر گالری پروژه',
          button: { text: 'افزودن به گالری پروژه' },
          library: { type: 'image' },
          multiple: true,
        });

        frame.on('select', function () {
          var selection = frame.state().get('selection');
          selection.each(function (attachment) {
            var data = attachment.toJSON();
            if (!data || !data.id) return;
            if (previewBox.querySelector('.laico-gallery-thumb[data-id="' + data.id + '"]')) {
              return;
            }
            var thumbUrl =
              (data.sizes && data.sizes.thumbnail && data.sizes.thumbnail.url) ||
              data.url;

            var div = document.createElement('div');
            div.className = 'laico-gallery-thumb';
            div.setAttribute('data-id', String(data.id));

            var img = document.createElement('img');
            img.src = thumbUrl;
            img.alt = '';

            var rmBtn = document.createElement('button');
            rmBtn.type = 'button';
            rmBtn.className = 'laico-remove-thumb';
            rmBtn.setAttribute('aria-label', 'حذف');
            rmBtn.textContent = '×';

            div.appendChild(img);
            div.appendChild(rmBtn);
            previewBox.appendChild(div);
          });

          syncGalleryIdsFromDom();
        });

        frame.open();
      });
    }

    // 6. Multi-Image Project Sketch Picker (`#laico-pick-sketches`) — same thumbnails as the gallery, own field
    var sketchBtn = document.getElementById('laico-pick-sketches');
    var sketchClearBtn = document.getElementById('laico-clear-sketches');
    var sketchIdsInput = document.getElementById('laico_sketch_ids');
    var sketchBox = document.getElementById('laico-sketch-preview');

    function syncSketchIdsFromDom() {
      if (!sketchIdsInput || !sketchBox) return;
      var ids = [];
      sketchBox.querySelectorAll('.laico-gallery-thumb[data-id]').forEach(function (el) {
        var id = parseInt(el.getAttribute('data-id'), 10);
        if (id > 0 && ids.indexOf(id) === -1) {
          ids.push(id);
        }
      });
      sketchIdsInput.value = ids.join(',');
    }

    if (sketchBox) {
      sketchBox.addEventListener('click', function (e) {
        var rm = e.target.closest('.laico-remove-thumb');
        if (!rm) return;
        e.preventDefault();
        var item = rm.closest('.laico-gallery-thumb');
        if (item) {
          item.remove();
          syncSketchIdsFromDom();
        }
      });
    }

    if (sketchClearBtn && sketchBox && sketchIdsInput) {
      sketchClearBtn.addEventListener('click', function (e) {
        e.preventDefault();
        sketchBox.innerHTML = '';
        sketchIdsInput.value = '';
      });
    }

    if (sketchBtn && sketchBox && sketchIdsInput) {
      sketchBtn.addEventListener('click', function (e) {
        e.preventDefault();
        if (!window.wp || !window.wp.media) return;

        var frame = window.wp.media({
          title: 'انتخاب اسکیس‌های پروژه',
          button: { text: 'افزودن به اسکیس‌های پروژه' },
          library: { type: 'image' },
          multiple: true,
        });

        frame.on('select', function () {
          var selection = frame.state().get('selection');
          selection.each(function (attachment) {
            var data = attachment.toJSON();
            if (!data || !data.id) return;
            if (sketchBox.querySelector('.laico-gallery-thumb[data-id="' + data.id + '"]')) {
              return;
            }
            var thumbUrl =
              (data.sizes && data.sizes.thumbnail && data.sizes.thumbnail.url) ||
              data.url;

            var div = document.createElement('div');
            div.className = 'laico-gallery-thumb';
            div.setAttribute('data-id', String(data.id));

            var img = document.createElement('img');
            img.src = thumbUrl;
            img.alt = '';

            var rmBtn = document.createElement('button');
            rmBtn.type = 'button';
            rmBtn.className = 'laico-remove-thumb';
            rmBtn.setAttribute('aria-label', 'حذف');
            rmBtn.textContent = '×';

            div.appendChild(img);
            div.appendChild(rmBtn);
            sketchBox.appendChild(div);
          });

          syncSketchIdsFromDom();
        });

        frame.open();
      });
    }
  });
})();
