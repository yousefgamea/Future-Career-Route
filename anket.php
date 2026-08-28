<?php
session_start();
include 'baglanti.php';

// Kariyer ilgi alanı anketi - sitenin "Trend Meslek Alanları" olarak
// gösterdiği dört alana (Mühendislik, Tıp, Hukuk, Eğitim) göre kuruldu.
$sorular = [
    'soru1' => [
        'text' => 'Ders çalışırken hangi tür konularda kendini daha güçlü hissedersin?',
        'options' => [
            'muhendislik' => 'Sayısal problemler, formüller ve mantık soruları',
            'tip'         => 'Biyoloji, kimya ve insan vücuduyla ilgili konular',
            'hukuk'       => 'Metin analizi, tartışma ve mantık yürütme',
            'egitim'      => 'Bir konuyu başkasına anlatmak ve örneklerle açıklamak',
        ],
    ],
    'soru2' => [
        'text' => 'Boş vaktinde en çok hangi aktiviteyi yapmaktan keyif alırsın?',
        'options' => [
            'muhendislik' => 'Bir şeyler tasarlamak, kodlamak ya da tamir etmek',
            'tip'         => 'Sağlıkla ilgili belgeseller izlemek, ilk yardım öğrenmek',
            'hukuk'       => 'Güncel olayları tartışmak, haklı-haksız değerlendirmesi yapmak',
            'egitim'      => 'Birine yeni bir şey öğretmek, ders çalışmasına yardım etmek',
        ],
    ],
    'soru3' => [
        'text' => 'Bir problemle karşılaştığında ilk refleksin ne olur?',
        'options' => [
            'muhendislik' => 'Sistemli bir şekilde çözüm yolu kurgularım',
            'tip'         => 'Önce durumu iyi anlamaya, şefkatle yaklaşmaya çalışırım',
            'hukuk'       => 'Kuralları ve tarafların haklarını değerlendiririm',
            'egitim'      => 'Sabırla açıklayarak sorunu adım adım çözmeye çalışırım',
        ],
    ],
    'soru4' => [
        'text' => 'Hangi çalışma ortamı sana daha cazip gelir?',
        'options' => [
            'muhendislik' => 'Laboratuvar, atölye ya da bilgisayar başında proje geliştirme',
            'tip'         => 'Hastane veya klinik gibi bir sağlık ortamı',
            'hukuk'       => 'Büro, mahkeme salonu gibi resmi bir ortam',
            'egitim'      => 'Okul, sınıf ya da eğitim atölyesi',
        ],
    ],
    'soru5' => [
        'text' => 'Kendini en çok hangi kelimeyle tanımlarsın?',
        'options' => [
            'muhendislik' => 'Analitik',
            'tip'         => 'Şefkatli',
            'hukuk'       => 'İkna edici',
            'egitim'      => 'Sabırlı',
        ],
    ],
];

$alanIsimleri = [
    'muhendislik' => 'Mühendislik',
    'tip'         => 'Tıp',
    'hukuk'       => 'Hukuk',
    'egitim'      => 'Eğitim',
];

