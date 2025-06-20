# Security Policy

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 1.7.3   | :white_check_mark: |
| < 1.7.3 | :x:                |

## Reporting a Vulnerability

If you discover a security vulnerability within MCP Bridge, please send an email to the plugin maintainer. All security vulnerabilities will be promptly addressed.

**Please do not report security vulnerabilities through public GitHub issues.**

## Security Best Practices

1. **Always use the latest version** - Security updates are released as needed
2. **Use strong application passwords** - Generate unique passwords for each integration
3. **Limit access** - Only grant MCP access to trusted applications
4. **Monitor logs** - Regularly check plugin logs for suspicious activity

## Recent Security Updates

### Version 1.7.3
- Elevated required permissions to `manage_options` (administrator only)
- Removed public test endpoint that exposed version information
- Enhanced access control for sensitive operations

## Authentication Methods

MCP Bridge supports multiple authentication methods:
- WordPress Application Passwords (recommended)
- Bearer Token authentication
- HTTP Basic Authentication

All authentication methods require administrator privileges as of version 1.7.3.