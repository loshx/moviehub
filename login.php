<?php
require __DIR__ . '/config/db.php';

$error = '';
$mode = $_POST['mode'] ?? 'login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($mode === 'register') {
        if ($email === '' && $phone === '') {
            $error = 'Email or phone is required.';
        } elseif ($password === '' || strlen($password) < 4) {
            $error = 'Password must be at least 4 characters.';
        } else {
            $name = trim($_POST['name'] ?? '');
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare('INSERT INTO users (name, email, phone, password_hash) VALUES (:n, :e, :p, :ph)');
            try {
                $stmt->execute([
                    ':n' => $name !== '' ? $name : 'User',
                    ':e' => $email !== '' ? $email : null,
                    ':p' => $phone !== '' ? $phone : null,
                    ':ph' => $hash
                ]);
                $_SESSION['user_id'] = (int)$pdo->lastInsertId();
                header('Location: index.php');
                exit;
            } catch (PDOException $e) {
                $error = 'User already exists with this email.';
            }
        }
    } else { // login
        if ($email === '' && $phone === '') {
            $error = 'Enter email or phone.';
        } else {
            if ($email !== '') {
                $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :e');
                $stmt->execute([':e' => $email]);
            } else {
                $stmt = $pdo->prepare('SELECT * FROM users WHERE phone = :p');
                $stmt->execute([':p' => $phone]);
            }
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user && $user['password_hash'] && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = (int)$user['id'];
                header('Location: index.php');
                exit;
            } else {
                $error = 'Invalid credentials.';
            }
        }
    }
}

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sign in – MovieHub</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="page auth-page">
<main class="auth-container">
    <div class="auth-card">
        <h1>MovieHub</h1>
        <p class="subtitle">Sign in or create an account.</p>

        <?php if ($error): ?>
            <div class="auth-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="post" class="auth-form">
            <input type="hidden" name="mode" id="modeField" value="<?php echo htmlspecialchars($mode); ?>">

            <div class="auth-toggle">
                <button type="button" class="auth-toggle-btn <?php echo $mode === 'login' ? 'active' : ''; ?>" data-mode="login">Sign in</button>
                <button type="button" class="auth-toggle-btn <?php echo $mode === 'register' ? 'active' : ''; ?>" data-mode="register">Register</button>
            </div>

            <div class="auth-field auth-name" <?php echo $mode === 'register' ? '' : 'style="display:none"'; ?>>
                <label>Name</label>
                <input type="text" name="name" placeholder="Your name">
            </div>

            <div class="auth-field">
                <label>Email (Gmail) or phone</label>
                <input type="email" name="email" placeholder="you@gmail.com">
                <span class="or-separator">or</span>
                <input type="text" name="phone" placeholder="Phone number">
            </div>

            <div class="auth-field">
                <label>Password</label>
                <input type="password" name="password" placeholder="Password">
            </div>

            <button class="btn-primary auth-submit">
                <?php echo $mode === 'register' ? 'Create account' : 'Sign in'; ?>
            </button>
        </form>
    </div>
</main>

<script>
// toggle login/register on client side
const toggleButtons = document.querySelectorAll('.auth-toggle-btn');
const modeField = document.getElementById('modeField');
const nameField = document.querySelector('.auth-name');
const submitBtn = document.querySelector('.auth-submit');

toggleButtons.forEach(btn => {
    btn.addEventListener('click', () => {
        toggleButtons.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        const mode = btn.dataset.mode;
        modeField.value = mode;
        if (mode === 'register') {
            nameField.style.display = '';
            submitBtn.textContent = 'Create account';
        } else {
            nameField.style.display = 'none';
            submitBtn.textContent = 'Sign in';
        }
    });
});
</script>
</body>
</html>
