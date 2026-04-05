document.addEventListener('DOMContentLoaded', function () {

    var wrapper = document.querySelector('.bk-wrapper');
    if (!wrapper) return;

    // ── Katalog usluga (injektovan iz PHP-a) ──────────────────
    var BK_USLUGE   = window.bkUsluge || [];
    var BK_MIN_CENA = 4000;

    // ── State ─────────────────────────────────────────────────
    var currentStep = 1;
    var totalSteps  = 3;

    var stanje = {
        primarna_slug:      null,
        primarna_naziv:     null,
        podusluga:          null, // objekat podusluge
        kolicina:           50,
        opstina_transport:  0,   // fiksni RSD
        opstina_naziv:      '',
        ugovor_procenat:    0,
        ugovor_naziv:       'Jednokratno',
        hitnost_procenat:   0,
        hitnost_naziv:      'Standardno (3-5 dana)',
    };

    // ── Element refs ──────────────────────────────────────────
    var emailInput   = document.getElementById('bk-email');
    var emailCheck   = document.getElementById('bk-email-check');
    var btnIzracunaj = document.getElementById('bk-btn-izracunaj');
    var next1        = document.getElementById('bk-next-1');

    // ── Progress bar ──────────────────────────────────────────
    function updateProgress(step) {
        var fill = document.getElementById('bk-progress-fill');
        var pct  = ((step - 1) / (totalSteps - 1)) * 100;
        fill.style.width = pct + '%';

        document.querySelectorAll('.bk-progress-step').forEach(function (el) {
            var s = parseInt(el.getAttribute('data-step'));
            el.classList.toggle('active',    s === step);
            el.classList.toggle('completed', s < step);
        });
    }

    // ── Step transition (animacija) ───────────────────────────
    function goToStep(next, direction) {
        var current = document.getElementById('bk-step-' + currentStep);
        var target  = document.getElementById('bk-step-' + next);
        var outClass = direction === 'forward' ? 'bk-slide-out-left'  : 'bk-slide-out-right';
        var inClass  = direction === 'forward' ? 'bk-slide-in-right'  : 'bk-slide-in-left';

        current.classList.add(outClass);
        current.addEventListener('animationend', function handler() {
            current.removeEventListener('animationend', handler);
            current.style.display = 'none';
            current.classList.remove(outClass);

            target.style.display = 'block';
            target.classList.add(inClass);
            target.addEventListener('animationend', function h2() {
                target.removeEventListener('animationend', h2);
                target.classList.remove(inClass);
            });

            currentStep = next;
            updateProgress(next);

            if (next === 3) {
                buildReview();
            }

            wrapper.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, { once: true });
    }

    // ── Korak 1: Primarne usluge ──────────────────────────────

    document.querySelectorAll('input[name="primarna_usluga"]').forEach(function (r) {
        r.addEventListener('change', function () {
            stanje.primarna_slug  = this.value;
            stanje.primarna_naziv = this.getAttribute('data-naziv');
            stanje.podusluga = null;

            // Ukloni selected sa svim karticama
            document.querySelectorAll('.bk-primary-card').forEach(function(c) {
                c.classList.remove('selected');
            });
            this.closest('.bk-primary-card').classList.add('selected');

            prikaziPodusluge(this.value);
            sakriKolicinu();
            updateNext1();
        });
    });

    function prikaziPodusluge(primarySlug) {
        var usluga = BK_USLUGE.find(function (u) { return u.slug === primarySlug; });
        var wrap   = document.getElementById('bk-podusluge-wrap');

        if (!usluga || !usluga.podusluge || usluga.podusluge.length === 0) {
            wrap.style.display = 'none';
            return;
        }

        document.getElementById('bk-primarna-naziv').textContent = usluga.naziv;

        var lista = document.getElementById('bk-podusluge-lista');
        lista.innerHTML = '';

        usluga.podusluge.forEach(function (ps) {
            var card = document.createElement('label');
            card.className = 'bk-podusluga-card';

            var inp = document.createElement('input');
            inp.type  = 'radio';
            inp.name  = 'podusluga';
            inp.value = ps.slug;

            card.appendChild(inp);

            var nameEl = document.createElement('div');
            nameEl.className = 'bk-ps-name';
            nameEl.textContent = ps.naziv;
            card.appendChild(nameEl);

            var badgeWrap = document.createElement('div');
            badgeWrap.className = 'bk-ps-cena-badge';
            badgeWrap.innerHTML = formatCenaBadge(ps);
            card.appendChild(badgeWrap);

            if (ps.napomena) {
                var nap = document.createElement('div');
                nap.className = 'bk-ps-napomena';
                nap.textContent = ps.napomena;
                card.appendChild(nap);
            }

            inp.addEventListener('change', function () {
                document.querySelectorAll('.bk-podusluga-card').forEach(function (c) {
                    c.classList.remove('selected');
                });
                card.classList.add('selected');
                stanje.podusluga = ps;
                prikaziKolicinu(ps);
                updateNext1();
                updateLiveCena();
            });

            lista.appendChild(card);
        });

        wrap.style.display = 'block';
    }

    function formatCenaBadge(ps) {
        var tip = ps.tip_cene;
        var jed = ps.jedinica_label || 'm²';
        var pillLabels = {
            'po_m2':      'po ' + jed,
            'raspon_m2':  'raspon/' + jed,
            'po_komadu':  'po kom',
            'raspon_kom': 'raspon/kom',
            'po_dogovoru':'po dogovoru',
        };
        var pill = '<span class="bk-ps-tip-pill ' + tip + '">' + (pillLabels[tip] || tip) + '</span>';

        var cena = '';
        if (tip === 'po_dogovoru') {
            cena = '<span class="bk-ps-cena-text">Po dogovoru</span>';
        } else if (tip === 'raspon_m2' || tip === 'raspon_kom') {
            cena = '<span class="bk-ps-cena-text">' + fmtRsd(ps.cena_min) + ' – ' + fmtRsd(ps.cena_max) + ' RSD/' + jed + '</span>';
        } else if (tip === 'po_m2') {
            cena = '<span class="bk-ps-cena-text">' + fmtRsd(ps.cena_min) + ' RSD/' + jed + '</span>';
        } else if (tip === 'po_komadu') {
            cena = '<span class="bk-ps-cena-text">od ' + fmtRsd(ps.cena_min) + ' RSD/kom</span>';
        }
        return cena + ' ' + pill;
    }

    // ── Unos količine ─────────────────────────────────────────

    function prikaziKolicinu(ps) {
        var wrap = document.getElementById('bk-kolicina-wrap');
        if (ps.tip_cene === 'po_dogovoru') {
            wrap.style.display = 'none';
            return;
        }
        var jed = ps.jedinica_label || 'm²';
        var titleMap = {
            'po_m2':      'Unesite površinu (' + jed + ')',
            'raspon_m2':  'Unesite površinu (' + jed + ')',
            'po_komadu':  'Unesite broj komada',
            'raspon_kom': 'Unesite broj komada',
        };
        document.getElementById('bk-kolicina-title').textContent   = titleMap[ps.tip_cene] || 'Unesite količinu';
        document.getElementById('bk-kolicina-napomena').textContent = ps.napomena || '';
        var jedEl = document.getElementById('bk-kolicina-jed');
        if (jedEl) jedEl.textContent = jed;

        var inp = document.getElementById('bk-kolicina-input');
        // Resetuj na razumnu default vrednost
        inp.value = (ps.tip_cene === 'po_komadu' || ps.tip_cene === 'raspon_kom') ? 1 : 50;
        stanje.kolicina = parseFloat(inp.value);

        wrap.style.display = 'block';
        document.getElementById('bk-live-cena').style.display = 'block';
        updateLiveCena();
    }

    function sakriKolicinu() {
        document.getElementById('bk-kolicina-wrap').style.display = 'none';
    }

    document.getElementById('bk-kolicina-input').addEventListener('input', function () {
        stanje.kolicina = parseFloat(this.value) || 0;
        updateLiveCena();
        updateNext1();
    });

    // ── Live cena preview ─────────────────────────────────────

    function updateLiveCena() {
        if (!stanje.podusluga) return;
        var ps  = stanje.podusluga;
        var kol = stanje.kolicina;
        var el  = document.getElementById('bk-live-cena-value');
        var tipEl = document.getElementById('bk-live-cena-tip');

        if (ps.tip_cene === 'po_dogovoru') return;

        if (ps.tip_cene === 'raspon_m2' || ps.tip_cene === 'raspon_kom') {
            var rawMin = ps.cena_min * kol;
            var rawMax = ps.cena_max * kol;
            var dMin   = Math.max(rawMin, BK_MIN_CENA);
            var dMax   = Math.max(rawMax, BK_MIN_CENA);
            el.textContent  = fmtRsd(dMin) + ' – ' + fmtRsd(dMax) + ' RSD';
            tipEl.textContent = (rawMin < BK_MIN_CENA || rawMax < BK_MIN_CENA)
                ? 'Primenjena minimalna cena: ' + fmtRsd(BK_MIN_CENA) + ' RSD'
                : 'Okvirna cena bez putnog doplatka';
        } else {
            var raw  = ps.cena_min * kol;
            var disp = Math.max(raw, BK_MIN_CENA);
            el.textContent  = '≈ ' + fmtRsd(disp) + ' RSD';
            tipEl.textContent = raw < BK_MIN_CENA
                ? 'Primenjena minimalna cena: ' + fmtRsd(BK_MIN_CENA) + ' RSD'
                : 'Okvirna cena bez putnog doplatka';
        }
    }

    // ── Validacija + Next ─────────────────────────────────────

    function updateNext1() {
        var ps  = stanje.podusluga;
        var kol = stanje.kolicina;
        var ok  = stanje.primarna_slug &&
                  ps &&
                  (ps.tip_cene === 'po_dogovoru' || kol > 0);
        next1.disabled = !ok;
    }

    // ── Navigacija ────────────────────────────────────────────

    next1.addEventListener('click', function () {
        if (!stanje.primarna_slug || !stanje.podusluga) return;
        goToStep(2, 'forward');
    });

    document.getElementById('bk-back-2').addEventListener('click', function () {
        goToStep(1, 'backward');
    });

    document.getElementById('bk-next-2').addEventListener('click', function () {
        if (!document.getElementById('opstina').value) {
            setError('bk-error-2', 'Molimo izaberite opštinu.');
            return;
        }
        setError('bk-error-2', '');
        goToStep(3, 'forward');
    });

    document.getElementById('bk-back-3').addEventListener('click', function () {
        goToStep(2, 'backward');
    });

    // ── Korak 2 — pratimo promene ─────────────────────────────

    document.getElementById('opstina').addEventListener('change', function () {
        stanje.opstina_transport = parseInt(this.value) || 0;
        var selOpt = this.options[this.selectedIndex];
        stanje.opstina_naziv = selOpt.getAttribute('data-naziv') || selOpt.text.split('(')[0].trim();
        setError('bk-error-2', '');
    });

    document.querySelectorAll('input[name="ucestalost"]').forEach(function (r) {
        r.addEventListener('change', function () {
            stanje.ugovor_procenat = parseInt(this.value) || 0;
            var chipLabel = this.closest('.bk-chip').querySelector('.bk-chip-label');
            stanje.ugovor_naziv = chipLabel.childNodes[0].nodeValue
                ? chipLabel.childNodes[0].nodeValue.trim()
                : chipLabel.textContent.split('\n')[0].trim();
        });
    });

    document.querySelectorAll('input[name="hitnost"]').forEach(function (r) {
        r.addEventListener('change', function () {
            stanje.hitnost_procenat = parseInt(this.value) || 0;
            var chipLabel = this.closest('.bk-chip').querySelector('.bk-chip-label');
            stanje.hitnost_naziv = chipLabel.childNodes[0].nodeValue
                ? chipLabel.childNodes[0].nodeValue.trim()
                : chipLabel.textContent.split('\n')[0].trim();
        });
    });

    // ── Review card (korak 3) ─────────────────────────────────

    function buildReview() {
        var grid = document.getElementById('bk-review-grid');
        if (!grid) return;

        var fin  = izracunajFinalnu();
        var ps   = stanje.podusluga;

        var items = [
            { label: 'Usluga',    value: stanje.primarna_naziv || '—' },
            { label: 'Podusluga', value: ps ? ps.naziv : '—' },
        ];

        if (ps && ps.tip_cene !== 'po_dogovoru') {
            items.push({ label: 'Količina', value: stanje.kolicina + ' ' + (ps.jedinica_label || 'm²') });
        }

        items.push(
            { label: 'Opština', value: stanje.opstina_naziv || '—' },
            { label: 'Ugovor',  value: stanje.ugovor_naziv },
            { label: 'Hitnost', value: stanje.hitnost_naziv },
        );



        grid.innerHTML = items.map(function (it) {
            return '<div class="bk-review-row">'
                + '<span class="bk-review-label">' + escHtml(it.label) + '</span>'
                + '<span class="bk-review-value' + (it.bold ? ' bk-review-bold' : '') + '">' + escHtml(it.value) + '</span>'
                + '</div>';
        }).join('');
    }

    // ── Računanje finalne cene ────────────────────────────────

    function izracunajFinalnu() {
        var ps = stanje.podusluga;
        if (!ps) return null;

        var kol       = stanje.kolicina;
        var transport = stanje.opstina_transport; // fiksni RSD
        var ugovor    = stanje.ugovor_procenat;   // % (negativno = popust)
        var hitnost   = stanje.hitnost_procenat;  // % (pozitivno = doplata)
        var minTotal  = BK_MIN_CENA + transport;

        // Vraća cenu usluge bez transporta (zaokruženo, min 4000)
        function uslugaCena(osnova) {
            var c = osnova;
            c = c * (1 + ugovor  / 100);
            c = c * (1 + hitnost / 100);
            return Math.max(Math.round(c / 100) * 100, BK_MIN_CENA);
        }

        if (ps.tip_cene === 'po_dogovoru') {
            return {
                tip:        'dogovor',
                cena_str:   '≥ ' + fmtRsd(minTotal) + ' RSD + po dogovoru',
                cena_rsd:   minTotal,
                usluga_str: '≥ ' + fmtRsd(BK_MIN_CENA) + ' RSD',
                stavka:     ps.naziv + ': Po dogovoru (min. ' + fmtRsd(minTotal) + ' RSD)',
            };
        }

        if (ps.tip_cene === 'raspon_m2' || ps.tip_cene === 'raspon_kom') {
            var uslMin = uslugaCena(ps.cena_min * kol);
            var uslMax = uslugaCena(ps.cena_max * kol);
            var finMin = Math.max(uslMin + transport, minTotal);
            var finMax = Math.max(uslMax + transport, minTotal);
            return {
                tip:        'raspon',
                cena_str:   fmtRsd(finMin) + ' – ' + fmtRsd(finMax) + ' RSD',
                cena_rsd:   Math.round((finMin + finMax) / 2),
                usluga_str: fmtRsd(uslMin) + ' – ' + fmtRsd(uslMax) + ' RSD',
                stavka:     ps.naziv + ' (' + kol + ' ' + (ps.jedinica_label || 'm²') + '): '
                            + fmtRsd(finMin) + '–' + fmtRsd(finMax) + ' RSD',
            };
        }

        // po_m2 ili po_komadu — fiksna
        var usl = uslugaCena(ps.cena_min * kol);
        var fin = Math.max(usl + transport, minTotal);
        return {
            tip:        'fiksna',
            cena_str:   '≈ ' + fmtRsd(fin) + ' RSD',
            cena_rsd:   fin,
            usluga_str: '≈ ' + fmtRsd(usl) + ' RSD',
            stavka:     ps.naziv + ' (' + kol + ' ' + (ps.jedinica_label || 'm²') + '): ≈ ' + fmtRsd(fin) + ' RSD',
        };
    }

    // ── Email validacija ──────────────────────────────────────

    function isValidEmail(val) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(val.trim());
    }

    function updateEmailState() {
        var valid = isValidEmail(emailInput.value);
        var dirty = emailInput.value.length > 0;
        emailInput.classList.toggle('valid',   valid);
        emailInput.classList.toggle('invalid', dirty && !valid);
        emailCheck.classList.toggle('visible', valid);
        btnIzracunaj.disabled = !valid;
    }

    emailInput.addEventListener('input', updateEmailState);
    emailInput.addEventListener('blur',  updateEmailState);
    updateEmailState();

    // Sakrij sve error divove dok nemaju sadržaj
    function setError(id, msg) {
        var el = document.getElementById(id);
        if (!el) return;
        el.textContent = msg || '';
        el.style.display = msg ? '' : 'none';
    }
    // Init — sakrij sve
    ['bk-error-1', 'bk-error-2', 'bk-error-3'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) el.style.display = 'none';
    });

    // ── Kalkulacija + AJAX slanje ─────────────────────────────

    btnIzracunaj.addEventListener('click', function () {
        var errorEl  = document.getElementById('bk-error-3');
        var resultEl = document.getElementById('bk-result');
        var statusEl = document.getElementById('bk-send-status');

        errorEl.style.display = 'none';
        errorEl.textContent   = '';
        statusEl.className    = 'bk-send-status';
        statusEl.textContent  = '';

        var email = emailInput.value.trim();
        if (!isValidEmail(email)) return;

        var fin = izracunajFinalnu();
        if (!fin) return;

        var ps = stanje.podusluga;

        // ── Prikaži rezultat odmah ─────────────────────────────
        document.getElementById('result-subtitle').textContent =
            stanje.primarna_naziv + ' › ' + ps.naziv;

        // Cena prikaz
        var cenaEl = document.getElementById('result-cena');
        var cenaValEl = cenaEl.closest('.bk-result-price-value') || cenaEl.parentElement;
        var cenaDisplay;
        if (fin.tip === 'raspon') {
            // "4.000 – 6.000 RSD" → prikazujemo samo brojeve, RSD je u subtitlu
            cenaDisplay = fin.cena_str.replace(' RSD', '');
            cenaValEl.classList.add('raspon');
        } else if (fin.tip === 'dogovor') {
            cenaDisplay = fmtRsd(BK_MIN_CENA + stanje.opstina_transport) + '+';
            cenaValEl.classList.remove('raspon');
        } else {
            cenaDisplay = fmtRsd(fin.cena_rsd);
            cenaValEl.classList.remove('raspon');
        }
        cenaEl.textContent = cenaDisplay;

        // Razrada
        var bd = document.getElementById('result-breakdown');
        var bdHTML = '<div class="bk-breakdown-item">'
            + '<span class="name">' + escHtml(ps.naziv) + '</span>';

        if (ps.tip_cene !== 'po_dogovoru') {
            bdHTML += '<span class="meta"> &nbsp;' + stanje.kolicina + ' ' + escHtml(ps.jedinica_label || 'm²') + '</span>';
        }
        bdHTML += '<span class="price">' + escHtml(fin.usluga_str || fin.cena_str) + '</span></div>';

        if (stanje.opstina_transport > 0) {
            bdHTML += '<div class="bk-breakdown-item"><span class="name">Putni trošak (' + escHtml(stanje.opstina_naziv) + ')</span>'
                + '<span class="meta">+' + fmtRsd(stanje.opstina_transport) + ' RSD</span><span class="price"></span></div>';
        }
        if (stanje.ugovor_procenat !== 0) {
            bdHTML += '<div class="bk-breakdown-item"><span class="name">' + escHtml(stanje.ugovor_naziv) + '</span>'
                + '<span class="meta">' + stanje.ugovor_procenat + '%</span><span class="price"></span></div>';
        }
        if (stanje.hitnost_procenat !== 0) {
            bdHTML += '<div class="bk-breakdown-item"><span class="name">' + escHtml(stanje.hitnost_naziv) + '</span>'
                + '<span class="meta">+' + stanje.hitnost_procenat + '%</span><span class="price"></span></div>';
        }
        var minTotal = BK_MIN_CENA + stanje.opstina_transport;
        if (fin.cena_rsd <= minTotal || fin.tip === 'dogovor') {
            bdHTML += '<div class="bk-breakdown-item bk-bd-min"><span class="name">⚠️ Minimalna cena</span>'
                + '<span class="meta"></span><span class="price">' + fmtRsd(minTotal) + ' RSD</span></div>';
        }
        bd.innerHTML = bdHTML;

        // Tagovi
        var tags = [];
        if (stanje.opstina_naziv) tags.push(stanje.opstina_naziv);
        if (ps.tip_cene !== 'po_dogovoru') tags.push(stanje.kolicina + ' ' + (ps.jedinica_label || 'm²'));
        tags.push(stanje.ugovor_naziv, stanje.hitnost_naziv);
        if (fin.tip === 'dogovor')            tags.push('🤝 Po dogovoru');
        if (fin.cena_rsd <= minTotal)          tags.push('⚠️ Min. cena ' + fmtRsd(minTotal) + ' RSD');

        document.getElementById('result-tags').innerHTML = tags.map(function (t) {
            return '<span class="bk-result-tag">' + escHtml(t) + '</span>';
        }).join('');

        resultEl.style.display = 'block';
        resultEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

        // ── AJAX ──────────────────────────────────────────────
        btnIzracunaj.disabled   = true;
        btnIzracunaj.textContent = 'Slanje...';

        posaljiEmail({
            email:    email,
            usluge:   stanje.primarna_naziv + ' › ' + ps.naziv,
            cena:     fin.cena_str,
            povrsina: ps.tip_cene !== 'po_dogovoru' ? stanje.kolicina : 0,
            opstina:  stanje.opstina_naziv || '—',
            ugovor:   stanje.ugovor_naziv,
            hitnost:  stanje.hitnost_naziv,
            stavke:   fin.stavka,
        });
    });

    // ── AJAX slanje ───────────────────────────────────────────

    function posaljiEmail(data) {
        var statusEl = document.getElementById('bk-send-status');
        statusEl.className   = 'bk-send-status sending';
        statusEl.textContent = '⏳ Šaljemo procenu na ' + data.email + '...';

        var fd = new FormData();
        fd.append('action',   'bk_posalji_email');
        fd.append('nonce',    bkAjax.nonce);
        fd.append('email',    data.email);
        fd.append('usluge',   data.usluge);
        fd.append('cena',     data.cena);
        fd.append('povrsina', data.povrsina);
        fd.append('opstina',  data.opstina);
        fd.append('ugovor',   data.ugovor);
        fd.append('hitnost',  data.hitnost);
        fd.append('stavke',   data.stavke);

        fetch(bkAjax.url, { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                var statusEl = document.getElementById('bk-send-status');
                if (res.success) {
                    statusEl.className   = 'bk-send-status success';
                    statusEl.textContent = '✅ Procena je poslata na ' + data.email;
                } else {
                    var msg = '⚠️ Greška pri slanju. Pokušajte ponovo.';
                    if (res.data && res.data.code === 'rate_limited') {
                        msg = '⏱ Previše zahteva. Pokušajte ponovo za sat vremena.';
                    }
                    statusEl.className   = 'bk-send-status error';
                    statusEl.textContent = msg;
                }
                btnIzracunaj.disabled   = false;
                btnIzracunaj.textContent = 'Izračunaj i pošalji procenu 📨';
            })
            .catch(function () {
                var statusEl = document.getElementById('bk-send-status');
                statusEl.className   = 'bk-send-status error';
                statusEl.textContent = '⚠️ Greška pri slanju. Proverite internet konekciju.';
                btnIzracunaj.disabled   = false;
                btnIzracunaj.textContent = 'Izračunaj i pošalji procenu 📨';
            });
    }

    // ── Reset ─────────────────────────────────────────────────

    function reset() {
        // Primarne usluge
        document.querySelectorAll('input[name="primarna_usluga"]').forEach(function (r) { r.checked = false; });
        document.querySelectorAll('.bk-primary-card').forEach(function (c) { c.classList.remove('selected'); });

        // Podusluge
        document.getElementById('bk-podusluge-wrap').style.display = 'none';
        document.getElementById('bk-kolicina-wrap').style.display  = 'none';
        document.getElementById('bk-live-cena').style.display      = 'none';

        // Korak 2
        document.getElementById('opstina').value = '';
        document.querySelector('input[name="ucestalost"][value="0"]').checked = true;
        document.querySelector('input[name="hitnost"][value="0"]').checked    = true;

        // Resetuj stanje
        stanje = {
            primarna_slug: null, primarna_naziv: null, podusluga: null,
            kolicina: 50, opstina_transport: 0, opstina_naziv: '',
            ugovor_procenat: 0, ugovor_naziv: 'Jednokratno',
            hitnost_procenat: 0, hitnost_naziv: 'Standardno (3-5 dana)',
        };

        document.getElementById('bk-result').style.display = 'none';
        ['bk-error-1', 'bk-error-2', 'bk-error-3'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) { el.style.display = 'none'; el.textContent = ''; }
        });
        document.getElementById('bk-send-status').className   = 'bk-send-status';
        document.getElementById('bk-send-status').textContent = '';
        emailInput.value = '';
        updateEmailState();
        next1.disabled = true;

        // Vrati na korak 1
        document.getElementById('bk-step-' + currentStep).style.display = 'none';
        document.getElementById('bk-step-1').style.display = 'block';
        currentStep = 1;
        updateProgress(1);

        wrapper.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    document.getElementById('bk-btn-reset').addEventListener('click', reset);
    document.getElementById('bk-btn-kontakt').addEventListener('click', function () {
        window.location.href = 'tel:+381600000000';
    });

    // ── Init ──────────────────────────────────────────────────
    updateProgress(1);

    // ── Pomoćne ───────────────────────────────────────────────
    function fmtRsd(n) {
        return Math.round(n).toLocaleString('sr-RS');
    }
    function escHtml(s) {
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }
});
