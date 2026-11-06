(function () {
  "use strict";

  document.documentElement.classList.add("js-enabled");

  var toggle = document.querySelector(".nav-toggle");
  var nav = document.getElementById("site-nav");
  if (!toggle || !nav) {
    return;
  }

  var label = toggle.querySelector(".sr-only");

  function setOpen(open) {
    nav.classList.toggle("is-open", open);
    toggle.setAttribute("aria-expanded", open ? "true" : "false");
    if (label) {
      label.textContent = open ? "Close menu" : "Open menu";
    }
  }

  toggle.addEventListener("click", function () {
    setOpen(!nav.classList.contains("is-open"));
  });

  document.addEventListener("keydown", function (event) {
    if (event.key === "Escape") {
      setOpen(false);
    }
  });

  nav.querySelectorAll("a").forEach(function (link) {
    link.addEventListener("click", function () {
      if (window.matchMedia("(max-width: 1100px)").matches) {
        setOpen(false);
      }
    });
  });
})();

(function () {
  "use strict";

  var root = document.querySelector("[data-dashboard]");
  var data = window.antiquesMarketplaceDashboard;
  if (!root || !data || !data.periods) {
    return;
  }

  var reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  var locale = document.documentElement.lang || undefined;
  var el = {
    results: root.querySelector("#dashboard-results"),
    median: root.querySelector("[data-dashboard-median]"),
    lots: root.querySelector("[data-dashboard-lots]"),
    span: root.querySelector("[data-dashboard-span]"),
    top: root.querySelector("[data-dashboard-top]"),
    topNote: root.querySelector("[data-dashboard-top-note]"),
    summary: root.querySelector("[data-dashboard-summary]"),
    price: root.querySelector("[data-dashboard-price]"),
    volume: root.querySelector("[data-dashboard-volume]"),
    countries: root.querySelector("[data-dashboard-countries]"),
    range: root.querySelector(".trends-range")
  };
  var buttons = root.querySelectorAll("[data-dashboard-range]");
  var current = "12m";

  function escapeHtml(value) {
    return String(value)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  // Restart a CSS animation class on an element.
  function replay(node, className) {
    node.classList.remove(className);
    void node.offsetWidth;
    node.classList.add(className);
  }

  /* ---------- Numbers: tween between values, keeping prefix/suffix (¥, $, commas, decimals) ---------- */

  var NUMBER = /^([^\d-]*)(-?[\d,]+(?:\.\d+)?)(.*)$/;

  function tweenText(node, text) {
    if (!node) {
      return;
    }
    var to = NUMBER.exec(text);
    var from = NUMBER.exec(node.textContent.trim());
    if (reduce || !to || !from) {
      node.textContent = text;
      return;
    }
    var start = parseFloat(from[2].replace(/,/g, ""));
    var end = parseFloat(to[2].replace(/,/g, ""));
    var decimals = (to[2].split(".")[1] || "").length;
    var began = performance.now();
    var token = (node._tween = {});
    (function frame(now) {
      if (node._tween !== token) {
        return;
      }
      var t = Math.min((now - began) / 700, 1);
      var value = start + (end - start) * (1 - Math.pow(1 - t, 3));
      node.textContent = t < 1
        ? to[1] + value.toLocaleString("en-US", { minimumFractionDigits: decimals, maximumFractionDigits: decimals }) + to[3]
        : text;
      if (t < 1) {
        requestAnimationFrame(frame);
      }
    })(began);
  }

  /* ---------- Charts ---------- */

  function trimPoints(points) {
    var list = (points || []).filter(function (p) {
      return p && p.v !== null && p.v !== undefined;
    });
    while (list.length && !Number(list[0].v)) {
      list.shift();
    }
    while (list.length && !Number(list[list.length - 1].v)) {
      list.pop();
    }
    return list;
  }

  function formatter(key, compact) {
    if (key === "volume") {
      return new Intl.NumberFormat(locale, compact ? { notation: "compact" } : {});
    }
    return new Intl.NumberFormat(locale, {
      style: "currency",
      currency: data.currency || "USD",
      notation: compact ? "compact" : "standard",
      minimumFractionDigits: 0,
      maximumFractionDigits: data.currency === "JPY" || compact ? 0 : 2
    });
  }

  var palette = {
    price: { stroke: "#9a7a3d", fill: "#c4a45e", opacity: 0.24 },
    volume: { stroke: "#141821", fill: "#141821", opacity: 0.07 }
  };

  function drawChart(target, points, key, animate) {
    if (!target) {
      return;
    }
    var list = trimPoints(points);
    if (!list.length) {
      target.innerHTML = '<p class="trends-chart__empty">' + escapeHtml(data.empty) + "</p>";
      target.setAttribute("aria-label", data.empty);
      return;
    }
    var values = list.map(function (p) { return Number(p.v); });
    var labels = list.map(function (p) { return String(p.t || ""); });
    var w = Math.max(target.clientWidth, 280);
    var h = Math.max(target.clientHeight, 160);
    var pad = { l: 52, r: 12, t: 12, b: 26 };
    var max = Math.max.apply(null, values);
    var min = Math.min.apply(null, values);
    min = min === max ? 0 : Math.max(0, min - (max - min) * 0.15);
    var span = max - min || 1;
    var iw = w - pad.l - pad.r;
    var ih = h - pad.t - pad.b;
    var xy = values.map(function (v, i) {
      return [
        pad.l + (values.length === 1 ? iw / 2 : (i / (values.length - 1)) * iw),
        pad.t + ih - ((v - min) / span) * ih
      ];
    });
    var line = xy.map(function (p, i) { return (i ? "L" : "M") + p[0].toFixed(1) + " " + p[1].toFixed(1); }).join(" ");
    var base = (pad.t + ih).toFixed(1);
    var area = line + " L" + xy[xy.length - 1][0].toFixed(1) + " " + base + " L" + xy[0][0].toFixed(1) + " " + base + " Z";
    var color = palette[key] || palette.price;
    var full = formatter(key, false);
    var short = formatter(key, true);
    var gid = "trends-grad-" + key;

    var grid = "";
    for (var g = 0; g <= 3; g++) {
      var gy = pad.t + (ih / 3) * g;
      var gv = max - (span / 3) * g;
      grid += '<line x1="' + pad.l + '" x2="' + (w - pad.r) + '" y1="' + gy.toFixed(1) + '" y2="' + gy.toFixed(1) + '" stroke="#e8dfcf" stroke-dasharray="' + (g === 3 ? "0" : "3 4") + '"></line>' +
        '<text x="' + (pad.l - 10) + '" y="' + (gy + 4).toFixed(1) + '" text-anchor="end" fill="#8b8376" font-size="11">' + escapeHtml(short.format(gv)) + "</text>";
    }
    var mid = Math.floor((labels.length - 1) / 2);
    var xLabels = [[0, "start"], [mid, "middle"], [labels.length - 1, "end"]]
      .filter(function (item, i, all) { return labels.length > 2 || i !== 1; })
      .map(function (item) {
        return '<text x="' + xy[item[0]][0].toFixed(1) + '" y="' + (h - 6) + '" text-anchor="' + item[1] + '" fill="#8b8376" font-size="11">' + escapeHtml(labels[item[0]]) + "</text>";
      }).join("");
    var last = xy[xy.length - 1];

    target.setAttribute("aria-label",
      (key === "volume" ? data.volume_label : data.price_label) + ": " + labels[0] + " – " + labels[labels.length - 1] +
      ", " + full.format(Math.min.apply(null, values)) + " – " + full.format(max) + ".");
    target.innerHTML =
      '<svg viewBox="0 0 ' + w + " " + h + '" width="' + w + '" height="' + h + '" aria-hidden="true">' +
      '<defs><linearGradient id="' + gid + '" x1="0" y1="0" x2="0" y2="1">' +
      '<stop offset="0" stop-color="' + color.fill + '" stop-opacity="' + color.opacity + '"></stop>' +
      '<stop offset="1" stop-color="' + color.fill + '" stop-opacity="0"></stop></linearGradient></defs>' +
      grid + xLabels +
      '<path class="trends-chart__area" d="' + area + '" fill="url(#' + gid + ')"></path>' +
      '<path class="trends-chart__line" d="' + line + '" pathLength="1" fill="none" stroke="' + color.stroke + '" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"></path>' +
      '<circle class="trends-chart__end" cx="' + last[0].toFixed(1) + '" cy="' + last[1].toFixed(1) + '" r="4" fill="' + color.stroke + '"></circle>' +
      '<line class="trends-chart__guide" y1="' + pad.t + '" y2="' + base + '" stroke="' + color.stroke + '" stroke-opacity="0.35"></line>' +
      '<circle class="trends-chart__dot" r="5" fill="#fffdf8" stroke="' + color.stroke + '" stroke-width="2"></circle>' +
      "</svg>" +
      '<div class="trends-chart__tip"></div>';

    // Hover / touch: snap a guide, dot and tooltip to the nearest point.
    var svg = target.querySelector("svg");
    var guide = svg.querySelector(".trends-chart__guide");
    var dot = svg.querySelector(".trends-chart__dot");
    var tip = target.querySelector(".trends-chart__tip");
    target.onpointermove = function (event) {
      var box = svg.getBoundingClientRect();
      var x = (event.clientX - box.left) * (w / box.width);
      var i = Math.round(((x - pad.l) / iw) * (values.length - 1));
      i = Math.max(0, Math.min(values.length - 1, i));
      guide.setAttribute("x1", xy[i][0]);
      guide.setAttribute("x2", xy[i][0]);
      dot.setAttribute("cx", xy[i][0]);
      dot.setAttribute("cy", xy[i][1]);
      tip.innerHTML = escapeHtml(labels[i]) + "<strong>" + escapeHtml(full.format(values[i])) + "</strong>";
      tip.style.left = (xy[i][0] / w) * 100 + "%";
      tip.style.top = (xy[i][1] / h) * 100 + "%";
      target.classList.add("is-hovering");
    };
    target.onpointerleave = function () {
      target.classList.remove("is-hovering");
    };

    if (animate && !reduce) {
      replay(target, "is-drawing");
    }
  }

  /* ---------- Who is buying ---------- */

  function renderCountries(countries) {
    if (!el.countries) {
      return;
    }
    if (!countries || !countries.length) {
      el.countries.innerHTML = '<li class="trends-who__empty">' + escapeHtml(data.empty) + "</li>";
      return;
    }
    el.countries.innerHTML = countries.map(function (c, i) {
      var cats = (c.categories || []).map(function (cat) {
        return '<a href="' + escapeHtml(cat.url || "#") + '">#' + escapeHtml(cat.label || "") + "</a>";
      }).join("");
      return '<li class="trends-who__row">' +
        '<span class="trends-who__rank">' + (i < 9 ? "0" : "") + (i + 1) + "</span>" +
        '<span class="trends-who__name">' + escapeHtml(c.name || "") + "</span>" +
        '<span class="trends-who__bar" aria-hidden="true"><i style="--w: ' + Number(c.bar || 0) + '%"></i></span>' +
        '<span class="trends-who__lots">' + escapeHtml(c.lots || "") + "</span>" +
        '<span class="trends-who__cats">' + cats + "</span>" +
        '<span class="trends-who__price">' + escapeHtml(c.median || "") + "</span>" +
        "</li>";
    }).join("");
    if (!reduce) {
      replay(el.countries, "is-growing");
    }
  }

  /* ---------- Range control ---------- */

  var thumb = null;
  if (el.range) {
    thumb = document.createElement("span");
    thumb.className = "trends-range__thumb";
    thumb.setAttribute("aria-hidden", "true");
    el.range.insertBefore(thumb, el.range.firstChild);
    el.range.classList.add("has-thumb");
  }

  function moveThumb(button, instant) {
    if (!thumb || !button) {
      return;
    }
    if (instant) {
      thumb.style.transition = "none";
    }
    thumb.style.width = button.offsetWidth + "px";
    thumb.style.transform = "translateX(" + button.offsetLeft + "px)";
    if (instant) {
      void thumb.offsetWidth;
      thumb.style.transition = "";
    }
  }

  function swapSummary(key) {
    if (!el.summary || !data.summary || data.summary[key] === undefined) {
      return;
    }
    if (reduce) {
      el.summary.innerHTML = data.summary[key];
      return;
    }
    el.summary.classList.add("is-swapping");
    window.setTimeout(function () {
      el.summary.innerHTML = data.summary[key];
      el.summary.classList.remove("is-swapping");
    }, 220);
  }

  var seen = { charts: false };

  function applyRange(key, initial) {
    var period = data.periods[key];
    if (!period) {
      return;
    }
    current = key;
    root.setAttribute("data-dashboard-range", key);
    buttons.forEach(function (button) {
      var on = button.getAttribute("data-dashboard-range") === key;
      button.classList.toggle("is-active", on);
      button.setAttribute("aria-selected", on ? "true" : "false");
      button.setAttribute("tabindex", on ? "0" : "-1");
      if (on) {
        moveThumb(button, initial);
      }
    });
    if (el.results) {
      el.results.setAttribute("aria-labelledby", "dashboard-range-" + key);
    }
    if (initial) {
      return; // Server already rendered this range; charts draw when scrolled into view.
    }
    tweenText(el.median, period.median);
    tweenText(el.lots, period.lots);
    if (el.span) {
      el.span.textContent = period.span;
    }
    if (el.top) {
      el.top.textContent = period.top;
    }
    if (el.topNote) {
      el.topNote.textContent = period.top_note;
    }
    swapSummary(key);
    drawChart(el.price, period.price, "price", seen.charts);
    drawChart(el.volume, period.volume, "volume", seen.charts);
    renderCountries(period.countries);
  }

  buttons.forEach(function (button, index) {
    button.addEventListener("click", function () {
      if (button.getAttribute("data-dashboard-range") !== current) {
        applyRange(button.getAttribute("data-dashboard-range"));
      }
    });
    button.addEventListener("keydown", function (event) {
      var next = index;
      if (event.key === "ArrowRight" || event.key === "ArrowDown") {
        next = (index + 1) % buttons.length;
      } else if (event.key === "ArrowLeft" || event.key === "ArrowUp") {
        next = (index - 1 + buttons.length) % buttons.length;
      } else if (event.key === "Home") {
        next = 0;
      } else if (event.key === "End") {
        next = buttons.length - 1;
      } else {
        return;
      }
      event.preventDefault();
      buttons[next].focus();
      applyRange(buttons[next].getAttribute("data-dashboard-range"));
    });
  });

  applyRange(current, true);
  if (document.fonts && document.fonts.ready) {
    document.fonts.ready.then(function () {
      moveThumb(root.querySelector(".trends-range__btn.is-active"), true);
    });
  }

  // First draw: charts and bars animate when they scroll into view.
  var period = data.periods[current];
  drawChart(el.price, period.price, "price", false);
  drawChart(el.volume, period.volume, "volume", false);
  if ("IntersectionObserver" in window && !reduce) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) {
          return;
        }
        io.unobserve(entry.target);
        if (entry.target === el.countries) {
          replay(el.countries, "is-growing");
        } else {
          seen.charts = true;
          replay(entry.target, "is-drawing");
        }
      });
    }, { threshold: 0.3 });
    [el.price, el.volume, el.countries].forEach(function (node) {
      if (node) {
        io.observe(node);
      }
    });
  } else {
    seen.charts = true;
  }

  // Redraw charts at the new size (text stays crisp) and keep the thumb aligned.
  var resizeTimer;
  window.addEventListener("resize", function () {
    window.clearTimeout(resizeTimer);
    resizeTimer = window.setTimeout(function () {
      var p = data.periods[current];
      drawChart(el.price, p.price, "price", false);
      drawChart(el.volume, p.volume, "volume", false);
      moveThumb(root.querySelector(".trends-range__btn.is-active"), true);
    }, 150);
  });
})();

