<?php
/**
 * Pure Meals Basket — Feedback form mail handler
 * Receives POST from the feedback form on index.html, sanitizes input,
 * and emails hello@puremealsbasket.co.ke. Responds with JSON for the
 * AJAX handler in js/main.js.
 */

header('Content-Type: application/json');

define('PMB_RECIPIENT', 'hello@puremealsbasket.co.ke');

$ALLOWED_EVENT_TYPES = [
    'Church Event',
    'School Event',
    'Corporate Event',
    'Wedding',
    'Birthday',
    'Graduation',
    'Other',
];

function pmb_respond($success, $message) {
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

// Strip control characters (incl. CRLF) to prevent email header injection.
function pmb_clean_header_value($value) {
    $value = trim($value);
    $value = preg_replace('/[\r\n\x00-\x1F\x7F]+/', ' ', $value);
    return trim($value);
}

function pmb_clean_text($value) {
    $value = trim($value);
    $value = strip_tags($value);
    return $value;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    pmb_respond(false, 'Method not allowed.');
}

$name       = isset($_POST['name']) ? pmb_clean_header_value($_POST['name']) : '';
$phone      = isset($_POST['phone']) ? pmb_clean_header_value($_POST['phone']) : '';
$eventType  = isset($_POST['event_type']) ? pmb_clean_header_value($_POST['event_type']) : '';
$experience = isset($_POST['experience']) ? pmb_clean_text($_POST['experience']) : '';
$rating     = isset($_POST['rating']) ? pmb_clean_header_value($_POST['rating']) : '';

if ($name === '' || $phone === '' || $eventType === '' || $experience === '') {
    http_response_code(422);
    pmb_respond(false, 'Please fill in all required fields.');
}

if (!in_array($eventType, $ALLOWED_EVENT_TYPES, true)) {
    http_response_code(422);
    pmb_respond(false, 'Invalid event type.');
}

$ratingNum = (int) $rating;
if ($ratingNum < 1 || $ratingNum > 5) {
    $ratingNum = 0;
}

// Basic phone sanity check — digits, spaces, +, -, ( ) only.
if (!preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) {
    http_response_code(422);
    pmb_respond(false, 'Please enter a valid phone number.');
}

$subject = 'PMB Feedback — ' . $eventType . ' from ' . $name;

$stars = $ratingNum > 0 ? str_repeat('*', $ratingNum) . str_repeat('-', 5 - $ratingNum) . " ({$ratingNum}/5)" : 'Not provided';

$body  = "New feedback received via puremealsbasket.co.ke\n\n";
$body .= "Name: {$name}\n";
$body .= "Phone: {$phone}\n";
$body .= "Event Type: {$eventType}\n";
$body .= "Star Rating: {$stars}\n\n";
$body .= "Experience:\n{$experience}\n";

$headers   = [];
$headers[] = 'From: Pure Meals Basket Website <no-reply@puremealsbasket.co.ke>';
// Reply-To is set to the submitter's phone number field per business request,
// since the feedback form does not collect an email address.
$headers[] = 'Reply-To: ' . $phone;
$headers[] = 'Content-Type: text/plain; charset=UTF-8';
$headers[] = 'X-Mailer: PHP/' . phpversion();

$sent = @mail(PMB_RECIPIENT, $subject, $body, implode("\r\n", $headers));

if ($sent) {
    pmb_respond(true, 'Asante sana! Thank you for your feedback. We truly appreciate you taking the time to share your experience with us.');
} else {
    http_response_code(500);
    pmb_respond(false, 'Something went wrong. Please try again or reach us directly on WhatsApp.');
}
