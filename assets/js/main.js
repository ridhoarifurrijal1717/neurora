/* ===== Neurora — interaksi & animasi UI ===== */
(() => {
  const $ = (s, c = document) => c.querySelector(s);
  const $$ = (s, c = document) => [...c.querySelectorAll(s)];

  /* Header + progress bar */
  const header = $('#siteHeader'), bar = $('#progress');
  const onScroll = () => {
    header.classList.toggle('scrolled', scrollY > 20);
    const h = document.documentElement.scrollHeight - innerHeight;
    bar.style.width = (h > 0 ? scrollY / h * 100 : 0) + '%';
  };
  addEventListener('scroll', onScroll, { passive: true }); onScroll();

  /* Mobile menu */
  const links = $('#navLinks');
  $('#menuBtn')?.addEventListener('click', () => { const open = links.classList.toggle('open'); $('#menuBtn').setAttribute('aria-expanded', String(open)); });
  $$('a', links).forEach(a => a.addEventListener('click', () => { links.classList.remove('open'); $('#menuBtn').setAttribute('aria-expanded', 'false'); }));

  /* Scroll reveal */
  const io = new IntersectionObserver(es => es.forEach(e => {
    if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); }
  }), { threshold: .12 });
  $$('.reveal').forEach(el => io.observe(el));

  /* Active nav on scroll */
  const sections = $$('main section[id]');
  const spy = new IntersectionObserver(es => es.forEach(e => {
    if (!e.isIntersecting) return;
    const id = e.target.id;
    const target = {home:'index.php#home',problem:'problem.php',solutions:'solution.php','impact-intro':'impact.php',about:'index.php#about'}[id];
    if (target) $$('.nav-links a, .bottom-nav a').forEach(a => a.classList.toggle('active', a.getAttribute('href') === target));
  }), { rootMargin: '-45% 0px -50% 0px' });
  sections.forEach(s => spy.observe(s));

  /* Counter + gauge */
  const ease = t => 1 - Math.pow(1 - t, 3);
  function count(el) {
    const to = parseFloat(el.dataset.to), dec = +(el.dataset.dec || 0), t0 = performance.now(), dur = 1800;
    (function step(now) {
      const p = Math.min((now - t0) / dur, 1);
      el.textContent = (to * ease(p)).toFixed(dec);
      if (p < 1) requestAnimationFrame(step);
    })(t0);
  }
  const impact = $('#impact');
  if (impact) new IntersectionObserver(([e], o) => {
    if (!e.isIntersecting) return;
    $$('.count', impact).forEach(count);
    $$('.gauge', impact).forEach(g => $('.g-fg', g).style.strokeDashoffset = 276.46 * (1 - g.dataset.value / 100));
    o.disconnect();
  }, { threshold: .35 }).observe(impact);

  /* 3D tilt hover */
  if (matchMedia('(hover:hover)').matches) {
    $$('.tilt').forEach(el => {
      el.addEventListener('mousemove', e => {
        const r = el.getBoundingClientRect(), x = (e.clientX - r.left) / r.width - .5, y = (e.clientY - r.top) / r.height - .5;
        el.style.transform = `perspective(700px) rotateX(${-y * 8}deg) rotateY(${x * 8}deg) translateY(-6px)`;
      });
      el.addEventListener('mouseleave', () => el.style.transform = '');
    });
  }

  /* Ripple pada tombol */
  $$('.btn').forEach(b => b.addEventListener('click', e => {
    const r = b.getBoundingClientRect(), d = Math.max(r.width, r.height);
    const s = document.createElement('span'); s.className = 'ripple';
    s.style.cssText = `width:${d}px;height:${d}px;left:${e.clientX - r.left - d / 2}px;top:${e.clientY - r.top - d / 2}px`;
    b.appendChild(s); setTimeout(() => s.remove(), 600);
  }));

  /* Parallax blob mengikuti mouse */
  const blobs = $$('.bg-blobs span');
  addEventListener('mousemove', e => {
    const x = e.clientX / innerWidth - .5, y = e.clientY / innerHeight - .5;
    blobs.forEach((b, i) => b.style.translate = `${x * (30 + i * 20)}px ${y * (30 + i * 20)}px`);
  }, { passive: true });

  /* Search (AJAX → api/search.php → katalog PHP) */
  const sm = $('#searchModal'), si = $('#searchInput'), sr = $('#searchResults');
  const openS = () => { sm.hidden = false; si.focus(); };
  const closeS = () => { sm.hidden = true; si.value = ''; sr.innerHTML = ''; };
  $('#openSearch')?.addEventListener('click', openS);
  $('#closeSearch')?.addEventListener('click', closeS);
  sm?.addEventListener('click', e => { if (e.target === sm) closeS(); });
  addEventListener('keydown', e => {
    if (e.key === 'Escape') { closeS(); }
    if (e.key === '/' && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA') { e.preventDefault(); openS(); }
  });
  let timer;
  si?.addEventListener('input', () => {
    clearTimeout(timer);
    timer = setTimeout(async () => {
      const q = si.value.trim();
      if (q.length < 2) { sr.innerHTML = ''; return; }
      try {
        const res = await (await fetch('api/search.php?q=' + encodeURIComponent(q))).json();
        sr.innerHTML = res.length
          ? res.map(r => `<li><a href="${r.url}"><b>${esc(r.title)}</b> <small>· ${r.type}<br>${esc(r.text)}</small></a></li>`).join('')
          : '<li style="padding:10px 14px">Tidak ada hasil.</li>';
        $$('a', sr).forEach(a => a.addEventListener('click', closeS));
      } catch { sr.innerHTML = '<li style="padding:10px 14px">Gagal mencari.</li>'; }
    }, 250);
  });
  const esc = s => String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

  /* Form kontak (AJAX → api/contact.php → INSERT) */
  const form = $('#contactForm'), st = $('#formStatus');
  form?.addEventListener('submit', async e => {
    e.preventDefault();
    st.className = 'form-status'; st.textContent = 'Mengirim…';
    try {
      const r = await fetch('api/contact.php', { method: 'POST', body: new FormData(form) });
      const j = await r.json();
      st.textContent = j.message; st.classList.add(j.ok ? 'ok' : 'err');
      if (j.ok) form.reset();
    } catch { st.textContent = 'Terjadi kesalahan jaringan.'; st.classList.add('err'); }
  });
})();


