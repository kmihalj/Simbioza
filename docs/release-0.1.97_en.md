# Simbioza 0.1.97

Concurrent editing of the same shared draft no longer silently replaces another
editor's changes. A stale save is rejected, the current server draft is shown,
and the rejected editor's changes remain available in a per-user local recovery
copy. The editor offers a side-by-side source comparison before that person
chooses whether to restore or discard their own edits. Restoring is deliberate;
it does not automatically merge conflicting versions. Local autosave is cleared
only after the server confirms a successful save.

The first visit now uses an enabled language preferred by the browser, including
regional preferences such as `fr-CH` when the `fr` language pack is installed.
If no enabled language matches, the application's primary language is used.
An explicit choice in the language menu takes precedence and is remembered for
one year in a cookie scoped to this installation's path. Separate installations
on the same host therefore keep separate language choices. An installation can
set `detect_browser_locale` to `false` to opt out of automatic detection.

This release requires Editor 0.1.36 and Menu 0.1.25. It has no database
migrations. Existing drafts and published pages remain intact; normal GUI or
CLI updating is sufficient. GitHub Sponsors funding support was already
included in the earlier 0.1.92–0.1.96 release series.
