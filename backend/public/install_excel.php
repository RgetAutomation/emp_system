<?php
set_time_limit(30);

$url = 'https://raw.githubusercontent.com/shuchkin/simplexlsx/master/src/SimpleXLSX.php';
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
$content = curl_exec($ch);

if ($content) {
    $dir = __DIR__ . '/../app/Libraries';
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    file_put_contents($dir . '/SimpleXLSX.php', $content);
    echo "SUCCESS";
} else {
    echo "FAILED";
}
