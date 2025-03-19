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

// Get all issues from the database
try {
    $stmt = $pdo->prepare("
        SELECT i.id, i.short_description, i.long_description, i.open_date, 
               i.close_date, i.priority, i.org, i.project, i.per_id, 
               CONCAT(p.fname, ' ', p.lname) as assigned_to
        FROM iss_iss i
        LEFT JOIN iss_per p ON i.per_id = p.id
        ORDER BY i.project, i.priority, i.open_date
    ");
    $stmt->execute();
    $issues = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all persons for the dropdown
    $stmt = $pdo->prepare("SELECT id, CONCAT(fname, ' ', lname) as full_name, email FROM iss_per ORDER BY fname, lname");
    $stmt->execute();
    $persons = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Process form submission for adding new issue
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_issue'])) {
    try {
        $short_description = trim($_POST['short_description']);
        $long_description = trim($_POST['long_description']);
        $open_date = $_POST['open_date'];
        $close_date = $_POST['close_date'] ?: null;
        $priority = $_POST['priority'];
        $org = trim($_POST['org']);
        $project = trim($_POST['project']);
        $per_id = $_POST['per_id'];
        
        // Validate inputs
        if (empty($short_description) || empty($open_date) || empty($priority) || empty($org) || empty($project) || empty($per_id)) {
            $error = "All required fields must be filled out";
        } else {
            // Insert new issue
            $stmt = $pdo->prepare("
                INSERT INTO iss_iss (short_description, long_description, open_date, close_date, priority, org, project, per_id)
                VALUES (:short_description, :long_description, :open_date, :close_date, :priority, :org, :project, :per_id)
            ");
            
            $stmt->bindParam(':short_description', $short_description, PDO::PARAM_STR);
            $stmt->bindParam(':long_description', $long_description, PDO::PARAM_STR);
            $stmt->bindParam(':open_date', $open_date, PDO::PARAM_STR);
            $stmt->bindParam(':close_date', $close_date, PDO::PARAM_STR);
            $stmt->bindParam(':priority', $priority, PDO::PARAM_STR);
            $stmt->bindParam(':org', $org, PDO::PARAM_STR);
            $stmt->bindParam(':project', $project, PDO::PARAM_STR);
            $stmt->bindParam(':per_id', $per_id, PDO::PARAM_INT);
            
            $stmt->execute();
            
            // Redirect to refresh the page and show the new issue
            header('Location: issues_list.php');
            exit();
        }
    } catch (PDOException $e) {
        $error = "Error adding issue: " . $e->getMessage();
    }
}

// Process form submission for updating issue
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_issue'])) {
    try {
        $id = $_POST['issue_id'];
        $short_description = trim($_POST['short_description']);
        $long_description = trim($_POST['long_description']);
        $open_date = $_POST['open_date'];
        $close_date = $_POST['close_date'] ?: null;
        $priority = $_POST['priority'];
        $org = trim($_POST['org']);
        $project = trim($_POST['project']);
        $per_id = $_POST['per_id'];
        
        // Validate inputs
        if (empty($short_description) || empty($open_date) || empty($priority) || empty($org) || empty($project) || empty($per_id)) {
            $error = "All required fields must be filled out";
        } else {
            // Update the issue
            $stmt = $pdo->prepare("
                UPDATE iss_iss 
                SET short_description = :short_description,
                    long_description = :long_description,
                    open_date = :open_date,
                    close_date = :close_date,
                    priority = :priority,
                    org = :org,
                    project = :project,
                    per_id = :per_id
                WHERE id = :id
            ");
            
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':short_description', $short_description, PDO::PARAM_STR);
            $stmt->bindParam(':long_description', $long_description, PDO::PARAM_STR);
            $stmt->bindParam(':open_date', $open_date, PDO::PARAM_STR);
            $stmt->bindParam(':close_date', $close_date, PDO::PARAM_STR);
            $stmt->bindParam(':priority', $priority, PDO::PARAM_STR);
            $stmt->bindParam(':org', $org, PDO::PARAM_STR);
            $stmt->bindParam(':project', $project, PDO::PARAM_STR);
            $stmt->bindParam(':per_id', $per_id, PDO::PARAM_INT);
            
            $stmt->execute();
            
            // Redirect to refresh the page
            header('Location: issues_list.php');
            exit();
        }
    } catch (PDOException $e) {
        $error = "Error updating issue: " . $e->getMessage();
    }
}

// Process form submission for deleting issue
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_issue'])) {
    try {
        $id = $_POST['issue_id'];
        
        // Delete the issue
        $stmt = $pdo->prepare("DELETE FROM iss_iss WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        // Redirect to refresh the page
        header('Location: issues_list.php');
        exit();
    } catch (PDOException $e) {
        $error = "Error deleting issue: " . $e->getMessage();
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
    <title>Department Status Report - Issues List</title>
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
        .logout-btn {
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
        .priority-high {
            color: #ff0000;
            font-weight: bold;
        }
        .priority-medium {
            color: #ff9900;
        }
        .priority-low {
            color: #009900;
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
        .no-issues {
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
        
        /* Modal styles */
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
        textarea.form-control {
            height: 100px;
            resize: vertical;
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
            <h2>Department Status Report - Issues List</h2>
            <div class="user-info">
                Welcome, <?php echo htmlspecialchars($userName); ?>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
        
        <button id="openAddModal" class="add-btn">
            <span class="add-icon">+</span> Add New Issue
        </button>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (empty($issues)): ?>
            <div class="no-issues">No issues found in the system.</div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Project</th>
                        <th>Description</th>
                        <th>Priority</th>
                        <th>Organization</th>
                        <th>Open Date</th>
                        <th>Close Date</th>
                        <th>Assigned To</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($issues as $issue): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($issue['id']); ?></td>
                            <td><?php echo htmlspecialchars($issue['project']); ?></td>
                            <td><?php echo htmlspecialchars($issue['short_description']); ?></td>
                            <td class="priority-<?php echo strtolower(htmlspecialchars($issue['priority'])); ?>">
                                <?php echo htmlspecialchars($issue['priority']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($issue['org']); ?></td>
                            <td><?php echo formatDate($issue['open_date']); ?></td>
                            <td><?php echo formatDate($issue['close_date']); ?></td>
                            <td><?php echo htmlspecialchars($issue['assigned_to']); ?></td>
                            <td class="actions">
                                <button class="action-btn read-btn" 
                                    data-id="<?php echo $issue['id']; ?>"
                                    data-shortdesc="<?php echo htmlspecialchars($issue['short_description']); ?>"
                                    data-longdesc="<?php echo htmlspecialchars($issue['long_description']); ?>"
                                    data-priority="<?php echo htmlspecialchars($issue['priority']); ?>"
                                    data-org="<?php echo htmlspecialchars($issue['org']); ?>"
                                    data-project="<?php echo htmlspecialchars($issue['project']); ?>"
                                    data-perid="<?php echo htmlspecialchars($issue['per_id']); ?>"
                                    data-opendate="<?php echo formatDateForInput($issue['open_date']); ?>"
                                    data-closedate="<?php echo formatDateForInput($issue['close_date']); ?>"
                                    data-assignedto="<?php echo htmlspecialchars($issue['assigned_to']); ?>">R</button>
                                <button class="action-btn update-btn"
                                    data-id="<?php echo $issue['id']; ?>"
                                    data-shortdesc="<?php echo htmlspecialchars($issue['short_description']); ?>"
                                    data-longdesc="<?php echo htmlspecialchars($issue['long_description']); ?>"
                                    data-priority="<?php echo htmlspecialchars($issue['priority']); ?>"
                                    data-org="<?php echo htmlspecialchars($issue['org']); ?>"
                                    data-project="<?php echo htmlspecialchars($issue['project']); ?>"
                                    data-perid="<?php echo htmlspecialchars($issue['per_id']); ?>"
                                    data-opendate="<?php echo formatDateForInput($issue['open_date']); ?>"
                                    data-closedate="<?php echo formatDateForInput($issue['close_date']); ?>"
                                    data-assignedto="<?php echo htmlspecialchars($issue['assigned_to']); ?>">U</button>
                                <button class="action-btn delete-btn"
                                    data-id="<?php echo $issue['id']; ?>"
                                    data-shortdesc="<?php echo htmlspecialchars($issue['short_description']); ?>"
                                    data-project="<?php echo htmlspecialchars($issue['project']); ?>">D</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    
    <!-- Add Issue Modal -->
    <div id="addIssueModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Add New Issue</h3>
            <form method="post" action="issues_list.php">
                <div class="form-group">
                    <label for="short_description">Short Description *</label>
                    <input type="text" id="short_description" name="short_description" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="long_description">Long Description</label>
                    <textarea id="long_description" name="long_description" class="form-control"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="project">Project *</label>
                    <input type="text" id="project" name="project" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="org">Organization *</label>
                    <input type="text" id="org" name="org" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="priority">Priority *</label>
                    <select id="priority" name="priority" class="form-control" required>
                        <option value="">Select Priority</option>
                        <option value="High">High</option>
                        <option value="Medium">Medium</option>
                        <option value="Low">Low</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="open_date">Open Date *</label>
                    <input type="date" id="open_date" name="open_date" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div class="form-group">
                    <label for="close_date">Close Date</label>
                    <input type="date" id="close_date" name="close_date" class="form-control">
                </div>
                
                <div class="form-group">
                    <label for="per_id">Assigned To *</label>
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
                    <button type="submit" name="add_issue" class="btn btn-primary">Add Issue</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Read Issue Modal -->
    <div id="readIssueModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>View Issue Details</h3>
            <form>
                <div class="form-group">
                    <label>Short Description</label>
                    <input type="text" id="read_short_description" class="form-control" readonly>
                </div>
                
                <div class="form-group">
                    <label>Long Description</label>
                    <textarea id="read_long_description" class="form-control" readonly></textarea>
                </div>
                
                <div class="form-group">
                    <label>Project</label>
                    <input type="text" id="read_project" class="form-control" readonly>
                </div>
                
                <div class="form-group">
                    <label>Organization</label>
                    <input type="text" id="read_org" class="form-control" readonly>
                </div>
                
                <div class="form-group">
                    <label>Priority</label>
                    <input type="text" id="read_priority" class="form-control" readonly>
                </div>
                
                <div class="form-group">
                    <label>Open Date</label>
                    <input type="date" id="read_open_date" class="form-control" readonly>
                </div>
                
                <div class="form-group">
                    <label>Close Date</label>
                    <input type="date" id="read_close_date" class="form-control" readonly>
                </div>
                
                <div class="form-group">
                    <label>Assigned To</label>
                    <input type="text" id="read_assigned_to" class="form-control" readonly>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary close-modal">Close</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Update Issue Modal -->
    <div id="updateIssueModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Update Issue</h3>
            <form method="post" action="issues_list.php">
                <input type="hidden" id="update_issue_id" name="issue_id">
                
                <div class="form-group">
                    <label for="update_short_description">Short Description *</label>
                    <input type="text" id="update_short_description" name="short_description" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="update_long_description">Long Description</label>
                    <textarea id="update_long_description" name="long_description" class="form-control"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="update_project">Project *</label>
                    <input type="text" id="update_project" name="project" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="update_org">Organization *</label>
                    <input type="text" id="update_org" name="org" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="update_priority">Priority *</label>
                    <select id="update_priority" name="priority" class="form-control" required>
                        <option value="">Select Priority</option>
                        <option value="High">High</option>
                        <option value="Medium">Medium</option>
                        <option value="Low">Low</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="update_open_date">Open Date *</label>
                    <input type="date" id="update_open_date" name="open_date" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="update_close_date">Close Date</label>
                    <input type="date" id="update_close_date" name="close_date" class="form-control">
                </div>
                
                <div class="form-group">
                    <label for="update_per_id">Assigned To *</label>
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
                    <button type="submit" name="update_issue" class="btn btn-primary">Update Issue</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Delete Issue Modal -->
    <div id="deleteIssueModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Delete Issue</h3>
            <form method="post" action="issues_list.php">
                <input type="hidden" id="delete_issue_id" name="issue_id">
                
                <div class="alert alert-danger">
                    <p>Are you sure you want to delete this issue?</p>
                    <p><strong>ID:</strong> <span id="delete_issue_id_display"></span></p>
                    <p><strong>Project:</strong> <span id="delete_project"></span></p>
                    <p><strong>Description:</strong> <span id="delete_short_description"></span></p>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary close-modal">Cancel</button>
                    <button type="submit" name="delete_issue" class="btn btn-danger">Delete Issue</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Get the modals
        var addModal = document.getElementById("addIssueModal");
        var readModal = document.getElementById("readIssueModal");
        var updateModal = document.getElementById("updateIssueModal");
        var deleteModal = document.getElementById("deleteIssueModal");
        
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
                var shortDesc = this.getAttribute("data-shortdesc");
                var longDesc = this.getAttribute("data-longdesc");
                var priority = this.getAttribute("data-priority");
                var org = this.getAttribute("data-org");
                var project = this.getAttribute("data-project");
                var openDate = this.getAttribute("data-opendate");
                var closeDate = this.getAttribute("data-closedate");
                var assignedTo = this.getAttribute("data-assignedto");
                
                // Populate read modal with data
                document.getElementById("read_short_description").value = shortDesc;
                document.getElementById("read_long_description").value = longDesc;
                document.getElementById("read_priority").value = priority;
                document.getElementById("read_org").value = org;
                document.getElementById("read_project").value = project;
                document.getElementById("read_open_date").value = openDate;
                document.getElementById("read_close_date").value = closeDate;
                document.getElementById("read_assigned_to").value = assignedTo;
                
                // Open the modal
                readModal.style.display = "block";
            }
        });
        
        // Add event listeners for update buttons
        updateBtns.forEach(function(btn) {
            btn.onclick = function() {
                var id = this.getAttribute("data-id");
                var shortDesc = this.getAttribute("data-shortdesc");
                var longDesc = this.getAttribute("data-longdesc");
                var priority = this.getAttribute("data-priority");
                var org = this.getAttribute("data-org");
                var project = this.getAttribute("data-project");
                var perId = this.getAttribute("data-perid");
                var openDate = this.getAttribute("data-opendate");
                var closeDate = this.getAttribute("data-closedate");
                
                // Populate update modal with data
                document.getElementById("update_issue_id").value = id;
                document.getElementById("update_short_description").value = shortDesc;
                document.getElementById("update_long_description").value = longDesc;
                document.getElementById("update_priority").value = priority;
                document.getElementById("update_org").value = org;
                document.getElementById("update_project").value = project;
                document.getElementById("update_per_id").value = perId;
                document.getElementById("update_open_date").value = openDate;
                document.getElementById("update_close_date").value = closeDate || '';
                
                // Open the modal
                updateModal.style.display = "block";
            }
        });
        
        // Add event listeners for delete buttons
        deleteBtns.forEach(function(btn) {
            btn.onclick = function() {
                var id = this.getAttribute("data-id");
                var shortDesc = this.getAttribute("data-shortdesc");
                var project = this.getAttribute("data-project");
                
                // Populate delete modal with data
                document.getElementById("delete_issue_id").value = id;
                document.getElementById("delete_issue_id_display").textContent = id;
                document.getElementById("delete_short_description").textContent = shortDesc;
                document.getElementById("delete_project").textContent = project;
                
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
