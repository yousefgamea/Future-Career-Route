<?php
session_start();
include 'baglanti.php';

// Giriş yapılmamışsa bu sayfayı görüntülemeyi engelle
if (empty($_SESSION['mentor_id'])) {
    header("Location: post-job.php");
    exit();
}

// Mentor bilgilerini çek
$mentor_id = $_SESSION['mentor_id'];
// Not: CV sütunu LONGBLOB (gerçek dosya içeriği) olduğu için sayfa her
// açıldığında tüm dosyayı belleğe çekmemek adına burada seçmiyoruz;
// CV'nin var olup olmadığını küçük bir bayrakla kontrol edip indirme
// linkini cv-indir.php üzerinden ayrıca sunuyoruz.
$sql = "SELECT MentorID, Isim, Soyisim, Email, Telefon, DogumTarihi, FotoURL, DersAdi, Video, Sifre,
               (CV IS NOT NULL) AS CVVarMi
        FROM Mentor WHERE MentorID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $mentor_id);
$stmt->execute();
$result = $stmt->get_result();
$mentor = $result->fetch_assoc();

if (!$mentor) {
    echo "Mentor bilgileri alınamadı.";
    exit();
}

// Mentor artık birden fazla program açabiliyor. Oluşturma/düzenleme/silme
// ve üye listesi ayrı sayfalarda (program-olustur.php, program-duzenle.php,
// program-uyeler.php) - burada sadece özet gösteriyoruz.
$programlarStmt = $conn->prepare("
    SELECT p.ProgramID, p.ProgramAdi,
           (SELECT COUNT(*) FROM kullanici k WHERE k.ProgramID = p.ProgramID) AS KatilimciSayisi
    FROM program p
    WHERE p.MentorID = ?
    ORDER BY p.ProgramID DESC
");
$programlarStmt->bind_param("i", $mentor_id);
$programlarStmt->execute();
$mentorunProgramlari = $programlarStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$programlarStmt->close();

// Yorum silme (sadece kendisine yazılmış yorumu silebilir)
$yorumHata = '';
$yorumBasari = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['yorum_sil'])) {
    $silinecekYorumID = (int) $_POST['yorum_sil'];
    $silStmt = $conn->prepare("DELETE FROM yorum WHERE YorumID = ? AND MentorID = ?");
    $silStmt->bind_param("ii", $silinecekYorumID, $mentor_id);
    if ($silStmt->execute() && $silStmt->affected_rows > 0) {
        $yorumBasari = "Yorum silindi.";
    } else {
        $yorumHata = "Yorum silinemedi ya da sana ait değil.";
    }
    $silStmt->close();
}

