# Changelog

All notable changes to MCP Bridge will be documented in this file.

## [1.7.3] - 2024-06-20

### Security
- Changed minimum required capability from `edit_posts` to `manage_options` (administrator only)
- Disabled public test endpoint `/mcp/v1/test` to prevent version information disclosure
- Fixed potential privilege escalation risk where users with author role could access user management functions

### Changed
- All MCP operations now require administrator privileges
- Test endpoint moved to commented code for development use only

## [1.7.2] - Previous version

### Features
- Full MCP protocol support
- WordPress integration with various tools
- Application Password authentication