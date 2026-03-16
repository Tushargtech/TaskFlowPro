<?php

declare(strict_types=1);

class Attachment
{
    private const ENTITY_TASK = 'task';
    private const ENTITY_PROJECT = 'project';
    private const MAX_FILE_SIZE_BYTES = 5 * 1024 * 1024;
    private const ALLOWED_EXTENSIONS = ['pdf', 'doc', 'docx', 'png', 'jpg', 'jpeg', 'txt'];

    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function isValidEntityType(string $entityType): bool
    {
        return in_array($entityType, [self::ENTITY_TASK, self::ENTITY_PROJECT], true);
    }

    public function listByEntity(string $entityType, int $entityId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT attachment_id, entity_type, entity_id, original_name, mime_type, file_size, uploaded_by, uploaded_on
             FROM attachments
             WHERE entity_type = :entity_type AND entity_id = :entity_id
             ORDER BY uploaded_on DESC, attachment_id DESC'
        );

        $stmt->execute([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $attachmentId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT attachment_id, entity_type, entity_id, original_name, stored_name, mime_type, file_size, uploaded_by, uploaded_on
             FROM attachments
             WHERE attachment_id = :attachment_id'
        );

        $stmt->execute(['attachment_id' => $attachmentId]);
        $attachment = $stmt->fetch(PDO::FETCH_ASSOC);

        return $attachment ?: null;
    }

    public function entityExists(string $entityType, int $entityId): bool
    {
        if ($entityId <= 0 || !$this->isValidEntityType($entityType)) {
            return false;
        }

        if ($entityType === self::ENTITY_TASK) {
            $stmt = $this->pdo->prepare('SELECT 1 FROM tasks WHERE task_id = :id');
            $stmt->execute(['id' => $entityId]);
            return (bool) $stmt->fetchColumn();
        }

        $stmt = $this->pdo->prepare('SELECT 1 FROM projects WHERE project_id = :id');
        $stmt->execute(['id' => $entityId]);
        return (bool) $stmt->fetchColumn();
    }

    public function canAccessEntity(string $entityType, int $entityId, int $userId, bool $isAdmin): bool
    {
        if ($entityId <= 0 || $userId <= 0 || !$this->isValidEntityType($entityType)) {
            return false;
        }

        if ($isAdmin) {
            return $this->entityExists($entityType, $entityId);
        }

        if ($entityType === self::ENTITY_PROJECT) {
            return $this->entityExists($entityType, $entityId);
        }

        $stmt = $this->pdo->prepare(
            'SELECT 1
             FROM tasks
             WHERE task_id = :task_id AND task_assigned_to = :user_id'
        );

        $stmt->execute([
            'task_id' => $entityId,
            'user_id' => $userId,
        ]);

        return (bool) $stmt->fetchColumn();
    }

    public function createFromUpload(string $entityType, int $entityId, array $file, int $uploadedBy): array
    {
        if (!$this->isValidEntityType($entityType)) {
            return ['success' => false, 'message' => 'invalid_entity'];
        }

        if (!$this->entityExists($entityType, $entityId)) {
            return ['success' => false, 'message' => 'entity_not_found'];
        }

        $errorCode = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($errorCode !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => $this->uploadErrorMessage($errorCode)];
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            return ['success' => false, 'message' => 'invalid_upload'];
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > self::MAX_FILE_SIZE_BYTES) {
            return ['success' => false, 'message' => 'file_too_large'];
        }

        $originalName = trim((string) ($file['name'] ?? ''));
        if ($originalName === '') {
            return ['success' => false, 'message' => 'invalid_filename'];
        }

        $safeOriginalName = $this->sanitizeOriginalName($originalName);
        $extension = strtolower((string) pathinfo($safeOriginalName, PATHINFO_EXTENSION));
        if ($extension === '' || !in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            return ['success' => false, 'message' => 'unsupported_file_type'];
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = (string) ($finfo->file($tmpName) ?: 'application/octet-stream');

        $storageDir = $this->getStorageDirectory();
        if (!is_dir($storageDir) && !mkdir($storageDir, 0777, true) && !is_dir($storageDir)) {
            return ['success' => false, 'message' => 'storage_unavailable'];
        }

        if (!is_writable($storageDir)) {
            @chmod($storageDir, 0777);
        }

        if (!is_writable($storageDir)) {
            return ['success' => false, 'message' => 'storage_unavailable'];
        }

        $storedName = bin2hex(random_bytes(16)) . '.' . $extension;
        $targetPath = $storageDir . DIRECTORY_SEPARATOR . $storedName;

        if (!move_uploaded_file($tmpName, $targetPath)) {
            return ['success' => false, 'message' => 'upload_move_failed'];
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO attachments (entity_type, entity_id, original_name, stored_name, mime_type, file_size, uploaded_by)
             VALUES (:entity_type, :entity_id, :original_name, :stored_name, :mime_type, :file_size, :uploaded_by)'
        );

        $saved = $stmt->execute([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'original_name' => $safeOriginalName,
            'stored_name' => $storedName,
            'mime_type' => $mimeType,
            'file_size' => $size,
            'uploaded_by' => $uploadedBy,
        ]);

        if (!$saved) {
            @unlink($targetPath);
            return ['success' => false, 'message' => 'db_save_failed'];
        }

        return [
            'success' => true,
            'attachment_id' => (int) $this->pdo->lastInsertId(),
            'original_name' => $safeOriginalName,
            'mime_type' => $mimeType,
            'file_size' => $size,
        ];
    }

    public function deleteAttachment(int $attachmentId): bool
    {
        $attachment = $this->getById($attachmentId);
        if ($attachment === null) {
            return false;
        }

        $stmt = $this->pdo->prepare('DELETE FROM attachments WHERE attachment_id = :attachment_id');
        $deleted = $stmt->execute(['attachment_id' => $attachmentId]);

        if ($deleted) {
            $filePath = $this->getStorageDirectory() . DIRECTORY_SEPARATOR . ($attachment['stored_name'] ?? '');
            if (is_file($filePath)) {
                @unlink($filePath);
            }
        }

        return $deleted;
    }

    public function getAbsoluteFilePath(array $attachment): string
    {
        return $this->getStorageDirectory() . DIRECTORY_SEPARATOR . ($attachment['stored_name'] ?? '');
    }

    public function getStorageDirectory(): string
    {
        return APP_ROOT . '/storage/attachments';
    }

    public function maxFileSizeBytes(): int
    {
        return self::MAX_FILE_SIZE_BYTES;
    }

    private function sanitizeOriginalName(string $name): string
    {
        $base = basename($name);
        $normalized = preg_replace('/[^A-Za-z0-9._ -]/', '_', $base);
        $trimmed = trim((string) $normalized);

        return $trimmed === '' ? 'file' : $trimmed;
    }

    private function uploadErrorMessage(int $errorCode): string
    {
        return match ($errorCode) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'file_too_large',
            UPLOAD_ERR_PARTIAL => 'upload_partial',
            UPLOAD_ERR_NO_FILE => 'missing_file',
            default => 'upload_failed',
        };
    }
}
