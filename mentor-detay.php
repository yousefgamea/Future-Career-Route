<?php
session_start();
include 'baglanti.php';

$mentorID = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($mentorID <= 0) {
    header("Location: mentor-listesi.php");
    exit();
}

$hata = '';
$basari = '';

// Mentor bilgisi + varsa bağlı olduğu program (mentor.ProgramID)
$stmt = $conn->prepare("
    SELECT m.MentorID, m.Isim, m.Soyisim, m.DersAdi, m.FotoURL, m.Video, m.ProgramID AS MentorProgramID, m.Onaylandi
    FROM mentor m WHERE m.MentorID = ?
");
$stmt->bind_param("i", $mentorID);
$stmt->execute();
$mentor = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Onaylanmamış (yönetici onayı bekleyen) mentorların profili henüz herkese
// açık değil - kendi giriş yaptığı sayfa hariç.
if (!$mentor || (int) $mentor['Onaylandi'] === 0) {
    echo "Mentor bulunamadı.";
    exit();
}

// Bu mentorun açtığı TÜM programlar (artık bir mentor birden fazla program
// açabiliyor) - her biri kendi katılımcı sayısıyla birlikte.
$programlarStmt = $conn->prepare("
    SELECT p.ProgramID, p.ProgramAdi, p.Aciklama, p.Kontenjan,
           (SELECT COUNT(*) FROM kullanici k WHERE k.ProgramID = p.ProgramID) AS KatilimciSayisi
    FROM program p
    WHERE p.MentorID = ?
    ORDER BY p.ProgramID DESC
");
$programlarStmt->bind_param("i", $mentorID);
$programlarStmt->execute();
$mentorunProgramlari = $programlarStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$programlarStmt->close();

// Yorum ekleme (sadece giriş yapmış öğrenci)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['yorum_ekle'])) {
    if (empty($_SESSION['kullanici_id'])) {
        $hata = "Yorum bırakmak için öğrenci hesabınla giriş yapmalısın.";
    } else {
        $yorumMetni = trim($_POST['yorum'] ?? '');
        if (empty($yorumMetni)) {
            $hata = "Lütfen bir yorum yaz.";
        } else {
            $kullaniciID = $_SESSION['kullanici_id'];

            // Bir öğrenci aynı mentora sadece 1 yorum bırakabilir - tekrar
            // gönderirse yeni bir satır eklemek yerine mevcut yorumunu
            // günceller (spam'i önlemek için).
            $varMiStmt = $conn->prepare("SELECT YorumID FROM yorum WHERE MentorID = ? AND KullaniciID = ?");
            $varMiStmt->bind_param("ii", $mentorID, $kullaniciID);
            $varMiStmt->execute();
            $mevcutYorum = $varMiStmt->get_result()->fetch_assoc();
            $varMiStmt->close();

            if ($mevcutYorum) {
                $guncelleYorumStmt = $conn->prepare("UPDATE yorum SET Yorum = ? WHERE YorumID = ?");
                $guncelleYorumStmt->bind_param("si", $yorumMetni, $mevcutYorum['YorumID']);
                if ($guncelleYorumStmt->execute()) {
                    $basari = "Yorumun güncellendi.";
                } else {
                    $hata = "Yorum güncellenirken bir hata oluştu.";
                }
                $guncelleYorumStmt->close();
            } else {
                $ekleStmt = $conn->prepare("INSERT INTO yorum (MentorID, KullaniciID, Yorum) VALUES (?, ?, ?)");
                $ekleStmt->bind_param("iis", $mentorID, $kullaniciID, $yorumMetni);
                if ($ekleStmt->execute()) {
                    $basari = "Yorumun eklendi.";
                } else {
                    $hata = "Yorum eklenirken bir hata oluştu.";
                }
                $ekleStmt->close();
            }
        }
    }
}

// Programa katılma (sadece giriş yapmış öğrenci) - artık formdan hangi
// programa katılınacağı (program_id) geliyor, çünkü mentorun birden fazla
// programı olabilir.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['programa_katil'])) {
    $secilenProgramID = isset($_POST['program_id']) ? (int) $_POST['program_id'] : 0;

    // Seçilen program gerçekten bu mentora mı ait, listede var mı kontrol et
    $secilenProgram = null;
    foreach ($mentorunProgramlari as $p) {
        if ((int) $p['ProgramID'] === $secilenProgramID) {
            $secilenProgram = $p;
            break;
        }
    }

    if (empty($_SESSION['kullanici_id'])) {
        $hata = "Bir programa katılmak için öğrenci hesabınla giriş yapmalısın.";
    } elseif (!$secilenProgram) {
        $hata = "Böyle bir program bulunamadı.";
    } elseif (!empty($secilenProgram['Kontenjan']) && $secilenProgram['KatilimciSayisi'] >= (int) $secilenProgram['Kontenjan']) {
        $hata = "Bu programın kontenjanı dolu.";
    } else {
        $kullaniciID = $_SESSION['kullanici_id'];

        // Kullanıcının hâlihazırda başka bir programı varsa, geçmişe kaydet
        $eskiStmt = $conn->prepare("SELECT ProgramID FROM kullanici WHERE KullaniciID = ?");
        $eskiStmt->bind_param("i", $kullaniciID);
        $eskiStmt->execute();
        $eskiProgram = $eskiStmt->get_result()->fetch_assoc();
        $eskiStmt->close();

        if (!empty($eskiProgram['ProgramID']) && $eskiProgram['ProgramID'] != $secilenProgramID) {
            $gecmisStmt = $conn->prepare("INSERT INTO gecmis (KullaniciID, ProgramID) VALUES (?, ?)");
            $gecmisStmt->bind_param("ii", $kullaniciID, $eskiProgram['ProgramID']);
            $gecmisStmt->execute();
            $gecmisStmt->close();
        }

        $guncelleStmt = $conn->prepare("UPDATE kullanici SET ProgramID = ? WHERE KullaniciID = ?");
        $guncelleStmt->bind_param("ii", $secilenProgramID, $kullaniciID);
        if ($guncelleStmt->execute()) {
            $basari = "Bu programa katıldın!";
            // Katılımcı sayısını ekranda güncel göstermek için listeyi tazele
            foreach ($mentorunProgramlari as &$p) {
                if ((int) $p['ProgramID'] === $secilenProgramID) {
                    $p['KatilimciSayisi']++;
                }
            }
            unset($p);
        } else {
            $hata = "Programa katılırken bir hata oluştu.";
        }
        $guncelleStmt->close();
    }
}

