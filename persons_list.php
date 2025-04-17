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

// Get current user ID from session
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
    $error = "Database error: " . $e->getMessage();
}

// Get all persons from the database
try {
    $stmt = $pdo->prepare("
        SELECT id, fname, lname, mobile, email, admin 
        FROM iss_per 
        ORDER BY lname, fname
    ");
    $stmt->execute();
    $persons = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Process form submission for adding new person
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_person'])) {
    try {
        $fname = trim($_POST['fname']);
        $lname = trim($_POST['lname']);
        $mobile = trim($_POST['mobile']);
        $email = trim($_POST['email']);
        $admin = isset($_POST['admin']) ? 1 : 0;
        $pwd_salt = bin2hex(random_bytes(16)); // Generate salt
        $pwd_hash = password_hash($_POST['password'] . $pwd_salt, PASSWORD_DEFAULT);
        
        // Validate inputs
        if (empty($fname) || empty($lname) || empty($email) || empty($_POST['password'])) {
            $error = "First name, last name, email, and password are required";
        } else {
            // Insert new person
            $stmt = $pdo->prepare("
                INSERT INTO iss_per (fname, lname, mobile, email, pwd_hash, pwd_salt, admin)
                VALUES (:fname, :lname, :mobile, :email, :pwd_hash, :pwd_salt, :admin)
            ");
            
            $stmt->bindParam(':fname', $fname, PDO::PARAM_STR);
            $stmt->bindParam(':lname', $lname, PDO::PARAM_STR);
            $stmt->bindParam(':mobile', $mobile, PDO::PARAM_STR);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->bindParam(':pwd_hash', $pwd_hash, PDO::PARAM_STR);
            $stmt->bindParam(':pwd_salt', $pwd_salt, PDO::PARAM_STR);
            $stmt->bindParam(':admin', $admin, PDO::PARAM_INT);
            
            $stmt->execute();
            
            // Redirect to refresh the page
            header('Location: persons_list.php');
            exit();
        }
    } catch (PDOException $e) {
        $error = "Error adding person: " . $e->getMessage();
    }
}

// Process form submission for updating person
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_person'])) {
    // Check if user is admin
    if (!$isAdmin) {
        $error = "You don't have permission to update person records.";
    } else {
        try {
                $id = $_POST['person_id'];
            $fname = trim($_POST['fname']);
            $lname = trim($_POST['lname']);
            $mobile = trim($_POST['mobile']);
            $email = trim($_POST['email']);
            $admin = isset($_POST['admin']) ? 1 : 0;
            
            // Validate inputs
            if (empty($fname) || empty($lname) || empty($email)) {
                $error = "First name, last name, and email are required";
            } else {
                // Update person
                $stmt = $pdo->prepare("
                    UPDATE iss_per 
                    SET fname = :fname,
                        lname = :lname,
                        mobile = :mobile,
                        email = :email,
                        admin = :admin
                    WHERE id = :id
                ");
                
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                $stmt->bindParam(':fname', $fname, PDO::PARAM_STR);
                $stmt->bindParam(':lname', $lname, PDO::PARAM_STR);
                $stmt->bindParam(':mobile', $mobile, PDO::PARAM_STR);
                $stmt->bindParam(':email', $email, PDO::PARAM_STR);
                $stmt->bindParam(':admin', $admin, PDO::PARAM_INT);
                
                $stmt->execute();
                
                // Redirect to refresh the page
                header('Location: persons_list.php');
                exit();
            }
        } catch (PDOException $e) {
            $error = "Error updating person: " . $e->getMessage();
        }
    }
}

// Process form submission for deleting person
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_person'])) {
    // Check if user is admin
    if ($isAdmin) {
        $error = "You don't have permission to delete person records.";
    } else {
        try {
            $id = $_POST['person_id'];
        
            // Delete the person
            $stmt = $pdo->prepare("DELETE FROM iss_per WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            // Redirect to refresh the page
            header('Location: persons_list.php');
            exit();
        } catch (PDOException $e) {
            $error = "Error deleting person: " . $e->getMessage();
        }
    }
}

// Get user name
$userName = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'User';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department Status Report - Persons List</title>
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
            <h2>Department Status Report - Persons List</h2>
            <div class="user-info">
                Welcome, <?php echo htmlspecialchars($userName); ?>
                <a href="logout.php" class="logout-btn">Logout</a>
                <a href="issues_list.php" class="issues-btn">Back to Issues</a>
            </div>
        </div>
        
        <button id="openAddModal" class="add-btn">
            <span class="add-icon">+</span> Add New Person
        </button>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (empty($persons)): ?>
            <div class="no-persons">No persons found in the system.</div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Mobile</th>
                        <th>Email</th>
                        <th>Admin</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($persons as $person): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($person['id']); ?></td>
                            <td><?php echo htmlspecialchars($person['fname']); ?></td>
                            <td><?php echo htmlspecialchars($person['lname']); ?></td>
                            <td><?php echo htmlspecialchars($person['mobile']); ?></td>
                            <td><?php echo htmlspecialchars($person['email']); ?></td>
                            <td><?php echo $person['admin'] ? 'Yes' : 'No'; ?></td>
                            <td class="actions">
                                <button class="action-btn read-btn" 
                                    data-id="<?php echo $person['id']; ?>"
                                    data-fname="<?php echo htmlspecialchars($person['fname']); ?>"
                                    data-lname="<?php echo htmlspecialchars($person['lname']); ?>"
                                    data-mobile="<?php echo htmlspecialchars($person['mobile']); ?>"
                                    data-email="<?php echo htmlspecialchars($person['email']); ?>"
                                    data-admin="<?php echo $person['admin']; ?>">R</button>
                                <?php if ($isAdmin): ?>
                                    <button class="action-btn update-btn"
                                        data-id="<?php echo $person['id']; ?>"
                                        data-fname="<?php echo htmlspecialchars($person['fname']); ?>"
                                        data-lname="<?php echo htmlspecialchars($person['lname']); ?>"
                                        data-mobile="<?php echo htmlspecialchars($person['mobile']); ?>"
                                        data-email="<?php echo htmlspecialchars($person['email']); ?>"
                                        data-admin="<?php echo $person['admin']; ?>">U</button>
                                    <button class="action-btn delete-btn"
                                        data-id="<?php echo $person['id']; ?>"
                                        data-fname="<?php echo htmlspecialchars($person['fname']); ?>"
                                        data-lname="<?php echo htmlspecialchars($person['lname']); ?>">D</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    
    <!-- Add Person Modal -->
    <div id="addPersonModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Add New Person</h3>
            <form method="post" action="persons_list.php">
                <div class="form-group">
                    <label for="fname">First Name *</label>
                    <input type="text" id="fname" name="fname" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="lname">Last Name *</label>
                    <input type="text" id="lname" name="lname" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="mobile">Mobile</label>
                    <input type="tel" id="mobile" name="mobile" class="form-control">
                </div>
                
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" id="admin" name="admin" value="1">
                    <label for="admin">Admin User</label>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" id="cancelAdd">Cancel</button>
                    <button type="submit" name="add_person" class="btn btn-primary">Add Person</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Read Person Modal -->
    <div id="readPersonModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>View Person Details</h3>
            <form>
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" id="read_fname" class="form-control" readonly>
                </div>
                
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" id="read_lname" class="form-control" readonly>
                </div>
                
                <div class="form-group">
                    <label>Mobile</label>
                    <input type="tel" id="read_mobile" class="form-control" readonly>
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" id="read_email" class="form-control" readonly>
                </div>
                
                <div class="form-group">
                    <label>Admin Status</label>
                    <input type="text" id="read_admin" class="form-control" readonly>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary close-modal">Close</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Update Person Modal -->
    <div id="updatePersonModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Update Person</h3>
            <form method="post" action="persons_list.php">
                <input type="hidden" id="update_person_id" name="person_id">
                
                <div class="form-group">
                    <label for="update_fname">First Name *</label>
                    <input type="text" id="update_fname" name="fname" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="update_lname">Last Name *</label>
                    <input type="text" id="update_lname" name="lname" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="update_mobile">Mobile</label>
                    <input type="tel" id="update_mobile" name="mobile" class="form-control">
                </div>
                
                <div class="form-group">
                    <label for="update_email">Email *</label>
                    <input type="email" id="update_email" name="email" class="form-control" required>
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" id="update_admin" name="admin" value="1">
                    <label for="update_admin">Admin User</label>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary close-modal">Cancel</button>
                    <button type="submit" name="update_person" class="btn btn-primary">Update Person</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Delete Person Modal -->
    <div id="deletePersonModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Delete Person</h3>
            <form method="post" action="persons_list.php">
                <input type="hidden" id="delete_person_id" name="person_id">
                
                <div class="alert alert-danger">
                    <p>Are you sure you want to delete this person?</p>
                    <p><strong>ID:</strong> <span id="delete_person_id_display"></span></p>
                    <p><strong>Name:</strong> <span id="delete_person_name"></span></p>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary close-modal">Cancel</button>
                    <button type="submit" name="delete_person" class="btn btn-danger">Delete Person</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Get the modals
        var addModal = document.getElementById("addPersonModal");
        var readModal = document.getElementById("readPersonModal");
        var updateModal = document.getElementById("updatePersonModal");
        var deleteModal = document.getElementById("deletePersonModal");
        
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
                var fname = this.getAttribute("data-fname");
                var lname = this.getAttribute("data-lname");
                var mobile = this.getAttribute("data-mobile");
                var email = this.getAttribute("data-email");
                var admin = this.getAttribute("data-admin") === '1' ? 'Yes' : 'No';
                
                // Populate read modal with data
                document.getElementById("read_fname").value = fname;
                document.getElementById("read_lname").value = lname;
                document.getElementById("read_mobile").value = mobile;
                document.getElementById("read_email").value = email;
                document.getElementById("read_admin").value = admin;
                
                // Open the modal
                readModal.style.display = "block";
            }
        });
        
        // Add event listeners for update buttons
        updateBtns.forEach(function(btn) {
            btn.onclick = function() {
                var id = this.getAttribute("data-id");
                var fname = this.getAttribute("data-fname");
                var lname = this.getAttribute("data-lname");
                var mobile = this.getAttribute("data-mobile");
                var email = this.getAttribute("data-email");
                var admin = this.getAttribute("data-admin") === '1';
                
                // Populate update modal with data
                document.getElementById("update_person_id").value = id;
                document.getElementById("update_fname").value = fname;
                document.getElementById("update_lname").value = lname;
                document.getElementById("update_mobile").value = mobile;
                document.getElementById("update_email").value = email;
                document.getElementById("update_admin").checked = admin;
                
                // Open the modal
                updateModal.style.display = "block";
            }
        });
        
        // Add event listeners for delete buttons
        deleteBtns.forEach(function(btn) {
            btn.onclick = function() {
                var id = this.getAttribute("data-id");
                var fname = this.getAttribute("data-fname");
                var lname = this.getAttribute("data-lname");
                
                // Populate delete modal with data
                document.getElementById("delete_person_id").value = id;
                document.getElementById("delete_person_id_display").textContent = id;
                document.getElementById("delete_person_name").textContent = fname + " " + lname;
                
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