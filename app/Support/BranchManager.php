<?php

namespace App\Support;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class BranchManager
{
    /**
     * Return all valid configured branches (falls back to current branch if none configured).
     *
     * @return array<int, array{key:string,name:string,prefix:string,url:string,is_current:bool}>
     */
    public function branches(): array
    {
        $currentPrefix = (string) DB::getTablePrefix();
        $configured = config('branches.portal');

        if (! is_array($configured)) {
            $raw = trim((string) config('branches.portal_raw', ''));
            $decoded = $raw !== '' ? json_decode($raw, true) : null;
            $configured = is_array($decoded) ? $decoded : [];
        }

        $branches = [];
        $seenKeys = [];

        foreach ($configured as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $key = strtolower(trim((string) ($entry['key'] ?? '')));
            $name = trim((string) ($entry['name'] ?? ''));
            $prefix = (string) ($entry['prefix'] ?? '');
            $url = rtrim(trim((string) ($entry['url'] ?? '')), '/');

            if ($key === '' || $name === '' || isset($seenKeys[$key])) {
                continue;
            }

            if (preg_match('/^[a-z0-9_-]+$/', $key) !== 1) {
                continue;
            }

            if (preg_match('/^[a-zA-Z0-9_]*$/', $prefix) !== 1) {
                continue;
            }

            $seenKeys[$key] = true;
            $branches[] = [
                'key' => $key,
                'name' => $name,
                'prefix' => $prefix,
                'url' => $url,
                'is_current' => $prefix === $currentPrefix,
            ];
        }

        if ($branches === []) {
            return [[
                'key' => strtolower(trim((string) config('branches.current_key', 'cs1'))) ?: 'cs1',
                'name' => trim((string) config('branches.current_name', config('app.name', 'Cơ sở 1'))) ?: 'Cơ sở 1',
                'prefix' => $currentPrefix,
                'url' => rtrim((string) config('app.url', ''), '/'),
                'is_current' => true,
            ]];
        }

        return $branches;
    }

    /**
     * Return only branches whose required tables exist in the database.
     *
     * @param  array<int, string>  $requiredTables
     * @return array<int, array{key:string,name:string,prefix:string,url:string,is_current:bool,connection:string}>
     */
    public function availableBranches(array $requiredTables = ['products', 'sales', 'sale_items']): array
    {
        $available = [];

        foreach ($this->branches() as $branch) {
            $connectionName = $this->resolveConnectionName($branch, $requiredTables);

            if ($connectionName === null) {
                continue;
            }

            $available[] = [
                ...$branch,
                'connection' => $connectionName,
            ];
        }

        return $available;
    }

    /**
     * Find a configured branch by key.
     *
     * @return array{key:string,name:string,prefix:string,url:string,is_current:bool}|null
     */
    public function findBranch(string $branchKey): ?array
    {
        $normalized = strtolower(trim($branchKey));

        foreach ($this->branches() as $branch) {
            if ($branch['key'] === $normalized) {
                return $branch;
            }
        }

        return null;
    }

    /**
     * Return the current site's branch entry.
     *
     * @return array{key:string,name:string,prefix:string,url:string,is_current:bool}
     */
    public function currentBranch(): array
    {
        $branches = $this->branches();

        foreach ($branches as $branch) {
            if ($branch['is_current']) {
                return $branch;
            }
        }

        return $branches[0];
    }

