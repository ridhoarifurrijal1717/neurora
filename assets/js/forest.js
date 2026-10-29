/* ===== Latar hutan: parallax (mouse + scroll) & partikel daun / kunang-kunang ===== */
(() => {
  const layers = [...document.querySelectorAll('.bg-layer')];
  const cv = document.getElementById('fx');
  if (!layers.length) return;
  const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* Parallax */
  let mx = 0, my = 0, cmx = 0, cmy = 0, cs = 0, ts = 0;
  const calcScroll = () => {
    const h = document.documentElement.scrollHeight - innerHeight;
    ts = h > 0 ? scrollY / h : 0;
  };
  addEventListener('scroll', calcScroll, { passive: true }); calcScroll();
  addEventListener('mousemove', e => { mx = e.clientX / innerWidth - .5; my = e.clientY / innerHeight - .5; }, { passive: true });

  /* Partikel */
  let ctx, W = 0, H = 0, leaves = [], flies = [];
  const rnd = (a, b) => a + Math.random() * (b - a);
  if (cv && !reduce) {
    ctx = cv.getContext('2d');
    const resize = () => {
      W = cv.width = innerWidth; H = cv.height = innerHeight;
      const k = Math.min(1, W / 1300);
      leaves = Array.from({ length: Math.round(22 * k) + 6 }, mkLeaf);
      flies = Array.from({ length: Math.round(45 * k) + 12 }, mkFly);
    };
    const mkLeaf = () => ({
      x: rnd(0, W), y: rnd(-H, H), s: rnd(7, 15), vy: rnd(.25, .7), vx: rnd(-.15, .35),
      r: rnd(0, 6.28), vr: rnd(-.015, .015), ph: rnd(0, 6.28), hue: rnd(135, 165), l: rnd(38, 55), a: rnd(.35, .75)
    });
    const mkFly = () => ({ x: rnd(0, W), y: rnd(H * .3, H), r: rnd(1.2, 3), vx: rnd(-.12, .12), vy: rnd(-.2, -.02), ph: rnd(0, 6.28), sp: rnd(.01, .03) });
    addEventListener('resize', resize); resize();
  }

  function drawLeaf(l) {
    ctx.save();
    ctx.translate(l.x, l.y); ctx.rotate(l.r);
    ctx.globalAlpha = l.a;
    ctx.fillStyle = `hsl(${l.hue},65%,${l.l}%)`;
    ctx.beginPath();
    ctx.moveTo(0, -l.s);
    ctx.bezierCurveTo(l.s * .9, -l.s * .4, l.s * .7, l.s * .6, 0, l.s);
    ctx.bezierCurveTo(-l.s * .7, l.s * .6, -l.s * .9, -l.s * .4, 0, -l.s);
    ctx.fill();
    ctx.strokeStyle = 'rgba(255,255,255,.45)'; ctx.lineWidth = .8;
    ctx.beginPath(); ctx.moveTo(0, -l.s); ctx.lineTo(0, l.s); ctx.stroke();
    ctx.restore();
  }

  let t = 0;
  (function loop() {
    requestAnimationFrame(loop);
    if (document.hidden) return;
    t++;

    cmx += (mx - cmx) * .05; cmy += (my - cmy) * .05; cs += (ts - cs) * .06;
    for (const el of layers) {
      const d = parseFloat(el.dataset.depth);
      el.style.setProperty('--tx', (-cmx * d * innerWidth * .2).toFixed(1) + 'px');
      el.style.setProperty('--ty', (-cs * d * innerHeight * .6 - cmy * d * innerHeight * .12).toFixed(1) + 'px');
    }

    if (!ctx) return;
    ctx.clearRect(0, 0, W, H);
    for (const l of leaves) {
      l.ph += .012; l.x += l.vx + Math.sin(l.ph) * .5; l.y += l.vy; l.r += l.vr;
      if (l.y > H + 20 || l.x > W + 30) { l.y = -20; l.x = rnd(-30, W); }
      drawLeaf(l);
    }
    for (const f of flies) {
      f.ph += f.sp; f.x += f.vx + Math.sin(f.ph) * .3; f.y += f.vy;
      if (f.y < -10) { f.y = H + 10; f.x = rnd(0, W); }
      const a = .35 + Math.sin(f.ph * 2) * .35;
      const g = ctx.createRadialGradient(f.x, f.y, 0, f.x, f.y, f.r * 5);
      g.addColorStop(0, `rgba(255,252,210,${a})`); g.addColorStop(.4, `rgba(190,255,220,${a * .45})`); g.addColorStop(1, 'rgba(190,255,220,0)');
      ctx.globalAlpha = 1; ctx.fillStyle = g;
      ctx.beginPath(); ctx.arc(f.x, f.y, f.r * 5, 0, 6.283); ctx.fill();
    }
  })();
})();
