<?php
session_start();

// === CONFIG ===
$CONFIG_FILE = __DIR__ . "/otp_config.php";
$LOG_FILE = __DIR__ . "/otp_log.txt";
$PROJECT_NAME = "🌟 OTP Galaxy: Digits Dev Gateway";
$AUTHOR_NAME = "Mohamad AlJasem";
$AUTHOR_SITE = "https://aljasem.eu.org";

// === Load credentials ===
if (!file_exists($CONFIG_FILE)) {
    file_put_contents($CONFIG_FILE, "<?php
\$USERNAME = 'admin';
\$PASSWORD = '1234';
\$GA_ENABLED = false;
");
}
include $CONFIG_FILE;

// === Default for GA toggle ===
if (!isset($GA_ENABLED)) $GA_ENABLED = false;

// === Handle login ===
if (isset($_POST['login'])) {
    if ($_POST['username'] === $USERNAME && $_POST['password'] === $PASSWORD) {
        $_SESSION['logged_in'] = true;
    } else {
        $error = "❌ Invalid credentials.";
    }
}

// === Handle logout ===
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}

// === Handle OTP logging ===
if (isset($_REQUEST['to'], $_REQUEST['otp']) && !empty($_SESSION['logged_in'])) {
    $to        = htmlspecialchars($_REQUEST['to']);
    $message   = htmlspecialchars($_REQUEST['message'] ?? '');
    $sender_id = htmlspecialchars($_REQUEST['sender_id'] ?? '');
    $otp       = htmlspecialchars($_REQUEST['otp']);
    $entry     = date("Y-m-d H:i:s") . " | 📞 $to | 🔢 $otp | 📨 $sender_id | 💬 $message" . PHP_EOL;
    file_put_contents($LOG_FILE, $entry, FILE_APPEND | LOCK_EX);
}

