# Documentation Tooling Guide

## Code Documentation (phpDocumentor)

Generate documentation:

```
vendor/bin/phpdoc
```

Output:

```
build/docs/index.html
```

---

## Local Development

Run Symfony server:

```
symfony server:start
```

or:

```
php -S localhost:8000 -t public
```

---

## Symfony Profiler

Ensure enabled in dev:

```
web_profiler:
    toolbar: true
```

---

## API Documentation (planned)

Next step:
- NelmioApiDocBundle
- Swagger UI at /api/doc

---

## Notes

- All services, commands, and entities are now fully DocBlock-covered
- phpDocumentor provides full static documentation
- Next milestone: OpenAPI + E2E observability
