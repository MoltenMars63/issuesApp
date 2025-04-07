<?php
session_start();
require_once '../database/database.php';
$pdo = Database::connect();

$email = "";
$error = "";
$redirect_url = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } else {
        try {
            // Using your existing table structure with admin column
            $stmt = $pdo->prepare("SELECT id, fname, lname, pwd_hash, pwd_salt, admin FROM iss_per WHERE email = :email");
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            
            if ($stmt->rowCount() == 1) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $stored_hash = $row["pwd_hash"];
                $salt = $row["pwd_salt"];
                $is_admin = ($row["admin"] == 'Y'); // Convert Y/N to boolean
                
                // Calculate hash using the existing method in your system
                $calculated_hash = md5($password . $salt);
                
                // Check if calculated hash matches stored hash
                if ($calculated_hash === $stored_hash) {
                    // Add failed attempts tracking in session instead of new column
                    // Create session with CSRF token
                    $_SESSION["user_id"] = $row["id"];
                    $_SESSION["user_name"] = $row["fname"] . " " . $row["lname"];
                    $_SESSION["user_email"] = $email;
                    $_SESSION["is_admin"] = $is_admin;
                    $_SESSION["logged_in"] = true;
                    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
                    $_SESSION["last_activity"] = time();
                    
                    // Log login attempt
                    $login_time = date("Y-m-d H:i:s");
                    $ip = $_SERVER['REMOTE_ADDR'];
                    error_log("User login: ID={$row["id"]}, Name={$row["fname"]} {$row["lname"]}, Time=$login_time, IP=$ip");
                    
                    // Redirect based on admin status
                    if ($is_admin) {
                        $redirect_url = "issues_list.php";
                    }
                    header("Location: $redirect_url");
                    exit();
                } else {
                    // No need to update the database for failed attempts
                    // Just track in the session
                    if (!isset($_SESSION["failed_attempts"])) {
                        $_SESSION["failed_attempts"] = 0;
                    }
                    $_SESSION["failed_attempts"]++;
                    
                    if ($_SESSION["failed_attempts"] >= 5) {
                        $error = "Too many failed attempts please try again later";
                        $_SESSION["lockout_until"] = time() + 900; // 15 minutes lockout
                    } else {
                        $error = "Invalid email or password";
                    }
                }
            } else {
                $error = "Invalid email or password";
                // Sleep to prevent timing attacks
                usleep(random_int(100000, 500000));
            }
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $error = "A system error occurred Please try again later";
        }
    }
}

// Check for lockout
if (isset($_SESSION["lockout_until"]) && time() < $_SESSION["lockout_until"]) {
    $remaining = ceil(($_SESSION["lockout_until"] - time()) / 60);
    $error = "Account temporarily locked Please try again in $remaining minutes";
}

// Auto logout after inactivity
function session_expired() {
    $max_lifetime = 30 * 60; // 30 minutes
    if (isset($_SESSION["last_activity"]) && (time() - $_SESSION["last_activity"] > $max_lifetime)) {
        return true;
    }
    return false;
}

// Check if user is already logged in and session not expired
if (isset($_SESSION["logged_in"]) && $_SESSION["logged_in"] === true) {
    if (session_expired()) {
        session_unset();
        session_destroy();
        session_start();
        $error = "Session expired please login again";
    } else {
        $_SESSION["last_activity"] = time(); // Update last activity time
        $redirect_url = ($_SESSION["is_admin"]) ? "issues_list.php" : "issues_list.php";
        header("Location: $redirect_url");
        exit();
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
            font-family: Arial sans-serif;
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
            <div class="error"><?php echo htmlspecialchars($error) ?></div>
        <?php endif ?>
        
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) ?>">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email) ?>" required>
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
