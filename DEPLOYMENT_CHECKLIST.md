# TunzaAssets Enterprise - Deployment Checklist

## Pre-Deployment Checklist

### 1. Database Setup ☐
```bash
# Run all migrations
php artisan migrate

# Expected: 5 new migration files should run successfully
# - 2026_06_19_010000_enhance_asset_lifecycle.php
# - 2026_06_19_020000_create_approval_workflows.php
# - 2026_06_19_030000_enhance_maintenance_work_orders.php
# - 2026_06_19_040000_complete_finance_module.php
# - 2026_06_19_050000_create_audit_compliance_tables.php
```

### 2. Seed Default Data ☐
```bash
# Seed approval workflows
php artisan db:seed --class=ApprovalWorkflowSeeder

# Expected: Default workflows created for:
# - Requisitions (3 approval levels)
# - Purchase Orders (3 approval levels)
# - CAPEX Requests (3 approval levels)
# - Asset Disposals (3 approval levels)
```

### 3. Verify Models ☐
Check that all new models are created:
```bash
ls -la app/Models/

# New models should include:
# - ApprovalWorkflow.php
# - ApprovalLevel.php
# - ApprovalRequest.php
# - ApprovalAction.php
# - WorkOrder.php
# - PreventiveMaintenanceSchedule.php
# - MaintenanceContract.php
# - AssetDowntimeLog.php
# - SparePart.php
# - PartsUsage.php
# - CostCenter.php
# - Budget.php
# - BudgetLineItem.php
# - JournalEntry.php
# - JournalEntryLine.php
# - PaymentTerm.php
# - PaymentRun.php
# - PaymentRunItem.php
# - FinancialReport.php
# - AssetDisposal.php
# - AssetTransfer.php
# - AuditLog.php
# - Document.php
# - AssetAudit.php
# - AssetAuditItem.php
# - ComplianceRequirement.php
# - ComplianceSubmission.php
# - UserSession.php
# - DataExportLog.php
```

### 4. Verify Jobs ☐
```bash
ls -la app/Jobs/

# Should include:
# - RunMonthlyDepreciation.php
# - GeneratePreventiveMaintenanceWorkOrders.php
# - SendLicenseExpiryNotifications.php
# - SendMaintenanceContractRenewalReminders.php
# - CheckApprovalReminders.php
# - CheckBudgetThresholds.php
```

### 5. Configure Environment ☐

Add to `.env`:
```env
# Queue Configuration
QUEUE_CONNECTION=database  # or redis for production

# Mail Configuration (for notifications)
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-email
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@tunzaassets.com
MAIL_FROM_NAME="TunzaAssets"

# File Storage
FILESYSTEM_DISK=local  # or s3 for production

# Session
SESSION_DRIVER=database

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=info
```

### 6. Setup Laravel Scheduler ☐

**Option A: Crontab (Linux/Mac)**
```bash
crontab -e

# Add this line:
* * * * * cd /path/to/tunzaassets && php artisan schedule:run >> /dev/null 2>&1
```

**Option B: Windows Task Scheduler**
- Create new task
- Trigger: Every 1 minute
- Action: `php C:\path\to\tunzaassets\artisan schedule:run`

### 7. Add Scheduler Code ☐

Edit `app/Console/Kernel.php`:
```php
<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Jobs\RunMonthlyDepreciation;
use App\Jobs\GeneratePreventiveMaintenanceWorkOrders;
use App\Jobs\SendLicenseExpiryNotifications;
use App\Jobs\SendMaintenanceContractRenewalReminders;
use App\Jobs\CheckApprovalReminders;
use App\Jobs\CheckBudgetThresholds;
use App\Models\Company;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule)
    {
        // Monthly depreciation run - 1st of every month at 1 AM
        $schedule->call(function () {
            foreach (Company::all() as $company) {
                RunMonthlyDepreciation::dispatch($company->id, now());
            }
        })->monthlyOn(1, '01:00')->name('monthly-depreciation');

        // Generate preventive maintenance work orders - Daily at 6 AM
        $schedule->job(new GeneratePreventiveMaintenanceWorkOrders)
            ->daily()
            ->at('06:00')
            ->name('generate-pm-work-orders');

        // Send license expiry notifications - Daily at 8 AM
        $schedule->job(new SendLicenseExpiryNotifications)
            ->daily()
            ->at('08:00')
            ->name('license-expiry-notifications');

        // Send maintenance contract renewal reminders - Daily at 8 AM
        $schedule->job(new SendMaintenanceContractRenewalReminders)
            ->daily()
            ->at('08:00')
            ->name('contract-renewal-reminders');

        // Check pending approvals and send reminders - Daily at 9 AM
        $schedule->job(new CheckApprovalReminders)
            ->daily()
            ->at('09:00')
            ->name('approval-reminders');

        // Check budget thresholds and send alerts - Daily at 10 AM
        $schedule->job(new CheckBudgetThresholds)
            ->dailyAt('10:00')
            ->name('budget-threshold-alerts');
    }
}
```

