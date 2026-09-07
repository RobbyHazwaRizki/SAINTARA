// ============================================================
// SAINTARA — ELLA'S LAB
// assets/js/data.js — Konten Materi 24 Topik + Seed Soal
// Dipakai sebagai fallback saat DB kosong / offline
// ============================================================

const SAINTARA_DATA = {

  // ── METADATA TOPIK 24 ──────────────────────────────────────
  topik: [
    // KELAS 1
    { id:1,  kelas:1, judul:'Panca Indera',            icon:'👁️',  urutan:1 },
    { id:2,  kelas:1, judul:'Hewan Peliharaan',         icon:'🐾',  urutan:2 },
    { id:3,  kelas:1, judul:'Bagian Tumbuhan',          icon:'🌱',  urutan:3 },
    { id:4,  kelas:1, judul:'Siang & Malam',            icon:'🌙',  urutan:4 },
    // KELAS 2
    { id:5,  kelas:2, judul:'Hewan & Makanannya',       icon:'🐄',  urutan:1 },
    { id:6,  kelas:2, judul:'Pertumbuhan Tanaman',      icon:'🌿',  urutan:2 },
    { id:7,  kelas:2, judul:'Wujud Benda',              icon:'🧊',  urutan:3 },
    { id:8,  kelas:2, judul:'Lingkungan Sehat',         icon:'🏡',  urutan:4 },
    // KELAS 3
    { id:9,  kelas:3, judul:'Ciri Makhluk Hidup',       icon:'🔬',  urutan:1 },
    { id:10, kelas:3, judul:'Perubahan Makhluk Hidup',  icon:'🦋',  urutan:2 },
    { id:11, kelas:3, judul:'Sumber Energi',            icon:'⚡',  urutan:3 },
    { id:12, kelas:3, judul:'Gerak Benda',              icon:'🎯',  urutan:4 },
    // KELAS 4
    { id:13, kelas:4, judul:'Rangka Tubuh',             icon:'🦴',  urutan:1 },
    { id:14, kelas:4, judul:'Daur Hidup Hewan',         icon:'🐛',  urutan:2 },
    { id:15, kelas:4, judul:'Perubahan Wujud Benda',    icon:'💧',  urutan:3 },
    { id:16, kelas:4, judul:'Sumber Daya Alam',         icon:'🌏',  urutan:4 },
    // KELAS 5
    { id:17, kelas:5, judul:'Sistem Peredaran Darah',   icon:'❤️',  urutan:1 },
    { id:18, kelas:5, judul:'Adaptasi',                 icon:'🦎',  urutan:2 },
    { id:19, kelas:5, judul:'Sifat Cahaya',             icon:'💡',  urutan:3 },
    { id:20, kelas:5, judul:'Siklus Air',               icon:'🌊',  urutan:4 },
    // KELAS 6
    { id:21, kelas:6, judul:'Perkembangbiakan',         icon:'🥚',  urutan:1 },
    { id:22, kelas:6, judul:'Tata Surya',               icon:'🪐',  urutan:2 },
    { id:23, kelas:6, judul:'Gaya & Gerak',             icon:'🚀',  urutan:3 },
    { id:24, kelas:6, judul:'Bumi & Perubahannya',      icon:'🌋',  urutan:4 },
  ],

  // ── KONTEN MATERI (untuk halaman materi-detail fallback) ──
  materi: {
    // ─ KELAS 1 ───────────────────────────────────────────────
    'Panca Indera': {
      intro: 'Tubuh kita punya 5 indera yang membantu mengenal dunia. Tanpa indera, kita tidak bisa melihat pelangi, mencium bunga, atau merasakan es krim yang dingin!',
      sections: [
        { judul:'Mata — Indera Penglihatan', icon:'bi-eye-fill', warna:'sec-blue',
          isi:'Mata melihat bentuk, warna, dan gerak. Kita harus menjaganya dengan tidak terlalu lama menatap layar!' },
        { judul:'Telinga — Indera Pendengaran', icon:'bi-ear-fill', warna:'sec-green',
          isi:'Telinga menangkap gelombang suara. Dari bisikan lembut hingga klakson keras — telinga mendengar semuanya!' },
        { judul:'Hidung — Indera Penciuman', icon:'bi-wind', warna:'sec-yellow',
          isi:'Hidung mencium bau harum maupun bau tak sedap. Hidung juga membantu kita bernapas!' },
        { judul:'Lidah — Indera Pengecapan', icon:'bi-droplet-fill', warna:'sec-mint',
          isi:'Lidah merasakan manis, asam, asin, pahit, dan gurih (umami). Makanan jadi lebih nikmat berkat lidah!' },
        { judul:'Kulit — Indera Peraba', icon:'bi-hand-index-thumb-fill', warna:'sec-pink',
          isi:'Kulit merasakan sentuhan, tekanan, panas, dingin, dan sakit. Kulit adalah organ terbesar tubuh!' },
      ],
      fun_fact: 'Tahukah kamu? Lidah manusia bisa membedakan lebih dari 10.000 jenis rasa berbeda!',
    },
    'Hewan Peliharaan': {
      intro: 'Hewan peliharaan adalah hewan yang hidup bersama manusia dan dirawat dengan penuh kasih sayang.',
      sections: [
        { judul:'Anjing', icon:'bi-heart-fill', warna:'sec-blue',
          isi:'Anjing adalah hewan peliharaan yang setia. Perlu dimandikan, diberi makan, dan diajak bermain setiap hari!' },
        { judul:'Kucing', icon:'bi-stars', warna:'sec-green',
          isi:'Kucing suka bermain dan tidur. Mereka bisa membersihkan diri sendiri dengan cara menjilat bulunya!' },
        { judul:'Ikan Hias', icon:'bi-water', warna:'sec-yellow',
          isi:'Ikan tinggal di dalam air. Kita harus membersihkan akuarium dan memberi makan secara teratur!' },
        { judul:'Burung', icon:'bi-feather', warna:'sec-mint',
          isi:'Burung berkicau dengan merdu. Beberapa jenis burung bahkan bisa menirukan suara manusia!' },
      ],
      fun_fact: 'Anjing bisa mencium bau 100.000 kali lebih kuat dari manusia!',
    },
    'Bagian Tumbuhan': {
      intro: 'Tumbuhan terdiri dari beberapa bagian yang masing-masing punya fungsi penting untuk kelangsungan hidup tumbuhan.',
      sections: [
        { judul:'Akar', icon:'bi-diagram-3-fill', warna:'sec-blue',
          isi:'Akar menyerap air dan mineral dari dalam tanah. Akar juga menopang tumbuhan agar tidak mudah roboh!' },
        { judul:'Batang', icon:'bi-bar-chart-fill', warna:'sec-green',
          isi:'Batang mengangkut air dan makanan dari akar ke daun. Batang juga menjadi tempat melekatnya daun dan bunga!' },
        { judul:'Daun', icon:'bi-circle-fill', warna:'sec-yellow',
          isi:'Daun tempat fotosintesis terjadi — mengubah sinar matahari + air + CO₂ menjadi makanan dan oksigen!' },
        { judul:'Bunga & Buah', icon:'bi-flower1', warna:'sec-mint',
          isi:'Bunga adalah alat perkembangbiakan tumbuhan. Setelah penyerbukan, bunga berubah menjadi buah!' },
      ],
      fun_fact: 'Pohon tertinggi di dunia mencapai 115 meter — setinggi gedung 30 lantai!',
    },
    'Siang & Malam': {
      intro: 'Siang dan malam terjadi karena Bumi berputar pada porosnya sendiri (rotasi) selama 24 jam.',
      sections: [
        { judul:'Siang Hari', icon:'bi-sun-fill', warna:'sec-yellow',
          isi:'Saat siang, bagian Bumi menghadap matahari. Langit terang, suhu hangat, dan makhluk hidup beraktivitas!' },
        { judul:'Malam Hari', icon:'bi-moon-fill', warna:'sec-blue',
          isi:'Saat malam, bagian Bumi membelakangi matahari. Langit gelap, tampak bintang dan bulan!' },
        { judul:'Matahari Terbit & Tenggelam', icon:'bi-sunset-fill', warna:'sec-mint',
          isi:'Matahari terbit di timur dan tenggelam di barat karena Bumi berotasi dari barat ke timur!' },
      ],
      fun_fact: 'Satu hari di planet Venus lebih panjang dari setahun di Venus — rotasinya sangat lambat!',
    },

    // ─ KELAS 2 ───────────────────────────────────────────────
    'Hewan & Makanannya': {
      intro: 'Setiap hewan memiliki jenis makanan yang berbeda-beda. Berdasarkan makanannya, hewan dibagi menjadi tiga kelompok besar.',
      sections: [
        { judul:'Herbivora — Pemakan Tumbuhan', icon:'bi-flower2', warna:'sec-green',
          isi:'Contoh: sapi, kambing, kelinci, kuda. Herbivora memiliki gigi geraham yang kuat untuk mengunyah rumput!' },
        { judul:'Karnivora — Pemakan Daging', icon:'bi-lightning-fill', warna:'sec-blue',
          isi:'Contoh: harimau, singa, elang, buaya. Karnivora memiliki gigi taring yang tajam untuk merobek daging!' },
        { judul:'Omnivora — Pemakan Segalanya', icon:'bi-grid-fill', warna:'sec-yellow',
          isi:'Contoh: manusia, beruang, ayam, babi hutan. Omnivora bisa makan tumbuhan maupun daging!' },
      ],
      fun_fact: 'Beruang kutub adalah karnivora terbesar di darat — bisa memakan hingga 45 kg makanan sehari!',
    },
    'Pertumbuhan Tanaman': {
      intro: 'Tanaman tumbuh dari biji melalui beberapa tahap. Setiap tahap memerlukan air, cahaya matahari, dan nutrisi dari tanah.',
      sections: [
        { judul:'Tahap 1: Biji', icon:'bi-circle', warna:'sec-yellow',
          isi:'Biji adalah awal kehidupan tanaman. Di dalamnya tersimpan embrio dan cadangan makanan!' },
        { judul:'Tahap 2: Perkecambahan', icon:'bi-arrow-up', warna:'sec-green',
          isi:'Dengan air dan kehangatan, biji mulai berkecambah. Akar kecil muncul ke bawah, tunas ke atas!' },
        { judul:'Tahap 3: Tumbuh & Berbuah', icon:'bi-tree-fill', warna:'sec-blue',
          isi:'Tanaman dewasa menghasilkan bunga dan buah. Buah berisi biji baru untuk generasi berikutnya!' },
      ],
      fun_fact: 'Biji lotus bisa bertahan ribuan tahun dan masih bisa tumbuh! Ada biji yang tumbuh setelah 1.300 tahun!',
    },
    'Wujud Benda': {
      intro: 'Semua benda di sekitar kita memiliki wujud. Ada tiga wujud benda: padat, cair, dan gas.',
      sections: [
        { judul:'Benda Padat', icon:'bi-square-fill', warna:'sec-blue',
          isi:'Bentuk dan volumenya tetap. Contoh: batu, kayu, es batu, buku. Padat = bisa dipegang!' },
        { judul:'Benda Cair', icon:'bi-droplet-fill', warna:'sec-green',
          isi:'Volume tetap, bentuk mengikuti wadah. Contoh: air, minyak, susu. Cair = mengalir ke bawah!' },
        { judul:'Benda Gas', icon:'bi-cloud-fill', warna:'sec-yellow',
          isi:'Bentuk dan volume berubah mengisi ruang. Contoh: udara, uap air, asap. Gas = tidak terlihat!' },
      ],
      fun_fact: 'Air adalah satu-satunya zat yang ada di Bumi dalam tiga wujud sekaligus: es (padat), air (cair), uap (gas)!',
    },
    'Lingkungan Sehat': {
      intro: 'Lingkungan sehat membuat kita nyaman, aman, dan tidak mudah sakit. Semua orang bertanggung jawab menjaga lingkungan!',
      sections: [
        { judul:'Ciri Lingkungan Sehat', icon:'bi-check-circle-fill', warna:'sec-green',
          isi:'Udara bersih, air jernih, tidak bau, tidak ada sampah berserakan, dan banyak tanaman hijau!' },
        { judul:'Cara Menjaga Lingkungan', icon:'bi-recycle', warna:'sec-blue',
          isi:'Buang sampah di tempat yang benar, hemat air, tanam pohon, tidak membakar sampah sembarangan!' },
        { judul:'Dampak Lingkungan Kotor', icon:'bi-exclamation-triangle-fill', warna:'sec-yellow',
          isi:'Lingkungan kotor menyebabkan banjir, penyakit (diare, demam berdarah), dan udara tidak segar!' },
      ],
      fun_fact: 'Satu pohon dewasa bisa menyerap 22 kg CO₂ per tahun dan menghasilkan O₂ untuk 2 orang!',
    },

    // ─ KELAS 3 ───────────────────────────────────────────────
    'Ciri Makhluk Hidup': {
      intro: 'Apa yang membedakan makhluk hidup dengan benda mati? Ada 7 ciri utama yang dimiliki semua makhluk hidup!',
      sections: [
        { judul:'Bernapas & Bergerak', icon:'bi-wind', warna:'sec-blue',
          isi:'Semua makhluk hidup bernapas (mengambil O₂, mengeluarkan CO₂) dan bisa bergerak (walau tumbuhan lambat)!' },
        { judul:'Tumbuh & Berkembang Biak', icon:'bi-arrow-up-right', warna:'sec-green',
          isi:'Makhluk hidup bertambah besar dan menghasilkan keturunan untuk meneruskan jenisnya!' },
        { judul:'Memerlukan Makanan & Peka terhadap Rangsang', icon:'bi-lightning-fill', warna:'sec-yellow',
          isi:'Semua makhluk hidup butuh energi dari makanan dan merespons perubahan lingkungan (cahaya, suhu, sentuhan)!' },
        { judul:'Mengeluarkan Zat Sisa', icon:'bi-arrow-right-circle', warna:'sec-mint',
          isi:'Makhluk hidup mengeluarkan zat sisa metabolisme: manusia (keringat, urine), tumbuhan (O₂ & H₂O)!' },
      ],
      fun_fact: 'Pohon bisa "berkomunikasi" satu sama lain melalui jaringan jamur di tanah yang disebut "wood wide web"!',
    },
    'Perubahan Makhluk Hidup': {
      intro: 'Semua makhluk hidup mengalami perubahan seiring waktu. Perubahan ini bisa berupa pertumbuhan, perkembangan, atau metamorfosis!',
      sections: [
        { judul:'Pertumbuhan Manusia', icon:'bi-person-fill', warna:'sec-blue',
          isi:'Bayi → anak-anak → remaja → dewasa → lansia. Setiap tahap punya kemampuan dan kebutuhan berbeda!' },
        { judul:'Metamorfosis Sempurna', icon:'bi-arrow-right', warna:'sec-green',
          isi:'Telur → larva → pupa → dewasa. Contoh: kupu-kupu, katak, nyamuk, lebah!' },
        { judul:'Metamorfosis Tidak Sempurna', icon:'bi-arrows-expand', warna:'sec-yellow',
          isi:'Telur → nimfa → dewasa. Contoh: belalang, kecoa, capung. Tidak ada tahap pupa!' },
      ],
      fun_fact: 'Kupu-kupu memiliki 4 jenis mata yang bisa melihat warna ultraviolet yang tidak bisa dilihat manusia!',
    },
    'Sumber Energi': {
      intro: 'Energi membuat segala sesuatu bisa bekerja. Tanpa energi, tidak ada gerakan, tidak ada cahaya, tidak ada panas!',
      sections: [
        { judul:'Energi Matahari', icon:'bi-sun-fill', warna:'sec-yellow',
          isi:'Matahari adalah sumber energi utama Bumi! Energinya menggerakkan angin, hujan, dan fotosintesis!' },
        { judul:'Energi Listrik', icon:'bi-lightning-charge-fill', warna:'sec-blue',
          isi:'Listrik menggerakkan lampu, TV, HP, dan AC. Dihasilkan dari PLTA, PLTU, atau panel surya!' },
        { judul:'Energi Panas & Gerak', icon:'bi-fire', warna:'sec-mint',
          isi:'Api menghasilkan panas dan cahaya. Angin dan air menghasilkan energi gerak yang bisa diubah jadi listrik!' },
      ],
      fun_fact: 'Dalam 1 jam, Matahari mengirim energi ke Bumi yang cukup untuk memenuhi kebutuhan listrik dunia selama setahun!',
    },
    'Gerak Benda': {
      intro: 'Benda bisa bergerak karena ada gaya yang bekerja padanya. Gaya adalah tarikan atau dorongan!',
      sections: [
        { judul:'Gaya Dorong & Tarik', icon:'bi-arrows-expand', warna:'sec-blue',
          isi:'Mendorong meja = gaya dorong. Menarik tali = gaya tarik. Gaya membuat benda diam jadi bergerak!' },
        { judul:'Gaya Gravitasi', icon:'bi-arrow-down-circle-fill', warna:'sec-green',
          isi:'Gravitasi menarik semua benda ke pusat Bumi. Itulah kenapa apel jatuh ke bawah, bukan ke atas!' },
        { judul:'Gaya Gesek & Magnet', icon:'bi-magnet-fill', warna:'sec-yellow',
          isi:'Gesek menghambat gerak (lantai kasar = gesek besar). Magnet menarik benda-benda tertentu dari jarak jauh!' },
      ],
      fun_fact: 'Di luar angkasa tidak ada gravitasi! Astronot melayang karena tidak ada gaya tarik ke bawah!',
    },

    // ─ KELAS 4 ───────────────────────────────────────────────
    'Rangka Tubuh': {
      intro: 'Rangka adalah kerangka tubuh yang terbuat dari tulang. Tanpa rangka, tubuh kita tidak bisa berdiri tegak!',
      sections: [
        { judul:'Fungsi Rangka', icon:'bi-shield-fill', warna:'sec-blue',
          isi:'Rangka: (1) menegakkan tubuh, (2) melindungi organ vital (otak, jantung, paru), (3) tempat melekat otot!' },
        { judul:'Bagian Rangka', icon:'bi-diagram-3', warna:'sec-green',
          isi:'Rangka kepala (tengkorak), rangka badan (tulang punggung, rusuk, dada), rangka anggota gerak (tangan, kaki)!' },
        { judul:'Menjaga Kesehatan Tulang', icon:'bi-heart-fill', warna:'sec-yellow',
          isi:'Makan makanan kaya kalsium (susu, ikan), berjemur sinar matahari pagi, dan olahraga teratur!' },
      ],
      fun_fact: 'Bayi baru lahir punya sekitar 300 tulang, tapi dewasa hanya 206 — beberapa menyatu saat tumbuh besar!',
    },
    'Daur Hidup Hewan': {
      intro: 'Setiap hewan mengalami siklus hidup dari lahir/menetas hingga dewasa. Ada yang sederhana, ada yang mengalami metamorfosis!',
      sections: [
        { judul:'Daur Hidup Kupu-Kupu', icon:'bi-flower1', warna:'sec-blue',
          isi:'Telur → ulat (larva) → kepompong (pupa) → kupu-kupu dewasa. Ini metamorfosis sempurna!' },
        { judul:'Daur Hidup Katak', icon:'bi-water', warna:'sec-green',
          isi:'Telur → berudu (hidup di air, bernapas insang) → katak muda → katak dewasa (darat & air, bernapas paru)!' },
        { judul:'Daur Hidup Ayam & Ikan', icon:'bi-circle', warna:'sec-yellow',
          isi:'Ayam: telur → anak ayam → ayam dewasa (tidak metamorfosis). Ikan: telur → burayak → ikan dewasa!' },
      ],
      fun_fact: 'Seekor kupu-kupu monarch bisa terbang migrasi sejauh 4.800 km — dari Kanada ke Meksiko!',
    },
    'Perubahan Wujud Benda': {
      intro: 'Benda bisa berubah wujud karena pengaruh suhu (dipanaskan atau didinginkan). Ada 6 jenis perubahan wujud!',
      sections: [
        { judul:'Mencair & Membeku', icon:'bi-thermometer-half', warna:'sec-blue',
          isi:'Mencair: padat → cair (es dipanaskan jadi air). Membeku: cair → padat (air didinginkan jadi es)!' },
        { judul:'Menguap & Mengembun', icon:'bi-cloud-fill', warna:'sec-green',
          isi:'Menguap: cair → gas (air dipanaskan). Mengembun: gas → cair (uap air mendingin jadi tetes air)!' },
        { judul:'Menyublim & Mengkristal', icon:'bi-stars', warna:'sec-yellow',
          isi:'Menyublim: padat → gas langsung (kapur barus). Mengkristal: gas → padat langsung (salju terbentuk)!' },
      ],
      fun_fact: 'Kapur barus (kamfer) menyublim — langsung dari padat ke gas tanpa melewati fase cair!',
    },
    'Sumber Daya Alam': {
      intro: 'Sumber daya alam (SDA) adalah kekayaan alam yang bisa dimanfaatkan manusia. Ada yang dapat diperbarui, ada yang tidak!',
      sections: [
        { judul:'SDA Dapat Diperbarui', icon:'bi-arrow-repeat', warna:'sec-green',
          isi:'Terus tersedia jika dikelola dengan baik. Contoh: air, udara, tanah, tumbuhan, hewan, sinar matahari!' },
        { judul:'SDA Tidak Dapat Diperbarui', icon:'bi-hourglass-bottom', warna:'sec-yellow',
          isi:'Terbatas dan akan habis. Contoh: minyak bumi, batu bara, gas alam, emas, besi!' },
        { judul:'Menjaga SDA', icon:'bi-tree-fill', warna:'sec-blue',
          isi:'Hemat energi, daur ulang, tidak merusak hutan, gunakan energi terbarukan (surya, angin, air)!' },
      ],
      fun_fact: 'Indonesia adalah salah satu negara dengan keanekaragaman hayati terbesar di dunia — disebut "megabiodiversity"!',
    },

    // ─ KELAS 5 ───────────────────────────────────────────────
    'Sistem Peredaran Darah': {
      intro: 'Sistem peredaran darah mengantarkan oksigen dan nutrisi ke seluruh tubuh. Jantung adalah pompanya!',
      sections: [
        { judul:'Jantung — Si Pompa Setia', icon:'bi-heart-pulse-fill', warna:'sec-blue',
          isi:'Jantung berdetak sekitar 100.000 kali per hari! Memompa darah ke paru-paru dan seluruh tubuh!' },
        { judul:'Pembuluh Darah', icon:'bi-diagram-2-fill', warna:'sec-green',
          isi:'Arteri: bawa darah dari jantung (kaya O₂). Vena: bawa darah ke jantung (kaya CO₂). Kapiler: sangat halus!' },
        { judul:'Darah & Fungsinya', icon:'bi-droplet-fill', warna:'sec-yellow',
          isi:'Darah terdiri dari sel darah merah (O₂), sel darah putih (imun), trombosit (pembekuan), dan plasma!' },
      ],
      fun_fact: 'Jika semua pembuluh darah manusia disambung, panjangnya mencapai 100.000 km — bisa mengelilingi Bumi 2,5 kali!',
    },
    'Adaptasi': {
      intro: 'Adaptasi adalah kemampuan makhluk hidup menyesuaikan diri dengan lingkungannya agar bisa bertahan hidup!',
      sections: [
        { judul:'Adaptasi Morfologi', icon:'bi-eye-fill', warna:'sec-blue',
          isi:'Adaptasi bentuk tubuh. Contoh: paruh burung (menyesuaikan makanan), kaki bebek berselaput (berenang)!' },
        { judul:'Adaptasi Fisiologi', icon:'bi-activity', warna:'sec-green',
          isi:'Adaptasi fungsi tubuh. Contoh: unta menyimpan lemak di punuk, ikan memproduksi antifreeze di darah!' },
        { judul:'Adaptasi Tingkah Laku', icon:'bi-person-walking', warna:'sec-yellow',
          isi:'Adaptasi perilaku. Contoh: beruang hibernasi saat dingin, burung migrasi mencari tempat hangat!' },
      ],
      fun_fact: 'Bunglon berubah warna bukan untuk kamuflase, tapi untuk komunikasi dan mengatur suhu tubuh!',
    },
    'Sifat Cahaya': {
      intro: 'Cahaya adalah gelombang elektromagnetik yang bisa kita lihat. Cahaya punya banyak sifat unik yang bisa dimanfaatkan!',
      sections: [
        { judul:'Merambat Lurus & Menembus', icon:'bi-arrow-right', warna:'sec-yellow',
          isi:'Cahaya merambat lurus (senter membuat sinar lurus). Bisa menembus benda bening seperti kaca dan air!' },
        { judul:'Dipantulkan & Dibiaskan', icon:'bi-symmetry-horizontal', warna:'sec-blue',
          isi:'Dipantulkan: cermin memantulkan cahaya (hukum pantul). Dibiaskan: pensil dalam gelas air terlihat bengkok!' },
        { judul:'Dispersi — Pelangi', icon:'bi-rainbow', warna:'sec-mint',
          isi:'Cahaya putih bisa diurai menjadi 7 warna pelangi (merah, jingga, kuning, hijau, biru, nila, ungu) oleh prisma!' },
      ],
      fun_fact: 'Cahaya adalah hal tercepat di alam semesta — 299.792.458 meter per detik! Dari Matahari ke Bumi: 8 menit!',
    },
    'Siklus Air': {
      intro: 'Air di Bumi selalu berputar dalam siklus yang tidak pernah berhenti. Siklus inilah yang menjamin ketersediaan air!',
      sections: [
        { judul:'Evaporasi & Transpirasi', icon:'bi-sun-fill', warna:'sec-yellow',
          isi:'Evaporasi: air menguap karena panas matahari. Transpirasi: tumbuhan menguapkan air melalui daun!' },
        { judul:'Kondensasi & Presipitasi', icon:'bi-cloud-rain-fill', warna:'sec-blue',
          isi:'Kondensasi: uap air mendingin menjadi awan. Presipitasi: tetes air jatuh sebagai hujan, salju, atau es!' },
        { judul:'Infiltrasi & Aliran', icon:'bi-arrow-down-circle', warna:'sec-green',
          isi:'Sebagian air meresap ke tanah (infiltrasi) menjadi air tanah. Sisanya mengalir ke sungai, danau, laut!' },
      ],
      fun_fact: 'Air yang kamu minum hari ini pernah diminum oleh dinosaurus — siklus air mendaur ulang air yang sama selama miliaran tahun!',
    },

    // ─ KELAS 6 ───────────────────────────────────────────────
    'Perkembangbiakan': {
      intro: 'Makhluk hidup berkembang biak untuk melestarikan jenisnya. Ada perkembangbiakan generatif (kawin) dan vegetatif (tanpa kawin)!',
      sections: [
        { judul:'Perkembangbiakan Generatif', icon:'bi-flower1', warna:'sec-blue',
          isi:'Melibatkan sel telur dan sperma. Pada tumbuhan: penyerbukan → pembuahan → biji. Pada hewan: bertelur atau melahirkan!' },
        { judul:'Perkembangbiakan Vegetatif Alami', icon:'bi-scissors', warna:'sec-green',
          isi:'Tanpa kawin, tumbuh dari bagian tubuh. Contoh: pisang (tunas), kentang (umbi), jahe (rimpang), stroberi (stolon)!' },
        { judul:'Perkembangbiakan Vegetatif Buatan', icon:'bi-tools', warna:'sec-yellow',
          isi:'Dibantu manusia. Contoh: setek (singkong), cangkok (mangga), okulasi (mawar), kultur jaringan (anggrek)!' },
      ],
      fun_fact: 'Hydra (hewan air kecil) bisa berkembang biak dengan cara tunas — tumbuh miniatur dirinya dari tubuhnya!',
    },
    'Tata Surya': {
      intro: 'Tata surya adalah sistem yang terdiri dari Matahari dan semua benda langit yang mengorbit di sekitarnya!',
      sections: [
        { judul:'Matahari & 8 Planet', icon:'bi-sun-fill', warna:'sec-yellow',
          isi:'Urutan: Merkurius, Venus, Bumi, Mars (planet dalam) → Jupiter, Saturnus, Uranus, Neptunus (planet luar)!' },
        { judul:'Bulan, Asteroid & Komet', icon:'bi-moon-fill', warna:'sec-blue',
          isi:'Bulan = satelit alami Bumi. Asteroid = batu besar yang mengorbit. Komet = bola es & debu dengan ekor cahaya!' },
        { judul:'Rotasi & Revolusi', icon:'bi-arrow-clockwise', warna:'sec-green',
          isi:'Rotasi Bumi (24 jam) = siang & malam. Revolusi Bumi mengelilingi Matahari (365,25 hari) = 1 tahun!' },
      ],
      fun_fact: 'Jupiter begitu besar sehingga semua planet lain di tata surya bisa masuk ke dalamnya — dengan ruang tersisa!',
    },
    'Gaya & Gerak': {
      intro: 'Gaya adalah tarikan atau dorongan yang memengaruhi gerak benda. Hukum Newton menjelaskan hubungan gaya dan gerak!',
      sections: [
        { judul:'Hukum Newton I, II, III', icon:'bi-calculator-fill', warna:'sec-blue',
          isi:'I: Benda diam tetap diam kecuali ada gaya. II: F = m × a (gaya = massa × percepatan). III: Aksi = reaksi!' },
        { judul:'Gaya Gesek & Manfaatnya', icon:'bi-x-circle-fill', warna:'sec-green',
          isi:'Gesek menghambat gerak TAPI sangat berguna: ban mobil menggenggam jalan, rem bekerja, kita bisa berjalan!' },
        { judul:'Gravitasi & Gaya Berat', icon:'bi-arrow-down', warna:'sec-yellow',
          isi:'Gravitasi Bumi menarik semua benda. Gaya berat = massa × gravitasi (g = 10 m/s²). Berat berbeda dengan massa!' },
      ],
      fun_fact: 'Newton terinspirasi menemukan hukum gravitasi karena melihat apel jatuh di kebunnya pada tahun 1666!',
    },
    'Bumi & Perubahannya': {
      intro: 'Bumi terus berubah karena berbagai proses alam — dari dalam (gempa, gunung meletus) maupun dari luar (erosi, pemanasan global)!',
      sections: [
        { judul:'Lapisan Bumi', icon:'bi-layers-fill', warna:'sec-blue',
          isi:'Kerak (tipis, tempat kita tinggal) → Mantel (cair panas, magma) → Inti Luar (cair besi) → Inti Dalam (padat)!' },
        { judul:'Gempa Bumi & Gunung Meletus', icon:'bi-exclamation-triangle-fill', warna:'sec-yellow',
          isi:'Gempa: pergerakan lempeng tektonik. Gunung meletus: magma dari dalam bumi keluar sebagai lava!' },
        { judul:'Erosi & Pemanasan Global', icon:'bi-thermometer-high', warna:'sec-green',
          isi:'Erosi: pengikisan tanah oleh air/angin (dicegah dengan reboisasi). Pemanasan global: suhu Bumi naik karena gas rumah kaca!' },
      ],
      fun_fact: 'Gunung Api Krakatau di Indonesia meletus tahun 1883 dan suaranya terdengar hingga 4.800 km jauhnya!',
    },
  },

  // ── SEED SOAL FALLBACK (per kelas, untuk kuis offline) ────
  soal: {
    // Kelas 1 — 5 soal
    1: [
      { pertanyaan:'Indera apa yang kita gunakan untuk melihat?',
        opsi_a:'Telinga', opsi_b:'Mata', opsi_c:'Hidung', opsi_d:'Kulit', jawaban:'b' },
      { pertanyaan:'Berapa jumlah panca indera manusia?',
        opsi_a:'3', opsi_b:'4', opsi_c:'5', opsi_d:'6', jawaban:'c' },
      { pertanyaan:'Indera apa yang digunakan untuk mencium bau?',
        opsi_a:'Mata', opsi_b:'Lidah', opsi_c:'Hidung', opsi_d:'Telinga', jawaban:'c' },
      { pertanyaan:'Organ apa yang digunakan untuk merasakan panas dan dingin?',
        opsi_a:'Mata', opsi_b:'Hidung', opsi_c:'Telinga', opsi_d:'Kulit', jawaban:'d' },
      { pertanyaan:'Indera pengecap ada di organ apa?',
        opsi_a:'Hidung', opsi_b:'Lidah', opsi_c:'Kulit', opsi_d:'Mata', jawaban:'b' },
    ],
    // Kelas 2 — 5 soal
    2: [
      { pertanyaan:'Hewan yang hanya makan tumbuhan disebut?',
        opsi_a:'Karnivora', opsi_b:'Omnivora', opsi_c:'Herbivora', opsi_d:'Predator', jawaban:'c' },
      { pertanyaan:'Air berubah menjadi es saat?',
        opsi_a:'Dipanaskan', opsi_b:'Didinginkan', opsi_c:'Digerakkan', opsi_d:'Ditekan', jawaban:'b' },
      { pertanyaan:'Bagian tumbuhan yang menyerap air dari tanah adalah?',
        opsi_a:'Daun', opsi_b:'Batang', opsi_c:'Bunga', opsi_d:'Akar', jawaban:'d' },
      { pertanyaan:'Harimau adalah contoh hewan?',
        opsi_a:'Herbivora', opsi_b:'Omnivora', opsi_c:'Karnivora', opsi_d:'Reptilia', jawaban:'c' },
      { pertanyaan:'Ciri lingkungan sehat adalah?',
        opsi_a:'Banyak sampah', opsi_b:'Air keruh', opsi_c:'Udara bersih', opsi_d:'Berbau busuk', jawaban:'c' },
    ],
    // Kelas 3 — 10 soal
    3: [
      { pertanyaan:'Manakah yang BUKAN ciri makhluk hidup?',
        opsi_a:'Bernapas', opsi_b:'Berkembang biak', opsi_c:'Bergerak', opsi_d:'Berkarat', jawaban:'d' },
      { pertanyaan:'Metamorfosis sempurna dialami oleh?',
        opsi_a:'Belalang', opsi_b:'Kecoa', opsi_c:'Kupu-kupu', opsi_d:'Jangkrik', jawaban:'c' },
      { pertanyaan:'Sumber energi utama di Bumi adalah?',
        opsi_a:'Angin', opsi_b:'Matahari', opsi_c:'Air', opsi_d:'Api', jawaban:'b' },
      { pertanyaan:'Gaya yang menyebabkan benda jatuh ke bawah disebut?',
        opsi_a:'Gaya magnet', opsi_b:'Gaya gesek', opsi_c:'Gaya gravitasi', opsi_d:'Gaya pegas', jawaban:'c' },
      { pertanyaan:'Tahapan metamorfosis sempurna yang benar adalah?',
        opsi_a:'Telur → dewasa → pupa → larva', opsi_b:'Telur → larva → pupa → dewasa',
        opsi_c:'Telur → nimfa → dewasa', opsi_d:'Telur → dewasa langsung', jawaban:'b' },
      { pertanyaan:'Energi listrik dihasilkan oleh?',
        opsi_a:'Kompor', opsi_b:'Lilin', opsi_c:'Generator/PLTA', opsi_d:'Baterai saja', jawaban:'c' },
      { pertanyaan:'Gaya gesek yang besar terjadi pada permukaan?',
        opsi_a:'Es licin', opsi_b:'Kaca halus', opsi_c:'Aspal kasar', opsi_d:'Minyak', jawaban:'c' },
      { pertanyaan:'Benda yang bergerak menggelinding adalah?',
        opsi_a:'Pensil', opsi_b:'Bola', opsi_c:'Buku', opsi_d:'Meja', jawaban:'b' },
      { pertanyaan:'Katak muda yang hidup di air dan bernapas dengan insang disebut?',
        opsi_a:'Kecebong', opsi_b:'Larva', opsi_c:'Nimfa', opsi_d:'Pupa', jawaban:'a' },
      { pertanyaan:'Energi panas matahari diubah menjadi energi listrik oleh?',
        opsi_a:'PLTA', opsi_b:'PLTU', opsi_c:'Panel surya', opsi_d:'Generator', jawaban:'c' },
    ],
    // Kelas 4 — 10 soal
    4: [
      { pertanyaan:'Tulang yang melindungi jantung dan paru-paru adalah?',
        opsi_a:'Tulang tengkorak', opsi_b:'Tulang rusuk', opsi_c:'Tulang belakang', opsi_d:'Tulang kering', jawaban:'b' },
      { pertanyaan:'Urutan daur hidup kupu-kupu yang benar adalah?',
        opsi_a:'Telur-ulat-kupu-pupa', opsi_b:'Telur-pupa-ulat-kupu',
        opsi_c:'Telur-ulat-pupa-kupu', opsi_d:'Ulat-telur-pupa-kupu', jawaban:'c' },
      { pertanyaan:'Perubahan wujud dari cair menjadi gas disebut?',
        opsi_a:'Membeku', opsi_b:'Mencair', opsi_c:'Menguap', opsi_d:'Mengembun', jawaban:'c' },
      { pertanyaan:'Minyak bumi termasuk sumber daya alam?',
        opsi_a:'Dapat diperbarui', opsi_b:'Tidak dapat diperbarui', opsi_c:'Hayati', opsi_d:'Terbarukan', jawaban:'b' },
      { pertanyaan:'Bayi lahir punya sekitar berapa tulang?',
        opsi_a:'206', opsi_b:'250', opsi_c:'300', opsi_d:'350', jawaban:'c' },
      { pertanyaan:'Kapur barus yang menyusut lama-lama adalah contoh?',
        opsi_a:'Mencair', opsi_b:'Menguap', opsi_c:'Menyublim', opsi_d:'Membeku', jawaban:'c' },
      { pertanyaan:'Daur hidup yang TIDAK mengalami metamorfosis adalah?',
        opsi_a:'Kupu-kupu', opsi_b:'Nyamuk', opsi_c:'Ayam', opsi_d:'Katak', jawaban:'c' },
      { pertanyaan:'Contoh sumber daya alam yang dapat diperbarui adalah?',
        opsi_a:'Batu bara', opsi_b:'Minyak bumi', opsi_c:'Gas alam', opsi_d:'Air', jawaban:'d' },
      { pertanyaan:'Fungsi rangka pada manusia adalah, KECUALI?',
        opsi_a:'Menegakkan tubuh', opsi_b:'Melindungi organ', opsi_c:'Tempat otot melekat', opsi_d:'Memompa darah', jawaban:'d' },
      { pertanyaan:'Perubahan wujud dari gas menjadi cair disebut?',
        opsi_a:'Menguap', opsi_b:'Mengembun', opsi_c:'Membeku', opsi_d:'Menyublim', jawaban:'b' },
    ],
    // Kelas 5 — 15 soal
    5: [
      { pertanyaan:'Organ yang memompa darah ke seluruh tubuh adalah?',
        opsi_a:'Paru-paru', opsi_b:'Jantung', opsi_c:'Ginjal', opsi_d:'Hati', jawaban:'b' },
      { pertanyaan:'Adaptasi bentuk tubuh pada makhluk hidup disebut adaptasi?',
        opsi_a:'Fisiologi', opsi_b:'Tingkah laku', opsi_c:'Morfologi', opsi_d:'Habitat', jawaban:'c' },
      { pertanyaan:'Sifat cahaya yang membuat pensil dalam gelas air terlihat bengkok adalah?',
        opsi_a:'Dipantulkan', opsi_b:'Diserap', opsi_c:'Dibiaskan', opsi_d:'Disebarkan', jawaban:'c' },
      { pertanyaan:'Proses penguapan air oleh panas matahari dalam siklus air disebut?',
        opsi_a:'Kondensasi', opsi_b:'Presipitasi', opsi_c:'Evaporasi', opsi_d:'Infiltrasi', jawaban:'c' },
      { pertanyaan:'Pembuluh darah yang membawa darah dari jantung ke seluruh tubuh disebut?',
        opsi_a:'Vena', opsi_b:'Kapiler', opsi_c:'Arteri', opsi_d:'Aorta saja', jawaban:'c' },
      { pertanyaan:'Unta menyimpan cadangan makanan di punuk adalah adaptasi?',
        opsi_a:'Morfologi', opsi_b:'Fisiologi', opsi_c:'Tingkah laku', opsi_d:'Struktural', jawaban:'b' },
      { pertanyaan:'Cahaya putih yang diurai menjadi 7 warna pelangi adalah fenomena?',
        opsi_a:'Refleksi', opsi_b:'Refraksi', opsi_c:'Dispersi', opsi_d:'Difraksi', jawaban:'c' },
      { pertanyaan:'Uap air yang mendingin dan berubah menjadi awan disebut?',
        opsi_a:'Evaporasi', opsi_b:'Kondensasi', opsi_c:'Presipitasi', opsi_d:'Infiltrasi', jawaban:'b' },
      { pertanyaan:'Darah terdiri dari KECUALI?',
        opsi_a:'Sel darah merah', opsi_b:'Sel darah putih', opsi_c:'Trombosit', opsi_d:'Hemoglobin saja', jawaban:'d' },
      { pertanyaan:'Burung bermigrasi ke daerah hangat adalah contoh adaptasi?',
        opsi_a:'Morfologi', opsi_b:'Fisiologi', opsi_c:'Tingkah laku', opsi_d:'Struktural', jawaban:'c' },
      { pertanyaan:'Cermin memantulkan cahaya adalah sifat cahaya?',
        opsi_a:'Merambat lurus', opsi_b:'Dipantulkan', opsi_c:'Dibiaskan', opsi_d:'Dispersi', jawaban:'b' },
      { pertanyaan:'Air yang meresap ke dalam tanah dalam siklus air disebut?',
        opsi_a:'Evaporasi', opsi_b:'Presipitasi', opsi_c:'Kondensasi', opsi_d:'Infiltrasi', jawaban:'d' },
      { pertanyaan:'Sel darah putih berfungsi untuk?',
        opsi_a:'Membawa oksigen', opsi_b:'Membekukan darah', opsi_c:'Melawan penyakit', opsi_d:'Memberi warna darah', jawaban:'c' },
      { pertanyaan:'Kaki bebek yang berselaput adalah contoh adaptasi?',
        opsi_a:'Fisiologi', opsi_b:'Morfologi', opsi_c:'Tingkah laku', opsi_d:'Perilaku', jawaban:'b' },
      { pertanyaan:'Proses jatuhnya air hujan ke bumi disebut?',
        opsi_a:'Evaporasi', opsi_b:'Kondensasi', opsi_c:'Presipitasi', opsi_d:'Transpirasi', jawaban:'c' },
    ],
    // Kelas 6 — 15 soal
    6: [
      { pertanyaan:'Perkembangbiakan tumbuhan dengan cara cangkok termasuk?',
        opsi_a:'Generatif', opsi_b:'Vegetatif alami', opsi_c:'Vegetatif buatan', opsi_d:'Penyerbukan', jawaban:'c' },
      { pertanyaan:'Planet terbesar di tata surya adalah?',
        opsi_a:'Saturnus', opsi_b:'Uranus', opsi_c:'Jupiter', opsi_d:'Neptunus', jawaban:'c' },
      { pertanyaan:'Hukum Newton yang menyatakan setiap aksi ada reaksi adalah hukum ke?',
        opsi_a:'I', opsi_b:'II', opsi_c:'III', opsi_d:'IV', jawaban:'c' },
      { pertanyaan:'Pergerakan lempeng tektonik dapat menyebabkan?',
        opsi_a:'Hujan lebat', opsi_b:'Gempa bumi', opsi_c:'Pelangi', opsi_d:'Angin topan', jawaban:'b' },
      { pertanyaan:'Rotasi Bumi menyebabkan?',
        opsi_a:'Pergantian musim', opsi_b:'Gerhana matahari', opsi_c:'Siang dan malam', opsi_d:'Pasang surut', jawaban:'c' },
      { pertanyaan:'Tanaman pisang berkembang biak secara vegetatif melalui?',
        opsi_a:'Biji', opsi_b:'Setek', opsi_c:'Tunas', opsi_d:'Cangkok', jawaban:'c' },
      { pertanyaan:'Urutan lapisan Bumi dari luar ke dalam yang benar adalah?',
        opsi_a:'Inti-mantel-kerak', opsi_b:'Kerak-mantel-inti', opsi_c:'Mantel-kerak-inti', opsi_d:'Kerak-inti-mantel', jawaban:'b' },
      { pertanyaan:'Planet yang letaknya paling jauh dari Matahari adalah?',
        opsi_a:'Uranus', opsi_b:'Saturnus', opsi_c:'Jupiter', opsi_d:'Neptunus', jawaban:'d' },
      { pertanyaan:'Gaya gesek bermanfaat dalam kehidupan sehari-hari, yaitu?',
        opsi_a:'Membuat benda sulit digeser', opsi_b:'Membantu kita berjalan', opsi_c:'Memperlambat semua gerak', opsi_d:'Jawaban a dan b benar', jawaban:'d' },
      { pertanyaan:'Pemanasan global disebabkan oleh?',
        opsi_a:'Rotasi Bumi', opsi_b:'Gas rumah kaca berlebihan', opsi_c:'Hujan lebat', opsi_d:'Angin kencang', jawaban:'b' },
      { pertanyaan:'Revolusi Bumi mengelilingi Matahari memakan waktu?',
        opsi_a:'24 jam', opsi_b:'30 hari', opsi_c:'365,25 hari', opsi_d:'12 bulan kalender', jawaban:'c' },
      { pertanyaan:'Erosi tanah dapat dicegah dengan cara?',
        opsi_a:'Menebang hutan', opsi_b:'Reboisasi', opsi_c:'Membakar sampah', opsi_d:'Membangun pabrik', jawaban:'b' },
      { pertanyaan:'Penyerbukan dibantu angin disebut?',
        opsi_a:'Entomofili', opsi_b:'Ornitofili', opsi_c:'Anemofili', opsi_d:'Hidrofili', jawaban:'c' },
      { pertanyaan:'Komet berbeda dari asteroid karena komet memiliki?',
        opsi_a:'Ukuran yang lebih besar', opsi_b:'Ekor yang terlihat', opsi_c:'Orbit yang tetap', opsi_d:'Inti yang padat saja', jawaban:'b' },
      { pertanyaan:'Gaya berat benda dihitung dengan rumus?',
        opsi_a:'W = m + g', opsi_b:'W = m × g', opsi_c:'W = m / g', opsi_d:'W = g / m', jawaban:'b' },
    ],
  },

  // ── HELPER ─────────────────────────────────────────────────
  getSoalByKelas(kelas) {
    const soalKelas = this.soal[kelas] || this.soal[1];
    return soalKelas.map((s, i) => ({ ...s, id: 0, _fallback: true }));
  },

  getTopikByKelas(kelas) {
    return this.topik.filter(t => t.kelas === kelas);
  },

  getMateriByJudul(judul) {
    return this.materi[judul] || null;
  },
};

// Expose global
window.SAINTARA_DATA = SAINTARA_DATA;