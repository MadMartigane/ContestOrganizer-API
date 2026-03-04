<?php

namespace middleware;

use services\JwtService;
use controllers\CommonController;

if (!defined('PROJECT_ROOT_PATH')) {
    require_once('../utils/403.php');
}

class AuthMiddleware
{
    private static ?AuthMiddleware $instance = null;
    private ?object $currentUser = null;
    private JwtService $jwtService;

    private function __construct()
    {
        $this->jwtService = new JwtService();
    }

    /**
     * Get singleton instance
     */
    public static function getInstance(): AuthMiddleware
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Extract JWT token from Authorization header
     *
     * @return string|null Token string or null if not found
     */
    public function getTokenFromRequest(): ?string
    {
        $headers = getallheaders();
        
        if ($headers === false) {
            return null;
        }

        // Case-insensitive header lookup
        $authHeader = null;
        foreach ($headers as $name => $value) {
            if (strtolower($name) === 'authorization') {
                $authHeader = $value;
                break;
            }
        }

        if ($authHeader === null) {
            return null;
        }

        // Check for Bearer token format
        if (strpos($authHeader, 'Bearer ') === 0) {
            return substr($authHeader, 7);
        }

        return null;
    }

    /**
     * Check if token is blacklisted in database
     *
     * @param string $jti Token unique identifier
     * @return bool True if blacklisted
     */
    private function isTokenBlacklisted(string $jti): bool {
        require_once PROJECT_ROOT_PATH . 'models/db.php';
        $db = DB::getInstance();
        $result = $db->fetchOne(
            "SELECT 1 FROM token_blacklist WHERE jti = ?",
            [$jti]
        );
        return $result !== null;
    }

    /**
     * Validate the request by checking JWT token
     *
     * @return object|null Decoded token object with user data or null if invalid
     */
    public function validateRequest(): ?object
    {
        $token = $this->getTokenFromRequest();
        
        if ($token === null) {
            return null;
        }

        // Validate token signature and expiration
        $decoded = $this->jwtService->validateToken($token);
        
        if ($decoded === null) {
            return null;
        }

        // Check if token is blacklisted
        if (isset($decoded->jti) && $this->isTokenBlacklisted($decoded->jti)) {
            return null;
        }

        return $decoded;
    }

    /**
     * Main middleware handler - validates request and sets user context
     *
     * @return void
     */
    public function handle(): void
    {
        $decoded = $this->validateRequest();

        if ($decoded === null) {
            $this->sendUnauthorizedResponse('Invalid or missing authentication token');
            return;
        }

        // Store user data for controllers to access
        $this->currentUser = $decoded;
        
        // Store in global for easy access in controllers
        $_REQUEST['auth_user'] = (array) $decoded;
    }

    /**
     * Send 401 Unauthorized response and exit
     *
     * @param string $message Error message
     * @return void
     */
    private function sendUnauthorizedResponse(string $message = 'Unauthorized'): void
    {
        $ctrl = CommonController::getInstance();
        
        $response = [
            'procedure' => 'UNAUTHORIZED',
            'data' => null,
            'error' => [
                'message' => $message
            ]
        ];

        $ctrl->sendOutput(
            json_encode($response),
            [
                'HTTP/1.1 401 Unauthorized',
                'Content-Type: application/json;charset=UTF-8'
            ]
        );
        
        exit;
    }

    /**
     * Get current authenticated user data
     *
     * @return array|null User data array or null if not authenticated
     */
    public function getCurrentUser(): ?array
    {
        if ($this->currentUser === null) {
            return null;
        }

        return (array) $this->currentUser;
    }

    /**
     * Force authentication or throw error
     *
     * @return void
     * @throws \Exception If not authenticated
     */
    public function requireAuth(): void
    {
        if ($this->currentUser === null) {
            $this->sendUnauthorizedResponse('Authentication required');
        }
    }

    /**
     * Check if user has required role
     *
     * @param string ...$roles Allowed roles
     * @return void
     * @throws \Exception If user doesn't have required role
     */
    public function requireRole(string ...$roles): void
    {
        $this->requireAuth();

        if (!isset($this->currentUser->role)) {
            $this->sendUnauthorizedResponse('Insufficient permissions');
        }

        if (!in_array($this->currentUser->role, $roles)) {
            $this->sendUnauthorizedResponse('Insufficient permissions');
        }
    }

    /**
     * Check if user is authenticated
     *
     * @return bool True if authenticated
     */
    public function isAuthenticated(): bool
    {
        return $this->currentUser !== null;
    }

    /**
     * Get user ID from current user
     *
     * @return int|null User ID or null if not authenticated
     */
    public function getUserId(): ?int
    {
        if ($this->currentUser === null || !isset($this->currentUser->user_id)) {
            return null;
        }

        return (int) $this->currentUser->user_id;
    }

    /**
     * Get user role from current user
     *
     * @return string|null User role or null if not authenticated
     */
    public function getUserRole(): ?string
    {
        if ($this->currentUser === null || !isset($this->currentUser->role)) {
            return null;
        }

        return $this->currentUser->role;
    }
}
