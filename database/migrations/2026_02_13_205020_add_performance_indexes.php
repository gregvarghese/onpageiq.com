<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Scans table indexes
        if (! $this->indexExists('scans', 'scans_status_created_at_index')) {
            Schema::table('scans', function (Blueprint $table) {
                $table->index(['status', 'created_at']);
            });
        }
        if (! $this->indexExists('scans', 'scans_triggered_by_user_id_index')) {
            Schema::table('scans', function (Blueprint $table) {
                $table->index('triggered_by_user_id');
            });
        }

        // URLs table indexes
        if (! $this->indexExists('urls', 'urls_project_id_status_index')) {
            Schema::table('urls', function (Blueprint $table) {
                $table->index(['project_id', 'status']);
            });
        }
        if (! $this->indexExists('urls', 'urls_last_scanned_at_index')) {
            Schema::table('urls', function (Blueprint $table) {
                $table->index('last_scanned_at');
            });
        }

        // Credit transactions indexes
        if (! $this->indexExists('credit_transactions', 'credit_transactions_organization_id_created_at_index')) {
            Schema::table('credit_transactions', function (Blueprint $table) {
                $table->index(['organization_id', 'created_at']);
            });
        }
        if (! $this->indexExists('credit_transactions', 'credit_transactions_organization_id_type_index')) {
            Schema::table('credit_transactions', function (Blueprint $table) {
                $table->index(['organization_id', 'type']);
            });
        }

        // Audit logs indexes
        if (! $this->indexExists('audit_logs', 'audit_logs_organization_id_created_at_index')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->index(['organization_id', 'created_at']);
            });
        }
        if (! $this->indexExists('audit_logs', 'audit_logs_auditable_type_auditable_id_index')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->index(['auditable_type', 'auditable_id']);
            });
        }

        // Webhook deliveries indexes
        if (! $this->indexExists('webhook_deliveries', 'webhook_deliveries_webhook_endpoint_id_created_at_index')) {
            Schema::table('webhook_deliveries', function (Blueprint $table) {
                $table->index(['webhook_endpoint_id', 'created_at']);
            });
        }
        if (! $this->indexExists('webhook_deliveries', 'webhook_deliveries_delivered_at_index')) {
            Schema::table('webhook_deliveries', function (Blueprint $table) {
                $table->index('delivered_at');
            });
        }

        // Notifications table indexes
        if (! $this->indexExists('notifications', 'notifications_notifiable_read_at_index')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->index(['notifiable_type', 'notifiable_id', 'read_at'], 'notifications_notifiable_read_at_index');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Not dropping indexes as they may have been created by other migrations
    }

    /**
     * Check if an index exists on a table.
     */
    protected function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $driverName = $connection->getDriverName();

        if ($driverName === 'sqlite') {
            $indexes = $connection->select("PRAGMA index_list({$table})");

            return collect($indexes)->pluck('name')->contains($indexName);
        }

        // PostgreSQL / MySQL
        $indexes = $connection->getDoctrineSchemaManager()->listTableIndexes($table);

        return isset($indexes[$indexName]);
    }
};
