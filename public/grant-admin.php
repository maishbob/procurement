<?php
/**
 * Bootstrap Admin Access — Direct SQL (no artisan, no timeout)
 * -------------------------------------------------------
 * Reads DB credentials from .env, then:
 *   1. Creates the super-admin role (if missing)
 *   2. Creates the users.manage permission (if missing)
 *   3. Links permission → role
 *   4. Links role → user
 *   5. Clears Spatie cache row from the cache table
 *
 * SECURITY: Delete this file from public_html immediately after use.
 */

define('RUNNER_PASSWORD', 'proc2026setup');
$appPath = '/home/tosnaxan/procurement';

// ── Auth ────────────────────────────────────────────────────────────────────
session_start();
$authed = !empty($_SESSION['artisan_authed']);
if (!$authed) {
    if (isset($_POST['password']) && $_POST['password'] === RUNNER_PASSWORD) {
        $_SESSION['artisan_authed'] = true;
        $authed = true;
    }
    if (!$authed) { ?>
<!DOCTYPE html><html><head><title>Grant Admin Login</title>
<style>body{font-family:monospace;background:#111;color:#eee;display:flex;align-items:center;justify-content:center;height:100vh;margin:0}
form{background:#222;padding:2rem;border-radius:8px}input{display:block;width:260px;padding:.5rem;margin:.5rem 0;background:#333;border:1px solid #555;color:#eee;border-radius:4px}
button{padding:.5rem 1.2rem;background:#2563eb;color:#fff;border:none;border-radius:4px;cursor:pointer}</style></head><body>
<form method="post"><h3>Grant Admin Login</h3>
<input type="password" name="password" placeholder="Password" autofocus>
<button>Login</button></form></body></html>
<?php exit; } }

// ── Parse .env ──────────────────────────────────────────────────────────────
function parseEnv(string $path): array {
    $env = [];
    if (!file_exists($path)) return $env;
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (!str_contains($line, '=')) continue;
        [$key, $val] = explode('=', $line, 2);
        $val = trim($val, " \t\n\r\0\x0B\"'");
        $env[trim($key)] = $val;
    }
    return $env;
}

$env    = parseEnv($appPath . '/.env');
$dbHost = $env['DB_HOST']     ?? '127.0.0.1';
$dbPort = $env['DB_PORT']     ?? '3306';
$dbName = $env['DB_DATABASE'] ?? '';
$dbUser = $env['DB_USERNAME'] ?? '';
$dbPass = $env['DB_PASSWORD'] ?? '';

// ── Connect ──────────────────────────────────────────────────────────────────
$pdo = null;
$connectError = '';
try {
    $pdo = new PDO(
        "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4",
        $dbUser, $dbPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (Exception $e) {
    $connectError = $e->getMessage();
}

// ── Actions ──────────────────────────────────────────────────────────────────
$output  = '';
$success = null;

// List users
if (isset($_POST['list_users']) && $pdo) {
    $rows = $pdo->query("SELECT u.id, u.name, u.email,
        GROUP_CONCAT(r.name ORDER BY r.name SEPARATOR ', ') AS roles
        FROM users u
        LEFT JOIN model_has_roles mhr ON mhr.model_id = u.id AND mhr.model_type = 'App\\\\Models\\\\User'
        LEFT JOIN roles r ON r.id = mhr.role_id
        GROUP BY u.id ORDER BY u.id")->fetchAll(PDO::FETCH_ASSOC);
    $output = "Users in database:\n";
    foreach ($rows as $r) {
        $output .= "  [{$r['id']}] {$r['name']} <{$r['email']}> — roles: " . ($r['roles'] ?: '(none)') . "\n";
    }
    if (!$rows) $output = "No users found. Run the main database seeder first.";
    $success = true;
}

// Grant super-admin
if (isset($_POST['grant']) && $pdo) {
    $email = trim($_POST['email'] ?? 'admin@procurement.local');
    $log   = [];

    try {
        // 1. Find user
        $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            // List available users to help
            $all = $pdo->query("SELECT id, name, email FROM users ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
            $output  = "❌ User not found: {$email}\n\nAvailable users:\n";
            foreach ($all as $u) $output .= "  [{$u['id']}] {$u['name']} <{$u['email']}>\n";
            $success = false;
        } else {
            $log[] = "✔ Found user: {$user['name']} <{$user['email']}> (id={$user['id']})";

            // 2. Upsert super-admin role
            //    The seeder stores name="Super Administrator", slug="super-admin"
            $stmt = $pdo->prepare("SELECT id FROM roles WHERE slug = ? AND guard_name = 'web' LIMIT 1");
            $stmt->execute(['super-admin']);
            $role = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$role) {
                $pdo->prepare(
                    "INSERT INTO roles (name, guard_name, slug, description, level, is_system_role, is_active, created_at, updated_at)
                     VALUES ('Super Administrator','web','super-admin','Full system access',1,1,1,NOW(),NOW())"
                )->execute();
                $roleId = (int) $pdo->lastInsertId();
                $log[] = "✔ Created role 'Super Administrator' (slug=super-admin, id={$roleId})";
            } else {
                $roleId = (int) $role['id'];
                $log[] = "✔ Role already exists (slug=super-admin, id={$roleId})";
            }

            // 3. Upsert all required permissions and link to role
            $requiredPermissions = [
                // Admin / sidebar visibility
                ['name' => 'users.manage',               'module' => 'admin',         'description' => 'Manage users'],
                ['name' => 'roles.manage',               'module' => 'admin',         'description' => 'Manage roles'],
                ['name' => 'permissions.manage',         'module' => 'admin',         'description' => 'Manage permissions'],
                ['name' => 'departments.manage',         'module' => 'admin',         'description' => 'Manage departments'],
                ['name' => 'system.configure',           'module' => 'admin',         'description' => 'Configure system settings'],
                // Requisitions
                ['name' => 'requisitions.create',        'module' => 'requisitions',  'description' => 'Create requisitions'],
                ['name' => 'requisitions.view',          'module' => 'requisitions',  'description' => 'View requisitions'],
                ['name' => 'requisitions.view-all',      'module' => 'requisitions',  'description' => 'View all requisitions'],
                ['name' => 'requisitions.edit',          'module' => 'requisitions',  'description' => 'Edit requisitions'],
                ['name' => 'requisitions.approve-hod',   'module' => 'requisitions',  'description' => 'Approve as HOD'],
                ['name' => 'requisitions.approve-budget','module' => 'requisitions',  'description' => 'Approve budget'],
                ['name' => 'requisitions.approve-principal','module'=>'requisitions', 'description' => 'Approve as Principal'],
                ['name' => 'requisitions.reject',        'module' => 'requisitions',  'description' => 'Reject requisitions'],
                ['name' => 'requisitions.cancel',        'module' => 'requisitions',  'description' => 'Cancel requisitions'],
                // Procurement
                ['name' => 'procurement.create',         'module' => 'procurement',   'description' => 'Create procurement'],
                ['name' => 'procurement.view',           'module' => 'procurement',   'description' => 'View procurement'],
                ['name' => 'procurement.edit',           'module' => 'procurement',   'description' => 'Edit procurement'],
                ['name' => 'procurement.issue-rfq',      'module' => 'procurement',   'description' => 'Issue RFQ'],
                ['name' => 'procurement.evaluate-bids',  'module' => 'procurement',   'description' => 'Evaluate bids'],
                ['name' => 'procurement.recommend-award','module' => 'procurement',   'description' => 'Recommend award'],
                ['name' => 'procurement.approve-award',  'module' => 'procurement',   'description' => 'Approve award'],
                // Purchase Orders
                ['name' => 'purchase-orders.create',     'module' => 'purchase-orders','description' => 'Create POs'],
                ['name' => 'purchase-orders.view',       'module' => 'purchase-orders','description' => 'View POs'],
                ['name' => 'purchase-orders.view-all',   'module' => 'purchase-orders','description' => 'View all POs'],
                ['name' => 'purchase-orders.edit',       'module' => 'purchase-orders','description' => 'Edit POs'],
                ['name' => 'purchase-orders.approve',    'module' => 'purchase-orders','description' => 'Approve POs'],
                ['name' => 'purchase-orders.issue',      'module' => 'purchase-orders','description' => 'Issue POs'],
                ['name' => 'purchase-orders.cancel',     'module' => 'purchase-orders','description' => 'Cancel POs'],
                // GRN
                ['name' => 'grn.create',                 'module' => 'receiving',     'description' => 'Create GRN'],
                ['name' => 'grn.view',                   'module' => 'receiving',     'description' => 'View GRNs'],
                ['name' => 'grn.inspect',                'module' => 'receiving',     'description' => 'Inspect GRN'],
                ['name' => 'grn.approve',                'module' => 'receiving',     'description' => 'Approve GRN'],
                ['name' => 'grn.post',                   'module' => 'receiving',     'description' => 'Post GRN'],
                // Inventory
                ['name' => 'inventory.view',             'module' => 'inventory',     'description' => 'View inventory'],
                ['name' => 'inventory.manage',           'module' => 'inventory',     'description' => 'Manage inventory'],
                ['name' => 'inventory.issue',            'module' => 'inventory',     'description' => 'Issue stock'],
                ['name' => 'inventory.adjust',           'module' => 'inventory',     'description' => 'Adjust stock'],
                ['name' => 'inventory.approve-adjustment','module'=> 'inventory',     'description' => 'Approve adjustments'],
                ['name' => 'inventory.transfer',         'module' => 'inventory',     'description' => 'Transfer stock'],
                ['name' => 'inventory.count',            'module' => 'inventory',     'description' => 'Stock count'],
                // Suppliers
                ['name' => 'suppliers.create',           'module' => 'suppliers',     'description' => 'Register suppliers'],
                ['name' => 'suppliers.view',             'module' => 'suppliers',     'description' => 'View suppliers'],
                ['name' => 'suppliers.edit',             'module' => 'suppliers',     'description' => 'Edit suppliers'],
                ['name' => 'suppliers.approve',          'module' => 'suppliers',     'description' => 'Approve suppliers'],
                ['name' => 'suppliers.blacklist',        'module' => 'suppliers',     'description' => 'Blacklist suppliers'],
                ['name' => 'suppliers.rate',             'module' => 'suppliers',     'description' => 'Rate suppliers'],
                // Finance
                ['name' => 'invoices.create',            'module' => 'finance',       'description' => 'Create invoices'],
                ['name' => 'invoices.view',              'module' => 'finance',       'description' => 'View invoices'],
                ['name' => 'invoices.verify',            'module' => 'finance',       'description' => 'Verify invoices'],
                ['name' => 'invoices.approve',           'module' => 'finance',       'description' => 'Approve invoices'],
                ['name' => 'payments.create',            'module' => 'finance',       'description' => 'Create payments'],
                ['name' => 'payments.view',              'module' => 'finance',       'description' => 'View payments'],
                ['name' => 'payments.view-all',          'module' => 'finance',       'description' => 'View all payments'],
                ['name' => 'payments.approve',           'module' => 'finance',       'description' => 'Approve payments'],
                ['name' => 'payments.process',           'module' => 'finance',       'description' => 'Process payments'],
                ['name' => 'wht.manage',                 'module' => 'finance',       'description' => 'WHT certificates'],
                // Budget
                ['name' => 'budget.view',                'module' => 'budget',        'description' => 'View budgets'],
                ['name' => 'budget.manage',              'module' => 'budget',        'description' => 'Manage budgets'],
                ['name' => 'budget.approve',             'module' => 'budget',        'description' => 'Approve budgets'],
                // Reports
                ['name' => 'reports.procurement',        'module' => 'reports',       'description' => 'Procurement reports'],
                ['name' => 'reports.financial',          'module' => 'reports',       'description' => 'Financial reports'],
                ['name' => 'reports.inventory',          'module' => 'reports',       'description' => 'Inventory reports'],
                ['name' => 'reports.compliance',         'module' => 'reports',       'description' => 'Compliance reports'],
                ['name' => 'reports.audit',              'module' => 'reports',       'description' => 'Audit reports'],
                // Audit
                ['name' => 'audit.view',                 'module' => 'audit',         'description' => 'View audit logs'],
                ['name' => 'audit.view-all',             'module' => 'audit',         'description' => 'View all audit logs'],
                // Planning
                ['name' => 'manage_annual_procurement_plans', 'module' => 'planning', 'description' => 'Manage APP'],
                ['name' => 'review_annual_procurement_plans', 'module' => 'planning', 'description' => 'Review APP'],
                ['name' => 'approve_annual_procurement_plans','module' => 'planning', 'description' => 'Approve APP'],
            ];

            $insertStmt = $pdo->prepare(
                "INSERT IGNORE INTO permissions (name, guard_name, module, description, created_at, updated_at)
                 VALUES (?, 'web', ?, ?, NOW(), NOW())"
            );
            $linkStmt = $pdo->prepare(
                "INSERT IGNORE INTO role_has_permissions (permission_id, role_id)
                 SELECT id, ? FROM permissions WHERE name = ? AND guard_name = 'web'"
            );

            $permCount = 0;
            foreach ($requiredPermissions as $p) {
                $insertStmt->execute([$p['name'], $p['module'], $p['description']]);
                $linkStmt->execute([$roleId, $p['name']]);
                $permCount++;
            }
            $log[] = "✔ Upserted {$permCount} permissions and linked to role";

            // keep $permId for legacy reference (users.manage)
            $permId = (int) $pdo->query("SELECT id FROM permissions WHERE name='users.manage' AND guard_name='web' LIMIT 1")->fetchColumn();

            // 5. Link role → user
            $modelType = 'App\\Models\\User';
            $stmt = $pdo->prepare("SELECT 1 FROM model_has_roles WHERE role_id = ? AND model_id = ? AND model_type = ? LIMIT 1");
            $stmt->execute([$roleId, $user['id'], $modelType]);
            if (!$stmt->fetch()) {
                $pdo->prepare("INSERT INTO model_has_roles (role_id, model_id, model_type) VALUES (?, ?, ?)")
                    ->execute([$roleId, $user['id'], $modelType]);
                $log[] = "✔ Assigned role → user";
            } else {
                $log[] = "✔ User already has this role";
            }

            // 6. Clear Spatie permission cache (delete from cache table if using DB driver)
            try {
                $pdo->exec("DELETE FROM cache WHERE `key` LIKE '%spatie%' OR `key` LIKE '%permission%'");
                $log[] = "✔ Cleared Spatie cache from DB cache table";
            } catch (Exception $e) {
                $log[] = "ℹ Cache clear skipped ({$e->getMessage()})";
            }

            $log[] = "";
            $log[] = "✅ Done! {$user['name']} <{$user['email']}> now has super-admin role.";
            $log[] = "   → Try /admin/users now.";
            $log[] = "";
            $log[] = "⚠  REMEMBER: Delete grant-admin.php from public_html!";

            $output  = implode("\n", $log);
            $success = true;
        }
    } catch (Exception $e) {
        $output  = "❌ Error: " . $e->getMessage();
        $success = false;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Grant Super-Admin</title>
<style>
  *{box-sizing:border-box}
  body{font-family:monospace;background:#0f172a;color:#e2e8f0;margin:0;padding:2rem}
  h2{color:#38bdf8;margin:0 0 .25rem}
  .warning{background:#7f1d1d;border:1px solid #ef4444;padding:.6rem 1rem;border-radius:6px;margin:1rem 0;font-size:.85rem}
  .card{background:#1e293b;border-radius:8px;padding:1.5rem;max-width:560px;margin-bottom:1.5rem}
  label{display:block;font-size:.85rem;color:#94a3b8;margin-bottom:.4rem}
  input[type=email]{width:100%;padding:.5rem .75rem;background:#0f172a;border:1px solid #334155;border-radius:4px;color:#e2e8f0;font-family:monospace;font-size:.9rem}
  .btn{display:inline-block;padding:.5rem 1.2rem;border:none;border-radius:4px;cursor:pointer;font-family:monospace;font-size:.85rem;margin-top:.75rem}
  .btn-blue{background:#2563eb;color:#fff}
  .btn-slate{background:#475569;color:#fff;margin-left:.5rem}
  pre{background:#0f172a;border:1px solid #334155;padding:1rem;border-radius:6px;white-space:pre-wrap;word-wrap:break-word;font-size:.82rem;line-height:1.6;margin-top:1rem}
  .ok{color:#4ade80} .err{color:#f87171}
  .dbinfo{font-size:.78rem;color:#64748b;margin-bottom:.75rem}
</style>
</head>
<body>
<h2>Grant Super-Admin Role</h2>
<div class="warning">⚠ SECURITY: Delete this file from <code>public_html</code> immediately after use.</div>

<?php if ($connectError): ?>
  <div class="card">
    <pre class="err">❌ DB Connection failed: <?= htmlspecialchars($connectError) ?>

Parsed from .env:
  DB_HOST     = <?= htmlspecialchars($dbHost) ?>

  DB_PORT     = <?= htmlspecialchars($dbPort) ?>

  DB_DATABASE = <?= htmlspecialchars($dbName) ?>

  DB_USERNAME = <?= htmlspecialchars($dbUser) ?>
</pre>
  </div>
<?php else: ?>

<div class="card">
  <div class="dbinfo">Connected to: <strong><?= htmlspecialchars($dbName) ?></strong> on <?= htmlspecialchars($dbHost) ?></div>
  <form method="post">
    <label for="email">User email address</label>
    <input type="email" id="email" name="email"
           value="<?= htmlspecialchars($_POST['email'] ?? 'admin@procurement.local') ?>" required>
    <br>
    <button type="submit" name="grant" value="1" class="btn btn-blue">Assign super-admin role</button>
    <button type="submit" name="list_users" value="1" class="btn btn-slate">List all users + roles</button>
  </form>

  <?php if ($output !== ''): ?>
    <pre class="<?= $success ? 'ok' : 'err' ?>"><?= htmlspecialchars($output) ?></pre>
  <?php endif; ?>
</div>

<?php endif; ?>
</body>
</html>
