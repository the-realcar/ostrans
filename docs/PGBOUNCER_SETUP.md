# PgBouncer Connection Pooling Configuration Guide
# For PPUT Ostrans PostgreSQL Database

## Overview
PgBouncer is a lightweight connection pooler for PostgreSQL that significantly improves performance by reusing database connections instead of creating new ones for each request.

## Benefits
- **Reduced Connection Overhead**: Reuses existing connections
- **Better Performance**: Lower latency for connection establishment
- **Resource Efficiency**: Fewer PostgreSQL processes
- **Scalability**: Handle more concurrent users with fewer resources

## Installation

### Windows
1. Download PgBouncer for Windows from: https://www.pgbouncer.org/downloads.html
2. Extract to `C:\Program Files\PgBouncer\`
3. Create configuration file: `pgbouncer.ini`

### Linux (Ubuntu/Debian)
```bash
sudo apt-get update
sudo apt-get install pgbouncer
```

### Linux (CentOS/RHEL)
```bash
sudo yum install pgbouncer
```

## Configuration

### pgbouncer.ini

Create/edit `C:\Program Files\PgBouncer\pgbouncer.ini` (Windows) or `/etc/pgbouncer/pgbouncer.ini` (Linux):

```ini
[databases]
ostrans = host=127.0.0.1 port=5432 dbname=ostrans

[pgbouncer]
; IP address where PgBouncer listens
listen_addr = 127.0.0.1
listen_port = 6432

; Authentication settings
auth_type = md5
auth_file = C:\Program Files\PgBouncer\userlist.txt  ; Windows
; auth_file = /etc/pgbouncer/userlist.txt            ; Linux

; Administrator users
admin_users = postgres

; Connection pooling mode
; session = one server connection per client connection (default)
; transaction = server connection returned after transaction (recommended)
; statement = server connection returned after each statement
pool_mode = transaction

; Maximum number of client connections
max_client_conn = 100

; Default pool size per user/database pair
default_pool_size = 25

; Minimum pool size
min_pool_size = 5

; Reserve pool - how many additional connections to allow if pool is full
reserve_pool_size = 5

; Server connection lifetime
server_lifetime = 3600

; Idle timeout
server_idle_timeout = 600

; Log settings
log_connections = 1
log_disconnections = 1
log_pooler_errors = 1

; Stats period
stats_period = 60
```

### userlist.txt

Create `C:\Program Files\PgBouncer\userlist.txt` (Windows) or `/etc/pgbouncer/userlist.txt` (Linux):

```txt
"postgres" "md5<MD5_HASH_OF_PASSWORD>"
"ostrans_user" "md5<MD5_HASH_OF_PASSWORD>"
```

To generate MD5 hash:
```bash
# Linux/Mac
echo -n "passwordusername" | md5sum

# PowerShell (Windows)
$password = "password"
$username = "postgres"
$combined = $password + $username
$md5 = [System.Security.Cryptography.MD5]::Create()
$hash = $md5.ComputeHash([System.Text.Encoding]::UTF8.GetBytes($combined))
$hashString = [System.BitConverter]::ToString($hash).Replace("-","").ToLower()
Write-Host "md5$hashString"
```

## Starting PgBouncer

### Windows
```powershell
# Start as foreground process
cd "C:\Program Files\PgBouncer"
.\pgbouncer.exe pgbouncer.ini

# Or install as Windows Service
sc create PgBouncer binPath= "C:\Program Files\PgBouncer\pgbouncer.exe C:\Program Files\PgBouncer\pgbouncer.ini" start= auto
sc start PgBouncer
```

### Linux
```bash
# Start service
sudo systemctl start pgbouncer

# Enable on boot
sudo systemctl enable pgbouncer

