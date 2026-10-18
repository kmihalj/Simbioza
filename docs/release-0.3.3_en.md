# Simbioza 0.3.3 — Docker security updates

[Croatian version](https://github.com/kmihalj/Simbioza/blob/0.3.3/docs/release-0.3.3_hr.md)

> Historical release note — current installation instructions are in [release 0.3.4](release-0.3.4_en.md). The original 0.3.3 tag and image remain unchanged.

This patch publishes newly built Linux **AMD64 and ARM64** images containing
updated Debian packages. Framework remains **v0.0.25**. API **0.3.1** includes
the corrected quick-start documentation; the other public modules remain
**0.3.0**. The optional private DEMO companion is **0.3.4**. Existing tags and
previous release images are not replaced.

## Changes

- The common PHP runtime stage now upgrades inherited Debian packages before
  compiling PHP extensions. Installing build dependencies alone left older
  `curl`/`libcurl` packages in the pinned base image.
- Both image architectures retain non-root execution, immutable application
  code, separate persistent runtime and PostgreSQL volumes, and the existing
  vulnerability gate. Fixable HIGH/CRITICAL findings still block publication.
- Installation commands, Docker examples and bundled EN/HR installation
  archives use **0.3.3**. A regression test rejects version drift in current
  Docker examples and README links.
- Configuration instructions link to the published HTML Editor script-registry
  documentation; API 0.3.1 ships the corrected Auth CLI-example links.
- Documentation checks distinguish independently versioned modules from the
  application, while continuing to reject invalid PHP/JSON examples.
- There are no new application database migrations or authentication changes.
  The archived Confluence site-import work is not included; single-space
  import remains unchanged.

## Installation and updates

New installations should follow the
[complete installation index](https://github.com/kmihalj/Simbioza/blob/0.3.3/docs/installation_en.md)
or the [Docker guide](https://github.com/kmihalj/Simbioza/blob/0.3.3/docs/docker_en.md).
The existing 0.3.x installation can use its normal update procedure; do not
rerun initial installation or delete a database to apply this patch.

For Docker, select `ghcr.io/kmihalj/simbioza:0.3.3`, preserve both named volumes,
take a consistent backup and follow the explicit image migration/restart steps
in the Docker guide. Existing containers do not change until the operator
pulls and deploys the new image. Do not use `docker compose down -v` for updates.

The [private DEMO guide](https://github.com/kmihalj/simbioza-module-demo/blob/0.3.4/docs/demo_installation_en.md)
uses application 0.3.3 and demo module 0.3.4; repository access is required.
No running demo or apps-test instance is automatically updated by this release.

## Verification and downloads

The release CI checks PHP 8.2–8.5, clean module/database installations, browser,
API and performance scenarios, and native Docker installation/persistence and
security checks on both architectures. Release assets include the Compose
bundle, checksums, resolved dependency locks, SBOMs and vulnerability reports.

[Release and downloads](https://github.com/kmihalj/Simbioza/releases/tag/0.3.3)