### 8. Setup Queue Workers ☐

**Development:**
```bash
php artisan queue:work
```

**Production (using Supervisor):**

Create `/etc/supervisor/conf.d/tunzaassets-worker.conf`:
```ini
[program:tunzaassets-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/tunzaassets/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/tunzaassets/storage/logs/worker.log
stopwaitsecs=3600
```

Then:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start tunzaassets-worker:*
```

### 9. Add API Routes ☐

Edit `routes/api.php` and add:
```php
use App\Http\Controllers\Api\ApprovalController;
use App\Http\Controllers\Api\WorkOrderController;
use App\Http\Controllers\Api\BudgetController;

Route::middleware('auth:sanctum')->group(function () {
    
    // Approval Routes
    Route::prefix('approvals')->group(function () {
        Route::get('/', [ApprovalController::class, 'index']);
        Route::get('/pending', [ApprovalController::class, 'myPendingApprovals']);
        Route::get('/{id}', [ApprovalController::class, 'show']);
        Route::post('/{actionId}/process', [ApprovalController::class, 'processAction']);
        Route::post('/{id}/cancel', [ApprovalController::class, 'cancel']);
    });

    // Work Order Routes
    Route::apiResource('work-orders', WorkOrderController::class);
    Route::post('work-orders/{id}/complete', [WorkOrderController::class, 'complete']);

    // Budget Routes
    Route::apiResource('budgets', BudgetController::class);
    Route::get('budgets/{id}/utilization', [BudgetController::class, 'utilization']);
    
    // TODO: Add more controllers as needed:
    // - AssetDisposalController
    // - AssetTransferController
    // - PreventiveMaintenanceScheduleController
    // - MaintenanceContractController
    // - CostCenterController
    // - JournalEntryController
    // - AssetAuditController
    // - ComplianceController
});
```

### 10. File Storage Setup ☐
```bash
# Create storage directories
mkdir -p storage/app/documents
mkdir -p storage/app/audit-attachments
mkdir -p storage/app/work-order-attachments

# Set permissions (Linux/Mac)
chmod -R 775 storage
chown -R www-data:www-data storage  # adjust user as needed
```

### 11. Database Indexes (Optional - For Performance) ☐
```sql
-- Additional performance indexes
CREATE INDEX idx_audit_logs_user_date ON audit_logs(user_id, created_at);
CREATE INDEX idx_approval_requests_status ON approval_requests(status, company_id);
CREATE INDEX idx_work_orders_status_date ON work_orders(status, scheduled_date);
CREATE INDEX idx_budgets_active ON budgets(status, fiscal_year) WHERE status = 'active';
```

---

## Post-Deployment Testing

### Test 1: Approval Workflow ☐
1. Create a requisition
2. Verify approval request is created
3. Check pending approvals API: `GET /api/approvals/pending`
4. Approve first level: `POST /api/approvals/{actionId}/process`
5. Verify it moves to next level
6. Complete all approvals
7. Verify requisition status changes to 'approved'

### Test 2: Work Orders ☐
1. Create work order: `POST /api/work-orders`
2. Assign to user
3. Update status to 'in_progress'
4. Complete work order: `POST /api/work-orders/{id}/complete`
5. Verify history is tracked

### Test 3: Budget Tracking ☐
1. Create budget: `POST /api/budgets`
2. Create requisition linked to budget
3. Check budget utilization: `GET /api/budgets/{id}/utilization`
4. Verify amounts are tracked correctly

### Test 4: Background Jobs ☐
```bash
# Test depreciation job
php artisan tinker
>>> \App\Jobs\RunMonthlyDepreciation::dispatch(1, now());

# Test PM generation
>>> \App\Jobs\GeneratePreventiveMaintenanceWorkOrders::dispatch();

# Check queue
php artisan queue:failed  # Should be empty
```

### Test 5: Audit Logging ☐
1. Create/update any entity
2. Check audit_logs table
3. Verify before/after values are captured
4. Verify user information is logged

---

## Monitoring Setup

### 1. Laravel Telescope (Development) ☐
```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

