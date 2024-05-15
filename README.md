# README

# Requirements

- PHP >= 8.0
- Any of the following network engines
    - Swoole PHP extension >= 4.5，with `swoole.use_shortname` set to `Off` in your `php.ini`
    - Swow PHP extension (Beta)
- JSON PHP extension
- Pcntl PHP extension
- OpenSSL PHP extension （If you need to use the HTTPS）
- PDO PHP extension （If you need to use the MySQL Client）
- Redis PHP extension （If you need to use the Redis Client）
- Protobuf PHP extension （If you need to use the gRPC Server or Client）

# Database

Import the database schema [db.sql](db.sql)

# Configuration

Update `.env` file with your database credentials

# CF WAF RULE

- Block all api requests for search engine bots

   ```
   (http.request.uri.path contains "/api" and not http.request.uri.path contains "sitemap" and not http.request.uri.path contains "media" and cf.verified_bot_category in {"Search Engine Crawler" "Search Engine Optimization" "AI Crawler" "Page Preview"}) or (http.request.uri.path contains "/api" and not http.request.uri.path contains "sitemap" and not http.request.uri.path contains "media" and http.user_agent contains "bot") or (http.request.uri.path contains "/api" and not http.request.uri.path contains "sitemap" and not http.request.uri.path contains "media" and http.user_agent contains "spider")
   ```

# Run

```bash
php bin/hyperf.php start
```
