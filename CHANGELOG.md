# Changelog

All notable changes to MCP Bridge will be documented in this file.

## [1.7.4] - 2024-06-22

### Fixed
- Fixed URL auto-detection in `wp_upload_media` tool
- Removed incorrect default value for `source_type` parameter
- Added intelligent source type detection based on content

### Changed
- `source_type` is now optional and auto-detected when not specified
- URLs are automatically detected when starting with http:// or https://
- Local file paths are detected when the file exists
- Everything else defaults to base64

## [1.7.3] - 2024-06-20

### Security
- Changed minimum required capability from `edit_posts` to `manage_options` (administrator only)
- Disabled public test endpoint `/mcp/v1/test` to prevent version information disclosure
- Fixed potential privilege escalation risk where users with author role could access user management functions

### Features
- Enhanced `wp_upload_media` tool with URL upload support
  - Added `source_type` parameter supporting "file", "base64", and "url"
  - Added ability to download and upload images from remote URLs
  - Automatic file extension detection from MIME types
  - Enhanced validation for allowed file types
  - Maintained backward compatibility with existing parameters

### Changed
- All MCP operations now require administrator privileges
- Test endpoint moved to commented code for development use only
- `wp_upload_media` now uses unified `source` parameter instead of separate `file_data`/`file_path`

## [1.7.2] - Previous version

### Features
- Full MCP protocol support
- WordPress integration with various tools
- Application Password authentication