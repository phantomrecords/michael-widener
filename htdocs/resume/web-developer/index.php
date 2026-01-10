<?php
declare(strict_types=1);

require_once __DIR__ . '/../../auth.php';
require_login('/resume/');

$html = __DIR__ . '/Web-Designer-Developer.html';
if (is_file($html) && is_readable($html)) {
    header('Content-Type: text/html; charset=UTF-8');
    readfile($html);
    exit;
}

http_response_code(404);
header('Content-Type: text/plain; charset=UTF-8');
echo "Not found.";

