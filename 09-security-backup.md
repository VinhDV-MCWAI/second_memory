# 09. Bảo Mật & Backup - Security & Disaster Recovery

> Chiến lược bảo mật, backup/restore, monitoring và operational best practices

---

## 🔒 Security Architecture

### Security Layers

```
┌─────────────────────────────────────────┐
│  1. Network Security                    │
│     - Firewall, SSL/TLS                 │
└─────────────────────────────────────────┘
┌─────────────────────────────────────────┐
│  2. Application Security                │
│     - JWT Auth, CORS, Rate Limiting     │
└─────────────────────────────────────────┘
┌─────────────────────────────────────────┐
│  3. Database Security                   │
│     - Prepared Statements, Encryption   │
└─────────────────────────────────────────┘
┌─────────────────────────────────────────┐
│  4. Data Security                       │
│     - Backup Encryption, Access Control │
└─────────────────────────────────────────┘
```

---

## 🛡️ Application Security

### 1. Authentication (JWT)

**Token Structure**:
```
Header.Payload.Signature
eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.
eyJzdWIiOjEsImV4cCI6MTY4MDAwMDAwMH0.
signature_here
```

**Security Measures**:

```php
// config/jwt.php
return [
    'secret' => env('JWT_SECRET'),
    'ttl' => 1440,  // 24 hours
    'refresh_ttl' => 20160,  // 2 weeks
    'algo' => 'HS256',
    'required_claims' => ['iss', 'iat', 'exp', 'nbf', 'sub', 'jti'],
    'blacklist_enabled' => true,  // ✅ Enable token blacklist
    'blacklist_grace_period' => 30,
];
```

**Token Blacklist** (Logout):

```php
public function logout()
{
    $token = auth()->getToken();
    
    // Add to Redis blacklist
    JWTAuth::invalidate($token);
    
    // Also delete from Redis storage
    Redis::del("jwt:{auth()->id()}:{$token}");
    
    return response()->json(['message' => 'Logged out successfully']);
}
```

---

### 2. Authorization (RBAC)

**Permission Check Flow**:

```php
// Middleware: CheckPermission
public function handle(Request $request, Closure $next, string $permission)
{
    // 1. Get authenticated admin
    $admin = auth()->user();
    
    if (!$admin) {
        throw new UnauthorizedException();
    }
    
    // 2. Check if admin has permission
    if (!$this->hasPermission($admin, $permission)) {
        throw new ForbiddenException("You don't have permission: {$permission}");
    }
    
    return $next($request);
}

private function hasPermission(Admin $admin, string $permission): bool
{
    // Check via cache first
    $cacheKey = "admin:{$admin->id}:permissions";
    
    return Cache::remember($cacheKey, 3600, function () use ($admin, $permission) {
        return DB::table('admin_role_mst')
            ->join('api_role_mst', 'admin_role_mst.role_mst_id', '=', 'api_role_mst.role_mst_id')
            ->join('api_mst', 'api_role_mst.api_mst_id', '=', 'api_mst.id')
            ->where('admin_role_mst.admin_mst_id', $admin->id)
            ->where('api_mst.endpoint', $permission)
            ->exists();
    });
}
```

**Usage**:
```php
Route::middleware(['auth:api', 'permission:admin.categories.delete'])
    ->delete('/admin/categories/{id}', [CategoryController::class, 'destroy']);
```

---

### 3. CORS (Cross-Origin Resource Sharing)

```php
// config/cors.php
return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        'http://localhost:3000',      // Dashboard (dev)
        'http://localhost:3457',      // Docs (dev)
        'https://yourdomain.com',     // Production
    ],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,   // Allow cookies/auth headers
];
```

**⚠️ Production**: Không dùng `'*'` (wildcard), list exact domains.

---

### 4. Rate Limiting

**API Rate Limit**:

```php
// app/Http/Kernel.php
protected $middlewareGroups = [
    'api' => [
        'throttle:api',  // Default: 60 requests/minute
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ],
];

// routes/api.php
Route::middleware('throttle:10,1')->group(function () {  // 10 req/min
    Route::post('/auth/login', [AuthController::class, 'login']);
});
```

**Custom Rate Limiter**:

```php
// app/Providers/RouteServiceProvider.php
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by(
        $request->user()?->id ?: $request->ip()
    );
});

RateLimiter::for('uploads', function (Request $request) {
    return Limit::perMinute(10)->by(
        $request->user()->id
    )->response(function () {
        return response()->json([
            'error' => 'Too many upload requests. Please try again later.'
        ], 429);
    });
});
```

---

### 5. SQL Injection Prevention

**✅ SAFE: Eloquent ORM (uses prepared statements)**:

```php
// Automatically escaped
Category::where('name', $userInput)->get();

// Raw query with bindings (safe)
DB::select('SELECT * FROM categories WHERE name = ?', [$userInput]);
```

