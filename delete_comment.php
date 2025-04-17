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

// Store current user id from session
$current_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

// Check if current user is admin
$isAdmin = false;
try {
    $stmt = $pdo->prepare("SELECT admin FROM iss_per WHERE id = :user_id");
    $stmt->bindParam(':user_id', $current_user_id, PDO::PARAM_INT);
    $stmt->execute();
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    $isAdmin = $userData && isset($userData['admin']) && $userData['admin'] === 'Y';
} catch (PDOException $e) {
    $error = "Database error " . $e->getMessage();
}

try {
    $commentId = (int)$_POST['comment_id'];
    
    // Check if current user is the comment creator
    $stmt = $pdo->prepare("SELECT creator_id FROM iss_com WHERE id = :comment_id");
    $stmt->bindParam(':comment_id', $commentId, PDO::PARAM_INT);
    $stmt->execute();
    $comment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Only proceed if user is admin or comment creator
    if ($isAdmin || ($comment && $comment['creator_id'] == $current_user_id)) {
        // Delete the comment
        $stmt = $pdo->prepare("DELETE FROM iss_com WHERE id = :comment_id");
        $stmt->bindParam(':comment_id', $commentId, PDO::PARAM_INT);
        $stmt->execute();
    } else {
        $_SESSION['error'] = "You don't have permission to delete this comment";
    }
    
    // Redirect back to issue list
    header('Location: issues_list.php');
} catch (PDOException $e) {
    $_SESSION['error'] = "Error deleting comment: " . $e->getMessage();
    header('Location: issues_list.php');
}
?>