(function () {
  "use strict";

  function applyImageFallback(image) {
    if (!image || image.tagName !== "IMG") {
      return;
    }
    var fallbackSource = image.getAttribute("data-fallback-src");
    if (!fallbackSource || image.getAttribute("src") === fallbackSource) {
      return;
    }
    image.classList.add("is-fallback-image");
    image.setAttribute("src", fallbackSource);
    var fallbackAlt = image.getAttribute("data-fallback-alt");
    if (fallbackAlt) {
      image.setAttribute("alt", fallbackAlt);
    }
  }

  document.addEventListener("error", function (event) {
    applyImageFallback(event.target);
  }, true);

  document.querySelectorAll("img[data-fallback-src]").forEach(function (image) {
    if (image.complete && image.naturalWidth === 0) {
      applyImageFallback(image);
    }
  });
})();

(function () {
  "use strict";

  var gallery = document.querySelector("[data-product-gallery]");
  var lightbox = document.querySelector("[data-product-lightbox]");
  if (!gallery || !lightbox) {
    return;
  }

  var mainImage = gallery.querySelector("[data-main-image]");
  var mainImageLink = gallery.querySelector("[data-main-image-link]");
  var thumbs = gallery.querySelectorAll(".product-thumb");
  var stage = lightbox.querySelector("[data-lightbox-stage]");
  var lightboxImage = lightbox.querySelector("[data-lightbox-image]");
  var counter = lightbox.querySelector("[data-lightbox-counter]");
  var zoomLevelEl = lightbox.querySelector("[data-lightbox-zoom-level]");
  var closeButton = lightbox.querySelector("[data-lightbox-close]");
  var previousButton = lightbox.querySelector("[data-lightbox-previous]");
  var nextButton = lightbox.querySelector("[data-lightbox-next]");
  var zoomInButton = lightbox.querySelector("[data-lightbox-zoom-in]");
  var zoomOutButton = lightbox.querySelector("[data-lightbox-zoom-out]");
  var zoomResetButton = lightbox.querySelector("[data-lightbox-zoom-reset]");
  var images = [];
  var currentIndex = 0;
  var openingElement = null;
  var zoomScale = 1;
  var panX = 0;
  var panY = 0;
  var minZoom = 1;
  var maxZoom = 4;
  var zoomStep = 0.25;
  var isPanning = false;
  var panStartX = 0;
  var panStartY = 0;
  var panOriginX = 0;
  var panOriginY = 0;
  var pinchStartDistance = 0;
  var pinchStartZoom = 1;

  thumbs.forEach(function (thumb) {
    var thumbSource = thumb.getAttribute("data-thumb-src");
    if (thumbSource) {
      images.push(thumbSource);
    }
  });
  if (!images.length && mainImageLink) {
    images.push(mainImageLink.getAttribute("href"));
  } else if (!images.length && mainImage) {
    images.push(mainImage.getAttribute("src"));
  }

  function loadDeferredThumbs() {
    gallery.querySelectorAll("img[data-src].is-deferred").forEach(function (image) {
      var deferredSource = image.getAttribute("data-src");
      if (!deferredSource) {
        return;
      }
      image.setAttribute("src", deferredSource);
      image.removeAttribute("data-src");
      image.classList.remove("is-deferred");
    });
  }

  function scheduleThumbLoading() {
    var start = function () {
      if ("requestIdleCallback" in window) {
        window.requestIdleCallback(loadDeferredThumbs, { timeout: 1200 });
      } else {
        window.setTimeout(loadDeferredThumbs, 200);
      }
    };
    if (!mainImage) {
      start();
      return;
    }
    if (mainImage.complete) {
      start();
      return;
    }
    mainImage.addEventListener("load", start, { once: true });
    mainImage.addEventListener("error", start, { once: true });
  }

  scheduleThumbLoading();

  function clamp(value, min, max) {
    return Math.min(max, Math.max(min, value));
  }

  function updateZoomUi() {
    if (!lightboxImage) {
      return;
    }
    lightboxImage.style.transform = "translate(" + panX + "px, " + panY + "px) scale(" + zoomScale + ")";
    lightbox.classList.toggle("is-zoomed", zoomScale > 1.01);
    if (zoomLevelEl) {
      zoomLevelEl.textContent = Math.round(zoomScale * 100) + "%";
    }
    if (zoomOutButton) {
      zoomOutButton.disabled = zoomScale <= minZoom + 0.001;
    }
    if (zoomInButton) {
      zoomInButton.disabled = zoomScale >= maxZoom - 0.001;
    }
    if (zoomResetButton) {
      zoomResetButton.disabled = zoomScale <= minZoom + 0.001 && panX === 0 && panY === 0;
    }
  }

  function resetZoom() {
    zoomScale = 1;
    panX = 0;
    panY = 0;
    updateZoomUi();
  }

  function setZoom(nextScale, originX, originY) {
    var previous = zoomScale;
    zoomScale = clamp(nextScale, minZoom, maxZoom);
    if (zoomScale <= minZoom + 0.001) {
      panX = 0;
      panY = 0;
    } else if (typeof originX === "number" && typeof originY === "number" && lightboxImage) {
      var rect = lightboxImage.getBoundingClientRect();
      var centerX = rect.left + rect.width / 2;
      var centerY = rect.top + rect.height / 2;
      var offsetX = originX - centerX;
      var offsetY = originY - centerY;
      var ratio = zoomScale / previous;
      panX = offsetX - (offsetX - panX) * ratio;
      panY = offsetY - (offsetY - panY) * ratio;
    }
    updateZoomUi();
  }

  function selectGalleryImage(index) {
    var thumb = thumbs[index];
    var fullSource = thumb ? thumb.getAttribute("data-thumb-src") : images[index];
    var displaySource = thumb ? (thumb.getAttribute("data-thumb-display") || fullSource) : fullSource;
    if (!fullSource || !mainImage) {
      return;
    }
    currentIndex = index;
    mainImage.setAttribute("src", displaySource);
    if (mainImageLink) {
      mainImageLink.setAttribute("href", fullSource);
    }
    thumbs.forEach(function (item, thumbIndex) {
      item.classList.toggle("is-active", thumbIndex === index);
    });
  }

  function showLightboxImage(index) {
    if (!images.length || !lightboxImage) {
      return;
    }
    currentIndex = (index + images.length) % images.length;
    lightboxImage.setAttribute("src", images[currentIndex]);
    lightboxImage.removeAttribute("data-src");
    if (counter) {
      counter.textContent = (currentIndex + 1) + " / " + images.length;
    }
    resetZoom();
  }

  function closeLightbox() {
    if (lightbox.open) {
      lightbox.close();
    }
  }

  function touchDistance(touches) {
    var dx = touches[0].clientX - touches[1].clientX;
    var dy = touches[0].clientY - touches[1].clientY;
    return Math.sqrt(dx * dx + dy * dy);
  }

  thumbs.forEach(function (thumb, index) {
    thumb.addEventListener("click", function () {
      selectGalleryImage(index);
    });
  });

  if (mainImageLink) {
    mainImageLink.addEventListener("click", function (event) {
      if (typeof lightbox.showModal !== "function") {
        return;
      }
      event.preventDefault();
      openingElement = mainImageLink;
      var selectedSource = mainImageLink.getAttribute("href");
      var selectedIndex = images.indexOf(selectedSource);
      showLightboxImage(selectedIndex >= 0 ? selectedIndex : currentIndex);
      previousButton.hidden = images.length < 2;
      nextButton.hidden = images.length < 2;
      document.body.classList.add("has-open-lightbox");
      lightbox.showModal();
      closeButton.focus();
    });
  }

  closeButton.addEventListener("click", closeLightbox);
  previousButton.addEventListener("click", function () {
    showLightboxImage(currentIndex - 1);
  });
  nextButton.addEventListener("click", function () {
    showLightboxImage(currentIndex + 1);
  });

  if (zoomInButton) {
    zoomInButton.addEventListener("click", function () {
      setZoom(zoomScale + zoomStep);
    });
  }
  if (zoomOutButton) {
    zoomOutButton.addEventListener("click", function () {
      setZoom(zoomScale - zoomStep);
    });
  }
  if (zoomResetButton) {
    zoomResetButton.addEventListener("click", resetZoom);
  }

  lightbox.addEventListener("click", function (event) {
    if (
      event.target === lightbox ||
      event.target.classList.contains("product-lightbox__surface")
    ) {
      closeLightbox();
    }
  });

  lightbox.addEventListener("wheel", function (event) {
    if (!lightbox.open) {
      return;
    }
    event.preventDefault();
    var direction = event.deltaY < 0 ? zoomStep : -zoomStep;
    setZoom(zoomScale + direction, event.clientX, event.clientY);
  }, { passive: false });

  if (lightboxImage) {
    lightboxImage.addEventListener("dblclick", function (event) {
      event.preventDefault();
      if (zoomScale > 1.01) {
        resetZoom();
      } else {
        setZoom(2, event.clientX, event.clientY);
      }
    });

    lightboxImage.addEventListener("pointerdown", function (event) {
      if (zoomScale <= 1.01 || event.button !== 0) {
        return;
      }
      isPanning = true;
      panStartX = event.clientX;
      panStartY = event.clientY;
      panOriginX = panX;
      panOriginY = panY;
      lightboxImage.setPointerCapture(event.pointerId);
      lightboxImage.classList.add("is-panning");
    });

    lightboxImage.addEventListener("pointermove", function (event) {
      if (!isPanning) {
        return;
      }
      panX = panOriginX + (event.clientX - panStartX);
      panY = panOriginY + (event.clientY - panStartY);
      updateZoomUi();
    });

    function endPan(event) {
      if (!isPanning) {
        return;
      }
      isPanning = false;
      lightboxImage.classList.remove("is-panning");
      if (lightboxImage.hasPointerCapture(event.pointerId)) {
        lightboxImage.releasePointerCapture(event.pointerId);
      }
    }

    lightboxImage.addEventListener("pointerup", endPan);
    lightboxImage.addEventListener("pointercancel", endPan);
  }

  if (stage) {
    stage.addEventListener("touchstart", function (event) {
      if (event.touches.length === 2) {
        pinchStartDistance = touchDistance(event.touches);
        pinchStartZoom = zoomScale;
      }
    }, { passive: true });

    stage.addEventListener("touchmove", function (event) {
      if (event.touches.length !== 2 || !pinchStartDistance) {
        return;
      }
      event.preventDefault();
      var midpointX = (event.touches[0].clientX + event.touches[1].clientX) / 2;
      var midpointY = (event.touches[0].clientY + event.touches[1].clientY) / 2;
      setZoom(pinchStartZoom * (touchDistance(event.touches) / pinchStartDistance), midpointX, midpointY);
    }, { passive: false });

    stage.addEventListener("touchend", function () {
      pinchStartDistance = 0;
    });
  }

  lightbox.addEventListener("keydown", function (event) {
    if (event.key === "ArrowLeft") {
      event.preventDefault();
      showLightboxImage(currentIndex - 1);
    } else if (event.key === "ArrowRight") {
      event.preventDefault();
      showLightboxImage(currentIndex + 1);
    } else if (event.key === "+" || event.key === "=") {
      event.preventDefault();
      setZoom(zoomScale + zoomStep);
    } else if (event.key === "-" || event.key === "_") {
      event.preventDefault();
      setZoom(zoomScale - zoomStep);
    } else if (event.key === "0") {
      event.preventDefault();
      resetZoom();
    }
  });
  lightbox.addEventListener("close", function () {
    document.body.classList.remove("has-open-lightbox");
    resetZoom();
    if (openingElement) {
      openingElement.focus();
    }
  });

  updateZoomUi();
})();

