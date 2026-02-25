<?php
$file = 'c:/laragon/www/procurement/app/Http/Controllers/AdminController.php';
$content = file_get_contents($file);

$replacements = [
    'index' => 'system.configure',
    'indexUsers' => 'users.manage',
    'createUser' => 'users.manage',
    'storeUser' => 'users.manage',
    'showUser' => 'users.manage',
    'editUser' => 'users.manage',
    'updateUser' => 'users.manage',
    'destroyUser' => 'users.manage',
    'resetPassword' => 'users.manage',
    'toggleUserStatus' => 'users.manage',
    'indexDepartments' => 'departments.manage',
    'createDepartment' => 'departments.manage',
    'storeDepartment' => 'departments.manage',
    'showDepartment' => 'departments.manage',
    'editDepartment' => 'departments.manage',
    'updateDepartment' => 'departments.manage',
    'destroyDepartment' => 'departments.manage',
    'indexBudgetLines' => 'budget.manage',
    'createBudgetLine' => 'budget.manage',
    'storeBudgetLine' => 'budget.manage',
    'indexStores' => 'inventory.manage',
    'createStore' => 'inventory.manage',
    'storeStore' => 'inventory.manage',
    'indexCategories' => 'inventory.manage',
    'createCategory' => 'inventory.manage',
    'storeCategory' => 'inventory.manage',
    'editSettings' => 'system.configure',
    'updateSettings' => 'system.configure',
    'editFiscalYear' => 'system.configure',
    'updateFiscalYear' => 'system.configure',
    'activityLogs' => 'audit.view-all',
    'exportActivityLogs' => 'audit.view-all',
    'systemHealth' => 'system.configure',
    'clearCache' => 'system.configure'
];

foreach ($replacements as $method => $permission) {
    // Regex matches the method definition, and the first \s+\$this->authorize('admin'); inside it.
    $pattern = '/(public\s+function\s+' . $method . '\s*\([^\)]*\)\s*\{[^{}]*?\$this->authorize\()\'admin\'(\);)/s';
    $replacement = '${1}\'' . $permission . '\'${2}';
    $content = preg_replace($pattern, $replacement, $content, 1);
}

file_put_contents($file, $content);
echo "Done.\n";
