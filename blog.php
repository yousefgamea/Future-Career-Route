<?php
session_start();
include 'baglanti.php';

// Mentorların oluşturduğu, gerçek programlar (ad girilmiş olanlar)
$gercekProgramlarStmt = $conn->prepare("
    SELECT p.ProgramID, p.ProgramAdi, p.Aciklama, p.Kontenjan,
           m.MentorID, m.Isim, m.Soyisim, m.DersAdi,
           (SELECT COUNT(*) FROM kullanici k WHERE k.ProgramID = p.ProgramID) AS KatilimciSayisi
    FROM program p
    JOIN mentor m ON p.MentorID = m.MentorID
    WHERE p.ProgramAdi IS NOT NULL AND p.ProgramAdi <> '' AND m.Onaylandi = 1
    ORDER BY p.ProgramID DESC
");
$gercekProgramlarStmt->execute();
$gercekProgramlar = $gercekProgramlarStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$gercekProgramlarStmt->close();
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
              <li><a href="mentor-listesi.php">Mentorlar</a></li>
              <li><a href="about.php">Hakkımızda</a></li>
             
              <li><a href="blog.php" class="active">Programlar</a></li>
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
            <h1 class="text-white font-weight-bold">Programlarımız</h1>
            <div class="custom-breadcrumbs">
              <a href="#">Ana Sayfa</a> <span class="mx-2 slash">/</span>
              <span class="text-white"><strong>Programlar</strong></span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="site-section">
      <div class="container">
        <div class="row mb-5">
          <div class="col-lg-12 mb-4">
            <p>Future Career Route olarak öğrencilere dört ana kariyer alanında rehberlik ediyoruz. Aşağıdaki
              programlardan birini seçerek o alandaki bölümler, eğitim süreleri ve kariyer olanakları hakkında
              detaylı bilgi alabilir, alana uygun mentorlarla eşleşebilirsin.</p>
          </div>
          <div class="col-md-6 col-lg-3 mb-5">
            <a href="Muhendislik.php"><img src="images/sq_img_1.jpg" alt="Mühendislik" class="img-fluid rounded mb-4"></a>
            <h3><a href="Muhendislik.php" class="text-black">Mühendislik</a></h3>
            <p>Makine, Elektrik-Elektronik, Bilgisayar, İnşaat, Endüstri, Kimya ve daha birçok mühendislik dalında bölüm ve kariyer rehberi.</p>
            <a href="Muhendislik.php">Programı İncele &raquo;</a>
          </div>
          <div class="col-md-6 col-lg-3 mb-5">
            <a href="tip.php"><img src="images/sq_img_2.jpg" alt="Tıp" class="img-fluid rounded mb-4"></a>
            <h3><a href="tip.php" class="text-black">Tıp</a></h3>
            <p>Tıp, Diş Hekimliği, Veterinerlik ve sağlık bilimleri alanındaki fakülteler, eğitim süreleri ve kariyer olanakları.</p>
            <a href="tip.php">Programı İncele &raquo;</a>
          </div>
          <div class="col-md-6 col-lg-3 mb-5">
            <a href="hukuk.php"><img src="images/sq_img_4.jpg" alt="Hukuk" class="img-fluid rounded mb-4"></a>
            <h3><a href="hukuk.php" class="text-black">Hukuk</a></h3>
            <p>Hukuk eğitimi, mezuniyet sonrası unvanlar ve avukatlık, hakimlik-savcılık, noterlik gibi kariyer olanakları.</p>
            <a href="hukuk.php">Programı İncele &raquo;</a>
          </div>
          <div class="col-md-6 col-lg-3 mb-5">
            <a href="egitim.php"><img src="images/sq_img_5.jpg" alt="Eğitim" class="img-fluid rounded mb-4"></a>
            <h3><a href="egitim.php" class="text-black">Eğitim</a></h3>
            <p>Sınıf Öğretmenliği, Okul Öncesi, PDR, Fen ve Matematik Öğretmenliği gibi öğretmenlik programları ve kariyer yolları.</p>
            <a href="egitim.php">Programı İncele &raquo;</a>
          </div>
        </div>

        <div class="row mb-5">
          <div class="col-lg-12 text-center">
            <p>Hangi alanın sana uygun olduğundan emin değil misin? <a href="anket.php">Kariyer ilgi alanı anketimizi</a> doldurarak sana en uygun programı keşfedebilirsin.</p>
          </div>
        </div>

        <?php if (!empty($gercekProgramlar)): ?>
        <div class="row mb-4">
          <div class="col-lg-12">
            <h2>Mentorlarımızın Açtığı Programlar</h2>
            <p>Mentorlarımızın oluşturduğu ve şu anda katılıma açık olan gerçek programlar aşağıda listelenmiştir.</p>
          </div>
        </div>
        <div class="row mb-5">
          <?php foreach ($gercekProgramlar as $gp): ?>
            <div class="col-md-6 col-lg-4 mb-4">
              <div class="profile-card h-100">
                <h4><?php echo htmlspecialchars($gp['ProgramAdi']); ?></h4>
                <p><small>Mentor: <?php echo htmlspecialchars(trim($gp['Isim'] . ' ' . $gp['Soyisim'])); ?> &middot; <?php echo htmlspecialchars($gp['DersAdi']); ?></small></p>
                <?php if (!empty($gp['Aciklama'])): ?>
                  <p><?php echo nl2br(htmlspecialchars(mb_strimwidth($gp['Aciklama'], 0, 160, '...'))); ?></p>
                <?php endif; ?>
                <?php if (!empty($gp['Kontenjan'])): ?>
                  <p><small><?php echo (int) $gp['KatilimciSayisi']; ?> / <?php echo (int) $gp['Kontenjan']; ?> öğrenci katıldı</small></p>
                <?php else: ?>
                  <p><small><?php echo (int) $gp['KatilimciSayisi']; ?> öğrenci katıldı</small></p>
                <?php endif; ?>
                <a href="mentor-detay.php?id=<?php echo (int) $gp['MentorID']; ?>" class="btn btn-outline-primary btn-sm">Mentoru ve Programı İncele</a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

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
   
   
     
  </body>
</html>