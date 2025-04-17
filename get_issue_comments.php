<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not authorized']);
    exit();
}

// Check if issue_id is provided
if (!isset($_GET['issue_id']) || !is_numeric($_GET['issue_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid issue ID']);
    exit();
}

// Include database connection
require '../database/database.php';
$pdo = Database::connect();

// Get comments for the specific issue
try {
    $issueId = (int)$_GET['issue_id'];
    
    $stmt = $pdo->prepare("
    SELECT c.id, c.short_comment, c.long_comment, c.posted_date, 
           CONCAT(p.fname, ' ', p.lname) as commenter_name
    FROM iss_com c
    LEFT JOIN iss_per p ON c.per_id = p.id
    WHERE c.iss_id = :issueId
    ORDER BY c.posted_date DESC
");
    $stmt->bindParam(':issueId', $issueId, PDO::PARAM_INT);
    $stmt->execute();
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return comments as JSON
    header('Content-Type: application/json');
    echo json_encode($comments);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
