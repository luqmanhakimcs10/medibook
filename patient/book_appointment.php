<?php
// patient/book_appointment.php — Complete with slot bug fix
require_once '../config/db.php';
require_once '../includes/functions.php';
require_once '../includes/cities.php';
requireRole('patient');

$uid     = $_SESSION['user_id'];
$patient = getUserById($uid);
$my_city = $patient['city'] ?? '';
$error   = $success = '';

// ── Handle Booking Submission ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // doctor_id here is users.id (sent from the dropdown)
    $doctor_users_id = intval($_POST['doctor_id']         ?? 0);
    $appt_date       = sanitize($_POST['appointment_date'] ?? '');
    $time_slot       = sanitize($_POST['time_slot']        ?? '');
    $symptoms        = sanitize($_POST['symptoms']          ?? '');

    if (!$doctor_users_id || !$appt_date || !$time_slot) {
        $error = 'Please select a doctor, date, and time slot.';
    } elseif (strtotime($appt_date) < strtotime(date('Y-m-d'))) {
        $error = 'Please select a future date.';
    } else {
        // Check if this slot is already booked
        // appointments.doctor_id = users.id → use $doctor_users_id
        $check = $conn->query(
            "SELECT id FROM appointments
             WHERE doctor_id        = $doctor_users_id
               AND appointment_date = '$appt_date'
               AND time_slot        = '$time_slot'
               AND status          != 'cancelled'"
        );

        if ($check && $check->num_rows > 0) {
            $error = 'This slot was just booked by someone else. Please choose another.';
        } else {
            // Insert appointment — doctor_id stores users.id
            $conn->query(
                "INSERT INTO appointments
                    (patient_id, doctor_id, appointment_date, time_slot, symptoms, status)
                 VALUES
                    ($uid, $doctor_users_id, '$appt_date', '$time_slot', '$symptoms', 'pending')"
            );

            // Notify patient
            addNotification($uid, 'Appointment Booked!',
                "Your appointment is booked for $appt_date at $time_slot. Awaiting confirmation.");

            // Notify doctor
            addNotification($doctor_users_id, 'New Appointment Request',
                "New appointment request for $appt_date at $time_slot from a patient.");

            // Email patient
            sendEmailReminder(
                $patient['email'], $patient['name'],
                'Appointment Booked — MediBook',
                "Your appointment has been booked for <strong>$appt_date at $time_slot</strong>. The doctor will confirm shortly."
            );

            $success = "✅ Appointment booked for <strong>$appt_date at $time_slot</strong>! You will be notified when confirmed.";
        }
    }
}

// ── Filters ───────────────────────────────────────────────
$filter_city = sanitize($_GET['city'] ?? $my_city);
$filter_spec = sanitize($_GET['spec'] ?? '');

// Build doctor list with both filters
$where_parts = [
    "u.is_active = 1",
    "(d.status = 'approved' OR d.status IS NULL)"
];
if ($filter_city) $where_parts[] = "u.city = '$filter_city'";
if ($filter_spec) $where_parts[] = "d.specialization LIKE '%$filter_spec%'";
$where = implode(' AND ', $where_parts);

$filtered_res  = $conn->query(
    "SELECT d.*, u.name, u.email, u.phone, u.gender,
            u.is_active, u.id AS user_id, u.city
     FROM doctors d JOIN users u ON d.user_id = u.id
     WHERE $where
     ORDER BY u.name"
);
$filtered_doctors = $filtered_res ? $filtered_res->fetch_all(MYSQLI_ASSOC) : [];

// All doctors (city-sorted — nearby first)
$all_doctors = ($filter_city || $filter_spec) ? $filtered_doctors : getDoctorsByCity($my_city);

// Split nearby vs other for grouped dropdown
$nearby_doctors = array_filter($all_doctors, fn($d) => !empty($filter_city) && ($d['city'] ?? '') === $filter_city);
$other_doctors  = array_filter($all_doctors, fn($d) =>  empty($filter_city) || ($d['city'] ?? '') !== $filter_city);

