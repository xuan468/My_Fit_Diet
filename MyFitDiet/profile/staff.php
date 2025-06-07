<?php
session_start();
include '../general/dbconn.php'; 
$userid = $_SESSION['userroleid'];
$userRole = strtolower($_SESSION['userrole']); // Assume this is set when the user logs in

switch ($userRole) {
    case 'admin':
        include '../general/admin-nav.php';
        break;
    case 'manager':
        include '../general/manager-nav.php';
        break;
    case 'reviewer':
        include '../general/reviewer-nav.php';
        break;
}

// Handle profile update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_all'])) {
        // Get form values
        $newUsername = $_POST['username'];
        $newGender = $_POST['gender'];
        $newAge = $_POST['age'];
        $newCountry = $_POST['country'];

        try {
            // Update General User Info
            $sqlUpdateUser = "UPDATE staff SET username = ?, gender = ?, age = ?, country = ? WHERE userroleID = ?";
            $stmtUpdateUser = $connection->prepare($sqlUpdateUser);
            $stmtUpdateUser->bind_param("ssisi", $newUsername, $newGender, $newAge, $newCountry, $userid);
            $stmtUpdateUser->execute();
            $stmtUpdateUser->close();

             // Check if a file was uploaded
             if (isset($_FILES["profile_pic"]) && $_FILES["profile_pic"]["error"] === 0) {
                $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
                $fileTmpPath = $_FILES["profile_pic"]["tmp_name"];
                $fileType = strtolower(pathinfo($_FILES["profile_pic"]["name"], PATHINFO_EXTENSION));

                if (in_array($fileType, $allowedTypes)) {
                    // Read file content as binary data
                    $imageData = file_get_contents($fileTmpPath);
                
                    // Store binary data in the database
                    $sql = "UPDATE staff SET profile_pic = ? WHERE userroleID = ?";
                    $stmt = $connection->prepare($sql);
                    $stmt->bind_param("si", $imageData, $userid);
                    $stmt->send_long_data(0, $imageData);
                    $stmt->execute();
                    $stmt->close();
                } else {
                    throw new Exception("Invalid file type. Only JPG, PNG, and GIF allowed.");
                }
            }
            echo "<script>
                window.location.href = window.location.href; // Refresh the page
                </script>";
            exit();
        }
        catch (Exception $e) {
            $connection->rollback();
            echo "Error: " . $e->getMessage();
        }
    }
}

// Fetch user details
$sqlUser = "SELECT * FROM staff WHERE userroleID = ?";
$stmtUser = $connection->prepare($sqlUser);
$stmtUser->bind_param("i", $userid);
$stmtUser->execute();
$userData = $stmtUser->get_result()->fetch_assoc();
$stmtUser->close();

if ($userData) { // Check if user exists
    $profilePic = $userData['profile_pic']; // Get profile picture

    if (!empty($profilePic)) {
        // Detect MIME type dynamically
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_buffer($finfo, $profilePic);
        finfo_close($finfo);

        // Convert binary data to Base64
        $imageSrc = "data:$mimeType;base64," . base64_encode($profilePic);
    } else {
        $imageSrc = "default-profile.png"; // Default profile picture
    }
} else {
    echo "<script>alert('User not found.'); window.history.back();</script>";
    exit;
}
$currentGender = $userData['gender'] ?? '';
$currentCountry = $userData['country'] ?? '';

