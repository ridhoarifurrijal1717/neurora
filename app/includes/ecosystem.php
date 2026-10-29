<?php
declare(strict_types=1);

/** Katalog mandiri agar database lama tidak menimpa alur ekosistem baru. */
function ecosystem_problems(): array {
    return [
        'udara' => [
            'category'=>'Udara', 'icon'=>'cloud', 'title'=>'Asap pembakaran sampah mengganggu',
            'description'=>'Udara di sekitar rumah berasap karena sampah dibakar. Ingin mencari cara menguranginya?',
            'prompt'=>'Sampah masih dibakar di sekitar rumah saya dan asapnya mengganggu warga.',
            'baseline_label'=>'Sampah yang biasa dibakar per hari', 'unit'=>'kg', 'baseline'=>5, 'target'=>60, 'daily'=>true,
            'target_label'=>'Target sampah yang dialihkan dari pembakaran', 'impact_label'=>'Sampah tidak jadi dibakar',
            'measurement'=>'Timbang sampah yang sebelumnya dibakar dan catat jumlah yang berhasil dialihkan ke pengelolaan yang aman. Angka ini tidak mengukur konsentrasi polutan udara.',
            'action'=>'Pisahkan sampah dari sumbernya dan sepakati jadwal pengangkutan bersama warga agar tidak perlu membakar sampah.'
        ],
        'air' => [
            'category'=>'Air', 'icon'=>'water', 'title'=>'Air terbuang dan saluran tercemar',
            'description'=>'Keran bocor, pemakaian berlebihan, atau saluran kotor? Mulai dari kebiasaan dan sumber masalahnya.',
            'prompt'=>'Pemakaian air di rumah saya boros dan saluran sekitar sering kotor. Saya ingin mulai menghemat air.',
            'baseline_label'=>'Pemakaian air awal per hari', 'unit'=>'liter', 'baseline'=>200, 'target'=>20, 'daily'=>true,
            'target_label'=>'Target penghematan pemakaian air', 'impact_label'=>'Pemakaian air yang dihemat',
            'measurement'=>'Bandingkan meter air pada jam yang sama sebelum dan sesudah tindakan. Penghematan volume tidak membuktikan air layak minum atau bebas pencemar.',
            'action'=>'Periksa kebocoran, catat meter air harian, dan hentikan pembuangan sampah atau bahan kimia ke saluran. Untuk pencemaran, hubungi pengelola air atau dinas terkait.'
        ],
        'sampah' => [
            'category'=>'Sampah', 'icon'=>'recycle', 'title'=>'Sampah menumpuk dan masih tercampur',
            'description'=>'Sampah rumah tangga sulit dikelola dan cepat memenuhi tempat pembuangan.',
            'prompt'=>'Sampah rumah tangga di lingkungan saya masih tercampur dan sering menumpuk.',
            'baseline_label'=>'Sampah campuran yang dibuang per hari', 'unit'=>'kg', 'baseline'=>10, 'target'=>30, 'daily'=>true,
            'target_label'=>'Target sampah yang dikurangi atau dialihkan', 'impact_label'=>'Sampah campuran yang dialihkan',
            'measurement'=>'Timbang sampah campuran serta sampah terpilah yang benar-benar diterima pengelola. Pisahkan limbah berbahaya dan elektronik.',
            'action'=>'Kurangi barang sekali pakai, pisahkan organik dan material daur ulang, lalu pastikan jalur pengangkutannya tersedia.'
        ],
        'tanah' => [
            'category'=>'Tanah', 'icon'=>'leaf', 'title'=>'Sisa organik terbuang, tanah kurang terawat',
            'description'=>'Sisa dapur dan daun hanya dibuang, sementara tanah di pekarangan membutuhkan perawatan.',
            'prompt'=>'Sisa dapur dan daun di rumah saya langsung dibuang. Saya ingin mengolahnya untuk merawat tanah.',
            'baseline_label'=>'Sisa organik yang dibuang per hari', 'unit'=>'kg', 'baseline'=>3, 'target'=>50, 'daily'=>true,
            'target_label'=>'Target organik yang diolah dengan aman', 'impact_label'=>'Bahan organik masuk pengolahan',
            'measurement'=>'Timbang bahan organik yang masuk pengolahan dan pantau bau, kelembapan, serta kematangan kompos. Berat bahan masuk bukan berat kompos jadi atau ukuran kesuburan tanah.',
            'action'=>'Mulai pengomposan skala kecil dengan bahan yang sesuai, kelola kelembapan dan aerasi, lalu gunakan hanya kompos matang.'
        ],
        'energi' => [
            'category'=>'Energi', 'icon'=>'bolt', 'title'=>'Listrik boros karena kebiasaan harian',
            'description'=>'Lampu, pendingin, atau perangkat terus menyala. Temukan langkah efisiensi yang bisa dimulai.',
            'prompt'=>'Listrik di rumah saya boros karena lampu dan perangkat sering menyala saat tidak digunakan.',
            'baseline_label'=>'Pemakaian listrik awal per hari', 'unit'=>'kWh', 'baseline'=>15, 'target'=>15, 'daily'=>true,
            'target_label'=>'Target penghematan listrik', 'impact_label'=>'Energi listrik yang dihemat',
            'measurement'=>'Bandingkan kWh meter pada periode dengan aktivitas dan cuaca serupa. Tidak ada konversi CO₂ tanpa faktor emisi yang sesuai lokasi dan periode.',
            'action'=>'Catat beban utama, atur jadwal pemakaian, matikan perangkat yang tidak digunakan, dan evaluasi konsumsi listrik tiap minggu.'
        ],
        'ruang-hijau' => [
            'category'=>'Ruang hijau', 'icon'=>'tree', 'title'=>'Pekarangan minim tanaman dan habitat',
            'description'=>'Ada lahan yang belum ditanami dan sedikit tempat bagi penyerbuk atau satwa kecil.',
            'prompt'=>'Pekarangan di lingkungan saya minim tanaman. Ada lahan yang ingin kami jadikan ruang hijau.',
            'baseline_label'=>'Luas lahan yang tersedia', 'unit'=>'m²', 'baseline'=>50, 'target'=>40, 'daily'=>false,
            'target_label'=>'Target bagian lahan yang dihijaukan', 'impact_label'=>'Lahan yang direncanakan dihijaukan',
            'measurement'=>'Ukur luas yang benar-benar ditanami, pantau tanaman yang bertahan dan kebutuhan perawatannya. Luas tanam tidak otomatis menunjukkan pemulihan biodiversitas.',
            'action'=>'Petakan lahan, pilih tanaman lokal yang sesuai kondisi setempat, pastikan izin penggunaan lahan, dan susun jadwal perawatan.'
        ],
    ];
}

