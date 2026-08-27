<?php

/**
 * WakahQuotation Terminal Installation Script
 * Complete setup for new installations with terminal-style interface
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

$password = 'admin123';

// Optional: Restrict to specific IPs (comma-separated). Leave empty to allow all.
$allowedIps = []; // e.g., ['192.168.1.1', '10.0.0.1']

// IP restriction check
if (!empty($allowedIps) && !in_array($_SERVER['REMOTE_ADDR'] ?? '', $allowedIps)) {
    http_response_code(403);
    die('<h1>403 Forbidden</h1><p>Access restricted. Your IP is not allowed.</p>');
}

/**
 * Safely run a shell command with error handling.
 */
function runCommand(string $command): string
{
    try {
        $output = shell_exec($command . ' 2>&1');
        if ($output === null) {
            return "❌ Command failed to execute (returned null).\n";
        }
        return $output;
    } catch (\Throwable $e) {
        return "❌ Error executing command: " . $e->getMessage() . "\n";
    }
}

/**
 * Check if an Artisan command exists before running it.
 */
function artisanCommandExists(string $commandName): bool
{
    $output = shell_exec("php artisan list --raw 2>&1");
    return $output !== null && str_contains($output, $commandName);
}

/**
 * Run an Artisan command safely.
 */
function runArtisan(string $command, string $label = '', bool $force = true): string
{
    $forceFlag = $force ? ' --force' : '';
    $fullCommand = "php artisan {$command}{$forceFlag} 2>&1";
    $result = runCommand($fullCommand);
    if ($label) {
        return "{$label}:\n{$result}";
    }
    return $result;
}

// Define available commands
$commands = [
    'help' => [
        'description' => 'Show available commands',
        'action' => 'help',
    ],
    'full-install' => [
        'description' => 'Complete installation (git + composer + env + migrate + seed + optimize)',
        'action' => 'full_install',
    ],
    'git-init' => [
        'description' => 'Initialize git repository and create initial commit',
        'action' => 'git_setup',
    ],
    'composer-install' => [
        'description' => 'Install composer dependencies (no-dev, optimized)',
        'action' => 'composer_install',
    ],
    'composer-update' => [
        'description' => 'Update composer dependencies (no-dev, optimized)',
        'action' => 'composer_update',
    ],
    'composer-dump' => [
        'description' => 'Regenerate composer autoloader (optimized)',
        'action' => 'composer_dump',
    ],
    'env-setup' => [
        'description' => 'Copy .env.example to .env and generate app key',
        'action' => 'env_setup',
    ],
    'migrate' => [
        'description' => 'Run database migrations',
        'action' => 'migrate',
    ],
    'migrate-fresh' => [
        'description' => 'Fresh migration (WARNING: deletes all data)',
        'action' => 'migrate_fresh',
    ],
    'migrate-fresh-seed' => [
        'description' => 'Fresh migration with seeder (WARNING: deletes all data)',
        'action' => 'migrate_fresh_seed',
    ],
    'migrate-seed' => [
        'description' => 'Run migrations and seed database',
        'action' => 'migrate_seed',
    ],
    'storage-setup' => [
        'description' => 'Create storage symlink and clear caches',
        'action' => 'storage_setup',
    ],
    'roles-setup' => [
        'description' => 'Create basic roles and generate model permissions',
        'action' => 'roles_permissions',
    ],
    'permissions-only' => [
        'description' => 'Generate model permissions only',
        'action' => 'permissions_only',
    ],
    'optimize' => [
        'description' => 'Cache config, routes, views, and Filament components',
        'action' => 'optimize',
    ],
    'optimize-clear' => [
        'description' => 'Clear all caches (config, routes, views, filament)',
        'action' => 'optimize_clear',
    ],
    'pesapal-register-ipn' => [
        'description' => 'Register PesaPal IPN URL (POST method)',
        'action' => 'pesapal_register_ipn',
    ],
    'pesapal-list-ipns' => [
        'description' => 'List registered PesaPal IPN URLs',
        'action' => 'pesapal_list_ipns',
    ],
    'pesapal-setup' => [
        'description' => 'Complete PesaPal setup (register + list + clear config)',
        'action' => 'pesapal_setup',
    ],
    'bridge-token' => [
        'description' => 'Generate a Sanctum API token for the WakahShipping bridge',
        'action' => 'bridge_token',
    ],
];

