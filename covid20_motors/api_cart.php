<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/database_config.php';

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create cart table if not exists
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'cart'");
    if ($tableCheck->rowCount() == 0) {
        $pdo->exec("
            CREATE TABLE cart (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                product_id INT NOT NULL,
                quantity INT NOT NULL DEFAULT 1,
                added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        $pdo->exec("CREATE INDEX idx_user_id ON cart(user_id)");
        $pdo->exec("CREATE INDEX idx_product_id ON cart(product_id)");
    }
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            $userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
            if (!$userId) {
                echo json_encode(['success' => false, 'message' => 'User ID required']);
                exit;
            }
            
            $stmt = $pdo->prepare("
                SELECT c.id, c.product_id, c.quantity, COALESCE(p.product_name, p.name) AS name, p.price, p.stock, p.image
                FROM cart c
                JOIN products p ON c.product_id = p.id
                WHERE c.user_id = ?
            ");
            $stmt->execute([$userId]);
            $cart = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'cart' => $cart]);
            break;
            
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                parse_str(file_get_contents('php://input'), $data);
            }
            if (!$data) {
                $data = $_POST;
            }
            $userId = isset($data['user_id']) ? intval($data['user_id']) : 0;
            $action = isset($data['action']) ? strtolower(trim($data['action'])) : 'add';
            $productId = isset($data['product_id']) ? intval($data['product_id']) : 0;
            $quantity = isset($data['quantity']) ? max(1, intval($data['quantity'])) : 1;
            $cartId = isset($data['cart_id']) ? intval($data['cart_id']) : 0;
            
            if (!$userId) {
                echo json_encode(['success' => false, 'message' => 'User ID is required']);
                exit;
            }
            
            if ($action === 'remove') {
                if (!$cartId && !$productId) {
                    echo json_encode(['success' => false, 'message' => 'Cart item ID or product ID is required for removal']);
                    exit;
                }

                if ($cartId) {
                    $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
                    $stmt->execute([$cartId, $userId]);
                } else {
                    $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
                    $stmt->execute([$userId, $productId]);
                }

                echo json_encode(['success' => true, 'message' => 'Item removed from cart']);
                break;
            }

            if (!$productId) {
                echo json_encode(['success' => false, 'message' => 'Product ID is required']);
                exit;
            }

            $stmt = $pdo->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$userId, $productId]);
            $check = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($check) {
                if ($action === 'update') {
                    $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
                    $stmt->execute([$quantity, $check['id']]);
                } else {
                    $stmt = $pdo->prepare("UPDATE cart SET quantity = quantity + ? WHERE id = ?");
                    $stmt->execute([$quantity, $check['id']]);
                }
            } else {
                $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
                $stmt->execute([$userId, $productId, $quantity]);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Added to cart'
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid method']);
    }
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>