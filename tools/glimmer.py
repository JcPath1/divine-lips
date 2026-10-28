"""Glimmer Bliss shop tools.

    python tools/glimmer.py check     - which secret settings are filled in (shows yes/no, never the values)
    python tools/glimmer.py ftp-test  - log in to the hosting and list the top folders (no secrets shown)
    python tools/glimmer.py local     - build a test copy of the shop on this computer (fake money, fake email)
    python tools/glimmer.py deploy    - upload the shop to glimmerbliss.com

Secrets come from private/glimmerbliss-secrets.env, which git never uploads.
Nothing in this script prints a password, key or username.
"""

import ftplib
import hashlib
import json
import os
import shutil
import ssl
import subprocess
import sys

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
SECRETS = os.path.join(ROOT, 'private', 'glimmerbliss-secrets.env')
MANIFEST = os.path.join(ROOT, 'private', '.deploy-manifest.json')
SITE_URL = 'https://glimmerbliss.com'

# Never upload these
SKIP_NAMES = {'.DS_Store', 'Thumbs.db', 'config.local.php', '.schema-v1', '.schema-v2', '.schema-v3'}
SKIP_SUFFIXES = ('_old.jpg',)


def load_secrets():
    if not os.path.isfile(SECRETS):
        sys.exit('Missing private/glimmerbliss-secrets.env')
    env = {}
    with open(SECRETS, encoding='utf-8-sig') as f:
        for line in f:
            line = line.strip()
            if not line or line.startswith('#') or '=' not in line:
                continue
            key, value = line.split('=', 1)
            env[key.strip()] = value.strip()
    return env


REQUIRED = {
    'deploy': ['FTP_HOST', 'FTP_USER', 'FTP_PASSWORD', 'FTP_WEB_ROOT', 'DB_NAME', 'DB_USER', 'DB_PASSWORD',
               'SHOP_EMAIL', 'SMTP_HOST', 'SMTP_PASSWORD', 'OWNER_ALERT_EMAIL', 'ADMIN_PASSWORD'],
    'payments (need at least one)': ['VENMO_USERNAME', 'ZELLE_EMAIL_OR_PHONE', 'PAYPAL_CLIENT_ID'],
}


def cmd_check():
    env = load_secrets()
    for group, keys in REQUIRED.items():
        print(f'\n{group}:')
        for k in keys:
            print(f"  {'OK     ' if env.get(k) else 'MISSING'}  {k}")
    if env.get('PAYPAL_CLIENT_ID') and not env.get('PAYPAL_SECRET'):
        print('\n  MISSING  PAYPAL_SECRET (needed with PAYPAL_CLIENT_ID)')
    print(f"\n  PAYPAL_MODE = {env.get('PAYPAL_MODE') or 'sandbox'}")


def php_exe():
    found = shutil.which('php')
    if found:
        return found
    base = os.path.expandvars(r'%LOCALAPPDATA%\Microsoft\WinGet\Packages')
    if os.path.isdir(base):
        for name in os.listdir(base):
            exe = os.path.join(base, name, 'php.exe')
            if name.startswith('PHP.PHP') and os.path.isfile(exe):
                return exe
    sys.exit('PHP is not installed on this computer (needed to scramble the admin password).')


def admin_hash(password):
    # Password goes in through stdin, so it never shows in the process list.
    out = subprocess.run([php_exe(), '-r', 'echo password_hash(trim(stream_get_contents(STDIN)), PASSWORD_DEFAULT);'],
                         input=password, capture_output=True, text=True, check=True)
    return out.stdout.strip()


def php_str(value):
    return "'" + str(value).replace('\\', '\\\\').replace("'", "\\'") + "'"


def php_config(c):
    lines = ['<?php', '// Made by tools/glimmer.py from private/glimmerbliss-secrets.env. Do not edit on the server.', 'return [']

    def emit(d, indent):
        for k, v in d.items():
            pad = '    ' * indent
            if isinstance(v, dict):
                lines.append(f'{pad}{php_str(k)} => [')
                emit(v, indent + 1)
                lines.append(f'{pad}],')
            elif isinstance(v, int):
                lines.append(f'{pad}{php_str(k)} => {v},')
            else:
                lines.append(f'{pad}{php_str(k)} => {php_str(v)},')
    emit(c, 1)
    lines.append('];')
    return '\n'.join(lines) + '\n'


def tax_rate_bp(env):
    """Texas sales tax for the shop's own location, e.g. TAX_RATE_PERCENT=8.25 -> 825."""
    raw = (env.get('TAX_RATE_PERCENT') or '8.25').strip().rstrip('%')
    rate = round(float(raw) * 100)
    if not 0 <= rate <= 2000:
        sys.exit(f'TAX_RATE_PERCENT looks wrong: {raw}')
    return rate


