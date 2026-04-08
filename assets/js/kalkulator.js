document.addEventListener("DOMContentLoaded", function () {
  var wrapper = document.querySelector(".bk-wrapper");
  if (!wrapper) return;

  // ── Katalog usluga (injektovan iz PHP-a) ──────────────────
  var BK_USLUGE = window.bkUsluge || [];
  var BK_MIN_CENA = 4000;

  // ── State ─────────────────────────────────────────────────
  var currentStep = 1;
  var totalSteps = 3;

  var stanje = {
    // Trenutna selekcija (u toku konfiguracije)
    primarna_slug: null,
    primarna_naziv: null,
    podusluga: null,
    kolicina: 50,
    // Korpa
    usluge_lista: [],
    // Korak 2
    opstina_transport: 0,
    opstina_naziv: "",
    ugovor_procenat: 0,
    ugovor_naziv: "Jednokratno",
    hitnost_procenat: 0,
    hitnost_naziv: "Standardno (3-5 dana)",
  };

  // ── Element refs ──────────────────────────────────────────
  var emailInput = document.getElementById("bk-email");
  var emailCheck = document.getElementById("bk-email-check");
  var btnIzracunaj = document.getElementById("bk-btn-izracunaj");
  var next1 = document.getElementById("bk-next-1");
  var btnDodaj = document.getElementById("bk-btn-dodaj");
  var dodajWrap = document.getElementById("bk-dodaj-wrap");

  // ── Progress bar ──────────────────────────────────────────
  function updateProgress(step) {
    var fill = document.getElementById("bk-progress-fill");
    var pct = ((step - 1) / (totalSteps - 1)) * 100;
    fill.style.width = pct + "%";

    document.querySelectorAll(".bk-progress-step").forEach(function (el) {
      var s = parseInt(el.getAttribute("data-step"));
      el.classList.toggle("active", s === step);
      el.classList.toggle("completed", s < step);
    });
  }

  // ── Step transition ───────────────────────────────────────
  function goToStep(next, direction) {
    var current = document.getElementById("bk-step-" + currentStep);
    var target = document.getElementById("bk-step-" + next);
    var outClass =
      direction === "forward" ? "bk-slide-out-left" : "bk-slide-out-right";
    var inClass =
      direction === "forward" ? "bk-slide-in-right" : "bk-slide-in-left";

    current.classList.add(outClass);

    var outDone = false;
    function afterOut() {
      if (outDone) return;
      outDone = true;
      current.style.display = "none";
      current.classList.remove(outClass);

      target.style.display = "block";
      target.classList.add(inClass);

      var inDone = false;
      function afterIn() {
        if (inDone) return;
        inDone = true;
        target.classList.remove(inClass);
      }
      target.addEventListener("animationend", afterIn, { once: true });
      setTimeout(afterIn, 350);

      currentStep = next;
      updateProgress(next);
      if (next === 3) buildReview();
      wrapper.scrollIntoView({ behavior: "smooth", block: "start" });
    }

    current.addEventListener("animationend", afterOut, { once: true });
    setTimeout(afterOut, 350);
  }

  // ── Korak 1: Primarne usluge ──────────────────────────────

  document
    .querySelectorAll('input[name="primarna_usluga"]')
    .forEach(function (r) {
      r.addEventListener("change", function () {
        stanje.primarna_slug = this.value;
        stanje.primarna_naziv = this.getAttribute("data-naziv");
        stanje.podusluga = null;
        stanje.kolicina = 50;

        document.querySelectorAll(".bk-primary-card").forEach(function (c) {
          c.classList.remove("selected");
        });
        this.closest(".bk-primary-card").classList.add("selected");

        prikaziPodusluge(this.value);
        sakriKolicinu();
        updateDodajWrap();
      });
    });

  function prikaziPodusluge(primarySlug) {
    var usluga = BK_USLUGE.find(function (u) {
      return u.slug === primarySlug;
    });
    var wrap = document.getElementById("bk-podusluge-wrap");

    if (!usluga || !usluga.podusluge || usluga.podusluge.length === 0) {
      wrap.style.display = "none";
      return;
    }

    document.getElementById("bk-primarna-naziv").textContent = usluga.naziv;

    var lista = document.getElementById("bk-podusluge-lista");
    lista.innerHTML = "";

    usluga.podusluge.forEach(function (ps) {
      var card = document.createElement("label");
      card.className = "bk-podusluga-card";

      var inp = document.createElement("input");
      inp.type = "radio";
      inp.name = "podusluga";
      inp.value = ps.slug;
      card.appendChild(inp);

      var nameEl = document.createElement("div");
      nameEl.className = "bk-ps-name";
      nameEl.textContent = ps.naziv;
      card.appendChild(nameEl);

      var badgeWrap = document.createElement("div");
      badgeWrap.className = "bk-ps-cena-badge";
      badgeWrap.innerHTML = formatCenaBadge(ps);
      card.appendChild(badgeWrap);

      if (ps.napomena) {
        var nap = document.createElement("div");
        nap.className = "bk-ps-napomena";
        nap.textContent = ps.napomena;
        card.appendChild(nap);
      }

      inp.addEventListener("change", function () {
        document.querySelectorAll(".bk-podusluga-card").forEach(function (c) {
          c.classList.remove("selected");
        });
        card.classList.add("selected");
        stanje.podusluga = ps;
        stanje.kolicina = 50;
        prikaziKolicinu(ps);
        updateDodajWrap();
        updateLiveCena();
      });

      lista.appendChild(card);
    });

    wrap.style.display = "block";
  }

  function formatCenaBadge(ps) {
    var tip = ps.tip_cene;
    var jed = ps.jedinica_label || "m²";
    var pillLabels = {
      po_m2: "po " + jed,
      raspon_m2: "raspon/" + jed,
      po_komadu: "po kom",
      raspon_kom: "raspon/kom",
      po_dogovoru: "po dogovoru",
    };
    var pill =
      '<span class="bk-ps-tip-pill ' +
      tip +
      '">' +
      (pillLabels[tip] || tip) +
      "</span>";

    var cena = "";
    if (tip === "po_dogovoru") {
      cena = '<span class="bk-ps-cena-text">Po dogovoru</span>';
    } else if (tip === "raspon_m2" || tip === "raspon_kom") {
      cena =
        '<span class="bk-ps-cena-text">' +
        fmtRsd(ps.cena_min) +
        " – " +
        fmtRsd(ps.cena_max) +
        " RSD/" +
        jed +
        "</span>";
    } else if (tip === "po_m2") {
      cena =
        '<span class="bk-ps-cena-text">' +
        fmtRsd(ps.cena_min) +
        " RSD/" +
        jed +
        "</span>";
    } else if (tip === "po_komadu") {
      cena =
        '<span class="bk-ps-cena-text">od ' +
        fmtRsd(ps.cena_min) +
        " RSD/kom</span>";
    }
    return cena + " " + pill;
  }

  // ── Unos količine ─────────────────────────────────────────

  function prikaziKolicinu(ps) {
    var wrap = document.getElementById("bk-kolicina-wrap");
    if (ps.tip_cene === "po_dogovoru") {
      wrap.style.display = "none";
      return;
    }
    var jed = ps.jedinica_label || "m²";
    var titleMap = {
      po_m2: "Unesite površinu (" + jed + ")",
      raspon_m2: "Unesite površinu (" + jed + ")",
      po_komadu: "Unesite broj komada",
      raspon_kom: "Unesite broj komada",
    };
    document.getElementById("bk-kolicina-title").textContent =
      titleMap[ps.tip_cene] || "Unesite količinu";
    document.getElementById("bk-kolicina-napomena").textContent =
      ps.napomena || "";
    var jedEl = document.getElementById("bk-kolicina-jed");
    if (jedEl) jedEl.textContent = jed;

    var inp = document.getElementById("bk-kolicina-input");
    inp.value =
      ps.tip_cene === "po_komadu" || ps.tip_cene === "raspon_kom" ? 1 : 50;
    stanje.kolicina = parseFloat(inp.value);

    wrap.style.display = "block";
    document.getElementById("bk-live-cena").style.display = "block";
  }

  function sakriKolicinu() {
    document.getElementById("bk-kolicina-wrap").style.display = "none";
  }

  document
    .getElementById("bk-kolicina-input")
    .addEventListener("input", function () {
      stanje.kolicina = parseFloat(this.value) || 0;
      updateDodajWrap();
      updateLiveCena();
    });

  // ── Live cena preview ─────────────────────────────────────

  function updateLiveCena() {
    if (!stanje.podusluga) return;
    var ps = stanje.podusluga;
    var kol = stanje.kolicina;
    var el = document.getElementById("bk-live-cena-value");
    var tipEl = document.getElementById("bk-live-cena-tip");

    if (ps.tip_cene === "po_dogovoru") return;

    if (ps.tip_cene === "raspon_m2" || ps.tip_cene === "raspon_kom") {
      var rawMin = ps.cena_min * kol;
      var rawMax = ps.cena_max * kol;
      var dMin = Math.max(rawMin, BK_MIN_CENA);
      var dMax = Math.max(rawMax, BK_MIN_CENA);
      el.textContent = fmtRsd(dMin) + " – " + fmtRsd(dMax) + " RSD";
      tipEl.textContent =
        rawMin < BK_MIN_CENA || rawMax < BK_MIN_CENA
          ? "Primenjena minimalna cena: " + fmtRsd(BK_MIN_CENA) + " RSD"
          : "Okvirna cena bez putnog doplatka";
    } else {
      var raw = ps.cena_min * kol;
      var disp = Math.max(raw, BK_MIN_CENA);
      el.textContent = "≈ " + fmtRsd(disp) + " RSD";
      tipEl.textContent =
        raw < BK_MIN_CENA
          ? "Primenjena minimalna cena: " + fmtRsd(BK_MIN_CENA) + " RSD"
          : "Okvirna cena bez putnog doplatka";
    }
  }

  // ── Dodaj uslugu (korpa) ──────────────────────────────────

  function updateDodajWrap() {
    var ps = stanje.podusluga;
    if (!ps) {
      dodajWrap.style.display = "none";
      return;
    }
    var ok = ps.tip_cene === "po_dogovoru" || stanje.kolicina > 0;
    dodajWrap.style.display = ok ? "block" : "none";
    btnDodaj.disabled = !ok;
  }

  btnDodaj.addEventListener("click", function () {
    var ps = stanje.podusluga;
    if (!ps) return;
    if (ps.tip_cene !== "po_dogovoru" && stanje.kolicina <= 0) return;

    var label = stanje.primarna_naziv + " › " + ps.naziv;

    stanje.usluge_lista.push({
      primarna_slug: stanje.primarna_slug,
      primarna_naziv: stanje.primarna_naziv,
      podusluga: ps,
      kolicina: stanje.kolicina,
    });

    resetSelection();
    renderCart();
    updateNext1();
    showToast("&#10003; Dodato: " + escHtml(label));

    var cartEl = document.getElementById("bk-cart");
    if (cartEl) {
      setTimeout(function () {
        cartEl.scrollIntoView({ behavior: "smooth", block: "nearest" });
      }, 80);
    }
  });

  function showToast(msg) {
    var toast = document.getElementById("bk-toast");
    if (!toast) {
      toast = document.createElement("div");
      toast.id = "bk-toast";
      document.body.appendChild(toast);
    }
    toast.innerHTML = msg;
    toast.className = "bk-toast bk-toast-in";
    clearTimeout(toast._timer);
    toast._timer = setTimeout(function () {
      toast.className = "bk-toast bk-toast-out";
    }, 2200);
  }

  function resetSelection() {
    document
      .querySelectorAll('input[name="primarna_usluga"]')
      .forEach(function (r) {
        r.checked = false;
      });
    document.querySelectorAll(".bk-primary-card").forEach(function (c) {
      c.classList.remove("selected");
    });
    document.getElementById("bk-podusluge-wrap").style.display = "none";
    document.getElementById("bk-kolicina-wrap").style.display = "none";
    document.getElementById("bk-live-cena").style.display = "none";
    dodajWrap.style.display = "none";
    stanje.primarna_slug = null;
    stanje.primarna_naziv = null;
    stanje.podusluga = null;
    stanje.kolicina = 50;
  }

  function renderCart() {
    var cartEl = document.getElementById("bk-cart");
    if (!cartEl) return;

    if (stanje.usluge_lista.length === 0) {
      cartEl.style.display = "none";
      return;
    }

    cartEl.style.display = "block";
    var itemsEl = cartEl.querySelector(".bk-cart-items");
    itemsEl.innerHTML = stanje.usluge_lista
      .map(function (item, i) {
        var ps = item.podusluga;
        var kol =
          ps.tip_cene !== "po_dogovoru"
            ? item.kolicina + " " + (ps.jedinica_label || "m²")
            : "Po dogovoru";
        return (
          '<div class="bk-cart-item">' +
          '<div class="bk-cart-item-info">' +
          '<div class="bk-cart-item-naziv">' +
          escHtml(item.primarna_naziv + " › " + ps.naziv) +
          "</div>" +
          '<div class="bk-cart-item-kol">' +
          escHtml(kol) +
          "</div>" +
          "</div>" +
          '<button class="bk-cart-remove" data-index="' +
          i +
          '" aria-label="Ukloni">&#x2715;</button>' +
          "</div>"
        );
      })
      .join("");

    itemsEl.querySelectorAll(".bk-cart-remove").forEach(function (btn) {
      btn.addEventListener("click", function () {
        stanje.usluge_lista.splice(
          parseInt(this.getAttribute("data-index")),
          1,
        );
        renderCart();
        updateNext1();
      });
    });
  }

  // ── Validacija + Next ─────────────────────────────────────

  function updateNext1() {
    next1.disabled = stanje.usluge_lista.length === 0;
  }

  // ── Navigacija ────────────────────────────────────────────

  next1.addEventListener("click", function () {
    if (stanje.usluge_lista.length === 0) return;
    goToStep(2, "forward");
  });

  document.getElementById("bk-back-2").addEventListener("click", function () {
    goToStep(1, "backward");
  });

  document.getElementById("bk-next-2").addEventListener("click", function () {
    if (!document.getElementById("opstina").value) {
      setError("bk-error-2", "Molimo izaberite opštinu.");
      return;
    }
    setError("bk-error-2", "");
    goToStep(3, "forward");
  });

  document.getElementById("bk-back-3").addEventListener("click", function () {
    goToStep(2, "backward");
  });

  // ── Korak 2 — pratimo promene ─────────────────────────────

  document.getElementById("opstina").addEventListener("change", function () {
    stanje.opstina_transport = parseInt(this.value) || 0;
    var selOpt = this.options[this.selectedIndex];
    stanje.opstina_naziv =
      selOpt.getAttribute("data-naziv") || selOpt.text.split("(")[0].trim();
    setError("bk-error-2", "");
  });

  document.querySelectorAll('input[name="ucestalost"]').forEach(function (r) {
    r.addEventListener("change", function () {
      stanje.ugovor_procenat = parseInt(this.value) || 0;
      var chipLabel = this.closest(".bk-chip").querySelector(".bk-chip-label");
      stanje.ugovor_naziv = chipLabel.childNodes[0].nodeValue
        ? chipLabel.childNodes[0].nodeValue.trim()
        : chipLabel.textContent.split("\n")[0].trim();
    });
  });

  document.querySelectorAll('input[name="hitnost"]').forEach(function (r) {
    r.addEventListener("change", function () {
      stanje.hitnost_procenat = parseInt(this.value) || 0;
      var chipLabel = this.closest(".bk-chip").querySelector(".bk-chip-label");
      stanje.hitnost_naziv = chipLabel.childNodes[0].nodeValue
        ? chipLabel.childNodes[0].nodeValue.trim()
        : chipLabel.textContent.split("\n")[0].trim();
    });
  });

  // ── Review card (korak 3) ─────────────────────────────────

  function buildReview() {
    var grid = document.getElementById("bk-review-grid");
    if (!grid) return;

    var html = "";
    var multi = stanje.usluge_lista.length > 1;

    stanje.usluge_lista.forEach(function (item, i) {
      var ps = item.podusluga;
      html +=
        '<div class="bk-review-row">' +
        '<span class="bk-review-label">' +
        (multi ? "Usluga " + (i + 1) : "Usluga") +
        "</span>" +
        '<span class="bk-review-value">' +
        escHtml(item.primarna_naziv + " › " + ps.naziv) +
        "</span>" +
        "</div>";
      if (ps.tip_cene !== "po_dogovoru") {
        html +=
          '<div class="bk-review-row">' +
          '<span class="bk-review-label">Količina</span>' +
          '<span class="bk-review-value">' +
          escHtml(item.kolicina + " " + (ps.jedinica_label || "m²")) +
          "</span>" +
          "</div>";
      }
    });

    html +=
      '<div class="bk-review-row"><span class="bk-review-label">Opština</span><span class="bk-review-value">' +
      escHtml(stanje.opstina_naziv || "—") +
      "</span></div>";
    html +=
      '<div class="bk-review-row"><span class="bk-review-label">Ugovor</span><span class="bk-review-value">' +
      escHtml(stanje.ugovor_naziv) +
      "</span></div>";
    html +=
      '<div class="bk-review-row"><span class="bk-review-label">Hitnost</span><span class="bk-review-value">' +
      escHtml(stanje.hitnost_naziv) +
      "</span></div>";

    grid.innerHTML = html;
  }

  // ── Računanje finalne cene ────────────────────────────────

  function izracunajFinalnu() {
    if (stanje.usluge_lista.length === 0) return null;

    var transport = stanje.opstina_transport;
    var ugovor = stanje.ugovor_procenat;
    var hitnost = stanje.hitnost_procenat;
    var minTotal = BK_MIN_CENA + transport;

    function uslugaCena(osnova) {
      var c = osnova;
      c = c * (1 + ugovor / 100);
      c = c * (1 + hitnost / 100);
      return Math.max(Math.round(c / 100) * 100, BK_MIN_CENA);
    }

    var stavke = [];
    var sumMin = 0;
    var sumMax = 0;
    var hasRaspon = false;
    var hasDogovor = false;

    stanje.usluge_lista.forEach(function (item) {
      var ps = item.podusluga;
      var kol = item.kolicina;

      if (ps.tip_cene === "po_dogovoru") {
        hasDogovor = true;
        stavke.push({
          naziv: item.primarna_naziv + " › " + ps.naziv,
          kolicina: null,
          cena_str: "Po dogovoru",
          cena_min: BK_MIN_CENA,
          cena_max: BK_MIN_CENA,
          tip: "dogovor",
        });
        sumMin += BK_MIN_CENA;
        sumMax += BK_MIN_CENA;
      } else if (ps.tip_cene === "raspon_m2" || ps.tip_cene === "raspon_kom") {
        hasRaspon = true;
        var uslMin = uslugaCena(ps.cena_min * kol);
        var uslMax = uslugaCena(ps.cena_max * kol);
        stavke.push({
          naziv: item.primarna_naziv + " › " + ps.naziv,
          kolicina: kol + " " + (ps.jedinica_label || "m²"),
          cena_str: fmtRsd(uslMin) + " – " + fmtRsd(uslMax) + " RSD",
          cena_min: uslMin,
          cena_max: uslMax,
          tip: "raspon",
        });
        sumMin += uslMin;
        sumMax += uslMax;
      } else {
        var usl = uslugaCena(ps.cena_min * kol);
        stavke.push({
          naziv: item.primarna_naziv + " › " + ps.naziv,
          kolicina: kol + " " + (ps.jedinica_label || "m²"),
          cena_str: "≈ " + fmtRsd(usl) + " RSD",
          cena_min: usl,
          cena_max: usl,
          tip: "fiksna",
        });
        sumMin += usl;
        sumMax += usl;
      }
    });

    var finMin = Math.max(sumMin + transport, minTotal);
    var finMax = Math.max(sumMax + transport, minTotal);
    var tip = hasDogovor ? "dogovor" : hasRaspon ? "raspon" : "fiksna";
    var cena_str =
      tip === "raspon" || tip === "dogovor"
        ? fmtRsd(finMin) + " – " + fmtRsd(finMax) + " RSD"
        : "≈ " + fmtRsd(finMin) + " RSD";

    return {
      tip: tip,
      stavke: stavke,
      cena_str: cena_str,
      cena_rsd: Math.round((finMin + finMax) / 2),
      finMin: finMin,
      finMax: finMax,
      sumMin: sumMin,
      sumMax: sumMax,
    };
  }

  // ── Email validacija ──────────────────────────────────────

  function isValidEmail(val) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(val.trim());
  }

  function updateEmailState() {
    var valid = isValidEmail(emailInput.value);
    var dirty = emailInput.value.length > 0;
    emailInput.classList.toggle("valid", valid);
    emailInput.classList.toggle("invalid", dirty && !valid);
    emailCheck.classList.toggle("visible", valid);
    btnIzracunaj.disabled = !valid;
  }

  emailInput.addEventListener("input", updateEmailState);
  emailInput.addEventListener("blur", updateEmailState);
  updateEmailState();

  function setError(id, msg) {
    var el = document.getElementById(id);
    if (!el) return;
    el.textContent = msg || "";
    el.style.display = msg ? "" : "none";
  }
  ["bk-error-1", "bk-error-2", "bk-error-3"].forEach(function (id) {
    var el = document.getElementById(id);
    if (el) el.style.display = "none";
  });

  // ── Kalkulacija + AJAX slanje ─────────────────────────────

  btnIzracunaj.addEventListener("click", function () {
    var errorEl = document.getElementById("bk-error-3");
    var resultEl = document.getElementById("bk-result");
    var statusEl = document.getElementById("bk-send-status");

    errorEl.style.display = "none";
    errorEl.textContent = "";
    statusEl.className = "bk-send-status";
    statusEl.textContent = "";

    var email = emailInput.value.trim();
    if (!isValidEmail(email)) return;

    var fin = izracunajFinalnu();
    if (!fin) return;

    // ── Subtitle ───────────────────────────────────────────
    document.getElementById("result-subtitle").textContent =
      stanje.usluge_lista.length === 1
        ? stanje.usluge_lista[0].primarna_naziv +
          " › " +
          stanje.usluge_lista[0].podusluga.naziv
        : stanje.usluge_lista.length + " odabrane usluge";

    // ── Cena prikaz ────────────────────────────────────────
    var cenaEl = document.getElementById("result-cena");
    var cenaValEl =
      cenaEl.closest(".bk-result-price-value") || cenaEl.parentElement;
    var cenaDisplay;
    if (fin.tip === "raspon" || fin.tip === "dogovor") {
      cenaDisplay = fin.cena_str.replace(" RSD", "");
      cenaValEl.classList.add("raspon");
    } else {
      cenaDisplay = fmtRsd(fin.cena_rsd);
      cenaValEl.classList.remove("raspon");
    }
    cenaEl.textContent = cenaDisplay;

    // ── Razrada po stavkama ────────────────────────────────
    var bd = document.getElementById("result-breakdown");
    var bdHTML = "";

    fin.stavke.forEach(function (s) {
      bdHTML +=
        '<div class="bk-breakdown-item">' +
        '<span class="name">' +
        escHtml(s.naziv) +
        "</span>" +
        '<span class="meta">' +
        (s.kolicina ? escHtml(s.kolicina) : "") +
        "</span>" +
        '<span class="price">' +
        escHtml(s.cena_str) +
        "</span>" +
        "</div>";
    });

    if (stanje.opstina_transport > 0) {
      bdHTML +=
        '<div class="bk-breakdown-item">' +
        '<span class="name">Putni trošak (' +
        escHtml(stanje.opstina_naziv) +
        ")</span>" +
        '<span class="meta">+' +
        fmtRsd(stanje.opstina_transport) +
        " RSD</span>" +
        '<span class="price"></span></div>';
    }
    if (stanje.ugovor_procenat !== 0) {
      bdHTML +=
        '<div class="bk-breakdown-item">' +
        '<span class="name">' +
        escHtml(stanje.ugovor_naziv) +
        "</span>" +
        '<span class="meta">' +
        stanje.ugovor_procenat +
        "%</span>" +
        '<span class="price"></span></div>';
    }
    if (stanje.hitnost_procenat !== 0) {
      bdHTML +=
        '<div class="bk-breakdown-item">' +
        '<span class="name">' +
        escHtml(stanje.hitnost_naziv) +
        "</span>" +
        '<span class="meta">+' +
        stanje.hitnost_procenat +
        "%</span>" +
        '<span class="price"></span></div>';
    }
    var minTotal = BK_MIN_CENA + stanje.opstina_transport;
    if (fin.cena_rsd <= minTotal || fin.tip === "dogovor") {
      bdHTML +=
        '<div class="bk-breakdown-item bk-bd-min">' +
        '<span class="name">⚠️ Minimalna cena</span>' +
        '<span class="meta"></span>' +
        '<span class="price">' +
        fmtRsd(minTotal) +
        " RSD</span></div>";
    }
    bd.innerHTML = bdHTML;

    // ── Tagovi ─────────────────────────────────────────────
    var tags = [];
    if (stanje.opstina_naziv) tags.push(stanje.opstina_naziv);
    stanje.usluge_lista.forEach(function (item) {
      tags.push(item.podusluga.naziv);
    });
    tags.push(stanje.ugovor_naziv, stanje.hitnost_naziv);
    if (fin.tip === "dogovor") tags.push("🤝 Po dogovoru");
    if (fin.cena_rsd <= minTotal)
      tags.push("⚠️ Min. cena " + fmtRsd(minTotal) + " RSD");

    document.getElementById("result-tags").innerHTML = tags
      .map(function (t) {
        return '<span class="bk-result-tag">' + escHtml(t) + "</span>";
      })
      .join("");

    resultEl.style.display = "block";
    resultEl.scrollIntoView({ behavior: "smooth", block: "nearest" });

    // ── AJAX ──────────────────────────────────────────────
    btnIzracunaj.disabled = true;
    btnIzracunaj.textContent = "Slanje...";

    var stavkeStr = fin.stavke
      .map(function (s) {
        return (
          s.naziv +
          (s.kolicina ? " (" + s.kolicina + ")" : "") +
          ": " +
          s.cena_str
        );
      })
      .join("\n");
    var uslugeNazivi = stanje.usluge_lista
      .map(function (item) {
        return item.primarna_naziv + " › " + item.podusluga.naziv;
      })
      .join(", ");

    posaljiEmail({
      email: email,
      usluge: uslugeNazivi,
      cena: fin.cena_str,
      povrsina: 0,
      opstina: stanje.opstina_naziv || "—",
      ugovor: stanje.ugovor_naziv,
      hitnost: stanje.hitnost_naziv,
      stavke: stavkeStr,
    });
  });

  // ── AJAX slanje ───────────────────────────────────────────

  function posaljiEmail(data) {
    var statusEl = document.getElementById("bk-send-status");
    statusEl.className = "bk-send-status sending";
    statusEl.textContent = "⏳ Šaljemo procenu na " + data.email + "...";

    var fd = new FormData();
    fd.append("action", "bk_posalji_email");
    fd.append("nonce", bkAjax.nonce);
    fd.append("email", data.email);
    fd.append("usluge", data.usluge);
    fd.append("cena", data.cena);
    fd.append("povrsina", data.povrsina);
    fd.append("opstina", data.opstina);
    fd.append("ugovor", data.ugovor);
    fd.append("hitnost", data.hitnost);
    fd.append("stavke", data.stavke);

    fetch(bkAjax.url, { method: "POST", body: fd })
      .then(function (r) {
        return r.json();
      })
      .then(function (res) {
        var statusEl = document.getElementById("bk-send-status");
        if (res.success) {
          statusEl.className = "bk-send-status success";
          statusEl.textContent = "✅ Procena je poslata na " + data.email;
        } else {
          var msg = "⚠️ Greška pri slanju. Pokušajte ponovo.";
          if (res.data && res.data.code === "rate_limited") {
            msg = "⏱ Previše zahteva. Pokušajte ponovo za sat vremena.";
          }
          statusEl.className = "bk-send-status error";
          statusEl.textContent = msg;
        }
        btnIzracunaj.disabled = false;
        btnIzracunaj.textContent = "Izračunaj i pošalji procenu 📨";
      })
      .catch(function () {
        var statusEl = document.getElementById("bk-send-status");
        statusEl.className = "bk-send-status error";
        statusEl.textContent =
          "⚠️ Greška pri slanju. Proverite internet konekciju.";
        btnIzracunaj.disabled = false;
        btnIzracunaj.textContent = "Izračunaj i pošalji procenu 📨";
      });
  }

  // ── Reset ─────────────────────────────────────────────────

  function reset() {
    resetSelection();
    stanje.usluge_lista = [];
    stanje.opstina_transport = 0;
    stanje.opstina_naziv = "";
    stanje.ugovor_procenat = 0;
    stanje.ugovor_naziv = "Jednokratno";
    stanje.hitnost_procenat = 0;
    stanje.hitnost_naziv = "Standardno (3-5 dana)";

    renderCart();

    document.getElementById("opstina").value = "";
    document.querySelector('input[name="ucestalost"][value="0"]').checked =
      true;
    document.querySelector('input[name="hitnost"][value="0"]').checked = true;

    document.getElementById("bk-result").style.display = "none";
    ["bk-error-1", "bk-error-2", "bk-error-3"].forEach(function (id) {
      var el = document.getElementById(id);
      if (el) {
        el.style.display = "none";
        el.textContent = "";
      }
    });
    document.getElementById("bk-send-status").className = "bk-send-status";
    document.getElementById("bk-send-status").textContent = "";
    emailInput.value = "";
    updateEmailState();
    next1.disabled = true;

    document.getElementById("bk-step-" + currentStep).style.display = "none";
    document.getElementById("bk-step-1").style.display = "block";
    currentStep = 1;
    updateProgress(1);

    wrapper.scrollIntoView({ behavior: "smooth", block: "start" });
  }

  document.getElementById("bk-btn-reset").addEventListener("click", reset);
  document
    .getElementById("bk-btn-kontakt")
    .addEventListener("click", function () {
      window.location.href = "tel:+381600000000";
    });

  // ── Init ──────────────────────────────────────────────────
  updateProgress(1);

  // ── Pomoćne ───────────────────────────────────────────────
  function fmtRsd(n) {
    return Math.round(n).toLocaleString("sr-RS");
  }
  function escHtml(s) {
    return String(s)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }
});
