<?php
session_start();
include 'baglanti.php'; // Veritabanı bağlantısı

// Giriş yapılmamışsa bu sayfayı görüntülemeyi engelle (önceden burada
// oturum kontrolü hiç yoktu ve $_SESSION['kullanici_id'] boşken sayfa
// "Kullanıcı bilgileri alınamadı." diyip patlıyordu).
if (empty($_SESSION['kullanici_id'])) {
    header("Location: post-job.php");
    exit();
}

$userID = $_SESSION['kullanici_id'];

// Kullanıcı tablosundan profilde gösterilecek tüm gerçek bilgileri al.
// Rozet (rozet tablosu) ve bağlı olduğu program (program + mentor) bilgisini
// de aynı sorguda LEFT JOIN ile çekiyoruz - kullanıcının RozetID/ProgramID'si
// boşsa (henüz rozet kazanmadıysa / bir programa katılmadıysa) bu alanlar
// NULL gelir, sayfada ona göre "henüz yok" gösteriyoruz.
$sql = "
    SELECT k.Isim, k.Soyisim, k.Email, k.TelefonNumarasi AS Telefon, k.DogumTarihi, k.Sinif, k.Alan,
           r.RozetPuani,
           p.ProgramID AS AktifProgramID, p.ProgramAdi AS ProgramAdi, m.MentorID AS ProgramMentorID,
           m.Isim AS ProgramMentorIsim, m.Soyisim AS ProgramMentorSoyisim, m.DersAdi AS ProgramDersAdi
    FROM kullanici k
    LEFT JOIN rozet r ON k.RozetID = r.RozetID
    LEFT JOIN program p ON k.ProgramID = p.ProgramID
    LEFT JOIN mentor m ON p.MentorID = m.MentorID
    WHERE k.KullaniciID = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    echo "Kullanıcı bilgileri alınamadı.";
    exit();
}

// Geçmiş programlar (gecmis tablosu - kullanıcının daha önce katıldığı
// programlar). Basit bir junction tablosu olduğu için sadece program ve
// o programı yürüten mentor bilgisini gösteriyoruz.
$gecmisStmt = $conn->prepare("
    SELECT g.ProgramID, p.ProgramAdi, m.Isim, m.Soyisim, m.DersAdi
    FROM gecmis g
    JOIN program p ON g.ProgramID = p.ProgramID
    JOIN mentor m ON p.MentorID = m.MentorID
    WHERE g.KullaniciID = ?
");
$gecmisStmt->bind_param("i", $userID);
$gecmisStmt->execute();
$gecmisListesi = $gecmisStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$gecmisStmt->close();

// Kayıt formundaki "Alan" alanı kod olarak (1,2,3,4) saklanıyor;
// profilde okunaklı olması için isme çeviriyoruz.
$alanIsimleri = [
    '1' => 'Sayısal',
    '2' => 'Sözel',
    '3' => 'Eşit Ağırlık',
    '4' => 'Dil',
];
$alanGosterim = $alanIsimleri[$user['Alan']] ?? ($user['Alan'] ?: '-');

// Not: Bu sayfada önceden "şifre değiştirme" alanları doğrulanıyordu
// ama karşılığında ne bir form ne de bir UPDATE sorgusu vardı - yani
// sayfa her açıldığında (POST olmasa bile) hataya düşen ölü kod
// çalışıyordu. Şifre değiştirme özelliği "Bilgilerimi Düzenle" akışına
// (kullanici-bilgilerini-duzenle.php) taşındı.
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
          color: white;
          text-align: center;
          padding: 20px 0;
        }
        .profile-card1 {
          box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
          border-radius: 8px;
          padding: 20px;
          margin: 10px 0;
          height: 100%;
        }
        .profile-card {
          box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
          border-radius: 8px;
          padding: 20px;
          margin: 10px 0;
          height: 100%;
        }
        .progress-bar {
          background-color: #007bff;
        }
        .profile-mini-card {
          margin-bottom: 30px;
        }

        .badges {
  display: flex;
  gap: 15px;
  flex-wrap: wrap;
  justify-content: start;
}

.badge {
  text-align: center;
  font-size: 14px;
  color: #555;
}

.badge img {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  border: 2px solid #007bff;
  padding: 5px;
  background-color: white;
}

