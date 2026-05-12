<!doctype html>
<html lang="he" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>כניסה</title>
  <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
  <main class="container">
    <form class="card" onsubmit="login(event)">
      <h2>כניסה למערכת</h2>
      <input id="username" placeholder="שם משתמש" required>
      <input id="password" type="password" placeholder="סיסמה" required>
      <button>כניסה</button>
      <p id="error" class="error"></p>
    </form>
  </main>
  <script>
    async function login(e) {
      e.preventDefault();
      const res = await fetch('/api/auth.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({username: username.value, password: password.value})
      });
      if (!res.ok) { error.textContent = 'שם משתמש או סיסמה שגויים'; return; }
      location.href = '/dashboard.php';
    }
  </script>
</body>
</html>