$connection->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <link rel="stylesheet" href="staff.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="profile-page">
        <div class="general-info">
            
            <div class="main-profilepic" id="main-profilepic">
                <img src="<?php echo $imageSrc; ?>" alt="Main Profile Picture" class="main-profile-pic">
            </div>

            <div class="main-info">
                <div class="profile-setting">
                    <button class="settings-btn" onclick="toggleForm()">Edit General Info</button>
                </div>
                <p class="username"><?php echo htmlspecialchars($userData['username']); ?></p>
                <p class="About">𝘼𝘽𝙊𝙐𝙏</p>
                <div class="info-item">
                    <span class="label">Role</span> 
                    <span class="data"><?php echo htmlspecialchars($userData['role']); ?></span>
                </div>
                <div class="info-item">
                    <span class="label">Gender</span> 
                    <span class="data"><?php echo htmlspecialchars($userData['gender']); ?></span>
                </div>
                <div class="info-item">
                    <span class="label">Age</span> 
                    <span class="data"><?php echo htmlspecialchars($userData['age']); ?></span>
                </div>
                <div class="info-item">
                    <span class="label">Country</span> 
                    <span class="data"><?php echo htmlspecialchars($userData['country']); ?></span>
                </div>
            </div>
        </div>
    </div>            

    <div class="modal-overlay" id="modalOverlay"></div>
    <div id="settings-form" class="settings-form">
        <form action="" method="POST" id="profileForm" enctype="multipart/form-data">
            <button class="close-btn" type="button" onclick="closeForm()">X</button>
            <div class="profilepic-placeholder" id="profilepic-placeholder">
                <img id="profilePic" class="profile-pic" src="<?php echo $imageSrc; ?>" alt="Profile Picture">
                <label for="fileInput" class="camera-icon">📷</label>
                <input type="file" id="fileInput" class="file-input" name="profile_pic" accept="image/*">
            </div>

            <div class="right-info">
                <div class="info">
                    <p>Username <input type="text" name="username" value="<?php echo htmlspecialchars($userData['username']); ?>"></p>
                    <p>Gender 
                        <select name="gender">
                            <option value="Select gender" hidden><?php echo empty($userData['gender']) ? 'Select gender' : htmlspecialchars($currentGender); ?></option>
                            <option value="Male" <?php echo ($currentGender == 'Male') ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo ($currentGender == 'Female') ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo ($currentGender == 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </p>
                    <p>Age <input type="number" name="age" value="<?php echo htmlspecialchars($userData['age']); ?>"></p>
                    <p>Country 
                        <select id="country" name="country" class="form-control">
                            <option value="Select country" hidden><?php echo empty($userData['country']) ? 'Select country' : htmlspecialchars($userData['country']); ?></option>
                            <option value="Afghanistan" <?php echo ($currentCountry == 'Afghanistan') ? 'selected' : ''; ?>>Afghanistan</option>
                            <option value="Åland Islands" <?php echo ($currentCountry == 'Åland Islands') ? 'selected' : ''; ?>>Åland Islands</option>
                            <option value="Albania" <?php echo ($currentCountry == 'Albania') ? 'selected' : ''; ?>>Albania</option>
                            <option value="Algeria" <?php echo ($currentCountry == 'Algeria') ? 'selected' : ''; ?>>Algeria</option>
                            <option value="American Samoa" <?php echo ($currentCountry == 'American Samoa') ? 'selected' : ''; ?>>American Samoa</option>
                            <option value="Andorra" <?php echo ($currentCountry == 'Andorra') ? 'selected' : ''; ?>>Andorra</option>
                            <option value="Angola" <?php echo ($currentCountry == 'Angola') ? 'selected' : ''; ?>>Angola</option>
                            <option value="Anguilla" <?php echo ($currentCountry == 'Anguilla') ? 'selected' : ''; ?>>Anguilla</option>
                            <option value="Antarctica" <?php echo ($currentCountry == 'Antarctica') ? 'selected' : ''; ?>>Antarctica</option>
                            <option value="Antigua and Barbuda" <?php echo ($currentCountry == 'Antigua and Barbuda') ? 'selected' : ''; ?>>Antigua and Barbuda</option>
                            <option value="Argentina" <?php echo ($currentCountry == 'Argentina') ? 'selected' : ''; ?>>Argentina</option>
                            <option value="Armenia" <?php echo ($currentCountry == 'Armenia') ? 'selected' : ''; ?>>Armenia</option>
                            <option value="Aruba" <?php echo ($currentCountry == 'Aruba') ? 'selected' : ''; ?>>Aruba</option>
                            <option value="Australia" <?php echo ($currentCountry == 'Australia') ? 'selected' : ''; ?>>Australia</option>
                            <option value="Austria" <?php echo ($currentCountry == 'Austria') ? 'selected' : ''; ?>>Austria</option>
                            <option value="Azerbaijan" <?php echo ($currentCountry == 'Azerbaijan') ? 'selected' : ''; ?>>Azerbaijan</option>
                            <option value="Bahamas" <?php echo ($currentCountry == 'Bahamas') ? 'selected' : ''; ?>>Bahamas</option>
                            <option value="Bahrain" <?php echo ($currentCountry == 'Bahrain') ? 'selected' : ''; ?>>Bahrain</option>
                            <option value="Bangladesh" <?php echo ($currentCountry == 'Bangladesh') ? 'selected' : ''; ?>>Bangladesh</option>
                            <option value="Barbados" <?php echo ($currentCountry == 'Barbados') ? 'selected' : ''; ?>>Barbados</option>
                            <option value="Belarus" <?php echo ($currentCountry == 'Belarus') ? 'selected' : ''; ?>>Belarus</option>
                            <option value="Belgium" <?php echo ($currentCountry == 'Belgium') ? 'selected' : ''; ?>>Belgium</option>
                            <option value="Belize" <?php echo ($currentCountry == 'Belize') ? 'selected' : ''; ?>>Belize</option>
                            <option value="Benin" <?php echo ($currentCountry == 'Benin') ? 'selected' : ''; ?>>Benin</option>
                            <option value="Bermuda" <?php echo ($currentCountry == 'Bermuda') ? 'selected' : ''; ?>>Bermuda</option>
                            <option value="Bhutan" <?php echo ($currentCountry == 'Bhutan') ? 'selected' : ''; ?>>Bhutan</option>
                            <option value="Bolivia" <?php echo ($currentCountry == 'Bolivia') ? 'selected' : ''; ?>>Bolivia</option>
                            <option value="Bosnia and Herzegovina" <?php echo ($currentCountry == 'Bosnia and Herzegovina') ? 'selected' : ''; ?>>Bosnia and Herzegovina</option>
                            <option value="Botswana" <?php echo ($currentCountry == 'Botswana') ? 'selected' : ''; ?>>Botswana</option>
                            <option value="Bouvet Island" <?php echo ($currentCountry == 'Bouvet Island') ? 'selected' : ''; ?>>Bouvet Island</option>
                            <option value="Brazil" <?php echo ($currentCountry == 'Brazil') ? 'selected' : ''; ?>>Brazil</option>
                            <option value="British Indian Ocean Territory" <?php echo ($currentCountry == 'British Indian Ocean Territory') ? 'selected' : ''; ?>>British Indian Ocean Territory</option>
                            <option value="Brunei Darussalam" <?php echo ($currentCountry == 'Brunei Darussalam') ? 'selected' : ''; ?>>Brunei Darussalam</option>
                            <option value="Bulgaria" <?php echo ($currentCountry == 'Bulgaria') ? 'selected' : ''; ?>>Bulgaria</option>
                            <option value="Burkina Faso" <?php echo ($currentCountry == 'Burkina Faso') ? 'selected' : ''; ?>>Burkina Faso</option>
                            <option value="Burundi" <?php echo ($currentCountry == 'Burundi') ? 'selected' : ''; ?>>Burundi</option>
                            <option value="Cambodia" <?php echo ($currentCountry == 'Cambodia') ? 'selected' : ''; ?>>Cambodia</option>
                            <option value="Cameroon" <?php echo ($currentCountry == 'Cameroon') ? 'selected' : ''; ?>>Cameroon</option>
                            <option value="Canada" <?php echo ($currentCountry == 'Canada') ? 'selected' : ''; ?>>Canada</option>
                            <option value="Cape Verde" <?php echo ($currentCountry == 'Cape Verde') ? 'selected' : ''; ?>>Cape Verde</option>
                            <option value="Cayman Islands" <?php echo ($currentCountry == 'Cayman Islands') ? 'selected' : ''; ?>>Cayman Islands</option>
                            <option value="Central African Republic" <?php echo ($currentCountry == 'Central African Republic') ? 'selected' : ''; ?>>Central African Republic</option>
                            <option value="Chad" <?php echo ($currentCountry == 'Chad') ? 'selected' : ''; ?>>Chad</option>
                            <option value="Chile" <?php echo ($currentCountry == 'Chile') ? 'selected' : ''; ?>>Chile</option>
                            <option value="China" <?php echo ($currentCountry == 'China') ? 'selected' : ''; ?>>China</option>
                            <option value="Christmas Island" <?php echo ($currentCountry == 'Christmas Island') ? 'selected' : ''; ?>>Christmas Island</option>
                            <option value="Cocos (Keeling) Islands" <?php echo ($currentCountry == 'Cocos (Keeling) Islands') ? 'selected' : ''; ?>>Cocos (Keeling) Islands</option>
                            <option value="Colombia" <?php echo ($currentCountry == 'Colombia') ? 'selected' : ''; ?>>Colombia</option>
                            <option value="Comoros" <?php echo ($currentCountry == 'Comoros') ? 'selected' : ''; ?>>Comoros</option>
                            <option value="Congo" <?php echo ($currentCountry == 'Congo') ? 'selected' : ''; ?>>Congo</option>
                            <option value="Congo, The Democratic Republic of The" <?php echo ($currentCountry == 'Congo, The Democratic Republic of The') ? 'selected' : ''; ?>>Congo, The Democratic Republic of The</option>
                            <option value="Cook Islands" <?php echo ($currentCountry == 'Cook Islands') ? 'selected' : ''; ?>>Cook Islands</option>
                            <option value="Costa Rica" <?php echo ($currentCountry == 'Costa Rica') ? 'selected' : ''; ?>>Costa Rica</option>
                            <option value="Cote D'ivoire" <?php echo ($currentCountry == "Cote D'ivoire") ? 'selected' : ''; ?>>Cote D'ivoire</option>
                            <option value="Croatia" <?php echo ($currentCountry == 'Croatia') ? 'selected' : ''; ?>>Croatia</option>
                            <option value="Cuba" <?php echo ($currentCountry == 'Cuba') ? 'selected' : ''; ?>>Cuba</option>
                            <option value="Cyprus" <?php echo ($currentCountry == 'Cyprus') ? 'selected' : ''; ?>>Cyprus</option>
                            <option value="Czech Republic" <?php echo ($currentCountry == 'Czech Republic') ? 'selected' : ''; ?>>Czech Republic</option>
                            <option value="Denmark" <?php echo ($currentCountry == 'Denmark') ? 'selected' : ''; ?>>Denmark</option>
                            <option value="Djibouti" <?php echo ($currentCountry == 'Djibouti') ? 'selected' : ''; ?>>Djibouti</option>
                            <option value="Dominica" <?php echo ($currentCountry == 'Dominica') ? 'selected' : ''; ?>>Dominica</option>
                            <option value="Dominican Republic" <?php echo ($currentCountry == 'Dominican Republic') ? 'selected' : ''; ?>>Dominican Republic</option>
                            <option value="Ecuador" <?php echo ($currentCountry == 'Ecuador') ? 'selected' : ''; ?>>Ecuador</option>
                            <option value="Egypt" <?php echo ($currentCountry == 'Egypt') ? 'selected' : ''; ?>>Egypt</option>
                            <option value="El Salvador" <?php echo ($currentCountry == 'El Salvador') ? 'selected' : ''; ?>>El Salvador</option>
                            <option value="Equatorial Guinea" <?php echo ($currentCountry == 'Equatorial Guinea') ? 'selected' : ''; ?>>Equatorial Guinea</option>
                            <option value="Eritrea" <?php echo ($currentCountry == 'Eritrea') ? 'selected' : ''; ?>>Eritrea</option>
                            <option value="Estonia" <?php echo ($currentCountry == 'Estonia') ? 'selected' : ''; ?>>Estonia</option>
                            <option value="Eswatini" <?php echo ($currentCountry == 'Eswatini') ? 'selected' : ''; ?>>Eswatini</option>
                            <option value="Ethiopia" <?php echo ($currentCountry == 'Ethiopia') ? 'selected' : ''; ?>>Ethiopia</option>
                            <option value="Fiji" <?php echo ($currentCountry == 'Fiji') ? 'selected' : ''; ?>>Fiji</option>
                            <option value="Finland" <?php echo ($currentCountry == 'Finland') ? 'selected' : ''; ?>>Finland</option>
                            <option value="France" <?php echo ($currentCountry == 'France') ? 'selected' : ''; ?>>France</option>
                            <option value="Gabon" <?php echo ($currentCountry == 'Gabon') ? 'selected' : ''; ?>>Gabon</option>
                            <option value="Gambia" <?php echo ($currentCountry == 'Gambia') ? 'selected' : ''; ?>>Gambia</option>
                            <option value="Georgia" <?php echo ($currentCountry == 'Georgia') ? 'selected' : ''; ?>>Georgia</option>
                            <option value="Germany" <?php echo ($currentCountry == 'Germany') ? 'selected' : ''; ?>>Germany</option>
                            <option value="Ghana" <?php echo ($currentCountry == 'Ghana') ? 'selected' : ''; ?>>Ghana</option>
                            <option value="Gibraltar" <?php echo ($currentCountry == 'Gibraltar') ? 'selected' : ''; ?>>Gibraltar</option>
                            <option value="Greece" <?php echo ($currentCountry == 'Greece') ? 'selected' : ''; ?>>Greece</option>
                            <option value="Greenland" <?php echo ($currentCountry == 'Greenland') ? 'selected' : ''; ?>>Greenland</option>
                            <option value="Grenada" <?php echo ($currentCountry == 'Grenada') ? 'selected' : ''; ?>>Grenada</option>
                            <option value="Guadeloupe" <?php echo ($currentCountry == 'Guadeloupe') ? 'selected' : ''; ?>>Guadeloupe</option>
                            <option value="Guam" <?php echo ($currentCountry == 'Guam') ? 'selected' : ''; ?>>Guam</option>
                            <option value="Guatemala" <?php echo ($currentCountry == 'Guatemala') ? 'selected' : ''; ?>>Guatemala</option>
                            <option value="Guinea" <?php echo ($currentCountry == 'Guinea') ? 'selected' : ''; ?>>Guinea</option>
                            <option value="Guinea-bissau" <?php echo ($currentCountry == 'Guinea-bissau') ? 'selected' : ''; ?>>Guinea-bissau</option>
                            <option value="Guyana" <?php echo ($currentCountry == 'Guyana') ? 'selected' : ''; ?>>Guyana</option>
                            <option value="Haiti" <?php echo ($currentCountry == 'Haiti') ? 'selected' : ''; ?>>Haiti</option>
                            <option value="Honduras" <?php echo ($currentCountry == 'Honduras') ? 'selected' : ''; ?>>Honduras</option>
                            <option value="Hong Kong" <?php echo ($currentCountry == 'Hong Kong') ? 'selected' : ''; ?>>Hong Kong</option>
                            <option value="Hungary" <?php echo ($currentCountry == 'Hungary') ? 'selected' : ''; ?>>Hungary</option>
                            <option value="Iceland" <?php echo ($currentCountry == 'Iceland') ? 'selected' : ''; ?>>Iceland</option>
                            <option value="India" <?php echo ($currentCountry == 'India') ? 'selected' : ''; ?>>India</option>
                            <option value="Indonesia" <?php echo ($currentCountry == 'Indonesia') ? 'selected' : ''; ?>>Indonesia</option>
                            <option value="Iran" <?php echo ($currentCountry == 'Iran') ? 'selected' : ''; ?>>Iran</option>
                            <option value="Iraq" <?php echo ($currentCountry == 'Iraq') ? 'selected' : ''; ?>>Iraq</option>
                            <option value="Ireland" <?php echo ($currentCountry == 'Ireland') ? 'selected' : ''; ?>>Ireland</option>
                            <option value="Israel" <?php echo ($currentCountry == 'Israel') ? 'selected' : ''; ?>>Israel</option>
                            <option value="Italy" <?php echo ($currentCountry == 'Italy') ? 'selected' : ''; ?>>Italy</option>
                            <option value="Jamaica" <?php echo ($currentCountry == 'Jamaica') ? 'selected' : ''; ?>>Jamaica</option>
                            <option value="Japan" <?php echo ($currentCountry == 'Japan') ? 'selected' : ''; ?>>Japan</option>
                            <option value="Jordan" <?php echo ($currentCountry == 'Jordan') ? 'selected' : ''; ?>>Jordan</option>
                            <option value="Kazakhstan" <?php echo ($currentCountry == 'Kazakhstan') ? 'selected' : ''; ?>>Kazakhstan</option>
                            <option value="Kenya" <?php echo ($currentCountry == 'Kenya') ? 'selected' : ''; ?>>Kenya</option>
                            <option value="Kiribati" <?php echo ($currentCountry == 'Kiribati') ? 'selected' : ''; ?>>Kiribati</option>
                            <option value="Korea, Democratic People's Republic of" <?php echo ($currentCountry == 'Korea, Democratic People\'s Republic of') ? 'selected' : ''; ?>>Korea, Democratic People's Republic of</option>
                            <option value="Korea, Republic of" <?php echo ($currentCountry == 'Korea, Republic of') ? 'selected' : ''; ?>>Korea, Republic of</option>
                            <option value="Kuwait" <?php echo ($currentCountry == 'Kuwait') ? 'selected' : ''; ?>>Kuwait</option>
                            <option value="Kyrgyzstan" <?php echo ($currentCountry == 'Kyrgyzstan') ? 'selected' : ''; ?>>Kyrgyzstan</option>
                            <option value="Lao People's Democratic Republic" <?php echo ($currentCountry == 'Lao People\'s Democratic Republic') ? 'selected' : ''; ?>>Lao People's Democratic Republic</option>
                            <option value="Latvia" <?php echo ($currentCountry == 'Latvia') ? 'selected' : ''; ?>>Latvia</option>
                            <option value="Lebanon" <?php echo ($currentCountry == 'Lebanon') ? 'selected' : ''; ?>>Lebanon</option>
                            <option value="Lesotho" <?php echo ($currentCountry == 'Lesotho') ? 'selected' : ''; ?>>Lesotho</option>
                            <option value="Liberia" <?php echo ($currentCountry == 'Liberia') ? 'selected' : ''; ?>>Liberia</option>
                            <option value="Libyan Arab Jamahiriya" <?php echo ($currentCountry == 'Libyan Arab Jamahiriya') ? 'selected' : ''; ?>>Libyan Arab Jamahiriya</option>
                            <option value="Liechtenstein" <?php echo ($currentCountry == 'Liechtenstein') ? 'selected' : ''; ?>>Liechtenstein</option>
                            <option value="Lithuania" <?php echo ($currentCountry == 'Lithuania') ? 'selected' : ''; ?>>Lithuania</option>
                            <option value="Luxembourg" <?php echo ($currentCountry == 'Luxembourg') ? 'selected' : ''; ?>>Luxembourg</option>
                            <option value="Macao" <?php echo ($currentCountry == 'Macao') ? 'selected' : ''; ?>>Macao</option>
                            <option value="Macedonia, The Former Yugoslav Republic of" <?php echo ($currentCountry == 'Macedonia, The Former Yugoslav Republic of') ? 'selected' : ''; ?>>Macedonia, The Former Yugoslav Republic of</option>
                            <option value="Madagascar" <?php echo ($currentCountry == 'Madagascar') ? 'selected' : ''; ?>>Madagascar</option>
                            <option value="Malawi" <?php echo ($currentCountry == 'Malawi') ? 'selected' : ''; ?>>Malawi</option>
                            <option value="Malaysia" <?php echo ($currentCountry == 'Malaysia') ? 'selected' : ''; ?>>Malaysia</option>
                            <option value="Maldives" <?php echo ($currentCountry == 'Maldives') ? 'selected' : ''; ?>>Maldives</option>
                            <option value="Mali" <?php echo ($currentCountry == 'Mali') ? 'selected' : ''; ?>>Mali</option>
                            <option value="Malta" <?php echo ($currentCountry == 'Malta') ? 'selected' : ''; ?>>Malta</option>
                            <option value="Marshall Islands" <?php echo ($currentCountry == 'Marshall Islands') ? 'selected' : ''; ?>>Marshall Islands</option>
                            <option value="Martinique" <?php echo ($currentCountry == 'Martinique') ? 'selected' : ''; ?>>Martinique</option>
                            <option value="Mauritania" <?php echo ($currentCountry == 'Mauritania') ? 'selected' : ''; ?>>Mauritania</option>
                            <option value="Mauritius" <?php echo ($currentCountry == 'Mauritius') ? 'selected' : ''; ?>>Mauritius</option>
                            <option value="Mayotte" <?php echo ($currentCountry == 'Mayotte') ? 'selected' : ''; ?>>Mayotte</option>
                            <option value="Mexico" <?php echo ($currentCountry == 'Mexico') ? 'selected' : ''; ?>>Mexico</option>
                            <option value="Micronesia, Federated States of" <?php echo ($currentCountry == 'Micronesia, Federated States of') ? 'selected' : ''; ?>>Micronesia, Federated States of</option>
                            <option value="Moldova, Republic of" <?php echo ($currentCountry == 'Moldova, Republic of') ? 'selected' : ''; ?>>Moldova, Republic of</option>
                            <option value="Monaco" <?php echo ($currentCountry == 'Monaco') ? 'selected' : ''; ?>>Monaco</option>
                            <option value="Mongolia" <?php echo ($currentCountry == 'Mongolia') ? 'selected' : ''; ?>>Mongolia</option>
                            <option value="Montenegro" <?php echo ($currentCountry == 'Montenegro') ? 'selected' : ''; ?>>Montenegro</option>
                            <option value="Montserrat" <?php echo ($currentCountry == 'Montserrat') ? 'selected' : ''; ?>>Montserrat</option>
                            <option value="Morocco" <?php echo ($currentCountry == 'Morocco') ? 'selected' : ''; ?>>Morocco</option>
                            <option value="Mozambique" <?php echo ($currentCountry == 'Mozambique') ? 'selected' : ''; ?>>Mozambique</option>
                            <option value="Myanmar" <?php echo ($currentCountry == 'Myanmar') ? 'selected' : ''; ?>>Myanmar</option>
                            <option value="Namibia" <?php echo ($currentCountry == 'Namibia') ? 'selected' : ''; ?>>Namibia</option>
                            <option value="Nauru" <?php echo ($currentCountry == 'Nauru') ? 'selected' : ''; ?>>Nauru</option>
                            <option value="Nepal" <?php echo ($currentCountry == 'Nepal') ? 'selected' : ''; ?>>Nepal</option>
                            <option value="Netherlands" <?php echo ($currentCountry == 'Netherlands') ? 'selected' : ''; ?>>Netherlands</option>
                            <option value="Netherlands Antilles" <?php echo ($currentCountry == 'Netherlands Antilles') ? 'selected' : ''; ?>>Netherlands Antilles</option>
                            <option value="New Caledonia" <?php echo ($currentCountry == 'New Caledonia') ? 'selected' : ''; ?>>New Caledonia</option>
                            <option value="New Zealand" <?php echo ($currentCountry == 'New Zealand') ? 'selected' : ''; ?>>New Zealand</option>
                            <option value="Nicaragua" <?php echo ($currentCountry == 'Nicaragua') ? 'selected' : ''; ?>>Nicaragua</option>
                            <option value="Niger" <?php echo ($currentCountry == 'Niger') ? 'selected' : ''; ?>>Niger</option>
                            <option value="Nigeria" <?php echo ($currentCountry == 'Nigeria') ? 'selected' : ''; ?>>Nigeria</option>
                            <option value="Niue" <?php echo ($currentCountry == 'Niue') ? 'selected' : ''; ?>>Niue</option>
                            <option value="Norfolk Island" <?php echo ($currentCountry == 'Norfolk Island') ? 'selected' : ''; ?>>Norfolk Island</option>
                            <option value="Northern Mariana Islands" <?php echo ($currentCountry == 'Northern Mariana Islands') ? 'selected' : ''; ?>>Northern Mariana Islands</option>
                            <option value="Norway" <?php echo ($currentCountry == 'Norway') ? 'selected' : ''; ?>>Norway</option>
                            <option value="Oman" <?php echo ($currentCountry == 'Oman') ? 'selected' : ''; ?>>Oman</option>
                            <option value="Pakistan" <?php echo ($currentCountry == 'Pakistan') ? 'selected' : ''; ?>>Pakistan</option>
                            <option value="Palau" <?php echo ($currentCountry == 'Palau') ? 'selected' : ''; ?>>Palau</option>
                            <option value="Palestinian Territory, Occupied" <?php echo ($currentCountry == 'Palestinian Territory, Occupied') ? 'selected' : ''; ?>>Palestinian Territory, Occupied</option>
                            <option value="Panama" <?php echo ($currentCountry == 'Panama') ? 'selected' : ''; ?>>Panama</option>
                            <option value="Papua New Guinea" <?php echo ($currentCountry == 'Papua New Guinea') ? 'selected' : ''; ?>>Papua New Guinea</option>
                            <option value="Paraguay" <?php echo ($currentCountry == 'Paraguay') ? 'selected' : ''; ?>>Paraguay</option>
                            <option value="Peru" <?php echo ($currentCountry == 'Peru') ? 'selected' : ''; ?>>Peru</option>
                            <option value="Philippines" <?php echo ($currentCountry == 'Philippines') ? 'selected' : ''; ?>>Philippines</option>
                            <option value="Pitcairn" <?php echo ($currentCountry == 'Pitcairn') ? 'selected' : ''; ?>>Pitcairn</option>
                            <option value="Poland" <?php echo ($currentCountry == 'Poland') ? 'selected' : ''; ?>>Poland</option>
                            <option value="Portugal" <?php echo ($currentCountry == 'Portugal') ? 'selected' : ''; ?>>Portugal</option>
                            <option value="Puerto Rico" <?php echo ($currentCountry == 'Puerto Rico') ? 'selected' : ''; ?>>Puerto Rico</option>
                            <option value="Qatar" <?php echo ($currentCountry == 'Qatar') ? 'selected' : ''; ?>>Qatar</option>
                            <option value="Reunion" <?php echo ($currentCountry == 'Reunion') ? 'selected' : ''; ?>>Reunion</option>
                            <option value="Romania" <?php echo ($currentCountry == 'Romania') ? 'selected' : ''; ?>>Romania</option>
                            <option value="Russian Federation" <?php echo ($currentCountry == 'Russian Federation') ? 'selected' : ''; ?>>Russian Federation</option>
                            <option value="Rwanda" <?php echo ($currentCountry == 'Rwanda') ? 'selected' : ''; ?>>Rwanda</option>
                            <option value="Saint Helena" <?php echo ($currentCountry == 'Saint Helena') ? 'selected' : ''; ?>>Saint Helena</option>
                            <option value="Saint Kitts and Nevis" <?php echo ($currentCountry == 'Saint Kitts and Nevis') ? 'selected' : ''; ?>>Saint Kitts and Nevis</option>
                            <option value="Saint Lucia" <?php echo ($currentCountry == 'Saint Lucia') ? 'selected' : ''; ?>>Saint Lucia</option>
                            <option value="Saint Pierre and Miquelon" <?php echo ($currentCountry == 'Saint Pierre and Miquelon') ? 'selected' : ''; ?>>Saint Pierre and Miquelon</option>
                            <option value="Saint Vincent and The Grenadines" <?php echo ($currentCountry == 'Saint Vincent and The Grenadines') ? 'selected' : ''; ?>>Saint Vincent and The Grenadines</option>
                            <option value="Samoa" <?php echo ($currentCountry == 'Samoa') ? 'selected' : ''; ?>>Samoa</option>
                            <option value="San Marino" <?php echo ($currentCountry == 'San Marino') ? 'selected' : ''; ?>>San Marino</option>
                            <option value="Sao Tome and Principe" <?php echo ($currentCountry == 'Sao Tome and Principe') ? 'selected' : ''; ?>>Sao Tome and Principe</option>
                            <option value="Saudi Arabia" <?php echo ($currentCountry == 'Saudi Arabia') ? 'selected' : ''; ?>>Saudi Arabia</option>
                            <option value="Senegal" <?php echo ($currentCountry == 'Senegal') ? 'selected' : ''; ?>>Senegal</option>
                            <option value="Serbia" <?php echo ($currentCountry == 'Serbia') ? 'selected' : ''; ?>>Serbia</option>
                            <option value="Seychelles" <?php echo ($currentCountry == 'Seychelles') ? 'selected' : ''; ?>>Seychelles</option>
                            <option value="Sierra Leone" <?php echo ($currentCountry == 'Sierra Leone') ? 'selected' : ''; ?>>Sierra Leone</option>
                            <option value="Singapore" <?php echo ($currentCountry == 'Singapore') ? 'selected' : ''; ?>>Singapore</option>
                            <option value="Slovakia" <?php echo ($currentCountry == 'Slovakia') ? 'selected' : ''; ?>>Slovakia</option>
                            <option value="Slovenia" <?php echo ($currentCountry == 'Slovenia') ? 'selected' : ''; ?>>Slovenia</option>
                            <option value="Solomon Islands" <?php echo ($currentCountry == 'Solomon Islands') ? 'selected' : ''; ?>>Solomon Islands</option>
                            <option value="Somalia" <?php echo ($currentCountry == 'Somalia') ? 'selected' : ''; ?>>Somalia</option>
                            <option value="South Africa" <?php echo ($currentCountry == 'South Africa') ? 'selected' : ''; ?>>South Africa</option>
                            <option value="South Georgia and The South Sandwich Islands" <?php echo ($currentCountry == 'South Georgia and The South Sandwich Islands') ? 'selected' : ''; ?>>South Georgia and The South Sandwich Islands</option>
                            <option value="Spain" <?php echo ($currentCountry == 'Spain') ? 'selected' : ''; ?>>Spain</option>
                            <option value="Sri Lanka" <?php echo ($currentCountry == 'Sri Lanka') ? 'selected' : ''; ?>>Sri Lanka</option>
                            <option value="Sudan" <?php echo ($currentCountry == 'Sudan') ? 'selected' : ''; ?>>Sudan</option>
                            <option value="Suriname" <?php echo ($currentCountry == 'Suriname') ? 'selected' : ''; ?>>Suriname</option>
                            <option value="Svalbard and Jan Mayen" <?php echo ($currentCountry == 'Svalbard and Jan Mayen') ? 'selected' : ''; ?>>Svalbard and Jan Mayen</option>
                            <option value="Swaziland" <?php echo ($currentCountry == 'Swaziland') ? 'selected' : ''; ?>>Swaziland</option>
                            <option value="Sweden" <?php echo ($currentCountry == 'Sweden') ? 'selected' : ''; ?>>Sweden</option>
                            <option value="Switzerland" <?php echo ($currentCountry == 'Switzerland') ? 'selected' : ''; ?>>Switzerland</option>
                            <option value="Syrian Arab Republic" <?php echo ($currentCountry == 'Syrian Arab Republic') ? 'selected' : ''; ?>>Syrian Arab Republic</option>
                            <option value="Taiwan" <?php echo ($currentCountry == 'Taiwan') ? 'selected' : ''; ?>>Taiwan</option>
                            <option value="Tajikistan" <?php echo ($currentCountry == 'Tajikistan') ? 'selected' : ''; ?>>Tajikistan</option>
                            <option value="Tanzania, United Republic of" <?php echo ($currentCountry == 'Tanzania, United Republic of') ? 'selected' : ''; ?>>Tanzania, United Republic of</option>
                            <option value="Thailand" <?php echo ($currentCountry == 'Thailand') ? 'selected' : ''; ?>>Thailand</option>
                            <option value="Timor-leste" <?php echo ($currentCountry == 'Timor-leste') ? 'selected' : ''; ?>>Timor-leste</option>
                            <option value="Togo" <?php echo ($currentCountry == 'Togo') ? 'selected' : ''; ?>>Togo</option>
                            <option value="Tokelau" <?php echo ($currentCountry == 'Tokelau') ? 'selected' : ''; ?>>Tokelau</option>
                            <option value="Tonga" <?php echo ($currentCountry == 'Tonga') ? 'selected' : ''; ?>>Tonga</option>
                            <option value="Trinidad and Tobago" <?php echo ($currentCountry == 'Trinidad and Tobago') ? 'selected' : ''; ?>>Trinidad and Tobago</option>
                            <option value="Tunisia" <?php echo ($currentCountry == 'Tunisia') ? 'selected' : ''; ?>>Tunisia</option>
                            <option value="Turkey" <?php echo ($currentCountry == 'Turkey') ? 'selected' : ''; ?>>Turkey</option>
                            <option value="Turkmenistan" <?php echo ($currentCountry == 'Turkmenistan') ? 'selected' : ''; ?>>Turkmenistan</option>
                            <option value="Turks and Caicos Islands" <?php echo ($currentCountry == 'Turks and Caicos Islands') ? 'selected' : ''; ?>>Turks and Caicos Islands</option>
                            <option value="Tuvalu" <?php echo ($currentCountry == 'Tuvalu') ? 'selected' : ''; ?>>Tuvalu</option>
                            <option value="Uganda" <?php echo ($currentCountry == 'Uganda') ? 'selected' : ''; ?>>Uganda</option>
                            <option value="Ukraine" <?php echo ($currentCountry == 'Ukraine') ? 'selected' : ''; ?>>Ukraine</option>
                            <option value="United Arab Emirates" <?php echo ($currentCountry == 'United Arab Emirates') ? 'selected' : ''; ?>>United Arab Emirates</option>
                            <option value="United Kingdom" <?php echo ($currentCountry == 'United Kingdom') ? 'selected' : ''; ?>>United Kingdom</option>
                            <option value="United States" <?php echo ($currentCountry == 'United States') ? 'selected' : ''; ?>>United States</option>
                            <option value="United States Minor Outlying Islands" <?php echo ($currentCountry == 'United States Minor Outlying Islands') ? 'selected' : ''; ?>>United States Minor Outlying Islands</option>
                            <option value="Uruguay" <?php echo ($currentCountry == 'Uruguay') ? 'selected' : ''; ?>>Uruguay</option>
                            <option value="Uzbekistan" <?php echo ($currentCountry == 'Uzbekistan') ? 'selected' : ''; ?>>Uzbekistan</option>
                            <option value="Vanuatu" <?php echo ($currentCountry == 'Vanuatu') ? 'selected' : ''; ?>>Vanuatu</option>
                            <option value="Venezuela (Bolivarian Republic of)" <?php echo ($currentCountry == 'Venezuela (Bolivarian Republic of)') ? 'selected' : ''; ?>>Venezuela (Bolivarian Republic of)</option>
                            <option value="Viet Nam" <?php echo ($currentCountry == 'Viet Nam') ? 'selected' : ''; ?>>Viet Nam</option>
                            <option value="Western Sahara" <?php echo ($currentCountry == 'Western Sahara') ? 'selected' : ''; ?>>Western Sahara</option>
                            <option value="Yemen" <?php echo ($currentCountry == 'Yemen') ? 'selected' : ''; ?>>Yemen</option>
                            <option value="Zambia" <?php echo ($currentCountry == 'Zambia') ? 'selected' : ''; ?>>Zambia</option>
                            <option value="Zimbabwe" <?php echo ($currentCountry == 'Zimbabwe') ? 'selected' : ''; ?>>Zimbabwe</option>
                        </select>
                    </p>
                </div>
            </div>

            <div class="upload-container">
                <button type="submit" name="update_all">Update</button>
            </div>
        </form>
    </div>
        </form>
    </div>
</body>
</html>

<script>
function toggleForm() {
    var form = document.getElementById("settings-form");
    var overlay = document.getElementById("modalOverlay");
    if (form.style.display === "none" || form.style.display === "") {
        form.style.display = "block";
        overlay.style.display = "block"; // Show overlay
        document.body.classList.add("modal-open"); // Disable interactions with the page
    } else {
        form.style.display = "none";
        overlay.style.display = "none"; // Hide overlay
        document.body.classList.remove("modal-open"); // Enable interactions with the page
    }
}


function closeForm() {
    document.getElementById('settings-form').style.display = 'none';
    document.getElementById("modalOverlay").style.display = "none"; 
    document.body.classList.remove("modal-open"); 
}


function submitForm() {
    document.getElementById('profileForm').submit();
}

document.addEventListener("DOMContentLoaded", function () {
    const fileInput = document.querySelector("#fileInput");
    const profilePic = document.querySelector("#profilepic-placeholder img"); // More precise selection

    fileInput.addEventListener("change", function (event) {
        const file = event.target.files[0];

        if (file) {
            const reader = new FileReader();

            reader.onload = function (e) {
                profilePic.src = e.target.result; // Ensures only the correct image is updated
                console.log("Profile picture updated!"); // Debugging message
            };

            reader.readAsDataURL(file);
        }
    });
});
</script>

<?php
include '../general/footer.php';
?>