**❌ UNSAFE: Raw queries without bindings**:

```php
// NEVER DO THIS
DB::select("SELECT * FROM categories WHERE name = '{$userInput}'");
```

---

### 6. XSS (Cross-Site Scripting) Prevention

**Laravel Blade** (auto-escapes):

```blade
{{-- ✅ SAFE: Auto-escaped --}}
<h1>{{ $category->name }}</h1>

{{-- ❌ UNSAFE: Raw HTML --}}
<h1>{!! $category->name !!}</h1>
```

**React** (auto-escapes):

```tsx
// ✅ SAFE
<h1>{category.name}</h1>

// ❌ UNSAFE
<div dangerouslySetInnerHTML={{ __html: category.description }} />
```

**Tiptap Editor**: Lưu JSON, không lưu HTML → Tránh XSS.

---

### 7. CSRF Protection

**Laravel APIs**: Sử dụng JWT thay vì session → Không cần CSRF token.

**Forms (nếu có)**: Laravel Blade tự thêm `@csrf` token.

---

### 8. Environment Variables Security

**❌ NEVER commit `.env` to Git**:

```.gitignore
.env
.env.local
.env.production
```

**✅ Use `.env.example` as template**:

```bash
APP_KEY=  # Generate: php artisan key:generate
JWT_SECRET=  # Generate: php artisan jwt:secret

DB_PASSWORD=your_strong_password_here
MINIO_ROOT_PASSWORD=another_strong_password

# Sensitive keys
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
```

**Secure Storage** (Production):
- Sử dụng **secrets manager** (AWS Secrets Manager, HashiCorp Vault)
- Hoặc encrypted `.env` file với **ansible-vault**, **sops**

---

## 💾 Backup & Restore Strategy

### Backup Architecture

```
┌─────────────────────────────────────────────────┐
│              Source Data                        │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐      │
│  │PostgreSQL│  │  MinIO   │  │   .env   │      │
│  └──────────┘  └──────────┘  └──────────┘      │
└─────────────────────────────────────────────────┘
              │
              ▼
┌─────────────────────────────────────────────────┐
│          Backup Script (backup.sh)              │
│  1. pg_dump (PostgreSQL)                        │
│  2. mc mirror (MinIO)                           │
│  3. tar.gz Archive                              │
└─────────────────────────────────────────────────┘
              │
              ▼
┌─────────────────────────────────────────────────┐
│        Multi-Cloud Distribution (Rclone)        │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐      │
│  │  Google  │  │ OneDrive │  │  AWS S3  │      │
│  │  Drive   │  │          │  │          │      │
│  └──────────┘  └──────────┘  └──────────┘      │
└─────────────────────────────────────────────────┘
```

---

### Backup Script (Chi tiết)

**File**: `backup/backup.sh`