function ecosystem_problem(string $id): ?array { return ecosystem_problems()[$id] ?? null; }

/** Aritmetika skenario; target adalah asumsi pengguna, bukan koefisien ilmiah. */
function estimate_impact(array $problem, float $baseline, float $target, int $days): array {
    $total = $baseline * ($problem['daily'] ? $days : 1);
    $benefit = $total * $target / 100;
    return [
        'total'=>$total, 'benefit'=>$benefit, 'remaining'=>$total-$benefit,
        'low'=>$total * max(0, $target * .8) / 100,
        'high'=>$total * min(100, $target * 1.2) / 100,
        'low_target'=>max(0, $target * .8), 'high_target'=>min(100, $target * 1.2)
    ];
}

function solution_record(string $id): ?array {
    if (!preg_match('/^[a-f0-9]{24}$/', $id)) return null;
    $record = $_SESSION['eco_solutions'][$id] ?? null;
    return $record && $record['created_at'] > time() - 86400 ? $record : null;
}

function conversation_record(string $id): ?array {
    if (!preg_match('/^[a-f0-9]{24}$/', $id)) return null;
    $record = $_SESSION['eco_conversations'][$id] ?? null;
    return $record && $record['created_at'] > time() - 86400 ? $record : null;
}

/** Hanya pesan yang terlihat yang dikirim ke browser; keluaran internal penyedia tetap di server. */
function public_conversation(array $conversation): array {
    return [
        'id'=>$conversation['id'], 'problem'=>$conversation['problem'],
        'messages'=>$conversation['messages'], 'turn'=>$conversation['turn'],
        'created_at'=>$conversation['created_at']
    ];
}

