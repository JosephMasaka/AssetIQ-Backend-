<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cost Centers
        if (!Schema::hasTable('cost_centers')) {
            Schema::create('cost_centers', function (Blueprint $table) {
            $table->id();
            $table->string('cost_center_code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->foreignId('manager_id')->nullable()->constrained('users');
            $table->string('type')->nullable(); // department, project, location
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('cost_centers');
            });
        }

        // Budgets
        if (!Schema::hasTable('budgets')) {
            Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->string('budget_code')->unique();
            $table->string('name');
            $table->string('budget_type'); // operational, capital, project
            $table->foreignId('cost_center_id')->nullable()->constrained('cost_centers');
            $table->integer('fiscal_year');
            $table->string('period_type')->default('annual'); // monthly, quarterly, annual
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('total_budget', 15, 2);
            $table->decimal('allocated_amount', 15, 2)->default(0);
            $table->decimal('committed_amount', 15, 2)->default(0);
            $table->decimal('actual_amount', 15, 2)->default(0);
            $table->decimal('available_amount', 15, 2);
            $table->string('status')->default('draft'); // draft, approved, active, closed
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            });
        }

        // Budget Line Items
        if (!Schema::hasTable('budget_line_items')) {
            Schema::create('budget_line_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained('budgets')->cascadeOnDelete();
            $table->foreignId('gl_account_id')->nullable()->constrained('gl_accounts');
            $table->foreignId('asset_category_id')->nullable()->constrained('asset_categories');
            $table->string('line_item_name');
            $table->text('description')->nullable();
            $table->decimal('budgeted_amount', 15, 2);
            $table->decimal('allocated_amount', 15, 2)->default(0);
            $table->decimal('committed_amount', 15, 2)->default(0);
            $table->decimal('actual_amount', 15, 2)->default(0);
            $table->decimal('variance', 15, 2)->default(0);
            $table->decimal('variance_percentage', 5, 2)->default(0);
            $table->timestamps();
            });
        }

        // Journal Entries
        if (!Schema::hasTable('journal_entries')) {
            Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->string('journal_number')->unique();
            $table->date('posting_date');
            $table->date('document_date');
            $table->string('document_type'); // manual, depreciation, asset_acquisition, asset_disposal, invoice
            $table->unsignedBigInteger('document_id')->nullable();
            $table->string('reference_number')->nullable();
            $table->text('description');
            $table->decimal('total_debit', 15, 2);
            $table->decimal('total_credit', 15, 2);
            $table->string('status')->default('draft'); // draft, posted, reversed
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('reversed_by_entry_id')->nullable()->constrained('journal_entries');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('posted_by')->references('id')->on('users');
            });
        }

        // Journal Entry Lines
        if (!Schema::hasTable('journal_entry_lines')) {
            Schema::create('journal_entry_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
            $table->integer('line_number');
            $table->foreignId('gl_account_id')->constrained('gl_accounts');
            $table->foreignId('cost_center_id')->nullable()->constrained('cost_centers');
            $table->string('debit_credit'); // debit, credit
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->string('reference')->nullable();
            $table->timestamps();
            });
        }

        // Payment Terms
        if (!Schema::hasTable('payment_terms')) {
            Schema::create('payment_terms', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->integer('days');
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->integer('discount_days')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('company_id');
            $table->timestamps();
            });
        }

        // Payment Runs
        if (!Schema::hasTable('payment_runs')) {
            Schema::create('payment_runs', function (Blueprint $table) {
            $table->id();
            $table->string('payment_run_number')->unique();
            $table->date('payment_date');
            $table->date('value_date')->nullable();
            $table->string('payment_method'); // bank_transfer, check, cash
            $table->string('status')->default('draft'); // draft, approved, executed, cancelled
            $table->decimal('total_amount', 15, 2);
            $table->integer('invoice_count')->default(0);
            $table->foreignId('bank_account_id')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('company_id');
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('approved_by')->references('id')->on('users');
            });
        }

        // Payment Run Items
        if (!Schema::hasTable('payment_run_items')) {
            Schema::create('payment_run_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_run_id')->constrained('payment_runs')->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained('invoices');
            $table->decimal('payment_amount', 15, 2);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            });
        }

        // Asset Financial Details Enhancement
        if (!Schema::hasColumn('assets', 'cost_center_id')) {
            Schema::table('assets', function (Blueprint $table) {
            $table->foreignId('cost_center_id')->nullable()->after('company_id')->constrained('cost_centers');
            $table->foreignId('budget_id')->nullable()->after('cost_center_id')->constrained('budgets');
            $table->string('funding_source')->nullable()->after('budget_id'); // capex, opex, lease, grant
            $table->decimal('accumulated_depreciation', 15, 2)->default(0)->after('purchase_cost');
            $table->decimal('net_book_value', 15, 2)->nullable()->after('accumulated_depreciation');
            $table->decimal('current_fair_value', 15, 2)->nullable()->after('net_book_value');
            });
        }

        // Add budget tracking to requisitions and POs
        if (!Schema::hasColumn('requisitions', 'budget_id')) {
            Schema::table('requisitions', function (Blueprint $table) {
            $table->foreignId('budget_id')->nullable()->after('company_id')->constrained('budgets');
            $table->foreignId('cost_center_id')->nullable()->after('budget_id')->constrained('cost_centers');
            });
        }

        if (!Schema::hasColumn('purchase_orders', 'budget_id')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
            $table->foreignId('budget_id')->nullable()->after('company_id')->constrained('budgets');
            $table->foreignId('cost_center_id')->nullable()->after('budget_id')->constrained('cost_centers');
            $table->decimal('total_amount', 15, 2)->nullable()->after('currency');
            });
        }

        // Financial Reports Configurations
        if (!Schema::hasTable('financial_reports')) {
            Schema::create('financial_reports', function (Blueprint $table) {
            $table->id();
            $table->string('report_name');
            $table->string('report_type'); // balance_sheet, income_statement, cash_flow, asset_register, depreciation_schedule
            $table->json('parameters')->nullable();
            $table->json('filters')->nullable();
            $table->boolean('is_scheduled')->default(false);
            $table->string('schedule_frequency')->nullable(); // daily, weekly, monthly, quarterly, yearly
            $table->json('recipients')->nullable();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_reports');

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['budget_id']);
            $table->dropForeign(['cost_center_id']);
            $table->dropColumn(['budget_id', 'cost_center_id', 'total_amount']);
        });

        Schema::table('requisitions', function (Blueprint $table) {
            $table->dropForeign(['budget_id']);
            $table->dropForeign(['cost_center_id']);
            $table->dropColumn(['budget_id', 'cost_center_id']);
        });

        Schema::table('assets', function (Blueprint $table) {
            $table->dropForeign(['cost_center_id']);
            $table->dropForeign(['budget_id']);
            $table->dropColumn([
                'cost_center_id',
                'budget_id',
                'funding_source',
                'accumulated_depreciation',
                'net_book_value',
                'current_fair_value'
            ]);
        });

        Schema::dropIfExists('payment_run_items');
        Schema::dropIfExists('payment_runs');
        Schema::dropIfExists('payment_terms');
        Schema::dropIfExists('journal_entry_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('budget_line_items');
        Schema::dropIfExists('budgets');
        Schema::dropIfExists('cost_centers');
    }
};
