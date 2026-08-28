<?php
session_start();
include 'baglanti.php';

if (empty($_SESSION['yonetici_id'])) {
    header("Location: yonetici-giris.php");
    exit();
}

$yoneticiID = $_SESSION['yonetici_id'];
$hata = '';
$basari = '';

// Yeni reklam ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reklam_ekle'])) {
    $reklamMetni = trim($_POST['reklam'] ?? '');
    if (empty($reklamMetni)) {
        $hata = "Reklam metni boş olamaz.";
    } else {
        $stmt = $conn->prepare("INSERT INTO reklam (YoneticiID, Reklam) VALUES (?, ?)");
        $stmt->bind_param("is", $yoneticiID, $reklamMetni);
        if ($stmt->execute()) {
            $basari = "Reklam eklendi.";
        } else {
            $hata = "Reklam eklenirken hata oluştu.";
        }
        $stmt->close();
    }
}

// Reklam silme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reklam_sil'])) {
    $reklamID = (int) $_POST['reklam_sil'];
    $stmt = $conn->prepare("DELETE FROM reklam WHERE ReklamID = ?");
    $stmt->bind_param("i", $reklamID);
    $stmt->execute();
    $stmt->close();
    $basari = "Reklam silindi.";
}

// Mentor onaylama (kayıt olan mentor artık giriş yapabilir, listede görünür)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mentor_onayla'])) {
    $onayMentorID = (int) $_POST['mentor_onayla'];
    $stmt = $conn->prepare("UPDATE mentor SET Onaylandi = 1 WHERE MentorID = ?");
    $stmt->bind_param("i", $onayMentorID);
    $stmt->execute();
    $stmt->close();
    $basari = "Mentor onaylandı.";
}

// Mentor kaydını reddetme (hesabı tamamen siler)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mentor_reddet'])) {
    $reddetMentorID = (int) $_POST['mentor_reddet'];
    $stmt = $conn->prepare("DELETE FROM mentor WHERE MentorID = ? AND Onaylandi = 0");
    $stmt->bind_param("i", $reddetMentorID);
    $stmt->execute();
    $stmt->close();
    $basari = "Mentor kaydı reddedildi ve silindi.";
}

// Öğrenciye rozet verme (aynı puana sahip bir rozet varsa onu kullanır,
// yoksa yeni bir rozet satırı oluşturur)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rozet_ver'])) {
    $rozetKullaniciID = (int) $_POST['rozet_ver'];
    $rozetPuani = (int) ($_POST['rozet_puani'] ?? 0);
    if ($rozetPuani <= 0) {
        $hata = "Rozet puanı 0'dan büyük olmalı.";
    } else {
        $rozetBulStmt = $conn->prepare("SELECT RozetID FROM rozet WHERE RozetPuani = ? LIMIT 1");
        $rozetBulStmt->bind_param("i", $rozetPuani);
        $rozetBulStmt->execute();
        $rozetSatiri = $rozetBulStmt->get_result()->fetch_assoc();
        $rozetBulStmt->close();

        if ($rozetSatiri) {
            $rozetID = $rozetSatiri['RozetID'];
        } else {
            $rozetEkleStmt = $conn->prepare("INSERT INTO rozet (RozetPuani) VALUES (?)");
            $rozetEkleStmt->bind_param("i", $rozetPuani);
            $rozetEkleStmt->execute();
            $rozetID = $conn->insert_id;
            $rozetEkleStmt->close();
        }

        $guncelleStmt = $conn->prepare("UPDATE kullanici SET RozetID = ? WHERE KullaniciID = ?");
        $guncelleStmt->bind_param("ii", $rozetID, $rozetKullaniciID);
        if ($guncelleStmt->execute()) {
            $basari = "Rozet verildi.";
        } else {
            $hata = "Rozet verilirken hata oluştu.";
        }
        $guncelleStmt->close();
    }
}

// Öğrenciden rozeti kaldırma
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rozet_kaldir'])) {
    $rozetKaldirID = (int) $_POST['rozet_kaldir'];
    $stmt = $conn->prepare("UPDATE kullanici SET RozetID = NULL WHERE KullaniciID = ?");
    $stmt->bind_param("i", $rozetKaldirID);
    $stmt->execute();
    $stmt->close();
    $basari = "Rozet kaldırıldı.";
}

// İletişim mesajına yanıt yazma/güncelleme (not: bu sadece panelde saklanır,
// kişiye otomatik email gitmez - site içi email gönderimi kurulu değil)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['iletisim_yanitla'])) {
    $iletisimID = (int) $_POST['iletisim_yanitla'];
    $yanitMetni = trim($_POST['yanit'] ?? '');
    if (empty($yanitMetni)) {
        $hata = "Yanıt metni boş olamaz.";
    } else {
        $stmt = $conn->prepare("UPDATE iletisim SET Yanit = ?, YanitTarihi = NOW() WHERE IletisimID = ?");
        $stmt->bind_param("si", $yanitMetni, $iletisimID);
        if ($stmt->execute()) {
            $basari = "Yanıt kaydedildi.";
        } else {
            $hata = "Yanıt kaydedilirken hata oluştu.";
        }
        $stmt->close();
    }
}

