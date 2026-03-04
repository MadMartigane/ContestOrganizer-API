<?php

namespace controllers;

use middleware\AuthMiddleware;

if (!defined('PROJECT_ROOT_PATH')) {
    require_once('../utils/403.php');
}

class FileController
{
    private \DB $db;
    private AuthMiddleware $authMiddleware;

    private const MAX_CONTENT_SIZE = 2000000;

    public function __construct()
    {
        $this->authMiddleware = AuthMiddleware::getInstance();
        $this->db = $this->getDatabaseConnection();
    }

    /**
     * Get database connection
     */
    private function getDatabaseConnection(): \DB
    {
        require_once PROJECT_ROOT_PATH . 'models/db.php';
        return \DB::getInstance();
    }

    /**
     * Handle GET /files
     * Returns list of files based on user role
     */
    public function listFiles(): void
    {
        $currentUser = $this->authMiddleware->validateRequest();

        if ($currentUser === null) {
            $this->sendError('Unauthorized', 401);
            return;
        }

        $role = $currentUser->role ?? 'spectator';
        $userId = $currentUser->user_id ?? null;

        $files = $this->getFilesByRole($role, $userId);

        $this->sendJsonResponse($files, 200);
    }

    /**
     * Handle GET /files/:id
     * Returns file content by ID
     */
    public function getFile(int $fileId): void
    {
        $currentUser = $this->authMiddleware->validateRequest();

        if ($currentUser === null) {
            $this->sendError('Unauthorized', 401);
            return;
        }

        $file = $this->findFileById($fileId);

        if ($file === null) {
            $this->sendError('File not found', 404);
            return;
        }

        unset($file['content']);
        $this->sendJsonResponse($file, 200);
    }

    /**
     * Handle POST /files
     * Creates a new file
     */
    public function createFile(): void
    {
        $currentUser = $this->authMiddleware->validateRequest();

        if ($currentUser === null) {
            $this->sendError('Unauthorized', 401);
            return;
        }

        $body = $this->getRequestBody();

        if (!isset($body['filename']) || empty(trim($body['filename']))) {
            $this->sendError('Filename is required', 400);
            return;
        }

        if (!isset($body['content']) || empty($body['content'])) {
            $this->sendError('Content is required', 400);
            return;
        }

        if (strlen($body['content']) > self::MAX_CONTENT_SIZE) {
            $this->sendError('Content exceeds maximum size of 2MB', 400);
            return;
        }

        $filename = trim($body['filename']);
        $content = $body['content'];
        $userId = $currentUser->user_id;

        $fileId = $this->insertFile($userId, $filename, $content);

        if ($fileId === null) {
            $this->sendError('Failed to create file', 500);
            return;
        }

        $file = $this->findFileById($fileId);
        unset($file['content']);

        $this->sendJsonResponse($file, 201);
    }

    /**
     * Handle PUT /files/:id
     * Updates an existing file
     */
    public function updateFile(int $fileId): void
    {
        $currentUser = $this->authMiddleware->validateRequest();

        if ($currentUser === null) {
            $this->sendError('Unauthorized', 401);
            return;
        }

        $file = $this->findFileById($fileId);

        if ($file === null) {
            $this->sendError('File not found', 404);
            return;
        }

        $role = $currentUser->role ?? 'spectator';
        $userId = $currentUser->user_id ?? null;

        if (!$this->canEditFile($role, $file['user_id'], $userId)) {
            $this->sendError('You do not have permission to edit this file', 403);
            return;
        }

        $body = $this->getRequestBody();

        $filename = $file['filename'];
        $content = $file['content'];

        if (isset($body['filename']) && !empty(trim($body['filename']))) {
            $filename = trim($body['filename']);
        }

        if (isset($body['content'])) {
            if (strlen($body['content']) > self::MAX_CONTENT_SIZE) {
                $this->sendError('Content exceeds maximum size of 2MB', 400);
                return;
            }
            $content = $body['content'];
        }

        $this->updateFileInDb($fileId, $filename, $content);

        $updatedFile = $this->findFileById($fileId);
        unset($updatedFile['content']);

        $this->sendJsonResponse($updatedFile, 200);
    }

    /**
     * Handle DELETE /files/:id
     * Deletes a file
     */
    public function deleteFile(int $fileId): void
    {
        $currentUser = $this->authMiddleware->validateRequest();

        if ($currentUser === null) {
            $this->sendError('Unauthorized', 401);
            return;
        }

        $file = $this->findFileById($fileId);

        if ($file === null) {
            $this->sendError('File not found', 404);
            return;
        }

        $role = $currentUser->role ?? 'spectator';
        $userId = $currentUser->user_id ?? null;

        if (!$this->canEditFile($role, $file['user_id'], $userId)) {
            $this->sendError('You do not have permission to delete this file', 403);
            return;
        }

        $this->deleteFileFromDb($fileId);

        $this->sendJsonResponse(['message' => 'File deleted successfully'], 200);
    }

    /**
     * Get files based on user role
     */
    private function getFilesByRole(string $role, ?int $userId): array
    {
        return $this->db->query('SELECT id, user_id, filename, created_at, updated_at FROM files ORDER BY created_at DESC');
    }

    /**
     * Find file by ID
     */
    private function findFileById(int $id): ?array
    {
        return $this->db->fetchOne('SELECT * FROM files WHERE id = ?', [$id]);
    }

    /**
     * Insert new file into database
     */
    private function insertFile(int $userId, string $filename, string $content): ?int
    {
        $now = date('Y-m-d H:i:s');

        $this->db->execute(
            'INSERT INTO files (user_id, filename, content, created_at, updated_at) VALUES (?, ?, ?, ?, ?)',
            [$userId, $filename, $content, $now, $now]
        );

        return (int) $this->db->lastInsertId();
    }

    /**
     * Update file in database
     */
    private function updateFileInDb(int $id, string $filename, string $content): bool
    {
        $now = date('Y-m-d H:i:s');

        $this->db->execute(
            'UPDATE files SET filename = ?, content = ?, updated_at = ? WHERE id = ?',
            [$filename, $content, $now, $id]
        );

        return true;
    }

    /**
     * Delete file from database
     */
    private function deleteFileFromDb(int $id): bool
    {
        $this->db->execute('DELETE FROM files WHERE id = ?', [$id]);
        return true;
    }

    /**
     * Check if user can edit/delete file based on role
     */
    private function canEditFile(string $role, int $fileOwnerId, ?int $userId): bool
    {
        if ($role === 'admin') {
            return true;
        }

        return $fileOwnerId === $userId;
    }

    /**
     * Parse JSON input from request body
     */
    private function getRequestBody(): array
    {
        $input = file_get_contents('php://input');

        if (empty($input)) {
            return [];
        }

        $data = json_decode($input, true);

        return is_array($data) ? $data : [];
    }

    /**
     * Send JSON response
     */
    private function sendJsonResponse(array $data, int $code): void
    {
        $httpMessages = [
            200 => '200 OK',
            201 => '201 Created',
            400 => '400 Bad Request',
            401 => '401 Unauthorized',
            403 => '403 Forbidden',
            404 => '404 Not Found',
            500 => '500 Internal Server Error',
        ];

        $httpCode = $httpMessages[$code] ?? '200 OK';

        header_remove('Set-Cookie');
        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json;charset=UTF-8');
        header($httpCode, true, $code);

        echo json_encode($data);
    }

    /**
     * Send error response
     */
    private function sendError(string $message, int $code): void
    {
        $response = [
            'error' => [
                'message' => $message,
            ],
        ];

        $this->sendJsonResponse($response, $code);
    }
}
