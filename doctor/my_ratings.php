<?php
require_once '../config/db.php';
require_once '../includes/functions.php';
requireRole('doctor');
$uid = $_SESSION['user_id'];

// Average rating
$avg_res = $conn->query("SELECT ROUND(AVG(rating),1) as avg, COUNT(*) as total FROM doctor_ratings WHERE doctor_id=$uid");
$avg_data = $avg_res ? $avg_res->fetch_assoc() : ['avg'=>0,'total'=>0];

// Rating breakdown (5 star, 4 star etc)
$breakdown = [];
for ($s = 5; $s >= 1; $s--) {
    $r = $conn->query("SELECT COUNT(*) as c FROM doctor_ratings WHERE doctor_id=$uid AND rating=$s")->fetch_assoc();
    $breakdown[$s] = intval($r['c']);
}

// All reviews
$reviews = $conn->query("SELECT r.*,u.name as patient_name,a.appointment_date,a.time_slot FROM doctor_ratings r JOIN users u ON r.patient_id=u.id JOIN appointments a ON r.appointment_id=a.id WHERE r.doctor_id=$uid ORDER BY r.created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>My Reviews — MediBook</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="dashboard-wrapper">
<?php include '../includes/sidebar_doctor.php'; ?>
<div class="main-content">
<div class="topbar">
    <div class="topbar-title">⭐ My Reviews & Ratings</div>
    <div class="topbar-right"><button class="theme-toggle">🌙</button></div>
</div>
<div class="page-content">

    <!-- Summary Card -->
    <div style="display:grid;grid-template-columns:1fr 2fr;gap:24px;margin-bottom:24px">
        <div class="card" style="text-align:center;padding:36px 24px">
            <div style="font-size:4rem;font-weight:800;font-family:'Sora',sans-serif;color:var(--primary);line-height:1"><?= $avg_data['avg'] ?: '—' ?></div>
            <div style="font-size:1.6rem;color:var(--accent);margin:8px 0">
                <?php
                $avg = floatval($avg_data['avg']);
                echo str_repeat('★', round($avg)) . str_repeat('☆', 5-round($avg));
                ?>
            </div>
            <div style="font-size:.9rem;color:var(--text-mid)">Based on <strong><?= $avg_data['total'] ?></strong> review<?= $avg_data['total']!=1?'s':'' ?></div>
        </div>
        <div class="card">
            <div class="card-header"><h3>Rating Breakdown</h3></div>
            <div class="card-body">
                <?php foreach ($breakdown as $star => $count):
                    $pct = $avg_data['total'] > 0 ? round(($count/$avg_data['total'])*100) : 0;
                ?>
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px">
                    <span style="font-size:.9rem;font-weight:700;min-width:16px;color:var(--text-dark)"><?= $star ?></span>
                    <span style="color:var(--accent);font-size:.9rem">★</span>
                    <div style="flex:1;height:10px;background:var(--border);border-radius:5px;overflow:hidden">
                        <div style="width:<?= $pct ?>%;height:100%;background:var(--accent);border-radius:5px;transition:width .8s ease"></div>
                    </div>
                    <span style="font-size:.85rem;color:var(--text-light);min-width:30px;text-align:right"><?= $count ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- All Reviews -->
    <div class="card">
        <div class="card-header"><h3>Patient Reviews</h3><span style="font-size:.85rem;color:var(--text-light)"><?= $reviews ? $reviews->num_rows : 0 ?> reviews</span></div>
        <?php if($reviews && $reviews->num_rows > 0): ?>
            <?php while($r=$reviews->fetch_assoc()): ?>
            <div style="padding:22px 24px;border-bottom:1px solid var(--border)">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:10px">
                    <div style="display:flex;align-items:center;gap:12px">
                        <div style="width:42px;height:42px;background:var(--primary);color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.95rem;flex-shrink:0">
                            <?= strtoupper(substr($r['patient_name'],0,1)) ?>
                        </div>
                        <div>
                            <div style="font-weight:700;font-size:.92rem"><?= htmlspecialchars($r['patient_name']) ?></div>
                            <div style="font-size:.78rem;color:var(--text-light)">
                                Appointment: <?= formatDate($r['appointment_date']) ?> · <?= htmlspecialchars($r['time_slot']) ?>
                            </div>
                        </div>
                    </div>
                    <div style="text-align:right;flex-shrink:0">
                        <div style="color:var(--accent);font-size:1rem"><?= str_repeat('★',$r['rating']) ?><?= str_repeat('☆',5-$r['rating']) ?></div>
                        <div style="font-size:.75rem;color:var(--text-light);margin-top:2px"><?= date('d M Y',strtotime($r['created_at'])) ?></div>
                    </div>
                </div>
                <?php if($r['review']): ?>
                <div style="background:var(--bg);border-radius:10px;padding:14px 16px;font-size:.88rem;color:var(--text-mid);line-height:1.7;font-style:italic;border-left:3px solid var(--primary)">
                    "<?= htmlspecialchars($r['review']) ?>"
                </div>
                <?php else: ?>
                <div style="font-size:.82rem;color:var(--text-light)">No written review — rating only.</div>
                <?php endif; ?>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
        <div class="empty-state"><div class="icon">⭐</div><p>No reviews yet. Ratings appear here after patients rate their appointments.</p></div>
        <?php endif; ?>
    </div>
</div>
</div>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>