// Basit istatistikler
$ogrenciSayisi = $conn->query("SELECT COUNT(*) AS n FROM kullanici")->fetch_assoc()['n'];
$mentorSayisi = $conn->query("SELECT COUNT(*) AS n FROM mentor")->fetch_assoc()['n'];
$programSayisi = $conn->query("SELECT COUNT(*) AS n FROM program")->fetch_assoc()['n'];
$anketSayisi = $conn->query("SELECT COUNT(*) AS n FROM anket")->fetch_assoc()['n'];
$iletisimSayisi = $conn->query("SELECT COUNT(*) AS n FROM iletisim")->fetch_assoc()['n'];

// Reklamlar (bu yöneticinin eklediği + varsa diğer yöneticilerin de)
$reklamlar = $conn->query("
    SELECT r.ReklamID, r.Reklam, y.Ad, y.Soyad
    FROM reklam r
    JOIN yonetici y ON r.YoneticiID = y.YoneticiID
    ORDER BY r.ReklamID DESC
")->fetch_all(MYSQLI_ASSOC);

// Son iletişim mesajları
$sonMesajlar = $conn->query("
    SELECT IletisimID, Isim, Soyisim, KullaniciMail, Konu, Sorun, Yanit
    FROM iletisim ORDER BY IletisimID DESC LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

// Onay bekleyen mentorlar
$onayBekleyenMentorlar = $conn->query("
    SELECT MentorID, Isim, Soyisim, Email, DersAdi
    FROM mentor WHERE Onaylandi = 0 ORDER BY MentorID DESC
")->fetch_all(MYSQLI_ASSOC);

// Öğrenciler + varsa rozet puanı (rozet verme/kaldırma için)
$ogrenciler = $conn->query("
    SELECT k.KullaniciID, k.Isim, k.Soyisim, k.Email, r.RozetPuani
    FROM kullanici k
    LEFT JOIN rozet r ON k.RozetID = r.RozetID
    ORDER BY k.Isim
")->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <title>Yönetici Paneli - Future Career Route</title>
  <style>
    body { font-family: sans-serif; max-width: 1000px; margin: 30px auto; padding: 0 20px; }
    .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .stats { display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 30px; }
    .stat-card { background: #f8f9fa; border: 1px solid #ddd; border-radius: 8px; padding: 15px 20px; min-width: 140px; }
    .stat-card h3 { margin: 0; font-size: 1.8em; }
    .stat-card p { margin: 0; color: #666; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
    th, td { text-align: left; padding: 8px; border-bottom: 1px solid #eee; }
    .card { background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin-bottom: 30px; }
    input, textarea { padding: 8px; width: 100%; box-sizing: border-box; margin-bottom: 10px; }
    button { padding: 8px 16px; background: #89ba16; color: #fff; border: none; cursor: pointer; }
    .danger { background: #d9534f; }
    .success { color: green; }
    .error { color: red; }
  </style>
</head>
<body>
  <div class="top-bar">
    <h2>Yönetici Paneli — Hoş geldin, <?php echo htmlspecialchars($_SESSION['yonetici_ad']); ?></h2>
    <div>
      <a href="index.php">Siteye Dön</a> |
      <a href="yonetici-cikis.php">Çıkış Yap</a>
    </div>
  </div>

  <?php if ($hata): ?><p class="error"><?php echo htmlspecialchars($hata); ?></p><?php endif; ?>
  <?php if ($basari): ?><p class="success"><?php echo htmlspecialchars($basari); ?></p><?php endif; ?>

  <div class="stats">
    <div class="stat-card"><h3><?php echo (int) $ogrenciSayisi; ?></h3><p>Öğrenci</p></div>
    <div class="stat-card"><h3><?php echo (int) $mentorSayisi; ?></h3><p>Mentor</p></div>
    <div class="stat-card"><h3><?php echo (int) $programSayisi; ?></h3><p>Program</p></div>
    <div class="stat-card"><h3><?php echo (int) $anketSayisi; ?></h3><p>Anket Sonucu</p></div>
    <div class="stat-card"><h3><?php echo (int) $iletisimSayisi; ?></h3><p>İletişim Mesajı</p></div>
  </div>

  <div class="card">
    <h3>Onay Bekleyen Mentorlar</h3>
    <table>
      <tr><th>Ad Soyad</th><th>Email</th><th>Ders</th><th></th></tr>
      <?php foreach ($onayBekleyenMentorlar as $om): ?>
      <tr>
        <td><?php echo htmlspecialchars(trim($om['Isim'] . ' ' . $om['Soyisim'])); ?></td>
        <td><?php echo htmlspecialchars($om['Email']); ?></td>
        <td><?php echo htmlspecialchars($om['DersAdi']); ?></td>
        <td>
          <form method="POST" style="margin:0; display:inline-block;">
            <button type="submit" name="mentor_onayla" value="<?php echo (int) $om['MentorID']; ?>">Onayla</button>
          </form>
          <form method="POST" style="margin:0; display:inline-block;" onsubmit="return confirm('Bu mentor kaydını reddedip silmek istediğine emin misin?');">
            <button type="submit" name="mentor_reddet" value="<?php echo (int) $om['MentorID']; ?>" class="danger">Reddet</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($onayBekleyenMentorlar)): ?>
      <tr><td colspan="4">Onay bekleyen mentor yok.</td></tr>
      <?php endif; ?>
    </table>
  </div>

  <div class="card">
    <h3>Öğrencilere Rozet Ver</h3>
    <table>
      <tr><th>Ad Soyad</th><th>Email</th><th>Rozet Puanı</th><th></th></tr>
      <?php foreach ($ogrenciler as $og): ?>
      <tr>
        <td><?php echo htmlspecialchars(trim($og['Isim'] . ' ' . $og['Soyisim'])); ?></td>
        <td><?php echo htmlspecialchars($og['Email']); ?></td>
        <td><?php echo $og['RozetPuani'] !== null ? (int) $og['RozetPuani'] : '—'; ?></td>
        <td>
          <form method="POST" style="margin:0; display:flex; gap:5px; align-items:center;">
            <input type="number" name="rozet_puani" min="1" placeholder="Puan" style="width:70px; margin:0;">
            <button type="submit" name="rozet_ver" value="<?php echo (int) $og['KullaniciID']; ?>">Ver</button>
            <?php if ($og['RozetPuani'] !== null): ?>
              <button type="submit" name="rozet_kaldir" value="<?php echo (int) $og['KullaniciID']; ?>" class="danger" formnovalidate>Kaldır</button>
            <?php endif; ?>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($ogrenciler)): ?>
      <tr><td colspan="4">Henüz kayıtlı öğrenci yok.</td></tr>
      <?php endif; ?>
    </table>
  </div>

  <div class="card">
    <h3>Reklamlar</h3>
    <table>
      <tr><th>Reklam</th><th>Ekleyen</th><th></th></tr>
      <?php foreach ($reklamlar as $r): ?>
      <tr>
        <td><?php echo htmlspecialchars($r['Reklam']); ?></td>
        <td><?php echo htmlspecialchars(trim($r['Ad'] . ' ' . $r['Soyad'])); ?></td>
        <td>
          <form method="POST" style="margin:0;">
            <button type="submit" name="reklam_sil" value="<?php echo (int) $r['ReklamID']; ?>" class="danger">Sil</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($reklamlar)): ?>
      <tr><td colspan="3">Henüz reklam eklenmemiş.</td></tr>
      <?php endif; ?>
    </table>

    <form method="POST">
      <textarea name="reklam" rows="2" placeholder="Yeni reklam metni..." required></textarea>
      <button type="submit" name="reklam_ekle" value="1">Reklam Ekle</button>
    </form>
  </div>

  <div class="card">
    <h3>Son İletişim Mesajları</h3>
    <p style="color:#666; font-size:0.9em;">Not: buraya yazdığın yanıt sadece panelde saklanır, kişiye otomatik email olarak gitmez - cevabı kendisine iletmek istersen kendi mail adresinden yazman gerekir.</p>
    <?php foreach ($sonMesajlar as $m): ?>
      <div style="border-bottom: 1px solid #eee; padding: 12px 0;">
        <p style="margin:0;"><strong><?php echo htmlspecialchars(trim($m['Isim'] . ' ' . $m['Soyisim'])); ?></strong> — <?php echo htmlspecialchars($m['KullaniciMail']); ?> — <em><?php echo htmlspecialchars($m['Konu']); ?></em></p>
        <p style="margin:5px 0;"><?php echo nl2br(htmlspecialchars($m['Sorun'])); ?></p>
        <?php if (!empty($m['Yanit'])): ?>
          <p style="margin:5px 0; background:#f0f7e6; padding:8px; border-radius:4px;"><strong>Yanıtın:</strong> <?php echo nl2br(htmlspecialchars($m['Yanit'])); ?></p>
        <?php endif; ?>
        <form method="POST" style="margin-top:5px;">
          <textarea name="yanit" rows="2" placeholder="Yanıt yaz..."><?php echo htmlspecialchars($m['Yanit'] ?? ''); ?></textarea>
          <button type="submit" name="iletisim_yanitla" value="<?php echo (int) $m['IletisimID']; ?>"><?php echo !empty($m['Yanit']) ? 'Yanıtı Güncelle' : 'Yanıtla'; ?></button>
        </form>
      </div>
    <?php endforeach; ?>
    <?php if (empty($sonMesajlar)): ?>
      <p>Henüz mesaj yok.</p>
    <?php endif; ?>
  </div>
</body>
</html>
