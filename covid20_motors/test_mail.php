<?php
// test_mail.php - Test native PHP mail
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Testing PHP Mail Function</h2>";

// Test 1: Basic mail
$to = "your_email@gmail.com";  // PALITAN ITO
$subject = "Test Email from XAMPP";
$message = "<h1>Test</h1><p>Your email is working!</p>";
$headers = "From: noreply@covid20smotors.com\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";

$result = mail($to, $subject, $message, $headers);

if ($result) {
    echo "<p style='color:green'>✅ Email sent successfully!</p>";
    echo "<p>Check your inbox/spam folder.</p>";
} else {
    echo "<p style='color:red'>❌ Email failed.</p>";
    echo "<p>Check that sendmail is configured correctly.</p>";
}

// Test 2: Check sendmail
echo "<h3>Sendmail Status:</h3>";
if (file_exists('C:\xampp\sendmail\sendmail.exe')) {
    echo "<p style='color:green'>✅ Sendmail exists</p>";
} else {
    echo "<p style='color:red'>❌ Sendmail not found</p>";
}

echo "<h3>php.ini mail settings:</h3>";
echo "<pre>";
echo "SMTP: " . ini_get('SMTP') . "\n";
echo "smtp_port: " . ini_get('smtp_port') . "\n";
echo "sendmail_from: " . ini_get('sendmail_from') . "\n";
echo "sendmail_path: " . ini_get('sendmail_path') . "\n";
echo "</pre>";
?>