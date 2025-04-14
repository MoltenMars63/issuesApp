<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['comment_id'])) {
    header('Location: issues_list.php');
    exit();
}

require '../database/database.php';
$pdo = Database::connect();

try {
    $commentId = (int)$_POST['comment_id'];
    
    // Delete the comment
    $stmt = $pdo->prepare("DELETE FROM iss_com WHERE id = :comment_id");
    $stmt->bindParam(':comment_id', $commentId, PDO::PARAM_INT);
    $stmt->execute();
    
    // Redirect back to issue list
    header('Location: issues_list.php');

} catch (PDOException $e) {
    $_SESSION['error'] = "Error deleting comment: " . $e->getMessage();
    header('Location: issues_list.php');
}
?>