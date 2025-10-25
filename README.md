# 🔐 PHP OTP Receiver

A simple **one-file PHP app** for receiving and displaying **OTP codes** sent from other services or applications via **HTTP GET requests**.

Perfect for developers who are testing apps, SMS gateways, or plugins (like WordPress *Digits*) that send one-time passwords.

---

## ✨ Features

- 🧩 Lightweight – only one PHP file.
- 🌐 Receive OTP via **HTTP GET**.
- 🕒 Timestamped OTP list (optional auto-refresh).
- 📱 Works with any service: Twilio, MSG91, Firebase, etc.
- 🧰 Can be integrated with other PHP apps, APIs, or local testing.
- 🔒 Optional key-based access restriction.
- 🌓 Optional dark/light mode UI (if you enable frontend).

---

## ⚙️ How It Works

You send an HTTP request to your hosted file:

```
GET https://yourdomain.com/otp.php?source=twilio&code=123456
```

The app will:
1. Receive the `code` and `source`
2. Save or display them in the web interface
3. Optionally log them in a file or database (if enabled)

---

## 🚀 Quick Setup

### 1️⃣ Upload the file
Copy `otp.php` (and optional `config.php`) to your web server’s public folder.

### 2️⃣ Test it
Visit:
```
https://yourdomain.com/otp.php?source=testapp&code=999999
```

You should see your OTP displayed on the page.

---

## 🔑 Optional Configuration

Create a file named `config.php` (same directory):

```php
<?php
// Optional secret key (disable if not needed)
$otp_secret_key = "my-secret-key";

// Logging settings
$enable_logging = true;
$log_file = "otp_log.txt";
?>
```

Then call:
```
https://yourdomain.com/otp.php?key=my-secret-key&source=twilio&code=123456
```

---

## 💾 Optional Logging

When `$enable_logging = true;`, OTPs will be saved to `otp_log.txt` like:

```
[2025-10-25 20:13:22] Source: Twilio | OTP: 123456
```

---

## 🧩 Integration Examples

### ✅ From Python:
```python
import requests
requests.get("https://yourdomain.com/otp.php", params={"source": "test", "code": "654321"})
```

### ✅ From PHP:
```php
file_get_contents("https://yourdomain.com/otp.php?source=api&code=789012");
```

### ✅ From WordPress (Digits plugin hook)
Use your SMS gateway’s “Custom HTTP API” and set the endpoint to your `otp.php`.

---

## 🌈 Optional UI Features

You can enhance it with:
- `assets/style.css` — gradient background and clean OTP cards
- Auto-refresh every 10 seconds (`assets/script.js`)
- Copy OTP button
- Dark mode toggle

---

## 🧰 Example Minimal UI (optional)

```php
<?php
if (isset($_GET['code'])) {
  $source = $_GET['source'] ?? 'unknown';
  $code = $_GET['code'];
  echo "<div class='otp-box'><b>$source</b>: <span>$code</span></div>";
} else {
  echo "<p>Waiting for OTPs...</p>";
}
?>
```

---

## 🧑‍💻 Author

**Mohamad AlJasem**  
Doctor • Software & AI Developer • Clinical Informatics Researcher  
🌐 [aljasem.eu.org](https://aljasem.eu.org)

---

## 🪪 License

Licensed under the **MIT License** — free for personal or commercial use with attribution.

---

## 🌟 Contributing

Pull requests are welcome!  
Ideas for enhancement:
- [ ] SQLite or file-based OTP history  
- [ ] JSON API endpoint  
- [ ] WebSocket live updates  
- [ ] Authentication & dashboard mode
