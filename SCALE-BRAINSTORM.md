# Brainstorm Notes — Commands Roadmap

> **Status:** Pivoted. `/optimize` comes first — maximize single-node performance before horizontal scaling. `/scale` is parked until the optimized baseline exists.

---

# /scale — Parked

(Revisit after /optimize is built)


## Goal

A `/scale` command that takes an existing app built by `/build` and upgrades it to production-grade infrastructure on AWS + Kubernetes. Not a toy — generates real deployable systems.

---

## MCP Tool Layer

| MCP Server | Role |
|------------|------|
| **AWS MCP (awslabs)** | Provisions ECR, EKS cluster, ElastiCache Redis, RDS, VPC, IAM roles |
| **Kubernetes MCP** | Applies manifests, checks pod health, manages rollouts |
| **Cloudflare MCP** | Already in stack — rewires tunnel target from Docker Compose to K8s ingress |

---

## Human-in-the-Loop (Minimized)

**One-time gate:** AWS credentials configured (`aws configure` or vars in `.env`).

After that, `/scale` drives everything end-to-end:
- EKS cluster creation
- ECR repo + Docker image build + push
- Manifest generation and apply
- ElastiCache Redis provisioning
- Cloudflare tunnel rewired to K8s ingress

---

## Timing

- **First run:** ~20 min (EKS cluster creation is slow AWS-side)
- **Subsequent deploys:** fast — new image push + `kubectl rollout restart`

---

## Scale Targets

- **Traffic / request throughput** — multiple PHP/Apache replicas behind K8s ingress + HPA
- **Background job processing** — worker pods consuming Redis queues

---

## Open Questions

- [ ] Redis integration model: simple queue vs pub/sub vs both
- [ ] Worker dispatch pattern: Model method async dispatch vs event-driven
- [ ] MySQL: keep in-cluster or migrate to RDS on first `/scale`
- [ ] Cloudflare tunnel: rewire to K8s ingress or use Cloudflare's K8s operator
