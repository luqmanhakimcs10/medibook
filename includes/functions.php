<?php
// MediBook — includes/functions.php
// SLOT BUG FIX: getAvailableSlots now correctly converts users.id → doctors.id
// before querying time_slots table

function sanitize($data) {
    global $conn;
    return htmlspecialchars(strip_tags(trim($conn->real_escape_string($data))));
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: " . SITE_URL . "/auth/login.php");
        exit();
    }
}

function requireRole($role) {
    requireLogin();
    if ($_SESSION['role'] !== $role) {
        header("Location: " . SITE_URL . "/index.php");
        exit();
    }
}

function getUserById($id) {
    global $conn;
    $id     = intval($id);
    $result = $conn->query("SELECT * FROM users WHERE id = $id");
    return $result ? $result->fetch_assoc() : null;
}

function getDoctorInfo($user_id) {
    global $conn;
    $user_id = intval($user_id);
    $result  = $conn->query(
        "SELECT d.*, u.name, u.email, u.phone, u.gender, u.profile_image, u.city
         FROM doctors d
         JOIN users u ON d.user_id = u.id
         WHERE d.user_id = $user_id"
    );
    return $result ? $result->fetch_assoc() : null;
}

function getAllDoctors() {
    global $conn;
    $result = $conn->query(
        "SELECT d.*, u.name, u.email, u.phone, u.gender,
                u.is_active, u.id AS user_id, u.city
         FROM doctors d
         JOIN users u ON d.user_id = u.id
         WHERE u.is_active = 1
           AND (d.status = 'approved' OR d.status IS NULL)
         ORDER BY u.name"
    );
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function getDoctorsByCity($city = '') {
    global $conn;
    $city = sanitize($city);

    if (empty($city)) {
        return getAllDoctors();
    }

    $same = $conn->query(
        "SELECT d.*, u.name, u.email, u.phone, u.gender,
                u.is_active, u.id AS user_id, u.city
         FROM doctors d JOIN users u ON d.user_id = u.id
         WHERE u.is_active = 1
           AND (d.status = 'approved' OR d.status IS NULL)
           AND u.city = '$city'
         ORDER BY u.name"
    );

    $others = $conn->query(
        "SELECT d.*, u.name, u.email, u.phone, u.gender,
                u.is_active, u.id AS user_id, u.city
         FROM doctors d JOIN users u ON d.user_id = u.id
         WHERE u.is_active = 1
           AND (d.status = 'approved' OR d.status IS NULL)
           AND (u.city != '$city' OR u.city IS NULL)
         ORDER BY u.name"
    );

    $result = [];
    if ($same)   $result = array_merge($result, $same->fetch_all(MYSQLI_ASSOC));
    if ($others) $result = array_merge($result, $others->fetch_all(MYSQLI_ASSOC));
    return $result;
}

// ============================================================
//  getAvailableSlots — FIXED
//
//  THE BUG EXPLAINED:
//  - time_slots.doctor_id  = doctors.id  (e.g. Sara = 3)
//  - appointments.doctor_id = users.id   (e.g. Sara = 4)
//  - booking form sends users.id as doctor_id
//
//  OLD (broken): WHERE time_slots.doctor_id = users.id
//                → finds nothing for Sara (4 ≠ 3)
//
//  FIXED: first look up doctors.id using users.id,
//         then query time_slots with the correct doctors.id
// ============================================================
function getAvailableSlots($users_id, $date) {
    global $conn;

    $users_id = intval($users_id);
    $date     = sanitize($date);

    // Step 1: Convert users.id → doctors.id
    $doc_res = $conn->query(
        "SELECT id FROM doctors WHERE user_id = $users_id LIMIT 1"
    );

    if (!$doc_res || $doc_res->num_rows === 0) {
        // No doctor profile found for this user
        return [];
    }

    $doctors_id = intval($doc_res->fetch_assoc()['id']);

    // Step 2: Get all ACTIVE slots using doctors.id (correct column)
    $slots_res = $conn->query(
        "SELECT slot_time
         FROM time_slots
         WHERE doctor_id = $doctors_id
           AND is_active  = 1
         ORDER BY STR_TO_DATE(slot_time, '%h:%i %p')"
    );

    if (!$slots_res || $slots_res->num_rows === 0) {
        return [];
    }

    $all_slots = $slots_res->fetch_all(MYSQLI_ASSOC);

    // Step 3: Get already-booked slots for this date
    //         appointments.doctor_id = users.id  → use $users_id here
    $booked_res = $conn->query(
        "SELECT time_slot
         FROM appointments
         WHERE doctor_id       = $users_id
           AND appointment_date = '$date'
           AND status          != 'cancelled'"
    );

    $booked = [];
    if ($booked_res) {
        $booked = array_column(
            $booked_res->fetch_all(MYSQLI_ASSOC),
            'time_slot'
        );
    }

    // Step 4: Return slots that are NOT already booked
    $available = [];
    foreach ($all_slots as $slot) {
        if (!in_array($slot['slot_time'], $booked)) {
            $available[] = $slot['slot_time'];
        }
    }

    return $available;
}

function getUnreadNotifications($user_id) {
    global $conn;
    $user_id = intval($user_id);
    $result  = $conn->query(
        "SELECT COUNT(*) as count FROM notifications WHERE user_id = $user_id AND is_read = 0"
    );
    return $result ? intval($result->fetch_assoc()['count']) : 0;
}

function addNotification($user_id, $title, $message) {
    global $conn;
    $user_id = intval($user_id);
    $title   = $conn->real_escape_string($title);
    $message = $conn->real_escape_string($message);
    $conn->query(
        "INSERT INTO notifications (user_id, title, message)
         VALUES ($user_id, '$title', '$message')"
    );
}

function sendEmailReminder($to_email, $to_name, $subject, $message) {
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8\r\n";
    $headers .= "From: MediBook <noreply@medibook.com>\r\n";
    $body = "
    <html><body style='font-family:Arial;padding:20px;background:#f5f5f5'>
    <div style='background:white;padding:30px;border-radius:10px;max-width:600px;margin:auto'>
    <h2 style='color:#0D9488'>🏥 MediBook</h2>
    <p>Dear <strong>$to_name</strong>,</p>
    <p>$message</p>
    <hr style='border-color:#eee'>
    <small style='color:#999'>MediBook Online Clinic System</small>
    </div></body></html>";
    @mail($to_email, $subject, $body, $headers);
}

function formatDate($date) {
    return date('d M Y', strtotime($date));
}

function getStatusBadge($status) {
    $badges = [
        'pending'   => '<span class="badge badge-warning">Pending</span>',
        'confirmed' => '<span class="badge badge-success">Confirmed</span>',
        'completed' => '<span class="badge badge-info">Completed</span>',
        'cancelled' => '<span class="badge badge-danger">Cancelled</span>',
    ];
    return $badges[$status]
        ?? '<span class="badge badge-secondary">' . ucfirst($status) . '</span>';
}

function getTotalStats() {
    global $conn;
    $stats = [];

    $r1 = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='doctor'");
    $stats['doctors'] = $r1 ? intval($r1->fetch_assoc()['c']) : 0;

    $r2 = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='patient'");
    $stats['patients'] = $r2 ? intval($r2->fetch_assoc()['c']) : 0;

    $r3 = $conn->query("SELECT COUNT(*) as c FROM appointments");
    $stats['appointments'] = $r3 ? intval($r3->fetch_assoc()['c']) : 0;

    $r4 = $conn->query("SELECT COUNT(*) as c FROM appointments WHERE appointment_date = CURDATE()");
    $stats['today'] = $r4 ? intval($r4->fetch_assoc()['c']) : 0;

    return $stats;
}
?>