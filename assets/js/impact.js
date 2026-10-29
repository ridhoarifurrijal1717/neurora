(() => {
  /* ── Kalkulator skenario ── */
  const $ = id => document.getElementById(id);
  const calculator = $('impactCalculator');
  if (!calculator) return;

  const form = $('impactForm');
  const inputs = [$('impactBaseline'), $('impactTarget'), $('impactDays')];
  const initial = inputs.map(i => i.value);
  const daily = calculator.dataset.daily === '1';
  const unit  = calculator.dataset.unit;
  const num   = v => Number(v).toLocaleString('id-ID', { maximumFractionDigits: 2 });

  function calculate() {
    if (!form.checkValidity()) {
      $('impactError').textContent = 'Isi nilai awal > 0 sampai 1.000.000, target 0–100%, dan periode 1–365 hari.';
      $('impactNumbers').hidden = true;
      $('impactFormula').textContent = 'Lengkapi angka yang valid untuk menghitung skenario.';
      return;
    }
    const [baseline, target, days] = inputs.map(i => Number(i.value));
    const total   = baseline * (daily ? days : 1);
    const benefit = total * target / 100;
    const lowT    = Math.max(0, target * .8);
    const highT   = Math.min(100, target * 1.2);
    $('impactError').textContent = '';
    $('impactNumbers').hidden = false;
    $('impactBenefit').textContent    = num(benefit);
    $('impactPeriod').textContent     = daily ? `dalam ${days} hari` : `target pada hari ke-${days}`;
    $('impactTotal').textContent      = num(total) + ' ' + unit;
    $('impactRemaining').textContent  = num(total - benefit) + ' ' + unit;
    $('scenarioBar').style.width      = target + '%';
    $('impactRange').textContent      = `${num(total * lowT / 100)}–${num(total * highT / 100)} ${unit} (target ${num(lowT)}–${num(highT)}%)`;
    $('impactFormula').textContent    = `${num(baseline)} ${unit}${daily ? '/hari × ' + days + ' hari' : ''} × ${num(target)}% = ${num(benefit)} ${unit}.`;

    // update chart if available
    if (window._impactChart) {
      const d = window._impactData;
      const wSum = d.weights.reduce((a, b) => a + b, 0);
      let cum = 0;
      const chartData = d.weights.map(w => {
        cum += w / wSum * target;
        return +(total * cum / 100).toFixed(2);
      });
      window._impactChart.data.datasets[0].data = chartData;
      window._impactChart.update('active');
    }
  }

  form.addEventListener('submit', e => e.preventDefault());
  form.addEventListener('input', calculate);
  $('resetImpact').addEventListener('click', () => {
    inputs.forEach((inp, i) => inp.value = initial[i]);
    calculate();
  });
  calculate();

  /* ── Chart.js: proyeksi per langkah ── */
  const chartCanvas = $('impactChart');
  const dataEl      = $('impactData');
  if (!chartCanvas || !dataEl) return;

  const d = JSON.parse(dataEl.textContent);
  window._impactData = d;

  const SCRIPT_SRC = 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js';
  const script = document.createElement('script');
  script.src = SCRIPT_SRC;
  script.onload = () => buildChart(d);
  document.head.appendChild(script);

  function buildChart(d) {
    const total  = d.baseline * (d.daily ? d.days : 1);
    const wSum   = d.weights.reduce((a, b) => a + b, 0);
    let cum = 0;
    const vals = d.weights.map(w => {
      cum += w / wSum * d.target;
      return +(total * cum / 100).toFixed(2);
    });

    const labels = d.steps.map((s, i) => `Langkah ${i + 1}: ${s.length > 22 ? s.slice(0, 22) + '…' : s}`);

    window._impactChart = new Chart(chartCanvas, {
      type: 'bar',
      data: {
        labels,
        datasets: [
          {
            label: `Kumulatif (${d.unit})`,
            data: vals,
            backgroundColor: vals.map((_, i) => `rgba(16,185,129,${0.35 + i / vals.length * 0.55})`),
            borderColor: 'rgba(7,93,66,.6)',
            borderWidth: 1.5,
            borderRadius: 8,
            borderSkipped: false,
          },
          {
            label: `Target total (${d.unit})`,
            data: Array(vals.length).fill(+(total * d.target / 100).toFixed(2)),
            type: 'line',
            borderColor: 'rgba(7,93,66,.45)',
            borderWidth: 1.5,
            borderDash: [5, 4],
            pointRadius: 0,
            tension: 0,
            fill: false,
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
          legend: {
            labels: {
              font: { family: "'Plus Jakarta Sans', sans-serif", size: 12 },
              color: '#24473e',
              boxWidth: 14,
              padding: 18,
            }
          },
          tooltip: {
            backgroundColor: 'rgba(10,83,60,.93)',
            titleFont: { family: "'Plus Jakarta Sans', sans-serif", size: 13, weight: '700' },
            bodyFont:  { family: "'Inter', sans-serif", size: 12 },
            padding: 12,
            cornerRadius: 10,
            callbacks: {
              label: ctx => ` ${ctx.dataset.label}: ${Number(ctx.parsed.y).toLocaleString('id-ID', { maximumFractionDigits: 2 })} ${d.unit}`,
            }
          }
        },
        scales: {
          x: {
            grid: { color: 'rgba(0,0,0,.04)' },
            ticks: {
              font: { family: "'Inter', sans-serif", size: 11 },
              color: '#4c665b',
              maxRotation: 25,
            }
          },
          y: {
            grid: { color: 'rgba(0,0,0,.06)' },
            ticks: {
              font: { family: "'Inter', sans-serif", size: 11 },
              color: '#4c665b',
              callback: v => Number(v).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' ' + d.unit,
            },
            beginAtZero: true,
          }
        }
      }
    });
  }

  /* ── Animasi step bars ── */
  const bars = document.querySelectorAll('.step-bar-fill');
  if (bars.length && 'IntersectionObserver' in window) {
    const obs = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.style.transform = 'scaleX(1)';
          obs.unobserve(entry.target);
        }
      });
    }, { threshold: 0.2 });
    bars.forEach(b => { b.style.transform = 'scaleX(0)'; obs.observe(b); });
  } else {
    bars.forEach(b => b.style.transform = 'scaleX(1)');
  }
})();
