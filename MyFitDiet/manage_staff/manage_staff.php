<?php
ob_start();
include '../general/dbconn.php'; // Ensure this path is correct
session_start();
include '../general/manager-nav.php';
// Initialize search and filter variables
$search = $_GET['search'] ?? '';
$role_filter = $_GET['role'] ?? 'all';
$status_filter = $_GET['status'] ?? 'all';

// Base query
$query = "SELECT userroleid, email, username, role, status FROM staff WHERE 1=1";

// Add search condition
if (!empty($search)) {
    $query .= " AND (username LIKE ? OR email LIKE ?)";
    $search_term = "%$search%";
}

// Add role filter
if ($role_filter !== 'all') {
    $query .= " AND role = ?";
}

// Add status filter
if ($status_filter !== 'all') {
    $query .= " AND status = ?";
}

// Prepare and execute the query
$stmt = $connection->prepare($query);

if (!$stmt) {
    die("Error preparing query: " . $connection->error);
}

// Bind parameters dynamically
$param_types = '';
$param_values = [];

if (!empty($search)) {
    $param_types .= 'ss';
    $param_values[] = $search_term;
    $param_values[] = $search_term;
}

if ($role_filter !== 'all') {
    $param_types .= 's';
    $param_values[] = $role_filter;
}

if ($status_filter !== 'all') {
    $param_types .= 's';
    $param_values[] = $status_filter;
}

if (!empty($param_types)) {
    $stmt->bind_param($param_types, ...$param_values);
}

$stmt->execute();
$result_manage = $stmt->get_result();

if (!$result_manage) {
    die("Error fetching staff data: " . $connection->error);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_information') {
        // Update staff information
        $userroleid = $_POST['userroleid'];
        $email = $_POST['email'];
        $username = $_POST['username'];
        $role = $_POST['role'];
        $status = $_POST['status'];

        $update_query = "UPDATE staff SET email = ?, username = ?, role = ?, status = ? WHERE userroleid = ?";
        $update_stmt = $connection->prepare($update_query);
        $update_stmt->bind_param("ssssi", $email, $username, $role, $status, $userroleid);
        $update_stmt->execute();

        // Redirect to refresh the page
        header("Location: manage_staff.php");
        exit();
    } elseif ($action === 'delete') {
        // Delete staff
        $userroleid = $_POST['userroleid'];

        $delete_query = "DELETE FROM staff WHERE userroleid = ?";
        $delete_stmt = $connection->prepare($delete_query);
        $delete_stmt->bind_param("i", $userroleid);
        $delete_stmt->execute();

        // Redirect to refresh the page
        header("Location: manage_staff.php");
        exit();
    }
}
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Staff</title>
    <link rel="stylesheet" href="manage_staff.css?v=<?php echo time(); ?>">
    <script defer src="manage_staff.js?v=<?php echo time(); ?>"></script>
</head>
<body>
    <section class="search-filter">
        <!-- Search Bar -->
        <div class="search-bar">
            <form method="GET" action="manage_staff.php">
                <input type="text" name="search" placeholder="Search by username or email" value="<?= htmlspecialchars($search); ?>">
                <button type="submit">Search</button>
            </form>
        </div>

        <!-- Filter Bar -->
        <div class="filter-bar">
            <form method="GET" action="manage_staff.php">
                <div class="filter-options">
                    <!-- Role Filter -->
                    <select name="role">
                        <option value="all" <?= $role_filter === 'all' ? 'selected' : ''; ?>>All Roles</option>
                        <option value="Manager" <?= $role_filter === 'Manager' ? 'selected' : ''; ?>>Manager</option>
                        <option value="Admin" <?= $role_filter === 'Admin' ? 'selected' : ''; ?>>Admin</option>
                        <option value="Reviewer" <?= $role_filter === 'Reviewer' ? 'selected' : ''; ?>>Reviewer</option>
                    </select>

                    <!-- Status Filter -->
                    <select name="status">
                        <option value="all" <?= $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="Active" <?= $status_filter === 'Active' ? 'selected' : ''; ?>>Active</option>
                        <option value="Block" <?= $status_filter === 'Block' ? 'selected' : ''; ?>>Block</option>
                    </select>
                </div>
                <button type="submit">Filter</button>
            </form>
        </div>
    </section>

    <section class="challenge-levels">
        <h2>Manage Staff</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Email</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($manage = $result_manage->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($manage['userroleid']); ?></td>
                        <td><?= htmlspecialchars($manage['email']); ?></td>
                        <td><?= htmlspecialchars($manage['username']); ?></td>
                        <td><?= htmlspecialchars($manage['role']); ?></td>
                        <td><?= htmlspecialchars($manage['status']); ?></td>
                        <td>
                            <button class="edit-staff-btn" data-level-id="<?= htmlspecialchars($manage['userroleid']); ?>">Edit</button>
                            <button class="delete-staff-btn" data-level-id="<?= htmlspecialchars($manage['userroleid']); ?>">Delete</button>
                        </td>   
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <button id="addStaffBtn"><a href="../register/staff_register.php">Add New Staff</a></button>
    </section>

    <div id="editStaffModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Edit Staff Information</h2>
            <form id="editStaffForm" method="POST">
                <input type="hidden" name="action" value="update_information">
                <input type="hidden" name="userroleid" id="edituserroleid">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
                <div class="form-group">
                    <label for="role">Role:</label>
                    <select id="role" name="role" required>
                        <option value="Manager">Manager</option>
                        <option value="Admin">Admin</option>
                        <option value="Reviewer">Reviewer</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="status">Status:</label>
                    <select id="status" name="status" required>
                        <option value="Active">Active</option>
                        <option value="Block">Block</option>
                    </select>
                </div>
                <button type="submit" class="save-changes">Save Changes</button>
            </form>
        </div>
    </div>

    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>⚠️ Delete Staff</h2>
            <div class="modal-buttons">
                <button class="cancel-delete">Cancel</button>
                <form id="deleteForm" method="POST" style="display:inline;">
                    <input type="hidden" name="userroleid" id="userroleid">
                    <input type="hidden" name="action" value="delete">
                    <button type="submit" class="confirm-delete">Delete</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>