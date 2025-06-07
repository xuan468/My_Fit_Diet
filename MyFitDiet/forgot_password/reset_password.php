<?php
session_start();
include '../general/dbconn.php'; // 包含数据库连接文件

if (!isset($_SESSION['reset_user'])) {
    header("Location: ../forgot_password/forgot_password.php"); // 如果未通过验证，重定向到忘记密码页面
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    if ($newPassword !== $confirmPassword) {
        $error = "Passwords do not match.";
    } else {
        // 更新密码
        $email = $_SESSION['reset_user'];
        $password = password_hash($newPassword, PASSWORD_DEFAULT); // 哈希密码

        $query = "UPDATE user SET password = ? WHERE email = ?";
        $stmt = $connection->prepare($query);
        $stmt->bind_param("ss", $password, $email);
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $success = "Your password has been reset successfully.";
                unset($_SESSION['reset_user']); // 清除会话
                header("Location: ../login/login.php");
                exit();
            } else {
                $error = "Failed to reset password.";
            }
        } else {
            $error = "Error executing query: " . $stmt->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="../forgot_password/reset_password.css">
</head>
<body>
    <div class="container">
        <h2>Reset Password</h2>
        <?php if ($error): ?>
            <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert success"><?php echo $success; ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label for="new_password">New Password:</label>
                <input type="password" id="new_password" name="new_password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit">Reset Password</button>
        </form>
    </div>
</body>
</html>