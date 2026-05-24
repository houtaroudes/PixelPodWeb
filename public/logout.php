<?php
session_start();
session_unset();
session_destroy();
header('Location: http://localhost/PixelPodWeb/public/login.php');
exit;