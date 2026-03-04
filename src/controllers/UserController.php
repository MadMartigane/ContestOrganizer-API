<?php

namespace controllers;

use middleware\AuthMiddleware;

if (!defined('PROJECT_ROOT_PATH')) {
    require_once('../utils/403.php');
}

class UserController
{
    private \DB $db;
    private AuthMiddleware $authMiddleware;

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
     * Handle POST /users - Create a new user (admin only)
     */
    public function createUser(): void
    {
        // Require authentication and admin role
        $currentUser = $this->authMiddleware->validateRequest();

        if ($currentUser === null) {
            $this->sendError('Authentication required', 401);
            return;
        }

        if (!isset($currentUser->role) || $currentUser->role !== 'admin') {
            $this->sendError('Forbidden: Admin access required', 403);
            return;
        }

        $body = $this->getRequestBody();

        // Validate required fields
        if (!isset($body['email']) || !isset($body['password']) || !isset($body['role'])) {
            $this->sendError('Email, password, and role are required', 400);
            return;
        }

        $email = trim($body['email']);
        $password = $body['password'];
        $role = trim($body['role']);

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->sendError('Invalid email format', 400);
            return;
        }

        // Validate password minimum length
        if (strlen($password) < 8) {
            $this->sendError('Password must be at least 8 characters', 400);
            return;
        }

        // Validate role
        $validRoles = ['admin', 'organizer', 'spectator'];
        if (!in_array($role, $validRoles, true)) {
            $this->sendError('Role must be one of: admin, organizer, spectator', 400);
            return;
        }

        // Check if email already exists
        if ($this->emailExists($email)) {
            $this->sendError('Email already exists', 400);
            return;
        }

        // Hash password with Argon2id
        $passwordHash = password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);

        // Insert user into database
        $userId = $this->insertUser($email, $passwordHash, $role);

        if ($userId === null) {
            $this->sendError('Failed to create user', 500);
            return;
        }

        // Return success response
        $response = [
            'id' => $userId,
            'email' => $email,
            'role' => $role,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $this->sendJsonResponse($response, 201);
    }

    /**
     * Handle GET /users - List all users (admin only)
     */
    public function listUsers(): void
    {
        // Require authentication and admin role
        $currentUser = $this->authMiddleware->validateRequest();

        if ($currentUser === null) {
            $this->sendError('Authentication required', 401);
            return;
        }

        if (!isset($currentUser->role) || $currentUser->role !== 'admin') {
            $this->sendError('Forbidden: Admin access required', 403);
            return;
        }

        // Get all users from database
        $users = $this->getAllUsers();

        // Return users list (excluding password_hash)
        $this->sendJsonResponse($users, 200);
    }

    /**
     * Handle DELETE /users/:id - Delete a user (admin only)
     */
    public function deleteUser(int $userId): void
    {
        // Require authentication and admin role
        $currentUser = $this->authMiddleware->validateRequest();

        if ($currentUser === null) {
            $this->sendError('Authentication required', 401);
            return;
        }

        if (!isset($currentUser->role) || $currentUser->role !== 'admin') {
            $this->sendError('Forbidden: Admin access required', 403);
            return;
        }

        // Prevent deleting own account
        $currentUserId = isset($currentUser->user_id) ? (int) $currentUser->user_id : null;

        if ($currentUserId === $userId) {
            $this->sendError('Cannot delete your own account', 403);
            return;
        }

        // Check if user exists
        $user = $this->getUserById($userId);

        if ($user === null) {
            $this->sendError('User not found', 404);
            return;
        }

        // Delete user from database
        $deleted = $this->deleteUserById($userId);

        if (!$deleted) {
            $this->sendError('Failed to delete user', 500);
            return;
        }

        // Return success response
        $response = [
            'message' => 'User deleted successfully',
        ];

        $this->sendJsonResponse($response, 200);
    }

    /**
     * Check if email already exists in database
     */
    private function emailExists(string $email): bool
    {
        $result = $this->db->fetchOne('SELECT id FROM users WHERE email = ?', [$email]);
        return $result !== null;
    }

    /**
     * Get user by ID
     */
    private function getUserById(int $userId): ?array
    {
        return $this->db->fetchOne('SELECT * FROM users WHERE id = ?', [$userId]);
    }

    /**
     * Get all users from database
     */
    private function getAllUsers(): array
    {
        return $this->db->query('SELECT id, email, role, created_at FROM users ORDER BY id ASC');
    }

    /**
     * Insert new user into database
     */
    private function insertUser(string $email, string $passwordHash, string $role): ?int
    {
        $this->db->execute(
            'INSERT INTO users (email, password_hash, role, created_at) VALUES (?, ?, ?, datetime("now"))',
            [$email, $passwordHash, $role]
        );

        return (int) $this->db->lastInsertId();
    }

    /**
     * Delete user by ID
     */
    private function deleteUserById(int $userId): bool
    {
        $rowCount = $this->db->execute('DELETE FROM users WHERE id = ?', [$userId]);
        return $rowCount > 0;
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