def server_config(env):
    return {
        'site_url': SITE_URL,
        'shipping_cents': 700,
        'tax_rate_bp': tax_rate_bp(env),
        'db': {'dsn': f"mysql:host={env.get('DB_HOST') or 'localhost'};dbname={env['DB_NAME']};charset=utf8mb4",
               'user': env['DB_USER'], 'pass': env['DB_PASSWORD']},
        'mail': {'mode': 'smtp', 'from': env['SHOP_EMAIL'], 'smtp_host': env['SMTP_HOST'],
                 'smtp_port': int(env.get('SMTP_PORT') or 465), 'smtp_pass': env['SMTP_PASSWORD'],
                 'owner_alert': env['OWNER_ALERT_EMAIL']},
        'admin_password_hash': admin_hash(env['ADMIN_PASSWORD']),
        'pay': {'venmo_username': env.get('VENMO_USERNAME', '').lstrip('@'),
                'zelle_contact': env.get('ZELLE_EMAIL_OR_PHONE', ''), 'zelle_name': env.get('ZELLE_NAME', '')},
        'paypal': {'mode': 'live' if env.get('PAYPAL_MODE') == 'live' else 'sandbox',
                   'client_id': env.get('PAYPAL_CLIENT_ID', ''), 'secret': env.get('PAYPAL_SECRET', '')},
    }


HTACCESS = """# Glimmer Bliss - made by tools/glimmer.py
Options -Indexes
RewriteEngine On

# Let cPanel's free SSL (AutoSSL) check the domain over plain http
RewriteRule ^\\.well-known/ - [L]

# Always use the padlock (https) and the plain domain (no www)
RewriteCond %{HTTPS} off [OR]
RewriteCond %{HTTP_HOST} ^www\\. [NC]
RewriteRule ^ https://glimmerbliss.com%{REQUEST_URI} [L,R=301]

# Hide files starting with a dot or underscore (like api/_boot.php)
RewriteRule (^|/)[._] - [F,L]

<IfModule mod_headers.c>
  Header always set X-Content-Type-Options "nosniff"
  Header always set Referrer-Policy "strict-origin-when-cross-origin"
  Header always set Strict-Transport-Security "max-age=31536000"
</IfModule>
"""


def build_stage(stage, config_php, config_name):
    """Lay the shop out exactly like the server: public_html/ + glimmerbliss_app/ side by side."""
    if os.path.isdir(stage):
        shutil.rmtree(stage)
    web = os.path.join(stage, 'public_html')
    app = os.path.join(stage, 'glimmerbliss_app')
    ignore = shutil.ignore_patterns(*SKIP_NAMES, '*_old.jpg')
    shutil.copytree(os.path.join(ROOT, 'docs'), web, ignore=ignore)
    shutil.copytree(os.path.join(ROOT, 'server', 'public'), web, dirs_exist_ok=True, ignore=ignore)
    shutil.copytree(os.path.join(ROOT, 'server', 'app'), app, ignore=ignore)
    with open(os.path.join(web, '.htaccess'), 'w', encoding='utf-8', newline='\n') as f:
        f.write(HTACCESS)
    with open(os.path.join(app, '.htaccess'), 'w', encoding='utf-8', newline='\n') as f:
        f.write('Require all denied\n')
    name = 'config.php' if config_php.startswith('<?php\n// Made') and 'mysql:' in config_php else 'config.local.php'
    with open(os.path.join(app, name), 'w', encoding='utf-8', newline='\n') as f:
        f.write(config_php)
    return web, app


def cmd_local():
    stage = os.path.join(ROOT, 'private', 'local-test')
    data = os.path.join(stage, 'data')
    config = {
        'site_url': 'http://localhost:8080',
        'shipping_cents': 700,
        'db': {'dsn': 'sqlite:' + os.path.join(data, 'shop.sqlite').replace('\\', '/'), 'user': '', 'pass': ''},
        'mail': {'mode': 'log', 'log_file': os.path.join(data, 'emails.txt').replace('\\', '/'), 'owner_alert': 'owner@example.test',
                 'from': 'hello@example.test'},
        'admin_password_hash': admin_hash('test-admin'),
        'pay': {'venmo_username': 'GlimmerBliss-Test', 'zelle_contact': 'pay@example.test', 'zelle_name': 'Glimmer Bliss'},
        'paypal': {'mode': 'sandbox', 'client_id': '', 'secret': ''},
    }
    keep = None
    if os.path.isdir(data):
        keep = os.path.join(ROOT, 'private', '.local-data-keep')
        shutil.rmtree(keep, ignore_errors=True)
        shutil.copytree(data, keep)
    build_stage(stage, php_config(config), 'config.local.php')
    if keep:
        shutil.copytree(keep, data)
        shutil.rmtree(keep, ignore_errors=True)
    os.makedirs(data, exist_ok=True)
    web = os.path.join(stage, 'public_html')
    print('Test shop built. Start it with:')
    print(f'  "{php_exe()}" -S localhost:8080 -t "{web}"')
    print('Then open http://localhost:8080   (admin password: test-admin, emails go to private/local-test/data/emails.txt)')


