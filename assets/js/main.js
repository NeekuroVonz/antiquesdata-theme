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
  var root = document.querySelector("[data-dashboard]");
  var data = window.antiquesMarketplaceDashboard;
  if (!root || !data || !data.periods) {
    return;
  }

  var medianEl = root.querySelector("[data-dashboard-median]");
  var captionEl = root.querySelector("[data-dashboard-caption]");
  var resultsEl = root.querySelector("#dashboard-results");
  var priceEl = root.querySelector("[data-dashboard-price]");
  var volumeEl = root.querySelector("[data-dashboard-volume]");
  var countryEl = root.querySelector("[data-dashboard-countries]");
  var buttons = root.querySelectorAll("[data-dashboard-range]");

  function escapeHtml(value) {
    return String(value)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function nonempty(point) {
    return point && point.v !== null && point.v !== undefined && Number(point.v) !== 0;
  }

  function trimPoints(points) {
    var list = (points || []).slice();
    while (list.length && !nonempty(list[0])) {
      list.shift();
    }
    while (list.length && !nonempty(list[list.length - 1])) {
      list.pop();
    }
    return list;
  }

  function drawChart(target, points, key) {
    if (!target) {
      return;
    }
    var trimmed = trimPoints(points);
    var values = [];
    var labels = [];
    trimmed.forEach(function (point) {
      values.push(Number(point.v));
      labels.push(String(point.t || ""));
    });
    if (!values.length) {
      target.innerHTML = '<p class="trends-chart__empty">' + escapeHtml(data.empty) + "</p>";
      target.setAttribute("aria-label", data.empty);
      return;
    }
    var width = 640;
    var height = 220;
    var padL = 44;
    var padR = 16;
    var padT = 18;
    var padB = 28;
    var min = Math.min.apply(null, values);
    var max = Math.max.apply(null, values);
    if (min === max) {
      min = 0;
    }
    var span = max - min || 1;
    var innerW = width - padL - padR;
    var innerH = height - padT - padB;
    var coords = values.map(function (value, index) {
      var x = padL + (values.length === 1 ? innerW / 2 : (index / (values.length - 1)) * innerW);
      var y = padT + innerH - ((value - min) / span) * innerH;
      return [x, y];
    });
    var line = coords.map(function (pair, index) {
      return (index === 0 ? "M" : "L") + pair[0].toFixed(1) + " " + pair[1].toFixed(1);
    }).join(" ");
    var area = line + " L" + coords[coords.length - 1][0].toFixed(1) + " " + (height - padB) +
      " L" + coords[0][0].toFixed(1) + " " + (height - padB) + " Z";
    var firstLabel = labels[0] || "";
    var lastLabel = labels[labels.length - 1] || "";
    var stroke = key === "volume" ? "#f4efe6" : "#c4a45e";
    var fill = key === "volume" ? "rgba(244,239,230,0.12)" : "rgba(196,164,94,0.16)";
    var numberFormatter = key === "volume"
      ? new Intl.NumberFormat()
      : new Intl.NumberFormat(undefined, {
        style: "currency",
        currency: data.currency || "USD",
        maximumFractionDigits: data.currency === "JPY" ? 0 : 2
      });
    var chartLabel = key === "volume" ? data.volume_label : data.price_label;
    var maxLabel = numberFormatter.format(max);
    var minLabel = numberFormatter.format(min);
    target.setAttribute(
      "aria-label",
      chartLabel + ": " + firstLabel + " to " + lastLabel + ". " + minLabel + " to " + maxLabel + "."
    );
    target.innerHTML =
      '<svg viewBox="0 0 ' + width + " " + height + '" role="img" aria-hidden="true">' +
      '<line x1="' + padL + '" y1="' + padT + '" x2="' + (width - padR) + '" y2="' + padT + '" stroke="rgba(244,239,230,0.12)"></line>' +
      '<line x1="' + padL + '" y1="' + (height - padB) + '" x2="' + (width - padR) + '" y2="' + (height - padB) + '" stroke="rgba(244,239,230,0.12)"></line>' +
      '<path d="' + area + '" fill="' + fill + '"></path>' +
      '<path d="' + line + '" fill="none" stroke="' + stroke + '" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"></path>' +
      '<text x="' + (padL - 7) + '" y="' + (padT + 4) + '" fill="rgba(244,239,230,0.62)" font-size="10" text-anchor="end">' + escapeHtml(maxLabel) + "</text>" +
      '<text x="' + (padL - 7) + '" y="' + (height - padB + 4) + '" fill="rgba(244,239,230,0.62)" font-size="10" text-anchor="end">' + escapeHtml(minLabel) + "</text>" +
      '<text x="' + padL + '" y="' + (height - 8) + '" fill="rgba(244,239,230,0.55)" font-size="11">' + escapeHtml(firstLabel) + "</text>" +
      '<text x="' + (width - padR) + '" y="' + (height - 8) + '" fill="rgba(244,239,230,0.55)" font-size="11" text-anchor="end">' + escapeHtml(lastLabel) + "</text>" +
      "</svg>";
  }

  function renderCountries(countries) {
    if (!countryEl) {
      return;
    }
    if (!countries || !countries.length) {
      countryEl.innerHTML = '<li class="trends-who__empty">' + escapeHtml(data.empty) + "</li>";
      return;
    }
    countryEl.innerHTML = countries.map(function (country) {
      var cats = (country.categories || []).map(function (category) {
        return '<a href="' + escapeHtml(category.url || "#") + '">#' + escapeHtml(category.label || "") + "</a>";
      }).join("");
      return '<li class="trends-who__row">' +
        '<span class="trends-who__name">' + escapeHtml(country.name || "") + "</span>" +
        '<span class="trends-who__lots">' + escapeHtml(country.lots || "") + "</span>" +
        '<span class="trends-who__cats">' + cats + "</span>" +
        '<span class="trends-who__price">' + escapeHtml(country.median || "") + "</span>" +
        "</li>";
    }).join("");
  }

  function applyRange(key) {
    var period = data.periods[key];
    if (!period) {
      return;
    }
    if (medianEl) {
      medianEl.textContent = period.median;
    }
    if (captionEl) {
      captionEl.textContent = period.caption;
    }
    if (resultsEl) {
      resultsEl.setAttribute("aria-labelledby", "dashboard-range-" + key);
    }
    drawChart(priceEl, period.price, "price");
    drawChart(volumeEl, period.volume, "volume");
    renderCountries(period.countries);
    buttons.forEach(function (button) {
      var on = button.getAttribute("data-dashboard-range") === key;
      button.classList.toggle("is-active", on);
      button.setAttribute("aria-selected", on ? "true" : "false");
      button.setAttribute("tabindex", on ? "0" : "-1");
    });
  }

  buttons.forEach(function (button, index) {
    button.addEventListener("click", function () {
      applyRange(button.getAttribute("data-dashboard-range"));
    });
    button.addEventListener("keydown", function (event) {
      var nextIndex = index;
      if (event.key === "ArrowRight" || event.key === "ArrowDown") {
        nextIndex = (index + 1) % buttons.length;
      } else if (event.key === "ArrowLeft" || event.key === "ArrowUp") {
        nextIndex = (index - 1 + buttons.length) % buttons.length;
      } else if (event.key === "Home") {
        nextIndex = 0;
      } else if (event.key === "End") {
        nextIndex = buttons.length - 1;
      } else {
        return;
      }
      event.preventDefault();
      buttons[nextIndex].focus();
      applyRange(buttons[nextIndex].getAttribute("data-dashboard-range"));
    });
  });

  applyRange("12m");
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
