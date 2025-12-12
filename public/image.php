<?php
// image.php
if (!isset($_GET['file']) || empty($_GET['file'])) {
    http_response_code(404);
    exit('File not found.');
}

$filename = basename($_GET['file']); // sanitize filename
$filepath = __DIR__ . '/../uploads/' . $filename; // physical path to your uploads folder

if (!file_exists($filepath)) {
    http_response_code(404);
    exit('File not found.');
}

// Get MIME type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $filepath);
finfo_close($finfo);

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($filepath));
readfile($filepath);
exit;
