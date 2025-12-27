<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth.php';

$qs = $_SERVER['QUERY_STRING'] ?? '';
if (!is_string($qs)) $qs = '';
$suffix = $qs !== '' ? ('?' . $qs) : '';

redirect('/forgot/' . $suffix);

