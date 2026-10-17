# Deferred Confluence site migration snapshot

On 17 October 2026 the full-site importer was deferred. `confluence-site` preserves
the application integration, migration wrappers, deployment/preflight fixes,
installation guide packages and browser tests prepared during that work. This is
not a release and must not be merged merely to publish the existing space importer.

Matching branches exist in `simbioza-module-confluence-import`,
`heartphrame-module-calendar` and `simbioza-module-user`. Framework and Auth remain
outside this snapshot. Private HFC configuration, import drafts, real archives and
read-only mapping evidence are not committed to Git.

The application baseline is `main` at `a41dc81` (Simbioza 0.3.2). The importer,
Calendar and User baselines are their published 0.3.0 `main` revisions. Candidate
version/dependency changes in this branch are unpublished and must be reconsidered
before development resumes. Independently useful deployment fixes can be selected
and verified separately instead of bringing the full-site feature into a release.

For the remaining acceptance work see the importer's
`docs/confluence-site-branch_en.md` and `docs/site-import-plan_en.md`. Preserving
this snapshot does not approve unresolved user/calendar mappings or start an import.
