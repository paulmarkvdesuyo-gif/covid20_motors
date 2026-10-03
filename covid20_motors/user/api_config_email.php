<?php
// =============================================
// EMAIL CONFIGURATION FILE
// =============================================

// SMTP Settings
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_AUTH', true);

// Your email credentials (CHANGE THESE!)
define('SMTP_USERNAME', 'your_email@gmail.com');     // Your Gmail
define('SMTP_PASSWORD', 'your_app_password');        // Gmail App Password

// Sender details
define('FROM_EMAIL', 'your_email@gmail.com');
define('FROM_NAME', 'Co_vid 20\'s Motor Parts');

// Admin notification email
define('ADMIN_EMAIL', 'admin@covid20smotors.com');   // Admin email

// =============================================
// EMAIL FUNCTIONS
// =============================================

function appBaseUrl() {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/');
    $projectPath = preg_replace('~/user/.*$~', '', $scriptPath);
    if ($projectPath === $scriptPath) {
        $projectPath = dirname($scriptPath);
    }
    $projectPath = rtrim($projectPath, '/');

    return $scheme . '://' . $host . ($projectPath === '' ? '/' : $projectPath . '/');
}

function sendEmail($to, $subject, $message, $isHTML = true) {
    // Check if PHPMailer exists
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = SMTP_AUTH;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_SECURE;
            $mail->Port = SMTP_PORT;
            
            $mail->setFrom(FROM_EMAIL, FROM_NAME);
            $mail->addAddress($to);
            $mail->addReplyTo(FROM_EMAIL, FROM_NAME);
            
            $mail->isHTML($isHTML);
            $mail->Subject = $subject;
            $mail->Body = $message;
            
            if (!$isHTML) {
                $mail->AltBody = strip_tags($message);
            }
            
            $mail->send();
            return ['success' => true, 'message' => 'Email sent'];
        } catch (Exception $e) {
            error_log("PHPMailer Error: " . $e->getMessage());
            return sendMailFallback($to, $subject, $message);
        }
    }
    
    return sendMailFallback($to, $subject, $message);
}

function sendMailFallback($to, $subject, $message) {
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . FROM_NAME . " <" . FROM_EMAIL . ">\r\n";
    $headers .= "Reply-To: " . FROM_EMAIL . "\r\n";
    
    $result = @mail($to, $subject, $message, $headers);
    return [
        'success' => $result,
        'message' => $result ? 'Email sent via mail()' : 'Failed to send'
    ];
}

// =============================================
// ADMIN NOTIFICATION EMAIL
// =============================================

