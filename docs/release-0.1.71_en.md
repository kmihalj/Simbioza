# Simbioza 0.1.71

Includes all fixes, the theme, and bilingual guides from 0.1.70, plus Calendar 0.1.17.

## Separate My meetings calendar

Clicking **My meetings** in the calendar list opens its own calendar view,
without events from other calendars. It shows the signed-in user's meetings
even without a subscription to their source calendar. Clicking an event,
including its icon, opens the meeting details and permitted actions.

The virtual calendar remains a projection, not a new write destination or a
copy of each event. Editing still follows the source meeting's permissions.
The CalDAV `my-meetings` collection remains read-only.

## Clear meeting time and status

Meeting details now highlight the date, time, and status in a prominent card.
Dates and times follow the interface language: for example,
**Tuesday, September 15, 2026** and **10:00 AM – 11:00 AM** in English,
or their Croatian equivalents. The meeting list is localized too. Multi-day
meetings show both dates, and all-day events show **All day**.

The card adapts to mobile screens, follows theme colors, and retains readable
contrast in light and dark palettes. It also works without the Theme module.
Stored meeting times and time zones are unchanged.

## Quieter updater

Fetching a release tag no longer prints Git's `detached HEAD` advice.
The setting applies only to that operation: errors remain visible and global
Git settings are unchanged.

Bundled theme/guide delivery and the guide-image ownership repair remain
included. The guides' attachment list remains intentionally hidden.

Apps-test is not updated automatically. Its administrator runs:

```bash
cd /data/www/simbioza && sudo php update.php
```
