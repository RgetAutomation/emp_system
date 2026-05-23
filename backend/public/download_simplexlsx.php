<?php
$dir = __DIR__ . '/../app/Libraries';
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}
$content = file_get_contents('https://raw.githubusercontent.com/shuchkin/simplexlsx/master/src/SimpleXLSX.php');
if ($content) {
    file_put_contents($dir . '/SimpleXLSX.php', $content);
    echo "Success downloading SimpleXLSX";
} else {
    echo "Failed to download";
}
