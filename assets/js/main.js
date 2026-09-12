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
