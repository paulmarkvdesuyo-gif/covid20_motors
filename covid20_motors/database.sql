-- =============================================
-- Import this file after selecting the database configured in database_config.php.
-- Complete Database for Co_vid 20's Motor Parts
-- =============================================

-- =============================================
-- 1. USERS TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    fullname VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    city VARCHAR(50),
    province VARCHAR(50),
    zip_code VARCHAR(10),
    role ENUM('admin', 'staff', 'customer') DEFAULT 'customer',
    is_active TINYINT(1) DEFAULT 1,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role)
);

-- =============================================
-- 2. PRODUCTS TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_name VARCHAR(150) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    cost_price DECIMAL(10,2) DEFAULT 0.00,
    stock INT NOT NULL DEFAULT 0,
    reorder_level INT DEFAULT 5,
    category VARCHAR(50),
    sub_category VARCHAR(50),
    brand VARCHAR(50),
    sku VARCHAR(50) UNIQUE,
    image VARCHAR(255),
    weight DECIMAL(8,2) DEFAULT 0,
    dimensions VARCHAR(50),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_brand (brand),
    INDEX idx_stock (stock),
    INDEX idx_sku (sku)
);

-- =============================================
-- 3. CATEGORIES TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(50),
    parent_id INT DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE CASCADE,
    INDEX idx_parent (parent_id)
);

-- =============================================
-- 4. ORDERS TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    order_number VARCHAR(20) UNIQUE NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    shipping_fee DECIMAL(8,2) DEFAULT 0.00,
    discount DECIMAL(8,2) DEFAULT 0.00,
    tax DECIMAL(8,2) DEFAULT 0.00,
    status ENUM('pending', 'confirmed', 'processing', 'shipped', 'out_for_delivery', 'delivered', 'completed', 'cancelled', 'denied', 'refunded') DEFAULT 'pending',
    payment_method ENUM('cash', 'gcash', 'bank_transfer', 'card', 'paypal') DEFAULT 'cash',
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    delivery_address TEXT NOT NULL,
    delivery_instructions TEXT,
    tracking_number VARCHAR(50),
    delivery_status VARCHAR(50),
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estimated_delivery DATE,
    delivered_at TIMESTAMP NULL,
    cancelled_at TIMESTAMP NULL,
    notes TEXT,
    payment_receipt VARCHAR(500) NULL,
    admin_message TEXT NULL,
    gcash_number VARCHAR(30) NULL,
    gcash_name VARCHAR(150) NULL,
    qr_index INT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_order_number (order_number),
    INDEX idx_status (status),
    INDEX idx_payment_status (payment_status),
    INDEX idx_order_date (order_date)
);

-- =============================================
-- 5. ORDER ITEMS TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    discount DECIMAL(8,2) DEFAULT 0.00,
    subtotal DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_order_id (order_id),
    INDEX idx_product_id (product_id)
);

-- =============================================
-- 6. CART ITEMS TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS cart_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_cart_item (user_id, product_id),
    INDEX idx_user_id (user_id)
);

-- =============================================
-- 7. SALES TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS sales (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    selling_price DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    user_id INT,
    order_id INT,
    sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    INDEX idx_product_id (product_id),
    INDEX idx_sale_date (sale_date)
);

-- =============================================
-- 8. NOTIFICATIONS TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    order_id INT,
    type ENUM('order_update', 'payment', 'delivery', 'promotion', 'system') DEFAULT 'system',
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('unread', 'read') DEFAULT 'unread',
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status)
);

-- =============================================
-- 9. REVIEWS TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    order_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    INDEX idx_product_id (product_id),
    INDEX idx_rating (rating)
);

-- =============================================
-- 10. WISHLIST TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS wishlist (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_wishlist (user_id, product_id)
);

-- =============================================
-- 11. COUPONS TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS coupons (
    id INT PRIMARY KEY AUTO_INCREMENT,
    coupon_code VARCHAR(50) UNIQUE NOT NULL,
    discount_type ENUM('percentage', 'fixed') DEFAULT 'percentage',
    discount_value DECIMAL(10,2) NOT NULL,
    min_order_amount DECIMAL(10,2) DEFAULT 0.00,
    max_discount DECIMAL(10,2) DEFAULT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    usage_limit INT DEFAULT 1,
    used_count INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_coupon_code (coupon_code),
    INDEX idx_active (is_active)
);

-- =============================================
-- 12. SHIPPING TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS shipping (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    shipping_method VARCHAR(50) NOT NULL,
    tracking_number VARCHAR(50),
    status ENUM('pending', 'picked', 'in_transit', 'delivered', 'failed') DEFAULT 'pending',
    shipped_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    carrier VARCHAR(50),
    shipping_cost DECIMAL(8,2) DEFAULT 0.00,
    notes TEXT,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    INDEX idx_order_id (order_id),
    INDEX idx_tracking_number (tracking_number)
);

-- =============================================
-- 13. PAYMENT TRANSACTIONS TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS payment_transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash', 'gcash', 'bank_transfer', 'card', 'paypal') NOT NULL,
    transaction_id VARCHAR(100),
    reference_number VARCHAR(100),
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_order_id (order_id),
    INDEX idx_transaction_id (transaction_id)
);

-- =============================================
-- 14. ACTIVITY LOGS TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
);

