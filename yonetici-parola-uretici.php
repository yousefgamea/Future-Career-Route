<?php
// YARDIMCI ARAÇ - ilk yönetici hesabını oluşturmak için.
// Bu sayfa herkese açık kayıt formu DEĞİLDİR; yöneticiler kendi kendine
// kayıt olamaz (yonetici tablosuna kayıt eklemenin bilinçli bir güvenlik
// kararı olması gerekir). Bu sayfa sadece phpMyAdmin'den elle bir
// yönetici satırı eklerken, şifreni güvenli şekilde hash'lemeni sağlar.
// İlk yöneticini oluşturduktan sonra bu dosyayı sunucudan silmen önerilir.
session_start();
$hash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['sifre'])) {
    $hash = password_hash($_POST['sifre'], PASSWORD_DEFAULT);
}
?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <title>Yönetici Parola Üretici</title>
  <style>
    body { font-family: sans-serif; max-width: 600px; margin: 40px auto; padding: 0 20px; }
    input, button { padding: 8px; font-size: 1em; }
    textarea { width: 100%; padding: 8px; margin-top: 10px; }
  </style>
</head>
<body>
  <h2>Yönetici Parola Üretici</h2>
  <p>Bu araç, phpMyAdmin'den <code>yonetici</code> tablosuna elle ilk yönetici satırını eklerken
     kullanılacak hash'lenmiş şifreyi üretir. Şifreni düz metin olarak veritabanına <strong>asla</strong>
     yazma.</p>
  <form method="POST">
    <label>Şifre: <input type="text" name="sifre" required></label>
    <button type="submit">Hash Üret</button>
  </form>
  <?php if ($hash): ?>
    <p><strong>Hash'lenmiş şifren:</strong></p>
    <textarea rows="3" readonly onclick="this.select()"><?php echo htmlspecialchars($hash); ?></textarea>
    <p>Bunu phpMyAdmin'de <code>yonetici</code> tablosuna yeni satır eklerken <code>Sifre</code> sütununa
       yapıştır; <code>Mail</code>, <code>Ad</code>, <code>Soyad</code> sütunlarını da doldur.</p>
  <?php endif; ?>
  <p><em>Not: İlk yöneticini oluşturduktan sonra güvenlik için bu dosyayı (yonetici-parola-uretici.php)
     sunucudan silmen önerilir.</em></p>
</body>
</html>
