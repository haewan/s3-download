#!/usr/bin/env php
<?php
require_once 'Services/Amazon/S3.php';
ini_set('display_errors', 1);

$opts = getopt('b:t::l::');
if (!isset($opts['b']) || !is_string($opts['b'])) {
    echo "Usage: " . basename(__FILE__) . " -b BUCKETNAME [ -t TTL, -l /path/to/folder ]" . PHP_EOL;
    exit(1);
}
$bucket = $opts['b'];

$ttl = (60*10); // valid for 10 minutes
if (isset($opts['t'])) {
    $ttl = (int) $opts['t'];
    if ($ttl < (60*10)) {
        echo "TTL too small." . PHP_EOL;
        exit(1);
    }
}
$mode = "0755";

$config = parse_ini_file($_SERVER['HOME'] . '/.s3cfg', false, INI_SCANNER_RAW);
$s3     = Services_Amazon_S3::getAccount($config['access_key'], $config['secret_key']);

$obj   = $s3->getBucket($bucket);
$files = $obj->getObjects();

$base = __DIR__;
if (isset($opts['l']) && !empty($opts['l'])) {
    $base = realpath($opts['l']);
    if ($base === false) {
        echo "Could not resolve the location." . PHP_EOL;
        exit(1);
    }
    if (!is_writable($base)) {
        echo "The current user cannot write to {$base}.";
        exit(1);
    }
}

$dirs = array();
$dl   = array();
$size = 0;
foreach ($files as $file) {
    $dirs[]          = dirname($file->key);
    $size           += $file->size;
    $dl[$file->key]  = $file;
}
$dirs = array_unique($dirs);

echo "Downloading: " . round(($size/1024/1024/1024), 2) . " GB" . PHP_EOL;

foreach ($dirs as $dir) {
    $p = "{$base}/{$dir}";
    if (file_exists($p)) {
        continue;
    }
    mkdir($p, $mode, true);
}

echo "Downloading files ..." . PHP_EOL;

$failed = array();

foreach ($dl as $file => $object) {
    //echo "Downloading {$file} ..." . PHP_EOL;
    try {
        $status = $object->load();
    } catch (\Exception $e) {
        //var_dump($e); exit;
        $failed[$file] = $e->getMessage();
        continue;
    }
    if ($status === false) {
        //echo "Could not load {$file}!" . PHP_EOL;
        $failed[$file] = "Could not load.";
        continue;
    } else {
        echo ".";
    }
    $status = file_put_contents("{$base}/{$file}", $object->data);
    if ($status === false) {
        $failed[$file] = "Could not write.";
    }
    unset($object);
}
echo PHP_EOL;

if (count($failed) > 0) {
    echo "Problem?" . PHP_EOL . PHP_EOL;
    foreach ($failed as $file => $problem) {
        echo "File: {$file}, Reason: {$problem}" . PHP_EOL;
    }
    exit(1);
}
exit(0);
