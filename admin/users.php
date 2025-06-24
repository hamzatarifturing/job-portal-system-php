<?php
// Include session handling and authentication check
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Include database configuration
require_once '../includes/db_config.php';

// Set page title
$page_title = "User Management";

// Include header
include_once '../includes/header.php';

// Fetch all users except admin
$query = "SELECT * FROM users WHERE user_type != 'admin' ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);

// Handle user status update if requested
if (isset($_POST['action']) && isset($_POST['user_id'])) {
    $userId = mysqli_real_escape_string($conn, $_POST['user_id']);
    $action = mysqli_real_escape_string($conn, $_POST['action']);

    if ($action === 'activate') {
        $updateQuery = "UPDATE users SET status = 'active' WHERE id = '$userId'";
    } elseif ($action === 'suspend') {
        $updateQuery = "UPDATE users SET status = 'suspended' WHERE id = '$userId'";
    } elseif ($action === 'deactivate') {
        $updateQuery = "UPDATE users SET status = 'inactive' WHERE id = '$userId'";
    }

    if (isset($updateQuery)) {
        if (mysqli_query($conn, $updateQuery)) {
            $success_message = "User status updated successfully.";
            // Refresh the user list
            $result = mysqli_query($conn, $query);
        } else {
            $error_message = "Error updating user status: " . mysqli_error($conn);
        }
    }
}
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4>User Management</h4>
                    <div>
                        <input type="text" id="searchInput" class="form-control" placeholder="Search by name, email, or user type...">
                    </div>
                </div>
                <div class="card-body">
                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success"><?php echo $success_message; ?></div>
                    <?php endif; ?>
                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table table-striped" id="usersTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>User Type</th>
                                    <th>Status</th>
                                    <th>Last Login</th>
                                    <th>Registered</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($result) > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                        <tr>
                                            <td><?php echo $row['id']; ?></td>
                                            <td>
                                                <?php if (!empty($row['profile_image']) && $row['profile_image'] != 'default.jpg'): ?>
                                                    <img src="../uploads/profile/<?php echo $row['profile_image']; ?>" alt="Profile Image" class="rounded-circle mr-2" width="30" height="30">
                                                <?php else: ?>
                                                    <img src="../assets/images/default.jpg" alt="Default Profile" class="rounded-circle mr-2" width="30" height="30">
                                                <?php endif; ?>
                                                <?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                                            <td>
                                                <?php if ($row['user_type'] == 'jobseeker'): ?>
                                                    <span class="badge badge-info">Job Seeker</span>
                                                <?php elseif ($row['user_type'] == 'employer'): ?>
                                                    <span class="badge badge-primary">Employer</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($row['status'] == 'active'): ?>
                                                    <span class="badge badge-success">Active</span>
                                                <?php elseif ($row['status'] == 'inactive'): ?>
                                                    <span class="badge badge-warning">Inactive</span>
                                                <?php elseif ($row['status'] == 'suspended'): ?>
                                                    <span class="badge badge-danger">Suspended</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($row['last_login']): ?>
                                                    <?php echo date('M d, Y H:i', strtotime($row['last_login'])); ?>
                                                <?php else: ?>
                                                    Never
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                            <td>
                                                <div class="dropdown">
                                                    <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                        Actions
                                                    </button>
                                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                        <a class="dropdown-item" href="view_user.php?id=<?php echo $row['id']; ?>">View Details</a>
                                                        
                                                        <form method="POST">
                                                            <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                                            <?php if ($row['status'] != 'active'): ?>
                                                                <button type="submit" name="action" value="activate" class="dropdown-item">Activate</button>
                                                            <?php endif; ?>
                                                            
                                                            <?php if ($row['status'] != 'suspended'): ?>
                                                                <button type="submit" name="action" value="suspend" class="dropdown-item">Suspend</button>
                                                            <?php endif; ?>
                                                            
                                                            <?php if ($row['status'] != 'inactive'): ?>
                                                                <button type="submit" name="action" value="deactivate" class="dropdown-item">Deactivate</button>
                                                            <?php endif; ?>
                                                        </form>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No users found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Search functionality
document.getElementById('searchInput').addEventListener('keyup', function() {
    const searchText = this.value.toLowerCase();
    const table = document.getElementById('usersTable');
    const rows = table.getElementsByTagName('tr');

    for (let i = 1; i < rows.length; i++) { // Skip header row (i=0)
        const row = rows[i];
        const cells = row.getElementsByTagName('td');
        let shouldShow = false;

        // Don't search the last column (actions)
        for (let j = 0; j < cells.length - 1; j++) {
            const cellText = cells[j].textContent || cells[j].innerText;
            if (cellText.toLowerCase().indexOf(searchText) > -1) {
                shouldShow = true;
                break;
            }
        }
        
        row.style.display = shouldShow ? '' : 'none';
    }
});
</script>

<?php
// Include footer
include_once '../includes/footer.php';

// Close database connection
mysqli_close($conn);
?>