$sonucMesaji = null;
$hataMesaji = null;
$secilenCevaplar = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_SESSION['kullanici_id'])) {
        $hataMesaji = "Anketi tamamlayabilmek için önce giriş yapmalısın.";
    } else {
        $puanlar = ['muhendislik' => 0, 'tip' => 0, 'hukuk' => 0, 'egitim' => 0];
        $cevapMetinleri = [];
        $tumSorularCevaplandi = true;

        foreach ($sorular as $key => $soru) {
            $secilen = $_POST[$key] ?? '';
            $secilenCevaplar[$key] = $secilen;
            if (!isset($puanlar[$secilen])) {
                $tumSorularCevaplandi = false;
                continue;
            }
            $puanlar[$secilen]++;
            $cevapMetinleri[] = $soru['text'] . ' -> ' . $soru['options'][$secilen];
        }

        if (!$tumSorularCevaplandi) {
            $hataMesaji = "Lütfen bütün soruları cevapla.";
        } else {
            arsort($puanlar);
            $onerilenAlanKey = array_key_first($puanlar);
            $onerilenAlan = $alanIsimleri[$onerilenAlanKey];

            $sonucMetni = "Önerilen alan: " . $onerilenAlan . "\n" . implode("\n", $cevapMetinleri);

            $stmt = $conn->prepare("INSERT INTO anket (Sonuc, KullaniciID) VALUES (?, ?)");
            $stmt->bind_param("si", $sonucMetni, $_SESSION['kullanici_id']);
            if ($stmt->execute()) {
                $sonucMesaji = $onerilenAlan;
            } else {
                $hataMesaji = "Sonucun kaydedilmesi sırasında bir hata oluştu, lütfen tekrar dene.";
            }
            $stmt->close();
        }
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
              <li><a href="anket.php" class="active">Anket</a></li>
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
              <?php if (!empty($_SESSION['kullanici_id']) || !empty($_SESSION['mentor_id'])): ?>
              <a href="<?php echo !empty($_SESSION['mentor_id']) ? 'mentorun-profili.php' : 'kullanicinin-profili.php'; ?>" class="btn btn-outline-white border-width-2 d-none d-lg-inline-block"><span class="mr-2 icon-user"></span>Profilim</a>
              <a href="logout.php" class="btn btn-primary border-width-2 d-none d-lg-inline-block"><span class="mr-2 icon-lock_outline"></span>Çıkış Yap</a>
              <?php else: ?>
              <a href="post-job.php" class="btn btn-outline-white border-width-2 d-none d-lg-inline-block"><span class="mr-2 icon-lock_outline"></span>Giriş Yap</a>
              <a href="kayit-ol-secigi.php" class="btn btn-primary border-width-2 d-none d-lg-inline-block"><span class="mr-2 icon-add"></span>Kayıt Ol</a>
              <?php endif; ?>
            </div>
            <a href="#" class="site-menu-toggle js-menu-toggle d-inline-block d-xl-none mt-lg-2 ml-3"  onclick="OnePageNavigation()"><span class="icon-menu h3 m-0 p-0 mt-2"></span></a>
          </div>

        </div>
      </div>
    </header>

    <!-- HOME -->
    <section class="section-hero overlay inner-page bg-image" style="background-image: url('images/hero_1.jpg');" id="home-section">
      <div class="container">
        <div class="row">
          <div class="col-md-7">
            <h1 class="text-white font-weight-bold">Anket</h1>
            <div class="custom-breadcrumbs">
              <a href="#">Ana Sayfa</a> <span class="mx-2 slash">/</span>
              <span class="text-white"><strong>Anket</strong></span>
            </div>
          </div>
        </div>
      </div>
    </section>

    
    <section class="site-section">
      <div class="container">

        <div class="row align-items-center mb-5">
          <div class="col-lg-8 mb-4 mb-lg-0">
            <div class="d-flex align-items-center">
              <div>
                <h2>Anket</h2>
              </div>
            </div>
          </div>
          <!-- <div class="col-lg-4">
            <div class="row">
              <div class="col-6">
                <a href="#" class="btn btn-block btn-light btn-md"><span class="icon-open_in_new mr-2"></span>Preview</a>
              </div>
              <div class="col-6">
                <a href="#" class="btn btn-block btn-primary btn-md">Save Job</a>
              </div>
            </div>
          </div> -->
        </div>
        <?php if ($sonucMesaji): ?>
        <div class="row mb-5">
          <div class="col-lg-12">
            <div class="alert alert-success">
              <h4 class="alert-heading mb-2">Anketin tamamlandı!</h4>
              Cevaplarına göre sana en uygun görünen alan: <strong><?php echo htmlspecialchars($sonucMesaji); ?></strong>.
              Sonucun profiline kaydedildi.
            </div>
          </div>
        </div>
        <?php elseif (empty($_SESSION['kullanici_id'])): ?>
        <div class="row mb-5">
          <div class="col-lg-12">
            <div class="alert alert-warning">
              Anketi doldurup sonucunu kaydedebilmek için önce
              <a href="post-job.php">giriş yapmalısın</a> ya da
              <a href="kayit-ol-secigi.php">kayıt olmalısın</a>.
              Aşağıdaki soruları yine de inceleyebilirsin.
            </div>
          </div>
        </div>
        <?php endif; ?>

        <?php if ($hataMesaji): ?>
        <div class="row mb-3">
          <div class="col-lg-12">
            <div class="alert alert-danger"><?php echo htmlspecialchars($hataMesaji); ?></div>
          </div>
        </div>
        <?php endif; ?>

        <div class="row mb-5">
          <div class="col-lg-12">
            <form class="p-4 p-md-5 border rounded" method="post">
              <p class="text-muted mb-4">Aşağıdaki soruları cevapla, sana hangi meslek alanının daha uygun olabileceğini görelim.</p>

              <?php foreach ($sorular as $key => $soru): ?>
              <div class="form-group mb-4">
                <label class="text-black d-block mb-2"><strong><?php echo htmlspecialchars($soru['text']); ?></strong></label>
                <?php foreach ($soru['options'] as $optKey => $optText): ?>
                <div class="form-check">
                  <input
                    class="form-check-input"
                    type="radio"
                    name="<?php echo $key; ?>"
                    id="<?php echo $key . '_' . $optKey; ?>"
                    value="<?php echo $optKey; ?>"
                    <?php if (($secilenCevaplar[$key] ?? '') === $optKey) echo 'checked'; ?>
                    required
                  >
                  <label class="form-check-label" for="<?php echo $key . '_' . $optKey; ?>">
                    <?php echo htmlspecialchars($optText); ?>
                  </label>
                </div>
                <?php endforeach; ?>
              </div>
              <?php endforeach; ?>

              <button type="submit" class="btn px-4 btn-primary text-white">Anketi Tamamla</button>
            </form>
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
   
   
     
  </body>
</html>