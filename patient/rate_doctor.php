<?php
// patient/rate_doctor.php — Complete rating submission page
require_once '../config/db.php';
require_once '../includes/functions.php';
requireRole('patient');

$uid     = $_SESSION['user_id'];
$appt_id = intval($_GET['appt'] ?? 0);
$error   = $success = '';

if (!$appt_id) { header("Location: my_appointments.php"); exit(); }

// Verify this appointment belongs to this patient and is completed
$appt = $conn->query(
    "SELECT a.*,u.name as doctor_name,d.specialization,d.qualification
     FROM appointments a
     JOIN users u   ON a.doctor_id = u.id
     JOIN doctors d ON d.user_id   = u.id
     WHERE a.id=$appt_id AND a.patient_id=$uid AND a.status='completed'"
)->fetch_assoc();

if (!$appt) { header("Location: my_appointments.php"); exit(); }

// Check if already rated
$existing = $conn->query("SELECT * FROM doctor_ratings WHERE appointment_id=$appt_id")->fetch_assoc();

if ($_SERVER['REQUEST_METHOD']==='POST' && !$existing) {
    $rating = intval($_POST['rating'] ?? 0);
    $review = sanitize($_POST['review'] ?? '');

    if ($rating < 1 || $rating > 5) {
        $error = 'Please select a rating between 1 and 5 stars.';
    } else {
        $doctor_id = intval($appt['doctor_id']);
        $conn->query(
            "INSERT INTO doctor_ratings (doctor_id,patient_id,appointment_id,rating,review)
             VALUES ($doctor_id,$uid,$appt_id,$rating,'$review')"
        );
        // Notify doctor
        addNotification($doctor_id, 'New Patient Review!',
            "A patient rated your appointment on {$appt['appointment_date']}. Rating: $rating/5.");
        $success  = 'Thank you for your feedback! Your review helps other patients.';
        $existing = $conn->query("SELECT * FROM doctor_ratings WHERE appointment_id=$appt_id")->fetch_assoc();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Rate Doctor — MediBook</title>
<link rel="stylesheet" href="../assets/css/style.css">
<style>
.star-group { display:flex; gap:6px; flex-direction:row-reverse; justify-content:flex-end; margin-top:8px; }
.star-group input { display:none; }
.star-group label {
    font-size:2.4rem;
    cursor:pointer;
    color:var(--border);
    transition:color .15s;
    line-height:1;
}
.star-group input:checked ~ label,
.star-group label:hover,
.star-group label:hover ~ label { color:var(--accent); }
</style>
</head>
<body>
<div class="dashboard-wrapper">
<?php include '../includes/sidebar_patient.php'; ?>
<div class="main-content">
<div class="topbar">
    <div class="topbar-title">⭐ Rate Your Doctor</div>
    <div class="topbar-right">
        <button class="theme-toggle">🌙</button>
        <a href="my_appointments.php" class="btn btn-outline btn-sm">← My Appointments</a>
    </div>
</div>
<div class="page-content" style="max-width:620px">
    <?php if($error):   ?><div class="alert alert-danger">⚠️ <?= $error ?></div><?php endif; ?>
    <?php if($success): ?><div class="alert alert-success">✅ <?= $success ?></div><?php endif; ?>

    <!-- Doctor Info Card -->
    <div class="card" style="margin-bottom:20px">
        <div class="card-body" style="display:flex;align-items:center;gap:18px">
            <div style="width:60px;height:60px;background:var(--info);color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.5rem;font-weight:700;font-family:'Sora',sans-serif;flex-shrink:0">
                <?= strtoupper(substr($appt['doctor_name'],3,1)) ?>
            </div>
            <div>
                <div style="font-weight:700;font-size:1rem"><?= htmlspecialchars($appt['doctor_name']) ?></div>
                <div style="color:var(--primary);font-size:.85rem;font-weight:600"><?= htmlspecialchars($appt['specialization']) ?></div>
                <div style="font-size:.8rem;color:var(--text-light);margin-top:2px">
                    Appointment: <?= formatDate($appt['appointment_date']) ?> at <?= htmlspecialchars($appt['time_slot']) ?>
                </div>
            </div>
        </div>
    </div>

    <?php if ($existing): ?>
    <!-- Already Rated View -->
    <div class="card">
        <div class="card-body" style="text-align:center;padding:40px">
            <div style="font-size:2.5rem;margin-bottom:12px;color:var(--accent)">
                <?= str_repeat('★',$existing['rating']) ?><?= str_repeat('☆',5-$existing['rating']) ?>
            </div>
            <h3 style="margin-bottom:8px">You already rated this appointment</h3>
            <p style="color:var(--text-mid)">Your rating: <strong><?= $existing['rating'] ?>/5</strong></p>
            <?php if($existing['review']): ?>
            <div style="margin-top:16px;padding:16px;background:var(--bg);border-radius:10px;font-size:.9rem;color:var(--text-mid);font-style:italic;border-left:3px solid var(--primary)">
                "<?= htmlspecialchars($existing['review']) ?>"
            </div>
            <?php endif; ?>
            <div style="margin-top:20px">
                <a href="my_appointments.php" class="btn btn-primary">← Back to Appointments</a>
            </div>
        </div>
    </div>

    <?php else: ?>
    <!-- Rating Form -->
    <div class="card">
        <div class="card-header"><h3>Share Your Experience</h3></div>
        <div class="card-body">
            <form method="POST">
                <div class="form-group">
                    <label>Your Rating <span style="color:var(--danger)">*</span></label>
                    <div class="star-group" id="star-group">
                        <?php for($i=5;$i>=1;$i--): ?>
                        <input type="radio" name="rating" id="star<?= $i ?>" value="<?= $i ?>" required>
                        <label for="star<?= $i ?>" title="<?= $i ?> star<?= $i>1?'s':'' ?>">★</label>
                        <?php endfor; ?>
                    </div>
                    <div id="rating-label" style="font-size:.85rem;color:var(--text-light);margin-top:8px">Click a star to rate</div>
                </div>

                <div class="form-group">
                    <label>Write a Review <span style="font-weight:400;color:var(--text-light)">(optional)</span></label>
                    <textarea name="review" class="form-control" rows="4"
                        placeholder="Share your experience with this doctor. Was the consultation helpful? Would you recommend them?"></textarea>
                </div>

                <!-- Rating guide -->
                <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:8px;margin-bottom:20px;font-size:.75rem;text-align:center">
                    <?php foreach([1=>'Terrible',2=>'Poor',3=>'Average',4=>'Good',5=>'Excellent'] as $s=>$l): ?>
                    <div style="padding:6px;background:var(--bg);border-radius:8px;color:var(--text-light)">
                        <div style="font-size:1.1rem;color:var(--accent)"><?= str_repeat('★',$s) ?></div>
                        <div><?= $l ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <button type="submit" class="btn btn-primary btn-lg" style="width:100%;justify-content:center">
                    ⭐ Submit Rating
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>
</div>
</div>
<script src="../assets/js/main.js"></script>
<script>
const labels = {1:'Terrible 😟',2:'Poor 😕',3:'Average 😐',4:'Good 😊',5:'Excellent 🤩'};
document.querySelectorAll('.star-group input').forEach(input => {
    input.addEventListener('change', () => {
        const lbl = document.getElementById('rating-label');
        lbl.textContent = labels[input.value] + ' (' + input.value + '/5)';
        lbl.style.color = 'var(--accent)';
        lbl.style.fontWeight = '700';
    });
});
</script>
</body>
</html>