// Handle AJAX command execution
if ($_POST && isset($_POST['command'])) {
    header('Content-Type: application/json');
    
    if (!isset($_POST['password']) || $_POST['password'] !== $password) {
        echo json_encode(['success' => false, 'output' => "❌ Incorrect password!\n"]);
        exit;
    }
    
    $command = strtolower(trim($_POST['command']));
    
    if ($command === 'clear') {
        echo json_encode(['success' => true, 'output' => 'CLEAR_SCREEN']);
        exit;
    }
    
    if (!isset($commands[$command])) {
        chdir(dirname(__DIR__));
        $rawOutput = runCommand($command . ' 2>&1');
        echo json_encode(['success' => true, 'output' => $rawOutput]);
        exit;
    }
    
    $action = $commands[$command]['action'];
    chdir(dirname(__DIR__));
    
    switch ($action) {
        case 'help':
            $output = "📚 Available Commands:\n";
            $output .= str_repeat("═", 60) . "\n";
            foreach ($commands as $cmd => $info) {
                $output .= sprintf("  %-25s %s\n", $cmd, $info['description']);
            }
            $output .= str_repeat("═", 60) . "\n";
            $output .= "💡 Tip: Type a command and press Enter to execute\n";
            break;
            
        case 'full_install':
            $output = "🚀 Starting Full Installation\n";
            $output .= str_repeat("═", 80) . "\n";
            
            // 1. Git Operations
            $output .= "📦 [1/7] Git Operations\n";
            $output .= "Initializing git repository...\n";
            $output .= runCommand('git init 2>&1');
            $output .= "Adding all files...\n";
            $output .= runCommand('git add . 2>&1');
            $output .= "Creating initial commit...\n";
            $output .= runCommand('git commit -m "Initial commit - WakahQuotation installation" 2>&1');
            $output .= str_repeat("─", 40) . "\n";
            
            // 2. Composer Operations
            $output .= "🎵 [2/7] Composer Operations\n";
            $output .= "Installing composer dependencies...\n";
            putenv('HOME=' . dirname(__DIR__));
            putenv('COMPOSER_HOME=' . dirname(__DIR__) . '/.composer');
            $output .= runCommand('/opt/cpanel/composer/bin/composer install --no-dev --optimize-autoloader 2>&1');
            $output .= str_repeat("─", 40) . "\n";
            
            // 3. Environment Setup
            $output .= "⚙️ [3/7] Environment Setup\n";
            $output .= "Copying .env.example to .env...\n";
            if (file_exists('.env.example') && !file_exists('.env')) {
                if (copy('.env.example', '.env')) {
                    $output .= "✅ .env file created successfully\n";
                } else {
                    $output .= "❌ Failed to copy .env.example\n";
                }
            } elseif (file_exists('.env')) {
                $output .= "ℹ️ .env file already exists\n";
            } else {
                $output .= "❌ .env.example not found\n";
            }
            $output .= "Generating application key...\n";
            $output .= runArtisan('key:generate');
            $output .= str_repeat("─", 40) . "\n";
            
            // 4. Database Setup
            $output .= "🗄️ [4/7] Database Setup\n";
            $output .= "Running migrations...\n";
            $output .= runArtisan('migrate');
            $output .= "Seeding database...\n";
            $output .= runArtisan('db:seed');
            $output .= str_repeat("─", 40) . "\n";
            
            // 5. Storage Setup
            $output .= "📁 [5/7] Storage Setup\n";
            $output .= "Creating storage symbolic link...\n";
            $output .= runArtisan('storage:link');
            $output .= "Clearing and caching...\n";
            $output .= runArtisan('optimize:clear', '', false);
            $output .= str_repeat("─", 40) . "\n";
            
            // 6. Roles and Permissions Setup
            $output .= "👥 [6/7] Roles and Permissions Setup\n";
            $roles = ['admin', 'writer', 'member'];
            foreach ($roles as $role) {
                $output .= "Creating role: {$role}\n";
                $output .= runArtisan("role:create {$role}", "Role: {$role}");
            }
            $output .= "Generating model permissions...\n";
            if (artisanCommandExists('permissions:generate-models')) {
                $output .= runArtisan('permissions:generate-models', 'Permissions', false);
            } else {
                $output .= "⚠️ Command 'permissions:generate-models' not found. Skipping.\n";
            }
            $output .= str_repeat("─", 40) . "\n";
            
            // 7. Final Optimizations
            $output .= "⚡ [7/7] Final Optimizations\n";
            $output .= "Caching configuration...\n";
            $output .= runArtisan('config:cache', '', false);
            $output .= "Caching routes...\n";
            $output .= runArtisan('route:cache', '', false);
            $output .= "Caching views...\n";
            $output .= runArtisan('view:cache', '', false);
            $output .= "Caching Filament components...\n";
            $output .= runArtisan('filament:cache-components', '', false);
            $output .= str_repeat("═", 80) . "\n";
            $output .= "🎉 Installation Complete!\n";
            $output .= "📝 Next steps:\n";
            $output .= "   1. Update your .env file with database credentials\n";
            $output .= "   2. Run 'php artisan serve' to start the application\n";
            $output .= "   3. Visit /admin to access the admin panel\n";
            $output .= "   4. Create an admin user or login with default credentials\n";
            break;
            
        case 'git_setup':
            $output = "📦 Git Setup\n";
            $output .= "Initializing git repository...\n";
            $output .= runCommand('git init 2>&1');
            $output .= "Adding all files...\n";
            $output .= runCommand('git add . 2>&1');
            $output .= "Creating initial commit...\n";
            $output .= runCommand('git commit -m "Initial commit - WakahQuotation installation" 2>&1');
            break;
            
        case 'composer_install':
            $output = "🎵 Composer Installation\n";
            $output .= "Installing dependencies...\n";
            putenv('HOME=' . dirname(__DIR__));
            putenv('COMPOSER_HOME=' . dirname(__DIR__) . '/.composer');
            $output .= runCommand('/opt/cpanel/composer/bin/composer install --no-dev --optimize-autoloader 2>&1');
            break;
            
        case 'composer_update':
            $output = "🎵 Composer Update\n";
            $output .= "Updating dependencies...\n";
            putenv('HOME=' . dirname(__DIR__));
            putenv('COMPOSER_HOME=' . dirname(__DIR__) . '/.composer');
            $output .= runCommand('/opt/cpanel/composer/bin/composer update --no-dev --optimize-autoloader 2>&1');
            break;
            
        case 'composer_dump':
            $output = "🎵 Composer Dump Autoload\n";
            $output .= "Regenerating autoloader...\n";
            putenv('HOME=' . dirname(__DIR__));
            putenv('COMPOSER_HOME=' . dirname(__DIR__) . '/.composer');
            $output .= runCommand('/opt/cpanel/composer/bin/composer dump-autoload --optimize 2>&1');
            break;
            
        case 'migrate':
            $output = "🗄️ Database Migration\n";
            $output .= "Running migrations...\n";
            $output .= runArtisan('migrate');
            break;
            
        case 'migrate_fresh':
            $output = "🗄️ Fresh Migration\n";
            $output .= "⚠️ This will delete all data!\n";
            $output .= runArtisan('migrate:fresh');
            break;
            
        case 'migrate_fresh_seed':
            $output = "🗄️ Fresh Migration with Seeding\n";
            $output .= "⚠️ This will delete all data and reseed!\n";
            $output .= runArtisan('migrate:fresh --seed');
            break;
            
        case 'migrate_seed':
            $output = "🗄️ Migration with Seeding\n";
            $output .= "Running migrations and seeding...\n";
            $output .= runArtisan('migrate --seed');
            break;
            
        case 'env_setup':
            $output = "⚙️ Environment Setup\n";
            $output .= "Copying .env.example to .env...\n";
            if (file_exists('.env.example') && !file_exists('.env')) {
                if (copy('.env.example', '.env')) {
                    $output .= "✅ .env file created successfully\n";
                } else {
                    $output .= "❌ Failed to copy .env.example\n";
                }
            } elseif (file_exists('.env')) {
                $output .= "ℹ️ .env file already exists\n";
            } else {
                $output .= "❌ .env.example not found\n";
            }
            $output .= "Generating application key...\n";
            $output .= runArtisan('key:generate');
            break;
            
        case 'database_setup':
            $output = "🗄️ Database Setup\n";
            $output .= "Running migrations...\n";
            $output .= runArtisan('migrate');
            $output .= "Seeding database...\n";
            $output .= runArtisan('db:seed');
            break;
            
        case 'storage_setup':
            $output = "📁 Storage Setup\n";
            $output .= "Creating storage symbolic link...\n";
            $output .= runArtisan('storage:link');
            $output .= "Clearing caches...\n";
            $output .= runArtisan('optimize:clear', '', false);
            break;
            
        case 'roles_permissions':
            $output = "👥 Roles and Permissions Setup\n";
            $roles = ['admin', 'writer', 'member'];
            foreach ($roles as $role) {
                $output .= "Creating role: {$role}\n";
                $output .= runArtisan("role:create {$role}", "Role: {$role}");
            }
            break;
            
        case 'permissions_only':
            $output = "🔑 Generating Model Permissions\n";
            $output .= str_repeat("─", 40) . "\n";
            $output .= "Generating model permissions (independent of roles)...\n";
            if (artisanCommandExists('permissions:generate-models')) {
                $output .= runArtisan('permissions:generate-models', 'Permissions', false);
            } else {
                $output .= "⚠️ Command 'permissions:generate-models' not found. Skipping.\n";
            }
            break;
            
        case 'optimize':
            $output = "⚡ Optimization\n";
            $output .= "Caching configuration...\n";
            $output .= runArtisan('config:cache', '', false);
            $output .= "Caching routes...\n";
            $output .= runArtisan('route:cache', '', false);
            $output .= "Caching views...\n";
            $output .= runArtisan('view:cache', '', false);
            $output .= "Caching Filament components...\n";
            $output .= runArtisan('filament:cache-components', '', false);
            break;
            
        case 'optimize_clear':
            $output = "🧹 Clearing All Caches\n";
            $output .= runArtisan('config:clear', '', false);
            $output .= runArtisan('route:clear', '', false);
            $output .= runArtisan('view:clear', '', false);
            $output .= runArtisan('optimize:clear', '', false);
            $output .= runArtisan('filament:cache-components', '', false);
            $output .= "✅ All caches cleared\n";
            break;
            
        case 'pesapal_register_ipn':
            $output = "💳 PesaPal IPN Registration\n";
            $output .= "Registering IPN URL with PesaPal (POST method)...\n";
            $output .= runArtisan('pesapal:register-ipn --type=POST');
            break;
            
        case 'pesapal_list_ipns':
            $output = "💳 PesaPal IPN List\n";
            $output .= "Fetching registered IPN URLs...\n";
            $output .= runArtisan('pesapal:list-ipns');
            break;
            
        case 'bridge_token':
            $output = "🔑 Bridge API Token Generation\n";
            $output .= str_repeat("─", 40) . "\n";
            $output .= "Generating WakahShipping bridge token...\n";
            $output .= runArtisan('bridge:generate-token', 'Bridge Token', false);
            $output .= str_repeat("─", 40) . "\n";
            $output .= "📝 Copy the token above to WakahQuotation's .env as WAKAH_SHIPPING_API_TOKEN\n";
            break;

        case 'pesapal_setup':
            $output = "💳 PesaPal Complete Setup\n";
            $output .= str_repeat("─", 40) . "\n";
            $output .= "Step 1: Registering IPN URL (POST method)...\n";
            $output .= runArtisan('pesapal:register-ipn --type=POST');
            $output .= str_repeat("─", 40) . "\n";
            $output .= "Step 2: Listing registered IPNs...\n";
            $output .= runArtisan('pesapal:list-ipns');
            $output .= str_repeat("─", 40) . "\n";
            $output .= "Step 3: Clearing configuration cache...\n";
            $output .= runArtisan('config:clear', '', false);
            $output .= str_repeat("═", 80) . "\n";
            $output .= "🎉 PesaPal Setup Complete!\n";
            $output .= "📝 Next steps:\n";
            $output .= "   1. Add the IPN ID to your .env file: PESAPAL_IPN_ID=your-ipn-id\n";
            $output .= "   2. Ensure PESAPAL_CONSUMER_KEY and PESAPAL_CONSUMER_SECRET are set\n";
            $output .= "   3. Test payment functionality in your application\n";
            break;
            
        default:
            $output = "❌ Unknown action\n";
    }
    
    echo json_encode(['success' => true, 'output' => $output]);
    exit;
}

