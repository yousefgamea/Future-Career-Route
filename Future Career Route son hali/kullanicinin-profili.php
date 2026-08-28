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

// Kullanıcı tablosundan sadece isim, email ve telefon bilgisini al
$sql = "
    SELECT IsimSoyisim AS Isim, Email, TelefonNumarasi AS Telefon
    FROM Kullanici WHERE KullaniciID = ?
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
// Not: Bu sayfada önceden "şifre değiştirme" alanları doğrulanıyordu
// ama karşılığında ne bir form ne de bir UPDATE sorgusu vardı - yani
// sayfa her açıldığında (POST olmasa bile) hataya düşen ölü kod
// çalışıyordu. Şifre değiştirme özelliği eklenmediği için kaldırıldı;
// istersen bunu ayrı bir adım olarak birlikte ekleyebiliriz.
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
          position: relative;
          left: 760px;
          bottom: 2140px;
        }
        .col-md-5 {
          box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
          border-radius: 8px;
          padding: 20px;
          margin: 10px 0;
          position: relative;
          left: 775px;
          bottom: 2140px;
        }
        .progress-bar {
          background-color: #007bff;
        }
        .col-md-7{

            position: relative;
            right: 60px;
            top: 20px;
        }
        .col-md-8{
            width: 650px;
            height: auto;
            position: relative;
            left: 150px;
            bottom: 425px;
        }
        .col-md-9{
            width: 650px;
            height: auto;
            position: relative;
            left: 360px;
            bottom: 835px;
        }
        .col-md-10{
            width: 650px;
            height: auto;
            position: relative;
            right: 62px;
            bottom: 800px;
        }
        .col-md-11{
            width: 650px;
            height: auto;
            position: relative;
            left: 150px;
            bottom: 1246px;
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
              <li><a href="about.php">Hakkımızda</a></li>
            

              <li><a href="blog.php">Programlar</a></li>
              <li><a href="contact.php">İletişim</a></li>
              <li class="d-lg-none"><a href="post-job.php">Giriş Yap</a></li>
              <li class="d-lg-none"><a href="kayit-ol-secigi.php">Kayıt Ol</a></li>
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
        <h1><?php echo htmlspecialchars($user['Isim']); ?></h1>
      </div>
      <div class="container">
        <div class="profile-card">
            <h5>Rozetler ve Puanlar</h5>
            <div class="badges">
              <!-- Rozet 1 -->
              <div class="badge">
                <img src="https://via.placeholder.com/50" alt="Rozet 1">
              </div>
              <!-- Rozet 2 -->
              <div class="badge">
                <img src="https://via.placeholder.com/50" alt="Rozet 2">
              </div>
              <!-- Rozet 3 -->
              <div class="badge">
                <img src="https://via.placeholder.com/50" alt="Rozet 3">
              </div>
            </div>
            <!-- Puan -->
            <div class="points mt-3">
              <h6>Puanlar</h6>
              <p><strong>Toplam Puan:</strong> 1250</p>
            </div>
          </div>
          


        <div class="col-md-7">
        <div class="col-md-6 col-lg-4 mb-5">
            <a href="blog-single.php"><img src="images/sq_img_1.jpg" alt="Image" class="img-fluid rounded mb-4"></a>
            <h3><a href="blog-single.php" class="text-black">7 Factors for Choosing Between Two Jobs</a></h3>
            <div>Ayşe Demir <span class="mx-2">|</span> <a href="#">2 Comments</a></div>
          </div></div>
          <div class="col-md-8">
          <div class="col-md-6 col-lg-4 mb-5">
            <a href="blog-single.php"><img src="images/sq_img_2.jpg" alt="Image" class="img-fluid rounded mb-4"></a>
            <h3><a href="blog-single.php" class="text-black">How to Write a Creative Cover Letter</a></h3>
            <div>Mehmet Çelik<span class="mx-2">|</span> <a href="#">2 Comments</a></div>
          </div></div>
          <div class="col-md-9">
            <div class="col-md-6 col-lg-4 mb-5">
              <a href="blog-single.php"><img src="images/sq_img_4.jpg" alt="Image" class="img-fluid rounded mb-4"></a>
              <h3><a href="blog-single.php" class="text-black">The Right Way to Quit a Job You Started</a></h3>
              <div>Ayşe Demir<span class="mx-2">|</span> <a href="#">2 Comments</a></div>
            </div>
          </div>
            <div class="col-md-10">
            <div class="col-md-6 col-lg-4 mb-5">
              <a href="blog-single.php"><img src="images/sq_img_7.jpg" alt="Image" class="img-fluid rounded mb-4"></a>
              <h3><a href="blog-single.php" class="text-black">7 Factors for Choosing Between Two Jobs</a></h3>
              <div>Mehmet Çelik<span class="mx-2">|</span> <a href="#">2 Comments</a></div>
            </div>
          </div>
            <div class="col-md-11">
            <div class="col-md-6 col-lg-4 mb-5">
              <a href="blog-single.php"><img src="images/sq_img_5.jpg" alt="Image" class="img-fluid rounded mb-4"></a>
              <h3><a href="blog-single.php" class="text-black">How to Write a Creative Cover Letter</a></h3>
              <div>Mehmet Çelik<span class="mx-2">|</span> <a href="#">2 Comments</a></div>
            </div>
          </div>
     

        <div class="col-md-4">
            <div class="profile-card1">
              <h5>Temel Bilgiler</h5>
              <p><strong>Email:</strong> <?php echo htmlspecialchars($user['Email']); ?></p>
              <p><strong>Telefon numarası:</strong> <?php echo htmlspecialchars($user['Telefon']); ?></p>
              <p><strong>Meslek İlgi Alanı:</strong> Bilgisayar Mühendisliği</p>
              <p><strong>Rol:</strong> Kullanıcı (Menti)</p>
            </div>
          </div>
          

          <div class="col-md-5">
            <div class="profile-card">
              <h5>performans</h5>
              <p><strong>Tamamlanan Hedefler:</strong> 3/5</p>
              <div class="progress">
                <div class="progress-bar" role="progressbar" style="width: 60%;" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100">
                  %60 Tamamlandı
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-5">
            <div class="profile-card">
              <h5>Bağlantılar</h5>
              <ul>
                <li>Mentor: Ayşe Demir</li>
                <li>Mentor: Mehmet Çelik</li>
              </ul>
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
              <li><a href="#">Kariyer</a></li>
              <li><a href="blog.php">Programlar</a></li>
            </ul>
          </div>
          <div class="col-6 col-md-3 mb-4 mb-md-0">
            <h3>Destek</h3>
            <ul class="list-unstyled">
              <li><a href="contact.php">Destek</a></li>
              <li><a href="#">Privacy</a></li>
              <li><a href="#">Hizmet Şartları</a></li>
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
