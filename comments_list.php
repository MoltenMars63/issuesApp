<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Redirect to login page if not logged in
    header('Location: login.php');
    exit();
}

// Include database connection
require '../database/database.php';
$pdo = Database::connect();

// Get all comments from the database
try {
    $stmt = $pdo->prepare("
        SELECT c.id, c.iss_id, c.short_comment, c.long_comment, c.posted_date, 
               CONCAT(p.fname, ' ', p.lname) as posted_by,
               i.short_description as issue_description
        FROM iss_com c
        LEFT JOIN iss_per p ON c.per_id = p.id
        LEFT JOIN iss_iss i ON c.iss_id = i.id
        ORDER BY c.posted_date DESC
    ");
    $stmt->execute();
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all persons for the dropdown
    $stmt = $pdo->prepare("SELECT id, CONCAT(fname, ' ', lname) as full_name, email FROM iss_per ORDER BY fname, lname");
    $stmt->execute();
    $persons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all issues for the dropdown
    $stmt = $pdo->prepare("SELECT id, short_description FROM iss_iss ORDER BY project, short_description");
    $stmt->execute();
    $issues = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Process form submission for adding new comment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    try {
        $short_comment = trim($_POST['short_comment']);
        $long_comment = trim($_POST['long_comment']);
        $posted_date = $_POST['posted_date'];
        $iss_id = $_POST['iss_id'];
        $per_id = $_POST['per_id'];
        
        // Validate inputs
        if (empty($short_comment) || empty($posted_date) || empty($iss_id) || empty($per_id)) {
            $error = "All required fields must be filled out";
        } else {
            // Insert new comment
            $stmt = $pdo->prepare("
                INSERT INTO iss_com (short_comment, long_comment, posted_date, iss_id, per_id)
                VALUES (:short_comment, :long_comment, :posted_date, :iss_id, :per_id)
            ");
            
            $stmt->bindParam(':short_comment', $short_comment, PDO::PARAM_STR);
            $stmt->bindParam(':long_comment', $long_comment, PDO::PARAM_STR);
            $stmt->bindParam(':posted_date', $posted_date, PDO::PARAM_STR);
            $stmt->bindParam(':iss_id', $iss_id, PDO::PARAM_INT);
            $stmt->bindParam(':per_id', $per_id, PDO::PARAM_INT);
            
            $stmt->execute();
            
            // Redirect to refresh the page and show the new comment
            header('Location: comments_list.php');
            exit();
        }
    } catch (PDOException $e) {
        $error = "Error adding comment: " . $e->getMessage();
    }
}

// Process form submission for updating comment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_comment'])) {
    try {
        $id = $_POST['comment_id'];
        $short_comment = trim($_POST['short_comment']);
        $long_comment = trim($_POST['long_comment']);
        $posted_date = $_POST['posted_date'];
        $iss_id = $_POST['iss_id'];
        $per_id = $_POST['per_id'];
        
        // Validate inputs
        if (empty($short_comment) || empty($posted_date) || empty($iss_id) || empty($per_id)) {
            $error = "All required fields must be filled out";
        } else {
            // Update the comment
            $stmt = $pdo->prepare("
                UPDATE iss_com 
                SET short_comment = :short_comment,
                    long_comment = :long_comment,
                    posted_date = :posted_date,
                    iss_id = :iss_id,
                    per_id = :per_id
                WHERE id = :id
            ");
            
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':short_comment', $short_comment, PDO::PARAM_STR);
            $stmt->bindParam(':long_comment', $long_comment, PDO::PARAM_STR);
            $stmt->bindParam(':posted_date', $posted_date, PDO::PARAM_STR);
            $stmt->bindParam(':iss_id', $iss_id, PDO::PARAM_INT);
            $stmt->bindParam(':per_id', $per_id, PDO::PARAM_INT);
            
            $stmt->execute();
            
            // Redirect to refresh the page
            header('Location: comments_list.php');
            exit();
        }
    } catch (PDOException $e) {
        $error = "Error updating comment: " . $e->getMessage();
    }
}

// Process form submission for deleting comment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_comment'])) {
    try {
        $id = $_POST['comment_id'];
        
        // Delete the comment
        $stmt = $pdo->prepare("DELETE FROM iss_com WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        // Redirect to refresh the page
        header('Location: comments_list.php');
        exit();
    } catch (PDOException $e) {
        $error = "Error deleting comment: " . $e->getMessage();
    }
}

// Format date function
function formatDate($date) {
    return $date ? date('m/d/Y', strtotime($date)) : '';
}

// Format date for input field
function formatDateForInput($date) {
    return $date ? date('Y-m-d', strtotime($date)) : '';
}

// Get user name
$userName = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'User';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department Status Report - Comments List</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .header h2 {
            margin: 0;
            color: #333;
        }
        .user-info {
            display: flex;
            align-items: center;
        }
        .logout-btn, .issues-btn {
            margin-left: 15px;
            padding: 5px 10px;
            background-color: #f44336;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }
        .issues-btn {
            background-color: #2196F3;
        }
        .add-btn {
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            display: flex;
            align-items: center;
        }
        .add-btn:hover {
            background-color: #45a049;
        }
        .add-icon {
            font-size: 20px;
            margin-right: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        .actions {
            display: flex;
            gap: 10px;
        }
        .action-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            color: white;
            text-decoration: none;
            font-size: 14px;
        }
        .read-btn {
            background-color: #2196F3;
        }
        .update-btn {
            background-color: #ff9800;
        }
        .delete-btn {
            background-color: #f44336;
        }
        .no-persons {
            text-align: center;
            padding: 20px;
            font-style: italic;
            color: #666;
        }
        .error {
            color: #f44336;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #f44336;
            border-radius: 4px;
            background-color: #ffebee;
        }
        
        /* Modal styles - very similar to issues_list.php */
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 60%;
            max-width: 700px;
            border-radius: 5px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-control {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        .checkbox-group input {
            margin-right: 10px;
        }
        .form-actions {
            text-align: right;
            margin-top: 20px;
        }
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-primary {
            background-color: #4CAF50;
            color: white;
        }
        .btn-warning {
            background-color: #ff9800;
            color: white;
        }
        .btn-danger {
            background-color: #f44336;
            color: white;
        }
        .btn-secondary {
            background-color: #ccc;
            color: #333;
            margin-right: 10px;
        }
        .delete-warning {
            background-color: #ffebee;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            border-left: 5px solid #f44336;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Department Status Report - Comments List</h2>
            <div class="user-info">
                Welcome, <?php echo htmlspecialchars($userName); ?>
                <a href="logout.php" class="logout-btn">Logout</a>
                <a href="issues_list.php" class="issues-btn">Back to Issues</a>
            </div>
        </div>
        
        <button id="openAddModal" class="add-btn">
            <span class="add-icon">+</span> Add New Comment
        </button>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (empty($comments)): ?>
            <div class="no-issues">No comments found in the system.</div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Issue</th>
                        <th>Short Comment</th>
                        <th>Posted Date</th>
                        <th>Posted By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($comments as $comment): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($comment['id']); ?></td>
                            <td><?php echo htmlspecialchars($comment['issue_description']); ?></td>
                            <td><?php echo htmlspecialchars($comment['short_comment']); ?></td>
                            <td><?php echo formatDate($comment['posted_date']); ?></td>
                            <td><?php echo htmlspecialchars($comment['posted_by']); ?></td>
                            <td class="actions">
                                <button class="action-btn read-btn" 
                                    data-id="<?php echo $comment['id']; ?>"
                                    data-shortcomment="<?php echo htmlspecialchars($comment['short_comment']); ?>"
                                    data-longcomment="<?php echo htmlspecialchars($comment['long_comment']); ?>"
                                    data-issueid="<?php echo htmlspecialchars($comment['iss_id']); ?>"
                                    data-issueDesc="<?php echo htmlspecialchars($comment['issue_description']); ?>"
                                    data-posteddate="<?php echo formatDateForInput($comment['posted_date']); ?>"
                                    data-postedby="<?php echo htmlspecialchars($comment['posted_by']); ?>">R</button>
                                <button class="action-btn update-btn"
                                    data-id="<?php echo $comment['id']; ?>"
                                    data-shortcomment="<?php echo htmlspecialchars($comment['short_comment']); ?>"
                                    data-longcomment="<?php echo htmlspecialchars($comment['long_comment']); ?>"
                                    data-issueid="<?php echo htmlspecialchars($comment['iss_id']); ?>"
                                    data-posteddate="<?php echo formatDateForInput($comment['posted_date']); ?>">U</button>
                                <button class="action-btn delete-btn"
                                    data-id="<?php echo $comment['id']; ?>"
                                    data-shortcomment="<?php echo htmlspecialchars($comment['short_comment']); ?>"
                                    data-issuedesc="<?php echo htmlspecialchars($comment['issue_description']); ?>">D</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    
    <!-- Add Comment Modal -->
    <div id="addCommentModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Add New Comment</h3>
            <form method="post" action="comments_list.php">
                <div class="form-group">
                    <label for="short_comment">Short Comment *</label>
                    <input type="text" id="short_comment" name="short_comment" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="long_comment">Long Comment</label>
                    <textarea id="long_comment" name="long_comment" class="form-control"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="iss_id">Related Issue *</label>
                    <select id="iss_id" name="iss_id" class="form-control" required>
                        <option value="">Select Issue</option>
                        <?php foreach ($issues as $issue): ?>
                        <option value="<?php echo $issue['id']; ?>">
                            <?php echo htmlspecialchars($issue['short_description']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="posted_date">Posted Date *</label>
                    <input type="date" id="posted_date" name="posted_date" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div class="form-group">
                    <label for="per_id">Posted By *</label>
                    <select id="per_id" name="per_id" class="form-control" required>
                        <option value="">Select Person</option>
                        <?php foreach ($persons as $person): ?>
                        <option value="<?php echo $person['id']; ?>">
                            <?php echo htmlspecialchars($person['full_name']); ?> (<?php echo htmlspecialchars($person['email']); ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" id="cancelAdd">Cancel</button>
                    <button type="submit" name="add_comment" class="btn btn-primary">Add Comment</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Read Comment Modal -->
    <div id="readCommentModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>View Comment Details</h3>
            <form>
                <div class="form-group">
                    <label>Short Comment</label>
                    <input type="text" id="read_short_comment" class="form-control" readonly>
                </div>
                
                <div class="form-group">
                    <label>Long Comment</label>
                    <textarea id="read_long_comment" class="form-control" readonly></textarea>
                </div>
                
                <div class="form-group">
                    <label>Related Issue</label>
                    <input type="text" id="read_issue" class="form-control" readonly>
                </div>
                
                <div class="form-group">
                    <label>Posted Date</label>
                    <input type="date" id="read_posted_date" class="form-control" readonly>
                </div>
                
                <div class="form-group">
                    <label>Posted By</label>
                    <input type="text" id="read_posted_by" class="form-control" readonly>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary close-modal">Close</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Update Comment Modal -->
    <div id="updateCommentModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Update Comment</h3>
            <form method="post" action="comments_list.php">
                <input type="hidden" id="update_comment_id" name="comment_id">
                
                <div class="form-group">
                    <label for="update_short_comment">Short Comment *</label>
                    <input type="text" id="update_short_comment" name="short_comment" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="update_long_comment">Long Comment</label>
                    <textarea id="update_long_comment" name="long_comment" class="form-control"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="update_iss_id">Related Issue *</label>
                    <select id="update_iss_id" name="iss_id" class="form-control" required>
                        <option value="">Select Issue</option>
                        <?php foreach ($issues as $issue): ?>
                        <option value="<?php echo $issue['id']; ?>">
                            <?php echo htmlspecialchars($issue['short_description']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="update_posted_date">Posted Date *</label>
                    <input type="date" id="update_posted_date" name="posted_date" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="update_per_id">Posted By *</label>
                    <select id="update_per_id" name="per_id" class="form-control" required>
                        <option value="">Select Person</option>
                        <?php foreach ($persons as $person): ?>
                        <option value="<?php echo $person['id']; ?>">
                            <?php echo htmlspecialchars($person['full_name']); ?> (<?php echo htmlspecialchars($person['email']); ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary close-modal">Cancel</button>
                    <button type="submit" name="update_comment" class="btn btn-primary">Update Comment</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Delete Comment Modal -->
    <div id="deleteCommentModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Delete Comment</h3>
            <form method="post" action="comments_list.php">
                <input type="hidden" id="delete_comment_id" name="comment_id">
                
                <div class="alert alert-danger">
                    <p>Are you sure you want to delete this comment?</p>
                    <p><strong>ID:</strong> <span id="delete_comment_id_display"></span></p>
                    <p><strong>Issue:</strong> <span id="delete_issue_description"></span></p>
                    <p><strong>Comment:</strong> <span id="delete_short_comment"></span></p>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary close-modal">Cancel</button>
                    <button type="submit" name="delete_comment" class="btn btn-danger">Delete Comment</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // JavaScript modal handling code (identical to issues_list.php)
        // Replace all instances of issue with comment in selectors and attribute names
        // ... (use the same script as in issues_list.php, just change the variable names)
        
        // Get the modals
        var addModal = document.getElementById("addCommentModal");
        var readModal = document.getElementById("readCommentModal");
        var updateModal = document.getElementById("updateCommentModal");
        var deleteModal = document.getElementById("deleteCommentModal");
        
        // Get the buttons that open the modals
        var addBtn = document.getElementById("openAddModal");
        var readBtns = document.querySelectorAll(".read-btn");
        var updateBtns = document.querySelectorAll(".update-btn");
        var deleteBtns = document.querySelectorAll(".delete-btn");
        
        // Get the <span> elements that close the modals
        var closeSpans = document.getElementsByClassName("close");
        
        // Get the cancel buttons
        var cancelAddBtn = document.getElementById("cancelAdd");
        var closeModalBtns = document.querySelectorAll(".close-modal");
        
        // When the user clicks the button, open the add modal
        addBtn.onclick = function() {
            addModal.style.display = "block";
        }
        
        // Add event listeners for read buttons
        readBtns.forEach(function(btn) {
            btn.onclick = function() {
                var id = this.getAttribute("data-id");
                var shortComment = this.getAttribute("data-shortcomment");
                var longComment = this.getAttribute("data-longcomment");
                var issueDesc = this.getAttribute("data-issueDesc");
                var postedDate = this.getAttribute("data-posteddate");
                var postedBy = this.getAttribute("data-postedby");
                
                // Populate read modal with data
                document.getElementById("read_short_comment").value = shortComment;
                document.getElementById("read_long_comment").value = longComment;
                document.getElementById("read_issue").value = issueDesc;
                document.getElementById("read_posted_date").value = postedDate;
                document.getElementById("read_posted_by").value = postedBy;
                
                // Open the modal
                readModal.style.display = "block";
            }
        });
        
        // Add event listeners for update buttons
        updateBtns.forEach(function(btn) {
            btn.onclick = function() {
                var id = this.getAttribute("data-id");
                var shortComment = this.getAttribute("data-shortcomment");
                var longComment = this.getAttribute("data-longcomment");
                var issueId = this.getAttribute("data-issueid");
                var postedDate = this.getAttribute("data-posteddate");
                
                // Populate update modal with data
                document.getElementById("update_comment_id").value = id;
                document.getElementById("update_short_comment").value = shortComment;
                document.getElementById("update_long_comment").value = longComment;
                document.getElementById("update_iss_id").value = issueId;
                document.getElementById("update_posted_date").value = postedDate;
                
                // Open the modal
                updateModal.style.display = "block";
            }
        });
        
        // Add event listeners for delete buttons
        deleteBtns.forEach(function(btn) {
            btn.onclick = function() {
                var id = this.getAttribute("data-id");
                var shortComment = this.getAttribute("data-shortcomment");
                var issueDesc = this.getAttribute("data-issuedesc");
                
                // Populate delete modal with data
                document.getElementById("delete_comment_id").value = id;
                document.getElementById("delete_comment_id_display").textContent = id;
                document.getElementById("delete_short_comment").textContent = shortComment;
                document.getElementById("delete_issue_description").textContent = issueDesc;
                
                // Open the modal
                deleteModal.style.display = "block";
            }
        });
        
        // When the user clicks on <span> (x), close the modals
        for (var i = 0; i < closeSpans.length; i++) {
            closeSpans[i].onclick = function() {
                addModal.style.display = "none";
                readModal.style.display = "none";
                updateModal.style.display = "none";
                deleteModal.style.display = "none";
            }
        }
        
        // When the user clicks on cancel buttons, close the modals
        cancelAddBtn.onclick = function() {
            addModal.style.display = "none";
        }
        
        closeModalBtns.forEach(function(btn) {
            btn.onclick = function() {
                addModal.style.display = "none";
                readModal.style.display = "none";
                updateModal.style.display = "none";
                deleteModal.style.display = "none";
            }
        });
        
        // When the user clicks anywhere outside of the modals, close them
        window.onclick = function(event) {
            if (event.target == addModal) {
                addModal.style.display = "none";
            } else if (event.target == readModal) {
                readModal.style.display = "none";
            } else if (event.target == updateModal) {
                updateModal.style.display = "none";
            } else if (event.target == deleteModal) {
                deleteModal.style.display = "none";
            }
        }
    </script>
</body>
</html>