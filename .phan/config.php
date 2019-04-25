<?php

$cfg = require __DIR__ . '/../vendor/mediawiki/mediawiki-phan-config/src/config.php';

// T191668
$cfg['suppress_issue_types'][] = 'PhanParamTooMany';

return $cfg;