// Bu mentora yazılmış yorumları çek
$yorumStmt = $conn->prepare("
    SELECT y.Yorum, k.Isim, k.Soyisim
    FROM yorum y
    JOIN kullanici k ON y.KullaniciID = k.KullaniciID
    WHERE y.MentorID = ?
    ORDER BY y.YorumID DESC
");
$yorumStmt->bind_param("i", $mentorID);
$yorumStmt->execute();
$yorumlar = $yorumStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$yorumStmt->close();

// Giriş yapmış öğrencinin bu mentora daha önce yazdığı yorumu varsa,
// formu boş göndermek yerine üzerine yazabilmesi için önceden dolduruyoruz.
$benimYorumum = '';
if (!empty($_SESSION['kullanici_id'])) {
    $benimYorumStmt = $conn->prepare("SELECT Yorum FROM yorum WHERE MentorID = ? AND KullaniciID = ?");
    $benimYorumStmt->bind_param("ii", $mentorID, $_SESSION['kullanici_id']);
    $benimYorumStmt->execute();
    $benimYorumSonuc = $benimYorumStmt->get_result()->fetch_assoc();
    $benimYorumStmt->close();
    if ($benimYorumSonuc) {
        $benimYorumum = $benimYorumSonuc['Yorum'];
    }
}
?>
<!doctype html>
<html lang="en">
  <head>
    <title>Future Career Route</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <link rel="stylesheet" href="css/custom-bs.css">
    <link rel="stylesheet" href="css/jquery.fancybox.min.css">
    <link rel="stylesheet" href="css/bootstrap-select.min.css">
    <link rel="stylesheet" href="fonts/icomoon/style.css">
    <link rel="stylesheet" href="fonts/line-icons/style.css">
    <link rel="stylesheet" href="css/owl.carousel.min.css">
    <link rel="stylesheet" href="css/animate.min.css">
    <link rel="stylesheet" href="css/quill.snow.css">

    <!-- MAIN CSS -->
    <link rel="stylesheet" href="css/style.css">
    <style>
      .profile-card {
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
      }
    </style>
  </head>
  <body id="top">

  <div id="overlayer"></div>
  <div class="loader">
    <div class="spinner-border text-primary" role="status">
      <span class="sr-only">Yükleniyor...</span>
    </div>
  </div>

<div class="site-wrap">

    <div class="site-mobile-menu site-navbar-target">
      <div class="site-mobile-menu-header">
        <div class="site-mobile-menu-close mt-3">
          <span class="icon-close2 js-menu-toggle"></span>
        </div>
      </div>
      <div class="site-mobile-menu-body"></div>
    </div> <!-- .site-mobile-menu -->

    <!-- NAVBAR -->
    <header class="site-navbar mt-3">
      <div class="container-fluid">
        <div class="row align-items-center">
          <div class="site-logo col-6">
            <a href="index.php">
              <img src="images/Logo.png" alt="logo" style="width:110px; height:auto;">
            </a>
          </div>
          <nav class="mx-auto site-navigation">
            <ul class="site-menu js-clone-nav d-none d-xl-block ml-0 pl-0">
              <li><a href="index.php" class="nav-link">Ana Sayfa</a></li>
              <li><a href="anket.php">Anket</a></li>
              <li><a href="mentor-listesi.php" class="active">Mentorlar</a></li>
              <li><a href="about.php">Hakkımızda</a></li>
              <li><a href="blog.php">Programlar</a></li>
              <li><a href="contact.php">İletişim</a></li>
              <?php if (!empty($_SESSION['kullanici_id']) || !empty($_SESSION['mentor_id'])): ?>
              <li class="d-lg-none"><a href="<?php echo !empty($_SESSION['mentor_id']) ? 'mentorun-profili.php' : 'kullanicinin-profili.php'; ?>">Profilim</a></li>
              <li class="d-lg-none"><a href="logout.php">Çıkış Yap</a></li>
              <?php else: ?>
              <li class="d-lg-none"><a href="post-job.php">Giriş Yap</a></li>
              <li class="d-lg-none"><a href="kayit-ol-secigi.php">Kayıt Ol</a></li>
              <?php endif; ?>
            </ul>
          </nav>
          <div class="right-cta-menu text-right d-flex aligin-items-center col-6">
            <div class="ml-auto d-none d-lg-block">
              <?php if (!empty($_SESSION['kullanici_id']) || !empty($_SESSION['mentor_id'])): ?>
              <a href="<?php echo !empty($_SESSION['mentor_id']) ? 'mentorun-profili.php' : 'kullanicinin-profili.php'; ?>" class="btn btn-outline-white border-width-2 d-none d-lg-inline-block"><span class="mr-2 icon-user"></span>Profilim</a>
              <a href="logout.php" class="btn btn-primary border-width-2 d-none d-lg-inline-block"><span class="mr-2 icon-lock_outline"></span>Çıkış Yap</a>
              <?php else: ?>
              <a href="post-job.php" class="btn btn-outline-white border-width-2 d-none d-lg-inline-block"><span class="mr-2 icon-lock_outline"></span>Giriş Yap</a>
              <a href="kayit-ol-secigi.php" class="btn btn-primary border-width-2 d-none d-lg-inline-block"><span class="mr-2 icon-add"></span>Kayıt Ol</a>
              <?php endif; ?>
            </div>
            <a href="#" class="site-menu-toggle js-menu-toggle d-inline-block d-xl-none mt-lg-2 ml-3" onclick="OnePageNavigation()"><span class="icon-menu h3 m-0 p-0 mt-2"></span></a>
          </div>
        </div>
      </div>
    </header>

    <!-- HOME -->
    <section class="section-hero overlay inner-page bg-image" style="background-image: url('images/hero_1.jpg');" id="home-section">
      <div class="container">
        <div class="row">
          <div class="col-md-7">
            <h1 class="text-white font-weight-bold"><?php echo htmlspecialchars(trim($mentor['Isim'] . ' ' . $mentor['Soyisim'])); ?></h1>
            <div class="custom-breadcrumbs">
              <a href="mentor-listesi.php">Mentorlar</a> <span class="mx-2 slash">/</span>
              <span class="text-white"><strong><?php echo htmlspecialchars(trim($mentor['Isim'] . ' ' . $mentor['Soyisim'])); ?></strong></span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="site-section">
      <div class="container">

        <?php if ($hata): ?>
          <div class="alert alert-danger"><?php echo htmlspecialchars($hata); ?></div>
        <?php endif; ?>
        <?php if ($basari): ?>
          <div class="alert alert-success"><?php echo htmlspecialchars($basari); ?></div>
        <?php endif; ?>

        <div class="row">
          <div class="col-md-4 mb-4 text-center">
            <img src="<?php echo !empty($mentor['FotoURL']) ? htmlspecialchars($mentor['FotoURL']) : 'images/person_1.jpg'; ?>" alt="Mentor" class="rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
            <h4><?php echo htmlspecialchars(trim($mentor['Isim'] . ' ' . $mentor['Soyisim'])); ?></h4>
            <p><?php echo htmlspecialchars($mentor['DersAdi']); ?></p>

            <?php if (empty($mentorunProgramlari)): ?>
              <p><em>Bu mentor henüz bir program açmamış.</em></p>
            <?php else: ?>
              <p><small><?php echo count($mentorunProgramlari); ?> program</small></p>
            <?php endif; ?>
          </div>

          <div class="col-md-8 mb-4">
            <?php if (!empty($mentorunProgramlari)): ?>
              <?php foreach ($mentorunProgramlari as $p): ?>
                <div class="profile-card mb-3">
                  <h5><?php echo htmlspecialchars($p['ProgramAdi'] ?: 'Program'); ?></h5>
                  <?php if (!empty($p['Aciklama'])): ?>
                    <p><?php echo nl2br(htmlspecialchars($p['Aciklama'])); ?></p>
                  <?php endif; ?>
                  <?php if (!empty($p['Kontenjan'])): ?>
                    <p><small><?php echo (int) $p['KatilimciSayisi']; ?> / <?php echo (int) $p['Kontenjan']; ?> öğrenci katıldı</small></p>
                  <?php else: ?>
                    <p><small><?php echo (int) $p['KatilimciSayisi']; ?> öğrenci katıldı</small></p>
                  <?php endif; ?>
                  <form method="POST">
                    <input type="hidden" name="program_id" value="<?php echo (int) $p['ProgramID']; ?>">
                    <?php if (!empty($p['Kontenjan']) && $p['KatilimciSayisi'] >= (int) $p['Kontenjan']): ?>
                      <button type="button" class="btn btn-secondary btn-sm" disabled>Kontenjan Dolu</button>
                    <?php else: ?>
                      <button type="submit" name="programa_katil" value="1" class="btn btn-primary btn-sm">Bu Programa Katıl</button>
                    <?php endif; ?>
                  </form>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>

            <?php if (!empty($mentor['Video'])): ?>
              <div class="profile-card">
                <h5>Tanıtım Videosu</h5>
                <p><a href="<?php echo htmlspecialchars($mentor['Video']); ?>" target="_blank" rel="noopener">Videoyu Görüntüle</a></p>
              </div>
            <?php endif; ?>

            <div class="profile-card">
              <h5>Öğrenci Yorumları</h5>
              <?php if (!empty($yorumlar)): ?>
                <?php foreach ($yorumlar as $y): ?>
                  <blockquote>
                    <p>"<?php echo htmlspecialchars($y['Yorum']); ?>"</p>
                    <footer>— <?php echo htmlspecialchars(trim($y['Isim'] . ' ' . $y['Soyisim'])); ?></footer>
                  </blockquote>
                <?php endforeach; ?>
              <?php else: ?>
                <p>Henüz yorum yapılmamış.</p>
              <?php endif; ?>

              <?php if (!empty($_SESSION['kullanici_id'])): ?>
                <form method="POST" class="mt-3">
                  <div class="form-group">
                    <label><?php echo $benimYorumum !== '' ? 'Yorumunu Düzenle' : 'Yorum Yaz'; ?></label>
                    <textarea name="yorum" class="form-control" rows="3" required><?php echo htmlspecialchars($benimYorumum); ?></textarea>
                    <?php if ($benimYorumum !== ''): ?>
                      <small class="text-muted">Bu mentora zaten bir yorum bırakmışsın - gönderirsen yorumun güncellenir.</small>
                    <?php endif; ?>
                  </div>
                  <button type="submit" name="yorum_ekle" value="1" class="btn btn-outline-primary btn-sm"><?php echo $benimYorumum !== '' ? 'Yorumu Güncelle' : 'Yorumu Gönder'; ?></button>
                </form>
              <?php elseif (empty($_SESSION['mentor_id'])): ?>
                <p><a href="post-job.php">Giriş yap</a> ve yorum bırak.</p>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </section>

    <footer class="site-footer">

      <a href="#top" class="smoothscroll scroll-top">
        <span class="icon-keyboard_arrow_up"></span>
      </a>

      <div class="container">
        <div class="row mb-5">
          <div class="col-6 col-md-3 mb-4 mb-md-0">
            <h3>Trend Meslek Alanları</h3>
            <ul class="list-unstyled">
              <li><a href="Muhendislik.php">Mühendislik</a></li>
              <li><a href="tip.php">Tıp</a></li>
              <li><a href="hukuk.php">Hukuk</a></li>
              <li><a href="egitim.php">Eğitim</a></li>
            </ul>
          </div>
          <div class="col-6 col-md-3 mb-4 mb-md-0">
            <h3>Şirket</h3>
            <ul class="list-unstyled">
              <li><a href="about.php">Hakkımızda</a></li>
              <li><a href="anket.php">Anket</a></li>
              <li><a href="blog.php">Programlar</a></li>
            </ul>
          </div>
          <div class="col-6 col-md-3 mb-4 mb-md-0">
            <h3>Destek</h3>
            <ul class="list-unstyled">
              <li><a href="contact.php">Destek</a></li>
              <li><a href="gizlilik-politikasi.php">Privacy</a></li>
              <li><a href="hizmet-sartlari.php">Hizmet Şartları</a></li>
            </ul>
          </div>
          <div class="col-6 col-md-3 mb-4 mb-md-0">
            <h3>Bize Ulaşın</h3>
            <div class="footer-social">
              <a href="#"><span class="icon-facebook"></span></a>
              <a href="#"><span class="icon-twitter"></span></a>
              <a href="#"><span class="icon-instagram"></span></a>
              <a href="#"><span class="icon-linkedin"></span></a>
            </div>
          </div>
        </div>

        <div class="row text-center">
          <div class="col-12">
            <p class="copyright"><small>
              Telif hakkı &copy;<script>document.write(new Date().getFullYear());</script> Bütün hakkılar saklıdır
            </small></p>
          </div>
        </div>
      </div>
    </footer>

</div>

  <script src="js/jquery.min.js"></script>
  <script src="js/bootstrap.bundle.min.js"></script>
  <script src="js/isotope.pkgd.min.js"></script>
  <script src="js/stickyfill.min.js"></script>
  <script src="js/jquery.fancybox.min.js"></script>
  <script src="js/jquery.easing.1.3.js"></script>
  <script src="js/jquery.waypoints.min.js"></script>
  <script src="js/jquery.animateNumber.min.js"></script>
  <script src="js/owl.carousel.min.js"></script>
  <script src="js/quill.min.js"></script>
  <script src="js/bootstrap-select.min.js"></script>
  <script src="js/custom.js"></script>
  </body>
</html>
