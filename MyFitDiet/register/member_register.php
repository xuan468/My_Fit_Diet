<?php
include '../general/dbconn.php';
session_start();

$messages = array();

if (isset($_POST["submit"])) {
    // Retrieve form data
    $email = $_POST["email"];
    $username = $_POST["username"];
    $password = $_POST["password"];
    $gender = $_POST["gender"];
    $age = $_POST["age"];
    $country = $_POST["country"];
    $parents_name = $_POST["parents_name"];
    $birthday_date = $_POST["birthday_date"];
    $favorite_food = $_POST["favorite_food"];

    // Validate form fields
    if (empty($email) || empty($username) || empty($password) || empty($gender) || empty($age) || empty($country) || empty($parents_name) || empty($birthday_date) || empty($favorite_food)) {
        array_push($messages, "All fields should be filled");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        array_push($messages, "Invalid email format");
    }

    if (!preg_match('/^[a-zA-Z0-9_ ]+$/', $username)) {
        array_push($messages, "Invalid username format");
    }

    if (strlen($password) < 6) {
        array_push($messages, "Password must be at least 6 characters long"); 
    }

    if (!in_array($gender, ['Male', 'Female'])) {
        array_push($messages, "Please choose a valid gender");
    }

    if (!filter_var($age, FILTER_VALIDATE_INT) || $age < 18 || $age > 100) {
        array_push($messages, "Invalid age (must be between 18 and 100)");
    }

    if (!preg_match('/^[a-zA-Z0-9_ ]+$/', $parents_name)) {
        array_push($messages, "Invalid format");
    }

    if (!preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $birthday_date)) {
        array_push($messages, "Invalid date format. Please use dd/mm/yyyy.");
    }

    if (!preg_match('/^[a-zA-Z0-9_ ]+$/', $favorite_food)) {
        array_push($messages, "Invalid format");
    }

    // Check if profile picture exists and has no upload errors
    if (!isset($_FILES['profile_pic']) || $_FILES['profile_pic']['error'] != UPLOAD_ERR_OK) {
        array_push($messages, "Profile picture upload failed");
    } else {
        // Validate file type and size
        $file_tmp = $_FILES['profile_pic']['tmp_name'];
        $file_name = $_FILES['profile_pic']['name'];
        $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png'];

        if (!in_array($file_extension, $allowed_extensions)) {
            array_push($messages, "Invalid profile picture format (Allowed: JPG, JPEG, PNG)");
        }

        if ($_FILES['profile_pic']['size'] > 10 * 1024 * 1024) {
            array_push($messages, "Profile picture size should not exceed 10MB");
        }

        // Read image data if no errors
        if (count($messages) == 0) {
            $profile_pic = file_get_contents($file_tmp);
        }
    }

    // Check if email is already registered
    $sql = "SELECT userID FROM user WHERE email = ?";
    $stmt = mysqli_prepare($connection, $sql);
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        array_push($messages, "Email has already been registered");
    }
    mysqli_stmt_close($stmt);

    // Display errors if any
    if (!empty($messages)) {
        foreach ($messages as $message) {
            echo "<script>alert('$message');</script>";
        }
    } else {
        // Insert data into database (with binary image data)
        $sql = "INSERT INTO user (email, username, password, gender, age, country, profile_pic, status, parents_name, birthday_date, favorite_food) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($connection, $sql);
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $status = 'Active';
        mysqli_stmt_bind_param($stmt, 'ssssisbssss', $email, $username, $hashed_password, $gender, $age, $country, $profile_pic, $status, $parents_name, $birthday_date, $favorite_food);
        mysqli_stmt_send_long_data($stmt, 6, $profile_pic); // Send binary data for LONGBLOB

        if (mysqli_stmt_execute($stmt)) {
            echo "<script>alert('You are registered successfully'); window.location.href = '../login/login.php';</script>";
            exit();
        } else {
            echo "<script>alert('Error during registration, please check again');</script>";
        }

        mysqli_stmt_close($stmt);
    }
}

