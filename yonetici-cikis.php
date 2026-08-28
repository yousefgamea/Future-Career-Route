<?php
session_start();
unset($_SESSION['yonetici_id'], $_SESSION['yonetici_ad']);
header("Location: yonetici-giris.php");
exit();