    /**
     * Resolve a connection name pointing to the same physical database with the target branch prefix.
     *
     * @param  array{key:string,name:string,prefix:string,url?:string,is_current:bool}  $branch
     * @param  array<int, string>  $requiredTables
     */
    public function resolveConnectionName(array $branch, array $requiredTables = ['products']): ?string
    {
        $defaultConnection = DB::getDefaultConnection();
        $defaultPrefix = (string) DB::connection($defaultConnection)->getTablePrefix();

        if ($branch['prefix'] === $defaultPrefix) {
            return $this->hasRequiredTables($defaultConnection, $requiredTables) ? $defaultConnection : null;
        }

        $connectionName = 'portal_branch_'.$branch['key'];
        $baseConfig = config("database.connections.{$defaultConnection}", []);

        if (! is_array($baseConfig) || $baseConfig === []) {
            return null;
        }

        $branchConfig = array_merge($baseConfig, [
            'prefix' => $branch['prefix'],
        ]);

        config(["database.connections.{$connectionName}" => $branchConfig]);
        DB::purge($connectionName);

        try {
            $connection = DB::connection($connectionName);

            // All branches share the exact same physical database; reusing the
            // default connection's PDO avoids extra connections and guarantees
            // true single-transaction atomicity (and avoids SQLite file locks).
            $defaultConn = DB::connection($defaultConnection);
            $connection->setPdo($defaultConn->getPdo());
            $connection->setReadPdo($defaultConn->getReadPdo());

            return $this->hasRequiredTables($connectionName, $requiredTables) ? $connectionName : null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Read payment bank settings for a specific branch connection.
     *
     * @return array{bank_code:string,bank_name:string,account_number:string,account_name:string,configured:bool}
     */
    public function readBankSettings(string $connectionName, bool $isCurrentBranch = false): array
    {
        $rows = collect();

        try {
            if (Schema::connection($connectionName)->hasTable('settings')) {
                $rows = DB::connection($connectionName)
                    ->table('settings')
                    ->whereIn('key', ['bank_name', 'bank_account', 'bank_account_name'])
                    ->pluck('value', 'key');
            }
        } catch (Throwable) {
            $rows = collect();
        }

        $fallbackBank = $isCurrentBranch ? (string) config('branches.default_bank.bank_name', '') : '';
        $fallbackAccount = $isCurrentBranch ? (string) config('branches.default_bank.bank_account', '') : '';
        $fallbackAccountName = $isCurrentBranch ? (string) config('branches.default_bank.bank_account_name', '') : '';

        $rawBank = trim((string) ($rows->get('bank_name') ?? ''));
        $rawAccount = trim((string) ($rows->get('bank_account') ?? ''));
        $rawAccountName = trim((string) ($rows->get('bank_account_name') ?? ''));

        $bankCode = Setting::resolveBankCode($rawBank !== '' ? $rawBank : $fallbackBank);
        $bankName = Setting::resolveBankLabel($bankCode);
        $accountNumber = $rawAccount !== '' ? $rawAccount : trim($fallbackAccount);
        $accountName = $rawAccountName !== '' ? $rawAccountName : trim($fallbackAccountName);

        return [
            'bank_code' => $bankCode,
            'bank_name' => $bankName,
            'account_number' => $accountNumber,
            'account_name' => $accountName,
            'configured' => $bankCode !== '' && $accountNumber !== '' && $accountName !== '',
        ];
    }

    /**
     * Save payment bank settings for a specific branch connection.
     */
    public function saveBankSettings(string $connectionName, string $bankCode, string $accountNumber, string $accountName): void
    {
        if (! Schema::connection($connectionName)->hasTable('settings')) {
            return;
        }

        $connection = DB::connection($connectionName);
        $now = now();
        $values = [
            'bank_name' => Setting::resolveBankCode($bankCode),
            'bank_account' => trim($accountNumber),
            'bank_account_name' => trim($accountName),
        ];

        foreach ($values as $key => $value) {
            $exists = $connection->table('settings')->where('key', $key)->exists();

            if ($exists) {
                $connection->table('settings')
                    ->where('key', $key)
                    ->update([
                        'value' => $value,
                        'updated_at' => $now,
                    ]);
            } else {
                $connection->table('settings')->insert([
                    'key' => $key,
                    'value' => $value,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /**
     * Resolve a valid user_id in the target branch's users table to satisfy foreign key constraints.
     */
    public function resolveBranchUserId(ConnectionInterface $connection, ?User $user): ?int
    {
        if ($user === null) {
            return null;
        }

        $defaultPrefix = (string) DB::getTablePrefix();
        if ((string) $connection->getTablePrefix() === $defaultPrefix) {
            return (int) $user->id;
        }

        try {
            $matchedId = $connection->table('users')
                ->where('email', $user->email)
                ->value('id');

            return $matchedId !== null ? (int) $matchedId : null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param  array<int, string>  $requiredTables
     */
    private function hasRequiredTables(string $connectionName, array $requiredTables): bool
    {
        try {
            $schema = Schema::connection($connectionName);

            foreach ($requiredTables as $table) {
                if (! $schema->hasTable($table)) {
                    return false;
                }
            }

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
