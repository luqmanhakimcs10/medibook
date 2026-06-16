<?php
// includes/get_slots.php
// AJAX endpoint — returns available time slots as JSON
//
// Called by patient/book_appointment.php via fetch()
// URL: get_slots.php?doctor_id=USERS_ID&date=YYYY-MM-DD
//
// The doctor_id received here is users.id (from the booking dropdown).
// getAvailableSlots() internally converts it to doctors.id
// before querying the time_slots table. This is the key fix.

require_once '../config/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Receive users.id from the booking form dropdown
$users_id = intval($_GET['doctor_id'] ?? 0);
$date     = sanitize($_GET['date']      ?? '');

// Basic validation
if (!$users_id || !$date) {
    echo json_encode(['slots' => [], 'error' => 'Missing doctor or date']);
    exit();
}

// Reject past dates
if (strtotime($date) < strtotime(date('Y-m-d'))) {
    echo json_encode(['slots' => [], 'error' => 'Date is in the past']);
    exit();
}

// getAvailableSlots handles users.id → doctors.id translation internally
$slots = getAvailableSlots($users_id, $date);

echo json_encode(['slots' => $slots]);
?>