(function () {
  var config = window.antiquesProductTranslate;
  if (!config || !config.ajaxUrl || !config.productId) {
    return;
  }

  var root = document.querySelector("[data-product-translate-root]");
  if (!root) {
    return;
  }

  var statusEl = root.querySelector("[data-product-translate-status]");
  var started = false;

  function setStatus(message, state) {
    if (!statusEl) {
      return;
    }
    if (!message) {
      statusEl.hidden = true;
      statusEl.textContent = "";
      statusEl.classList.remove("is-busy", "is-done", "is-error");
      return;
    }
    statusEl.hidden = false;
    statusEl.textContent = message;
    statusEl.classList.remove("is-busy", "is-done", "is-error");
    if (state) {
      statusEl.classList.add(state);
    }
  }

  function escapeHtml(value) {
    return String(value)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function setRichText(element, text) {
    if (!element) {
      return;
    }
    element.innerHTML = escapeHtml(text).replace(/\r\n|\r|\n/g, "<br>");
  }

  function applyTitle(payload) {
    if (!payload || !payload.title) {
      return;
    }
    var titleEl = root.querySelector("[data-product-title]");
    if (titleEl) {
      titleEl.textContent = payload.title;
    }
    var titleSeparator = document.title.indexOf("|");
    if (titleSeparator !== -1) {
      document.title = payload.title + document.title.slice(titleSeparator);
    } else {
      document.title = payload.title;
    }
    var mainImage = root.querySelector("[data-main-image]");
    if (mainImage && !mainImage.classList.contains("is-fallback-image")) {
      mainImage.setAttribute("alt", payload.title);
    }
  }

  function latinCount(text) {
    return (String(text).match(/[A-Za-z]/g) || []).length;
  }

  function cjkCount(text) {
    return (String(text).match(/[\u3040-\u30FF\u4E00-\u9FFF]/g) || []).length;
  }

  function needsClientTranslate(text) {
    var lang = config.lang || "en";
    var latin = latinCount(text);
    var cjk = cjkCount(text);
    if (lang === "ja") {
      return latin >= 40 && (cjk === 0 || latin > cjk * 0.25);
    }
    return cjk >= 8 && latin < 48;
  }

  function chunkText(text, maxLen) {
    var lines = String(text).split(/\r\n|\r|\n/);
    var chunks = [];
    var current = "";
    lines.forEach(function (line) {
      var candidate = current ? current + "\n" + line : line;
      if (candidate.length > maxLen && current) {
        chunks.push(current);
        current = line;
      } else {
        current = candidate;
      }
    });
    if (current) {
      chunks.push(current);
    }
    // Merge tiny leading chunks.
    var merged = [];
    chunks.forEach(function (chunk) {
      if (merged.length && merged[merged.length - 1].length < 100) {
        merged[merged.length - 1] += "\n" + chunk;
      } else {
        merged.push(chunk);
      }
    });
    return merged;
  }

  function translateChunkClient(text, lang) {
    var sourceLang = lang === "ja" ? "en" : "ja";
    if (lang === "ja" && cjkCount(text) > latinCount(text)) {
      sourceLang = "auto";
    }
    var url =
      "https://clients5.google.com/translate_a/t?client=dict-chrome-ex&sl=" +
      encodeURIComponent(sourceLang) +
      "&tl=" +
      encodeURIComponent(lang) +
      "&q=" +
      encodeURIComponent(text);
    return fetch(url)
      .then(function (response) {
        return response.json();
      })
      .then(function (data) {
        if (Array.isArray(data) && typeof data[0] === "string") {
          return data[0];
        }
        if (Array.isArray(data)) {
          return data
            .map(function (row) {
              return Array.isArray(row) && typeof row[0] === "string" ? row[0] : "";
            })
            .join("");
        }
        return text;
      })
      .catch(function () {
        return text;
      });
  }

  function translateRichTextClient(element) {
    if (!element) {
      return Promise.resolve();
    }
    var source = element.innerText || element.textContent || "";
    if (!needsClientTranslate(source)) {
      return Promise.resolve();
    }
    var lang = config.lang || "en";
    var chunks = chunkText(source, 420);
    var queue = chunks.map(function (chunk) {
      return translateChunkClient(chunk, lang);
    });
    return Promise.all(queue).then(function (parts) {
      setRichText(element, parts.join("\n"));
    });
  }

  function finishClientFallback() {
    var nodes = root.querySelectorAll("[data-product-richtext]");
    var tasks = [];
    nodes.forEach(function (node) {
      tasks.push(translateRichTextClient(node));
    });
    var titleEl = root.querySelector("[data-product-title]");
    if (titleEl && needsClientTranslate(titleEl.textContent || "")) {
      tasks.push(
        translateChunkClient(titleEl.textContent || "", config.lang || "en").then(function (translated) {
          applyTitle({ title: translated });
        })
      );
    }
    return Promise.all(tasks);
  }

  function fetchTranslation(mode) {
    var body = new URLSearchParams();
    body.set("action", config.action || "antiques_translate_product");
    body.set("nonce", config.nonce || "");
    body.set("product_id", String(config.productId));
    body.set("lang", config.lang || "en");
    body.set("mode", mode || "full");

    return fetch(config.ajaxUrl, {
      method: "POST",
      credentials: "same-origin",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
      },
      body: body.toString(),
    }).then(function (response) {
      return response.json().then(function (json) {
        return { ok: response.ok, json: json };
      });
    });
  }

  function applyPayload(payload) {
    if (!payload) {
      return;
    }
    if (payload.title) {
      applyTitle(payload);
    }
    if (payload.mode === "title") {
      return;
    }
    if (payload.changed === false) {
      // Still attempt client fallback for mixed-language overviews.
    } else {
      var overview = root.querySelector("[data-product-details-overview] [data-product-richtext]");
      if (overview && typeof payload.details_overview === "string") {
        setRichText(overview, payload.details_overview);
      }

      var description = root.querySelector("[data-product-description] [data-product-richtext]");
      if (description && typeof payload.description === "string") {
        setRichText(description, payload.description);
      }

      var detailItems = root.querySelectorAll("[data-product-detail-item]");
      if (payload.details && payload.details.length && detailItems.length) {
        payload.details.forEach(function (item, index) {
          var row = detailItems[index];
          if (!row || !item) {
            return;
          }
          var label = row.querySelector("[data-product-detail-label]");
          var value = row.querySelector("[data-product-detail-value]");
          if (label && typeof item.label === "string") {
            label.textContent = item.label;
          }
          if (value && typeof item.value === "string") {
            value.textContent = item.value;
          }
        });
      }
    }
  }

  function requestTranslation() {
    if (started) {
      return;
    }
    started = true;
    root.classList.add("is-translating");
    setStatus(config.statusTranslating || "Translating…", "is-busy");

    var titlePromise = fetchTranslation("title").then(function (result) {
      if (result.ok && result.json && result.json.success) {
        applyPayload(result.json.data || {});
      }
      return result;
    });

    var fullPromise = fetchTranslation("full")
      .then(function (result) {
        if (result.ok && result.json && result.json.success) {
          applyPayload(result.json.data || {});
        }
        return finishClientFallback();
      })
      .then(function () {
        root.classList.remove("is-translating");
        setStatus(config.statusDone || "Translated", "is-done");
        window.setTimeout(function () {
          setStatus("", "");
        }, 2500);
      });

    Promise.all([titlePromise, fullPromise]).catch(function () {
      finishClientFallback()
        .catch(function () {
          return null;
        })
        .then(function () {
          root.classList.remove("is-translating");
          setStatus(config.statusError || "Could not translate product text.", "is-error");
        });
    });
  }

  function scheduleTranslation() {
    // Start ASAP after first paint; do not wait for full window load.
    if ("requestIdleCallback" in window) {
      window.requestIdleCallback(requestTranslation, { timeout: 400 });
    } else {
      window.setTimeout(requestTranslation, 50);
    }
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", scheduleTranslation, { once: true });
  } else {
    scheduleTranslation();
  }
})();

