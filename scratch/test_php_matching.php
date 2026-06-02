<?php

$_POST['email'] = 'dis-u1376o@lico.moe';
$_POST['app_name'] = 'Disney+';

ob_start();
include __DIR__ . '/../get_otp.php';
$output = ob_get_clean();

echo "PHP response:\n";
echo $output;
echo "\n";
