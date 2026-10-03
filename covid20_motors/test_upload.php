<?php
// test_upload.php - Check if uploads folder is working

echo "<h2>Uploads Folder Test</h2>";

// Check if uploads folder exists
$uploadDir = 'uploads/';
if (file_exists($uploadDir)) {
    echo "<p style='color:green'>✅ Uploads folder exists at: " . realpath($uploadDir) . "</p>";
    
    // Check if writable
    if (is_writable($uploadDir)) {
        echo "<p style='color:green'>✅ Uploads folder is writable</p>";
    } else {
        echo "<p style='color:red'>❌ Uploads folder is NOT writable. Please set permissions to 755 or 777</p>";
    }
} else {
    echo "<p style='color:red'>❌ Uploads folder does NOT exist at: " . $uploadDir . "</p>";
    echo "<p>Creating uploads folder...</p>";
    if (mkdir($uploadDir, 0777, true)) {
        echo "<p style='color:green'>✅ Uploads folder created successfully!</p>";
    } else {
        echo "<p style='color:red'>❌ Failed to create uploads folder. Please create it manually.</p>";
    }
}

// List files in uploads folder
echo "<h3>Files in Uploads Folder:</h3>";
if (is_dir($uploadDir)) {
    $files = scandir($uploadDir);
    $found = false;
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "<p>📁 <a href='$uploadDir$file' target='_blank'>$file</a></p>";
            $found = true;
        }
    }
    if (!$found) {
        echo "<p>No files found in uploads folder.</p>";
    }
}

// Test upload form
echo "<h3>Test Upload Form:</h3>";
echo "<form method='POST' enctype='multipart/form-data'>";
echo "<input type='file' name='test_image' accept='image/*'>";
echo "<button type='submit' name='upload'>Upload Test Image</button>";
echo "</form>";

// Handle test upload
if (isset($_POST['upload']) && isset($_FILES['test_image']) && $_FILES['test_image']['error'] === UPLOAD_ERR_OK) {
    $fileName = time() . '_' . $_FILES['test_image']['name'];
    $filePath = $uploadDir . $fileName;
    
    if (move_uploaded_file($_FILES['test_image']['tmp_name'], $filePath)) {
        echo "<p style='color:green'>✅ Test image uploaded successfully!</p>";
        echo "<p>File: <a href='$filePath' target='_blank'>$fileName</a></p>";
    } else {
        echo "<p style='color:red'>❌ Failed to upload test image.</p>";
    }
}

// Check database products with images
echo "<h3>Products with Images in Database:</h3>";

require_once __DIR__ . '/database_config.php';

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->query("SELECT id, name, image FROM products WHERE image IS NOT NULL AND image != '' LIMIT 10");
    $products = $stmt->fetchAll();
    
    if (count($products) > 0) {
        echo "<table border='1' cellpadding='8'>";
        echo "<tr><th>ID</th><th>Product Name</th><th>Image Path</th><th>Preview</th></tr>";
        foreach ($products as $p) {
            $imagePath = $p['image'];
            $preview = '';
            if (file_exists($imagePath)) {
                $preview = "<img src='$imagePath' width='50' height='50' style='object-fit:cover'>";
            } else {
                $preview = "<span style='color:red'>File not found</span>";
            }
            echo "<tr>";
            echo "<td>{$p['id']}</td>";
            echo "<td>{$p['name']}</td>";
            echo "<td>{$imagePath}</td>";
            echo "<td>$preview</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No products with images found in database.</p>";
    }
} catch(PDOException $e) {
    echo "<p style='color:red'>Database error: " . $e->getMessage() . "</p>";
}
?>