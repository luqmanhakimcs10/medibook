<?php
// auth/login.php — FIXED: clean single password check, no duplicates
require_once '../config/db.php';
require_once '../includes/functions.php';

if (isLoggedIn()) {
    header("Location: " . SITE_URL . "/{$_SESSION['role']}/dashboard.php");
    exit();
}

$error = $info = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = sanitize($_POST['role'] ?? 'patient');

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $em     = $conn->real_escape_string($email);
        $result = $conn->query("SELECT * FROM users WHERE email='$em' AND role='$role'");

        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // ── Password check: supports hashed AND plain text ──
            $pass_ok = password_verify($password, $user['password'])
                       || $password === $user['password'];

            if (!$pass_ok) {
                $error = 'Incorrect password. Please try again.';

            } elseif ($role === 'doctor') {
                // Check doctor approval status
                $doc_res = $conn->query("SELECT status, rejection_reason FROM doctors WHERE user_id={$user['id']}");
                $doc     = $doc_res ? $doc_res->fetch_assoc() : null;

                if (!$doc) {
                    $error = 'Doctor profile not found. Contact support.';

                } elseif ($doc['status'] === 'pending') {
                    $info = '⏳ Your application is <strong>under review</strong>. Our admin team is verifying your credentials. You will be notified once approved.';

                } elseif ($doc['status'] === 'rejected') {
                    $reason = htmlspecialchars($doc['rejection_reason'] ?? 'No reason provided.');
                    $info   = "❌ Your application was <strong>rejected</strong>.<br>Reason: <em>$reason</em><br>Please contact support if you believe this is an error.";

                } elseif (!$user['is_active']) {
                    $error = 'Your account has been deactivated. Contact admin.';

                } else {
                    // Approved doctor — log in
                    $_SESSION['user_id']   = $user['id'];
                    $_SESSION['name']      = $user['name'];
                    $_SESSION['email']     = $user['email'];
                    $_SESSION['role']      = $user['role'];
                    $_SESSION['logged_in'] = true;
                    header("Location: " . SITE_URL . "/doctor/dashboard.php");
                    exit();
                }

            } elseif (!$user['is_active']) {
                $error = 'Your account has been deactivated. Contact admin.';

            } else {
                // Patient or Admin — log in normally
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['name']      = $user['name'];
                $_SESSION['email']     = $user['email'];
                $_SESSION['role']      = $user['role'];
                $_SESSION['logged_in'] = true;
                header("Location: " . SITE_URL . "/{$user['role']}/dashboard.php");
                exit();
            }

        } else {
            $error = 'No account found with this email and role.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — MediBook</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="auth-wrapper">

    <!-- Left Panel -->
    <div class="auth-left">
        <div style="position:relative;z-index:1">
            <div style="font-size:3.5rem;margin-bottom:20px">🏥</div>
            <h2>Welcome Back to MediBook</h2>
            <p style="margin-top:10px">Your health, simplified. Access your appointments, prescriptions, and doctor updates all in one place.</p>
            <div style="margin-top:36px;display:flex;flex-direction:column;gap:14px">
                <?php
                $features = [
                    ['📅', 'Book Appointments',    'Pick your slot instantly'],
                    ['💊', 'Digital Prescriptions', 'View & download anytime'],
                    ['🔔', 'Smart Reminders',       'Never miss a visit'],
                ];
                foreach ($features as $f): ?>
                <div style="display:flex;align-items:center;gap:14px;background:rgba(255,255,255,0.1);padding:14px 18px;border-radius:12px">
                    <span style="font-size:1.3rem"><?= $f[0] ?></span>
                    <div>
                        <div style="color:white;font-weight:600;font-size:.88rem"><?= $f[1] ?></div>
                        <div style="color:rgba(255,255,255,0.6);font-size:.78rem"><?= $f[2] ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Right Panel -->
    <div class="auth-right">
        <div class="auth-box">
            <a href="../index.php" style="display:flex;align-items:center;gap:6px;margin-bottom:28px;color:var(--text-mid);font-size:.88rem">
                ← Back to Home
            </a>
            <h2>Sign In</h2>
            <p style="margin-bottom:24px">Access your MediBook account.</p>

            <?php if ($error): ?>
            <div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($info): ?>
            <div style="padding:16px 18px;background:#FEF3C7;border:1px solid #FDE68A;border-radius:8px;font-size:.9rem;color:#92400E;margin-bottom:20px;line-height:1.6">
                <?= $info ?>
            </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Login As</label>
                    <select name="role" class="form-control">
                        <option value="patient" <?= (($_POST['role'] ?? '') === 'patient') ? 'selected' : '' ?>>🧑 Patient</option>
                        <option value="doctor"  <?= (($_POST['role'] ?? '') === 'doctor')  ? 'selected' : '' ?>>👨‍⚕️ Doctor</option>
                        <option value="admin"   <?= (($_POST['role'] ?? '') === 'admin')   ? 'selected' : '' ?>>🔑 Admin</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" class="form-control"
                           placeholder="you@example.com" required
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control"
                           placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn btn-primary"
                        style="width:100%;justify-content:center;padding:13px">
                    Sign In →
                </button>
            </form>

            <div style="margin-top:24px;font-size:.85rem;color:var(--text-mid);text-align:center">
                New patient?
                <a href="register.php" style="color:var(--primary);font-weight:600">Register</a>
                &nbsp;·&nbsp;
                Doctor?
                <a href="register_doctor.php" style="color:var(--primary);font-weight:600">Apply Here</a>
            </div>
        </div>
    </div>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>