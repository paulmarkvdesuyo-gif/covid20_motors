<?php
echo "Uploads folder exists: " . (file_exists('uploads/') ? 'YES' : 'NO') . "<br>";
echo "Uploads folder writable: " . (is_writable('uploads/') ? 'YES' : 'NO') . "<br>";
echo "Full path: " . realpath('uploads/') . "<br>";
?>