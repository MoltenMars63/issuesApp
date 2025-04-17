<?php
// Start session
session_start();

// Enable error logging
error_log("Starting add_comment.php");
error_log("POST data: " . print_r($_POST, true));

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    error_log("User not logged in, redirecting to login.php");
    header('Location: login.php');
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['iss_id'])) {
    error_log("Invalid request method or missing iss_id, redirecting to issues_list.php");
    header('Location: issues_list.php');
    exit();
}

require '../database/database.php';
$pdo = Database::connect();

// Get current user ID from session
$current_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
error_log("Current user ID: " . $current_user_id);

try {
    // Validate required fields
    if (!isset($_POST['short_comment'])) {
        $_SESSION['error'] = "Missing required fields";
        error_log("Missing required fields, redirecting to issues_list.php");
        header('Location: issues_list.php');
        exit();
    }
    
    $issId = (int)$_POST['iss_id'];
    $shortComment = trim($_POST['short_comment']);
    // Long comment is optional
    $longComment = isset($_POST['long_comment']) ? trim($_POST['long_comment']) : '';
    
    error_log("Processing comment for issue ID: " . $issId);
    error_log("Short comment: " . $shortComment);
    error_log("Long comment: " . $longComment);
    
    // Validate inputs
    if (empty($shortComment)) {
        $_SESSION['error'] = "Short comment cannot be empty";
        error_log("Short comment is empty, redirecting to issue_detail.php?id=" . $issId);
        header('Location: issue_detail.php?id=' . $issId);
        exit();
    }
    
    // Insert the new comment
    $stmt = $pdo->prepare("
        INSERT INTO iss_com 
        (iss_id, per_id, short_comment, long_comment, posted_date, creator_id) 
        VALUES 
        (:iss_id, :per_id, :short_comment, :long_comment, CURDATE(), :creator_id)
    ");
    
    $stmt->bindParam(':iss_id', $issId, PDO::PARAM_INT);
    $stmt->bindParam(':per_id', $current_user_id, PDO::PARAM_INT);
    $stmt->bindParam(':short_comment', $shortComment, PDO::PARAM_STR);
    $stmt->bindParam(':long_comment', $longComment, PDO::PARAM_STR);
    $stmt->bindParam(':creator_id', $current_user_id, PDO::PARAM_INT);
    
    $result = $stmt->execute();
    error_log("Insert comment result: " . ($result ? "success" : "failure"));
    
    if ($result) {
        // Add success message
        $_SESSION['success'] = "Comment added successfully";
        error_log("Comment added successfully, redirecting to issues_list.php");
    } else {
        $_SESSION['error'] = "Failed to add comment";
        error_log("Failed to add comment, redirecting to issues_list.php");
    }
    
    // Redirect back to issue list
    header('Location: issues_list.php');
    exit();

} catch (PDOException $e) {
    error_log("PDO Exception: " . $e->getMessage());
    $_SESSION['error'] = "Error adding comment: " . $e->getMessage();
    header('Location: issues_list.php');
    exit();
} catch (Exception $e) {
    error_log("General Exception: " . $e->getMessage());
    $_SESSION['error'] = "Error adding comment: " . $e->getMessage();
    header('Location: issues_list.php');
    exit();
}
?>
