<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/require_admin_auth.php';

$upload_dir = '../uploads/products/';

if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$total_images = 0;
$total_size = 0;

$files = glob($upload_dir . '*');
if ($files) {
    foreach ($files as $file) {
        if (is_file($file)) {
            $total_images++;
            $total_size += filesize($file);
        }
    }
}

$total_size_mb = round($total_size / (1024 * 1024), 2);
$free_space_mb = round(disk_free_space($upload_dir) / (1024 * 1024), 2);

$limit_reached = $total_size_mb > 50; // 50MB limit

echo json_encode([
    'success' => true,
    'total_images' => $total_images,
    'total_size_mb' => $total_size_mb,
    'free_space_mb' => $free_space_mb,
    'limit_reached' => $limit_reached
]);
?>