<?php
/**
 * Secure Preview Generator
 * Generates watermarked preview images for the first 3 pages of documents
 * Prevents direct access to original files before purchase
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/helpers.php';
require_login();

header('Content-Type: application/json');

$note_id = (int)($_GET['id'] ?? 0);
$type = $_GET['type'] ?? 'pdf';

if ($note_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid note ID']);
    exit;
}

// Get note details
$sql = "SELECT n.file_path, LOWER(SUBSTRING_INDEX(n.file_path,'.',-1)) AS ext, n.title
        FROM notes n WHERE n.id = ? AND n.is_approved = 1 LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute([$note_id]);
$note = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$note) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Note not found']);
    exit;
}

$raw_fp = trim($note['file_path']);
$ext = strtolower($note['ext']);
$title = $note['title'];

// Build file paths
$UPLOAD_DIR = realpath(__DIR__ . '/uploads');
$PREVIEW_DIR = __DIR__ . '/previews';
$scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

// Create preview directory if it doesn't exist
if (!is_dir($PREVIEW_DIR)) {
    mkdir($PREVIEW_DIR, 0755, true);
}

// Get the actual file path
if (preg_match('#^https?://#i', $raw_fp) || str_starts_with($raw_fp, '/')) {
    $file_path = $raw_fp;
} else {
    $file_path = $UPLOAD_DIR . DIRECTORY_SEPARATOR . $raw_fp;
}

// Check if file exists
if (!file_exists($file_path)) {
    echo json_encode(['success' => false, 'error' => 'File not found']);
    exit;
}

/**
 * Generate preview images for PDF using PDF.js approach
 * Since ImageMagick might not be available, we'll use a different approach
 */
function generatePDFPreviews($note_id, $script_dir) {
    $previews = [];
    
    // For now, we'll create placeholder previews
    // In a real implementation, you'd use a server-side PDF processing library
    for ($i = 1; $i <= 3; $i++) {
        $preview_filename = "preview_{$note_id}_page_{$i}.jpg";
        $preview_path = __DIR__ . '/previews/' . $preview_filename;
        
        // Create a simple preview placeholder (you can replace this with actual PDF processing)
        if (!file_exists($preview_path)) {
            createPreviewPlaceholder($preview_path, $i);
        }
        
        $previews[] = $script_dir . '/previews/' . $preview_filename;
    }
    
    return ['success' => true, 'previews' => $previews];
}

/**
 * Create a preview placeholder image
 * Replace this with actual PDF page extraction in production
 */
function createPreviewPlaceholder($path, $page_num) {
    // Create a simple image placeholder
    $width = 600;
    $height = 800;
    
    $image = imagecreate($width, $height);
    
    // Colors
    $bg_color = imagecolorallocate($image, 248, 250, 252); // Light gray
    $border_color = imagecolorallocate($image, 203, 213, 225); // Border gray
    $text_color = imagecolorallocate($image, 71, 85, 105); // Dark gray
    $watermark_color = imagecolorallocate($image, 239, 68, 68); // Red
    
    // Fill background
    imagefill($image, 0, 0, $bg_color);
    
    // Draw border
    imagerectangle($image, 0, 0, $width-1, $height-1, $border_color);
    
    // Add page indicator
    $page_text = "Page $page_num";
    imagestring($image, 5, 20, 20, $page_text, $text_color);
    
    // Add watermark
    $watermark = "PREVIEW ONLY";
    $font_size = 5;
    $text_width = strlen($watermark) * imagefontwidth($font_size);
    $text_height = imagefontheight($font_size);
    
    // Center watermark
    $x = ($width - $text_width) / 2;
    $y = ($height - $text_height) / 2;
    
    imagestring($image, $font_size, $x, $y, $watermark, $watermark_color);
    
    // Add diagonal watermark
    imagettftext($image, 30, 45, $width/4, $height/2, $watermark_color, __DIR__ . '/arial.ttf', 'PREVIEW');
    
    // Save image
    imagejpeg($image, $path, 75);
    imagedestroy($image);
}

// Handle different file types
switch ($ext) {
    case 'pdf':
        $result = generatePDFPreviews($note_id, $scriptDir);
        break;
        
    case 'doc':
    case 'docx':
        $result = ['success' => true, 'type' => 'document', 'message' => 'Word document preview'];
        break;
        
    case 'ppt':
    case 'pptx':
        $result = ['success' => true, 'type' => 'presentation', 'message' => 'PowerPoint presentation preview'];
        break;
        
    default:
        $result = ['success' => false, 'error' => 'Unsupported file type for preview'];
        break;
}

echo json_encode($result);