// Bu mentora yazılan yorumları çek
$yorumStmt = $conn->prepare("
    SELECT y.YorumID, y.Yorum, k.Isim, k.Soyisim
    FROM yorum y
    JOIN kullanici k ON y.KullaniciID = k.KullaniciID
    WHERE y.MentorID = ?
    ORDER BY y.YorumID DESC
");
$yorumStmt->bind_param("i", $mentor_id);
$yorumStmt->execute();
$mentorunYorumlari = $yorumStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$yorumStmt->close();
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
        .profile-header {
  background-color: #007bff;
  color: white;
  padding: 20px;
  border-radius: 8px;
}

.profile-card {
  background-color: #f8f9fa;
  border: 1px solid #ddd;
  border-radius: 8px;
  padding: 20px;
  margin-bottom: 20px;
}

.badges img {
  width: 50px;
  margin-right: 10px;
}

.badge-icon {
  border-radius: 50%;
  border: 2px solid #007bff;
  padding: 5px;
}

.rounded-circle {
  border-radius: 50%;
  border: 3px solid #007bff;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.profile-mini-card {
  margin-bottom: 30px;
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
              <li><a href="index.php" class="nav-link active">Ana Sayfa</a></li>
              <li><a href="anket.php">Anket</a></li>
              <li><a href="mentor-listesi.php">Mentorlar</a></li>
              <li><a href="about.php">Hakkımızda</a></li>
              <!-- <li class="has-children">
                <a href="job-listings.php" class="active">Job Listings</a>
                <ul class="dropdown">
                  <li><a href="job-single.php">Job Single</a></li>
                  <li><a href="post-job.php" class="active">Post a Job</a></li>
                </ul>
              </li> -->
              <!-- <li class="has-children">
                <a href="services.php">Pages</a>
                <ul class="dropdown">
                  <li><a href="services.php">Services</a></li>
                  <li><a href="service-single.php">Service Single</a></li>
                  <li><a href="blog-single.php">Blog Single</a></li>
                  <li><a href="portfolio.php">Portfolio</a></li>
                  <li><a href="portfolio-single.php">Portfolio Single</a></li>
                  <li><a href="testimonials.php">Testimonials</a></li>
                  <li><a href="faq.php">Frequently Ask Questions</a></li>
                  <li><a href="gallery.php">Gallery</a></li>
                </ul>
              </li> -->
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
            <div class="ml-auto">
              <a href="logout.php" class="btn btn-outline-white border-width-2 d-none d-lg-inline-block"><span class="mr-2 icon-lock_outline"></span>Çıkış Yap</a> 
            
            </div>
            <a href="#" class="site-menu-toggle js-menu-toggle d-inline-block d-xl-none mt-lg-2 ml-3" onclick="OnePageNavigation()"><span class="icon-menu h3 m-0 p-0 mt-2"></span></a>
          </div>

        </div>
      </div>
    </header>

    <!-- HOME -->
    <section class="section-hero overlay inner-page bg-image" style="background-image: url('images/hero_1.jpg');" id="home-section">
      <!-- <div class="container">
        <div class="row">
          <div class="col-md-7">
            <h1 class="text-white font-weight-bold">Anket</h1>
            <div class="custom-breadcrumbs">
              <a href="#">Ana Sayfa</a> <span class="mx-2 slash">/</span>
              <span class="text-white"><strong>Anket</strong></span>
            </div>
          </div>
        </div>
      </div> -->
    </section>

    <div class="container mt-5">
        <!-- Mentorun Temel Bilgileri -->
        <div class="profile-header text-center">
            <div id="mentor-profile">
                <!-- Resim buraya dinamik olarak yüklenecek -->
              </div>
              
              <script>
                // Resmin URL'si
                const imageUrl = "images/person_1.jpg"; // Resmi proje klasörüne kaydettiyseniz doğru yolu kontrol edin.
              
                // HTML elementine resim ekleme
                const profileDiv = document.getElementById("mentor-profile");
                profileDiv.innerHTML = `
                 <img src="<?php echo !empty($mentor['FotoURL']) ? htmlspecialchars($mentor['FotoURL']) : 'images/person_1.jpg'; ?>"
     alt="Mentor Profili" class="rounded-circle" style="width: 120px; height: 120px;">

                `;
              </script>
              
              
              <h1><?php echo htmlspecialchars(trim($mentor['Isim'] . ' ' . $mentor['Soyisim'])); ?></h1>
          <p><?php echo htmlspecialchars($mentor['DersAdi']); ?> Mentoru</p>
        </div>

        <!-- Temel Bilgiler -->
        <div class="row mt-4">
          <div class="col-12">
            <div class="profile-card1">
              <div class="d-flex justify-content-between align-items-start flex-wrap">
                <h5>Temel Bilgiler</h5>
                <a href="mentor-bilgilerini-duzenle.php" class="btn btn-sm btn-primary">Bilgilerimi Düzenle</a>
              </div>
              <p><strong>Email:</strong> <?php echo htmlspecialchars($mentor['Email']); ?></p>
              <p><strong>Telefon numarası:</strong> <?php echo htmlspecialchars($mentor['Telefon']); ?></p>
              <p><strong>Doğum Tarihi:</strong> <?php echo htmlspecialchars($mentor['DogumTarihi']); ?></p>
              <p><strong>Ders/Uzmanlık Alanı:</strong> <?php echo htmlspecialchars($mentor['DersAdi']); ?></p>
              <?php if (!empty($mentor['CVVarMi'])): ?>
                <a href="cv-indir.php?id=<?php echo (int)$mentor['MentorID']; ?>" class="btn btn-outline-secondary mt-2">CV İndir</a>
              <?php endif; ?>
              <?php if (!empty($mentor['Video'])): ?>
                <a href="<?php echo htmlspecialchars($mentor['Video']); ?>" target="_blank" rel="noopener" class="btn btn-outline-secondary mt-2">Tanıtım Videomu Gör</a>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Programlarım -->
        <div class="row mt-4">
          <div class="col-12">
            <div class="profile-card1">
              <div class="d-flex justify-content-between align-items-start flex-wrap">
                <h5>Programlarım (<?php echo count($mentorunProgramlari); ?>)</h5>
                <a href="program-olustur.php" class="btn btn-sm btn-primary">
                  <?php echo !empty($mentorunProgramlari) ? 'Programlarımı Yönet' : 'Program Oluştur'; ?>
                </a>
              </div>

              <?php if (!empty($mentorunProgramlari)): ?>
                <ul class="mt-2">
                  <?php foreach ($mentorunProgramlari as $p): ?>
                    <li>
                      <?php echo htmlspecialchars($p['ProgramAdi']); ?>
                      — <?php echo (int) $p['KatilimciSayisi']; ?> öğrenci katıldı
                      (<a href="program-duzenle.php?id=<?php echo (int) $p['ProgramID']; ?>">düzenle</a>,
                      <a href="program-uyeler.php?id=<?php echo (int) $p['ProgramID']; ?>">üyeler</a>)
                    </li>
                  <?php endforeach; ?>
                </ul>
                <p>Mentor listesinde <a href="mentor-detay.php?id=<?php echo (int)$mentor['MentorID']; ?>">profilini</a> görüntüleyen öğrenciler artık programlarına katılabilir.</p>
              <?php else: ?>
                <p>Henüz bir programın yok. "Program Oluştur" butonuna basıp program adı, açıklama ve
                  kontenjan bilgisi girdiğinde öğrenciler mentor listesinden seni bulup katılabilir.</p>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Öğrenci Yorumları -->
        <div class="row mt-4">
          <div class="col-12">
            <div class="profile-card1">
              <h5>Öğrenci Yorumları</h5>
              <?php if ($yorumHata): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($yorumHata); ?></div>
              <?php endif; ?>
              <?php if ($yorumBasari): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($yorumBasari); ?></div>
              <?php endif; ?>
              <?php if (!empty($mentorunYorumlari)): ?>
                <?php foreach ($mentorunYorumlari as $y): ?>
                  <blockquote class="d-flex justify-content-between align-items-start">
                    <div>
                      <p>"<?php echo htmlspecialchars($y['Yorum']); ?>"</p>
                      <footer>— <?php echo htmlspecialchars(trim($y['Isim'] . ' ' . $y['Soyisim'])); ?></footer>
                    </div>
                    <form method="POST" onsubmit="return confirm('Bu yorumu silmek istediğine emin misin?');" class="ml-3">
                      <input type="hidden" name="yorum_sil" value="<?php echo (int) $y['YorumID']; ?>">
                      <button type="submit" class="btn btn-outline-danger btn-sm">Sil</button>
                    </form>
                  </blockquote>
                <?php endforeach; ?>
              <?php else: ?>
                <p>Henüz bir öğrenci yorum bırakmadı.</p>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
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
              <li><a href="mentor-listesi.php">Mentorlar</a></li>
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
            <h3>Bizimle Ulaş</h3>
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
              <!-- Link back to Colorlib can't be removed. Template is licensed under CC BY 3.0. -->
              Telif hakkı &copy;<script>document.write(new Date().getFullYear());</script> Bütün haklar saklıdır <a href="https://colorlib.com" target="_blank" ></a>
            <!-- Link back to Colorlib can't be removed. Template is licensed under CC BY 3.0. --></small></p>
          </div>
        </div>
      </div>
    </footer>
  
  </div>

    <!-- SCRIPTS -->
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

    <script src="js/rozet.js"></script>
   
   
     
  </body>
</html>