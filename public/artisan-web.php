<?php
/**
 * Web-based Artisan Runner for cPanel (no SSH required)
 * -------------------------------------------------------
 * SECURITY: Delete this file from the server immediately after use.
 * Place this in your public_html directory, run what you need, then delete it.
 */

define('RUNNER_PASSWORD', 'proc2026setup');

$appPath = '/home/tosnaxan/procurement';

// ── Auth ───────────────────────────────────────────────────────────────────
session_start();
$authed = !empty($_SESSION['artisan_authed']);

if (!$authed) {
    if (isset($_POST['password'])) {
        if ($_POST['password'] === RUNNER_PASSWORD) {
            $_SESSION['artisan_authed'] = true;
            $authed = true;
        } else {
            $authError = 'Wrong password.';
        }
    }
    if (!$authed) {
        ?><!DOCTYPE html><html><head><title>Runner Login</title>
        <style>body{font-family:monospace;background:#111;color:#eee;display:flex;align-items:center;justify-content:center;height:100vh;margin:0}
        form{background:#222;padding:2rem;border-radius:8px}input{display:block;width:260px;padding:.5rem;margin:.5rem 0;background:#333;border:1px solid #555;color:#eee;border-radius:4px}
        button{padding:.5rem 1.2rem;background:#2563eb;color:#fff;border:none;border-radius:4px;cursor:pointer}
        .err{color:#f87171;margin-top:.5rem}</style></head><body>
        <form method="post"><h3>Artisan Web Runner</h3>
        <input type="password" name="password" placeholder="Password" autofocus>
        <button type="submit">Login</button>
        <?php if (isset($authError)) echo "<p class='err'>$authError</p>"; ?>
        </form></body></html>
        <?php
        exit;
    }
}

// ── Log viewer ────────────────────────────────────────────────────────────
$logOutput = '';
if (isset($_POST['view_log'])) {
    $logFile = $appPath . '/storage/logs/laravel.log';
    if (file_exists($logFile)) {
        $lines = file($logFile);
        $last  = array_slice($lines, -120);
        $logOutput = implode('', $last);
    } else {
        $logOutput = 'Log file not found at: ' . $logFile;
    }
}

// ── Allowed commands ───────────────────────────────────────────────────────
$allowedCommands = [
    'config:clear'    => 'php artisan config:clear',
    'cache:clear'     => 'php artisan cache:clear',
    'view:clear'      => 'php artisan view:clear',
    'route:clear'     => 'php artisan route:clear',
    'optimize:clear'  => 'php artisan optimize:clear',
    'optimize'        => 'php artisan optimize',
    'migrate'         => 'php artisan migrate --force',
    'migrate:status'  => 'php artisan migrate:status',
    'db:seed'         => 'php artisan db:seed --force',
    'seed:roles'      => 'php artisan db:seed --class=RolesAndPermissionsSeeder --force',
    'storage:link'    => 'php artisan storage:link',
    'queue:restart'   => 'php artisan queue:restart',
    'key:generate'    => 'php artisan key:generate --force',
];

$output   = '';
$ran      = '';
$success  = null;

if (isset($_POST['cmd']) && array_key_exists($_POST['cmd'], $allowedCommands)) {
    $key = $_POST['cmd'];
    $cmd = 'cd ' . escapeshellarg($appPath) . ' && ' . $allowedCommands[$key] . ' 2>&1';
    $ran = $allowedCommands[$key];

    ob_start();
    $output  = shell_exec($cmd);
    ob_end_clean();

    $success = ($output !== null);
    if ($output === null) {
        $output = 'ERROR: shell_exec returned null. Check PHP disable_functions on this host.';
        $success = false;
    }
}

// ── Run ALL recommended post-deploy commands ───────────────────────────────
$allOutput = '';
if (isset($_POST['run_all'])) {
    $sequence = [
        'php artisan config:clear',
        'php artisan cache:clear',
        'php artisan view:clear',
        'php artisan route:clear',
        'php artisan migrate --force',
        'php artisan optimize',
    ];
    foreach ($sequence as $c) {
        $cmd        = 'cd ' . escapeshellarg($appPath) . ' && ' . $c . ' 2>&1';
        $allOutput .= "$ $c\n";
        $result     = shell_exec($cmd);
        $allOutput .= ($result ?? 'ERROR: shell_exec returned null.') . "\n\n";
    }
    $ran = 'Run All (post-deploy sequence)';
    $output  = $allOutput;
    $success = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Artisan Web Runner</title>
<style>
  * { box-sizing: border-box; }
  body { font-family: monospace; background: #0f172a; color: #e2e8f0; margin: 0; padding: 1.5rem; }
  h2 { color: #38bdf8; margin: 0 0 .25rem; }
  .warning { background: #7f1d1d; border: 1px solid #ef4444; padding: .6rem 1rem; border-radius: 6px; margin-bottom: 1.2rem; font-size: .85rem; }
  .grid { display: grid; grid-template-columns: 1fr 2fr; gap: 1rem; }
  .panel { background: #1e293b; border-radius: 8px; padding: 1rem; }
  h3 { margin: 0 0 .75rem; color: #94a3b8; font-size: .9rem; text-transform: uppercase; letter-spacing: .05em; }
  button { display: block; width: 100%; text-align: left; padding: .45rem .75rem; margin-bottom: .4rem;
           background: #334155; border: none; border-radius: 4px; color: #e2e8f0; cursor: pointer; font-family: monospace; font-size: .85rem; }
  button:hover { background: #2563eb; }
  .run-all { background: #065f46 !important; font-weight: bold; margin-top: .75rem; padding: .55rem .75rem; }
  .run-all:hover { background: #059669 !important; }
  pre { background: #0f172a; border: 1px solid #334155; padding: 1rem; border-radius: 6px; overflow-x: auto;
        white-space: pre-wrap; word-wrap: break-word; font-size: .8rem; line-height: 1.5; min-height: 200px; }
  .ok  { color: #4ade80; }
  .err { color: #f87171; }
  .label { font-size: .75rem; color: #64748b; margin-bottom: .3rem; }
</style>
</head>
<body>
<h2>Artisan Web Runner</h2>
<p style="color:#94a3b8;margin:.25rem 0 .75rem;font-size:.85rem">App path: <strong><?= htmlspecialchars($appPath) ?></strong></p>
<div class="warning">⚠ SECURITY: Delete this file from <code>public_html</code> immediately after you are done.</div>

<div class="grid">
  <!-- Commands panel -->
  <div class="panel">
    <h3>Commands</h3>
    <form method="post">
      <!-- individual commands -->
      <?php foreach ($allowedCommands as $key => $cmd): ?>
        <button type="submit" name="cmd" value="<?= $key ?>"><?= $cmd ?></button>
      <?php endforeach; ?>
      <!-- run all -->
      <button type="submit" name="run_all" value="1" class="run-all">▶ Run all (post-deploy sequence)</button>
      <!-- log viewer -->
      <button type="submit" name="view_log" value="1" style="background:#4c1d95;margin-top:.75rem;display:block;width:100%;text-align:left;padding:.45rem .75rem;border:none;border-radius:4px;color:#e2e8f0;cursor:pointer;font-family:monospace;font-size:.85rem;">🪵 View laravel.log (last 120 lines)</button>
    </form>
  </div>

  <!-- Output panel -->
  <div class="panel">
    <h3>Output</h3>
    <?php if ($logOutput): ?>
      <div class="label">storage/logs/laravel.log — last 120 lines</div>
      <pre style="color:#fbbf24;font-size:.75rem;"><?= htmlspecialchars($logOutput) ?></pre>
    <?php elseif ($ran): ?>
      <div class="label">$ <?= htmlspecialchars($ran) ?></div>
      <pre class="<?= $success ? 'ok' : 'err' ?>"><?= htmlspecialchars($output) ?></pre>
    <?php else: ?>
      <pre style="color:#475569">Click a command to run it.</pre>
    <?php endif; ?>
  </div>
</div>

</body>
</html>
