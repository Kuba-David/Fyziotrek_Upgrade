<?php
declare(strict_types=1);

const UPLOAD_MAX_BYTES = 10 * 1024 * 1024;
const UPLOAD_MAX_WIDTH = 1600;

/**
 * Store an uploaded image from $_FILES[$field], downscaled and re-encoded with GD
 * (which also strips anything that isn't pixel data). Returns the public path
 * relative to the site root, null when no file was sent; throws on invalid input.
 */
function store_uploaded_image(string $field): ?string
{
    $file = $_FILES[$field] ?? null;
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('Obrázek se nepodařilo nahrát. Zkuste menší soubor.');
    }
    if ($file['size'] > UPLOAD_MAX_BYTES) {
        throw new RuntimeException('Obrázek je větší než 10 MB.');
    }

    $mime = detect_mime($file['tmp_name']);
    $image = match ($mime) {
        'image/jpeg' => @imagecreatefromjpeg($file['tmp_name']),
        'image/png' => @imagecreatefrompng($file['tmp_name']),
        'image/webp' => @imagecreatefromwebp($file['tmp_name']),
        default => false,
    };
    if (!$image) {
        throw new RuntimeException('Podporované formáty obrázku jsou JPG, PNG a WebP.');
    }

    if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
        $orientation = (int) (@exif_read_data($file['tmp_name'])['Orientation'] ?? 1);
        $angle = [3 => 180, 6 => -90, 8 => 90][$orientation] ?? 0;
        if ($angle) {
            $image = imagerotate($image, $angle, 0);
        }
    }

    $width = imagesx($image);
    $height = imagesy($image);
    if ($width > UPLOAD_MAX_WIDTH) {
        $resized = imagescale($image, UPLOAD_MAX_WIDTH, (int) round($height * UPLOAD_MAX_WIDTH / $width), IMG_BICUBIC);
        imagedestroy($image);
        $image = $resized;
    }

    $dir = ROOT_DIR . '/uploads/' . date('Y');
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        throw new RuntimeException('Nelze vytvořit složku pro obrázky.');
    }
    // WebP is smaller; fall back to JPEG on a PHP build without WebP support.
    $webp = function_exists('imagewebp');
    $name = date('Y') . '/' . bin2hex(random_bytes(8)) . ($webp ? '.webp' : '.jpg');
    imagesavealpha($image, true);
    $ok = $webp ? imagewebp($image, ROOT_DIR . '/uploads/' . $name, 82) : imagejpeg($image, ROOT_DIR . '/uploads/' . $name, 85);
    imagedestroy($image);
    if (!$ok) {
        throw new RuntimeException('Obrázek se nepodařilo uložit.');
    }
    return 'uploads/' . $name;
}

function delete_uploaded_image(string $path): void
{
    delete_uploaded_file($path);
}

function delete_uploaded_file(string $path): void
{
    if (str_starts_with($path, 'uploads/') && !str_contains($path, '..')) {
        @unlink(ROOT_DIR . '/' . $path);
    }
}

const DOCUMENT_EXTENSIONS = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'jpg', 'jpeg', 'png'];
const DOCUMENT_MAX_BYTES = 20 * 1024 * 1024;

/**
 * Store one uploaded document (PDF, Word, …) for the "Ke stažení" list.
 * $file is a single entry in the $_FILES shape. Returns the public path.
 */
function store_uploaded_document(array $file): string
{
    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('Soubor „' . $file['name'] . '“ se nepodařilo nahrát.');
    }
    if ($file['size'] > DOCUMENT_MAX_BYTES) {
        throw new RuntimeException('Soubor „' . $file['name'] . '“ je větší než 20 MB.');
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, DOCUMENT_EXTENSIONS, true)) {
        throw new RuntimeException('Soubor „' . $file['name'] . '“ má nepodporovaný formát. Povolené jsou PDF, Word, Excel, PowerPoint a obrázky.');
    }
    $mime = detect_mime($file['tmp_name']);
    if (str_starts_with($mime, 'text/') || str_contains($mime, 'php') || str_contains($mime, 'html')) {
        throw new RuntimeException('Soubor „' . $file['name'] . '“ nevypadá jako dokument.');
    }

    $dir = ROOT_DIR . '/uploads/soubory';
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        throw new RuntimeException('Nelze vytvořit složku pro soubory.');
    }
    $base = slugify(pathinfo($file['name'], PATHINFO_FILENAME));
    $name = substr($base, 0, 60) . '-' . bin2hex(random_bytes(3)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
        throw new RuntimeException('Soubor „' . $file['name'] . '“ se nepodařilo uložit.');
    }
    return 'uploads/soubory/' . $name;
}

/** Re-shape a multi-file input ($_FILES['x']['name'][0] …) into a list of single files. */
function uploaded_files_list(string $field): array
{
    $files = $_FILES[$field] ?? null;
    if (!$files || !is_array($files['name'])) {
        return [];
    }
    $list = [];
    foreach ($files['name'] as $i => $name) {
        if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $list[] = [
            'name' => (string) $name,
            'tmp_name' => $files['tmp_name'][$i],
            'error' => $files['error'][$i],
            'size' => $files['size'][$i],
        ];
    }
    return $list;
}

function file_size_label(string $path): string
{
    $bytes = @filesize(ROOT_DIR . '/' . $path);
    if (!$bytes) {
        return '';
    }
    return $bytes >= 1048576 ? number_format($bytes / 1048576, 1, ',', ' ') . ' MB' : max(1, (int) round($bytes / 1024)) . ' kB';
}

function detect_mime(string $path): string
{
    if (class_exists('finfo')) {
        return (string) (new finfo(FILEINFO_MIME_TYPE))->file($path);
    }
    $info = @getimagesize($path);
    return $info ? (string) $info['mime'] : 'application/octet-stream';
}
