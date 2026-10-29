/* ===== Bumi 3D (Three.js) =====
 * - Berputar pelan otomatis
 * - Seret mouse / sentuh untuk memutar manual (OrbitControls)
 * - Scroll halaman ikut memutar bumi
 * - Ctrl + scroll atau tombol +/- untuk zoom
 */
(() => {
  const el = document.getElementById('globe');
  if (!el || !window.THREE) return;

  const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
  const scene = new THREE.Scene();
  const camera = new THREE.PerspectiveCamera(38, 1, 0.1, 100);
  const START_Z = 7.8; camera.position.set(0, 0.4, START_Z); camera.position.setLength(START_Z);

  const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
  renderer.setPixelRatio(Math.min(devicePixelRatio, 2));
  el.appendChild(renderer.domElement);

  /* Cahaya */
  scene.add(new THREE.AmbientLight(0xffffff, 0.9));
  const sun = new THREE.DirectionalLight(0xffffff, 1.1); sun.position.set(5, 2, 5); scene.add(sun);

  const earth = new THREE.Group(); scene.add(earth);

  /* Bumi */
  const loader = new THREE.TextureLoader(); loader.setCrossOrigin('anonymous');
  const earthMat = new THREE.MeshPhongMaterial({ color: 0x2f7fc0, shininess: 14, specular: new THREE.Color(0x335566) });
  const TEX = [
    'https://cdn.jsdelivr.net/gh/mrdoob/three.js@r128/examples/textures/planets/earth_atmos_2048.jpg',
    'https://cdn.jsdelivr.net/npm/three-globe/example/img/earth-blue-marble.jpg'
  ];
  (function tryLoad(i) {
    if (i >= TEX.length) return;
    loader.load(TEX[i], t => { earthMat.map = t; earthMat.color.set(0xffffff); earthMat.needsUpdate = true; }, undefined, () => tryLoad(i + 1));
  })(0);
  const sphere = new THREE.Mesh(new THREE.SphereGeometry(1.5, 64, 64), earthMat);
  earth.add(sphere);

  /* Awan */
  const cloudMat = new THREE.MeshPhongMaterial({ transparent: true, opacity: 0.0, depthWrite: false });
  loader.load('https://cdn.jsdelivr.net/gh/mrdoob/three.js@r128/examples/textures/planets/earth_clouds_1024.png',
    t => { cloudMat.map = t; cloudMat.opacity = 0.45; cloudMat.needsUpdate = true; });
  const clouds = new THREE.Mesh(new THREE.SphereGeometry(1.53, 64, 64), cloudMat);
  earth.add(clouds);

  /* Pinggiran bumi: cangkang KACA PLATINUM (bening di tengah, tepi perak berkilau) + halo tipis */
  const VERT = 'varying vec3 vN;void main(){vN=normalize(normalMatrix*normal);gl_Position=projectionMatrix*modelViewMatrix*vec4(position,1.0);}';

  const glass = new THREE.Mesh(
    new THREE.SphereGeometry(1.52, 96, 96),
    new THREE.ShaderMaterial({
      vertexShader: VERT,
      fragmentShader: `
        varying vec3 vN;
        void main(){
          vec3 n = normalize(vN);
          float d = abs(dot(n, vec3(0.,0.,1.)));
          float f = pow(1.0 - d, 2.3);                         // fresnel: kuat di tepi
          vec3 plat = vec3(0.91, 0.94, 0.96);                  // platinum silver
          vec3 tint = vec3(0.74, 0.90, 0.87);                  // sentuhan mint tipis
          vec3 col = mix(plat, tint, f * 0.55);
          float spec = pow(max(dot(n, normalize(vec3(-0.55, 0.62, 0.56))), 0.0), 22.0);   // kilau kaca
          float edge = smoothstep(0.86, 1.0, 1.0 - d);                                     // garis tepi tipis
          float a = 0.035 + f * 0.62 + spec * 0.35 + edge * 0.28;
          gl_FragColor = vec4(col + spec * 0.45 + edge * 0.1, clamp(a, 0.0, 0.92));
        }`,
      transparent: true, depthWrite: false
    })
  );
  earth.add(glass);

  const atmo = new THREE.Mesh(
    new THREE.SphereGeometry(1.8, 64, 64),
    new THREE.ShaderMaterial({
      vertexShader: VERT,
      fragmentShader: `
        varying vec3 vN;
        void main(){
          float d = abs(dot(normalize(vN), vec3(0.,0.,1.)));
          float a = pow(smoothstep(0.0, 0.54, d), 1.6) * 0.30;   // halo lembut memudar ke luar
          gl_FragColor = vec4(vec3(0.80, 0.94, 0.91), a);
        }`,
      side: THREE.BackSide, transparent: true, depthWrite: false
    })
  );
  scene.add(atmo);

  /* Cincin orbit SILVER bening (inti perak + lapisan kaca + node bercahaya) */
  const glowTex = (() => {
    const c = document.createElement('canvas'); c.width = c.height = 64;
    const x = c.getContext('2d'), g = x.createRadialGradient(32, 32, 0, 32, 32, 32);
    g.addColorStop(0, 'rgba(255,255,255,1)'); g.addColorStop(.25, 'rgba(226,232,240,.85)'); g.addColorStop(1, 'rgba(203,213,225,0)');
    x.fillStyle = g; x.fillRect(0, 0, 64, 64);
    return new THREE.CanvasTexture(c);
  })();
  const rings = [];
  [[1.95, 1.15, 0.3], [2.12, 0.5, -0.5], [2.3, 1.6, 0.9]].forEach(([r, rx, rz], i) => {
    const g = new THREE.Group();
    const mk = (tube, color, op) => new THREE.Mesh(new THREE.TorusGeometry(r, tube, 12, 220),
      new THREE.MeshBasicMaterial({ color, transparent: true, opacity: op, depthWrite: false }));
    const core = mk(0.007, 0xa9b7c6, 0.9);    // inti perak
    const mid  = mk(0.02, 0xdfe7ee, 0.5);     // lapisan kaca bening
    const halo = mk(0.055, 0xffffff, 0.22);   // pendar putih lembut
    const shine = mk(0.0028, 0xffffff, 0.95); // garis kilau tipis di atas inti
    g.add(halo, mid, core, shine);
    const nodes = [];
    for (let k = 0; k < 2; k++) {
      const s = new THREE.Sprite(new THREE.SpriteMaterial({ map: glowTex, transparent: true, depthWrite: false }));
      s.scale.set(0.42, 0.42, 1);
      const dot = new THREE.Mesh(new THREE.SphereGeometry(0.036, 16, 16), new THREE.MeshBasicMaterial({ color: 0xffffff }));
      g.add(s, dot); nodes.push({ s, dot, off: k * Math.PI });
    }
    g.rotation.set(rx, 0, rz);
    g.userData = { nodes, r, speed: 0.4 + i * 0.25, a: i * 2, mats: [core.material, mid.material, halo.material, shine.material], base: [0.9, 0.5, 0.22, 0.95], ph: i * 1.7 };
    scene.add(g); rings.push(g);
  });

  /* Partikel bintang */
  const pg = new THREE.BufferGeometry(), N = 220, pos = new Float32Array(N * 3);
  for (let i = 0; i < N; i++) {
    const r = 2.6 + Math.random() * 1.2, t = Math.random() * 6.283, p = Math.acos(2 * Math.random() - 1);
    pos.set([r * Math.sin(p) * Math.cos(t), r * Math.cos(p), r * Math.sin(p) * Math.sin(t)], i * 3);
  }
  pg.setAttribute('position', new THREE.BufferAttribute(pos, 3));
  const stars = new THREE.Points(pg, new THREE.PointsMaterial({ color: 0xcbd5e1, size: 0.03, transparent: true, opacity: 0.7 }));
  scene.add(stars);

  /* Kontrol manual */
  const controls = new THREE.OrbitControls(camera, renderer.domElement);
  controls.enablePan = false; controls.enableZoom = false;
  controls.enableDamping = true; controls.dampingFactor = 0.07;
  controls.rotateSpeed = 0.7;
  controls.minPolarAngle = Math.PI * 0.2; controls.maxPolarAngle = Math.PI * 0.8;
  renderer.domElement.style.touchAction = 'pan-y';          // geser vertikal tetap scroll halaman

  controls.saveState();
  let auto = !reduce, resumeT;
  controls.autoRotate = auto; controls.autoRotateSpeed = 0.6;   // putar perlahan
  controls.addEventListener('start', () => { controls.autoRotate = false; clearTimeout(resumeT); });
  controls.addEventListener('end', () => { if (auto) resumeT = setTimeout(() => controls.autoRotate = true, 2200); });

  /* Zoom */
  let targetZ = START_Z;
  const clampZ = z => Math.min(10, Math.max(5.2, z));
  el.addEventListener('wheel', e => { if (e.ctrlKey) { e.preventDefault(); targetZ = clampZ(targetZ + e.deltaY * 0.01); } }, { passive: false });
  const $ = id => document.getElementById(id);
  $('gZoomIn')?.addEventListener('click', () => targetZ = clampZ(targetZ - 0.8));
  $('gZoomOut')?.addEventListener('click', () => targetZ = clampZ(targetZ + 0.8));
  $('gReset')?.addEventListener('click', () => { targetZ = START_Z; controls.reset(); });
  $('gToggle')?.addEventListener('click', e => {
    auto = !auto; controls.autoRotate = auto;
    e.currentTarget.style.background = auto ? '' : '#fff';
    e.currentTarget.style.opacity = auto ? 1 : .6;
  });

  /* Scroll halaman → bumi ikut berputar */
  let scrollRot = 0, scrollTarget = 0;
  addEventListener('scroll', () => scrollTarget = scrollY * 0.0035, { passive: true });

  /* Responsif + hemat baterai */
  const resize = () => {
    const w = el.clientWidth, h = el.clientHeight;
    renderer.setSize(w, h, false); camera.aspect = w / h; camera.updateProjectionMatrix();
  };
  new ResizeObserver(resize).observe(el); resize();

  let visible = true;
  new IntersectionObserver(([e]) => visible = e.isIntersecting).observe(el);

  const clock = new THREE.Clock();
  (function loop() {
    requestAnimationFrame(loop);
    if (!visible) return;
    const dt = clock.getDelta();
    scrollRot += (scrollTarget - scrollRot) * 0.06;
    earth.rotation.y = scrollRot;
    clouds.rotation.y += dt * 0.02;
    stars.rotation.y += dt * 0.01;
    const tt = clock.elapsedTime;
    rings.forEach(g => {
      const d = g.userData; d.a += dt * d.speed;
      d.nodes.forEach(n => { const x = Math.cos(d.a + n.off) * d.r, y = Math.sin(d.a + n.off) * d.r; n.s.position.set(x, y, 0); n.dot.position.set(x, y, 0); });
      const pulse = 0.8 + 0.2 * Math.sin(tt * 2.2 + d.ph);              // berkilau halus
      d.mats.forEach((m, k) => m.opacity = d.base[k] * pulse);
    });
    const dist = camera.position.length();
    camera.position.multiplyScalar((dist + (targetZ - dist) * 0.08) / dist);   // zoom halus
    controls.update();
    renderer.render(scene, camera);
  })();
})();
