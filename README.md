# MCP Bridge

WordPress plugin that provides a Model Context Protocol (MCP) interface for seamless integration with AI tools and external applications.

## Features

- **MCP Protocol Support**: Full JSON-RPC 2.0 compliant MCP implementation
- **WordPress Integration**: Native WordPress REST API integration
- **Application Password Authentication**: Secure authentication using WordPress Application Passwords
- **Extensible Tool System**: Easy registration and management of MCP tools
- **Comprehensive Logging**: Debug and audit logging with configurable levels
- **PSR-4 Autoloading**: Modern PHP development standards

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

- `GET /wp-json/mcp/v1/tools` - Get available tools manifest
- `POST /wp-json/mcp/v1/rpc` - Execute MCP JSON-RPC requests

## Authentication

The plugin supports multiple authentication methods:

1. **X-API-Key Header**: Base64 encoded `username:application_password`
2. **Authorization Header**: Bearer token with Base64 encoded credentials
3. **HTTP Basic Auth**: Standard HTTP authentication

## Usage Example

```bash
# Get available tools
curl -X GET https://your-site.com/wp-json/mcp/v1/tools

# Get site information
curl -X POST https://your-site.com/wp-json/mcp/v1/rpc \
  -H "Content-Type: application/json" \
  -H "X-API-Key: base64(username:app_password)" \
  -d '{"method": "wp_get_site_info", "id": 1}'
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

### 1.0.0
- Initial release
- Basic MCP protocol implementation
- WordPress REST API integration
- Application Password authentication
- Core tool set (site info, posts CRUD)