# 🧪 Postman Runner MCP Tool Usage Guide

## Quick Start

The `mcp__postman-runner__run-collection` tool enables automated API testing using Newman (Postman's CLI runner).

### Basic Usage

```javascript
// Test a collection from URL
mcp__postman-runner__run-collection({
  "collection": "https://raw.githubusercontent.com/postmanlabs/newman/develop/examples/sample-collection.json"
})

// Test local collection with environment
mcp__postman-runner__run-collection({
  "collection": "./tests/api-collection.json",
  "environment": "./tests/local-env.json"
})
```

### Article System API Testing Examples

```javascript
// Test article issuance endpoints
mcp__postman-runner__run-collection({
  "collection": "./tests/article-api-tests.json",
  "environment": "./tests/article-env.json",
  "iterationCount": 3
})

// Test regional business rules (Prague/Brno/Ostrava)
mcp__postman-runner__run-collection({
  "collection": "./tests/regional-rules-tests.json",
  "environment": "./tests/czech-regions-env.json"
})
```

### Parameters

- **collection** (required): Path or URL to Postman collection
- **environment** (optional): Environment file for variables
- **globals** (optional): Global variables file
- **iterationCount** (optional): Number of test iterations

### Integration with Development Workflow

1. **API Development**: Create collections from OpenAPI docs at `http://localhost/api/doc`
2. **Automated Testing**: Run collections in CI/CD pipeline
3. **Regional Testing**: Validate Prague/Brno/Ostrava business rules
4. **Performance Testing**: Use iterationCount for load testing

### Expected Output

```
✓ Client Creation Tests (3 tests, 0 failures)
✓ Article Issuance Tests (5 tests, 0 failures)
✓ Regional Rules Tests (6 tests, 0 failures)
Duration: 1.2s
```

### Common Use Cases

- **Pre-deployment validation**: Test all API endpoints before release
- **Regional compliance**: Verify Czech market business rules
- **Integration testing**: Test CQRS command/query handlers
- **Performance monitoring**: Regular API health checks
