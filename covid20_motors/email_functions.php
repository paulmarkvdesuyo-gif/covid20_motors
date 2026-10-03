<?php
// ============================================
// EMAIL FUNCTIONS - UPDATED WITH STATUS
// ============================================

require_once 'config_email.php';

/**
 * Send email using native PHP mail function
 */
function sendEmail($to, $subject, $body, $isHtml = true) {
    $headers = "From: " . SITE_NAME . " <" . SMTP_USERNAME . ">\r\n";
    $headers .= "Reply-To: " . SMTP_USERNAME . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    $result = @mail($to, $subject, $body, $headers);
    
    if ($result) {
        return ['success' => true, 'message' => 'Email sent successfully'];
    } else {
        error_log("Email sending failed to: $to");
        return ['success' => false, 'message' => 'Email sending failed'];
    }
}

/**
 * Send order status update to customer
 */
function sendOrderStatusUpdate($order, $customer, $status) {
    $statusLabels = [
        'pending' => 'Pending - Awaiting Confirmation',
        'confirmed' => '✅ Confirmed - Order Accepted',
        'processing' => 'Processing - Preparing your order',
        'shipped' => '🚚 Shipped - On the way!',
        'completed' => '✅ Completed - Order Delivered',
        'cancelled' => '❌ Cancelled - Order Cancelled',
        'denied' => '❌ Denied - Order Not Accepted'
    ];
    
    $statusColors = [
        'pending' => '#f39c12',
        'confirmed' => '#27ae60',
        'processing' => '#3498db',
        'shipped' => '#2ecc71',
        'completed' => '#27ae60',
        'cancelled' => '#e74c3c',
        'denied' => '#e74c3c'
    ];
    
    $statusMessages = [
        'pending' => 'Your order is pending and waiting for admin confirmation.',
        'confirmed' => 'Your order has been confirmed! We are now preparing your items.',
        'processing' => 'Your order is now being processed and packed.',
        'shipped' => 'Your order has been shipped! You will receive it soon.',
        'completed' => 'Your order has been completed. Thank you for shopping with us!',
        'cancelled' => 'Your order has been cancelled. Please contact us if you have questions.',
        'denied' => 'Your order has been denied. Please contact us for more information.'
    ];
    
    $subject = 'Order Status Update - ' . SITE_NAME;
    
    $body = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #e67e22; padding: 15px; text-align: center; color: white; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 20px; border-radius: 0 0 10px 10px; }
            .status-badge { background: {$statusColors[$status]}; color: white; padding: 8px 20px; border-radius: 20px; font-size: 16px; display: inline-block; }
            .order-details { background: white; padding: 15px; border-radius: 8px; margin: 15px 0; }
            .footer { text-align: center; padding: 15px; color: #7f8c8d; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>📦 Order Status Update</h2>
            </div>
            <div class='content'>
                <h3>Hello " . htmlspecialchars($customer['fullname']) . ",</h3>
                <p>Your order status has been updated.</p>
                
                <div style='text-align:center; padding:20px;'>
                    <p><strong>Order Number:</strong> " . htmlspecialchars($order['order_number']) . "</p>
                    <p><strong>New Status:</strong></p>
                    <span class='status-badge'>" . $statusLabels[$status] . "</span>
                    <p style='margin-top:15px; color:#555;'>" . $statusMessages[$status] . "</p>
                </div>
                
                <div class='order-details'>
                    <p><strong>Total Amount:</strong> ₱" . number_format($order['total'], 2) . "</p>
                    <p><strong>Payment Method:</strong> " . htmlspecialchars($order['payment_method']) . "</p>
                    <p><strong>Shipping Address:</strong><br>" . nl2br(htmlspecialchars($order['shipping_address'])) . "</p>
                </div>
                
                <p>If you have any questions, please contact us.</p>
                <p>Thank you for shopping with us!</p>
            </div>
            <div class='footer'>
                <p>&copy; " . date('Y') . " " . SITE_NAME . ". All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($customer['email'], $subject, $body);
}
?>