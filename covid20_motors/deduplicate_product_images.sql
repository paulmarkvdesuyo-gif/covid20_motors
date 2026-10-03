-- Run after selecting the configured database.
-- The source and destination files in each group have identical SHA-256 hashes.
START TRANSACTION;

UPDATE products
SET image = 'uploads/product_images/product_99_1786897012.jpg'
WHERE id = 100 AND image = 'uploads/product_images/product_100_1786897009.jpg';

UPDATE products
SET image = 'uploads/product_images/product_99_1786897012.jpg'
WHERE id = 101 AND image = 'uploads/product_images/product_101_1786897023.jpg';

UPDATE products
SET image = 'uploads/product_images/product_105_1786897115.jpg'
WHERE id = 106 AND image = 'uploads/product_images/product_106_1786897121.jpg';

UPDATE products
SET image = 'uploads/product_images/product_42_1786894705.jpg'
WHERE id = 155 AND image = 'uploads/product_images/product_155_1786898810.jpg';

COMMIT;