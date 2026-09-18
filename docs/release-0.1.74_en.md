# Simbioza 0.1.74

This corrective release fixes an existing FPM installation update when the
updater needs to extend administrator-managed menu or theme settings.

The updater now rewrites such an existing file under an exclusive lock without
replacing its inode. Its FPM owner, runtime group, ACL, and mode therefore stay
unchanged, and the unprivileged `simbioza-deploy` process no longer needs to
restore them through `chown`.

Regression tests separately verify inode, owner, group, and mode preservation
for menu settings and stored themes.
