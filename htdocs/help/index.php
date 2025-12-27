<?php

require_once __DIR__ . '/../auth.php';
require_role('Owner','Investigator','Attorney','Police Officer','Sheriff','Security Guard');

require_login('/help/');

?>