# ---------- upload ----------

def ftp_connect(env):
    ctx = ssl.create_default_context()
    ftp = ftplib.FTP_TLS(context=ctx, timeout=60)
    ftp.connect(env['FTP_HOST'], int(env.get('FTP_PORT') or 21))
    ftp.login(env['FTP_USER'], env['FTP_PASSWORD'])
    ftp.prot_p()
    return ftp


def cmd_ftp_test():
    env = load_secrets()
    try:
        ftp = ftp_connect(env)
    except ftplib.error_perm as e:
        sys.exit(f'Login refused by the server: {str(e)[:3]} (check FTP_USER / FTP_PASSWORD)')
    except ssl.SSLError as e:
        sys.exit(f'Secure connection failed ({type(e).__name__}). Tell Claude - FTP_HOST may need to be the server name cPanel shows.')
    except OSError as e:
        sys.exit(f'Could not reach the FTP server ({type(e).__name__}). Check FTP_HOST and FTP_PORT.')
    print('Logged in OK (secure connection).')
    print('Starting folder:', ftp.pwd())
    print('Folders here:', ', '.join(sorted(n for n in ftp.nlst() if n not in ('.', '..'))))
    ftp.quit()


def file_hash(path):
    h = hashlib.sha256()
    with open(path, 'rb') as f:
        for chunk in iter(lambda: f.read(65536), b''):
            h.update(chunk)
    return h.hexdigest()


def ensure_dir(ftp, path, made):
    parts = path.strip('/').split('/')
    for i in range(1, len(parts) + 1):
        sub = '/'.join(parts[:i])
        if sub in made:
            continue
        try:
            ftp.mkd(sub)
        except ftplib.error_perm:
            pass  # already there
        made.add(sub)


def cmd_deploy():
    env = load_secrets()
    missing = [k for k in REQUIRED['deploy'] if not env.get(k)]
    if missing:
        sys.exit('Fill these in first: ' + ', '.join(missing))
    if not any(env.get(k) for k in REQUIRED['payments (need at least one)']):
        sys.exit('Fill in at least one way to pay (Venmo, Zelle or PayPal).')

    stage = os.path.join(ROOT, 'private', 'deploy-stage')
    build_stage(stage, php_config(server_config(env)), 'config.php')

    web_root = env.get('FTP_WEB_ROOT') or 'public_html'
    targets = {'public_html': web_root, 'glimmerbliss_app': 'glimmerbliss_app'}

    try:
        old = json.load(open(MANIFEST, encoding='utf-8'))
    except (OSError, ValueError):
        old = {}
    new = {}
    todo = []
    for top, remote_top in targets.items():
        base = os.path.join(stage, top)
        for dirpath, _, files in os.walk(base):
            for name in files:
                local = os.path.join(dirpath, name)
                rel = os.path.relpath(local, base).replace('\\', '/')
                remote = f'{remote_top}/{rel}'
                digest = file_hash(local)
                new[remote] = digest
                if old.get(remote) != digest:
                    todo.append((local, remote))

    print(f'{len(todo)} file(s) changed out of {len(new)}.')
    if not todo:
        shutil.rmtree(stage, ignore_errors=True)
        return
    ftp = ftp_connect(env)
    made = set()
    done = {k: v for k, v in old.items() if k in new}
    try:
        for local, remote in todo:
            ensure_dir(ftp, os.path.dirname(remote), made)
            with open(local, 'rb') as f:
                ftp.storbinary(f'STOR {remote}', f)
            done[remote] = new[remote]
            print('  sent', remote if not remote.endswith('config.php') else remote + ' (settings)')
    finally:
        with open(MANIFEST, 'w', encoding='utf-8') as f:
            json.dump(done, f, indent=0)
        ftp.quit()
        # The stage holds the filled-in config.php - don't leave it lying around
        shutil.rmtree(stage, ignore_errors=True)
    print('Upload finished.')


if __name__ == '__main__':
    commands = {'check': cmd_check, 'ftp-test': cmd_ftp_test, 'local': cmd_local, 'deploy': cmd_deploy}
    if len(sys.argv) != 2 or sys.argv[1] not in commands:
        sys.exit(__doc__)
    commands[sys.argv[1]]()