### 2. Laravel Horizon (Production - for Redis queues) ☐
```bash
composer require laravel/horizon
php artisan horizon:install
```

### 3. Log Monitoring ☐
Setup log monitoring for:
- `storage/logs/laravel.log`
- Failed jobs
- Depreciation run results
- Approval notifications

### 4. Database Monitoring ☐
Monitor:
- Table sizes (especially audit_logs)
- Query performance
- Index usage

---

## Security Checklist

### Application Security ☐
- [ ] All routes protected with `auth:sanctum` middleware
- [ ] CSRF protection enabled
- [ ] SQL injection prevention (using Eloquent)
- [ ] XSS protection (using Laravel's escaping)
- [ ] File upload validation
- [ ] Rate limiting enabled

### Database Security ☐
- [ ] Strong database password
- [ ] Database access restricted to application server
- [ ] Regular backups configured
- [ ] Audit logs retention policy defined

### API Security ☐
- [ ] API authentication required
- [ ] API rate limiting configured
- [ ] CORS properly configured
- [ ] API versioning implemented

---

## Performance Optimization

### Database ☐
```bash
# Optimize tables
php artisan db:optimize

# Index optimization (run EXPLAIN on slow queries)
# Add indexes as needed
```

### Caching ☐
```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache
```

### Queue Optimization ☐
- Use Redis for queues in production
- Monitor queue length
- Adjust number of workers based on load

---

## Backup Strategy

### Daily Backups ☐
```bash
#!/bin/bash
# backup.sh

# Database backup
mysqldump -u username -p database_name > backup_$(date +%Y%m%d).sql

# Document storage backup
tar -czf documents_$(date +%Y%m%d).tar.gz storage/app/documents/

# Keep last 30 days
find /backups/ -name "backup_*.sql" -mtime +30 -delete
find /backups/ -name "documents_*.tar.gz" -mtime +30 -delete
```

Add to crontab:
```
0 2 * * * /path/to/backup.sh
```

---

## Documentation

### API Documentation ☐
Generate API documentation:
```bash
# Install Scribe
composer require --dev knuckleswtf/scribe

# Generate docs
php artisan scribe:generate
```

### User Documentation ☐
- [ ] Create user manual for new features
- [ ] Document approval workflows
- [ ] Document work order process
- [ ] Document budget management

---

## Rollback Plan

### If Something Goes Wrong ☐

1. **Rollback migrations:**
```bash
php artisan migrate:rollback --step=5
```

2. **Restore database backup:**
```bash
mysql -u username -p database_name < backup_latest.sql
```

3. **Stop queue workers:**
```bash
sudo supervisorctl stop tunzaassets-worker:*
```

4. **Clear caches:**
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

---

## Production Deployment Steps

### Step-by-Step Deployment ☐

1. **Backup current system**
   ```bash
   ./backup.sh
   ```

2. **Enable maintenance mode**
   ```bash
   php artisan down
   ```

3. **Pull latest code**
   ```bash
   git pull origin main
   ```

4. **Update dependencies**
   ```bash
   composer install --optimize-autoloader --no-dev
   ```

5. **Run migrations**
   ```bash
   php artisan migrate --force
   ```

6. **Seed workflows**
   ```bash
   php artisan db:seed --class=ApprovalWorkflowSeeder --force
   ```

7. **Clear and cache**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

8. **Restart services**
   ```bash
   sudo supervisorctl restart tunzaassets-worker:*
   sudo systemctl restart php8.2-fpm  # or your PHP version
   sudo systemctl restart nginx       # or apache
   ```

9. **Disable maintenance mode**
   ```bash
   php artisan up
   ```

10. **Verify deployment**
    - Test approval creation
    - Test work order creation
    - Check logs for errors
    - Verify queue is processing

---

## Support Contacts

- **Developer**: [Your Name/Company]
- **Database Admin**: [DBA Contact]
- **Server Admin**: [Server Admin Contact]

---

## Sign-Off

- [ ] All migrations run successfully
- [ ] Seeders executed
- [ ] Scheduler configured and running
- [ ] Queue workers running
- [ ] API routes added
- [ ] Tests passed
- [ ] Performance acceptable
- [ ] Backups configured
- [ ] Monitoring enabled
- [ ] Documentation complete

**Deployed By**: ___________________

**Date**: ___________________

**Sign**: ___________________

---

**Congratulations! Your enterprise-grade asset management system is now deployed! 🚀**
