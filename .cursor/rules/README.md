# Cursor Rules for TEE Variation Swatches

This directory contains Cursor AI rules specific to this plugin.

## Rules Overview

| Rule | Applies To | Purpose |
|------|------------|---------|
| `00-start-here.mdc` | All files | Critical overview and essential patterns |
| `plugin-architecture.mdc` | All files | Core principles, file structure |
| `code-standards.mdc` | `*.php` | PHP security, WooCommerce patterns |
| `performance.mdc` | All files | Caching, batch queries, optimization |
| `common-tasks.mdc` | Manual | How-to guides for common tasks |

## Usage

Rules with `alwaysApply: true` automatically apply to all AI requests in this directory.

Rules with `globs` apply when editing matching files.

Rules with `description` can be manually referenced by the AI.

## Documentation

**Critical Reading**:
- **improvements.md** - Known performance issues and recommendations
- **variation-sw.plan.md** - Original implementation plan

## Quality Standards

- ✅ **Security**: Sanitize input, escape output, verify nonces
- ✅ **Performance**: Object caching, batch queries, HPOS support
- ✅ **Scalability**: Avoid N+1 queries, cache invalidation scoped to products
- ✅ **WooCommerce**: Use Data Stores, test with HPOS enabled
- ✅ **Clean Code**: Type hints, PHPDoc, no debug logs in production

## Contact

**Email**: phpdevsec@proton.me

