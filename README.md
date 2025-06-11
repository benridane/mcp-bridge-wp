# MCP Bridge

A WordPress plugin that provides MCP (Model Context Protocol) interface with Application Password authentication, enabling AI agents to interact with WordPress sites through a standardized interface.

**Important**: This plugin **does not support streaming functionality**. It only supports simple request-response HTTP communication and intentionally excludes complex SSE (Server-Sent Events) or long-lived connections.

## Features

- **MCP Protocol Support**: Full JSON-RPC 2.0 compliant MCP implementation
- **WordPress Integration**: Native WordPress REST API integration
- **Application Password Authentication**: Secure authentication using WordPress Application Passwords
- **Extensible Tool System**: Easy registration and management of MCP tools
- **Comprehensive Logging**: Debug and audit logging with configurable levels
- **PSR-4 Autoloading**: Modern PHP development standards
- **StreamableHTTP Support**: MCP over HTTP compliant endpoints (without streaming)

## Technical Specifications

### ✅ Supported Features
- **Streamable HTTP Transport**: Single endpoint (`/mcp`) communication
- **JSON-RPC 2.0**: Standard request-response format
- **WordPress REST API**: Existing API MCP tool integration
- **Authentication**: Application Password, Bearer Token support
- **Stateless Communication**: Simple HTTP request-response pattern

### ❌ Intentionally Excluded Features
- **Streaming Communication**: SSE, WebSocket, or long-lived connections
- **Real-time Notifications**: Server-push communication
- **Bidirectional Communication**: Client-to-server communication only
- **Session Management**: Stateless communication only
- **Complex Transport Protocols**: Simple HTTP only

## Available Tools

### Phase 1 (Core)
- `wp_get_site_info` - Get basic WordPress site information
- `wp_get_posts` - Retrieve WordPress posts with filtering options
- `wp_create_post` - Create new WordPress posts

### Phase 2 (Posts & Pages) - New in v1.4.0
- `wp_posts_search` - Search and filter WordPress posts with pagination
- `wp_get_post` - Get a WordPress post by ID
- `wp_update_post` - Update an existing WordPress post
- `wp_delete_post` - Delete a WordPress post
- `wp_get_post_meta` - Get metadata for a WordPress post
- `wp_get_post_meta_value` - Get a specific post meta value by meta ID
- `wp_add_post_meta` - Add metadata to a WordPress post
- `wp_update_post_meta` - Update post metadata by meta ID
- `wp_delete_post_meta` - Delete post metadata by meta ID
- `wp_pages_search` - Search and filter WordPress pages with pagination
- `wp_get_page` - Get a WordPress page by ID
- `wp_add_page` - Create a new WordPress page
- `wp_update_page` - Update an existing WordPress page
- `wp_delete_page` - Delete a WordPress page

## Installation

1. Download the latest release
2. Upload to `/wp-content/plugins/` directory
3. Activate the plugin through WordPress admin
4. Configure Application Passwords for API access

## API Endpoints

- **Primary MCP Endpoint (StreamableHTTP compliant)**: `POST /mcp`
- **Legacy Endpoint**: `POST /wp-json/mcp/v1/rpc` (maintained for backward compatibility)
- **Tools Manifest**: `GET /wp-json/mcp/v1/tools`

## Authentication

The plugin supports multiple authentication methods:

1. **X-API-Key Header**: Base64 encoded `username:application_password`
2. **Authorization Header**: Bearer token with Base64 encoded credentials
3. **HTTP Basic Auth**: Standard HTTP authentication

## Usage Example

```bash
# Using the StreamableHTTP compliant endpoint
curl -X POST https://your-site.com/wp-json/mcp/v1/mcp \
  -H "Content-Type: application/json" \
  -H "X-API-Key: base64(username:app_password)" \
  -d '{"method": "getPosts", "params": {"limit": 5}}'

# Legacy endpoint (still supported)
curl -X POST https://your-site.com/wp-json/mcp/v1/rpc \
  -H "Content-Type: application/json" \
  -H "X-API-Key: base64(username:app_password)" \
  -d '{"method": "getPosts", "params": {"limit": 5}}'

```

## Requirements

- WordPress 5.0+
- PHP 8.0+
- Application Passwords enabled

## License

GPL v2 or later

## Contributing

Contributions are welcome! Please read the contributing guidelines before submitting pull requests.

## Changelog

