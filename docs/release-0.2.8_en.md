# Simbioza 0.2.8

The optional Accessibility module is upgraded to 0.1.5. Lower-right notifications now reserve space above its visible launcher instead of appearing behind it. The rule covers shared application messages, module notifications, and dynamically created Comment and Task messages.

The offset follows the launcher's size, screen safe area, and text scaling. Long Comment and Task messages wrap within narrow screens, including at 200% text size. When the widget is absent or hidden, notifications retain their original position. Upper and left-side notifications are unchanged. The fix works with both light and dark themes.

Existing widget controls and translations in Croatian, English, German, French, Spanish, and Italian remain unchanged. No new text or database migrations are introduced.

Make a restorable backup before updating. Existing installations without the Accessibility module do not have it installed automatically; installed copies are updated with the normal application/module update.
