# Immediate Upgrades — Default Stack Improvements

These changes are not commands — they are permanent improvements to the default stack.
Every app built after these changes will get them automatically.

---

## 1. Apache → Nginx + PHP-FPM

**Current:** `php:8.2-apache` base image, `apache.conf`, `a2enmod rewrite`

**Change:**
- Base image: `php:8.2-fpm`
- Add Nginx as a separate service in `docker-compose.yml` (or install in same container)
- Nginx proxies PHP requests to PHP-FPM over a Unix socket
- Replace `apache.conf` with `nginx.conf`
- Remove `a2enmod rewrite` — Nginx handles rewrites natively

**Why:**
- Nginx handles static files and concurrent connections with far less memory than Apache
- PHP-FPM keeps worker processes alive — no per-request PHP startup cost
- Industry standard for PHP in production (Laravel Forge, Ploi, etc. all default to this)
- Same or simpler config surface than Apache

**Files affected:**
- `Dockerfile` — new base image, install Nginx, add OPcache ini
- `docker-compose.yml` — update app service, adjust healthcheck if any
- `apache.conf` — replace with `nginx.conf`

---

## 2. OPcache — Enabled by Default

**Current:** Not configured. PHP recompiles every `.php` file on every request.

**Change:**
- Add `opcache.ini` to repo
- `COPY` it into the container at `/usr/local/etc/php/conf.d/opcache.ini`
- Enable in `Dockerfile` via `docker-php-ext-enable opcache`

**Recommended settings:**
```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=60
opcache.fast_shutdown=1
```

**Why:**
- OPcache ships with PHP — zero extra service, zero operational overhead
- 30-50% throughput improvement on read-heavy monoliths with no code changes
- `revalidate_freq=60` means PHP checks for file changes every 60s — safe for prod, fast enough for dev with a container restart

**Files affected:**
- `opcache.ini` — new file at repo root, copied into container
- `Dockerfile` — `COPY opcache.ini` + `docker-php-ext-enable opcache`

---

## 3. MySQL Tuning — Dynamic Defaults at Build Time

**Current:** Stock `mysql:8.0` with zero custom config.

**Change:**
- Add `mysql.cnf` to repo, mounted into the `db` service via `docker-compose.yml`
- `/build` runs `free -m` at start, calculates `innodb_buffer_pool_size = 10% of total RAM`, writes it into `mysql.cnf` before `docker compose up`

**Settings baked in:**
```ini
[mysqld]
slow_query_log = 1
long_query_time = 1
innodb_flush_log_at_trx_commit = 2
max_connections = 100
innodb_buffer_pool_size = {AUTO}   # set dynamically by /build via free -m
```

**Why:**
- `innodb_buffer_pool_size` is the single biggest MySQL performance lever
- 10% of RAM is the default — conservative enough for multiple self-contained apps on the same host, still a massive improvement over MySQL's 128MB stock default. Each app stays fully portable: drop it on a dedicated machine and it automatically uses 10% of whatever RAM that machine has.
- Everything else is universally safe regardless of machine size

**Files affected:**
- `mysql.cnf` — new file at repo root
- `docker-compose.yml` — mount `mysql.cnf` into `db` service
- `build.md` — add `free -m` calculation step before `docker compose up`

---

## 4. Redis — Default Service, Three Optimizations

**Current:** No Redis. PHP file sessions. No caching layer. Every request hits MySQL.

**Change:**
- Add `redis` service to `docker-compose.yml`
- Install `phpredis` extension in `Dockerfile`
- Add `redis.php` config file (connection singleton, auto-loaded)
- Upgrade Jinja2 templates for `scaffold_crud`, `create_model`, `create_internal_api` to generate Redis-aware code by default

**Three optimizations baked into every generated app:**

### 4a. Sessions → Redis
```php
// auto-configured in session_start bootstrap
ini_set('session.save_handler', 'redis');
ini_set('session.save_path', 'tcp://redis:6379');
```
- File lock contention eliminated under concurrent load
- Sessions survive container restarts
- Zero code changes required in generated pages

### 4b. HTML Fragment Cache on HTMX API Endpoints
Every `create_internal_api` generated file gets a cache wrapper:
```php
$cacheKey = 'fragment:' . md5($_SERVER['REQUEST_URI']);
$cached = $redis->get($cacheKey);
if ($cached) { echo $cached; exit; }
// ... render HTML ...
$redis->setex($cacheKey, 300, $output); // 5 min TTL
echo $output;
```
Cache-bust rule injected into every write operation (POST/DELETE handlers bust the corresponding list fragment key).

### 4c. Query Result Cache in Models
Every `scaffold_*` and `create_model` generated `getAll()` method gets:
```php
public function getAll(): array {
    $key = '{resource}:all';
    $cached = $redis->get($key);
    if ($cached) return json_decode($cached, true);
    $result = $this->db->query(...)->fetchAll();
    $redis->setex($key, 300, json_encode($result));
    return $result;
}
```
`create()`, `update()`, `delete()` always bust `{resource}:all` — enforced symmetrically in the template so no inconsistency is possible.

**Why all three are safe as defaults:**
- Sessions: no downside, only upside
- HTML fragment cache: 5-min TTL is conservative, write operations always bust — stale data window is bounded
- Query cache: same TTL + bust pattern, same guarantee
- All three use the same Redis connection, same key convention — predictable, debuggable

**Critical template design constraint — cache key consistency:**

The 5-min TTL is a safety net, NOT the primary freshness mechanism. Users see their changes immediately because write operations bust the cache in the same request — before the user ever refreshes.

This only holds if the bust key exactly matches the set key. A mismatch means stale data until TTL expires — a silent bug that is hard to diagnose in production.

The Jinja2 templates MUST enforce a single key-naming convention, derived from the same template variable at scaffold time:

```php
// model.php.j2 — key set in getAll()
$key = 'resource:{{ table_name }}:all';

// model.php.j2 — key busted in create(), update(), delete()
$redis->del('resource:{{ table_name }}:all');
```

`{{ table_name }}` is the Jinja2 variable injected at scaffold time — same variable, same value in both locations. Mismatch is structurally impossible.

**Rule:** Never hardcode cache key strings. Always derive from the same Jinja2 template variable. This is a non-negotiable constraint on all cache-related templates.

**Files affected:**
- `docker-compose.yml` — add `redis` service
- `Dockerfile` — install `phpredis` extension
- `src/builder/templates/model.php.j2` — add cache get/set/bust to all read/write methods
- `src/builder/templates/internal_api.php.j2` — add fragment cache wrapper
- `src/builder/templates/session_bootstrap.php.j2` — Redis session handler config
- `build.md` — no changes needed, MCP tools handle it via updated templates

---

## Status

| Upgrade | Status |
|---------|--------|
| Nginx + PHP-FPM | Pending |
| OPcache | Pending |
| MySQL tuning (dynamic) | Pending |
| Redis (sessions + fragment cache + query cache) | Pending |