# Check status
sudo systemctl status pgbouncer
```

## Application Configuration

Update your application's database connection to use PgBouncer:

### Before (Direct PostgreSQL):
```php
$host = 'localhost';
$port = 5432;
$dbname = 'ostrans';
$pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $pass);
```

### After (Via PgBouncer):
```php
$host = 'localhost';
$port = 6432;  // PgBouncer port
$dbname = 'ostrans';
$pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $pass);
```

### Environment Variables (.env)
```env
DB_HOST=localhost
DB_PORT=6432
DB_NAME=ostrans
DB_USER=ostrans_user
DB_PASSWORD=your_password
```

## Monitoring

### Connect to PgBouncer Admin Console
```bash
# Linux
psql -p 6432 -U postgres pgbouncer

# Windows PowerShell
& "C:\Program Files\PostgreSQL\14\bin\psql.exe" -h localhost -p 6432 -U postgres pgbouncer
```

### Useful Admin Commands
```sql
-- Show all databases
SHOW DATABASES;

-- Show pool statistics
SHOW POOLS;

-- Show client connections
SHOW CLIENTS;

-- Show server connections
SHOW SERVERS;

-- Show configuration
SHOW CONFIG;

-- Show statistics
SHOW STATS;

-- Reload configuration (without restart)
RELOAD;

-- Close all connections
PAUSE;
RESUME;
```

## Performance Tuning

### Recommended Settings for PPUT Ostrans (80 concurrent users)

```ini
[pgbouncer]
pool_mode = transaction          ; Best for web applications
max_client_conn = 150            ; Allow 150 client connections
default_pool_size = 30           ; 30 server connections per pool
min_pool_size = 10               ; Keep 10 connections warm
reserve_pool_size = 10           ; 10 extra for bursts
max_db_connections = 50          ; Max connections per database
max_user_connections = 50        ; Max connections per user
```

### Pool Mode Comparison

| Mode | Use Case | Pros | Cons |
|------|----------|------|------|
| session | Long-running connections | Simple, predictable | More server connections needed |
| transaction | Web applications (recommended) | Excellent reuse, fewer connections | Requires transaction management |
| statement | Each SQL statement separate | Maximum reuse | Session-level features unavailable |

## Troubleshooting

### Connection Refused
- Check if PgBouncer is running
- Verify listen_addr and listen_port in config
- Check firewall rules

### Authentication Failed
- Verify userlist.txt has correct MD5 hashes
- Check auth_type setting
- Ensure PostgreSQL user has correct permissions

### Connection Pool Exhausted
- Increase default_pool_size
- Check for connection leaks in application
- Review max_client_conn setting

### High CPU Usage
- Reduce max_client_conn
- Consider increasing server resources
- Check for slow queries

## Health Checks

```powershell
# PowerShell health check script
$pgBouncerPort = 6432
$testConnection = Test-NetConnection -ComputerName localhost -Port $pgBouncerPort
if ($testConnection.TcpTestSucceeded) {
    Write-Host "PgBouncer is running on port $pgBouncerPort" -ForegroundColor Green
} else {
    Write-Host "PgBouncer is not accessible on port $pgBouncerPort" -ForegroundColor Red
}
```

## Best Practices

1. **Use Transaction Pooling**: Best for web applications like PPUT Ostrans
2. **Monitor Pool Usage**: Regularly check SHOW POOLS to ensure pools aren't exhausted
3. **Set Appropriate Timeouts**: Balance between connection reuse and stale connections
4. **Log Everything Initially**: Enable detailed logging during setup, reduce later
5. **Test Under Load**: Use load testing to validate pool size settings
6. **Backup Configuration**: Keep pgbouncer.ini in version control

## Security Considerations

1. **Restrict Listen Address**: Use 127.0.0.1 for local-only access
2. **Firewall Rules**: Block port 6432 from external access
3. **Use MD5 Authentication**: Don't use 'trust' in production
4. **Secure userlist.txt**: Set appropriate file permissions (600 on Linux)
5. **Monitor Admin Access**: Restrict admin_users to trusted accounts

## References

- Official Documentation: https://www.pgbouncer.org/
- GitHub: https://github.com/pgbouncer/pgbouncer
- PostgreSQL Wiki: https://wiki.postgresql.org/wiki/PgBouncer

## Support

For PPUT Ostrans specific questions, refer to project documentation.
For PgBouncer issues, consult official documentation or community forums.