mysqli_close($connection);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="member_register.css?">
</head>
<body>
    <div class="container">
        <div class="register">
            <form method="POST" enctype="multipart/form-data">
                <div class="form-container">
                    <div class="left-section">
                        <div class="title">Registration</div>
                        <div class='user-details'>
                            <div class='input-box'>
                                <span class='details'>Email</span>
                                <input type='email' name='email' placeholder='Enter your email' required>
                            </div>
                        </div>
                        <div class='user-details'>
                            <div class='input-box'>
                                <span class='details'>Username</span>
                                <input type='text' name='username' placeholder='Enter your username' required>
                            </div>
                        </div>
                        <div class='user-details'>
                            <div class='input-box'>
                                <span class='details'>Password</span>
                                <span class="icon" onclick="togglePassword()"><ion-icon name="lock-closed-outline" id="lock"></ion-icon></span>
                                <input type='password' name='password' id='password' placeholder='Enter your password' required>
                            </div>
                        </div>
                        <div class='user-details'>
                            <div class='input-box'>
                                <span class='details'>Age</span>
                                <input type='text' name='age' placeholder='Enter your age' required>
                            </div>
                        </div>
                        <div class='user-details'>
                            <div class='input-box'>
                                <span class='details'>Gender</span>
                                <select id="gender" name="gender" required>
                                    <option value="Choose_your_gender">Choose your gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                        </div>
                        <div class='user-details'>
                            <div class='input-box'>
                                <span class='details'>Country</span>
                                <select id="country" name="country">
                                    <option value="">Loading...</option>
                                </select>
                            </div>
                        </div>
                        <div class="profile_pic">
                            <label for="profile_pic" class="file-label">Upload Profile Picture</label>
                            <input type="file" id="profile_pic" name="profile_pic" accept="image/*" onchange="showFileName()">
                            <span class="file-name" id="file-name">No file chosen</span>
                        </div>
                    </div>
                    <div class="divider"></div>
                    <div class="right-section">
                        <div class="title">Security Verification</div>
                        <div class='user-details'>
                            <div class='input-box'>
                                <span class='details'>Parents Name</span>
                                <input type='text' name='parents_name' placeholder='Enter your parents name' required>
                            </div>
                        </div>
                        <div class='user-details'>
                            <div class='input-box'>
                                <span class='details'>Birthday Date</span>
                                <input type='text' name='birthday_date' placeholder='Enter your birthday date' required>
                            </div>
                        </div>
                        <div class='user-details'>
                            <div class='input-box'>
                                <span class='details'>Favorite Food</span>
                                <input type='text' name='favorite_food' placeholder='Enter your favorite food' required>
                            </div>
                        </div>
                        <div class="button-container">
                            <a href="../login/login.php" class="button back-button">Back</a>
                            <input type="submit" name="submit" class="button" value="Submit">
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</body>
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
<script>
        // Fetch country list from API
        async function loadCountries() {
            const response = await fetch('https://restcountries.com/v3.1/all');
            const countries = await response.json();

            const countrySelect = document.getElementById("country");
            countrySelect.innerHTML = '<option value="">Select Country</option>'; // Reset options

            countries.sort((a, b) => a.name.common.localeCompare(b.name.common)) // Sort alphabetically
                .forEach(country => {
                    const option = document.createElement("option");
                    option.value = country.cca2; // Use country code as value
                    option.textContent = country.name.common;
                    countrySelect.appendChild(option);
                });
        }

        loadCountries(); // Call function to populate the dropdown
</script>
<script>
    function showFileName() {
        const fileInput = document.getElementById("profile_pic");
        const fileNameDisplay = document.getElementById("file-name");
        fileNameDisplay.textContent = fileInput.files.length > 0 ? fileInput.files[0].name : "No file chosen";
    }
</script>
</body>
</html>