(function () {
  "use strict";

  // Header shadow once the page scrolls (all pages).
  var header = document.querySelector(".site-header");
  if (header) {
    var onScroll = function () {
      header.classList.toggle("is-scrolled", window.scrollY > 8);
    };
    window.addEventListener("scroll", onScroll, { passive: true });
    onScroll();
  }

  var reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  if (reduce || !("IntersectionObserver" in window)) {
    return;
  }

  // Hero photo drifts slower than the page (light parallax), only while the hero is on screen.
  var heroMedia = document.querySelector(".land-hero__media");
  if (heroMedia && window.matchMedia("(min-width: 901px)").matches) {
    var heroTicking = false;
    var heroLimit = heroMedia.parentNode.offsetHeight;
    window.addEventListener("resize", function () {
      heroLimit = heroMedia.parentNode.offsetHeight;
    }, { passive: true });
    window.addEventListener("scroll", function () {
      if (heroTicking || window.scrollY > heroLimit) {
        return;
      }
      heroTicking = true;
      window.requestAnimationFrame(function () {
        heroMedia.style.setProperty("--hero-shift", Math.round(window.scrollY * 0.22) + "px");
        heroTicking = false;
      });
    }, { passive: true });
  }

  // Scroll reveal on every page; siblings in a grid get a stagger.
  var targets = document.querySelectorAll(
    // Landing
    ".land-pain h2, .land-pain article, .land-closing," +
    ".land-features h2, .land-features article," +
    ".land-split h2, .land-genres li, .land-why p, .land-why__stats > div, .land-why img," +
    ".land-audience h2, .land-audience article," +
    ".land-pricing__intro, .land-price-card, .land-checks li," +
    ".land-faq h2, .land-faq__item," +
    // Static, auth, subscribe and legal pages
    ".static-page-title, .site-main--contained > .panel, .static-page-content > *," +
    // Catalog
    ".category-hub, .filter-panel, .results-meta, .listing-grid > .listing-card," +
    // Trend dashboard
    ".trends-summary, .trends-chart, .trends-who," +
    // Product detail
    ".product-detail-image-wrap, .product-detail-info, .product-details-grid, .product-detail-description, .product-bid-history," +
    // Footer
    ".footer-brand, .footer-nav > div, .footer-bottom"
  );
  if (!targets.length) {
    return;
  }

  // Elements already on screen at load are left alone: hiding them after first paint would flash.
  targets = Array.prototype.filter.call(targets, function (el) {
    return el.getBoundingClientRect().top > window.innerHeight;
  });

  targets.forEach(function (el) {
    var i = Array.prototype.indexOf.call(el.parentNode.children, el);
    el.classList.add("reveal");
    el.style.setProperty("--reveal-delay", Math.min(i, 8) * 70 + "ms");
  });

  // Bottom margin 0 so elements at the very end of the page (footer) still trigger.
  var io = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (!entry.isIntersecting) {
        return;
      }
      var el = entry.target;
      el.classList.add("is-visible");
      io.unobserve(el);
      // Drop reveal classes after the transition (timer: transitionend is unreliable in background tabs)
      // so each element's own hover transitions apply again.
      window.setTimeout(function () {
        el.classList.remove("reveal", "is-visible");
        el.style.removeProperty("--reveal-delay");
      }, 1500);
    });
  }, { threshold: 0 });

  targets.forEach(function (el) {
    io.observe(el);
  });

  // Count-up for the domestic vs overseas price-gap numbers.
  var counters = document.querySelectorAll(".land-why__stats strong");
  var countIo = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (!entry.isIntersecting) {
        return;
      }
      countIo.unobserve(entry.target);
      var el = entry.target;
      var text = el.textContent;
      var target = parseInt(text.replace(/[^\d]/g, ""), 10);
      var prefix = text.match(/^[^\d]*/)[0];
      if (!target) {
        return;
      }
      var start = performance.now();
      var duration = 1400;
      var tick = function (now) {
        var t = Math.min((now - start) / duration, 1);
        var eased = 1 - Math.pow(1 - t, 3);
        el.textContent = prefix + Math.round(target * eased).toLocaleString("en-US");
        if (t < 1) {
          requestAnimationFrame(tick);
        } else {
          el.textContent = text;
        }
      };
      requestAnimationFrame(tick);
    });
  }, { threshold: 0.6 });

  counters.forEach(function (el) {
    countIo.observe(el);
  });
})();
