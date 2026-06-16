<?php
// auth/register_doctor.php — UPDATED: city field added
require_once '../config/db.php';
require_once '../includes/functions.php';
require_once '../includes/cities.php';   // ← shared city list

if (isLoggedIn()) {
    header("Location: " . SITE_URL . "/{$_SESSION['role']}/dashboard.php");
    exit();
}

$error   = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_application'])) {

    // Personal Info
    $name   = sanitize($_POST['name']    ?? '');
    $email  = sanitize($_POST['email']   ?? '');
    $phone  = sanitize($_POST['phone']   ?? '');
    $gender = sanitize($_POST['gender']  ?? '');
    $city   = sanitize($_POST['city']    ?? '');   // ← NEW
    $pass   = $_POST['password']          ?? '';
    $pass2  = $_POST['confirm_password']  ?? '';

    // Professional Info
    $spec   = sanitize($_POST['specialization']   ?? '');
    $qual   = sanitize($_POST['qualification']     ?? '');
    $exp    = intval($_POST['experience_years']   ?? 0);
    $fee    = floatval($_POST['consultation_fee'] ?? 0);
    $bio    = sanitize($_POST['bio']              ?? '');
    $days   = isset($_POST['days'])
              ? implode(',', array_map('sanitize', $_POST['days']))
              : 'Mon,Tue,Wed,Thu,Fri';

    // Validate
    if (!$name || !$email || !$pass || !$spec || !$qual || !$city) {
        $error = 'Please fill in all required fields, including your city.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address.';
    } elseif (strlen($pass) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($pass !== $pass2) {
        $error = 'Passwords do not match.';
    } elseif (empty($_FILES['degree_certificate']['name'])) {
        $error = 'Degree certificate is required.';
    } elseif (empty($_FILES['pmdc_registration']['name'])) {
        $error = 'PMDC registration document is required.';
    } else {
        $em = $conn->real_escape_string($email);
        if ($conn->query("SELECT id FROM users WHERE email='$em'")->num_rows > 0) {
            $error = 'An account with this email already exists.';
        } else {
            $hashed = password_hash($pass, PASSWORD_DEFAULT);
            $conn->query(
                "INSERT INTO users (name, email, password, role, phone, gender, city, is_active)
                 VALUES ('$name', '$em', '$hashed', 'doctor', '$phone', '$gender', '$city', 0)"
            );
            $user_id = $conn->insert_id;

            $conn->query(
                "INSERT INTO doctors (user_id, specialization, qualification, experience_years,
                                      consultation_fee, bio, available_days, status)
                 VALUES ($user_id, '$spec', '$qual', $exp, $fee, '$bio', '$days', 'pending')"
            );
            $doc_id = $conn->insert_id;

            // Upload documents
            $upload_dir  = '../uploads/doctor_docs/';
            $allowed_ext = ['pdf','jpg','jpeg','png'];
            $max_size    = 5 * 1024 * 1024;
            $doc_fields  = [
                'degree_certificate' => 'degree_certificate',
                'pmdc_registration'  => 'pmdc_registration',
                'experience_letter'  => 'experience_letter',
                'cnic'               => 'cnic',
            ];

            foreach ($doc_fields as $field => $doc_type) {
                if (!empty($_FILES[$field]['name']) && $_FILES[$field]['error'] === 0) {
                    $orig = $_FILES[$field]['name'];
                    $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
                    if (!in_array($ext, $allowed_ext)) { $error = "Invalid file type for $field."; break; }
                    if ($_FILES[$field]['size'] > $max_size) { $error = "File $orig is too large (max 5MB)."; break; }
                    $safe = $doc_type . '_' . $user_id . '_' . time() . '.' . $ext;
                    if (move_uploaded_file($_FILES[$field]['tmp_name'], $upload_dir . $safe)) {
                        $fn = $conn->real_escape_string($orig);
                        $fp = $conn->real_escape_string('uploads/doctor_docs/' . $safe);
                        $dt = $conn->real_escape_string($doc_type);
                        $conn->query("INSERT INTO doctor_documents (doctor_id, doc_type, file_name, file_path)
                                      VALUES ($doc_id, '$dt', '$fn', '$fp')");
                    }
                }
            }

            if (!$error) {
                $admin = $conn->query("SELECT id FROM users WHERE role='admin' LIMIT 1")->fetch_assoc();
                if ($admin) addNotification($admin['id'], 'New Doctor Application', "Dr. $name from $city has applied to join MediBook.");
                $success = true;
            } else {
                $conn->query("DELETE FROM users WHERE id=$user_id");
                $conn->query("DELETE FROM doctors WHERE id=$doc_id");
            }
        }
    }
}

