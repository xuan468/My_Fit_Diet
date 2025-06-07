<?php
include '../general/dbconn.php'; // Ensure this path is correct
session_start();

if (isset($_POST['submit'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Query to get the user's information by email
    $sql = "SELECT userID, email, username, password FROM user WHERE email = ?";
    $stmt = mysqli_prepare($connection, $sql);
    mysqli_stmt_bind_param($stmt, 's', $email); // Bind parameters
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt); // Store the result for num_rows

    $rowCount = mysqli_stmt_num_rows($stmt);

    if ($rowCount > 0) {
        mysqli_stmt_bind_result($stmt, $userID, $email, $username, $hashed_password);
        mysqli_stmt_fetch($stmt);

        // Verify the password
        if (password_verify($password, $hashed_password)) {
            $_SESSION['userid'] = $userID; // Set session
            $_SESSION['username'] = $username;
            $_SESSION['userrole'] = 'Member';
            
            echo "<script>alert('Login Successful!'); window.location.href = '../homepage/member_homepage.php';</script>";
            exit();
        } else {
            echo "<script>alert('Incorrect email or password.');</script>";
        } 
    } else {
        // If no user found, check staff table
        $sql = "SELECT userroleID, email, username, password, role FROM staff WHERE email = ?";
        $stmt = mysqli_prepare($connection, $sql);
        mysqli_stmt_bind_param($stmt, 's', $email); // Bind parameters
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt); // Store the result for num_rows

        $rowCount = mysqli_stmt_num_rows($stmt);

        if ($rowCount > 0) {
            mysqli_stmt_bind_result($stmt, $userroleID, $email, $username, $hashed_password, $role);
            mysqli_stmt_fetch($stmt);

            // Verify the password
            if (password_verify($password, $hashed_password)) {
                $_SESSION['userroleid'] = $userroleID; // Set session
                $_SESSION['username'] = $username;
                $_SESSION['userrole'] = $role;
                $_SESSION['email'] = $email; // Set session
                
                $role_lower = strtolower($role);
                switch ($role_lower) {
                    case 'manager':
                        echo "<script>alert('Login Successful!'); window.location.href = '../homepage/manager_homepage.php';</script>";
                        exit();
                    case 'admin':
                        echo "<script>alert('Login Successful!'); window.location.href = '../homepage/admin_homepage.php';</script>";
                        exit();
                    case 'reviewer':
                        echo "<script>alert('Login Successful!'); window.location.href = '../profile/staff.php';</script>";
                        exit();
                }
            } else {
                echo "<script>alert('Incorrect email or password.');</script>";
            } 
        } else {
            echo "<script>alert('No user found with that email.');</script>";
        }

        // Close the statement
        mysqli_stmt_close($stmt);
    }
}

// Close the database connection
mysqli_close($connection);
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>login</title>
    <link rel="stylesheet" href="login.css">

</head>
<body>
    <div class="container">
        <div class="login">
            <form method="POST">
                <h2>Login<h2>
                <div class="input-box">
                    <span class="icon"><ion-icon name="mail-outline"></ion-icon></i></span>
                    <input type="email" name="email" id="email" placeholder="Email" required>
                </div>
                <div class="input-box">
                    <span class="icon" onclick="togglePassword()"><ion-icon name="lock-closed-outline" id="lock"></ion-icon></span>
                    <input type="password" name="password" id="password" placeholder="Password" required>
                </div>
                <div class="remember_forgot">
                    <a href="../forgot_password/forgot_password.php">Forgot Password?</a>
                </div> 
                <button type="submit" name="submit" id="submit">Login</button>
                <div class="register">
                    <a href="../register/member_register.php">Don't have an account? Create Now!</a>
                </div>
            </form>
        </div>
    </div>
<script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
<script>
    function togglePassword() {
        let passwordField = document.getElementById("password");
        let lockIcon = document.getElementById("lock");

        if (passwordField.type === "password") {
            passwordField.type = "text"; // Show password
            lockIcon.setAttribute("name", "lock-open-outline"); // Change icon
        } else {
            passwordField.type = "password"; // Hide password
            lockIcon.setAttribute("name", "lock-closed-outline"); // Change icon back
        }
    }
</script>

</body>
</html>