```bash
#!/bin/bash

set -e  # Exit on error
trap 'echo "❌ Backup failed"; cleanup' ERR  # Cleanup on error

# === CONFIGURATION ===
BACKUP_DIR="./backups"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_NAME="system_backup_${TIMESTAMP}"
BACKUP_PATH="${BACKUP_DIR}/${BACKUP_NAME}"

POSTGRES_CONTAINER="ml-postgres"
POSTGRES_DB="second_memory"
POSTGRES_USER="postgres"

MINIO_ENDPOINT="http://ml-minio:9000"
MINIO_BUCKET="second-memory"
MINIO_ALIAS="myminio"

RCLONE_REMOTES="gdrive:SecondMemory/Backups onedrive:SecondMemory/Backups"
RETENTION_DAYS=3

# === FUNCTIONS ===
cleanup() {
    echo "🧹 Cleaning up temporary files..."
    rm -rf "${BACKUP_DIR}"
}

# === STEP 1: Create backup directory ===
echo "📁 Creating backup directory..."
mkdir -p "${BACKUP_PATH}"
mkdir -p "${BACKUP_PATH}/minio"

# === STEP 2: Backup PostgreSQL ===
echo "💾 Backing up PostgreSQL database..."
docker exec "${POSTGRES_CONTAINER}" \
    pg_dump -U "${POSTGRES_USER}" -d "${POSTGRES_DB}" -Fc \
    > "${BACKUP_PATH}/database.dump"

echo "✅ PostgreSQL backup completed ($(du -h ${BACKUP_PATH}/database.dump | cut -f1))"

# === STEP 3: Backup MinIO ===
echo "📦 Backing up MinIO data..."
docker exec ml-minio mc mirror --overwrite \
    "${MINIO_ALIAS}/${MINIO_BUCKET}" \
    /tmp/minio_backup

docker cp ml-minio:/tmp/minio_backup "${BACKUP_PATH}/minio/"
docker exec ml-minio rm -rf /tmp/minio_backup

echo "✅ MinIO backup completed ($(du -sh ${BACKUP_PATH}/minio | cut -f1))"

# === STEP 4: Backup .env files ===
echo "🔐 Backing up environment files..."
cp ../laravel-api/.env "${BACKUP_PATH}/.env.laravel" 2>/dev/null || true
cp ../nextjs-fe/.env.local "${BACKUP_PATH}/.env.nextjs" 2>/dev/null || true
cp ../.env "${BACKUP_PATH}/.env.docker" 2>/dev/null || true

# === STEP 5: Create compressed archive ===
echo "🗜️  Compressing backup..."
tar -czf "${BACKUP_DIR}/${BACKUP_NAME}.tar.gz" \
    -C "${BACKUP_DIR}" "${BACKUP_NAME}"

echo "✅ Archive created: ${BACKUP_NAME}.tar.gz ($(du -h ${BACKUP_DIR}/${BACKUP_NAME}.tar.gz | cut -f1))"

# Remove uncompressed folder
rm -rf "${BACKUP_PATH}"

# === STEP 6: Upload to cloud (Rclone) ===
echo "☁️  Uploading to cloud storage..."

for remote in ${RCLONE_REMOTES}; do
    echo "  📤 Uploading to ${remote}..."
    rclone copy "${BACKUP_DIR}/${BACKUP_NAME}.tar.gz" "${remote}/" \
        --progress --transfers=4 --checkers=8
    
    # Cleanup old backups on cloud (> 3 days)
    echo "  🗑️  Removing old backups from ${remote}..."
    rclone delete "${remote}/" \
        --min-age "${RETENTION_DAYS}d" \
        --include "system_backup_*.tar.gz"
done

echo "✅ Cloud upload completed"

# === STEP 7: Cleanup local backup ===
echo "🧹 Removing local backup (zero-local footprint)..."
rm -rf "${BACKUP_DIR}"

# === DONE ===
echo "✅ Backup completed successfully: ${BACKUP_NAME}.tar.gz"
echo "📊 Summary:"
echo "   - PostgreSQL: ✅"
echo "   - MinIO: ✅"
echo "   - Environment files: ✅"
echo "   - Cloud upload: ✅"
echo "   - Local cleanup: ✅"
```

---

### Automated Backup (Cron Job)

**Setup**:

```bash
# Edit crontab
crontab -e

# Add daily backup at 2:00 AM
0 2 * * * /path/to/second-memory/backup/backup.sh >> /var/log/backup.log 2>&1
```

**Verify**:

```bash
# List cron jobs
crontab -l

# Test backup script manually
bash backup/backup.sh
```

---

### Restore Procedure

**File**: `backup/restore.sh`

```bash
#!/bin/bash

set -e
trap 'echo "❌ Restore failed"; cleanup' ERR

# === CONFIGURATION ===
RESTORE_DIR="./restore_tmp"
RCLONE_REMOTE="gdrive:SecondMemory/Backups"  # Change as needed

POSTGRES_CONTAINER="ml-postgres"
POSTGRES_DB="second_memory"
POSTGRES_USER="postgres"

# === FUNCTIONS ===
cleanup() {
    echo "🧹 Cleaning up..."
    rm -rf "${RESTORE_DIR}"
}

# === STEP 1: List available backups ===
echo "📋 Available backups:"
rclone ls "${RCLONE_REMOTE}/" | grep "system_backup_.*\.tar\.gz"

echo ""
read -p "Enter backup filename to restore (e.g., system_backup_20260409_120000.tar.gz): " BACKUP_FILE

# === STEP 2: Download backup from cloud ===
echo "📥 Downloading backup from cloud..."
mkdir -p "${RESTORE_DIR}"
rclone copy "${RCLONE_REMOTE}/${BACKUP_FILE}" "${RESTORE_DIR}/"

# === STEP 3: Extract archive ===
echo "📦 Extracting archive..."
tar -xzf "${RESTORE_DIR}/${BACKUP_FILE}" -C "${RESTORE_DIR}/"

BACKUP_NAME="${BACKUP_FILE%.tar.gz}"
BACKUP_PATH="${RESTORE_DIR}/${BACKUP_NAME}"

# === STEP 4: Restore PostgreSQL ===
echo "💾 Restoring PostgreSQL database..."
echo "⚠️  This will DROP and recreate the database. Continue? (y/N)"
read -r confirm

if [[ "$confirm" != "y" ]]; then
    echo "❌ Restore cancelled"
    cleanup
    exit 1
fi

# Drop and recreate database
docker exec "${POSTGRES_CONTAINER}" psql -U "${POSTGRES_USER}" -c "DROP DATABASE IF EXISTS ${POSTGRES_DB};"
docker exec "${POSTGRES_CONTAINER}" psql -U "${POSTGRES_USER}" -c "CREATE DATABASE ${POSTGRES_DB};"

# Restore from dump
docker cp "${BACKUP_PATH}/database.dump" "${POSTGRES_CONTAINER}:/tmp/restore.dump"
docker exec "${POSTGRES_CONTAINER}" \
    pg_restore -U "${POSTGRES_USER}" -d "${POSTGRES_DB}" \
    --clean --if-exists --no-owner /tmp/restore.dump

echo "✅ PostgreSQL restored"

# === STEP 5: Restore MinIO ===
echo "📦 Restoring MinIO data..."
docker exec ml-minio mc rm --recursive --force myminio/second-memory/
docker cp "${BACKUP_PATH}/minio/" ml-minio:/tmp/restore_minio
docker exec ml-minio mc mirror --overwrite /tmp/restore_minio myminio/second-memory/
docker exec ml-minio rm -rf /tmp/restore_minio

echo "✅ MinIO restored"

# === STEP 6: Restore .env files ===
echo "🔐 Restoring environment files..."
cp "${BACKUP_PATH}/.env.laravel" ../laravel-api/.env 2>/dev/null || true
cp "${BACKUP_PATH}/.env.nextjs" ../nextjs-fe/.env.local 2>/dev/null || true
cp "${BACKUP_PATH}/.env.docker" ../.env 2>/dev/null || true

# === STEP 7: Cleanup ===
cleanup

# === DONE ===
echo "✅ Restore completed successfully!"
echo "⚠️  Remember to restart services:"
echo "   cd docker && docker compose restart ml-php ml-reverb ml-queue"
```

