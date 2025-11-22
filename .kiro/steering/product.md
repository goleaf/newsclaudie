# Product Overview

## Laravel Blog News - Enhanced Blog Starter Kit

A modern, accessible, and feature-rich Laravel blog application with a powerful admin interface.

### Core Purpose

Provides a complete blogging solution with:
- Public-facing blog with posts, categories, tags, and comments
- Comprehensive admin panel for content management
- Multi-language support (English, Spanish)
- User authentication and role-based access control

### Key Features

**Public Site:**
- Markdown-based blog posts with live preview editor
- Category and tag organization
- User comments with email verification
- Responsive design with dark mode support
- SEO-friendly with OG tags and schema.org metadata

**Admin Interface:**
- Real-time search and filtering across all entities
- Bulk actions with optimistic UI updates
- Inline editing for quick updates
- Sortable columns with URL persistence
- Comprehensive CRUD for posts, categories, comments, and users
- Accessibility-first design (WCAG 2.1 compliant)

**Developer Experience:**
- Property-based testing for critical features
- Comprehensive documentation in `docs/` directory
- Type-safe TypeScript frontend
- Code quality tools (Pint, PHPStan, Rector)

### Target Users

- **Content Creators:** Authors and editors managing blog content
- **Administrators:** Managing users, comments, and site configuration
- **Developers:** Extending and customizing the platform

### Demo Mode

The application includes a demo mode for testing (NOT for production):
- Set `demoMode` to `true` in `config/blog.php`
- Allows anyone to log in as admin
- Includes seed data for demonstration
