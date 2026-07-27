# nginx vhosts

Web server hardening in this repo has been Apache-only: the root `.htaccess`
denies the backend tree, and `public/.htaccess` handles the front controller.
nginx reads neither. Dropped into an nginx webroot with no config of its own,
the app serves `/.env` and `/.git/config` to anyone who asks.

These two vhosts are the nginx equivalent. Pick one:

| File | Document root | Use when |
|---|---|---|
| [`zerp-public-docroot.conf`](zerp-public-docroot.conf) | `<repo>/public` | You control the vhost. **Prefer this.** |
| [`zerp-repo-docroot.conf`](zerp-repo-docroot.conf) | `<repo>` | The document root is fixed at the repo root by a panel, the shape the Hostinger deploy uses. |

The public-docroot vhost is the safer of the two by construction: `app/`,
`config/`, `storage/`, `.git/` and `.env` are not under the webroot at all, so
no deny rule has to hold for them to stay private. The repo-docroot vhost keeps
the whole tree under the webroot, so its deny rules are load-bearing, exactly
as the root `.htaccess` is under Apache.

Both assume PHP-FPM on `unix:/run/php/php8.2-fpm.sock`. Adapt `server_name`,
`root`, and `fastcgi_pass`, then:

```bash
sudo cp zerp-public-docroot.conf /etc/nginx/sites-available/zerp
sudo ln -s /etc/nginx/sites-available/zerp /etc/nginx/sites-enabled/zerp
sudo nginx -t && sudo systemctl reload nginx
```

TLS is left out on purpose. Terminate it with certbot (`sudo certbot --nginx
-d zerp.example.com`), which rewrites the `listen` lines in place.

## What each Apache rule became

| Root `.htaccess` | nginx |
|---|---|
| `Options -Indexes` | `autoindex off` |
| `<Files .env> Deny from all` | `location ~ /\.(?!well-known)` (covers `.env`, `.git`, every dotfile) |
| `RewriteRule ^(\.git\|app\|config\|...)` `[F,L]` | same list, as a regex `location` returning 403 |
| `RewriteCond !^/storage/media/` + `RewriteRule ^storage/ [F,L]` | `location ^~ /storage/media/` allow, declared before `location ^~ /storage/` deny |
| `RewriteRule ^(composer\.json\|artisan\|...\|.*\.md)$ [F,L]` | same list, as a regex `location` returning 403 |
| `RewriteRule .* [E=HTTP_AUTHORIZATION:...]` | `fastcgi_param HTTP_AUTHORIZATION $http_authorization` |
| `RewriteRule ^ index.php [L]` | `try_files ... @front` / `try_files ... /index.php?$query_string` |
| `RewriteRule ^(css\|assets\|js\|build\|packages\|...)/(.*)$ public/$1/$2` | `try_files /public$uri` (covers the same prefixes without enumerating them) |

Three rules go beyond what the `.htaccess` does, all worth keeping:

- **`vendor/` is denied.** The root `.htaccess` does not list it, and
  `public/vendor/` does not exist, so nothing legitimate is served from that
  prefix while every request to it reaches Composer package source sitting
  under the webroot.
- **`packages/*.{php,json,lock,md,env,yml,sh}` is denied.** `public/packages`
  is a tree of symlinks into `vendor/zerp/<pkg>`, so each module's own
  `composer.json` and source sit behind that prefix. The `.htaccess` rule
  covering those filenames is anchored at the root, so it does not reach them.
  Module images, the only thing meant to be served there, still work.
- **Only the front controller executes PHP.** Everything else matching
  `\.php$` returns 403, so a stray `.php` under the webroot (an upload, a
  leftover installer) cannot be run. Under Apache any such file executes.

`deploy/` was missing from both and has been added to the `.htaccess` deny
list too, so Apache and nginx stay in step.

## Verify before announcing the site

[`verify-hardening.sh`](verify-hardening.sh) runs the checklist from
[`../RELEASE_README.md`](../RELEASE_README.md) against a live host and exits
non-zero if anything is exposed, so it can gate a deploy:

```bash
./verify-hardening.sh https://zerp.example.com
```

It asks for ~24 paths that must not be readable and checks the app still
serves. A 403 and a 404 both pass: on the public-docroot vhost most of those
paths are not under the webroot at all and the app answers 404, which is as
private as a deny. Only a 200 is a leak.

If anything comes back **EXPOSED**, stop and fix it before going live, or your
source and DB credentials are downloadable. It works against Apache too.

## Note on ordering

nginx picks exactly one `location` per request, and not in file order: exact
(`=`) first, then the longest literal prefix marked `^~`, then regex locations
**in the order they appear**, then the longest plain prefix. Every deny rule in
these files is exact, `^~`, or regex, so all of them are decided before the
`location /` that routes to the app. If you add a rule, keep that in mind: a
plain `location /storage/` would lose to a regex and silently stop denying.
