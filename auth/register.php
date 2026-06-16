<?php
// auth/register.php — UPDATED: city field added
require_once '../config/db.php';
require_once '../includes/functions.php';
require_once '../includes/cities.php';   // ← shared city list

if (isLoggedIn()) {
    header("Location: " . SITE_URL . "/{$_SESSION['role']}/dashboard.php");
    exit();
}

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = sanitize($_POST['name']    ?? '');
    $email   = sanitize($_POST['email']   ?? '');
    $phone   = sanitize($_POST['phone']   ?? '');
    $gender  = sanitize($_POST['gender']  ?? '');
    $dob     = sanitize($_POST['dob']     ?? '');
    $city    = sanitize($_POST['city']    ?? '');   // ← NEW
    $address = sanitize($_POST['address'] ?? '');
    $pass    = $_POST['password']          ?? '';
    $pass2   = $_POST['confirm_password']  ?? '';

    if (empty($name) || empty($email) || empty($pass) || empty($city)) {
        $error = 'Name, email, city and password are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($pass) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($pass !== $pass2) {
        $error = 'Passwords do not match.';
    } else {
        $em = $conn->real_escape_string($email);
        if ($conn->query("SELECT id FROM users WHERE email='$em'")->num_rows > 0) {
            $error = 'An account with this email already exists.';
        } else {
            $hashed  = password_hash($pass, PASSWORD_DEFAULT);
            $dob_val = !empty($dob) ? "'$dob'" : 'NULL';
            $conn->query(
                "INSERT INTO users (name, email, password, role, phone, gender, date_of_birth, city, address)
                 VALUES ('$name', '$em', '$hashed', 'patient', '$phone', '$gender', $dob_val, '$city', '$address')"
            );
            $new_id = $conn->insert_id;
            addNotification($new_id, 'Welcome to MediBook!', 'Your account has been created successfully. Book your first appointment today!');
            $success = 'Account created! <a href="login.php" style="color:var(--primary);font-weight:600">Click here to login →</a>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — MediBook</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="auth-wrapper">

    <!-- Left Panel -->
    <div class="auth-left">
        <div style="position:relative;z-index:1">
            <div style="font-size:3.5rem;margin-bottom:20px">🩺</div>
            <h2>Join MediBook Today</h2>
            <p style="margin-top:10px">Create your free patient account and find top doctors near you.</p>
            <div style="margin-top:36px">
                <?php foreach ([
                    'Free to register',
                    'Find doctors in your city',
                    'Book unlimited appointments',
                    'Digital prescriptions',
                    'SMS & email reminders',
                ] as $perk): ?>
                <div style="display:flex;align-items:center;gap:10px;color:rgba(255,255,255,.9);font-size:.9rem;margin-bottom:12px">
                    <span style="width:22px;height:22px;background:rgba(255,255,255,.2);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.7rem;flex-shrink:0">✓</span>
                    <?= $perk ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Right Panel -->
    <div class="auth-right" style="align-items:flex-start;overflow-y:auto;padding:40px 60px">
        <div class="auth-box" style="padding:20px 0">
            <a href="../index.php" style="display:flex;align-items:center;gap:8px;margin-bottom:26px;color:var(--text-mid);font-size:.9rem">← Back to Home</a>
            <h2>Create Account</h2>
            <p style="margin-bottom:24px">Register as a patient — quick and free.</p>

            <?php if ($error):   ?><div class="alert alert-danger">⚠️ <?= $error ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success">✅ <?= $success ?></div><?php endif; ?>

            <?php if (!$success): ?>
            <form method="POST">
                <!-- Name + Phone -->
                <div class="form-row">
                    <div class="form-group">
                        <label>Full Name <span style="color:var(--danger)">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="Ali Hassan" required
                               value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="text" name="phone" class="form-control" placeholder="03XXXXXXXXX"
                               value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                    </div>
                </div>

                <!-- Email -->
                <div class="form-group">
                    <label>Email Address <span style="color:var(--danger)">*</span></label>
                    <input type="email" name="email" class="form-control" placeholder="you@example.com" required
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>

                <!-- Gender + DOB -->
                <div class="form-row">
                    <div class="form-group">
                        <label>Gender</label>
                        <select name="gender" class="form-control">
                            <option value="">Select</option>
                            <?php foreach (['Male','Female','Other'] as $g): ?>
                            <option value="<?= $g ?>" <?= ($_POST['gender'] ?? '') === $g ? 'selected' : '' ?>><?= $g ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Date of Birth</label>
                        <input type="date" name="dob" class="form-control" value="<?= htmlspecialchars($_POST['dob'] ?? '') ?>">
                    </div>
                </div>

                <!-- City (NEW) -->
                <div class="form-group">
                    <label>
                        City <span style="color:var(--danger)">*</span>
                        <span style="font-weight:400;color:var(--text-light);font-size:.78rem">— used to find nearby doctors</span>
                    </label>
                    <?php renderCitySelect($_POST['city'] ?? '', 'city', true); ?>
                </div>

                <!-- Address -->
                <div class="form-group">
                    <label>Street Address <span style="font-weight:400;color:var(--text-light);font-size:.78rem">(optional)</span></label>
                    <input type="text" name="address" class="form-control" placeholder="House #, Street, Area"
                           value="<?= htmlspecialchars($_POST['address'] ?? '') ?>">
                </div>

                <!-- Passwords -->
                <div class="form-row">
                    <div class="form-group">
                        <label>Password <span style="color:var(--danger)">*</span></label>
                        <input type="password" name="password" class="form-control" placeholder="Min. 6 characters" required>
                    </div>
                    <div class="form-group">
                        <label>Confirm Password <span style="color:var(--danger)">*</span></label>
                        <input type="password" name="confirm_password" class="form-control" placeholder="Repeat password" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:13px;margin-top:4px">
                    Create My Account →
                </button>
            </form>
            <?php endif; ?>

            <div style="text-align:center;margin-top:20px;font-size:.9rem;color:var(--text-mid)">
                Already have an account? <a href="login.php" style="color:var(--primary);font-weight:600">Sign In</a>
            </div>
        </div>
    </div>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>