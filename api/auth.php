<?php
// Start the session
session_start();

// Include your database connection
require_once 'db_connect.php';

// Check if the form 'action' was sent
if(isset($_POST['action'])) {

    // --- REGISTRATION LOGIC ---
    if($_POST['action'] == 'register') {
        
        // Get form data
        $username = $_POST['username'];
        $name = $_POST['name'];
        $mobile = $_POST['mobile'];
        $password = $_POST['password'];

        // --- Validation (basic) ---
        if(empty($username) || empty($name) || empty($mobile) || empty($password)) {
            die("Error: Please fill all fields.");
        }

        // --- Check if username or mobile already exists ---
        $sql_check = "SELECT id FROM users WHERE Username = ? OR mobile = ?";
        if($stmt_check = $conn->prepare($sql_check)) {
            $stmt_check->bind_param("ss", $username, $mobile);
            $stmt_check->execute();
            $stmt_check->store_result();

            if($stmt_check->num_rows > 0) {
                die("Error: Username or mobile number already taken.");
            }
            $stmt_check->close();
        } else {
            die("Error: Database check failed.");
        }


        // --- Create Hashed Password (THE SECURE WAY) ---
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // --- Insert New User into Database ---
        $sql_insert = "INSERT INTO users (Username, name, mobile, password_hash) VALUES (?, ?, ?, ?)";

        if($stmt_insert = $conn->prepare($sql_insert)) {
            // Bind variables to the prepared statement
            // "ssss" means 4 string variables
            $stmt_insert->bind_param("ssss", $username, $name, $mobile, $hashed_password);

            // Execute the statement
            if($stmt_insert->execute()) {
                // ... inside your registration success block ...

// OLD BROKEN CODE (What you likely have):
// echo "Registration successful! You can now log in.";
// header("Location: ../login.php");

// NEW FIXED CODE:
// redirects immediately without printing text
                  header("Location: ../login.php?registered=true");
                  exit();
              
            } else {
                echo "Error: Something went wrong. Please try again later.";
            }
            $stmt_insert->close();
        }
    }

    // --- LOGIN LOGIC (Fetching Data) ---
    if($_POST['action'] == 'login') {
        
        // Note: The form 'name' is 'username', which matches $_POST['username']
        $username = $_POST['username'];
        $password = $_POST['password'];

        if(empty($username) || empty($password)) {
            die("Error: Please enter username and password.");
        }

        // --- Prepare SQL to fetch user (using your column 'Username') ---
        $sql = "SELECT id, Username, password_hash FROM users WHERE Username = ?";
        
        if($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->store_result();

            // Check if username exists
            if($stmt->num_rows == 1) {
                // Bind result variables
                $stmt->bind_result($id, $fetched_username, $fetched_password_hash);
                
                // This is the "fetch" part
                if($stmt->fetch()) {
                    
                    // --- Verify the password ---
                    if(password_verify($password, $fetched_password_hash)) {
                        // Password is correct! Start a new session.
                        $user = mysqli_fetch_assoc($results);
                        
                        // Store data in session variables
                        $_SESSION["loggedin"] = true;
                        $_SESSION["id"] = $id;
                        $_SESSION["username"] = $fetched_username; // Storing the 'Username'
                        
                        // Redirect user to the main app page
                        header("location: dashboard.php");
                    } else {
                       array_push($errors, "Wrong username/password combination");
                    }
                }
            } else {
                echo "Invalid username or password.";
            }
            $stmt->close();
        }
    }
}

// Close database connection
$conn->close();

?>

