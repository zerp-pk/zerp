<?php

/**
 * Front controller for shared hosting, where the document root is the repo root
 * rather than public/.
 *
 * This was Laravel's built-in dev-server shim, which returned false for any file
 * that exists under public/ — a magic value only `php -S` understands. Apache and
 * LiteSpeed just end the script and send an empty 200, so /index.php and
 * /build/manifest.json served blank pages. The .htaccess rewrites static assets
 * straight out of public/ and never routes them here, so the branch had no job
 * beyond breaking those two URLs.
 */

require_once __DIR__.'/public/index.php';