// Specializations for filter pills
$specs_res = $conn->query("SELECT DISTINCT specialization FROM doctors WHERE status='approved' ORDER BY specialization");
$all_specs = $specs_res ? $specs_res->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment — MediBook</title>
    <!-- Dark mode MUST be in <head> to prevent white flash -->
    <script src="../assets/js/dark-mode.js"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dark-mode.css">
    <style>
        .spec-pill {
            padding: 6px 14px;
            border: 2px solid var(--border);
            border-radius: 20px;
            font-size: .8rem;
            font-weight: 600;
            color: var(--text-mid);
            cursor: pointer;
            transition: all .2s;
            background: white;
            text-decoration: none;
            display: inline-block;
        }
        .spec-pill:hover,
        .spec-pill.active {
            border-color: var(--primary);
            color: var(--primary);
            background: var(--primary-light);
        }
        [data-theme="dark"] .spec-pill {
            background: #1E293B;
            border-color: var(--border);
            color: var(--text-mid);
        }
        [data-theme="dark"] .spec-pill:hover,
        [data-theme="dark"] .spec-pill.active {
            background: var(--primary-light);
            color: var(--primary);
        }
    </style>
</head>
<body>
<div class="dashboard-wrapper">

    <?php include '../includes/sidebar_patient.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <div class="topbar-title">📅 Book Appointment</div>
            <div class="topbar-right">
                <button class="theme-toggle">🌙</button>
                <a href="my_appointments.php" class="btn btn-outline btn-sm">My Appointments</a>
            </div>
        </div>

        <div class="page-content">

            <?php if ($error):   ?><div class="alert alert-danger">⚠️ <?= $error ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

            <div style="display:grid;grid-template-columns:2fr 1fr;gap:24px">

                <!-- ── Left: Filters + Form ── -->
                <div>

                    <!-- Filter Card -->
                    <div class="card" style="margin-bottom:20px">
                        <div class="card-header"><h3>🔍 Find a Doctor</h3></div>
                        <div class="card-body">
                            <form method="GET" id="filter-form">
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px">
                                    <div>
                                        <label style="font-size:.82rem;font-weight:700;color:var(--text-mid);display:block;margin-bottom:6px">📍 City</label>
                                        <?php renderCitySelect($filter_city, 'city', false); ?>
                                    </div>
                                    <div>
                                        <label style="font-size:.82rem;font-weight:700;color:var(--text-mid);display:block;margin-bottom:6px">🩺 Specialization</label>
                                        <select name="spec" class="form-control">
                                            <option value="">All Specializations</option>
                                            <?php foreach ($all_specs as $s): ?>
                                            <option value="<?= htmlspecialchars($s['specialization']) ?>"
                                                <?= $filter_spec === $s['specialization'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($s['specialization']) ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div style="display:flex;gap:10px">
                                    <button type="submit" class="btn btn-primary">🔍 Apply Filters</button>
                                    <?php if ($filter_city || $filter_spec): ?>
                                    <a href="book_appointment.php" class="btn btn-outline">✕ Clear</a>
                                    <?php endif; ?>
                                </div>
                            </form>

                            <!-- Quick Spec Pills -->
                            <?php if (!empty($all_specs)): ?>
                            <div style="margin-top:14px;padding-top:14px;border-top:1px solid var(--border)">
                                <div style="font-size:.74rem;font-weight:700;color:var(--text-light);text-transform:uppercase;letter-spacing:.08em;margin-bottom:8px">Quick Filter</div>
                                <div style="display:flex;gap:8px;flex-wrap:wrap">
                                    <a href="book_appointment.php?city=<?= urlencode($filter_city) ?>"
                                       class="spec-pill <?= !$filter_spec ? 'active' : '' ?>">All</a>
                                    <?php foreach ($all_specs as $s): ?>
                                    <a href="?city=<?= urlencode($filter_city) ?>&spec=<?= urlencode($s['specialization']) ?>"
                                       class="spec-pill <?= $filter_spec === $s['specialization'] ? 'active' : '' ?>">
                                        <?= htmlspecialchars($s['specialization']) ?>
                                    </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if ($filter_city || $filter_spec): ?>
                            <div style="margin-top:10px;font-size:.82rem;color:var(--primary);font-weight:600">
                                Found <strong><?= count($all_doctors) ?></strong> doctor(s)
                                <?= $filter_spec ? "for <em>".htmlspecialchars($filter_spec)."</em>" : '' ?>
                                <?= $filter_city ? "in <em>".htmlspecialchars($filter_city)."</em>" : '' ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Booking Form -->
                    <div class="card">
                        <div class="card-header"><h3>✍️ Book Your Slot</h3></div>
                        <div class="card-body">
                            <form method="POST" id="bookingForm">

                                <!-- Doctor Dropdown -->
                                <div class="form-group">
                                    <label>Choose Doctor <span style="color:var(--danger)">*</span></label>
                                    <?php if (empty($all_doctors)): ?>
                                    <div class="alert alert-warning" style="margin:0">
                                        ⚠️ No doctors found. Please adjust your filters.
                                    </div>
                                    <?php else: ?>
                                    <select name="doctor_id" id="doctor_id"
                                            class="form-control" required
                                            onchange="onDoctorChange(this)">
                                        <option value="">-- Select a Doctor --</option>

                                        <?php if (!empty($nearby_doctors)): ?>
                                        <optgroup label="📍 Nearby — <?= htmlspecialchars($filter_city) ?>">
                                            <?php foreach ($nearby_doctors as $doc): ?>
                                            <option value="<?= $doc['user_id'] ?>"
                                                data-spec="<?= htmlspecialchars($doc['specialization']) ?>"
                                                data-exp="<?= $doc['experience_years'] ?>"
                                                data-fee="<?= $doc['consultation_fee'] ?>"
                                                data-city="<?= htmlspecialchars($doc['city'] ?? '') ?>"
                                                data-days="<?= htmlspecialchars($doc['available_days']) ?>"
                                                <?= isset($_POST['doctor_id']) && intval($_POST['doctor_id']) === $doc['user_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($doc['name']) ?> — <?= htmlspecialchars($doc['specialization']) ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                        <?php endif; ?>

                                        <?php
                                        $other_label = !empty($nearby_doctors) ? '🌍 Other Cities' : '👨‍⚕️ All Doctors';
                                        if (!empty($other_doctors)): ?>
                                        <optgroup label="<?= $other_label ?>">
                                            <?php foreach ($other_doctors as $doc): ?>
                                            <option value="<?= $doc['user_id'] ?>"
                                                data-spec="<?= htmlspecialchars($doc['specialization']) ?>"
                                                data-exp="<?= $doc['experience_years'] ?>"
                                                data-fee="<?= $doc['consultation_fee'] ?>"
                                                data-city="<?= htmlspecialchars($doc['city'] ?? '') ?>"
                                                data-days="<?= htmlspecialchars($doc['available_days']) ?>"
                                                <?= isset($_POST['doctor_id']) && intval($_POST['doctor_id']) === $doc['user_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($doc['name']) ?> — <?= htmlspecialchars($doc['specialization']) ?>
                                                (<?= htmlspecialchars($doc['city'] ?? 'N/A') ?>)
                                            </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                        <?php endif; ?>

                                        <?php
                                        // Fallback: if both groups are empty, show flat list
                                        if (empty($nearby_doctors) && empty($other_doctors)):
                                            foreach ($all_doctors as $doc): ?>
                                        <option value="<?= $doc['user_id'] ?>"
                                            data-spec="<?= htmlspecialchars($doc['specialization']) ?>"
                                            data-exp="<?= $doc['experience_years'] ?>"
                                            data-fee="<?= $doc['consultation_fee'] ?>"
                                            data-city="<?= htmlspecialchars($doc['city'] ?? '') ?>"
                                            data-days="<?= htmlspecialchars($doc['available_days']) ?>">
                                            <?= htmlspecialchars($doc['name']) ?> — <?= htmlspecialchars($doc['specialization']) ?>
                                            (<?= htmlspecialchars($doc['city'] ?? 'N/A') ?>)
                                        </option>
                                        <?php endforeach; endif; ?>
                                    </select>
                                    <?php endif; ?>
                                </div>

                                <!-- Doctor Info Preview -->
                                <div id="doctor-preview"
                                     style="display:none;background:var(--primary-light);border-radius:10px;padding:14px 18px;margin-bottom:18px;border:1px solid rgba(13,148,136,.2)">
                                    <div style="display:flex;gap:20px;flex-wrap:wrap;font-size:.86rem">
                                        <div>
                                            <span style="font-size:.7rem;font-weight:700;color:var(--primary);text-transform:uppercase;display:block;margin-bottom:2px">Specialization</span>
                                            <strong id="prev-spec">—</strong>
                                        </div>
                                        <div>
                                            <span style="font-size:.7rem;font-weight:700;color:var(--primary);text-transform:uppercase;display:block;margin-bottom:2px">Experience</span>
                                            <strong id="prev-exp">—</strong>
                                        </div>
                                        <div>
                                            <span style="font-size:.7rem;font-weight:700;color:var(--primary);text-transform:uppercase;display:block;margin-bottom:2px">Fee</span>
                                            <strong id="prev-fee">—</strong>
                                        </div>
                                        <div>
                                            <span style="font-size:.7rem;font-weight:700;color:var(--primary);text-transform:uppercase;display:block;margin-bottom:2px">City</span>
                                            <strong id="prev-city">—</strong>
                                        </div>
                                        <div>
                                            <span style="font-size:.7rem;font-weight:700;color:var(--primary);text-transform:uppercase;display:block;margin-bottom:2px">Available Days</span>
                                            <strong id="prev-days">—</strong>
                                        </div>
                                    </div>
                                </div>

                                <!-- Date Picker -->
                                <div class="form-group">
                                    <label>Appointment Date <span style="color:var(--danger)">*</span></label>
                                    <input type="date"
                                           name="appointment_date"
                                           id="appointment_date"
                                           class="form-control"
                                           required
                                           min="<?= date('Y-m-d') ?>"
                                           onchange="onDateChange()"
                                           value="<?= htmlspecialchars($_POST['appointment_date'] ?? '') ?>">
                                </div>

                                <!-- Time Slots -->
                                <div class="form-group">
                                    <label>Available Time Slots <span style="color:var(--danger)">*</span></label>
                                    <div id="slots-container">
                                        <p style="color:var(--text-light);font-size:.88rem">
                                            ← Select a doctor and date to see available slots
                                        </p>
                                    </div>
                                    <input type="hidden" name="time_slot" id="selected_slot" required>
                                </div>

                                <!-- Symptoms -->
                                <div class="form-group">
                                    <label>Symptoms / Reason
                                        <span style="font-weight:400;color:var(--text-light);font-size:.78rem">(optional)</span>
                                    </label>
                                    <textarea name="symptoms" class="form-control" rows="3"
                                        placeholder="Briefly describe your symptoms or reason for visit..."><?= htmlspecialchars($_POST['symptoms'] ?? '') ?></textarea>
                                </div>

                                <button type="submit" class="btn btn-primary btn-lg"
                                        style="width:100%;justify-content:center">
                                    📅 Confirm Appointment
                                </button>
                            </form>
                        </div>
                    </div>

                </div><!-- /left -->

                <!-- ── Right: Info Panel ── -->
                <div style="display:flex;flex-direction:column;gap:16px">

                    <!-- Your city card -->
                    <?php if ($my_city): ?>
                    <div class="card" style="background:var(--primary-light);border-color:rgba(13,148,136,.25)">
                        <div class="card-body">
                            <div style="font-size:1.3rem;margin-bottom:6px">📍</div>
                            <div style="font-weight:700;color:var(--primary);margin-bottom:4px">Your City</div>
                            <div style="font-weight:600;font-size:.95rem"><?= htmlspecialchars($my_city) ?></div>
                            <?php
                            $city_count = count(array_filter($all_doctors, fn($d) => ($d['city'] ?? '') === $my_city));
                            ?>
                            <div style="font-size:.8rem;color:var(--primary-dark);margin-top:6px">
                                <?= $city_count ?> doctor<?= $city_count !== 1 ? 's' : '' ?> available nearby
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="card" style="background:var(--accent-light);border-color:rgba(245,158,11,.3)">
                        <div class="card-body">
                            <div style="font-size:1.2rem;margin-bottom:6px">📍</div>
                            <p style="font-size:.85rem;color:#92400E">
                                Your city is not set.
                                <a href="profile.php" style="font-weight:700;color:#92400E">Update profile</a>
                                to see nearby doctors first.
                            </p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- How to book -->
                    <div class="card">
                        <div class="card-header"><h3>📌 How to Book</h3></div>
                        <div class="card-body">
                            <ol style="padding-left:18px;color:var(--text-mid);font-size:.87rem;display:flex;flex-direction:column;gap:10px">
                                <li>Filter doctors by city or specialization</li>
                                <li>Select a doctor from the list</li>
                                <li>Pick an available date</li>
                                <li>Click on a time slot</li>
                                <li>Add symptoms and confirm</li>
                            </ol>
                        </div>
                    </div>

                    <!-- Reminder -->
                    <div class="card" style="background:var(--accent-light);border-color:rgba(245,158,11,.3)">
                        <div class="card-body">
                            <div style="font-size:1.3rem;margin-bottom:6px">⚠️</div>
                            <p style="font-size:.85rem;color:#92400E">
                                Please arrive <strong>10 minutes early</strong>
                                and bring any previous reports or prescriptions.
                            </p>
                        </div>
                    </div>

                </div><!-- /right -->
            </div>
        </div><!-- /page-content -->
    </div><!-- /main-content -->
</div>

<script src="../assets/js/main.js"></script>
<script>
// ── Doctor dropdown change ──────────────────────────────
function onDoctorChange(select) {
    const opt     = select.options[select.selectedIndex];
    const preview = document.getElementById('doctor-preview');

    if (select.value) {
        document.getElementById('prev-spec').textContent  = opt.dataset.spec  || '—';
        document.getElementById('prev-exp').textContent   = (opt.dataset.exp  || '0') + ' Yrs';
        document.getElementById('prev-fee').textContent   = 'Rs ' + parseFloat(opt.dataset.fee || 0).toLocaleString();
        document.getElementById('prev-city').textContent  = opt.dataset.city  || '—';
        document.getElementById('prev-days').textContent  = opt.dataset.days  || '—';
        preview.style.display = 'block';
    } else {
        preview.style.display = 'none';
    }

    // Reset slots when doctor changes
    document.getElementById('slots-container').innerHTML =
        '<p style="color:var(--text-light);font-size:.88rem">← Now select a date to see available slots</p>';
    document.getElementById('selected_slot').value = '';
}

// ── Date change → load slots ────────────────────────────
function onDateChange() {
    const docId = document.getElementById('doctor_id').value;
    const date  = document.getElementById('appointment_date').value;
    if (docId && date) {
        loadSlots(docId, date);
    }
}

// ── Auto-trigger if page reloaded with values ───────────
document.addEventListener('DOMContentLoaded', () => {
    const sel  = document.getElementById('doctor_id');
    const date = document.getElementById('appointment_date');
    if (sel && sel.value)  onDoctorChange(sel);
    if (sel && sel.value && date && date.value) loadSlots(sel.value, date.value);
});
</script>
</body>
</html>