// === Handle credential and GA update ===
if (isset($_POST['update_credentials']) && !empty($_SESSION['logged_in'])) {
    $new_user = $_POST['new_username'];
    $new_pass = $_POST['new_password'];
    $ga_status = isset($_POST['ga_enabled']) ? 'true' : 'false';
    file_put_contents($CONFIG_FILE, "<?php
\$USERNAME = '" . addslashes($new_user) . "';
\$PASSWORD = '" . addslashes($new_pass) . "';
\$GA_ENABLED = $ga_status;
");
    $success = "✅ Settings updated. Please log in again.";
    session_destroy();
    header("Refresh:2; url=" . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}

// === Read log file ===
$entries = file_exists($LOG_FILE) ? file($LOG_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
$entries = array_reverse($entries);
$latest  = $entries[0] ?? null;
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<title><?= $PROJECT_NAME ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
:root {
  --gradient-bg: linear-gradient(90deg, #00c6ff 0%, #0072ff 100%);
  --sidebar-bg: linear-gradient(180deg, #00c6ff 0%, #00ff88 100%);
  --text-light: #fff;
  --text-dark: #212529;
}
body {
  background-color: #f8f9fa;
  transition: background 0.3s, color 0.3s;
}
.navbar {
  background: var(--gradient-bg);
}
.sidebar {
  min-height: 100vh;
  background: var(--sidebar-bg);
}
.sidebar .nav-link {
  color: #fff;
  font-weight: bold;
  margin-bottom: 5px;
}
.sidebar .nav-link.active {
  background-color: rgba(255,255,255,0.3);
  color: #fff !important;
}
.card-highlight {
  border-left: 5px solid #198754;
}
footer {
  background-color: #e9ecef;
  padding: 10px;
  text-align: center;
  position: fixed;
  bottom: 0;
  width: 100%;
  color: #000;
  font-weight: 500;
}
.dark-mode body {
  background-color: #121212;
  color: #f1f1f1;
}
.dark-mode .navbar, .dark-mode .sidebar {
  background: linear-gradient(90deg, #003e7e, #00897b);
}
.dark-mode footer {
  background: #1e1e1e;
  color: #ddd;
}
#darkModeToggle {
  border: none;
  background: transparent;
  color: #fff;
  font-size: 1.3rem;
  cursor: pointer;
}
.login-container {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100vh;
}
.login-card {
  width: 380px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
</style>

<script>
function showTab(tabId, el) {
    document.querySelectorAll('.tab-pane').forEach(el => el.classList.remove('show', 'active'));
    document.getElementById(tabId).classList.add('show', 'active');
    document.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));
    el.classList.add('active');
}
function toggleDarkMode() {
    const html = document.documentElement;
    const theme = html.getAttribute('data-theme');
    html.setAttribute('data-theme', theme === 'dark' ? 'light' : 'dark');
    localStorage.setItem('theme', html.getAttribute('data-theme'));
}
window.onload = () => {
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) document.documentElement.setAttribute('data-theme', savedTheme);
    const firstTab = document.querySelector('[data-tab="messages"]');
    if (firstTab) firstTab.click();
};
</script>

<?php if (!empty($GA_ENABLED)): ?>
<!-- 🔹 Google Analytics Placeholder -->
<script>
// Google Analytics Tracking Placeholder
console.log("Google Analytics Enabled - insert your GA tracking code here.");
</script>
<?php endif; ?>

</head>
<body>
<?php if (!isset($_SESSION['logged_in'])): ?>
<div class="login-container">
  <div class="login-card card">
    <div class="card-body text-center">
      <img src="https://cdn-icons-png.flaticon.com/512/906/906175.png" width="60" class="mb-3" alt="Logo">
      <h4 class="card-title mb-3"><?= $PROJECT_NAME ?></h4>
      <p class="text-muted mb-4">by <?= $AUTHOR_NAME ?></p>
      <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
      <?php endif; ?>
      <form method="post">
        <div class="mb-3">
          <label class="form-label">👤 Username</label>
          <input type="text" name="username" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">🔑 Password</label>
          <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" name="login" class="btn btn-success w-100">🚀 Login</button>
      </form>
    </div>
  </div>
</div>
<footer>
  © <?= date('Y') ?> <?= $AUTHOR_NAME ?> — <a href="<?= $AUTHOR_SITE ?>" target="_blank"><?= parse_url($AUTHOR_SITE, PHP_URL_HOST) ?></a>
</footer>

<?php else: ?>
<nav class="navbar navbar-dark">
  <div class="container-fluid d-flex justify-content-between align-items-center">
    <span class="navbar-brand"><?= $PROJECT_NAME ?></span>
    <div class="d-flex align-items-center gap-3">
      <!-- GitHub Stars Placeholder -->
      <a href="#" class="text-white text-decoration-none">
        ⭐ <span id="githubStars">123</span> Stars
      </a>
      <button id="darkModeToggle" onclick="toggleDarkMode()">🌓</button>
      <a href="?logout=1" class="btn btn-warning btn-sm">🔓 Logout</a>
    </div>
  </div>
</nav>

<div class="container-fluid">
  <div class="row">
    <div class="col-md-2 sidebar p-3">
      <nav class="nav flex-column">
        <a class="nav-link" href="javascript:void(0)" data-tab="messages" onclick="showTab('messages', this)">📨 Messages</a>
        <a class="nav-link" href="javascript:void(0)" data-tab="about" onclick="showTab('about', this)">ℹ️ About</a>
        <a class="nav-link" href="javascript:void(0)" data-tab="docs" onclick="showTab('docs', this)">📚 Documentation</a>
        <a class="nav-link" href="javascript:void(0)" data-tab="settings" onclick="showTab('settings', this)">⚙️ Settings</a>
      </nav>
    </div>
    <div class="col-md-10 p-4">
      <div class="tab-content">
        <div class="tab-pane fade" id="messages">
          <?php if ($latest): 
              preg_match('/📞 (.*?) \| 🔢 (.*?) \|/', $latest, $matches);
              $phone = $matches[1] ?? '';
              $otp   = $matches[2] ?? '';
          ?>
          <div class="card mb-4 card-highlight">
            <div class="card-body">
              <h5 class="card-title">🌟 Latest OTP</h5>
              <p><strong>📞 Phone:</strong> <?= $phone ?></p>
              <p><strong>🔢 OTP:</strong> <span class="text-danger fs-4"><?= $otp ?></span></p>
            </div>
          </div>
          <?php endif; ?>
          <h5>📜 All Messages</h5>
          <?php foreach ($entries as $line): ?>
            <div class="card mb-2">
              <div class="card-body p-2">
                <small><?= htmlspecialchars($line) ?></small>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="tab-pane fade" id="about">
          <h4>ℹ️ About</h4>
          <p><strong>OTP Galaxy</strong> is a custom OTP gateway endpoint for the <strong>Digits WordPress plugin</strong>. It logs OTP messages instead of sending them, making it ideal for testing, development, and debugging.</p>
        </div>

        <div class="tab-pane fade" id="docs">
          <h4>📚 Documentation</h4>
          <ol>
            <li>Install the <strong>Digits plugin</strong> on your WordPress site.</li>
            <li>Go to Digits settings → SMS Gateway → Custom Gateway.</li>
            <li>Set the API URL to: <code>https://yourdomain.com/otp-galaxy.php</code></li>
            <li>Use variables: <code>{to}</code>, <code>{message}</code>, <code>{sender_id}</code>, <code>{otp}</code>.</li>
          </ol>
        </div>

        <div class="tab-pane fade" id="settings">
          <h4>⚙️ Settings</h4>
          <?php if (isset($success)): ?>
              <div class="alert alert-success"><?= $success ?></div>
          <?php endif; ?>
          <form method="post">
            <div class="mb-3">
              <label class="form-label">New Username</label>
              <input type="text" name="new_username" class="form-control" required value="<?= htmlspecialchars($USERNAME) ?>">
            </div>
            <div class="mb-3">
              <label class="form-label">New Password</label>
              <input type="password" name="new_password" class="form-control" required>
            </div>
            <div class="form-check mb-3">
              <input class="form-check-input" type="checkbox" name="ga_enabled" id="ga_enabled" <?= $GA_ENABLED ? 'checked' : '' ?>>
              <label class="form-check-label" for="ga_enabled">Enable Google Analytics</label>
            </div>
            <button type="submit" name="update_credentials" class="btn btn-primary">Update & Save</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<footer>
  © <?= date('Y') ?> <?= $AUTHOR_NAME ?> — <a href="<?= $AUTHOR_SITE ?>" target="_blank"><?= parse_url($AUTHOR_SITE, PHP_URL_HOST) ?></a>
</footer>
<?php endif; ?>
</body>
</html>