// Handle initial password check
$authenticated = false;
if ($_POST && isset($_POST['password']) && $_POST['password'] === $password) {
    $authenticated = true;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WakahQuotation Terminal</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Cascadia Code', 'Fira Code', 'JetBrains Mono', 'Consolas', monospace;
            background: #0a0a0a;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: #d4d4d4;
        }
        
        .terminal-container {
            width: 100%;
            max-width: 1000px;
            background: #1e1e1e;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
            overflow: hidden;
            border: 1px solid #333;
        }
        
        .terminal-header {
            background: #2d2d2d;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            gap: 8px;
            border-bottom: 1px solid #333;
        }
        
        .terminal-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }
        
        .dot-red { background: #ff5f56; }
        .dot-yellow { background: #ffbd2e; }
        .dot-green { background: #27c93f; }
        
        .terminal-title {
            margin-left: 12px;
            color: #999;
            font-size: 13px;
        }
        
        .terminal-body {
            padding: 20px;
            min-height: 500px;
            max-height: 600px;
            overflow-y: auto;
            background: #1e1e1e;
        }
        
        .terminal-output {
            white-space: pre-wrap;
            word-wrap: break-word;
            line-height: 1.6;
            font-size: 14px;
        }
        
        .terminal-line {
            margin-bottom: 4px;
        }
        
        .prompt {
            color: #27c93f;
            font-weight: bold;
        }
        
        .command {
            color: #fff;
        }
        
        .output {
            color: #d4d4d4;
        }
        
        .error {
            color: #ff5f56;
        }
        
        .success {
            color: #27c93f;
        }
        
        .info {
            color: #61afef;
        }
        
        .warning {
            color: #ffbd2e;
        }
        
        .terminal-input-area {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 15px 20px;
            background: #252526;
            border-top: 1px solid #333;
        }
        
        .prompt-symbol {
            color: #27c93f;
            font-weight: bold;
            font-size: 14px;
        }
        
        #commandInput {
            flex: 1;
            background: transparent;
            border: none;
            color: #fff;
            font-family: inherit;
            font-size: 14px;
            outline: none;
            caret-color: #fff;
        }
        
        #commandInput::placeholder {
            color: #666;
        }
        
        .auth-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        
        .auth-box {
            background: #1e1e1e;
            padding: 40px;
            border-radius: 12px;
            border: 1px solid #333;
            min-width: 400px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
        }
        
        .auth-box h2 {
            color: #fff;
            margin-bottom: 10px;
            font-size: 24px;
        }
        
        .auth-box p {
            color: #999;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #d4d4d4;
            font-weight: 600;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            background: #252526;
            border: 1px solid #3e3e42;
            border-radius: 6px;
            color: #fff;
            font-family: inherit;
            font-size: 14px;
            outline: none;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            border-color: #27c93f;
        }
        
        .btn {
            width: 100%;
            padding: 14px;
            background: #27c93f;
            color: #1e1e1e;
            border: none;
            border-radius: 6px;
            font-family: inherit;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn:hover {
            background: #22b835;
            transform: translateY(-1px);
        }
        
        .hidden {
            display: none !important;
        }
        
        .scrollbar::-webkit-scrollbar {
            width: 8px;
        }
        
        .scrollbar::-webkit-scrollbar-track {
            background: #1e1e1e;
        }
        
        .scrollbar::-webkit-scrollbar-thumb {
            background: #3e3e42;
            border-radius: 4px;
        }
        
        .scrollbar::-webkit-scrollbar-thumb:hover {
            background: #4e4e52;
        }
        
        .ascii-art {
            color: #27c93f;
            font-size: 12px;
            line-height: 1.2;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <?php if (!$authenticated): ?>
    <div class="auth-overlay" id="authOverlay">
        <div class="auth-box">
            <div class="ascii-art">
 ____                     _        ____            _             _     ____            _             _       
/ ___| _ __   ___   ___ | |_ _ __/ ___| _ __ ___ | |_ _ __ ___ | |   |  _ \  __ _ ___| |__   __ _| |_ ___ 
\___ \| '_ \ / _ \ / _ \| __| '__\___ \| '_ ` _ \| __| '_ ` _ \| |   | | | |/ _` / __| '_ \ / _` | __/ _ \
 ___) | |_) | (_) | (_) | |_| |   ___) | | | | | | |_| | | | | | |   | |_| | (_| \__ \ | | | (_| | ||  __/
|____/| .__/ \___/ \___/ \__|_|  |____/|_| |_| |_|\__|_| |_| |_|_|  |____/ \__,_|___/_| |_|\__,_|\__\___|
      |_|                                                                                                     
            </div>
            <h2>🔐 Authentication Required</h2>
            <p>Enter the installation password to access the terminal</p>
            <form method="POST">
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required placeholder="Enter installation password" autofocus>
                </div>
                <button type="submit" class="btn">Access Terminal</button>
            </form>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="terminal-container <?php echo $authenticated ? '' : 'hidden'; ?>" id="terminalContainer">
        <div class="terminal-header">
            <div class="terminal-dot dot-red"></div>
            <div class="terminal-dot dot-yellow"></div>
            <div class="terminal-dot dot-green"></div>
            <span class="terminal-title">WakahQuotation Terminal v1.0 - Installation & Setup</span>
        </div>
        
        <div class="terminal-body scrollbar" id="terminalOutput">
            <div class="terminal-output" id="output"></div>
        </div>
        
        <div class="terminal-input-area">
            <span class="prompt-symbol">❯</span>
            <input 
                type="text" 
                id="commandInput" 
                placeholder="Type 'help' to see available commands..." 
                autocomplete="off"
                spellcheck="false"
            >
        </div>
    </div>
    
    <script>
        const commands = <?php echo json_encode($commands); ?>;
        const output = document.getElementById('output');
        const input = document.getElementById('commandInput');
        const terminalOutput = document.getElementById('terminalOutput');
        let commandHistory = [];
        let historyIndex = -1;
        
        function appendOutput(text, type = 'output') {
            const line = document.createElement('div');
            line.className = `terminal-line ${type}`;
            line.textContent = text;
            output.appendChild(line);
            terminalOutput.scrollTop = terminalOutput.scrollHeight;
        }
        
        function appendPrompt(command) {
            const line = document.createElement('div');
            line.className = 'terminal-line';
            line.innerHTML = `<span class="prompt">user@wakah:~$</span> <span class="command">${command}</span>`;
            output.appendChild(line);
        }
        
        function scrollToBottom() {
            terminalOutput.scrollTop = terminalOutput.scrollHeight;
        }
        
        async function executeCommand(command) {
            const trimmedCommand = command.trim().toLowerCase();
            
            if (!trimmedCommand) return;
            
            commandHistory.unshift(command);
            historyIndex = -1;
            
            appendPrompt(command);
            
            if (trimmedCommand === 'clear') {
                output.innerHTML = '';
                return;
            }
            
            if (trimmedCommand === 'help') {
                appendOutput('');
                appendOutput('📚 Available Commands:', 'info');
                appendOutput('═'.repeat(60), 'info');
                Object.keys(commands).forEach(cmd => {
                    const padding = ' '.repeat(25 - cmd.length);
                    appendOutput(`  ${cmd}${padding}${commands[cmd].description}`, 'output');
                });
                appendOutput('═'.repeat(60), 'info');
                appendOutput('💡 Tip: Use arrow keys ↑↓ to navigate command history', 'info');
                appendOutput('💡 You can also type raw shell commands (e.g. composer dump-autoload)', 'info');
                appendOutput('');
                return;
            }
            
            appendOutput('⏳ Executing...', 'warning');
            
            try {
                const formData = new FormData();
                formData.append('command', trimmedCommand);
                formData.append('password', '<?php echo $password; ?>');
                
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                const text = await response.text();
                let result;
                try {
                    result = JSON.parse(text);
                } catch (e) {
                    appendOutput('❌ Invalid response from server', 'error');
                    return;
                }
                
                const outputLines = output.querySelectorAll('.terminal-line');
                if (outputLines.length > 0) {
                    const lastLine = outputLines[outputLines.length - 1];
                    if (lastLine.textContent === '⏳ Executing...') {
                        lastLine.remove();
                    }
                }
                
                if (result.output === 'CLEAR_SCREEN') {
                    output.innerHTML = '';
                    return;
                }
                
                if (result.success) {
                    const lines = result.output.split('\n');
                    lines.forEach(line => {
                        if (line.includes('✅') || line.includes('🎉') || line.includes('Complete') || line.includes('successfully')) {
                            appendOutput(line, 'success');
                        } else if (line.includes('⚠️') || line.includes('WARNING')) {
                            appendOutput(line, 'warning');
                        } else if (line.includes('❌') || line.includes('Error')) {
                            appendOutput(line, 'error');
                        } else if (line.includes('ℹ️') || line.includes('info')) {
                            appendOutput(line, 'info');
                        } else {
                            appendOutput(line, 'output');
                        }
                    });
                } else {
                    appendOutput(result.output, 'error');
                }
            } catch (error) {
                appendOutput(`❌ Error: ${error.message}`, 'error');
            }
        }
        
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                executeCommand(this.value);
                this.value = '';
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (historyIndex < commandHistory.length - 1) {
                    historyIndex++;
                    this.value = commandHistory[historyIndex];
                }
            } else if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (historyIndex > 0) {
                    historyIndex--;
                    this.value = commandHistory[historyIndex];
                } else {
                    historyIndex = -1;
                    this.value = '';
                }
            } else if (e.key === 'Tab') {
                e.preventDefault();
                const value = this.value.toLowerCase();
                const matches = Object.keys(commands).filter(cmd => cmd.startsWith(value));
                if (matches.length === 1) {
                    this.value = matches[0];
                } else if (matches.length > 1) {
                    appendPrompt(this.value);
                    appendOutput(matches.join('  '), 'info');
                    this.value = '';
                }
            } else if (e.key === 'l' && e.ctrlKey) {
                e.preventDefault();
                output.innerHTML = '';
            }
        });
        
        // Auto-focus input
        input.focus();
        
        // Keep focus on input when clicking terminal
        document.querySelector('.terminal-body').addEventListener('click', function() {
            input.focus();
        });
        
        // Initial welcome message
        <?php if ($authenticated): ?>
        appendOutput('╔════════════════════════════════════════════════════════════════╗', 'success');
        appendOutput('║  Welcome to WakahQuotation Terminal v1.0                      ║', 'success');
        appendOutput('║  Installation & Setup Interface                                ║', 'success');
        appendOutput('╚════════════════════════════════════════════════════════════════╝', 'success');
        appendOutput('');
        appendOutput('💡 Type "help" to see available commands', 'info');
        appendOutput('💡 Use Tab for autocomplete, ↑↓ for history, Ctrl+L to clear', 'info');
        appendOutput('');
        <?php endif; ?>
    </script>
</body>
</html>
