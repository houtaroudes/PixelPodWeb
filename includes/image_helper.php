<?php
function handleServiceImage(array $file, string $urlInput, ?string $currentImage = null): array {
    $uploadDir = __DIR__ . '/../uploads/services/';
    $maxSize   = 5 * 1024 * 1024; // 5MB
    $allowed   = ['image/jpeg','image/png','image/webp','image/gif'];

    // File upload
    if (!empty($file['name']) && $file['error'] === UPLOAD_ERR_OK) {

        if ($file['size'] > $maxSize) {
            return ['success' => false, 'message' => 'Image too large. Max size is 5MB.'];
        }
        if (!in_array($file['type'], $allowed)) {
            return ['success' => false, 'message' => 'Invalid image type. Use JPG, PNG, WebP, or GIF.'];
        }

        $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'service_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . strtolower($ext);
        $destPath = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            return ['success' => false, 'message' => 'Failed to save uploaded image. Check folder permissions.'];
        }

        // Delete old uploaded image if exists
        if ($currentImage && strpos($currentImage, 'uploads/') !== false) {
            $oldPath = __DIR__ . '/../' . $currentImage;
            if (file_exists($oldPath)) @unlink($oldPath);
        }

        return [
            'success'    => true,
            'image'      => 'uploads/services/' . $filename,
            'image_type' => 'upload'
        ];
    }

    // URL input
    $url = trim($urlInput);
    if (!empty($url)) {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return ['success' => false, 'message' => 'Invalid image URL.'];
        }
        return [
            'success'    => true,
            'image'      => $url,
            'image_type' => 'url'
        ];
    }

    // No new image provided — keep existing
    return [
        'success'    => true,
        'image'      => $currentImage ?? null,
        'image_type' => null 
    ];
}

function getServiceImageSrc(?string $image, ?string $imageType): string {
    if (empty($image)) return '';
    if ($imageType === 'upload') {
        return SITE_URL . '/' . $image;
    }
    return $image; // URL type
}
