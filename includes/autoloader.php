<?php
/**
 * PSR-4 Autoloader for MCP Bridge Plugin
 *
 * @package McpBridge
 */

// セキュリティチェック
if (!defined('ABSPATH')) {
    exit;
}

/**
 * MCP Bridge Autoloader Class
 */
class McpBridgeAutoloader
{
    /**
     * Namespace prefix
     */
    private const NAMESPACE_PREFIX = 'McpBridge\\';

    /**
     * Base directory for the namespace prefix
     */
    private string $baseDir;

    /**
     * Constructor
     *
     * @param string $baseDir Base directory for the namespace prefix
     */
    public function __construct(string $baseDir)
    {
        $this->baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    /**
     * Register the autoloader
     */
    public function register(): void
    {
        spl_autoload_register([$this, 'loadClass']);
    }

    /**
     * Load a class file
     *
     * @param string $class The fully-qualified class name
     * @return mixed The mapped file name on success, or boolean false on failure
     */
    public function loadClass(string $class)
    {
        // Does the class use the namespace prefix?
        $len = strlen(self::NAMESPACE_PREFIX);
        if (strncmp(self::NAMESPACE_PREFIX, $class, $len) !== 0) {
            // No, move to the next registered autoloader
            return false;
        }

        // Get the relative class name
        $relativeClass = substr($class, $len);

        // Replace the namespace prefix with the base directory, replace namespace
        // separators with directory separators in the relative class name, append
        // with .php
        $file = $this->baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

        // If the file exists, require it
        if (file_exists($file)) {
            require $file;
            return $file;
        }

        return false;
    }
}

// Initialize and register the autoloader
$autoloader = new McpBridgeAutoloader(MCP_BRIDGE_INCLUDES_PATH);
$autoloader->register();