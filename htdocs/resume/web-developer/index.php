<?php
declare(strict_types=1);
session_start();
if (empty($_SESSION['hc_logged_in']) || $_SESSION['hc_logged_in'] !== true) {
    header('Location: /resume/');
    exit;
}
?>
