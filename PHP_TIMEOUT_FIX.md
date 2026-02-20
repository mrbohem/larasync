# PHP Timeout Fix for Large Table Sync

## Problem
When syncing tables with large amounts of data, you may encounter the error:
```
Symfony\Component\ErrorHandler\Error\FatalError - Maximum execution time of 30 seconds exceeded
```

## Solutions Implemented
We've optimized the sync service to use **chunked streaming** which processes data in batches instead of loading everything into memory at once. However, for extremely large tables, you may also need to increase PHP's execution time limits.

## Configuration Options

### Option 1: Edit PHP Configuration (php.ini) - **Recommended**

Locate your `php.ini` file and increase these settings:

```ini
max_execution_time = 300          ; 5 minutes (default: 30 seconds)
max_input_time = 300              ; 5 minutes
memory_limit = 512M               ; Increase if needed (default: 128M)
```

**Find php.ini location:**
```bash
php -i | grep "php.ini"
```

After editing, restart your web server:
```bash
# For Nginx + PHP-FPM
sudo systemctl restart php-fpm

# For Apache
sudo systemctl restart apache2

# For Docker
docker-compose restart
```

---

### Option 2: Create .htaccess (Apache Only)

If you're using Apache and don't have access to `php.ini`, create or edit `.htaccess` in your project root:

```apache
# .htaccess
<IfModule mod_php.c>
    php_value max_execution_time 300
    php_value max_input_time 300
    php_value memory_limit 512M
</IfModule>
```

---

### Option 3: Use Artisan Command with Extended Timeout

When running the sync command, you can set environment variables:

```bash
# Increase timeout in the command execution
env PHP_CLI_SERVER_WORKERS=100 timeout 600 php artisan larasync:sync

# Or use max_execution_time
php -d max_execution_time=300 artisan larasync:sync
```

---

### Option 4: Laravel Configuration (For Web Requests)

If syncing via the web dashboard, update your `Laravel.ini` or configure Docker/deployment:

**For Laravel Forge / Ploi:**
Add to your deployment script:
```bash
sed -i 's/max_execution_time = 30/max_execution_time = 300/' /etc/php/8.*/fpm/php.ini
systemctl restart php8.*-fpm
```

**For Docker:**
Add to `Dockerfile`:
```dockerfile
RUN echo "max_execution_time = 300\nmemory_limit = 512M" >> /usr/local/etc/php/conf.d/larasync.ini
```

---

## Performance Improvements Made

✅ **Chunked Streaming**: Data is now processed in 1000-row chunks instead of loading all at once
✅ **Batch Inserts**: Rows are inserted in batches of 500 for efficiency  
✅ **Memory Management**: Garbage collection triggered between chunks
✅ **Connection Pooling**: Reuses database connections

These changes allow syncing tables with **millions of rows** without memory issues.

---

## Recommended Settings by Table Size

| Table Size | max_execution_time | memory_limit | Notes |
|---|---|---|---|
| < 100K rows | 60 seconds | 256M | Default should work |
| 100K - 1M rows | 180 seconds | 384M | Most cases |
| 1M - 10M rows | 300+ seconds | 512M+ | Large tables |
| 10M+ rows | 600+ seconds | 1G | Batch sync in chunks |

---

## Testing Your Configuration

After making changes, verify the settings took effect:

```bash
php -i | grep max_execution_time
php -i | grep memory_limit
```

Expected output:
```
max_execution_time => 300 => 300
memory_limit => 512M => 512M
```

---

## Still Getting Timeouts?

1. **Check logs**: `tail -f storage/logs/laravel.log`
2. **Monitor memory**: `php -m | grep xdebug` (disable xdebug in production)
3. **Database indexing**: Ensure tables have proper indexes
4. **Network**: Slow database connections can cause timeouts
5. **Batch sync**: Consider syncing tables separately instead of all at once

---

## Additional Notes

- For **PostgreSQL**, triggers are temporarily disabled during sync for better performance
- For **MySQL**, foreign keys are disabled during sync to avoid constraint violations
- The chunked approach reduces memory from O(n) to O(1000) where n = total rows

