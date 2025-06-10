<?php
/**
 * Security Class for MCP Bridge
 *
 * @package McpBridge\Core
 */

namespace McpBridge\Core;

/**
 * Security utilities and enhanced protection
 */
class Security
{
    /**
     * Rate limiting storage
     */
    private static array $rateLimits = [];

    /**
     * Validate CORS origin
     *
     * @param string $origin
     * @return bool
     */
    public static function validateCorsOrigin(string $origin): bool
    {
        // Get allowed origins from settings
        $allowedOrigins = get_option('mcp_bridge_allowed_origins', []);
        
        // Default fallback for development
        $defaultAllowed = [
            'http://localhost',
            'https://localhost',
            'http://127.0.0.1',
            'https://127.0.0.1'
        ];
        
        $allowedOrigins = array_merge($allowedOrigins, $defaultAllowed);
        
        // Check if origin is in allowed list
        foreach ($allowedOrigins as $allowed) {
            if (strpos($origin, $allowed) === 0) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Rate limiting check
     *
     * @param string $identifier
     * @param int $limit
     * @param int $window
     * @return bool
     */
    public static function checkRateLimit(string $identifier, int $limit = 60, int $window = 3600): bool
    {
        $now = time();
        $windowStart = $now - $window;
        
        // Clean old entries
        if (isset(self::$rateLimits[$identifier])) {
            self::$rateLimits[$identifier] = array_filter(
                self::$rateLimits[$identifier],
                function($timestamp) use ($windowStart) {
                    return $timestamp > $windowStart;
                }
            );
        } else {
            self::$rateLimits[$identifier] = [];
        }
        
        // Check if limit exceeded
        if (count(self::$rateLimits[$identifier]) >= $limit) {
            Logger::warning('Rate limit exceeded', [
                'identifier' => $identifier,
                'current_count' => count(self::$rateLimits[$identifier]),
                'limit' => $limit
            ]);
            return false;
        }
        
        // Add current request
        self::$rateLimits[$identifier][] = $now;
        return true;
    }

    /**
     * Sanitize log data to prevent information leakage
     *
     * @param mixed $data
     * @return mixed
     */
    public static function sanitizeLogData($data)
    {
        if (is_string($data)) {
            // Redact potential passwords/tokens
            $patterns = [
                '/("password"|"token"|"key"|"secret"):\s*"[^"]*"/i' => '$1: "[REDACTED]"',
                '/Authorization:\s*Bearer\s+\S+/i' => 'Authorization: Bearer [REDACTED]',
                '/X-API-Key:\s*\S+/i' => 'X-API-Key: [REDACTED]'
            ];
            
            foreach ($patterns as $pattern => $replacement) {
                $data = preg_replace($pattern, $replacement, $data);
            }
        } elseif (is_array($data)) {
            foreach ($data as $key => $value) {
                if (in_array(strtolower($key), ['password', 'token', 'key', 'secret', 'auth'])) {
                    $data[$key] = '[REDACTED]';
                } else {
                    $data[$key] = self::sanitizeLogData($value);
                }
            }
        }
        
        return $data;
    }

    /**
     * Validate request signature (future implementation)
     *
     * @param string $payload
     * @param string $signature
     * @param string $secret
     * @return bool
     */
    public static function validateSignature(string $payload, string $signature, string $secret): bool
    {
        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Generate CSRF token
     *
     * @return string
     */
    public static function generateCsrfToken(): string
    {
        if (!session_id()) {
            session_start();
        }
        
        $token = bin2hex(random_bytes(32));
        $_SESSION['mcp_csrf_token'] = $token;
        return $token;
    }

    /**
     * Validate CSRF token
     *
     * @param string $token
     * @return bool
     */
    public static function validateCsrfToken(string $token): bool
    {
        if (!session_id()) {
            session_start();
        }
        
        return isset($_SESSION['mcp_csrf_token']) && 
               hash_equals($_SESSION['mcp_csrf_token'], $token);
    }

    /**
     * Check if IP is allowed
     *
     * @param string $ip
     * @return bool
     */
    public static function isIpAllowed(string $ip): bool
    {
        $allowedIps = get_option('mcp_bridge_allowed_ips', []);
        
        if (empty($allowedIps)) {
            return true; // No restrictions if not configured
        }
        
        foreach ($allowedIps as $allowedIp) {
            if (self::ipInRange($ip, $allowedIp)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if IP is in range
     *
     * @param string $ip
     * @param string $range
     * @return bool
     */
    private static function ipInRange(string $ip, string $range): bool
    {
        if (strpos($range, '/') === false) {
            return $ip === $range;
        }
        
        [$subnet, $mask] = explode('/', $range);
        return (ip2long($ip) & ~((1 << (32 - $mask)) - 1)) === ip2long($subnet);
    }
}