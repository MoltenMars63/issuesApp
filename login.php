<?php
// Start session
session_start();

// Include database connection
require_once '../database/database.php';
$pdo = Database::connect();

// Initialize variables
$email = "";
$error = "";

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get email and password from form
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } else {
        try {
            // Prepared statement to prevent SQL injection
            $stmt = $pdo->prepare("SELECT id, fname, lname, pwd_hash, pwd_salt FROM iss_per WHERE email = :email");
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            
            if ($stmt->rowCount() == 1) {
                // User found, verify password
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $stored_hash = $row["pwd_hash"];
                $salt = $row["pwd_salt"];
                
                // Calculate hash of provided password using the stored salt
                $calculated_hash = md5($password . $salt);
                
                
                // Check if calculated hash matches stored hash
                if ($calculated_hash === $stored_hash) {
                    // Password is correct, create session
                    $_SESSION["user_id"] = $row["id"];
                    $_SESSION["user_name"] = $row["fname"] . " " . $row["lname"];
                    $_SESSION["user_email"] = $email;
                    $_SESSION["logged_in"] = true;
                    
                    // Redirect to issues list
                    header("Location: issues_list.php");
                    exit();
                } else {
                    $error = "Invalid email or password";
                }
            } else {
                $error = "Invalid email or password";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
            // In production, you should log the error instead of displaying it
            // $error = "A system error occurred. Please try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department Status Report - Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .login-container {
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
            width: 350px;
        }
        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }
        .error {
            color: red;
            margin-bottom: 15px;
            text-align: center;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
        }
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Department Status Report</h2>
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit">Log In</button>
        </form>
    </div>
</body>
</html>