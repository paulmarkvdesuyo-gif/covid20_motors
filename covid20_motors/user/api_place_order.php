<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../db_config.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['user_id']) || !isset($data['cart']) || empty($data['cart'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

    $receiptColumnStmt = $db->query("SHOW COLUMNS FROM orders LIKE 'payment_receipt'");
    if (!$receiptColumnStmt->fetch()) {
        $db->exec("ALTER TABLE orders ADD COLUMN payment_receipt VARCHAR(500) NULL");
    }
    $messageColumnStmt = $db->query("SHOW COLUMNS FROM orders LIKE 'admin_message'");
    if (!$messageColumnStmt->fetch()) {
        $db->exec("ALTER TABLE orders ADD COLUMN admin_message TEXT NULL");
    }
    $gcashNumberColumnStmt = $db->query("SHOW COLUMNS FROM orders LIKE 'gcash_number'");
    if (!$gcashNumberColumnStmt->fetch()) {
        $db->exec("ALTER TABLE orders ADD COLUMN gcash_number VARCHAR(30) NULL");
    }
    $gcashNameColumnStmt = $db->query("SHOW COLUMNS FROM orders LIKE 'gcash_name'");
    if (!$gcashNameColumnStmt->fetch()) {
        $db->exec("ALTER TABLE orders ADD COLUMN gcash_name VARCHAR(150) NULL");
    }
    $qrIndexColumnStmt = $db->query("SHOW COLUMNS FROM orders LIKE 'qr_index'");
    if (!$qrIndexColumnStmt->fetch()) {
        $db->exec("ALTER TABLE orders ADD COLUMN qr_index INT NULL");
    }

try {
    $user_id = isset($data['user_id']) ? (int)$data['user_id'] : 0;
    $cart = $data['cart'];
    $address = isset($data['address']) ? $data['address'] : '';
    $payment_method = isset($data['payment_method']) ? $data['payment_method'] : 'cash';
    $delivery_info = isset($data['delivery_info']) ? $data['delivery_info'] : [];
    $user_lookup = isset($data['user_lookup']) && is_array($data['user_lookup']) ? $data['user_lookup'] : [];
    $gcash_info = isset($data['gcash_info']) ? $data['gcash_info'] : [];

    $gcashNumber = isset($gcash_info['display']) ? trim($gcash_info['display']) : '';
    $gcashName = isset($gcash_info['name']) ? trim($gcash_info['name']) : '';
    $qrIndex = isset($gcash_info['qr_index']) ? (int)$gcash_info['qr_index'] : null;

    if ($payment_method === 'gcash') {
        if ($gcashNumber === '' || $gcashName === '') {
            echo json_encode(['success' => false, 'message' => 'The GCash store number and name are required.']);
            exit;
        }
        $receipt_data = $gcash_info['receipt_data'] ?? '';
        if (!is_string($receipt_data) || !preg_match('/^data:image\/(jpeg|jpg|png|gif|webp);base64,/i', $receipt_data)) {
            echo json_encode(['success' => false, 'message' => 'A valid GCash proof-of-payment image is required.']);
            exit;
        }

        $encodedReceipt = preg_replace('/^data:image\/(jpeg|jpg|png|gif|webp);base64,/i', '', $receipt_data);
        $decodedReceipt = base64_decode($encodedReceipt, true);
        if ($decodedReceipt === false || strlen($decodedReceipt) === 0 || strlen($decodedReceipt) > 5 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'The GCash proof image must be no larger than 5 MB.']);
            exit;
        }
    }

    $user = null;

    if ($user_id > 0) {
        $userQuery = "SELECT * FROM users WHERE id = ? LIMIT 1";
        $userStmt = $db->prepare($userQuery);
        $userStmt->execute([$user_id]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    }

    if (!$user && !empty($user_lookup)) {
        $lookupClauses = [];
        $lookupParams = [];

        if (!empty($user_lookup['email'])) {
            $email_trimmed = trim(strtolower($user_lookup['email']));
            $lookupClauses[] = 'LOWER(TRIM(email)) = ?';
            $lookupParams[] = $email_trimmed;
        }

        if (!empty($user_lookup['username'])) {
            $username_trimmed = trim($user_lookup['username']);
            $lookupClauses[] = 'LOWER(TRIM(username)) = ?';
            $lookupParams[] = strtolower($username_trimmed);
        }

        if (!empty($lookupClauses)) {
            $lookupSql = "SELECT * FROM users WHERE " . implode(' OR ', $lookupClauses) . " LIMIT 1";
            $lookupStmt = $db->prepare($lookupSql);
            $lookupStmt->execute($lookupParams);
            $user = $lookupStmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $user_id = (int)$user['id'];
            }
        }
    }

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found. Please login again.']);
        exit;
    }

    $user_id = (int)$user['id'];

    // Calculate total
    $total = 0;
    foreach ($cart as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    
    // Generate order number
    $order_number = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    
    // Prepare customer information
    $customer_name = isset($delivery_info['fullname']) ? $delivery_info['fullname'] : $user['fullname'];
    $customer_email = isset($delivery_info['email']) && !empty($delivery_info['email']) ? $delivery_info['email'] : $user['email'];
    $customer_phone = isset($delivery_info['phone']) ? $delivery_info['phone'] : ($user['phone'] ?? '');
    $delivery_address = isset($data['address']) && !empty($data['address']) ? $data['address'] : ($user['address'] ?? '');
    
    // Insert order
    $orderQuery = "INSERT INTO orders (order_number, user_id, customer_name, customer_email, customer_phone, total_amount, subtotal, delivery_address, payment_method, status, order_date) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
    $orderStmt = $db->prepare($orderQuery);
    $orderStmt->execute([
        $order_number,
        $user_id,
        $customer_name,
        $customer_email,
        $customer_phone,
        $total,
        $total,
        $delivery_address,
        $payment_method
    ]);
    
    $order_id = $db->lastInsertId();

    if ($payment_method === 'gcash') {
        $gcashDetailsStmt = $db->prepare("UPDATE orders SET gcash_number = ?, gcash_name = ?, qr_index = ? WHERE id = ?");
        $gcashDetailsStmt->execute([$gcashNumber, $gcashName, $qrIndex, $order_id]);
    }

    // =============================================
    // SAVE GCASH RECEIPT IMAGE TO DATABASE
    // =============================================
    $receipt_path = null;
    if ($payment_method === 'gcash' && isset($gcash_info['receipt_data']) && !empty($gcash_info['receipt_data'])) {
        // Create uploads directory if not exists
        $uploadDir = __DIR__ . '/uploads/receipts/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $receipt_data = $gcash_info['receipt_data'];
        $receipt_name = isset($gcash_info['receipt_name']) ? $gcash_info['receipt_name'] : 'receipt.png';
        
        // If receipt data is base64
        if (strpos($receipt_data, 'data:image') === 0) {
            // Remove data URL prefix
            $image_data = preg_replace('#^data:image/\w+;base64,#i', '', $receipt_data);
            $image_data = base64_decode($image_data);
            
            if ($image_data !== false) {
                // Generate unique filename
                $extension = strtolower(pathinfo($receipt_name, PATHINFO_EXTENSION));
                if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
                    $extension = 'png';
                }
                if (empty($extension)) {
                    $extension = 'png';
                }
                $filename = 'receipt_order_' . $order_number . '_' . time() . '.' . $extension;
                $filepath = $uploadDir . $filename;
                
                if (file_put_contents($filepath, $image_data)) {
                    $receipt_path = 'uploads/receipts/' . $filename;
                    
                    // Update order with receipt path
                    $updateReceiptQuery = "UPDATE orders SET payment_receipt = ? WHERE id = ?";
                    $updateReceiptStmt = $db->prepare($updateReceiptQuery);
                    $updateReceiptStmt->execute([$receipt_path, $order_id]);
                }
            }
        } elseif (filter_var($receipt_data, FILTER_VALIDATE_URL)) {
            // If it's a URL, save it directly
            $receipt_path = $receipt_data;
            $updateReceiptQuery = "UPDATE orders SET payment_receipt = ? WHERE id = ?";
            $updateReceiptStmt = $db->prepare($updateReceiptQuery);
            $updateReceiptStmt->execute([$receipt_path, $order_id]);
        }
        
        // Save GCash info to order
        $gcashNote = "GCash Payment: Number: {$gcashNumber}, Account: {$gcashName}, QR #: {$qrIndex}";
        if ($receipt_path) {
            $gcashNote .= ", Receipt: {$receipt_path}";
        }
        
        try {
            // Check if admin_message column exists, if not, use a different approach
            $noteQuery = "UPDATE orders SET admin_message = CONCAT(IFNULL(admin_message, ''), ?) WHERE id = ?";
            $noteStmt = $db->prepare($noteQuery);
            $noteStmt->execute(["\n" . $gcashNote, $order_id]);
        } catch(Exception $e) {
            // If admin_message column doesn't exist, ignore
        }
    }
    
    // Insert order items and update stock
    foreach ($cart as $item) {
        // Insert order item
        $itemQuery = "INSERT INTO order_items (order_id, product_id, quantity, price, subtotal) 
                      VALUES (?, ?, ?, ?, ?)";
        $itemStmt = $db->prepare($itemQuery);
        $itemSubtotal = $item['price'] * $item['quantity'];
        $itemStmt->execute([
            $order_id,
            $item['product_id'],
            $item['quantity'],
            $item['price'],
            $itemSubtotal
        ]);

        // Record customer purchases in the admin sales history.
        $saleQuery = "INSERT INTO sales (product_id, quantity, selling_price, total, user_id, order_id)
                      VALUES (?, ?, ?, ?, ?, ?)";
        $saleStmt = $db->prepare($saleQuery);
        $saleStmt->execute([
            $item['product_id'],
            $item['quantity'],
            $item['price'],
            $itemSubtotal,
            $user_id,
            $order_id
        ]);
        
        // Update product stock
        $stockQuery = "UPDATE products SET stock = stock - ? WHERE id = ?";
        $stockStmt = $db->prepare($stockQuery);
        $stockStmt->execute([$item['quantity'], $item['product_id']]);
    }
    
    // Clear cart items after order
    $clearQuery = "DELETE FROM cart_items WHERE user_id = ?";
    $clearStmt = $db->prepare($clearQuery);
    $clearStmt->execute([$user_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Order placed successfully',
        'order_id' => $order_id,
        'order_number' => $order_number,
        'receipt_path' => $receipt_path
    ]);
    
} catch(PDOException $e) {
    error_log("Place order error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch(Exception $e) {
    error_log("Place order error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>