.points {
  background-color: #f8f9fa;
  padding: 10px;
  border-radius: 8px;
  border: 1px solid #ddd;
}

    .error-message {
        color: red;
        font-size: 0.8em;
        margin-top: 5px;
    }
    .success-icon {
        color: green;
        margin-left: 10px;
    }
    .file-feedback {
        display: inline-block;
        margin-left: 10px;
    }
    .required-field::after {
        content: " *";
        color: red;
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
      
      
    </section>

    
    <section class="site-section">
      <div class="profile-header">
        <h1><?php echo htmlspecialchars(trim($user['Isim'] . ' ' . $user['Soyisim'])); ?></h1>
      </div>
      <div class="container">
        <div class="row">
          <div class="col-md-8 mb-4">
            <div class="profile-card1">
              <div class="d-flex justify-content-between align-items-start flex-wrap">
                <h5>Temel Bilgiler</h5>
                <a href="kullanici-bilgilerini-duzenle.php" class="btn btn-sm btn-primary">Bilgilerimi Düzenle</a>
              </div>
              <p><strong>Ad Soyad:</strong> <?php echo htmlspecialchars(trim($user['Isim'] . ' ' . $user['Soyisim'])); ?></p>
              <p><strong>Email:</strong> <?php echo htmlspecialchars($user['Email']); ?></p>
              <p><strong>Telefon numarası:</strong> <?php echo htmlspecialchars($user['Telefon']); ?></p>
              <p><strong>Doğum Tarihi:</strong> <?php echo htmlspecialchars($user['DogumTarihi']); ?></p>
              <p><strong>Sınıf:</strong> <?php echo htmlspecialchars($user['Sinif']); ?></p>
              <p><strong>Alan:</strong> <?php echo htmlspecialchars($alanGosterim); ?></p>
              <p><strong>Rol:</strong> Kullanıcı (Menti)</p>
            </div>
          </div>

          <div class="col-md-4 mb-4">
            <div class="profile-card">
              <h5>Rozet</h5>
              <?php if ($user['RozetPuani'] !== null): ?>
                <p><strong>Rozet Puanı:</strong> <?php echo (int) $user['RozetPuani']; ?></p>
              <?php else: ?>
                <p>Henüz bir rozet kazanmadın.</p>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-md-6 mb-4">
            <div class="profile-card">
              <h5>Bağlı Olduğun Program</h5>
              <?php if (!empty($user['AktifProgramID'])): ?>
                <p><strong><?php echo htmlspecialchars($user['ProgramAdi'] ?: 'Program'); ?></strong></p>
                <p><strong>Mentor:</strong> <?php echo htmlspecialchars(trim($user['ProgramMentorIsim'] . ' ' . $user['ProgramMentorSoyisim'])); ?></p>
                <p><strong>Ders/Alan:</strong> <?php echo htmlspecialchars($user['ProgramDersAdi']); ?></p>
                <a href="program-detay.php?id=<?php echo (int) $user['AktifProgramID']; ?>" class="btn btn-outline-primary btn-sm">Programın İçeriğini Gör</a>
              <?php else: ?>
                <p>Henüz bir programa katılmadın. <a href="mentor-listesi.php">Mentorları incele</a> ve bir programa katıl.</p>
              <?php endif; ?>
            </div>
          </div>
          <div class="col-md-6 mb-4">
            <div class="profile-card">
              <h5>Geçmiş Programların</h5>
              <?php if (!empty($gecmisListesi)): ?>
                <ul>
                  <?php foreach ($gecmisListesi as $g): ?>
                    <li><a href="program-detay.php?id=<?php echo (int) $g['ProgramID']; ?>"><?php echo htmlspecialchars($g['ProgramAdi'] ?: 'Program'); ?></a> — <?php echo htmlspecialchars(trim($g['Isim'] . ' ' . $g['Soyisim'])); ?> (<?php echo htmlspecialchars($g['DersAdi']); ?>)</li>
                  <?php endforeach; ?>
                </ul>
              <?php else: ?>
                <p>Henüz geçmiş bir programın yok.</p>
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
              Telif hakkı &copy;<script>document.write(new Date().getFullYear());</script> Bütün hakkılar saklıdır <a href="https://colorlib.com" target="_blank" ></a>
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
