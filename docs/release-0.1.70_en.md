# Simbioza 0.1.70

Includes the fixes, bundled guides and theme from 0.1.69, plus Calendar 0.1.16.

## Multiple attendee selection

Meeting planning and calendar default attendees now support selecting multiple
people in the searchable dropdown and adding them with one **Add selected**
action. Selection is retained across searches and additional result pages.
People can be deselected using a checkbox or their removable name tag;
existing attendees display **added** and cannot be added twice. User lookup
remains server-paged, with at most 25 results per request.

The picker supports keyboard and touch input, narrow and landscape mobile
layouts, theme styling and standalone Bootstrap styling. Opening the picker
does not automatically open the mobile keyboard, and search Enter does not
submit the meeting form. Modern browsers use a top-layer popover to prevent
the sticky header from covering the picker. Owner and ACL selection remain
single-user pickers.

## Bundled guide images

The updater now repairs ownership after `sudo` imports: attachment files and
directories inherit their runtime storage owner and group. Permission modes
are not widened and private backup artifacts remain unchanged.
The repair also applies to already-imported meeting guides when the guide
package is unchanged, without reimporting content or adding history.
The guide's attachment list intentionally remains hidden.

Apps-test is not upgraded automatically. An administrator runs:

```bash
cd /data/www/simbioza && sudo php update.php
```
