<?php
declare(strict_types=1);

const QII_PRODUCT_IMAGE_DB_PREFIX = 'images/products/';

function qii_product_image_directory(): string
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'products';
}

function qii_product_image_mime_type(string $tmpName): string
{
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo === false) {
        return '';
    }

    $type = finfo_file($finfo, $tmpName);
    finfo_close($finfo);

    return is_string($type) ? $type : '';
}

function qii_store_product_image(string $field, ?string $existing = null): ?string
{
    if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return $existing;
    }

    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];
    $tmpName = (string)$_FILES[$field]['tmp_name'];
    $mimeType = qii_product_image_mime_type($tmpName);

    if (!isset($extensions[$mimeType]) || getimagesize($tmpName) === false) {
        throw new RuntimeException('Unsupported product image type');
    }

    $directory = qii_product_image_directory();
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException('Cannot create product image directory');
    }
    if (!is_writable($directory)) {
        throw new RuntimeException('Product image directory is not writable');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mimeType];
    $target = $directory . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($tmpName, $target)) {
        throw new RuntimeException('Cannot save uploaded product image');
    }
    clearstatcache(true, $target);
    if (!is_file($target) || filesize($target) <= 0) {
        @unlink($target);
        throw new RuntimeException('Uploaded product image was not written completely');
    }

    @chmod($target, 0644);

    return QII_PRODUCT_IMAGE_DB_PREFIX . $filename;
}

function qii_product_image_url(?string $path): string
{
    $path = trim((string)$path);
    if ($path === '' || preg_match('#^(?:https?:)?//#i', $path)) {
        return $path;
    }

    return '/' . ltrim(str_replace('\\', '/', $path), '/');
}

