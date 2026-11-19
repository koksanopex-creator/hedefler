<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$jsonFile = __DIR__ . '/pins.json';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Geçersiz veri']);
        exit;
    }

    if (!isset($data['admin_token']) || $data['admin_token'] !== 'koksan_egitim_admin_2026') {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Yetkisiz erişim: Pin kaydı için yönetici yetkisi gereklidir.']);
        exit;
    }
    
    $saveData = ['pins' => $data['pins'] ?? []];
    file_put_contents($jsonFile, json_encode($saveData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    echo json_encode(['success' => true, 'message' => 'Pin konumları başarıyla kaydedildi.', 'saved_at' => date('Y-m-d H:i:s')]);
    exit;
}

// GET request: return existing pins
if (file_exists($jsonFile)) {
    echo file_get_contents($jsonFile);
} else {
    echo json_encode(['success' => true, 'pins' => []]);
}
