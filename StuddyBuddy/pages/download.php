<?php
/**
 * Secure Download System
 * Only allows downloads for purchased materials
 * Verifies purchase ownership before serving files
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/helpers.php';
require_login();

$user = current_user();
$buyer_id = (int)$user['id'];
$note_id = (int)($_GET['id'] ?? 0);

if ($note_id <= 0) {
    http_response_code(400);
    exit('Invalid file ID.');
}

// Verify purchase ownership
$sql = "
    SELECT 
        n.id, n.title, n.file_path, n.seller_id,
        p.id AS purchase_id, p.status, p.created_at AS purchased_at,
        u.full_name AS seller_name,
        LOWER(SUBSTRING_INDEX(n.file_path,'.',-1)) AS ext
    FROM notes n
    INNER JOIN purchases p ON p.note_id = n.id
    LEFT JOIN users u ON u.id = n.seller_id
    WHERE n.id = ? 
    AND p.buyer_id = ? 
    AND p.status IN ('paid', 'completed')
    AND n.is_approved = 1
    LIMIT 1
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$note_id, $buyer_id]);
$purchase = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$purchase) {
    http_response_code(403);
    exit('
    <div style="font-family: Arial, sans-serif; max-width: 500px; margin: 100px auto; text-align: center; padding: 20px;">
        <div style="font-size: 48px; color: #ef4444; margin-bottom: 20px;">🚫</div>
        <h2 style="color: #374151;">Access Denied</h2>
        <p style="color: #6b7280;">You don\'t have permission to download this file. Please purchase it first.</p>
        <a href="materials.php" style="display: inline-block; margin-top: 20px; padding: 10px 20px; background: #3b82f6; color: white; text-decoration: none; border-radius: 5px;">Browse Materials</a>
    </div>
    ');
}

// Get file path
$raw_fp = trim($purchase['file_path']);
$UPLOAD_DIR = realpath(__DIR__ . '/uploads');

// Build actual file path
if (preg_match('#^https?://#i', $raw_fp) || str_starts_with($raw_fp, '/')) {
    $file_path = $raw_fp;
    $is_remote = true;
} else {
    $file_path = $UPLOAD_DIR . DIRECTORY_SEPARATOR . $raw_fp;
    $is_remote = false;
}

// Check if file exists
if (!$is_remote && !file_exists($file_path)) {
    http_response_code(404);
    exit('
    <div style="font-family: Arial, sans-serif; max-width: 500px; margin: 100px auto; text-align: center; padding: 20px;">
        <div style="font-size: 48px; color: #f59e0b; margin-bottom: 20px;">⚠️</div>
        <h2 style="color: #374151;">File Not Found</h2>
        <p style="color: #6b7280;">The requested file could not be found on our servers. Please contact support.</p>
        <a href="mypurchases.php" style="display: inline-block; margin-top: 20px; padding: 10px 20px; background: #3b82f6; color: white; text-decoration: none; border-radius: 5px;">My Purchases</a>
    </div>
    ');
}

// Log download attempt
$log_sql = "INSERT INTO download_logs (buyer_id, note_id, purchase_id, downloaded_at, ip_address) VALUES (?, ?, ?, NOW(), ?)";
try {
    $log_stmt = $pdo->prepare($log_sql);
    $log_stmt->execute([$buyer_id, $note_id, $purchase['purchase_id'], $_SERVER['REMOTE_ADDR']]);
} catch (Exception $e) {
    // Continue even if logging fails
}

// Prepare file for download
$filename = sanitizeFilename($purchase['title']) . '.' . $purchase['ext'];
$mime_type = getMimeType($purchase['ext']);

// Set download headers
header('Content-Type: ' . $mime_type);
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: private, must-revalidate');
header('Pragma: private');
header('Expires: 0');

if ($is_remote) {
    // Handle remote files
    $context = stream_context_create([
        'http' => [
            'timeout' => 30,
            'user_agent' => 'StudyBuddy Download System'
        ]
    ]);
    
    $file_content = file_get_contents($file_path, false, $context);
    if ($file_content === false) {
        http_response_code(500);
        exit('Error downloading file.');
    }
    
    header('Content-Length: ' . strlen($file_content));
    echo $file_content;
} else {
    // Handle local files
    $file_size = filesize($file_path);
    header('Content-Length: ' . $file_size);
    
    // Use readfile for better memory management with large files
    if (readfile($file_path) === false) {
        http_response_code(500);
        exit('Error reading file.');
    }
}

/**
 * Sanitize filename for download
 */
function sanitizeFilename($filename) {
    // Remove/replace potentially dangerous characters
    $filename = preg_replace('/[^a-zA-Z0-9\-_\s\.]/', '', $filename);
    $filename = preg_replace('/\s+/', '_', trim($filename));
    $filename = substr($filename, 0, 100); // Limit length
    return $filename ?: 'document';
}

/**
 * Get MIME type based on file extension
 */
function getMimeType($extension) {
    $mimes = [
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'txt' => 'text/plain',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'zip' => 'application/zip',
        'rar' => 'application/x-rar-compressed'
    ];
    
    return $mimes[strtolower($extension)] ?? 'application/octet-stream';
}
?>