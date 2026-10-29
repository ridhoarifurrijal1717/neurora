<section class="section container" id="contact">
  <div class="glass panel contact reveal">
    <div>
      <span class="eyebrow">CONTACT</span>
      <h2>Punya masukan untuk Neurora?</h2>
      <p class="muted">Sampaikan pertanyaan atau saran untuk membantu kami memperbaiki pengalamanmu. Untuk mencari solusi ekosistem, mulai dari halaman Problem.</p>
    </div>
    <form id="contactForm" novalidate>
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
      <label>Nama<input type="text" name="name" required maxlength="100" placeholder="Nama lengkap"></label>
      <label>Email<input type="email" name="email" required maxlength="150" placeholder="nama@email.com"></label>
      <label>Pesan<textarea name="message" rows="4" required maxlength="2000" placeholder="Tulis pesanmu…"></textarea></label>
      <button class="btn btn-primary" type="submit">Kirim Pesan <?= icon('arrow', 16) ?></button>
      <p class="form-status" id="formStatus" role="status"></p>
    </form>
  </div>
</section>
