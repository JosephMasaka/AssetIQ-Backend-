<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Schema::table('maintenances', function (Blueprint $table) {
        //     $table->renameColumn('cost', 'estimated_cost');
        // });

        // Enhance maintenance table
        Schema::table('maintenances', function (Blueprint $table) {
            if (!Schema::hasColumn('maintenances', 'maintenance_number')) {
                $table->string('maintenance_number')->unique()->after('id');
            }
            if (!Schema::hasColumn('maintenances', 'priority')) {
                $table->string('priority')->default('medium')->after('type'); // low, medium, high, critical
            }
            if (!Schema::hasColumn('maintenances', 'status')) {
                $table->string('status')->default('scheduled')->after('priority'); // scheduled, in_progress, completed, cancelled
            }
            if (!Schema::hasColumn('maintenances', 'scheduled_date')) {
                $table->date('scheduled_date')->nullable()->after('maintenance_date');
            }
            if (!Schema::hasColumn('maintenances', 'completed_date')) {
                $table->date('completed_date')->nullable()->after('scheduled_date');
            }
            if (!Schema::hasColumn('maintenances', 'estimated_duration')) {
                $table->time('estimated_duration')->nullable();
            }
            if (!Schema::hasColumn('maintenances', 'actual_duration')) {
                $table->time('actual_duration')->nullable();
            }
            if (!Schema::hasColumn('maintenances', 'assigned_to')) {
                $table->foreignId('assigned_to')->nullable()->constrained('users');
            }
            if (!Schema::hasColumn('maintenances', 'vendor_id')) {
                $table->foreignId('vendor_id')->nullable()->constrained('vendors');
            }
            if (!Schema::hasColumn('maintenances', 'work_order_id')) {
                $table->foreignId('work_order_id')->nullable();
            }
            if (!Schema::hasColumn('maintenances', 'actual_cost')) {
                $table->decimal('actual_cost', 15, 2)->nullable();
            }
            if (!Schema::hasColumn('maintenances', 'findings')) {
                $table->text('findings')->nullable();
            }
            if (!Schema::hasColumn('maintenances', 'recommendations')) {
                $table->text('recommendations')->nullable();
            }
            if (!Schema::hasColumn('maintenances', 'parts_used')) {
                $table->json('parts_used')->nullable();
            }
            if (!Schema::hasColumn('maintenances', 'requires_downtime')) {
                $table->boolean('requires_downtime')->default(false);
            }
            if (!Schema::hasColumn('maintenances', 'downtime_start')) {
                $table->timestamp('downtime_start')->nullable();
            }
            if (!Schema::hasColumn('maintenances', 'downtime_end')) {
                $table->timestamp('downtime_end')->nullable();
            }
        });

        // Work Orders table
        if (!Schema::hasTable('work_orders')) {
            Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->string('work_order_number')->unique();
            $table->string('title');
            $table->text('description');
            $table->string('type'); // corrective, preventive, inspection, calibration, emergency
            $table->string('priority')->default('medium'); // low, medium, high, critical
            $table->string('status')->default('open'); // open, assigned, in_progress, on_hold, completed, cancelled
            $table->foreignId('asset_id')->nullable()->constrained('assets');
            $table->string('location')->nullable();
            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->foreignId('assigned_team_id')->nullable();
            $table->date('requested_date');
            $table->date('scheduled_date')->nullable();
            $table->date('due_date')->nullable();
            $table->date('started_date')->nullable();
            $table->date('completed_date')->nullable();
            $table->time('estimated_hours')->nullable();
            $table->time('actual_hours')->nullable();
            $table->decimal('estimated_cost', 15, 2)->nullable();
            $table->decimal('actual_cost', 15, 2)->nullable();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors');
            $table->text('work_performed')->nullable();
            $table->text('parts_used')->nullable();
            $table->text('notes')->nullable();
            $table->json('attachments')->nullable();
            $table->foreignId('parent_work_order_id')->nullable()->constrained('work_orders');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            });
        }

        // Add FK to maintenances (only if work_order_id column doesn't already have a foreign key)
        if (Schema::hasColumn('maintenances', 'work_order_id')) {
            try {
                Schema::table('maintenances', function (Blueprint $table) {
                    $table->foreign('work_order_id')->references('id')->on('work_orders');
                });
            } catch (\Exception $e) {
                // Foreign key might already exist, skip
            }
        }

        // Preventive Maintenance Schedules
        if (!Schema::hasTable('preventive_maintenance_schedules')) {
            Schema::create('preventive_maintenance_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('schedule_name');
            $table->foreignId('asset_id')->nullable()->constrained('assets');
            $table->foreignId('asset_category_id')->nullable()->constrained('asset_categories');
            $table->string('frequency'); // daily, weekly, monthly, quarterly, yearly
            $table->integer('frequency_value')->default(1);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('last_performed_date')->nullable();
            $table->date('next_due_date');
            $table->text('tasks')->nullable();
            $table->text('instructions')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->foreignId('vendor_id')->nullable()->constrained('vendors');
            $table->decimal('estimated_cost', 15, 2)->nullable();
            $table->time('estimated_duration')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('auto_generate_wo')->default(true);
            $table->integer('lead_time_days')->default(7);
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            });
        }

        // Maintenance Contracts
        if (!Schema::hasTable('maintenance_contracts')) {
            Schema::create('maintenance_contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number')->unique();
            $table->string('contract_name');
            $table->foreignId('vendor_id')->constrained('vendors');
            $table->string('contract_type'); // comprehensive, preventive, on_call, warranty
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('contract_value', 15, 2);
            $table->string('payment_terms')->nullable();
            $table->text('scope_of_work')->nullable();
            $table->text('sla_terms')->nullable();
            $table->integer('response_time_hours')->nullable();
            $table->integer('resolution_time_hours')->nullable();
            $table->string('coverage'); // assets, category, location
            $table->json('covered_assets')->nullable();
            $table->json('covered_categories')->nullable();
            $table->json('covered_locations')->nullable();
            $table->boolean('includes_parts')->default(false);
            $table->boolean('includes_labor')->default(true);
            $table->integer('visits_per_year')->nullable();
            $table->integer('visits_completed')->default(0);
            $table->string('status')->default('active'); // active, expired, cancelled, suspended
            $table->date('renewal_notice_date')->nullable();
            $table->boolean('auto_renew')->default(false);
            $table->json('attachments')->nullable();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            });
        }

        // Asset Downtime Log
        if (!Schema::hasTable('asset_downtime_logs')) {
            Schema::create('asset_downtime_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->foreignId('work_order_id')->nullable()->constrained('work_orders');
            $table->foreignId('maintenance_id')->nullable()->constrained('maintenances');
            $table->string('reason'); // maintenance, breakdown, repair, inspection
            $table->timestamp('start_time');
            $table->timestamp('end_time')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->decimal('production_loss', 15, 2)->nullable();
            $table->text('impact_description')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            });
        }

        // Spare Parts Inventory
        if (!Schema::hasTable('spare_parts')) {
            Schema::create('spare_parts', function (Blueprint $table) {
            $table->id();
            $table->string('part_number')->unique();
            $table->string('part_name');
            $table->text('description')->nullable();
            $table->foreignId('asset_category_id')->nullable()->constrained('asset_categories');
            $table->string('manufacturer')->nullable();
            $table->string('model')->nullable();
            $table->integer('quantity_on_hand')->default(0);
            $table->integer('minimum_quantity')->default(0);
            $table->integer('reorder_quantity')->default(0);
            $table->string('location')->nullable();
            $table->decimal('unit_cost', 15, 2)->nullable();
            $table->foreignId('preferred_vendor_id')->nullable()->constrained('vendors');
            $table->string('status')->default('active');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            });
        }

        // Parts Usage Tracking
        if (!Schema::hasTable('parts_usage')) {
            Schema::create('parts_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spare_part_id')->constrained('spare_parts');
            $table->foreignId('work_order_id')->nullable()->constrained('work_orders');
            $table->foreignId('maintenance_id')->nullable()->constrained('maintenances');
            $table->foreignId('asset_id')->nullable()->constrained('assets');
            $table->integer('quantity_used');
            $table->decimal('unit_cost', 15, 2);
            $table->decimal('total_cost', 15, 2);
            $table->date('usage_date');
            $table->unsignedBigInteger('used_by');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('company_id');
            $table->timestamps();

            $table->foreign('used_by')->references('id')->on('users');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('parts_usage');
        Schema::dropIfExists('spare_parts');
        Schema::dropIfExists('asset_downtime_logs');
        Schema::dropIfExists('maintenance_contracts');
        Schema::dropIfExists('preventive_maintenance_schedules');

        Schema::table('maintenances', function (Blueprint $table) {
            $table->dropForeign(['work_order_id']);
        });

        Schema::dropIfExists('work_orders');

        Schema::table('maintenances', function (Blueprint $table) {
            $table->dropColumn([
                'maintenance_number',
                'priority',
                'status',
                'scheduled_date',
                'completed_date',
                'estimated_duration',
                'actual_duration',
                'assigned_to',
                'vendor_id',
                'work_order_id',
                'actual_cost',
                'findings',
                'recommendations',
                'parts_used',
                'requires_downtime',
                'downtime_start',
                'downtime_end'
            ]);
        });
    }
};
