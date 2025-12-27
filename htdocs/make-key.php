<?php
header('Content-Type: text/plain; charset=utf-8');
echo base64_encode(random_bytes(32));