### 1.4.0
- **🚀 Phase 2 Implementation Complete - Posts & Pages Tools**
  - Added comprehensive Posts tools with full CRUD operations
  - Implemented `wp_posts_search` for advanced post filtering and pagination
  - Added `wp_get_post`, `wp_update_post`, `wp_delete_post` for single post operations
  - Implemented complete Post Meta management tools
  - Added `wp_get_post_meta`, `wp_add_post_meta`, `wp_update_post_meta`, `wp_delete_post_meta`
  - Implemented comprehensive Pages tools with hierarchical support
  - Added `wp_pages_search`, `wp_get_page`, `wp_add_page`, `wp_update_page`, `wp_delete_page`
  - Enhanced REST API alias integration for all WordPress standard endpoints
  - Improved tool organization with dedicated namespace structure
  - All tools maintain existing functionality while adding advanced features
  - Full backward compatibility maintained for existing implementations

### 1.3.0
- **🛡️ Enhanced Security Features**
  - Added comprehensive Security class with multi-layer protection
  - Implemented CORS origin validation with configurable allowed domains
  - Added IP address restrictions with CIDR range support
  - Implemented rate limiting (100 requests per hour per IP)
  - Enhanced logging with automatic credential sanitization
  - Added proxy-aware client IP detection (Cloudflare, X-Forwarded-For support)
  - Improved authentication with enhanced error handling
- **⚙️ Advanced Admin Settings**
  - Added Security Settings section in admin panel
  - Configurable CORS origins management
  - IP restriction management interface
  - Rate limiting toggle with admin controls
  - Enhanced input validation and sanitization
- **🔧 Core Improvements**
  - Refactored WpMcp class with security integration
  - Enhanced session management with validation
  - Improved error responses with security headers
  - Better logging structure with sensitive data protection
  - Enhanced MCP protocol compliance

### 1.1.7
- **🚀 Post Creation Bug Fixes**
  - Fixed HTTP 500 error when creating posts via MCP
  - Improved REST API route handling and request processing
  - Enhanced error handling with detailed logging for post creation
  - Added direct callback implementation for `wp_create_post` tool
  - Better parameter validation and sanitization
  - Improved MCP-compliant response formatting for created posts

### 1.1.6
- Fixed logging configuration and enhanced debugging capabilities
- Improved tool registration system with better error handling
- Enhanced MCP protocol compliance

### 1.1.5
- **🔧 MCP Inspector v0.14.0 Zod Validation Fix**
  - Fixed `capabilities.logging` field type compatibility with MCP Inspector v0.14.0
  - Changed logging capabilities from array to object to meet Zod schema validation requirements
  - Resolved "Expected object, received array" validation error in MCP Inspector
  - Enhanced MCP protocol compliance for better client compatibility
  - Improved initialization response format for modern MCP clients

### 1.1.4
- **🔧 MCP Inspector Compatibility Fixes**
  - Fixed `sessionId undefined` error in MCP Inspector console output
  - Enhanced session management to accept and use client-provided session IDs
  - Improved Connection Error handling with better session tracking
  - Added comprehensive CORS headers for MCP Inspector compatibility
  - Enhanced debugging output with session status tracking
  - Added X-Session-Status and X-Server-Name headers for better client identification
  - Improved error responses with consistent session ID headers
  - Enhanced logging for MCP Inspector specific debugging

### 1.1.3
- Enhanced MCP Inspector compatibility with improved CORS handling
- Added comprehensive preflight OPTIONS request support
- Enhanced session management with guaranteed session ID availability
- Improved authentication flow for initialization methods
- Added prompts capability to server capabilities
- Enhanced logging for better connection debugging
- Fixed connection errors in MCP Inspector UI

### 1.1.2
- Fixed MCP Inspector sessionId undefined issue
- Enhanced session management with guaranteed session ID generation
- Improved Streamable HTTP transport compatibility
- Added X-MCP-Protocol-Version header for better client compatibility
- Enhanced logging for session tracking and debugging

### 1.1.1
- Added complete MCP Inspector compatibility
- Implemented `notifications/initialized` handshake support
- Enhanced MCP protocol compliance with proper initialization sequence
- Added detailed session tracking with X-MCP-Session-ID headers
- Improved authentication method detection and error handling
- Enhanced logging for better debugging and monitoring
- Maintained backward compatibility with legacy endpoints

### 1.1.0
- Added StreamableHTTP compliant `/mcp` endpoint
- Maintained backward compatibility with legacy endpoints
- Enhanced MCP over HTTP support

### 1.0.0
- Initial release
- Basic MCP protocol implementation
- WordPress REST API integration
- Application Password authentication
- Core tool set (site info, posts CRUD)