<?php
require __DIR__ . '/php/auth.php';
$message = '';
$type = 'error';
$messages = ['logout' => 'You have logged out successfully.', 'timeout' => 'Your session expired due to inactivity. Please log in again.', 'unauthorized' => 'Please log in to access the dashboard.'];
$key = is_string($_GET['message'] ?? null) ? $_GET['message'] : '';
$message = $messages[$key] ?? '';
if ($key === 'logout') $type = 'success';
try {
    if (currentUser()) redirectDashboard();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = is_string($_POST['email'] ?? null) ? strtolower(trim($_POST['email'])) : '';
        $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
        if (!validAuthToken()) {
            http_response_code(403);
            $message = 'Your form expired. Please try again.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
            $message = 'Enter a valid email address and password.';
        } else {
            $statement = authDb()->prepare('SELECT user_id,fullname,role,password_hash FROM users WHERE email=?');
            $statement->bind_param('s', $email);
            $statement->execute();
            $user = $statement->get_result()->fetch_assoc();
            if (!$user || !password_verify($password, $user['password_hash'])) {
                $message = 'Invalid email or password.';
            } else {
                revokeRemember();
                startLogin($user);
                if (($_POST['remember'] ?? '') === 'on') issueRemember((int) $user['user_id']);
                redirectDashboard();
            }
        }
    }
} catch (Throwable $e) {
    error_log('StudentHub login: ' . $e->getMessage());
    http_response_code(503);
    $message = 'Sign-in is temporarily unavailable. Please try again later.';
}
?>
<!DOCTYPE html>

<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">


<title>StudentHub | Login</title>

<link rel="stylesheet" href="css/style.css">
    <script src="js/script.js" defer></script>
</head>

<body>


<!-- ================= HEADER ================= -->

<header>
    <h1>StudentHub</h1>

    <div class="header-controls">
        <button type="button" id="theme-toggle" aria-pressed="false" hidden>Dark mode</button>
        <button type="button" id="menu-toggle" aria-controls="main-navigation" aria-expanded="false" hidden>Menu</button>
    </div>
    <nav id="main-navigation" aria-label="Main navigation">
        <ul>
            <li><a href="home.html">Home</a></li>
            <li><a href="about.html">About</a></li>
            <li><a href="login.php" class="active">Login</a></li>
            <li><a href="register.html">Register</a></li>
            <li><a href="dashboard-router.php">Dashboard</a></li>
            <li><a href="events.html">Events</a></li>
            <li><a href="profile.html">Profile</a></li>
            <li><a href="faq.html">FAQ</a></li>
            <li><a href="feedback.html">Feedback</a></li>
        <li><a href="contact.html">Contact</a></li>
        </ul>
    </nav>
</header>


<!-- ================= MAIN ================= -->

<main class="login-main">

    <section class="login-layout">

        <!-- Left information panel -->

        <div class="login-intro">

            <span class="login-label">
                STUDENT PORTAL
            </span>

            <h2>
                Welcome<br>
                <span>back.</span>
            </h2>

            <p>
                Sign in to continue to your StudentHub and keep
                track of your academic journey in one place.
            </p>

            <div class="login-highlights">

                <div class="login-highlight">
                    <span>01</span>
                    <div>
                        <strong>Stay Updated</strong>
                        <small>Check important notices</small>
                    </div>
                </div>

                <div class="login-highlight">
                    <span>02</span>
                    <div>
                        <strong>Stay Organized</strong>
                        <small>Access your daily schedule</small>
                    </div>
                </div>

                <div class="login-highlight">
                    <span>03</span>
                    <div>
                        <strong>Keep Learning</strong>
                        <small>Find useful study resources</small>
                    </div>
                </div>

            </div>

        </div>


        <!-- Login Card -->

        <div class="login-card">

            <div class="login-card-heading">

                <div class="login-card-icon">
                    SH
                </div>

                <div>
                    <span>ACCOUNT ACCESS</span>
                    <h3>Sign in</h3>
                </div>

            </div>


            <p class="login-card-text">
                Enter your details to access your student portal.
            </p>


            <?php if ($message !== ''): ?>
            <div class="form-status form-status-<?= escapeHtml($type) ?>" role="status"><?= escapeHtml($message) ?></div>
            <?php endif; ?>
            <form action="login.php" method="post" class="stacked-form">
                <input type="hidden" name="csrf_token" value="<?= escapeHtml(authToken()) ?>">

                <div class="form-group">

                    <label for="email">
                        Email Address
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email" autocomplete="username" maxlength="150"
                        placeholder="you@example.com"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="password">
                        Password
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password" autocomplete="current-password" maxlength="72"
                        placeholder="Enter your password"
                        required
                    >

                </div>


                <div class="login-options">

                    <label class="remember-option">
                        <input type="checkbox" name="remember">
                        <span>Remember me</span>
                    </label>



                </div>


                <button type="submit" class="login-button">
                    Login to StudentHub →
                </button>

            </form>


            <div class="login-divider">
                <span>NEW TO STUDENTHUB?</span>
            </div>


            <a href="register.html" class="register-link">
                Create a student account
            </a>

        </div>

    </section>

</main>


<!-- ================= FOOTER ================= -->

<footer>

    <div class="footer-content">

        <div>
            <h3>StudentHub</h3>

            <p>
                Your digital campus companion.
            </p>
        </div>

        <div>
            <p>
                &copy; 2026 StudentHub Portal.
                All rights reserved.
            </p>
        </div>

    </div>

</footer>

</body>
</html>