function sendAdminOrderNotification($order_data) {
    $baseUrl = appBaseUrl();
    $order_number = $order_data['order_number'] ?? 'N/A';
    $customer_name = $order_data['customer_name'] ?? 'Unknown';
    $customer_email = $order_data['customer_email'] ?? 'N/A';
    $customer_phone = $order_data['customer_phone'] ?? 'N/A';
    $total_amount = $order_data['total_amount'] ?? 0;
    $payment_method = $order_data['payment_method'] ?? 'cash';
    $delivery_address = $order_data['delivery_address'] ?? 'N/A';
    
    $paymentLabels = [
        'cash' => 'Cash on Delivery',
        'gcash' => 'GCash',
        'bank_transfer' => 'Bank Transfer',
        'card' => 'Credit/Debit Card'
    ];
    $payment_label = $paymentLabels[$payment_method] ?? $payment_method;
    
    $subject = "🛒 NEW ORDER #" . $order_number . " | Co_vid 20's";
    
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>New Order Notification</title>
        <style>
            body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; }
            .container { max-width: 700px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .header { text-align: center; border-bottom: 2px solid #27ae60; padding-bottom: 20px; margin-bottom: 20px; }
            .header h1 { color: #2c3e50; margin: 0; }
            .header h1 span { color: #27ae60; }
            .order-details { background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 20px 0; }
            .order-details p { margin: 5px 0; }
            .status-badge { display: inline-block; background: #f39c12; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; }
            .btn { display: inline-block; background: #27ae60; color: white; padding: 10px 25px; border-radius: 5px; text-decoration: none; }
            .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #7f8c8d; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🏍️ Co_vid 20's <span>Motor Parts</span></h1>
                <p><strong>🔔 NEW ORDER RECEIVED!</strong></p>
            </div>
            
            <h2>📦 Order #" . $order_number . "</h2>
            <p>A new order has been placed on your store.</p>
            
            <div class='order-details'>
                <p><strong>Customer:</strong> " . htmlspecialchars($customer_name) . "</p>
                <p><strong>Email:</strong> " . htmlspecialchars($customer_email) . "</p>
                <p><strong>Phone:</strong> " . htmlspecialchars($customer_phone) . "</p>
                <p><strong>Total Amount:</strong> <span style='color:#27ae60; font-size:20px; font-weight:bold;'>₱" . number_format($total_amount, 2) . "</span></p>
                <p><strong>Payment Method:</strong> " . htmlspecialchars($payment_label) . "</p>
                <p><strong>Delivery Address:</strong> " . nl2br(htmlspecialchars($delivery_address)) . "</p>
                <p><strong>Status:</strong> <span class='status-badge'>PENDING</span></p>
                <p><strong>Order Date:</strong> " . date('F d, Y h:i A') . "</p>
            </div>
            
            <p style='text-align:center;'>
                <a href='{$baseUrl}index.html?admin=1#ordersPage' class='btn'>📋 View in Admin</a>
            </p>
            
            <div class='footer'>
                <p>Co_vid 20's Motor Parts &bull; Admin Notification</p>
                <p style='font-size:10px; color:#999;'>This is an automated message.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail(ADMIN_EMAIL, $subject, $message);
}

// =============================================
// ORDER CONFIRMATION TO CUSTOMER
// =============================================

function sendOrderConfirmation($customer_email, $customer_name, $order_data) {
    $baseUrl = appBaseUrl();
    $order_number = $order_data['order_number'] ?? 'N/A';
    $total_amount = $order_data['total_amount'] ?? 0;
    $payment_method = $order_data['payment_method'] ?? 'cash';
    $delivery_address = $order_data['delivery_address'] ?? 'N/A';
    $items = $order_data['items'] ?? [];
    
    $paymentLabels = [
        'cash' => 'Cash on Delivery',
        'gcash' => 'GCash',
        'bank_transfer' => 'Bank Transfer',
        'card' => 'Credit/Debit Card'
    ];
    $payment_label = $paymentLabels[$payment_method] ?? $payment_method;
    
    $items_html = '';
    foreach ($items as $item) {
        $items_html .= "
            <tr>
                <td style='padding:8px; border:1px solid #ddd;'>" . htmlspecialchars($item['name'] ?? 'Product') . "</td>
                <td style='padding:8px; border:1px solid #ddd; text-align:center;'>" . ($item['quantity'] ?? 1) . "</td>
                <td style='padding:8px; border:1px solid #ddd; text-align:right;'>₱" . number_format($item['price'] ?? 0, 2) . "</td>
                <td style='padding:8px; border:1px solid #ddd; text-align:right;'>₱" . number_format(($item['price'] ?? 0) * ($item['quantity'] ?? 1), 2) . "</td>
            </tr>
        ";
    }
    
    $subject = "Order Confirmation - #" . $order_number . " | Co_vid 20's";
    
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Order Confirmation</title>
        <style>
            body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .header { text-align: center; border-bottom: 2px solid #e67e22; padding-bottom: 20px; margin-bottom: 20px; }
            .header h1 { color: #2c3e50; margin: 0; }
            .header h1 span { color: #e67e22; }
            .order-details { background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 20px 0; }
            .order-details p { margin: 5px 0; }
            table { width: 100%; border-collapse: collapse; margin: 15px 0; }
            th { background: #e67e22; color: white; padding: 10px; text-align: left; }
            .total-row { background: #f8f9fa; font-weight: bold; }
            .btn { display: inline-block; background: #e67e22; color: white; padding: 10px 25px; border-radius: 5px; text-decoration: none; }
            .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #7f8c8d; font-size: 12px; }
            .gcash-info { background: #fff3e6; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #e67e22; }
            .gcash-info .number { font-size: 18px; font-weight: bold; color: #2c3e50; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🏍️ Co_vid 20's <span>Motor Parts</span></h1>
                <p>Order Confirmation</p>
            </div>
            
            <h2>Thank you, " . htmlspecialchars($customer_name) . "!</h2>
            <p>Your order has been received and is being processed.</p>
            
            <div class='order-details'>
                <p><strong>Order Number:</strong> #" . htmlspecialchars($order_number) . "</p>
                <p><strong>Order Date:</strong> " . date('F d, Y h:i A') . "</p>
                <p><strong>Payment Method:</strong> " . htmlspecialchars($payment_label) . "</p>
                <p><strong>Delivery Address:</strong> " . nl2br(htmlspecialchars($delivery_address)) . "</p>
            </div>
            
            <h3>Order Summary</h3>
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th style='text-align:center;'>Qty</th>
                        <th style='text-align:right;'>Price</th>
                        <th style='text-align:right;'>Total</th>
                    </tr>
                </thead>
                <tbody>
                    " . $items_html . "
                    <tr class='total-row'>
                        <td colspan='3' style='text-align:right; padding:10px;'>TOTAL</td>
                        <td style='text-align:right; padding:10px; color:#e67e22; font-size:18px;'>₱" . number_format($total_amount, 2) . "</td>
                    </tr>
                </tbody>
            </table>
            
            " . ($payment_method === 'gcash' ? "
            <div class='gcash-info'>
                <h4>📱 GCash Payment Details</h4>
                <p><strong>GCash Number:</strong> <span class='number'>09243758020</span></p>
                <p><strong>Account Name:</strong> Justine Belmonte</p>
                <p><strong>Amount:</strong> <span style='color:#e67e22; font-weight:bold; font-size:18px;'>₱" . number_format($total_amount, 2) . "</span></p>
                <p><strong>Reference:</strong> Include order #" . htmlspecialchars($order_number) . "</p>
            </div>
            " : "") . "
            
            <p style='text-align:center;'>
                <a href='{$baseUrl}user/shop.html' class='btn'>Continue Shopping</a>
            </p>
            
            <div class='footer'>
                <p>Co_vid 20's Motor Parts &bull; Quality motorcycle parts since 2020</p>
                <p>Contact: support@covid20smotors.com &bull; 0912-345-6789</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($customer_email, $subject, $message);
}
?>