(() => {
  const $ = id => document.getElementById(id);
  const form = $('solutionForm');
  if (!form) return;
  const result = $('solutionResult'), status = $('solutionStatus');
  const generate = $('generateBtn'), example = $('exampleBtn');
  const chatForm = $('chatForm'), question = $('chatQuestion'), chatStatus = $('chatStatus');
  let conversation = JSON.parse($('initialConversation').textContent);
  let currentRecord = JSON.parse($('initialSolution').textContent);
  let referencePlanId = currentRecord?.mode === 'ai' ? currentRecord.id : '';
  let busy = null, freshChat = false;

  // Problem data dari server
  const allProblems = JSON.parse($('allProblems').textContent);
  let currentProblemId = JSON.parse($('initialProblemId').textContent) || '';

  const text = (tag, value, className) => {
    const element = document.createElement(tag);
    element.textContent = value;
    if (className) element.className = className;
    return element;
  };
  const scrollTo = element => element.scrollIntoView({ behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'start' });

  // Update form saat problem berubah via dropdown (hanya muncul jika belum ada problem terpilih di PHP)
  const problemSelect = $('problemSelect');
  const problemField = $('problemField');
  if (problemSelect) {
    problemSelect.addEventListener('change', () => {
      const pid = problemSelect.value;
      currentProblemId = pid;
      problemField.value = pid;
      const p = allProblems[pid];

      // Update label & placeholder form
      if (p) {
        $('baselineLabel').firstChild.textContent = p.baseline_label + ' (' + p.unit + ')';
        $('baselineInput').value = p.baseline;
        form.elements.target.value = p.target;
        form.elements.days.value = 30;
        // Isi complaint contoh kalau masih kosong / belum diubah user
        const complaintEl = $('complaint');
        if (!complaintEl.dataset.edited) complaintEl.value = p.prompt || '';
      } else {
        $('baselineLabel').firstChild.textContent = 'Kondisi awal';
        $('baselineInput').value = 10;
        form.elements.target.value = 30;
        form.elements.days.value = 30;
      }

      // Update topik chat
      const chatHeading = $('chatHeading');
      const chatTopic = $('chatTopic');
      if (p) {
        chatHeading.textContent = 'Obrolan tentang ' + p.category.toLowerCase();
        chatTopic.innerHTML = '<svg class="ico" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"/></svg> ' + e(p.category) + ' ';
      } else {
        chatHeading.textContent = 'Obrolan';
        chatTopic.innerHTML = '<svg class="ico" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 3 2.5 6.5L21 12l-6.5 2.5L12 21l-2.5-6.5L3 12l6.5-2.5z"/></svg> Ekosistem umum ';
      }
      const countSpan = document.createElement('span');
      countSpan.id = 'chatCount';
      countSpan.textContent = (conversation?.turn || 0) + ' / 12 tanya jawab';
      chatTopic.appendChild(countSpan);

      // Reset conversation jika problem berubah
      if (conversation && conversation.problem !== pid) {
        conversation = null; currentRecord = null; referencePlanId = ''; freshChat = true;
        result.hidden = true; renderChat(); setLocation();
        chatStatus.className = 'form-status';
        chatStatus.textContent = 'Kategori berubah. Chat baru dimulai.';
      }

      // Tampilkan/sembunyikan link detail problem
      const link = $('problemSelectLink');
      if (link) link.style.display = pid ? 'inline-flex' : 'none';

      setLocation();
    });
  }

  // Tandai complaint sebagai "sudah diedit user"
  const complaintEl = $('complaint');
  if (complaintEl) {
    complaintEl.addEventListener('input', () => { complaintEl.dataset.edited = '1'; });
  }

  function e(str) { return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

  function setLocation(record = null) {
    const url = new URL(location.href);
    const params = record ? { result: record.id } : {};
    if (!record && currentProblemId) params.problem = currentProblemId;
    if (!record && conversation) params.conversation = conversation.id;
    if (!record && !conversation && freshChat) params.new = '1';
    url.search = new URLSearchParams(params).toString();
    history.replaceState(null, '', url);
  }

  function updateMeta() {
    const turn = conversation?.turn || 0;
    const countEl = $('chatCount');
    if (countEl) countEl.textContent = `${turn} / 12 tanya jawab`;
    $('chatSuggestions').hidden = turn > 0;
    $('chatWelcome').hidden = turn > 0;
    $('chatNext').hidden = turn === 0;
    $('sendChatBtn').disabled = Boolean(busy) || turn >= 12;
    generate.textContent = busy === 'plan' ? 'Menyusun rencana…' : turn > 0 ? 'Buat rencana dari chat' : 'Buat solusi AI';
    $('planIntro').textContent = turn > 0
      ? 'Rencana akan memakai riwayat chat ini. Lengkapi ringkasan dan asumsi dampak, lalu buat rencana dari percakapanmu.'
      : 'Gunakan percakapanmu atau isi keluhan langsung. Lengkapi asumsi untuk menyusun rencana tindakan dan simulasi dampaknya.';
  }

  function lock(kind) {
    busy = kind;
    for (const button of [generate, example, $('sendChatBtn'), $('newChatBtn'), $('useChatBtn'), ...document.querySelectorAll('.question-chip')]) if (button) button.disabled = Boolean(kind);
    for (const field of [...form.querySelectorAll('input, textarea'), question]) field.readOnly = Boolean(kind);
    if (kind === 'plan') form.setAttribute('aria-busy', 'true'); else form.removeAttribute('aria-busy');
    if (kind === 'chat') chatForm.setAttribute('aria-busy', 'true'); else chatForm.removeAttribute('aria-busy');
    updateMeta();
  }

  function list(id, items) {
    $(id).replaceChildren(...items.map(item => text('li', item)));
  }

  function renderPlan(record) {
    currentRecord = record;
    if (record.mode === 'ai') referencePlanId = record.id;
    const data = record.solution;
    $('resultMode').textContent = record.mode === 'ai' ? 'SOLUSI DIBUAT OLEH AI · PERLU DIVERIFIKASI' : 'CONTOH STATIS · BUKAN JAWABAN AI';
    $('resultSummary').textContent = data.summary;
    $('resultHypothesis').textContent = data.impact_hypothesis;
    $('resultCaution').textContent = data.caution;
    list('resultCauses', data.possible_causes);
    list('resultMeasurements', data.measurement_plan);
    $('resultSteps').replaceChildren(...data.steps.map((step, index) => {
      const li = document.createElement('li');
      li.append(text('span', String(index + 1).padStart(2, '0'), 'step-number'));
      const body = document.createElement('div');
      body.append(text('small', step.timing), text('h3', step.title), text('p', step.action));
      li.append(body);
      return li;
    }));
    $('impactLink').href = 'impact.php?solution=' + encodeURIComponent(record.id);
    result.hidden = false;
  }

  function messageNode(message, pending = false) {
    const node = document.createElement('article');
    const isUser = message.role === 'user';
    node.className = 'chat-message ' + (isUser ? 'from-user' : 'from-ai') + (pending ? ' pending' : '');
    node.append(text('small', isUser ? pending ? 'Kamu · sedang dikirim' : 'Kamu' : 'Neurora AI'), text('p', message.content));
    return node;
  }

  function renderChat() {
    const messages = conversation?.messages || [];
    const log = $('chatMessages');
    const same = log.dataset.conversation === conversation?.id;
    const existing = same ? log.children.length : 0;
    if (!same || existing > messages.length) log.replaceChildren();
    const offset = same && existing <= messages.length ? existing : 0;
    log.append(...messages.slice(offset).map(message => messageNode(message)));
    log.dataset.conversation = conversation?.id || '';
    $('chatScroll').scrollTop = $('chatScroll').scrollHeight;
    updateMeta();
  }

  async function post(url, data) {
    const controller = new AbortController();
    const waitMs = Number(chatForm.dataset.timeoutMs) || 270000;
    const timeout = setTimeout(() => controller.abort(), waitMs);
    try {
      const response = await fetch(url, {
        method: 'POST', credentials: 'same-origin', signal: controller.signal,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ...data, csrf: document.querySelector('meta[name="csrf"]').content })
      });
      let json;
      try { json = await response.json(); } catch { throw new Error('Jawaban server belum bisa dibaca. Coba lagi dalam beberapa saat.'); }
      if (!response.ok || !json.ok) throw new Error(json.message || 'Permintaan belum berhasil. Coba lagi.');
      return json;
    } finally { clearTimeout(timeout); }
  }

  function errorMessage(error) {
    return error.name === 'AbortError' ? 'Waktu tunggu habis. Pesan belum terkonfirmasi; muat ulang untuk memeriksa riwayat sebelum mengirim ulang.' : error.message;
  }

  async function sendQuestion(event) {
    event.preventDefault();
    if (busy || (conversation?.turn || 0) >= 12 || !chatForm.reportValidity()) return;
    const message = question.value.trim();
    if (!message) { chatStatus.textContent = 'Tulis pertanyaan terlebih dahulu.'; question.focus(); return; }
    lock('chat');
    const pending = messageNode({ role: 'user', content: message }, true);
    $('chatMessages').append(pending);
    $('chatScroll').scrollTop = $('chatScroll').scrollHeight;
    chatStatus.className = 'form-status';
    chatStatus.textContent = 'AI sedang menyiapkan jawaban…';
    try {
      const data = await post('api/chat.php', {
        problem: currentProblemId, message,
        conversation: conversation?.id || '', turn: conversation?.turn || 0,
        context: $('context').value, solution_id: referencePlanId || undefined
      });
      pending.remove();
      conversation = data.conversation;
      freshChat = false;
      renderChat();
      question.value = '';
      updateCounter();
      chatStatus.className = 'form-status ok';
      chatStatus.textContent = conversation.turn >= 12 ? 'Jawaban siap. Batas chat ini tercapai; kamu bisa membuat rencana atau memulai chat baru.' : 'Jawaban siap. Kamu bisa bertanya lagi atau meminta solusi yang lebih spesifik.';
      if (!result.hidden) {
        result.hidden = true;
        currentRecord = null;
        status.className = 'form-status';
        status.textContent = 'Percakapan bertambah. Buat rencana kembali untuk memakai konteks terbaru sebelum melihat dampaknya.';
      }
      setLocation();
    } catch (error) {
      pending.remove();
      chatStatus.className = 'form-status err';
      chatStatus.textContent = errorMessage(error);
    } finally { lock(null); question.focus(); }
  }

  async function submit(mode) {
    if (busy || !form.reportValidity()) return;
    lock('plan');
    result.hidden = true;
    const data = { ...Object.fromEntries(new FormData(form)), mode };
    if (mode === 'ai' && conversation) { data.conversation = conversation.id; data.turn = conversation.turn; }
    status.className = 'form-status';
    status.textContent = mode === 'ai' ? 'AI sedang menyusun rencana solusi…' : 'Menyiapkan contoh alur…';
    try {
      const json = await post('api/solution.php', data);
      renderPlan(json.record);
      setLocation(json.record);
      status.textContent = mode === 'ai' ? 'Rencana solusi AI siap. Tinjau langkahnya lalu lanjutkan ke dugaan dampak.' : 'Contoh siap. Ini contoh statis, bukan jawaban AI untuk keluhan atau chatmu.';
      status.className = 'form-status ok';
      scrollTo(result);
    } catch (error) {
      status.className = 'form-status err';
      status.textContent = errorMessage(error);
    } finally { lock(null); }
  }

  function updateCounter() { $('chatCharCount').textContent = `${question.value.length.toLocaleString('id-ID')} / 2.000`; }

  chatForm.addEventListener('submit', sendQuestion);
  question.addEventListener('input', updateCounter);
  question.addEventListener('keydown', event => {
    if (event.key === 'Enter' && !event.shiftKey && !event.isComposing && !event.ctrlKey && !event.metaKey && !event.altKey) {
      event.preventDefault();
      if (!busy && (conversation?.turn || 0) < 12) chatForm.requestSubmit();
    }
  });
  document.querySelectorAll('.question-chip').forEach(button => button.addEventListener('click', () => {
    question.value = button.textContent;
    updateCounter(); question.focus();
  }));
  const useChatBtn = $('useChatBtn');
  if (useChatBtn) useChatBtn.addEventListener('click', () => { $('planDetails').open = true; scrollTo($('planBuilder')); generate.focus({ preventScroll: true }); });
  $('newChatBtn').addEventListener('click', () => {
    if (busy) return;
    conversation = null; currentRecord = null; referencePlanId = ''; freshChat = true;
    result.hidden = true;
    question.value = ''; updateCounter(); renderChat(); setLocation();
    status.textContent = '';
    chatStatus.className = 'form-status'; chatStatus.textContent = 'Chat baru siap. Tulis pertanyaanmu.';
    question.focus();
  });
  form.addEventListener('submit', event => { event.preventDefault(); submit('ai'); });
  example.addEventListener('click', () => submit('example'));
  form.addEventListener('input', () => {
    if (!result.hidden) {
      result.hidden = true; currentRecord = null;
      status.className = 'form-status'; status.textContent = 'Isian berubah. Buat rencana kembali agar hasil sesuai dengan isian terbaru.';
      setLocation();
    }
  });
  renderChat();
  if (currentRecord) {
    const stale = currentRecord.mode === 'ai' && currentRecord.conversation_id && conversation && (currentRecord.conversation_id !== conversation.id || currentRecord.conversation_turn !== conversation.turn);
    if (stale) {
      status.className = 'form-status'; status.textContent = 'Riwayat chat sudah bertambah sejak rencana ini dibuat. Buat rencana lagi untuk memakai konteks terbaru.';
      $('planDetails').open = true;
    } else renderPlan(currentRecord);
  }
})();