**Usage**:

```bash
bash backup/restore.sh
```

---

### Backup Best Practices

✅ **3-2-1 Rule**:
- **3** copies of data (original + 2 backups)
- **2** different media (local SSD + cloud)
- **1** offsite copy (cloud storage)

✅ **Atomic Consistency**: Backup PostgreSQL, MinIO và .env cùng thời điểm.

✅ **Test Restores**: Định kỳ test restore để đảm bảo backups không corrupt.

✅ **Encryption**: Encrypt sensitive backups (GPG, age).

```bash
# Encrypt backup
gpg --symmetric --cipher-algo AES256 backup.tar.gz

# Decrypt
gpg --decrypt backup.tar.gz.gpg > backup.tar.gz
```

✅ **Monitoring**: Alert nếu backup fail (Slack webhook, email).

---

## 📊 Monitoring & Logging

### Application Logging

**Laravel Log Channels**:

```php
// config/logging.php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['daily', 'slack'],
    ],
    
    'daily' => [
        'driver' => 'daily',
        'path' => storage_path('logs/laravel.log'),
        'level' => 'debug',
        'days' => 14,
    ],
    
    'slack' => [  // Optional: Send critical errors to Slack
        'driver' => 'slack',
        'url' => env('LOG_SLACK_WEBHOOK_URL'),
        'level' => 'critical',
    ],
];
```

**Usage**:

```php
use Illuminate\Support\Facades\Log;

Log::info('Category created', ['id' => $category->id]);
Log::warning('Cache miss', ['key' => $key]);
Log::error('File upload failed', ['error' => $e->getMessage()]);
Log::critical('Database connection lost');
```

---

### Health Check Endpoints

```php
// routes/web.php
Route::get('/health', function () {
    $dbStatus = 'connected';
    try {
        DB::connection()->getPdo();
    } catch (\Exception $e) {
        $dbStatus = 'disconnected';
    }
    
    $redisStatus = 'connected';
    try {
        Redis::ping();
    } catch (\Exception $e) {
        $redisStatus = 'disconnected';
    }
    
    return response()->json([
        'status' => ($dbStatus === 'connected' && $redisStatus === 'connected') ? 'healthy' : 'unhealthy',
        'timestamp' => now()->toIso8601String(),
        'services' => [
            'database' => $dbStatus,
            'cache' => $redisStatus,
        ],
    ], ($dbStatus === 'connected' && $redisStatus === 'connected') ? 200 : 503);
});
```

**Monitoring Service** (External): UptimeRobot, Pingdom, StatusCake.

---

## 🚨 Incident Response Plan

### 1. Data Breach

**Immediate Actions**:
1. Disconnect affected systems
2. Change all passwords/keys
3. Review access logs
4. Notify users (GDPR compliance)

---

### 2. Service Outage

**Runbook**:

```bash
# 1. Check service health
curl http://localhost/health

# 2. Check Docker containers
docker compose ps

# 3. Check logs
docker compose logs -f ml-php
docker compose logs -f ml-postgres

# 4. Restart services
docker compose restart ml-php ml-reverb ml-queue

# 5. If persists: Restore from backup
bash backup/restore.sh
```

---

**Cập nhật lần cuối**: 2026-04-09
