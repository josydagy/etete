<?php
// Email delivery is intentionally not configured in this local XAMPP package.
// Configure an authenticated SMTP provider before enabling automated password-reset emails.
function freshmart_send_email(string $to, string $subject, string $message): bool {
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) return false;
    return false;
}
