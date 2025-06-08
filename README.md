# MCP Bridge

A WordPress plugin that bridges the WordPress REST API with the Model Context Protocol (MCP), enabling AI agents to interact with WordPress sites through a standardized interface.

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

- `wp_get_site_info` - Get basic WordPress site information
- `wp_get_posts` - Retrieve WordPress posts with filtering options
- `wp_create_post` - Create new WordPress posts

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
curl -X POST https://your-site.com/mcp \
  -H "Content-Type: application/json" \
  -H "X-API-Key: base64(username:app_password)" \
  -d '{"method": "getPosts", "params": {"limit": 5}}'

# Legacy endpoint (still supported)
curl -X POST https://your-site.com/wp-json/mcp/v1/rpc \
  -H "Content-Type: application/json" \
  -H "X-API-Key: base64(username:app_password)" \
  -d '{"method": "getPosts", "params": {"limit": 5}}'

# Get available tools
curl -X GET https://your-site.com/wp-json/mcp/v1/tools
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