function save_conversation(array $conversation): void {
    $now = time();
    $records = array_filter($_SESSION['eco_conversations'] ?? [], static function ($r) use ($now) { return $r['created_at'] > $now - 86400; });
    unset($records[$conversation['id']]);
    while (count($records) >= 5) array_shift($records);
    $records[$conversation['id']] = $conversation;
    $_SESSION['eco_conversations'] = $records;
    $_SESSION['eco_chat_latest'][$conversation['problem']] = $conversation['id'];
}

/** Kuota yang sama untuk chat dan pembuatan rencana solusi. */
function reserve_ecosystem_request(): bool {
    $now = time();
    $attempts = array_values(array_filter($_SESSION['eco_attempts'] ?? [], static function ($t) use ($now) { return $t > $now - 600; }));
    if (count($attempts) >= 10) return false;
    $attempts[] = $now;
    $_SESSION['eco_attempts'] = $attempts;
    return true;
}

/** Contoh statis untuk rencana tanpa kategori problem spesifik. */
function example_solution_generic(?array $problem): array {
    if ($problem) return example_solution($problem);
    return [
        'summary'=>'Ini contoh statis untuk mencoba alur situs. Contoh belum mempertimbangkan keluhan khusus Anda dan tidak dibuat oleh AI.',
        'possible_causes'=>['Sumber masalah belum dievaluasi dari data awal.', 'Sarana dan pembagian peran perlu diperiksa di lokasi.'],
        'steps'=>[
            ['title'=>'Catat kondisi awal', 'action'=>'Amati sumber masalah dan catat data awal selama beberapa hari. Tentukan siapa yang akan membantu.', 'timing'=>'Hari 1–7'],
            ['title'=>'Mulai tindakan kecil', 'action'=>'Pilih satu langkah paling mudah dan mulai terapkan konsisten selama seminggu.', 'timing'=>'Minggu 2'],
            ['title'=>'Ukur dan perbaiki', 'action'=>'Bandingkan kondisi sebelum dan sesudah, catat perubahan yang terlihat, dan sesuaikan tindakan.', 'timing'=>'Setiap minggu']
        ],
        'impact_hypothesis'=>'Jika tindakan diterapkan konsisten dan target asumsi tercapai, indikator yang dipilih dapat membaik. Gunakan kalkulator untuk mencoba skenario, lalu verifikasi dengan pengukuran.',
        'measurement_plan'=>['Catat kondisi awal secara kuantitatif (jumlah, berat, volume, atau luas).', 'Bandingkan secara berkala dengan metode dan waktu yang sama.'],
        'caution'=>'Sesuaikan tindakan dengan kondisi setempat. Untuk pencemaran serius, limbah berbahaya, atau pekerjaan teknis, libatkan pengelola dan tenaga yang berwenang.'
    ];
}

function example_solution(array $problem): array {
    return [
        'summary'=>'Ini contoh statis untuk mencoba alur situs. Contoh belum mempertimbangkan keluhan khusus Anda dan tidak dibuat oleh AI.',
        'possible_causes'=>['Kebiasaan pengelolaan belum dievaluasi dari data awal.', 'Sarana dan pembagian peran perlu diperiksa di lokasi.'],
        'steps'=>[
            ['title'=>'Catat kondisi awal', 'action'=>'Amati sumber masalah dan catat data awal selama beberapa hari. Tentukan siapa yang akan membantu.', 'timing'=>'Hari 1–7'],
            ['title'=>'Mulai tindakan kecil', 'action'=>$problem['action'], 'timing'=>'Minggu 2'],
            ['title'=>'Ukur dan perbaiki', 'action'=>$problem['measurement'], 'timing'=>'Setiap minggu']
        ],
        'impact_hypothesis'=>'Jika tindakan diterapkan konsisten dan target asumsi tercapai, indikator yang dipilih dapat membaik. Gunakan kalkulator untuk mencoba skenario, lalu verifikasi dengan pengukuran.',
        'measurement_plan'=>[$problem['measurement'], 'Catat aktivitas atau kondisi lain yang berubah agar perbandingan tidak menyesatkan.'],
        'caution'=>'Sesuaikan tindakan dengan kondisi setempat. Untuk pencemaran serius, limbah berbahaya, atau pekerjaan teknis, libatkan pengelola dan tenaga yang berwenang.'
    ];
}
