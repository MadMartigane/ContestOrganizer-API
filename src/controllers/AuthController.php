<?php

namespace controllers;

use services\JwtService;
use middleware\AuthMiddleware;

if (!defined('PROJECT_ROOT_PATH')) {
    require_once('../utils/403.php');
}

class AuthController
{
    private \DB $db;
    private JwtService $jwtService;
    private AuthMiddleware $authMiddleware;

    public function __construct()
    {
        $this->jwtService = new JwtService();
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
     * Handle POST /auth/login
     */
    public function login(): void
    {
        $body = $this->getRequestBody();

        // Validate input
        if (!isset($body['email']) || !isset($body['password'])) {
            $this->sendError('Email and password are required', 400);
            return;
        }

        $email = trim($body['email']);
        $password = $body['password'];

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->sendError('Invalid email format', 400);
            return;
        }

        // Validate password not empty
        if (empty($password)) {
            $this->sendError('Password is required', 400);
            return;
        }

        // Query database for user
        $user = $this->findUserByEmail($email);

        if ($user === null) {
            $this->sendError('Invalid credentials', 401);
            return;
        }

        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            $this->sendError('Invalid credentials', 401);
            return;
        }

        // Generate JWT tokens
        $userData = [
            'user_id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
        ];

        $tokens = $this->jwtService->generateToken($userData);

        // Return success response
        $response = [
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'expires_in' => 3600,
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'role' => $user['role'],
            ],
        ];

        $this->sendJsonResponse($response, 200);
    }

    /**
     * Handle POST /auth/logout
     */
    public function logout(): void
    {
        // Require authentication
        $currentUser = $this->authMiddleware->validateRequest();

        if ($currentUser === null) {
            $this->sendError('Unauthorized', 401);
            return;
        }

        // Extract token jti (JWT ID)
        $jti = $currentUser->jti ?? null;

        if ($jti === null) {
            $this->sendError('Invalid token', 401);
            return;
        }

        // Add jti to token_blacklist table
        $this->addToBlacklist($jti, $currentUser->exp ?? time() + 3600);

        // Return success response
        $response = [
            'message' => 'Logged out successfully',
        ];

        $this->sendJsonResponse($response, 200);
    }

    /**
     * Find user by email in database
     */
    private function findUserByEmail(string $email): ?array
    {
        return $this->db->fetchOne('SELECT * FROM users WHERE email = ?', [$email]);
    }

    /**
     * Add token jti to blacklist
     */
    private function addToBlacklist(string $jti, int $expiresAt): bool
    {
        $this->db->execute(
            'INSERT INTO token_blacklist (jti, expires_at) VALUES (?, ?)',
            [$jti, $expiresAt]
        );
        return true;
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
            400 => '400 Bad Request',
            401 => '401 Unauthorized',
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