-- =============================================
-- 15. BRANDS TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS brands (
    id INT PRIMARY KEY AUTO_INCREMENT,
    brand_name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    logo VARCHAR(255),
    website VARCHAR(100),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- INSERT CATEGORIES
-- =============================================
INSERT INTO categories (category_name, description, icon) VALUES
('Engine Parts', 'All engine components and parts', '🔧'),
('Brakes', 'Brake systems and components', '🛑'),
('Electrical', 'Electrical and electronic parts', '⚡'),
('Accessories', 'Motorcycle accessories and add-ons', '🎨'),
('Tires', 'Tires and related products', '🛞'),
('Lubricants', 'Oils, greases and lubricants', '⛽'),
('Drive Train', 'Transmission and drive components', '⚙️'),
('Tools', 'Motorcycle maintenance tools', '🔨'),
('Body Parts', 'Fairings, fenders and body components', '🏍️'),
('Suspension', 'Suspension and steering components', '📐'),
('Fuel System', 'Fuel delivery and management', '⛽'),
('Cooling System', 'Radiator and cooling components', '❄️');

-- =============================================
-- INSERT BRANDS
-- =============================================
INSERT INTO brands (brand_name, description, website) VALUES
('NGK', 'Leading manufacturer of spark plugs', 'https://www.ngk.com'),
('Denso', 'Premium automotive parts manufacturer', 'https://www.denso.com'),
('Brembo', 'World leader in braking systems', 'https://www.brembo.com'),
('Ohlins', 'Premium suspension manufacturer', 'https://www.ohlins.com'),
('Michelin', 'Tire manufacturing giant', 'https://www.michelin.com'),
('Mobil', 'Leading lubricants brand', 'https://www.mobil.com'),
('Castrol', 'Premium engine oils and lubricants', 'https://www.castrol.com'),
('Bosch', 'Automotive parts and accessories', 'https://www.bosch.com'),
('Shimano', 'Bicycle and motorcycle components', 'https://www.shimano.com'),
('Dunlop', 'Tire manufacturer', 'https://www.dunlop.com');

-- =============================================
-- INSERT PRODUCTS (250+ Products)
-- =============================================

-- ENGINE PARTS (30 products)
INSERT INTO products (product_name, description, price, cost_price, stock, reorder_level, category, brand, sku) VALUES
('Piston Ring Set Standard', 'High quality piston ring set, standard size', 450.00, 280.00, 25, 5, 'Engine Parts', 'NGK', 'ENG-PRS-001'),
('Piston Ring Set Oversize', 'Oversize piston ring set for worn cylinders', 480.00, 300.00, 20, 5, 'Engine Parts', 'NGK', 'ENG-PRS-002'),
('Cylinder Head Gasket', 'Premium cylinder head gasket', 380.00, 220.00, 30, 5, 'Engine Parts', 'Bosch', 'ENG-CHG-001'),
('Valve Set (4pcs)', 'Complete valve set with guides', 650.00, 400.00, 15, 3, 'Engine Parts', 'NGK', 'ENG-VAL-001'),
('Camshaft Assembly', 'Performance camshaft with bearings', 1200.00, 750.00, 10, 3, 'Engine Parts', 'Bosch', 'ENG-CAM-001'),
('Crankshaft Assembly', 'Complete crankshaft with bearings', 2500.00, 1500.00, 8, 2, 'Engine Parts', 'Bosch', 'ENG-CRK-001'),
('Timing Chain', 'Heavy duty timing chain', 550.00, 350.00, 20, 5, 'Engine Parts', 'Bosch', 'ENG-TIM-001'),
('Oil Pump Assembly', 'High pressure oil pump', 890.00, 550.00, 12, 3, 'Engine Parts', 'Bosch', 'ENG-OIL-001'),
('Water Pump Assembly', 'Coolant circulation pump', 750.00, 450.00, 15, 3, 'Engine Parts', 'Bosch', 'ENG-WAT-001'),
('Air Filter Element', 'High flow air filter', 280.00, 160.00, 40, 10, 'Engine Parts', 'Bosch', 'ENG-AIR-001'),
('Oil Filter Element', 'Premium oil filter', 180.00, 100.00, 50, 10, 'Engine Parts', 'Bosch', 'ENG-OIF-001'),
('Spark Plug NGK', 'Iridium spark plug', 120.00, 70.00, 60, 10, 'Engine Parts', 'NGK', 'ENG-SPK-001'),
('Ignition Coil', 'High output ignition coil', 680.00, 400.00, 18, 5, 'Engine Parts', 'Bosch', 'ENG-IGN-001'),
('Starter Motor', 'Heavy duty starter motor', 1500.00, 900.00, 10, 3, 'Engine Parts', 'Bosch', 'ENG-STR-001'),
('Alternator Assembly', 'Complete alternator with regulator', 1800.00, 1100.00, 8, 2, 'Engine Parts', 'Bosch', 'ENG-ALT-001'),
('Radiator Hose Set', 'Silicon radiator hose kit', 320.00, 190.00, 25, 5, 'Engine Parts', 'Bosch', 'ENG-RAD-001'),
('Valve Spring Set', 'Heavy duty valve springs', 280.00, 170.00, 20, 5, 'Engine Parts', 'NGK', 'ENG-VSP-001'),
('Connecting Rod', 'Forged connecting rod', 890.00, 550.00, 12, 3, 'Engine Parts', 'Bosch', 'ENG-CON-001'),
('Cam Chain Guide', 'Durable cam chain guide', 250.00, 150.00, 22, 5, 'Engine Parts', 'Bosch', 'ENG-CCG-001'),
('Cylinder Sleeve', 'Hardened cylinder sleeve', 650.00, 400.00, 10, 3, 'Engine Parts', 'Bosch', 'ENG-CYS-001'),
('Piston Pin', 'High strength piston pin', 180.00, 100.00, 30, 5, 'Engine Parts', 'NGK', 'ENG-PPN-001'),
('Valve Guide Set', 'Bronze valve guides', 320.00, 190.00, 18, 5, 'Engine Parts', 'NGK', 'ENG-VGU-001'),
('Engine Mount Bracket', 'Heavy duty engine mount', 450.00, 280.00, 15, 3, 'Engine Parts', 'Bosch', 'ENG-EMB-001'),
('Exhaust Header Pipe', 'Stainless steel header pipe', 890.00, 550.00, 8, 2, 'Engine Parts', 'Bosch', 'ENG-EHP-001'),
('Muffler Assembly', 'Performance muffler with silencer', 1200.00, 750.00, 10, 3, 'Engine Parts', 'Bosch', 'ENG-MUF-001'),
('Throttle Body Assembly', 'Electronic throttle body', 1800.00, 1100.00, 6, 2, 'Engine Parts', 'Bosch', 'ENG-THR-001'),
('Fuel Injector', 'High flow fuel injector', 1500.00, 900.00, 8, 2, 'Engine Parts', 'Bosch', 'ENG-FUI-001'),
('Carburetor Rebuild Kit', 'Complete carburetor rebuild kit', 420.00, 250.00, 15, 3, 'Engine Parts', 'Bosch', 'ENG-CAR-001'),
('Timing Belt', 'Reinforced timing belt', 380.00, 230.00, 18, 5, 'Engine Parts', 'Bosch', 'ENG-TIB-001'),
('Valve Cover Gasket', 'Silicone valve cover gasket', 250.00, 150.00, 25, 5, 'Engine Parts', 'Bosch', 'ENG-VCG-001');

-- BRAKES (25 products)
INSERT INTO products (product_name, description, price, cost_price, stock, reorder_level, category, brand, sku) VALUES
('Brake Pads Front (Organic)', 'Organic brake pads for front', 450.00, 280.00, 35, 10, 'Brakes', 'Brembo', 'BRK-FP1-001'),
('Brake Pads Front (Sintered)', 'High performance sintered pads', 580.00, 350.00, 25, 5, 'Brakes', 'Brembo', 'BRK-FP2-001'),
('Brake Pads Rear (Organic)', 'Organic brake pads for rear', 380.00, 230.00, 30, 10, 'Brakes', 'Brembo', 'BRK-RP1-001'),
('Brake Pads Rear (Sintered)', 'High performance sintered pads', 520.00, 320.00, 22, 5, 'Brakes', 'Brembo', 'BRK-RP2-001'),
('Brake Disc Front (Solid)', 'Solid front brake disc', 1200.00, 720.00, 15, 3, 'Brakes', 'Brembo', 'BRK-FD1-001'),
('Brake Disc Front (Wave)', 'Wave pattern front disc', 1350.00, 810.00, 12, 3, 'Brakes', 'Brembo', 'BRK-FD2-001'),
('Brake Disc Rear (Solid)', 'Solid rear brake disc', 980.00, 590.00, 12, 3, 'Brakes', 'Brembo', 'BRK-RD1-001'),
('Brake Disc Rear (Wave)', 'Wave pattern rear disc', 1100.00, 660.00, 10, 3, 'Brakes', 'Brembo', 'BRK-RD2-001'),
('Brake Caliper Front', '4-piston front caliper', 850.00, 510.00, 10, 3, 'Brakes', 'Brembo', 'BRK-FC1-001'),
('Brake Caliper Rear', '2-piston rear caliper', 750.00, 450.00, 10, 3, 'Brakes', 'Brembo', 'BRK-RC1-001'),
('Brake Master Cylinder', 'Front brake master cylinder', 1100.00, 660.00, 8, 2, 'Brakes', 'Brembo', 'BRK-MC1-001'),
('Brake Fluid DOT 4', 'High performance brake fluid', 280.00, 170.00, 35, 10, 'Brakes', 'Castrol', 'BRK-BF1-001'),
('Brake Hose Front', 'Steel braided front brake hose', 420.00, 250.00, 20, 5, 'Brakes', 'Brembo', 'BRK-FH1-001'),
('Brake Hose Rear', 'Steel braided rear brake hose', 380.00, 230.00, 22, 5, 'Brakes', 'Brembo', 'BRK-RH1-001'),
('Brake Lever Front', 'Adjustable front brake lever', 580.00, 350.00, 25, 5, 'Brakes', 'Brembo', 'BRK-FL1-001'),
('Brake Lever Rear', 'Adjustable rear brake lever', 450.00, 270.00, 20, 5, 'Brakes', 'Brembo', 'BRK-RL1-001'),
('ABS Sensor Front', 'Front wheel speed sensor', 750.00, 450.00, 10, 3, 'Brakes', 'Bosch', 'BRK-AS1-001'),
('ABS Sensor Rear', 'Rear wheel speed sensor', 720.00, 430.00, 10, 3, 'Brakes', 'Bosch', 'BRK-AS2-001'),
('Brake Pad Retainer Set', 'Brake pad clips and springs', 180.00, 110.00, 30, 5, 'Brakes', 'Brembo', 'BRK-PRS-001'),
('Brake Caliper Rebuild Kit', 'Complete caliper seal kit', 350.00, 210.00, 18, 5, 'Brakes', 'Brembo', 'BRK-CRK-001'),
('Brake Disc Bolt Set', 'High strength disc bolts', 150.00, 90.00, 35, 10, 'Brakes', 'Brembo', 'BRK-DBS-001'),
('Brake Light Switch', 'Hydraulic brake light switch', 220.00, 130.00, 25, 5, 'Brakes', 'Bosch', 'BRK-BLS-001'),
('Brake Fluid Reservoir', 'Brake fluid reservoir tank', 320.00, 190.00, 15, 3, 'Brakes', 'Brembo', 'BRK-BFR-001'),
('Brake Line Banjo Bolt', 'Banjo bolt with copper washers', 120.00, 70.00, 40, 10, 'Brakes', 'Brembo', 'BRK-BJB-001'),
('Brake Bleeder Valve', 'Brake bleeder screw', 80.00, 45.00, 50, 10, 'Brakes', 'Brembo', 'BRK-BBV-001');

-- ELECTRICAL (30 products)
INSERT INTO products (product_name, description, price, cost_price, stock, reorder_level, category, brand, sku) VALUES
('LED Headlight Bulb H4', 'LED headlight conversion kit', 650.00, 390.00, 30, 5, 'Electrical', 'Bosch', 'ELE-H4-001'),
('LED Headlight Bulb H7', 'LED headlight bulb H7', 620.00, 370.00, 28, 5, 'Electrical', 'Bosch', 'ELE-H7-001'),
('LED Tail Light Assembly', 'Complete LED tail light', 480.00, 290.00, 25, 5, 'Electrical', 'Bosch', 'ELE-TL1-001'),
('LED Turn Signal Front', 'Front LED turn signals pair', 350.00, 210.00, 30, 5, 'Electrical', 'Bosch', 'ELE-TS1-001'),
('LED Turn Signal Rear', 'Rear LED turn signals pair', 330.00, 200.00, 28, 5, 'Electrical', 'Bosch', 'ELE-TS2-001'),
('Battery 12V 4Ah', 'Maintenance free battery', 1200.00, 720.00, 15, 3, 'Electrical', 'Bosch', 'ELE-BAT-001'),
('Battery 12V 7Ah', 'High capacity battery', 1500.00, 900.00, 15, 3, 'Electrical', 'Bosch', 'ELE-BAT-002'),
('Battery 12V 9Ah', 'Extra capacity battery', 1800.00, 1080.00, 10, 3, 'Electrical', 'Bosch', 'ELE-BAT-003'),
('Rectifier/Regulator 4-Wire', '4-wire rectifier regulator', 890.00, 530.00, 12, 3, 'Electrical', 'Bosch', 'ELE-RR1-001'),
('Rectifier/Regulator 5-Wire', '5-wire rectifier regulator', 950.00, 570.00, 10, 3, 'Electrical', 'Bosch', 'ELE-RR2-001'),
('CDI Unit DC Type', 'Digital CDI unit DC', 1200.00, 720.00, 10, 3, 'Electrical', 'Bosch', 'ELE-CDI-001'),
('CDI Unit AC Type', 'Digital CDI unit AC', 1100.00, 660.00, 12, 3, 'Electrical', 'Bosch', 'ELE-CDI-002'),
('Wiring Harness Complete', 'Complete wiring harness', 1800.00, 1080.00, 8, 2, 'Electrical', 'Bosch', 'ELE-WIR-001'),
('Fuse Box with Relays', 'Fuse box and relay panel', 250.00, 150.00, 35, 10, 'Electrical', 'Bosch', 'ELE-FUS-001'),
('Ignition Switch Set', 'Complete ignition switch with keys', 550.00, 330.00, 20, 5, 'Electrical', 'Bosch', 'ELE-IGS-001'),
('Starter Relay', 'Heavy duty starter relay', 380.00, 230.00, 25, 5, 'Electrical', 'Bosch', 'ELE-SRR-001'),
('Voltage Regulator', 'Voltage regulator module', 750.00, 450.00, 15, 3, 'Electrical', 'Bosch', 'ELE-VRG-001'),
('Speed Sensor Digital', 'Digital speed sensor', 450.00, 270.00, 18, 5, 'Electrical', 'Bosch', 'ELE-SPD-001'),
('Speed Sensor Analog', 'Analog speed sensor', 400.00, 240.00, 20, 5, 'Electrical', 'Bosch', 'ELE-SPA-001'),
('Gear Position Sensor', 'Gear position sensor', 380.00, 230.00, 15, 3, 'Electrical', 'Bosch', 'ELE-GPS-001'),
('Neutral Switch', 'Neutral gear switch', 180.00, 110.00, 30, 5, 'Electrical', 'Bosch', 'ELE-NEU-001'),
('Side Stand Switch', 'Side stand safety switch', 220.00, 130.00, 25, 5, 'Electrical', 'Bosch', 'ELE-SSS-001'),
('Clutch Switch', 'Clutch safety switch', 150.00, 90.00, 30, 5, 'Electrical', 'Bosch', 'ELE-CLS-001'),
('Horn Assembly', 'Dual tone horn', 280.00, 170.00, 35, 10, 'Electrical', 'Bosch', 'ELE-HRN-001'),
('Flasher Relay LED', 'LED flasher relay', 180.00, 110.00, 40, 10, 'Electrical', 'Bosch', 'ELE-FLR-001'),
('Instrument Cluster Digital', 'Digital instrument cluster', 1800.00, 1080.00, 6, 2, 'Electrical', 'Bosch', 'ELE-ICD-001'),
('Instrument Cluster Analog', 'Analog instrument cluster', 1500.00, 900.00, 8, 2, 'Electrical', 'Bosch', 'ELE-ICA-001'),
('Speedometer Cable', 'Speedometer cable', 250.00, 150.00, 20, 5, 'Electrical', 'Bosch', 'ELE-SPC-001'),
('Tachometer Cable', 'Tachometer cable', 220.00, 130.00, 18, 5, 'Electrical', 'Bosch', 'ELE-TAC-001'),
('Handlebar Switch Left', 'Left handlebar switch assembly', 450.00, 270.00, 15, 3, 'Electrical', 'Bosch', 'ELE-HSL-001');

-- ACCESSORIES (40 products)
INSERT INTO products (product_name, description, price, cost_price, stock, reorder_level, category, brand, sku) VALUES
('Handlebar Grips Rubber', 'Comfort rubber grips', 250.00, 150.00, 50, 10, 'Accessories', 'Bosch', 'ACC-HGR-001'),
('Handlebar Grips Gel', 'Gel comfort grips', 350.00, 210.00, 35, 5, 'Accessories', 'Bosch', 'ACC-HGG-001'),
('Handlebar Grips Heated', 'Heated grips with controller', 580.00, 350.00, 15, 3, 'Accessories', 'Bosch', 'ACC-HGH-001'),
('Mirror Set Round', 'Round chrome mirrors', 380.00, 230.00, 30, 5, 'Accessories', 'Bosch', 'ACC-MIR-001'),
('Mirror Set Rectangular', 'Rectangular mirrors', 420.00, 250.00, 25, 5, 'Accessories', 'Bosch', 'ACC-MIR-002'),
('Phone Mount Handlebar', 'Handlebar phone mount', 420.00, 250.00, 25, 5, 'Accessories', 'Bosch', 'ACC-PMH-001'),
('Phone Mount Suction', 'Suction cup phone mount', 380.00, 230.00, 20, 5, 'Accessories', 'Bosch', 'ACC-PMS-001'),
('USB Charger Dual Port', 'Dual USB charger', 350.00, 210.00, 30, 5, 'Accessories', 'Bosch', 'ACC-USC-001'),
('USB Charger Quick Charge', 'Quick charge 3.0 USB', 480.00, 290.00, 20, 5, 'Accessories', 'Bosch', 'ACC-USQ-001'),
('Tank Bag Medium', 'Medium magnetic tank bag', 890.00, 530.00, 15, 3, 'Accessories', 'Bosch', 'ACC-TBM-001'),
('Tank Bag Large', 'Large tank bag', 1200.00, 720.00, 10, 3, 'Accessories', 'Bosch', 'ACC-TBL-001'),
('Tail Bag Small', 'Small tail bag', 750.00, 450.00, 12, 3, 'Accessories', 'Bosch', 'ACC-TBS-001'),
('Tail Bag Medium', 'Medium tail bag', 980.00, 590.00, 10, 3, 'Accessories', 'Bosch', 'ACC-TBM2-001'),
('Windshield Small', 'Small fly screen', 1200.00, 720.00, 10, 3, 'Accessories', 'Bosch', 'ACC-WSS-001'),
('Windshield Medium', 'Medium windshield', 1500.00, 900.00, 8, 2, 'Accessories', 'Bosch', 'ACC-WSM-001'),
('Windshield Large', 'Large touring windshield', 1800.00, 1080.00, 6, 2, 'Accessories', 'Bosch', 'ACC-WSL-001'),
('Frame Sliders 4pcs', 'Frame slider set (4 pcs)', 650.00, 390.00, 20, 5, 'Accessories', 'Bosch', 'ACC-FRS-001'),
('Frame Sliders 2pcs', 'Frame slider set (2 pcs)', 380.00, 230.00, 25, 5, 'Accessories', 'Bosch', 'ACC-FRS-002'),
('Radiator Guard', 'Mesh radiator guard', 550.00, 330.00, 18, 5, 'Accessories', 'Bosch', 'ACC-RDG-001'),
('Chain Lube 150ml', 'Premium chain lubricant', 280.00, 170.00, 40, 10, 'Accessories', 'Castrol', 'ACC-CL1-001'),
('Chain Lube 400ml', 'Chain lubricant spray', 420.00, 250.00, 25, 5, 'Accessories', 'Castrol', 'ACC-CL2-001'),
('Helmet Lock Cable', 'Cable helmet lock', 180.00, 110.00, 35, 10, 'Accessories', 'Bosch', 'ACC-HLC-001'),
('Helmet Lock Combination', 'Combination helmet lock', 250.00, 150.00, 20, 5, 'Accessories', 'Bosch', 'ACC-HLC-002'),
('License Plate Holder Aluminum', 'Aluminum plate holder', 320.00, 190.00, 25, 5, 'Accessories', 'Bosch', 'ACC-LPH-001'),
('License Plate Holder Carbon', 'Carbon look plate holder', 450.00, 270.00, 15, 3, 'Accessories', 'Bosch', 'ACC-LPH-002'),
('Side Stand Steel', 'Steel side stand', 580.00, 350.00, 15, 3, 'Accessories', 'Bosch', 'ACC-SSS-001'),
('Side Stand Aluminum', 'Aluminum side stand', 780.00, 470.00, 10, 3, 'Accessories', 'Bosch', 'ACC-SSA-001'),
('Main Stand', 'Heavy duty main stand', 890.00, 530.00, 10, 3, 'Accessories', 'Bosch', 'ACC-MST-001'),
('Fender Eliminator Kit', 'Tail tidy kit', 650.00, 390.00, 12, 3, 'Accessories', 'Bosch', 'ACC-FEK-001'),
('Bar End Mirrors', 'Bar end mirrors pair', 480.00, 290.00, 18, 5, 'Accessories', 'Bosch', 'ACC-BEM-001'),
('Bar End Weights', 'Vibration dampening weights', 250.00, 150.00, 25, 5, 'Accessories', 'Bosch', 'ACC-BEW-001'),
('Tank Pad Protector', 'Tank protection pad', 180.00, 110.00, 30, 5, 'Accessories', 'Bosch', 'ACC-TPP-001'),
('Tank Grips Pair', 'Tank grip pads', 350.00, 210.00, 20, 5, 'Accessories', 'Bosch', 'ACC-TGK-001'),
('Footpeg Set Front', 'Front footpeg set', 420.00, 250.00, 15, 3, 'Accessories', 'Bosch', 'ACC-FPF-001'),
('Footpeg Set Rear', 'Rear footpeg set', 380.00, 230.00, 18, 5, 'Accessories', 'Bosch', 'ACC-FPR-001'),
('Rim Stickers Set', 'Reflective rim stickers', 150.00, 90.00, 40, 10, 'Accessories', 'Bosch', 'ACC-RST-001'),
('Valve Stem Caps', 'Anodized valve caps', 80.00, 45.00, 50, 10, 'Accessories', 'Bosch', 'ACC-VSC-001'),
('Motorcycle Keychain', 'Branded keychain', 120.00, 70.00, 45, 10, 'Accessories', 'Bosch', 'ACC-KEY-001'),
('Motorcycle Cover Medium', 'Medium motorcycle cover', 650.00, 390.00, 12, 3, 'Accessories', 'Bosch', 'ACC-MCM-001'),
('Motorcycle Cover Large', 'Large motorcycle cover', 850.00, 510.00, 10, 3, 'Accessories', 'Bosch', 'ACC-MCL-001');

-- TIRES (20 products)
INSERT INTO products (product_name, description, price, cost_price, stock, reorder_level, category, brand, sku) VALUES
('Front Tire 100/80-17', '100/80-17 front tire', 1900.00, 1140.00, 20, 5, 'Tires', 'Michelin', 'TIR-F1-001'),
('Front Tire 110/70-17', '110/70-17 front tire', 2100.00, 1260.00, 20, 5, 'Tires', 'Michelin', 'TIR-F2-001'),
('Front Tire 120/70-17', '120/70-17 front tire', 2200.00, 1320.00, 15, 3, 'Tires', 'Michelin', 'TIR-F3-001'),
('Front Tire 130/70-17', '130/70-17 front tire', 2400.00, 1440.00, 12, 3, 'Tires', 'Michelin', 'TIR-F4-001'),
('Rear Tire 130/70-17', '130/70-17 rear tire', 2300.00, 1380.00, 18, 5, 'Tires', 'Michelin', 'TIR-R1-001'),
('Rear Tire 140/70-17', '140/70-17 rear tire', 2500.00, 1500.00, 18, 5, 'Tires', 'Michelin', 'TIR-R2-001'),
('Rear Tire 150/70-17', '150/70-17 rear tire', 2800.00, 1680.00, 12, 3, 'Tires', 'Michelin', 'TIR-R3-001'),
('Rear Tire 160/60-17', '160/60-17 rear tire', 3000.00, 1800.00, 10, 3, 'Tires', 'Michelin', 'TIR-R4-001'),
('Rear Tire 180/55-17', '180/55-17 rear tire', 3500.00, 2100.00, 8, 2, 'Tires', 'Michelin', 'TIR-R5-001'),
('Tire Tube 17 inch', '17 inch inner tube', 350.00, 210.00, 30, 10, 'Tires', 'Michelin', 'TIR-TU1-001'),
('Tire Tube 18 inch', '18 inch inner tube', 380.00, 230.00, 25, 5, 'Tires', 'Michelin', 'TIR-TU2-001'),
('Tire Tube 16 inch', '16 inch inner tube', 320.00, 190.00, 20, 5, 'Tires', 'Michelin', 'TIR-TU3-001'),
('Tire Valve Set', 'Tire valve replacement set', 150.00, 90.00, 40, 10, 'Tires', 'Michelin', 'TIR-VAL-001'),
('Tire Repair Kit Plug', 'Plug type repair kit', 250.00, 150.00, 25, 5, 'Tires', 'Michelin', 'TIR-RPK-001'),
('Tire Repair Kit Patch', 'Patch type repair kit', 180.00, 110.00, 30, 5, 'Tires', 'Michelin', 'TIR-RPT-001'),
('Rim Tape 17 inch', '17 inch rim tape', 180.00, 110.00, 35, 10, 'Tires', 'Michelin', 'TIR-RT1-001'),
('Rim Tape 18 inch', '18 inch rim tape', 190.00, 110.00, 30, 5, 'Tires', 'Michelin', 'TIR-RT2-001'),
('Tire Changer Tool', 'Portable tire changer', 450.00, 270.00, 10, 3, 'Tires', 'Michelin', 'TIR-TCH-001'),
('Tire Balance Beads', 'Dynamic tire balancing beads', 220.00, 130.00, 20, 5, 'Tires', 'Michelin', 'TIR-BAL-001'),
('Tire Pressure Gauge Digital', 'Digital pressure gauge', 280.00, 170.00, 25, 5, 'Tires', 'Michelin', 'TIR-PGD-001');

-- LUBRICANTS (20 products)
INSERT INTO products (product_name, description, price, cost_price, stock, reorder_level, category, brand, sku) VALUES
('Engine Oil 10W-40 1L', '1L 10W-40 engine oil', 320.00, 190.00, 45, 10, 'Lubricants', 'Castrol', 'LUB-E1-001'),
('Engine Oil 10W-40 4L', '4L 10W-40 engine oil', 1200.00, 720.00, 20, 5, 'Lubricants', 'Castrol', 'LUB-E2-001'),
('Engine Oil 20W-50 1L', '1L 20W-50 engine oil', 300.00, 180.00, 35, 10, 'Lubricants', 'Castrol', 'LUB-E3-001'),
('Engine Oil 20W-50 4L', '4L 20W-50 engine oil', 1150.00, 690.00, 15, 3, 'Lubricants', 'Castrol', 'LUB-E4-001'),
('Engine Oil 5W-40 1L', '1L 5W-40 synthetic oil', 350.00, 210.00, 30, 5, 'Lubricants', 'Castrol', 'LUB-E5-001'),
('Engine Oil 5W-40 4L', '4L 5W-40 synthetic oil', 1300.00, 780.00, 12, 3, 'Lubricants', 'Castrol', 'LUB-E6-001'),
('Chain Oil Aerosol', 'Chain oil spray', 280.00, 170.00, 30, 5, 'Lubricants', 'Castrol', 'LUB-CH1-001'),
('Chain Oil Bottle', 'Chain oil bottle', 220.00, 130.00, 25, 5, 'Lubricants', 'Castrol', 'LUB-CH2-001'),
('Brake Fluid DOT 3', 'DOT 3 brake fluid', 250.00, 150.00, 25, 5, 'Lubricants', 'Castrol', 'LUB-BF1-001'),
('Brake Fluid DOT 4', 'DOT 4 brake fluid', 280.00, 170.00, 20, 5, 'Lubricants', 'Castrol', 'LUB-BF2-001'),
('Fork Oil 1L', '1L fork oil', 450.00, 270.00, 18, 5, 'Lubricants', 'Castrol', 'LUB-FO1-001'),
('Fork Oil 500ml', '500ml fork oil', 250.00, 150.00, 22, 5, 'Lubricants', 'Castrol', 'LUB-FO2-001'),
('Gear Oil 80W-90 1L', '1L gear oil', 380.00, 230.00, 20, 5, 'Lubricants', 'Castrol', 'LUB-GO1-001'),
('Gear Oil 85W-140 1L', '1L heavy duty gear oil', 420.00, 250.00, 15, 3, 'Lubricants', 'Castrol', 'LUB-GO2-001'),
('Coolant 1L', '1L coolant concentrate', 220.00, 130.00, 30, 5, 'Lubricants', 'Castrol', 'LUB-CO1-001'),
('Coolant 4L', '4L coolant concentrate', 780.00, 470.00, 12, 3, 'Lubricants', 'Castrol', 'LUB-CO2-001'),
('Chain Wax Aerosol', 'Chain wax spray', 350.00, 210.00, 25, 5, 'Lubricants', 'Castrol', 'LUB-CW1-001'),
('Grease Multi-Purpose', 'Multi-purpose grease', 180.00, 110.00, 35, 10, 'Lubricants', 'Castrol', 'LUB-GR1-001'),
('Brake Cleaner Aerosol', 'Brake cleaner spray', 280.00, 170.00, 20, 5, 'Lubricants', 'Castrol', 'LUB-BC1-001'),
('Carburetor Cleaner', 'Carburetor cleaner spray', 320.00, 190.00, 18, 5, 'Lubricants', 'Castrol', 'LUB-CC1-001');

-- DRIVE TRAIN (25 products)
INSERT INTO products (product_name, description, price, cost_price, stock, reorder_level, category, brand, sku) VALUES
('Chain & Sprocket Set Standard', 'Standard chain and sprocket set', 890.00, 530.00, 15, 3, 'Drive Train', 'Bosch', 'DRV-CS1-001'),
('Chain & Sprocket Set Heavy Duty', 'Heavy duty chain and sprocket', 1200.00, 720.00, 10, 3, 'Drive Train', 'Bosch', 'DRV-CS2-001'),
('Front Sprocket 14T', '14 tooth front sprocket', 380.00, 230.00, 20, 5, 'Drive Train', 'Bosch', 'DRV-FS1-001'),
('Front Sprocket 15T', '15 tooth front sprocket', 380.00, 230.00, 18, 5, 'Drive Train', 'Bosch', 'DRV-FS2-001'),
('Front Sprocket 16T', '16 tooth front sprocket', 400.00, 240.00, 15, 3, 'Drive Train', 'Bosch', 'DRV-FS3-001'),
('Rear Sprocket 40T', '40 tooth rear sprocket', 550.00, 330.00, 18, 5, 'Drive Train', 'Bosch', 'DRV-RS1-001'),
('Rear Sprocket 42T', '42 tooth rear sprocket', 550.00, 330.00, 16, 3, 'Drive Train', 'Bosch', 'DRV-RS2-001'),
('Rear Sprocket 44T', '44 tooth rear sprocket', 580.00, 350.00, 14, 3, 'Drive Train', 'Bosch', 'DRV-RS3-001'),
('Drive Chain O-Ring 520', '520 O-ring drive chain', 680.00, 410.00, 15, 3, 'Drive Train', 'Bosch', 'DRV-DC1-001'),
('Drive Chain O-Ring 525', '525 O-ring drive chain', 720.00, 430.00, 12, 3, 'Drive Train', 'Bosch', 'DRV-DC2-001'),
('Drive Chain X-Ring 520', '520 X-ring chain', 850.00, 510.00, 10, 3, 'Drive Train', 'Bosch', 'DRV-DC3-001'),
('Clutch Cable Standard', 'Standard clutch cable', 280.00, 170.00, 25, 5, 'Drive Train', 'Bosch', 'DRV-CC1-001'),
('Clutch Cable Heavy Duty', 'Heavy duty clutch cable', 380.00, 230.00, 18, 5, 'Drive Train', 'Bosch', 'DRV-CC2-001'),
('Throttle Cable Standard', 'Standard throttle cable', 250.00, 150.00, 30, 5, 'Drive Train', 'Bosch', 'DRV-TC1-001'),
('Throttle Cable Heavy Duty', 'Heavy duty throttle cable', 320.00, 190.00, 20, 5, 'Drive Train', 'Bosch', 'DRV-TC2-001'),
('Clutch Plate Set Friction', 'Friction clutch plates', 980.00, 590.00, 10, 3, 'Drive Train', 'Bosch', 'DRV-CPF-001'),
('Clutch Plate Set Steel', 'Steel clutch plates', 650.00, 390.00, 12, 3, 'Drive Train', 'Bosch', 'DRV-CPS-001'),
('Clutch Spring Set Heavy Duty', 'Heavy duty clutch springs', 450.00, 270.00, 15, 3, 'Drive Train', 'Bosch', 'DRV-CSH-001'),
('Clutch Spring Set Standard', 'Standard clutch springs', 350.00, 210.00, 18, 5, 'Drive Train', 'Bosch', 'DRV-CSS-001'),
('Clutch Basket Assembly', 'Complete clutch basket', 1500.00, 900.00, 8, 2, 'Drive Train', 'Bosch', 'DRV-CBA-001'),
('Clutch Pressure Plate', 'Clutch pressure plate', 550.00, 330.00, 12, 3, 'Drive Train', 'Bosch', 'DRV-CPP-001'),
('Clutch Push Rod', 'Clutch release push rod', 250.00, 150.00, 15, 3, 'Drive Train', 'Bosch', 'DRV-CPR-001'),
('Clutch Lever Assembly', 'Complete clutch lever', 450.00, 270.00, 15, 3, 'Drive Train', 'Bosch', 'DRV-CLA-001'),
('Gear Shift Linkage', 'Gear shift linkage rod', 380.00, 230.00, 12, 3, 'Drive Train', 'Bosch', 'DRV-GSL-001'),
('Gear Shift Pedal', 'Gear shift pedal assembly', 320.00, 190.00, 18, 5, 'Drive Train', 'Bosch', 'DRV-GSP-001');

-- TOOLS (20 products)
INSERT INTO products (product_name, description, price, cost_price, stock, reorder_level, category, brand, sku) VALUES
('Tire Pressure Gauge Analog', 'Analog tire pressure gauge', 280.00, 170.00, 30, 5, 'Tools', 'Bosch', 'TOL-TPG-001'),
('Tire Pressure Gauge Digital', 'Digital tire pressure gauge', 380.00, 230.00, 20, 5, 'Tools', 'Bosch', 'TOL-TPD-001'),
('Chain Breaker Tool', 'Chain breaker and rivet tool', 650.00, 390.00, 15, 3, 'Tools', 'Bosch', 'TOL-CBT-001'),
('Chain Riveting Tool', 'Chain riveting tool', 890.00, 530.00, 10, 3, 'Tools', 'Bosch', 'TOL-CRT-001'),
('Spark Plug Wrench', 'Spark plug socket wrench', 180.00, 110.00, 40, 10, 'Tools', 'Bosch', 'TOL-SPW-001'),
('Allen Key Set Metric', 'Metric allen key set', 350.00, 210.00, 25, 5, 'Tools', 'Bosch', 'TOL-AKM-001'),
('Allen Key Set SAE', 'SAE allen key set', 350.00, 210.00, 20, 5, 'Tools', 'Bosch', 'TOL-AKS-001'),
('Socket Set 1/4 inch', '1/4 inch socket set', 890.00, 530.00, 12, 3, 'Tools', 'Bosch', 'TOL-SS1-001'),
('Socket Set 3/8 inch', '3/8 inch socket set', 1200.00, 720.00, 10, 3, 'Tools', 'Bosch', 'TOL-SS2-001'),
('Torque Wrench 1/4 inch', '1/4 inch torque wrench', 1200.00, 720.00, 10, 3, 'Tools', 'Bosch', 'TOL-TW1-001'),
('Torque Wrench 3/8 inch', '3/8 inch torque wrench', 1500.00, 900.00, 8, 2, 'Tools', 'Bosch', 'TOL-TW2-001'),
('Battery Charger 6V/12V', '6V/12V battery charger', 1500.00, 900.00, 8, 2, 'Tools', 'Bosch', 'TOL-BCH-001'),
('Battery Charger Smart', 'Smart battery charger', 1800.00, 1080.00, 6, 2, 'Tools', 'Bosch', 'TOL-BCS-001'),
('Tire Inflator 12V', '12V portable inflator', 980.00, 590.00, 10, 3, 'Tools', 'Bosch', 'TOL-TI1-001'),
('Tire Inflator Manual', 'Manual tire pump', 450.00, 270.00, 15, 3, 'Tools', 'Bosch', 'TOL-TI2-001'),
('Feeler Gauge Set', 'Feeler gauge set', 250.00, 150.00, 20, 5, 'Tools', 'Bosch', 'TOL-FGS-001'),
('Valve Adjustment Tool', 'Valve adjustment tool', 380.00, 230.00, 15, 3, 'Tools', 'Bosch', 'TOL-VAT-001'),
('Flywheel Puller', 'Flywheel puller tool', 420.00, 250.00, 12, 3, 'Tools', 'Bosch', 'TOL-FWP-001'),
('Oil Filter Wrench', 'Oil filter removal wrench', 280.00, 170.00, 25, 5, 'Tools', 'Bosch', 'TOL-OFW-001'),
('Cable Lubricator Tool', 'Cable lubrication tool', 220.00, 130.00, 18, 5, 'Tools', 'Bosch', 'TOL-CLT-001');

-- =============================================
-- SAMPLE ORDERS
-- =============================================
INSERT INTO orders (user_id, order_number, total_amount, subtotal, shipping_fee, status, payment_method, delivery_address, order_date) VALUES
(3, 'ORD-20260101-ABC123', 1250.00, 1200.00, 50.00, 'delivered', 'cash', '123 Mabini St., Manila, Metro Manila', '2026-01-01 10:30:00'),
(3, 'ORD-20260105-DEF456', 3200.00, 3000.00, 200.00, 'shipped', 'gcash', '123 Mabini St., Manila, Metro Manila', '2026-01-05 14:20:00'),
(4, 'ORD-20260110-GHI789', 2100.00, 2000.00, 100.00, 'confirmed', 'bank_transfer', '456 Rizal Ave., Quezon City, Metro Manila', '2026-01-10 09:15:00'),
(5, 'ORD-20260115-JKL012', 540.00, 540.00, 0.00, 'pending', 'cash', '789 Bonifacio St., Pasig, Metro Manila', '2026-01-15 16:45:00'),
(3, 'ORD-20260120-MNO345', 450.00, 450.00, 0.00, 'processing', 'card', '123 Mabini St., Manila, Metro Manila', '2026-01-20 11:00:00');

-- =============================================
-- SAMPLE ORDER ITEMS
-- =============================================
INSERT INTO order_items (order_id, product_id, quantity, price, subtotal) VALUES
(1, 1, 2, 450.00, 900.00),
(1, 11, 1, 120.00, 120.00),
(1, 15, 1, 180.00, 180.00),
(2, 5, 1, 1200.00, 1200.00),
(2, 6, 1, 1500.00, 1500.00),
(2, 11, 2, 120.00, 240.00),
(3, 9, 1, 2100.00, 2100.00),
(4, 12, 3, 180.00, 540.00),
(5, 13, 1, 450.00, 450.00);

-- =============================================
-- SAMPLE CART ITEMS
-- =============================================
INSERT INTO cart_items (user_id, product_id, quantity) VALUES
(3, 1, 1),
(3, 5, 1),
(4, 9, 2),
(5, 11, 3);

-- =============================================
-- SAMPLE NOTIFICATIONS
-- =============================================
INSERT INTO notifications (user_id, order_id, type, title, message, status) VALUES
(3, 1, 'order_update', 'Order Delivered', 'Your order ORD-20260101-ABC123 has been delivered successfully!', 'read'),
(3, 2, 'order_update', 'Order Shipped', 'Your order ORD-20260105-DEF456 has been shipped. Track your delivery.', 'unread'),
(4, 3, 'order_update', 'Order Confirmed', 'Your order ORD-20260110-GHI789 has been confirmed. We are preparing your items.', 'unread');

-- =============================================
-- SAMPLE COUPONS
-- =============================================
INSERT INTO coupons (coupon_code, discount_type, discount_value, min_order_amount, start_date, end_date, usage_limit) VALUES
('WELCOME10', 'percentage', 10.00, 1000.00, '2026-01-01', '2026-12-31', 1),
('FREESHIP', 'percentage', 0.00, 5000.00, '2026-01-01', '2026-12-31', 1),
('SAVE20', 'percentage', 20.00, 2000.00, '2026-02-01', '2026-03-31', 1),
('DISCOUNT50', 'fixed', 50.00, 500.00, '2026-01-01', '2026-12-31', 1);

-- =============================================
-- SAMPLE REVIEWS
-- =============================================
INSERT INTO reviews (product_id, user_id, order_id, rating, comment, status) VALUES
(1, 3, 1, 5, 'Excellent brake pads! Very responsive and quiet.', 'approved'),
(5, 3, 2, 4, 'Good quality, fast delivery.', 'approved'),
(9, 4, 3, 5, 'Tire grips well and looks great!', 'approved'),
(11, 5, 4, 3, 'Fair price but took a while to arrive.', 'pending');

-- =============================================
-- VIEW ALL DATA SUMMARY
-- =============================================
SELECT '=== DATABASE SUMMARY ===' as '';
SELECT COUNT(*) as total_users FROM users;
SELECT COUNT(*) as total_products FROM products;
SELECT COUNT(*) as total_orders FROM orders;
SELECT COUNT(*) as total_categories FROM categories;
SELECT COUNT(*) as total_brands FROM brands;
SELECT COUNT(*) as total_reviews FROM reviews;
SELECT COUNT(*) as total_coupons FROM coupons;

SELECT '=== PRODUCTS BY CATEGORY ===' as '';
SELECT category, COUNT(*) as count FROM products GROUP BY category ORDER BY count DESC;

SELECT '=== ORDERS BY STATUS ===' as '';
SELECT status, COUNT(*) as count FROM orders GROUP BY status ORDER BY count DESC;

-- =============================================
-- END OF DATABASE
-- =============================================