$all_days = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
$posted_days = $_POST['days'] ?? ['Mon','Tue','Wed','Thu','Fri'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Registration — MediBook</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .doc-upload-box {
            border:2px dashed var(--border); border-radius:var(--radius-md);
            padding:20px; text-align:center; cursor:pointer; background:var(--bg);
            transition:all .2s; position:relative;
        }
        .doc-upload-box:hover { border-color:var(--primary); background:var(--primary-light); }
        .doc-upload-box input[type="file"] { position:absolute; inset:0; opacity:0; cursor:pointer; width:100%; height:100%; }
        .doc-upload-box .upload-icon { font-size:1.8rem; margin-bottom:6px; }
        .doc-upload-box .upload-label { font-size:.85rem; font-weight:600; color:var(--text-mid); }
        .doc-upload-box .upload-sub   { font-size:.75rem; color:var(--text-light); margin-top:3px; }
    </style>
</head>
<body style="background:var(--bg);padding:0;min-height:100vh">

<nav class="navbar" style="position:relative;border-bottom:1px solid var(--border)">
    <a href="../index.php" class="navbar-brand">
        <div class="nav-logo-icon">🏥</div> Medi<span>Book</span>
    </a>
    <div class="nav-links">
        <a href="login.php" class="btn btn-outline" style="padding:8px 18px">Login</a>
        <a href="register.php" class="btn btn-primary" style="padding:8px 18px">Patient Register</a>
    </div>
</nav>

<div style="max-width:860px;margin:40px auto;padding:0 20px 60px">

    <?php if ($success): ?>
    <div style="text-align:center;padding:60px 40px;background:white;border-radius:var(--radius-lg);border:1px solid var(--border);max-width:520px;margin:0 auto">
        <div style="font-size:4rem;margin-bottom:16px">🎉</div>
        <h2 style="margin-bottom:12px;color:var(--primary)">Application Submitted!</h2>
        <p style="color:var(--text-mid);line-height:1.7;margin-bottom:24px">
            Our admin team will review your credentials. You'll be notified once your account is approved.
        </p>
        <div style="background:var(--bg);border-radius:var(--radius-sm);padding:14px;margin-bottom:20px;font-size:.88rem;color:var(--text-mid)">
            ⏱️ Average review time: <strong>1–2 business days</strong>
        </div>
        <a href="login.php" class="btn btn-primary btn-lg" style="width:100%;justify-content:center">Go to Login</a>
    </div>

    <?php else: ?>

    <div style="text-align:center;margin-bottom:32px">
        <div style="display:inline-flex;align-items:center;gap:8px;background:var(--primary-light);color:var(--primary);padding:6px 16px;border-radius:50px;font-size:.82rem;font-weight:700;margin-bottom:14px">
            👨‍⚕️ Doctor Registration
        </div>
        <h1 style="font-size:1.8rem;margin-bottom:8px">Join MediBook as a Doctor</h1>
        <p style="color:var(--text-mid)">Fill in your details and upload your credentials. Our team will verify and approve your account.</p>
    </div>

    <?php if ($error): ?><div class="alert alert-danger">⚠️ <?= $error ?></div><?php endif; ?>

    <form method="POST" enctype="multipart/form-data">

        <!-- ── SECTION 1: Personal Info ── -->
        <div class="card" style="margin-bottom:20px">
            <div class="card-header"><h3>👤 Personal Information</h3></div>
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group">
                        <label>Full Name <span style="color:var(--danger)">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="Dr. Full Name" required
                               value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Email Address <span style="color:var(--danger)">*</span></label>
                        <input type="email" name="email" class="form-control" placeholder="your@email.com" required
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="text" name="phone" class="form-control" placeholder="03XXXXXXXXX"
                               value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Gender</label>
                        <select name="gender" class="form-control">
                            <option value="">Select</option>
                            <?php foreach (['Male','Female','Other'] as $g): ?>
                            <option value="<?= $g ?>" <?= ($_POST['gender'] ?? '') === $g ? 'selected' : '' ?>><?= $g ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- City (NEW) -->
                <div class="form-group">
                    <label>
                        Clinic / Practice City <span style="color:var(--danger)">*</span>
                        <span style="font-weight:400;color:var(--text-light);font-size:.78rem">— patients will use this to find you</span>
                    </label>
                    <?php renderCitySelect($_POST['city'] ?? '', 'city', true); ?>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Password <span style="color:var(--danger)">*</span> <span style="font-weight:400;color:var(--text-light);font-size:.78rem">(min. 8 chars)</span></label>
                        <input type="password" name="password" class="form-control" placeholder="Create a strong password" required minlength="8" id="pwd_field">
                        <div style="margin-top:6px">
                            <div id="pwd-strength-bar" style="height:4px;border-radius:2px;background:var(--border);transition:all .3s"></div>
                            <div id="pwd-strength-label" style="font-size:.72rem;color:var(--text-light);margin-top:3px"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Confirm Password <span style="color:var(--danger)">*</span></label>
                        <input type="password" name="confirm_password" class="form-control" placeholder="Repeat your password" required id="pwd_confirm">
                        <div id="pwd-match-msg" style="font-size:.75rem;margin-top:4px"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── SECTION 2: Professional Info ── -->
        <div class="card" style="margin-bottom:20px">
            <div class="card-header"><h3>🏥 Professional Information</h3></div>
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group">
                        <label>Specialization <span style="color:var(--danger)">*</span></label>
                        <input type="text" name="specialization" class="form-control" placeholder="e.g. Cardiologist" required
                               value="<?= htmlspecialchars($_POST['specialization'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Qualification <span style="color:var(--danger)">*</span></label>
                        <input type="text" name="qualification" class="form-control" placeholder="e.g. MBBS, FCPS" required
                               value="<?= htmlspecialchars($_POST['qualification'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Years of Experience</label>
                        <input type="number" name="experience_years" class="form-control" min="0" max="60"
                               value="<?= intval($_POST['experience_years'] ?? 0) ?>">
                    </div>
                    <div class="form-group">
                        <label>Consultation Fee (Rs)</label>
                        <input type="number" name="consultation_fee" class="form-control" min="0" step="50"
                               value="<?= floatval($_POST['consultation_fee'] ?? 1000) ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Available Days</label>
                    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:6px">
                        <?php foreach ($all_days as $day): ?>
                        <label style="padding:7px 13px;border:2px solid var(--border);border-radius:8px;font-weight:600;font-size:.82rem;cursor:pointer;transition:all .2s" id="lbl-<?= $day ?>">
                            <input type="checkbox" name="days[]" value="<?= $day ?>"
                                   <?= in_array($day, $posted_days) ? 'checked' : '' ?>
                                   onchange="styleLabel('<?= $day ?>',this.checked)" style="display:none">
                            <?= $day ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="form-group">
                    <label>Bio / About Yourself</label>
                    <textarea name="bio" class="form-control" rows="3"
                        placeholder="Briefly describe your expertise and patient approach..."><?= htmlspecialchars($_POST['bio'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- ── SECTION 3: Documents ── -->
        <div class="card" style="margin-bottom:24px">
            <div class="card-header">
                <h3>📄 Upload Documents</h3>
                <span style="font-size:.82rem;color:var(--text-light)">PDF, JPG, PNG — max 5MB each</span>
            </div>
            <div class="card-body">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                    <?php
                    $uploads = [
                        ['degree_certificate','box-degree','label-degree','🎓','Degree Certificate',true],
                        ['pmdc_registration', 'box-pmdc',  'label-pmdc',  '🏥','PMDC Registration', true],
                        ['experience_letter', 'box-exp',   'label-exp',   '📋','Experience Letter',  false],
                        ['cnic',              'box-cnic',  'label-cnic',  '🪪','CNIC / National ID', false],
                    ];
                    foreach ($uploads as [$fname, $boxId, $labelId, $icon, $label, $req]):
                    ?>
                    <div>
                        <label style="font-size:.85rem;font-weight:600;margin-bottom:8px;display:block">
                            <?= $label ?> <?= $req ? '<span style="color:var(--danger)">*</span>' : '<span style="font-weight:400;color:var(--text-light)">(optional)</span>' ?>
                        </label>
                        <div class="doc-upload-box" id="<?= $boxId ?>">
                            <input type="file" name="<?= $fname ?>" accept=".pdf,.jpg,.jpeg,.png"
                                   onchange="showFileName(this,'<?= $boxId ?>','<?= $labelId ?>')">
                            <div class="upload-icon"><?= $icon ?></div>
                            <div class="upload-label"><?= $label ?></div>
                            <div class="upload-sub" id="<?= $labelId ?>">Click to upload (PDF/Image)</div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div style="margin-top:18px;padding:12px 16px;background:var(--primary-light);border-radius:var(--radius-sm);border-left:3px solid var(--primary);font-size:.83rem;color:var(--primary-dark)">
                    🔒 Documents are only visible to our admin team for verification purposes.
                </div>
            </div>
        </div>

        <div style="text-align:center">
            <button type="submit" name="submit_application" class="btn btn-primary btn-lg" style="padding:16px 48px;font-size:1rem">
                🚀 Submit Application
            </button>
            <p style="margin-top:14px;font-size:.85rem;color:var(--text-light)">
                Already registered? <a href="login.php" style="color:var(--primary);font-weight:600">Sign In</a>
                &nbsp;|&nbsp; Patient? <a href="register.php" style="color:var(--primary);font-weight:600">Patient Register</a>
            </p>
        </div>
    </form>
    <?php endif; ?>
</div>

<script src="../assets/js/main.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('input[name="days[]"]').forEach(cb => styleLabel(cb.value, cb.checked));
});
function styleLabel(day, checked) {
    const lbl = document.getElementById('lbl-' + day);
    if (!lbl) return;
    lbl.style.borderColor = checked ? 'var(--primary)' : 'var(--border)';
    lbl.style.background  = checked ? 'var(--primary-light)' : 'white';
    lbl.style.color       = checked ? 'var(--primary)' : 'var(--text-mid)';
}
function showFileName(input, boxId, labelId) {
    if (input.files && input.files[0]) {
        const size = (input.files[0].size / 1024).toFixed(0);
        const lbl  = document.getElementById(labelId);
        const box  = document.getElementById(boxId);
        lbl.textContent   = '✅ ' + input.files[0].name + ' (' + size + ' KB)';
        lbl.style.color   = 'var(--success)';
        box.style.borderColor = 'var(--success)';
        box.style.background  = '#D1FAE5';
    }
}
document.getElementById('pwd_field').addEventListener('input', function() {
    const v = this.value;
    let s = 0;
    if (v.length >= 8)          s++;
    if (/[A-Z]/.test(v))        s++;
    if (/[0-9]/.test(v))        s++;
    if (/[^A-Za-z0-9]/.test(v)) s++;
    const cols = ['','#EF4444','#F59E0B','#10B981','#0D9488'];
    const labs = ['','Weak','Fair','Good','Strong'];
    const wids = ['0%','25%','50%','75%','100%'];
    document.getElementById('pwd-strength-bar').style.cssText   = `width:${wids[s]};background:${cols[s]};height:4px;border-radius:2px;transition:all .3s`;
    document.getElementById('pwd-strength-label').textContent   = labs[s] || '';
    document.getElementById('pwd-strength-label').style.color   = cols[s] || '';
});
document.getElementById('pwd_confirm').addEventListener('input', function() {
    const msg = document.getElementById('pwd-match-msg');
    const pw1 = document.getElementById('pwd_field').value;
    if (!this.value) { msg.textContent = ''; return; }
    msg.textContent = this.value === pw1 ? '✅ Passwords match' : '✗ Do not match';
    msg.style.color = this.value === pw1 ? 'var(--success)' : 'var(--danger)';
});
</script>
</body>
</html>