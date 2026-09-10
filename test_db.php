<?php
require_once 'config/database.php';

echo "<h2>✅ Database Connection Test</h2>";

// Check connection
if ($conn && !$conn->connect_error) {
    echo "<p style='color:green;font-size:20px;'>✅ Connected successfully to database!</p>";
    
    // Show database info
    $result = $conn->query("SELECT DATABASE() as db");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "<p><strong>Database:</strong> " . $row['db'] . "</p>";
    }
    
    // Show tables
    $result = $conn->query("SHOW TABLES");
    if ($result && $result->num_rows > 0) {
        echo "<h3>📋 Tables in database:</h3>";
        echo "<ul>";
        while ($row = $result->fetch_array()) {
            echo "<li>" . $row[0] . "</li>";
        }
        echo "</ul>";
    }
    
    echo "<p><a href='index.php'>🏠 Back to Home</a></p>";
} else {
    echo "<p style='color:red;'>❌ Connection failed: " . $conn->connect_error . "</p>";
}
?>