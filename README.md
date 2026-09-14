# Analysing

Analysing is a Symfony bundle that handles analytics query processing, metric collection, tracking, and reports. It provides a structured analytics layer, supporting both legacy and modern tracking boundaries.

This package is **not** a real-time data streaming platform (like Kafka/RabbitMQ) or a database storage engine. It provides the application-level logic for calculating and structuring analytical metrics.

## Current Posture

### What the component already does
- Defines metric trees, value objects, and analytical data transfer objects (DTOs).
- Structures metric collection across controllers and services.
- Detects PHP symbol layer placement using custom layer map scanners.
- Uses the canonical `App\Analysing\` namespace with technical-role-first Symfony roots and `Analytics*` subject-prefixed PHP declarations.

### What this repository does not claim yet
- Real-time stream processing or event streaming infrastructure.
- High-volume raw log ingestion.

## Runtime Surface & Entrypoints

The module integrates via standard Symfony controllers, CLI commands, and DI bundles:
- `src/Controller/` - Contains endpoints for accessing metric and analytical reports.
- `src/Command/` - Console commands for executing periodic metric aggregations.
- `src/AnalysingBundle.php` - Bootstraps DI configuration into host applications.

## Local Setup

Install dependencies:
```bash
composer install
```

Run the RC verification surface:
```bash
composer analyse
composer test
composer gate
```

Scan the local layer map when architecture evidence is needed:
```bash
composer lint:canon
```

## Local Composer Path Installation

To include Analysing in your host Symfony project:

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "../Analysing",
      "options": {
        "symlink": true
      }
    }
  ],
  "require": {
    "analysing/analytics": "*@dev"
  }
}
```

## Documentation Map

- [Analytics Layer Map Scanner Guide](docs/analytics-core-doc-layer-map.md)
- [Namespace Migration and Targets Sketch](docs/analytics-core-doc-namespace-target.md)
- [QA Environment Setup](docs/qa-environment.md)
- [Analytics RWE Sketch 9](docs/analytics-core-doc-rwe-sketch9.md)
