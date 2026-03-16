<?php

declare(strict_types=1);

function handleAttachments(string $method, ?int $id, ?string $action, Attachment $attachmentObj): void
{
    $currentUserId = (int) ($_SESSION['user_id'] ?? 0);
    $isAdmin = (($_SESSION['user_role'] ?? null) === Constants::ROLE_ADMIN);

    switch ($method) {
        case Constants::METHOD_GET:
            if ($id !== null && $action === 'download') {
                $attachment = $attachmentObj->getById($id);
                if ($attachment === null) {
                    respond(['message' => 'Attachment not found'], 404);
                }

                $entityType = (string) ($attachment['entity_type'] ?? '');
                $entityId = (int) ($attachment['entity_id'] ?? 0);

                if (!$attachmentObj->canAccessEntity($entityType, $entityId, $currentUserId, $isAdmin)) {
                    respond(['message' => 'Unauthorized'], 403);
                }

                $filePath = $attachmentObj->getAbsoluteFilePath($attachment);
                if (!is_file($filePath)) {
                    respond(['message' => 'Attachment file missing'], 404);
                }

                header_remove('Content-Type');
                $fileName = (string) ($attachment['original_name'] ?? 'attachment');
                $fallbackName = str_replace('"', '', $fileName);
                $encodedName = rawurlencode($fileName);
                header('Content-Type: ' . ($attachment['mime_type'] ?? 'application/octet-stream'));
                header('Content-Length: ' . (string) filesize($filePath));
                header('Content-Disposition: attachment; filename="' . $fallbackName . '"; filename*=UTF-8\'\'' . $encodedName);
                header('Cache-Control: private, max-age=0, no-store, no-cache, must-revalidate');
                header('Pragma: no-cache');

                readfile($filePath);
                exit;
            }

            $entityType = trim((string) ($_GET['entity_type'] ?? ''));
            $entityId = (int) ($_GET['entity_id'] ?? 0);

            if (!$attachmentObj->isValidEntityType($entityType) || $entityId <= 0) {
                respond(['message' => 'Invalid input'], 422);
            }

            if (!$attachmentObj->canAccessEntity($entityType, $entityId, $currentUserId, $isAdmin)) {
                respond(['message' => 'Unauthorized'], 403);
            }

            $attachments = $attachmentObj->listByEntity($entityType, $entityId);
            $items = array_map(static function (array $row): array {
                return [
                    'attachment_id' => (int) ($row['attachment_id'] ?? 0),
                    'original_name' => (string) ($row['original_name'] ?? ''),
                    'mime_type' => (string) ($row['mime_type'] ?? ''),
                    'file_size' => (int) ($row['file_size'] ?? 0),
                    'uploaded_by' => (int) ($row['uploaded_by'] ?? 0),
                    'uploaded_on' => (string) ($row['uploaded_on'] ?? ''),
                    'download_url' => APP_BASE . '/api/attachments/' . (int) ($row['attachment_id'] ?? 0) . '/download',
                ];
            }, $attachments);

            respond(['attachments' => $items]);
            break;

        case Constants::METHOD_POST:
            requireAdmin();

            $entityType = trim((string) ($_POST['entity_type'] ?? ''));
            $entityId = (int) ($_POST['entity_id'] ?? 0);
            $files = normalizeUploadedFiles($_FILES['attachments'] ?? null);

            if (!$attachmentObj->isValidEntityType($entityType) || $entityId <= 0) {
                respond(['success' => false, 'message' => 'invalid_input'], 422);
            }

            if (empty($files)) {
                respond(['success' => false, 'message' => 'missing_file'], 422);
            }

            $saved = [];
            foreach ($files as $file) {
                $result = $attachmentObj->createFromUpload($entityType, $entityId, $file, $currentUserId);
                if (!($result['success'] ?? false)) {
                    respond([
                        'success' => false,
                        'message' => $result['message'] ?? 'upload_failed',
                        'max_size_bytes' => $attachmentObj->maxFileSizeBytes(),
                    ], 422);
                }

                $saved[] = [
                    'attachment_id' => (int) ($result['attachment_id'] ?? 0),
                    'original_name' => (string) ($result['original_name'] ?? ''),
                    'mime_type' => (string) ($result['mime_type'] ?? ''),
                    'file_size' => (int) ($result['file_size'] ?? 0),
                    'download_url' => APP_BASE . '/api/attachments/' . (int) ($result['attachment_id'] ?? 0) . '/download',
                ];
            }

            respond(['success' => true, 'attachments' => $saved], 201);
            break;

        case Constants::METHOD_DELETE:
            requireAdmin();

            if ($id === null || $id <= 0) {
                respond(['message' => 'Attachment id is required'], 400);
            }

            $attachment = $attachmentObj->getById($id);
            if ($attachment === null) {
                respond(['message' => 'Attachment not found'], 404);
            }

            $deleted = $attachmentObj->deleteAttachment($id);
            respond(['success' => $deleted], $deleted ? 200 : 400);
            break;

        default:
            respond(['message' => 'Method not allowed'], 405);
    }
}

function normalizeUploadedFiles($files): array
{
    if (!is_array($files) || !isset($files['name'])) {
        return [];
    }

    if (!is_array($files['name'])) {
        return [$files];
    }

    $normalized = [];
    $count = count($files['name']);

    for ($index = 0; $index < $count; $index++) {
        $normalized[] = [
            'name' => $files['name'][$index] ?? '',
            'type' => $files['type'][$index] ?? '',
            'tmp_name' => $files['tmp_name'][$index] ?? '',
            'error' => $files['error'][$index] ?? UPLOAD_ERR_NO_FILE,
            'size' => $files['size'][$index] ?? 0,
        ];
    }

    return $normalized;
}
