<?php

$vendorAutoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($vendorAutoload)) {
    require_once $vendorAutoload;
}

spl_autoload_register(function ($class) {
    $dirs = ['helpers', 'controller', 'model', 'config'];
    foreach ($dirs as $dir) {
        $file = __DIR__ . "/../$dir/$class.php";
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});
