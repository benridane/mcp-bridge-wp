<?php
/**
 * Logger Class for MCP Bridge Plugin
 *
 * @package McpBridge\Core
 */

namespace McpBridge\Core;

/**
 * Logger Class
 */
class Logger
{
    /**
     * Log levels
     */
    public const DEBUG = 'DEBUG';
    public const INFO = 'INFO';
    public const WARNING = 'WARNING';
    public const ERROR = 'ERROR';

    /**
     * Log file path
     */
    private static ?string $logFile = null;

    /**
     * Initialize logger
     */
    public static function init(): void
    {
        self::$logFile = MCP_BRIDGE_LOGS_PATH . 'mcp-bridge.log';
        
        // Create logs directory if it doesn't exist
        if (!file_exists(MCP_BRIDGE_LOGS_PATH)) {
            wp_mkdir_p(MCP_BRIDGE_LOGS_PATH);
        }

        // Create .htaccess to protect log files
        self::createHtaccess();
    }

    /**
     * Log a message
     *
     * @param string $level Log level
     * @param string $message Log message
     * @param array $context Additional context
     */
    public static function log(string $level, string $message, array $context = []): void
    {
        if (!MCP_BRIDGE_LOG_ENABLED) {
            return;
        }

        if (!self::$logFile) {
            self::init();
        }

        $timestamp = current_time('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        $logEntry = "[{$timestamp}] [{$level}] {$message}{$contextStr}" . PHP_EOL;

        // Write to file
        file_put_contents(self::$logFile, $logEntry, FILE_APPEND | LOCK_EX);

        // Also log to WordPress debug.log if WP_DEBUG is enabled
        if (MCP_BRIDGE_DEBUG && function_exists('error_log')) {
            error_log("MCP Bridge [{$level}] {$message}");
        }
    }

    /**
     * Log debug message
     *
     * @param string $message
     * @param array $context
     */
    public static function debug(string $message, array $context = []): void
    {
        if (MCP_BRIDGE_DEBUG) {
            self::log(self::DEBUG, $message, $context);
        }
    }

    /**
     * Log info message
     *
     * @param string $message
     * @param array $context
     */
    public static function info(string $message, array $context = []): void
    {
        self::log(self::INFO, $message, $context);
    }

    /**
     * Log warning message
     *
     * @param string $message
     * @param array $context
     */
    public static function warning(string $message, array $context = []): void
    {
        self::log(self::WARNING, $message, $context);
    }

    /**
     * Log error message
     *
     * @param string $message
     * @param array $context
     */
    public static function error(string $message, array $context = []): void
    {
        self::log(self::ERROR, $message, $context);
    }

    /**
     * Create .htaccess file to protect logs
     */
    private static function createHtaccess(): void
    {
        $htaccessFile = MCP_BRIDGE_LOGS_PATH . '.htaccess';
        
        if (!file_exists($htaccessFile)) {
            $content = "Order deny,allow\nDeny from all\n";
            file_put_contents($htaccessFile, $content);
        }
    }

    /**
     * Get recent log entries
     *
     * @param int $lines Number of lines to read
     * @return array
     */
    public static function getRecentLogs(int $lines = 100): array
    {
        if (!self::$logFile || !file_exists(self::$logFile)) {
            return [];
        }

        $content = file_get_contents(self::$logFile);
        $logLines = explode(PHP_EOL, $content);
        $logLines = array_filter($logLines); // Remove empty lines
        
        return array_slice($logLines, -$lines);
    }

    /**
     * Clear log file
     */
    public static function clearLogs(): void
    {
        if (self::$logFile && file_exists(self::$logFile)) {
            file_put_contents(self::$logFile, '');
        }
    }
}