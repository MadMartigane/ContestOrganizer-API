<?php

namespace services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

if (!defined('PROJECT_ROOT_PATH')) {
    require_once('../utils/403.php');
}

class JwtService
{
    private const ACCESS_TOKEN_TTL = 3600;
    private const REFRESH_TOKEN_TTL = 604800;
    private const ALGORITHM = 'HS256';
    private const ISSUER = 'contest-api';

    private string $secret;

    public function __construct()
    {
        $this->secret = $_ENV['JWT_SECRET'] ?? throw new Exception('JWT_SECRET not configured');
    }

    /**
     * Generate access and refresh tokens
     *
     * @param array $userData User data containing user_id, email, role
     * @return array{access_token: string, refresh_token: string}
     */
    public function generateToken(array $userData): array
    {
        $now = time();
        $accessJti = $this->generateJti();
        $refreshJti = $this->generateJti();

        $accessTokenPayload = [
            'user_id' => $userData['user_id'],
            'email' => $userData['email'],
            'role' => $userData['role'],
            'jti' => $accessJti,
            'iat' => $now,
            'exp' => $now + self::ACCESS_TOKEN_TTL,
            'iss' => self::ISSUER,
        ];

        $refreshTokenPayload = [
            'user_id' => $userData['user_id'],
            'jti' => $refreshJti,
            'type' => 'refresh',
            'iat' => $now,
            'exp' => $now + self::REFRESH_TOKEN_TTL,
            'iss' => self::ISSUER,
        ];

        $accessToken = JWT::encode($accessTokenPayload, $this->secret, self::ALGORITHM);
        $refreshToken = JWT::encode($refreshTokenPayload, $this->secret, self::ALGORITHM);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
        ];
    }

    /**
     * Validate and decode a token
     *
     * @param string $token The JWT token to validate
     * @return object|null Decoded token or null if invalid
     */
    public function validateToken(string $token): ?object
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, self::ALGORITHM));
            return $decoded;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Decode token without validation (for blacklist checking)
     *
     * @param string $token The JWT token to decode
     * @return object|null Decoded token or null if decoding fails
     */
    public function decodeToken(string $token): ?object
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, self::ALGORITHM));
            return $decoded;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Generate a unique token ID
     *
     * @return string Unique token identifier
     */
    private function generateJti(): string
    {
        return bin2hex(random_bytes(16));
    }
}
