<?php
/**
 * Simple Upload Test - helps diagnose production issues
 */

header('Content-Type: application/json');

echo json_encode([
    'status' => 'ok',
    'message' => 'Upload endpoint is reachable',
    'php_version' => phpversion(),
    'post_max_size' => ini_get('post_max_size'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'max_execution_time' => ini_get('max_execution_time'),
    'memory_limit' => ini_get('memory_limit'),
    'request_method' => $_SERVER['REQUEST_METHOD'],
    'has_post_data' => !empty($_POST),
    'has_files' => !empty($_FILES),
]);
