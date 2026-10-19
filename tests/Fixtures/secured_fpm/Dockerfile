FROM debian:trixie

# EN: This disposable fixture runs real systemd, Apache and the distribution's PHP-FPM.
# It is not the production application image and must never receive real configuration/data.
#
# HR: Ovaj privremeni test koristi stvarni systemd, Apache i distribucijski PHP-FPM.
# Nije produkcijski aplikacijski image i ne smije dobiti stvarne postavke/podatke.
ENV container=docker
RUN apt-get update && DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends \
    systemd systemd-sysv dbus sudo procps git rsync composer acl apache2 curl ca-certificates \
    php-cli php-curl php-mbstring php-xml php-zip php-intl php-sqlite3 php-mysql php-pgsql php-fpm \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* \
    && useradd --create-home --shell /bin/bash fixture-admin \
    && printf 'fixture-admin ALL=(ALL) NOPASSWD: ALL\n' > /etc/sudoers.d/fixture-admin \
    && chmod 0440 /etc/sudoers.d/fixture-admin
STOPSIGNAL SIGRTMIN+3
CMD ["/sbin/init"]
