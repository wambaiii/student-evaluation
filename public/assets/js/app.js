/* Banana University – all front-end behaviour.
   No inline scripts anywhere (the Content-Security-Policy forbids them), so every
   feature switches on through a data-* attribute in the HTML. */
(function () {
  'use strict';

  var $ = function (sel, root) { return (root || document).querySelector(sel); };
  var $$ = function (sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); };

  /* ---------- Mobile menu ---------- */
  var navToggle = $('[data-nav-toggle]');
  if (navToggle) {
    navToggle.addEventListener('click', function () { $('[data-nav]').classList.toggle('open'); });
  }

  /* ---------- Live clock in the footer ---------- */
  var clock = $('[data-live-clock]');
  if (clock) {
    var tick = function () { clock.textContent = new Date().toLocaleTimeString(); };
    tick();
    setInterval(tick, 1000);
  }

  /* ---------- Live counters on the home page ---------- */
  var statsBox = $('[data-live-stats]');
  if (statsBox) {
    var animateTo = function (el, value) {
      if (typeof value !== 'number') { el.textContent = '–'; return; }
      var isFloat = !Number.isInteger(value);
      var from = parseFloat(el.dataset.v || '0') || 0;
      var start = performance.now();
      var dur = 700;
      el.dataset.v = String(value);
      (function step(now) {
        var t = Math.min(1, (now - start) / dur);
        var cur = from + (value - from) * t;
        el.textContent = isFloat ? cur.toFixed(2) : String(Math.round(cur));
        if (t < 1) { requestAnimationFrame(step); }
      })(start);
    };
    var loadStats = function () {
      fetch(statsBox.dataset.url, { cache: 'no-store' })
        .then(function (r) { if (!r.ok) { throw new Error('bad'); } return r.json(); })
        .then(function (d) {
          ['students', 'evaluations', 'units', 'average'].forEach(function (k) {
            var el = $('[data-stat="' + k + '"]', statsBox);
            if (el) { animateTo(el, d[k]); }
          });
          var up = $('[data-stat="updated"]', statsBox);
          if (up) { up.textContent = 'last update ' + d.updated; }
        })
        .catch(function () {
          var up = $('[data-stat="updated"]', statsBox);
          if (up) { up.textContent = 'waiting for the server…'; }
        });
    };
    loadStats();
    setInterval(loadStats, 10000);
  }

  /* ---------- Live photo carousel (Matched local photos & captions) ---------- */
  var carousel = $('[data-carousel]');
  if (carousel) {
    var imgs = $$('[data-slide]', carousel);
    var caption = $('[data-caption]', carousel);
    var interval = parseInt(carousel.dataset.interval || '5000', 10);
    
    // Pair each specific location name with its corresponding local image file in public/img/
    var slidesData = [
      { src: '/img/lecture.jpg', name: 'Lecture hall' },
      { src: '/img/lab.jpg', name: 'Innovation lab' },
      { src: '/img/cafe.jpg', name: 'Café corner' },
      { src: '/img/gate.jpg', name: 'Main gate' },
    ];

    var front = 0, paused = false, timer = null, index = 0;

    var nextPhoto = function () {
      index = (index + 1) % slidesData.length;
      var slide = slidesData[index];
      var loader = new Image();
      loader.onload = function () {
        var back = 1 - front;
        imgs[back].src = loader.src;
        imgs[back].classList.add('show');
        imgs[front].classList.remove('show');
        front = back;
        if (caption) { caption.textContent = '📷 ' + slide.name + ' · ' + new Date().toLocaleTimeString(); }
      };
      loader.onerror = function () {
        if (caption) { caption.textContent = '📷 ' + slide.name + ' (Add image to public/img/)'; }
      };
      loader.src = slide.src;
    };

    var start = function () { timer = setInterval(function () { if (!paused) { nextPhoto(); } }, interval); };

    // Initialize first slide
    var firstSlide = slidesData[0];
    imgs[0].src = firstSlide.src;
    imgs[0].classList.add('show');
    if (caption) { caption.textContent = '📷 ' + firstSlide.name + ' · ' + new Date().toLocaleTimeString(); }

    start();
    var pauseBtn = $('[data-carousel-pause]', carousel);
    var nextBtn = $('[data-carousel-next]', carousel);
    if (pauseBtn) {
      pauseBtn.addEventListener('click', function () {
        paused = !paused;
        pauseBtn.textContent = paused ? '▶ Play' : '⏸ Pause';
      });
    }
    if (nextBtn) { nextBtn.addEventListener('click', nextPhoto); }
  }

  /* ---------- Webcam photo booth (registration page) ---------- */
  var booth = $('[data-webcam]');
  if (booth) {
    var video = $('[data-video]', booth), canvas = $('[data-canvas]', booth);
    var preview = $('[data-preview]', booth), empty = $('[data-empty]', booth);
    var btnStart = $('[data-start]', booth), btnSnap = $('[data-snap]', booth), btnRetake = $('[data-retake]', booth);
    var msg = $('[data-msg]', booth);
    var field = $('[data-avatar-field]');
    var stream = null;

    var stopCamera = function () {
      if (stream) { stream.getTracks().forEach(function (t) { t.stop(); }); stream = null; }
      video.hidden = true;
    };

    btnStart.addEventListener('click', function () {
      if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        msg.textContent = 'Camera not supported here (browsers need HTTPS or localhost).';
        return;
      }
      navigator.mediaDevices.getUserMedia({ video: { width: 640, height: 480 }, audio: false })
        .then(function (s) {
          stream = s;
          video.srcObject = s;
          video.hidden = false; empty.hidden = true; preview.hidden = true;
          btnStart.hidden = true; btnSnap.hidden = false;
          msg.textContent = '';
        })
        .catch(function () { msg.textContent = 'Could not open the camera. You can skip this step.'; });
    });

    btnSnap.addEventListener('click', function () {
      var ctx = canvas.getContext('2d');
      ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
      var data = canvas.toDataURL('image/jpeg', 0.85);
      field.value = data;
      preview.src = data;
      preview.hidden = false;
      stopCamera();
      btnSnap.hidden = true; btnRetake.hidden = false;
      msg.textContent = 'Looking good! 😄';
    });

    btnRetake.addEventListener('click', function () {
      field.value = '';
      preview.hidden = true;
      btnRetake.hidden = true; btnStart.hidden = false; empty.hidden = false;
      msg.textContent = '';
    });

    window.addEventListener('beforeunload', stopCamera);
  }

  /* ---------- Password strength meter ---------- */
  var pw = $('[data-password]');
  if (pw) {
    var meter = $('[data-strength]'), note = $('[data-strength-text]');
    var words = ['Too weak', 'Weak', 'Okay', 'Good', 'Strong 💪'];
    pw.addEventListener('input', function () {
      var v = pw.value, score = 0;
      if (v.length >= 10) { score++; }
      if (/[a-z]/.test(v) && /[A-Z]/.test(v)) { score++; }
      if (/\d/.test(v)) { score++; }
      if (/[^A-Za-z0-9]/.test(v) || v.length >= 14) { score++; }
      meter.value = score;
      note.textContent = v ? words[score] : 'At least 10 characters, with a letter and a number.';
    });
  }

  /* ---------- Star ratings: show a word next to the stars ---------- */
  var starWords = { 1: 'Poor 😕', 2: 'Fair 🙂', 3: 'Good 😊', 4: 'Great 😄', 5: 'Excellent 🤩' };
  $$('[data-stars]').forEach(function (group) {
    var word = $('[data-star-word]', group);
    var update = function () {
      var checked = $('input:checked', group);
      word.textContent = checked ? starWords[checked.value] : '';
    };
    group.addEventListener('change', update);
    update();
  });

  /* ---------- Comment character counter ---------- */
  var comment = $('[data-comment]');
  if (comment) {
    var count = $('[data-count]');
    var upd = function () { count.textContent = String(comment.value.length); };
    comment.addEventListener('input', upd);
    upd();
  }

  /* ---------- QR code poster ---------- */
  var qrBox = $('[data-qr]');
  if (qrBox && window.QRCode) {
    new QRCode(qrBox, {
      text: qrBox.dataset.qrText,
      width: 260, height: 260,
      colorDark: '#1E1B4B', colorLight: '#ffffff',
      correctLevel: QRCode.CorrectLevel.H
    });
    var dl = $('[data-qr-download]');
    if (dl) {
      dl.addEventListener('click', function () {
        var c = $('canvas', qrBox);
        if (!c) { return; }
        var a = document.createElement('a');
        a.href = c.toDataURL('image/png');
        a.download = 'unit-evaluation-qr.png';
        a.click();
      });
    }
  }
  var printBtn = $('[data-print]');
  if (printBtn) { printBtn.addEventListener('click', function () { window.print(); }); }

  /* ---------- Confetti after a successful submission ---------- */
  if (document.body.dataset.confetti) {
    var cv = document.createElement('canvas');
    cv.className = 'confetti';
    document.body.appendChild(cv);
    var cx = cv.getContext('2d');
    var W = cv.width = window.innerWidth, H = cv.height = window.innerHeight;
    var colors = ['#FFD93D', '#FF6B9D', '#6C5CE7', '#00CEC9', '#FF9F43'];
    var bits = [];
    for (var i = 0; i < 160; i++) {
      bits.push({ x: Math.random() * W, y: -Math.random() * H, r: 4 + Math.random() * 6,
                  c: colors[i % colors.length], vy: 2 + Math.random() * 4, vx: -1 + Math.random() * 2 });
    }
    var frames = 0;
    (function draw() {
      cx.clearRect(0, 0, W, H);
      bits.forEach(function (b) {
        b.x += b.vx; b.y += b.vy;
        cx.fillStyle = b.c;
        cx.fillRect(b.x, b.y, b.r, b.r * 0.6);
      });
      frames++;
      if (frames < 260) { requestAnimationFrame(draw); } else { cv.remove(); }
    })();
  }

  /* ---------- Admin dashboard (live charts) ---------- */
  var dash = $('[data-dashboard]');
  if (dash && window.Chart) {
    var palette = ['#6C5CE7', '#FF6B9D', '#00CEC9', '#FF9F43', '#FFD93D', '#54A0FF', '#5F27CD', '#10AC84'];
    var charts = {};
    var setText = function (name, value) {
      var el = $('[data-d="' + name + '"]', dash);
      if (el) { el.textContent = (value === null || value === undefined) ? '–' : String(value); }
    };

    var makeCharts = function () {
      charts.units = new Chart($('[data-chart="units"]', dash), {
        type: 'bar',
        data: { labels: [], datasets: [{ label: 'Average (1–5)', data: [], backgroundColor: palette, borderRadius: 8 }] },
        options: { maintainAspectRatio: false, scales: { y: { min: 0, max: 5 } }, plugins: { legend: { display: false } } }
      });
      charts.dist = new Chart($('[data-chart="dist"]', dash), {
        type: 'doughnut',
        data: { labels: ['1 ★', '2 ★', '3 ★', '4 ★', '5 ★'], datasets: [{ data: [0, 0, 0, 0, 0],
                backgroundColor: ['#FF6B6B', '#FF9F43', '#FFD93D', '#00CEC9', '#6C5CE7'] }] },
        options: { maintainAspectRatio: false }
      });
      charts.questions = new Chart($('[data-chart="questions"]', dash), {
        type: 'radar',
        data: { labels: ['Teaching', 'Clarity', 'Engagement', 'Punctuality', 'Materials'],
                datasets: [{ label: 'Average', data: [0, 0, 0, 0, 0], backgroundColor: 'rgba(108,92,231,.25)',
                             borderColor: '#6C5CE7', pointBackgroundColor: '#FF6B9D' }] },
        options: { maintainAspectRatio: false, scales: { r: { min: 0, max: 5 } }, plugins: { legend: { display: false } } }
      });
    };
    makeCharts();

    var refresh = function () {
      fetch(dash.dataset.url, { cache: 'no-store', credentials: 'same-origin' })
        .then(function (r) { if (!r.ok) { throw new Error('bad'); } return r.json(); })
        .then(function (d) {
          setText('students', d.totals.students);
          setText('evaluations', d.totals.evaluations);
          setText('units', d.totals.units);
          setText('average', d.totals.average);
          setText('updated', d.updated);

          charts.units.data.labels = d.per_unit.map(function (u) { return u.code; });
          charts.units.data.datasets[0].data = d.per_unit.map(function (u) { return u.average || 0; });
          charts.units.update();
          charts.dist.data.datasets[0].data = d.distribution;
          charts.dist.update();
          var q = d.questions || {};
          charts.questions.data.datasets[0].data = [q.teaching_quality, q.clarity, q.engagement, q.punctuality, q.materials].map(function (v) { return v || 0; });
          charts.questions.update();

          // Table (built with textContent so comments/names can never inject HTML)
          var tbody = $('[data-d-units]', dash);
          tbody.textContent = '';
          d.per_unit.forEach(function (u) {
            var tr = document.createElement('tr');
            [u.code, u.title, u.lecturer, String(u.responses), u.average === null ? '–' : u.average.toFixed(2)].forEach(function (t) {
              var td = document.createElement('td'); td.textContent = t; tr.appendChild(td);
            });
            var tdq = document.createElement('td');
            var a = document.createElement('a');
            a.className = 'btn btn-small';
            a.href = dash.dataset.qrBase + '?unit=' + encodeURIComponent(u.id);
            a.textContent = 'QR';
            tdq.appendChild(a); tr.appendChild(tdq);
            tbody.appendChild(tr);
          });

          var list = $('[data-d-comments]', dash);
          list.textContent = '';
          if (!d.comments.length) {
            var li0 = document.createElement('li'); li0.textContent = 'No comments yet.'; list.appendChild(li0);
          }
          d.comments.forEach(function (c) {
            var li = document.createElement('li');
            var tag = document.createElement('span'); tag.className = 'code'; tag.textContent = c.code;
            var txt = document.createElement('p'); txt.textContent = c.comment;
            var when = document.createElement('small'); when.textContent = c.submitted_on;
            li.appendChild(tag); li.appendChild(txt); li.appendChild(when);
            list.appendChild(li);
          });
        })
        .catch(function () { setText('updated', 'connection problem'); });
    };
    refresh();
    setInterval(refresh, 8000);
  }
})();