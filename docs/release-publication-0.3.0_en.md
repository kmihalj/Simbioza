# Publication of Simbioza 0.3.0

[Croatian version](release-publication-0.3.0_hr.md)

Scope: all 18 public modules, the private demo module and Simbioza at tag 0.3.0; PostgreSQL Docker deployment with a configuration/data volume and a separate database volume. Host TCP port defaults to 1511 and remains configurable. Framework stays unchanged at its published ^0.0.25 dependency.

## Checklist

- [x] Inspect repository changes; preserve private settings and Framework edits.
- [ ] Validate module quality checks and isolated integrated E2E.
- [ ] Publish module main branches and verify branch CI before immutable tags.
- [ ] Publish coordinated module 0.3.0 tags and verify stable dependency CI.
- [ ] Verify fresh installation from published packages, without local aliases.
- [ ] Publish application main/tag and verify the complete database/browser/API matrix.
- [ ] Build and scan Linux amd64/arm64 images; publish GHCR image and Compose bundle.
- [ ] Verify registry access and release assets; record commits, CI and image digests.

Module branch CI uses a disposable composer.ci.json with development aliases for the runtime dependency closure of declared dependencies. Production composer.json remains unchanged. Tag CI retains stable ^0.3.0 requirements. Framework is never given a development alias. Quality checks are not skipped.

Logs and evidence belong in ignored build/release-0.3.0/. Never move published tags. Demo and apps-test reinstallation is a separate user-operated step; do not remove or reset either running site.
