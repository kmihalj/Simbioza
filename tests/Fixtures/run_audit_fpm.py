import json
import sys
import os
import pathlib
import shutil
import socket
import struct
import subprocess
import time

# EN: Creates a disposable copy and a private FPM process, not a registered service.
#
# HR: Stvara privremenu kopiju i privatni FPM proces, ne registrirani servis.
root = pathlib.Path('/tmp/simbioza-r030-fpm-fixture')
source = pathlib.Path(sys.argv[2] if len(sys.argv) > 2 else '/Volumes/Ext/Development/CR/HFClean/build/audit-0.3.0/fpm-input')
assert not root.exists(), 'The isolated fixture already exists.'
shutil.copytree(source, root, symlinks=True)
db = root / 'config/database.php'
db.write_text(db.read_text().replace(sys.argv[1], str(root)))
probe_source = pathlib.Path('/Volumes/Ext/Development/CR/HFClean/tests/Fixtures/audit_fpm_probe.php')
shutil.copy(probe_source, root / 'public/r030-probe.php')
shutil.copy(probe_source.with_name('audit_fpm_loader.php'), root / 'public/audit_fpm_loader.php')

def perms(directory, writable):
    for base, dirs, files in os.walk(directory, followlinks=False):
        for item in [pathlib.Path(base), *[pathlib.Path(base, f) for f in files]]:
            if item.is_symlink():
                continue
            if writable:
                shutil.chown(item, user='www-data', group='www-data')
            os.chmod(item, (0o770 if writable else 0o755) if item.is_dir() else (0o660 if writable else 0o644))

def rec(typ, data=b''):
    return struct.pack('!BBHHBB', 1, typ, 1, len(data), 0, 0) + data

def fcgi(script, uri):
    params = {'SCRIPT_FILENAME': str(root / 'public' / script), 'DOCUMENT_ROOT': str(root / 'public'),
              'SCRIPT_NAME': '/index.php', 'REQUEST_METHOD': 'GET', 'REQUEST_URI': uri,
              'QUERY_STRING': '', 'SERVER_PROTOCOL': 'HTTP/1.1', 'GATEWAY_INTERFACE': 'CGI/1.1',
              'HTTP_HOST': 'r030.invalid', 'SERVER_NAME': 'r030.invalid', 'SERVER_PORT': '80',
              'REMOTE_ADDR': '127.0.0.1', 'REDIRECT_STATUS': '200'}
    payload = b''
    for k, v in params.items():
        k, v = k.encode(), v.encode()
        payload += bytes([len(k), len(v)]) + k + v
    client = socket.socket(socket.AF_UNIX)
    client.settimeout(30)
    client.connect(str(root / 'fpm.sock'))
    client.sendall(rec(1, struct.pack('!HB5x', 1, 0)) + rec(4, payload) + rec(4) + rec(5))
    out, err = b'', b''
    def read(size):
        result = b''
        while len(result) < size:
            chunk = client.recv(size - len(result))
            if not chunk:
                raise RuntimeError('Truncated FastCGI response')
            result += chunk
        return result
    while True:
        ver, typ, request, length, padding, reserved = struct.unpack('!BBHHBB', read(8))
        data = read(length) if length else b''
        if padding:
            read(padding)
        if typ == 6:
            out += data
        elif typ == 7:
            err += data
        elif typ == 3:
            break
    client.close()
    if err:
        raise RuntimeError(err.decode())
    header, body = out.split(b'\r\n\r\n', 1)
    assert b'Status: 500' not in header, body[:2000]
    return header, body

try:
    perms(root, False)
    perms(root / 'data', True)
    for secure in (False, True):
        if secure:
            perms(root / 'config', False)
            for base, dirs, files in os.walk(root / 'config', followlinks=False):
                for item in [pathlib.Path(base), *[pathlib.Path(base, f) for f in files]]:
                    shutil.chown(item, user='root', group='root')
                    os.chmod(item, 0o755 if item.is_dir() else 0o644)
            (root / 'private').mkdir()
            # EN: Auth layers are complete files, not partial merged documents.
            #
            # HR: Auth slojevi su potpune datoteke, ne djelomični spojeni dokumenti.
            shutil.copytree(root / 'config/auth', root / 'private/auth')
            perms(root / 'private', True)
        else:
            perms(root / 'config', True)
        config = f'''[global]
daemonize = no
error_log = {root}/fpm.log
pid = {root}/fpm.pid
[r030-fixture]
user = www-data
group = www-data
listen = {root}/fpm.sock
pm = static
pm.max_children = 1
catch_workers_output = yes
clear_env = yes
env[HPH_APP_PATH] = {root}
env[HPH_CONFIG_PATH] = {root}/config
env[HPH_CONFIG_OVERRIDE_PATHS] = {root}/private
''' if secure else f'''[global]
daemonize = no
error_log = {root}/fpm.log
pid = {root}/fpm.pid
[r030-fixture]
user = www-data
group = www-data
listen = {root}/fpm.sock
pm = static
pm.max_children = 1
catch_workers_output = yes
clear_env = yes
env[HPH_APP_PATH] = {root}
env[HPH_CONFIG_PATH] = {root}/config
'''
        (root / 'fpm.conf').write_text(config)
        proc = subprocess.Popen(['/usr/sbin/php-fpm8.3', '-F', '-y', str(root / 'fpm.conf')], stdout=subprocess.DEVNULL, stderr=subprocess.PIPE)
        try:
            for _ in range(100):
                if (root / 'fpm.sock').exists():
                    break
                assert proc.poll() is None, proc.stderr.read().decode()
                time.sleep(.05)
            h, body = fcgi('index.php', '/')
            assert b'<html' in body.lower(), body[:1000]
            assert b'X-Content-Type-Options: nosniff' in h, h
            assert b'Content-Security-Policy:' in h, h
            h, body = fcgi('index.php', '/auth/login')
            assert b'<form' in body.lower(), body[:1000]
            h, body = fcgi('r030-probe.php', '/r030-probe.php')
            checks = json.loads(body)
            assert all(checks.values()), checks
            print(json.dumps({'mode': 'FPM_secure' if secure else 'FPM', 'http_home': True, 'http_login': True, 'checks': checks}))
        finally:
            proc.terminate()
            proc.wait(timeout=10)
finally:
    assert root.parent == pathlib.Path('/tmp') and root.name == 'simbioza-r030-fpm-fixture'
    shutil.rmtree(root)
