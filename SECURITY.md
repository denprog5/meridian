# Security Policy

## Supported Versions

The following versions of Meridian are currently receiving security updates:

| Version | Supported          |
| ------- | ------------------ |
| 1.x     | :white_check_mark: |

## Reporting a Vulnerability

The Meridian team takes security vulnerabilities seriously. We appreciate your efforts to responsibly disclose your findings.

### How to Report

If you discover a security vulnerability within Meridian, please send an email to **denisprogressive@gmail.com**. All security vulnerabilities will be promptly addressed.

### What to Include

When reporting a vulnerability, please include:

- A description of the vulnerability
- Steps to reproduce the issue
- Potential impact of the vulnerability
- Any possible mitigations you've identified

### Response Timeline

- **Initial Response**: Within 48 hours of receiving your report
- **Status Update**: Within 7 days with our assessment
- **Resolution**: Depending on complexity, typically within 30 days

### What to Expect

- We will acknowledge receipt of your vulnerability report
- We will provide an estimated timeline for addressing the vulnerability
- We will notify you when the vulnerability is fixed
- We will publicly acknowledge your responsible disclosure (unless you prefer to remain anonymous)

## Security Best Practices

When using Meridian in your application:

1. **Keep MaxMind credentials secure**: Never commit your `MAXMIND_LICENSE_KEY` or `MAXMIND_ACCOUNT_ID` to version control
2. **Update regularly**: Keep Meridian updated to receive the latest security patches
3. **Validate input**: Always validate user input before passing it to Meridian methods
4. **Use HTTPS**: Ensure all API communications use HTTPS in production

## Disclosure Policy

Please do not report security vulnerabilities through public GitHub issues. Use the email address provided above for responsible disclosure.

Thank you for helping keep Meridian and its users safe!
