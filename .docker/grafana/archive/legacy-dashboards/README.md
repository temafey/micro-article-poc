# Legacy Grafana Dashboards (Archived)

These dashboards were archived as part of TASK-038 (Legacy Stack Cleanup).

## Why Archived

These dashboards require metrics from containers that have been removed:
- **Node Exporter** (`node_*` metrics) - Host system metrics
- **cAdvisor** (`container_*` metrics) - Container metrics
- **Prometheus** (direct scrape targets)

The new observability stack (TASK-034, TASK-037) uses:
- **grafana/otel-lgtm** - Unified OpenTelemetry stack
- **Push-based telemetry** via OTLP protocol
- **Mimir** for metrics (instead of Prometheus)
- **Application-level metrics** (not host-level)

## Archived Dashboards

| Dashboard | Purpose | Required Exporter |
|-----------|---------|-------------------|
| docker_host.json | Host system metrics | Node Exporter |
| docker_containers.json | Container resource usage | cAdvisor |
| nginx_container.json | Nginx container metrics | cAdvisor + nginx-exporter |
| traefik_rev4.json | Traefik proxy metrics | Traefik metrics endpoint |
| monitor_services.json | Monitoring stack health | All exporters |

## Restoring (If Needed)

If you need to restore these dashboards:
1. Copy files to `.docker/grafana/provisioning/dashboards/`
2. Deploy Node Exporter and cAdvisor containers
3. Configure Prometheus scrape targets
4. Restart Grafana

## New Dashboards

Active dashboards for the new stack:
- `profiling.json` - Pyroscope continuous profiling
- `slo-overview.json` - SLO/SLI monitoring

## Related

- ADR-014: Observability Stack Modernization
- TASK-034: Observability Foundation
- TASK-037: Alerting & Profiling
- TASK-038: Legacy Stack Cleanup
