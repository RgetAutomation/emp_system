<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../app/Libraries/SimpleXLSX.php';

if (class_exists(\Shuchkin\SimpleXLSX::class)) {
    echo "Class exists!";
} else {
    echo "Class NOT FOUND!";
}
