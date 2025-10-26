<?php
session_start();

// === CONFIGURATION ===
$CONFIG_FILE = __DIR__ . "/otp_config.php";
$LOG_FILE    = __DIR__ . "/otp_log.txt";
$PROJECT_NAME = "PHP OTP Receiver Gateway";
$AUTHOR_NAME  = "Mohamad AlJasem";
$AUTHOR_SITE  = "https://aljasem.eu.org";

// === Load Configuration or Create Default ===
if (!file_exists($CONFIG_FILE)) {
    file_put_contents($CONFIG_FILE, "<?php
\$USERS = ['admin' => '1234'];
\$LOGIN_ENABLED = true;
\$FORWARD_EMAIL_ENABLED = false;
\$FORWARD_TO_EMAIL = 'your-email@example.com';
\$USE_PHP_MAIL = true;
\$SMTP_HOST = 'smtp.example.com';
\$SMTP_PORT = 587;
\$SMTP_USER = 'user@example.com';
\$SMTP_PASS = 'your_smtp_password';
");
}
include $CONFIG_FILE;

// === Read log file for message count ===
$message_count = file_exists($LOG_FILE) ? count(file($LOG_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)) : 0;

// === Handle Asynchronous Message Fetching ===
if (isset($_GET['fetch_messages'])) {
    if (!$LOGIN_ENABLED || !empty($_SESSION['logged_in'])) {
        $log_content = file_exists($LOG_FILE) ? file_get_contents($LOG_FILE) : '';
        $log_entries = array_reverse(array_filter(explode(PHP_EOL, $log_content)));
        foreach ($log_entries as $line) {
            echo '<div class="card mb-2"><div class="card-body p-2"><small>' . htmlspecialchars($line) . '</small></div></div>';
        }
    }
    exit;
}

// === Handle Login ===
if ($LOGIN_ENABLED && isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    if (isset($USERS[$username]) && $USERS[$username] === $password) {
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $username;
        header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
        exit;
    } else {
        $error = "❌ Invalid credentials.";
    }
}

// === Handle Logout ===
if (isset($_GET['logout'])) {
    session_start();
    $_SESSION = [];
    session_destroy();
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}

// === Handle OTP Logging ===
if (isset($_REQUEST['to'], $_REQUEST['otp'])) {
    $entry = date("Y-m-d H:i:s") . " | 📞 " . htmlspecialchars($_REQUEST['to']) . " | 🔢 " . htmlspecialchars($_REQUEST['otp']) . " | 📨 " . htmlspecialchars($_REQUEST['sender_id'] ?? '') . " | 💬 " . htmlspecialchars($_REQUEST['message'] ?? '') . PHP_EOL;
    file_put_contents($LOG_FILE, $entry, FILE_APPEND | LOCK_EX);
}

// === Function to save config file ===
function save_config($new_config_data) {
    $config_string = "<?php\n";
    foreach ($new_config_data as $key => $value) {
        $config_string .= "\${$key} = " . var_export($value, true) . ";\n";
    }
    file_put_contents(__DIR__ . "/otp_config.php", $config_string);
}

// === Handle User Profile Update ===
if ($LOGIN_ENABLED && isset($_POST['update_profile']) && !empty($_SESSION['logged_in'])) {
    $current_username = $_SESSION['username'];
    $new_username = trim($_POST['new_username']);
    $new_password = $_POST['new_password'];
    $updated_users = [];
    foreach($USERS as $user => $pass) {
        $updated_users[$user === $current_username ? $new_username : $user] = ($user === $current_username && !empty($new_password)) ? $new_password : $pass;
    }
    
    $all_settings = include __DIR__ . "/otp_config.php";
    $all_settings['USERS'] = $updated_users;
    save_config($all_settings);

    session_start();
    $_SESSION = [];
    session_destroy();
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?') . "?message=profile_updated");
    exit;
}

// === Handle Admin Settings Update ===
if ($LOGIN_ENABLED && isset($_POST['update_settings']) && !empty($_SESSION['logged_in'])) {
    $new_users = [];
    if (isset($_POST['usernames'], $_POST['passwords'])) {
        foreach ($_POST['usernames'] as $index => $username) {
            $username = trim($username);
            if (!empty($username)) {
                $password = $_POST['passwords'][$index];
                $new_users[$username] = !empty($password) ? $password : ($USERS[$username] ?? '');
            }
        }
    }
    
    $config_to_save = [
        'USERS' => $new_users, 'LOGIN_ENABLED' => isset($_POST['login_enabled']),
        'FORWARD_EMAIL_ENABLED' => isset($_POST['forward_email_enabled']), 'FORWARD_TO_EMAIL' => $_POST['forward_to_email'],
        'USE_PHP_MAIL' => $_POST['mail_method'] === 'php', 'SMTP_HOST' => $_POST['smtp_host'],
        'SMTP_PORT' => (int)$_POST['smtp_port'], 'SMTP_USER' => $_POST['smtp_user'], 'SMTP_PASS' => $_POST['smtp_pass']
    ];
    save_config($config_to_save);

    $_SESSION['success_message'] = "✅ Settings updated successfully.";
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?') . "?tab=settings");
    exit;
}

// Determine if the login page should be shown
$show_login_page = $LOGIN_ENABLED && !isset($_SESSION['logged_in']);
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<title><?= $PROJECT_NAME ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
:root { --navbar-height: 56px; --footer-height: 45px; }
html, body { height: 100%; }
body { display: flex; flex-direction: column; background-color: #f8f9fa; }
.main-wrapper { flex: 1; }
.navbar { background: linear-gradient(90deg, #00c6ff 0%, #0072ff 100%); }
.sidebar { background: linear-gradient(180deg, #00c6ff 0%, #00ff88 100%); }
footer { background-color: #e9ecef; padding: 10px; text-align: center; color: #000; font-weight: 500; height: var(--footer-height); }
[data-theme='dark'] body { background-color: #121212; color: #f1f1f1; }
[data-theme='dark'] .navbar, [data-theme='dark'] .sidebar { background: linear-gradient(90deg, #0f2027, #203a43, #2c5364); }
[data-theme='dark'] footer { background: #1e1e1e; color: #ddd; }
[data-theme='dark'] .card { background-color: #1e1e1e; border: 1px solid #333; }
[data-theme='dark'] .form-control { background-color: #2a2a2a; color: #f1f1f1; border-color: #444; }
.sidebar .nav-link { color: #fff; font-weight: bold; }
.sidebar .nav-link.active { background-color: rgba(255,255,255,0.3); }
#darkModeToggle { border: none; background: transparent; color: #fff; font-size: 1.5rem; cursor: pointer; padding: 0; line-height: 1; }
body.login-page {
    background-image: url('https://source.unsplash.com/random/1600x900/?technology,abstract');
    background-size: cover; background-position: center; position: relative;
    display: flex; align-items: center; justify-content: center;
}
body.login-page::before { content: ''; position: absolute; inset: 0; background: rgba(0, 0, 0, 0.5); z-index: 1; }
.login-container { z-index: 2; }
.login-card { width: 380px; background: rgba(255, 255, 255, 0.9); }
[data-theme='dark'] .login-card { background: rgba(30, 30, 30, 0.9); }
</style>
</head>
<body class="<?= $show_login_page ? 'login-page' : '' ?>">

<?php if ($show_login_page): ?>
<div class="login-container">
  <div class="login-card card">
    <div class="card-body text-center p-4">
      <h1 style="font-size: 4rem;">📨</h1>
      <h4 class="card-title mb-3"><?= $PROJECT_NAME ?></h4>
      <?php 
        if (isset($error)) echo '<div class="alert alert-danger">'.$error.'</div>'; 
        if (isset($_GET['message']) && $_GET['message'] === 'profile_updated') {
            echo '<div class="alert alert-success">✅ Profile updated. Please log in again.</div>';
        }
      ?>
      <form method="post" action="">
        <div class="mb-3 text-start"><label class="form-label">👤 Username</label><input type="text" name="username" class="form-control" required></div>
        <div class="mb-3 text-start"><label class="form-label">🔑 Password</label><input type="password" name="password" class="form-control" required></div>
        <button type="submit" name="login" class="btn btn-success w-100">🚀 Login</button>
      </form>
    </div>
  </div>
</div>

<?php else: // --- Main Application --- ?>
<div class="main-wrapper">
    <nav class="navbar navbar-dark" style="height: var(--navbar-height);">
      <div class="container-fluid">
        <span class="navbar-brand">📨 <?= $PROJECT_NAME ?></span>
        <div class="d-flex align-items-center gap-3">
          <?php if ($LOGIN_ENABLED && isset($_SESSION['username'])): ?>
            <span class="text-white">Welcome, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>!</span>
          <?php endif; ?>
          <button id="darkModeToggle">☀️</button>
          <?php if ($LOGIN_ENABLED): ?><a href="?logout=1" class="btn btn-warning btn-sm">🔓 Logout</a><?php endif; ?>
        </div>
      </div>
    </nav>
    <div class="container-fluid">
      <div class="row" style="min-height: calc(100vh - var(--navbar-height) - var(--footer-height));">
        <div class="col-md-2 sidebar p-3">
          <nav class="nav flex-column">
            <a class="nav-link" href="#" onclick="showTab('messages', this)">📨 Messages</a>
            <a class="nav-link" href="#" onclick="showTab('profile', this)">👤 Profile</a>
            <a class="nav-link" href="#" onclick="showTab('settings', this)">⚙️ Settings</a>
            <a class="nav-link" href="#" onclick="showTab('docs', this)">📚 Documentation</a>
          </nav>
        </div>
        <div class="col-md-10 p-4">
          <div class="tab-content">
            <div class="tab-pane fade" id="messages"><div id="message-list">Loading messages...</div></div>
            <div class="tab-pane fade" id="profile">
              <h4>👤 My Profile</h4>
              <p>Update your personal login credentials.</p>
              <form method="post">
                <div class="mb-3"><label class="form-label">Username</label><input type="text" name="new_username" class="form-control" required value="<?= htmlspecialchars($_SESSION['username'] ?? '') ?>"></div>
                <div class="mb-3"><label class="form-label">New Password</label><input type="password" name="new_password" class="form-control" placeholder="Leave blank to keep current password"></div>
                <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
              </form>
            </div>
            <div class="tab-pane fade" id="docs">
              <h4>📚 Documentation</h4>
              <ol>
                <li>Install the <strong>Digits plugin</strong> on your WordPress site.</li>
                <li>Go to Digits settings → SMS Gateway → Custom Gateway.</li>
                <li>Set the API URL to: <br><code><?= htmlspecialchars("http" . (isset($_SERVER['HTTPS']) ? "s" : "") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'] . "?to={to}&otp={otp}&message={message}&sender_id={sender_id}") ?></code></li>
              </ol>
            </div>
            <div class="tab-pane fade" id="settings">
              <h4>⚙️ Admin Settings</h4>
              <?php if (isset($_SESSION['success_message'])) { echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>'; unset($_SESSION['success_message']); } ?>
              <form method="post">
                <h5>General</h5>
                <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="login_enabled" id="login_enabled" <?= $LOGIN_ENABLED ? 'checked' : '' ?>><label class="form-check-label" for="login_enabled">Login Page Enabled</label></div><hr>
                <h5>User Management (Admin)</h5>
                <div id="user-list">
                    <?php foreach ($USERS as $user => $pass): ?>
                    <div class="row mb-2 user-row"><div class="col-md-5"><input type="text" name="usernames[]" class="form-control" value="<?= htmlspecialchars($user) ?>"></div><div class="col-md-5"><input type="password" name="passwords[]" class="form-control" placeholder="New password (leave blank to keep)"></div><div class="col-md-2"><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.user-row').remove()">X</button></div></div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="btn btn-secondary btn-sm mb-3" id="add-user-btn">+ Add User</button><hr>
                <h5>Email Forwarding</h5>
                <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="forward_email_enabled" id="forward_email_enabled" <?= $FORWARD_EMAIL_ENABLED ? 'checked' : '' ?>><label class="form-check-label" for="forward_email_enabled">Forward New Messages via Email</label></div>
                <div class="mb-3"><label class="form-label">Forward To Email Address</label><input type="email" name="forward_to_email" class="form-control" value="<?= htmlspecialchars($FORWARD_TO_EMAIL) ?>"></div>
                <div class="form-check"><input class="form-check-input" type="radio" name="mail_method" id="use_php_mail" value="php" <?= $USE_PHP_MAIL ? 'checked' : '' ?>><label class="form-check-label" for="use_php_mail">Use PHP mail()</label></div>
                <div class="form-check mb-3"><input class="form-check-input" type="radio" name="mail_method" id="use_smtp" value="smtp" <?= !$USE_PHP_MAIL ? 'checked' : '' ?>><label class="form-check-label" for="use_smtp">Use SMTP</label></div>
                <div id="smtp-settings" class="p-3 border rounded mb-3" style="display: <?= $USE_PHP_MAIL ? 'none' : 'block' ?>;">
                    <h6>SMTP Settings</h6>
                    <div class="row"><div class="col-md-6 mb-2"><input type="text" name="smtp_host" class="form-control" placeholder="SMTP Host" value="<?= htmlspecialchars($SMTP_HOST) ?>"></div><div class="col-md-6 mb-2"><input type="number" name="smtp_port" class="form-control" placeholder="SMTP Port" value="<?= htmlspecialchars($SMTP_PORT) ?>"></div><div class="col-md-6 mb-2"><input type="text" name="smtp_user" class="form-control" placeholder="SMTP Username" value="<?= htmlspecialchars($SMTP_USER) ?>"></div><div class="col-md-6 mb-2"><input type="password" name="smtp_pass" class="form-control" placeholder="SMTP Password" value="<?= htmlspecialchars($SMTP_PASS) ?>"></div></div>
                </div>
                <button type="submit" name="update_settings" class="btn btn-primary">Update Admin Settings</button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
</div>
<footer>
  <span>© <?= date('Y') ?> <?= $AUTHOR_NAME ?></span>
  <span class="mx-3">|</span>
  <span>📊 <strong>Stats:</strong> <?= $message_count ?> Messages</span>
</footer>
<?php endif; ?>

<script>
function showTab(tabId, el) {
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('show', 'active'));
    document.getElementById(tabId).classList.add('show', 'active');
    document.querySelectorAll('.sidebar .nav-link').forEach(l => l.classList.remove('active'));
    el.classList.add('active');
}
function toggleDarkMode() {
    const currentTheme = document.documentElement.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
    if(document.getElementById('darkModeToggle')) {
        document.getElementById('darkModeToggle').textContent = newTheme === 'dark' ? '🌙' : '☀️';
    }
}
function applyInitialTheme() {
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
    if(document.getElementById('darkModeToggle')) {
        document.getElementById('darkModeToggle').textContent = savedTheme === 'dark' ? '🌙' : '☀️';
    }
}
document.addEventListener('DOMContentLoaded', () => {
    applyInitialTheme();
    const darkModeButton = document.getElementById('darkModeToggle');
    if (darkModeButton) darkModeButton.addEventListener('click', toggleDarkMode);

    if (document.getElementById('message-list')) {
        let messageRefreshInterval = setInterval(() => {
            fetch('?fetch_messages=true')
                .then(r => r.text())
                .then(html => { if(document.getElementById('message-list')) document.getElementById('message-list').innerHTML = html || 'No messages yet.'; })
        }, 5000);
        fetch('?fetch_messages=true').then(r => r.text()).then(h => document.getElementById('message-list').innerHTML = h || 'No messages yet.');

        const urlParams = new URLSearchParams(window.location.search);
        const tab = urlParams.get('tab') || 'messages';
        const tabLink = document.querySelector(`.nav-link[onclick*="'${tab}'"]`);
        if (tabLink) tabLink.click();
        
        const addUserBtn = document.getElementById('add-user-btn');
        if (addUserBtn) {
            addUserBtn.addEventListener('click', () => {
                const userList = document.getElementById('user-list');
                const newUserRow = document.createElement('div');
                newUserRow.className = 'row mb-2 user-row';
                newUserRow.innerHTML = `<div class="col-md-5"><input type="text" name="usernames[]" class="form-control" placeholder="New Username" required></div><div class="col-md-5"><input type="password" name="passwords[]" class="form-control" placeholder="New Password" required></div><div class="col-md-2"><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.user-row').remove()">X</button></div>`;
                userList.appendChild(newUserRow);
            });
        }
        
        document.querySelectorAll('input[name="mail_method"]').forEach(radio => {
            radio.addEventListener('change', e => {
                document.getElementById('smtp-settings').style.display = e.target.value === 'smtp' ? 'block' : 'none';
            });
        });
    }
});
</script>
</body>
</html>