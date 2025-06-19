<?php
// Start the session
session_start();

// Check if user is logged in and is an admin
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

// Include database configuration and header
include_once("../includes/db_config.php");
include_once("../includes/header.php");

// Initialize variables
$success_message = "";
$error_message = "";

// Pagination setup
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Prepare the query to fetch users excluding admins
$count_query = "SELECT COUNT(*) AS total FROM users WHERE user_type != 'admin'";
$count_result = mysqli_query($conn, $count_query);
$count_row = mysqli_fetch_assoc($count_result);
$total_records = $count_row['total'];
$total_pages = ceil($total_records / $records_per_page);

// Fetch users with pagination
$users_query = "SELECT id, username, email, user_type, first_name, last_name, 
                status, is_verified, last_login, created_at 
                FROM users WHERE user_type != 'admin' 
                ORDER BY created_at DESC LIMIT ?, ?";
$users_stmt = mysqli_prepare($conn, $users_query);

if ($users_stmt) {
    mysqli_stmt_bind_param($users_stmt, "ii", $offset, $records_per_page);
    mysqli_stmt_execute($users_stmt);
    
    // Get the result set
    $result = mysqli_stmt_get_result($users_stmt);
    
    // If mysqli_stmt_get_result doesn't work (missing mysqlnd driver)
    if (!$result) {
        // Alternative approach for PHP 5 compatibility
        mysqli_stmt_bind_result($users_stmt, 
            $id, $username, $email, $user_type, 
            $first_name, $last_name, $status, 
            $is_verified, $last_login, $created_at
        );
        
        $users = array();
        while (mysqli_stmt_fetch($users_stmt)) {
            $users[] = array(
                'id' => $id,
                'username' => $username,
                'email' => $email,
                'user_type' => $user_type,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'status' => $status,
                'is_verified' => $is_verified,
                'last_login' => $last_login,
                'created_at' => $created_at
            );
        }
    } else {
        // If the function works, fetch all rows
        $users = array();
        while ($row = mysqli_fetch_assoc($result)) {
            $users[] = $row;
        }
    }
    
    mysqli_stmt_close($users_stmt);
} else {
    $error_message = "Database error: " . mysqli_error($conn);
}

// Function to handle status toggle
if (isset($_POST['toggle_status']) && isset($_POST['user_id'])) {
    $user_id = (int)$_POST['user_id'];
    $new_status = $_POST['new_status'] === 'active' ? 'active' : 'inactive';
    
    $update_query = "UPDATE users SET status = ? WHERE id = ? AND user_type != 'admin'";
    $update_stmt = mysqli_prepare($conn, $update_query);
    
    if ($update_stmt) {
        mysqli_stmt_bind_param($update_stmt, "si", $new_status, $user_id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            $success_message = "User status updated successfully!";
            header("Location: users.php");
            exit();
        } else {
            $error_message = "Failed to update user status: " . mysqli_error($conn);
        }
        
        mysqli_stmt_close($update_stmt);
    } else {
        $error_message = "Database error: " . mysqli_error($conn);
    }
}
?>

<div class="container mt-4">
    <h1 class="mb-4">Manage Users</h1>
    
    <?php if (!empty($error_message)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($error_message); ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($success_message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($success_message); ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="m-0">Users List</h5>
            <div>
                <span class="badge badge-light mr-2"><?php echo $total_records; ?> Total Users</span>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <?php if (!empty($users)): ?>
                <table class="table table-bordered table-striped table-hover">
                    <thead class="thead-dark">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>User Type</th>
                            <th>Status</th>
                            <th>Verified</th>
                            <th>Registration Date</th>
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['id']); ?></td>
                            <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo ($user['user_type'] == 'employer') ? 'primary' : 'success'; ?>">
                                    <?php echo htmlspecialchars(ucfirst($user['user_type'])); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo ($user['status'] == 'active') ? 'success' : 'danger'; ?>">
                                    <?php echo htmlspecialchars(ucfirst($user['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo ($user['is_verified'] == 1) ? 'success' : 'warning'; ?>">
                                    <?php echo ($user['is_verified'] == 1) ? 'Yes' : 'No'; ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <?php 
                                echo !empty($user['last_login']) ? 
                                    date('M d, Y H:i', strtotime($user['last_login'])) : 
                                    'Never logged in';
                                ?>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <form method="post" class="mr-1" onsubmit="return confirm('Are you sure you want to change this user\'s status?');">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="new_status" value="<?php echo ($user['status'] == 'active') ? 'inactive' : 'active'; ?>">
                                        <button type="submit" name="toggle_status" class="btn btn-sm btn-<?php echo ($user['status'] == 'active') ? 'warning' : 'success'; ?>">
                                            <?php echo ($user['status'] == 'active') ? 'Deactivate' : 'Activate'; ?>
                                        </button>
                                    </form>
                                    <a href="view_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-info">View</a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo ($page - 1); ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo ($page + 1); ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
                
                <?php else: ?>
                <div class="alert alert-info">
                    No users found.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- User Stats -->
    <div class="row">
        <div class="col-md-4">
            <div class="card border-primary mb-3">
                <div class="card-header bg-primary text-white">
                    <h5 class="m-0">Jobseekers</h5>
                </div>
                <div class="card-body text-center">
                    <p class="card-text display-4">
                        <?php 
                        $jobseeker_count_query = "SELECT COUNT(*) AS count FROM users WHERE user_type = 'jobseeker'";
                        $jobseeker_count_result = mysqli_query($conn, $jobseeker_count_query);
                        $jobseeker_count = mysqli_fetch_assoc($jobseeker_count_result);
                        echo $jobseeker_count['count'];
                        ?>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-success mb-3">
                <div class="card-header bg-success text-white">
                    <h5 class="m-0">Employers</h5>
                </div>
                <div class="card-body text-center">
                    <p class="card-text display-4">
                        <?php 
                        $employer_count_query = "SELECT COUNT(*) AS count FROM users WHERE user_type = 'employer'";
                        $employer_count_result = mysqli_query($conn, $employer_count_query);
                        $employer_count = mysqli_fetch_assoc($employer_count_result);
                        echo $employer_count['count'];
                        ?>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-danger mb-3">
                <div class="card-header bg-danger text-white">
                    <h5 class="m-0">Inactive Users</h5>
                </div>
                <div class="card-body text-center">
                    <p class="card-text display-4">
                        <?php 
                        $inactive_count_query = "SELECT COUNT(*) AS count FROM users WHERE status = 'inactive'";
                        $inactive_count_result = mysqli_query($conn, $inactive_count_query);
                        $inactive_count = mysqli_fetch_assoc($inactive_count_result);
                        echo $inactive_count['count'];
                        ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Actions -->
    <div class="card mb-4">
        <div class="card-header bg-secondary text-white">
            <h5 class="m-0">User Management Actions</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <a href="export_users.php" class="btn btn-block btn-outline-primary">
                        <i class="fa fa-download"></i> Export User Data
                    </a>
                </div>
                <div class="col-md-3 mb-3">
                    <a href="email_users.php" class="btn btn-block btn-outline-info">
                        <i class="fa fa-envelope"></i> Email Users
                    </a>
                </div>
                <div class="col-md-3 mb-3">
                    <a href="add_user.php" class="btn btn-block btn-outline-success">
                        <i class="fa fa-user-plus"></i> Add New User
                    </a>
                </div>
                <div class="col-md-3 mb-3">
                    <a href="dashboard.php" class="btn btn-block btn-outline-secondary">
                        <i class="fa fa-arrow-left"></i> Return to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once("../includes/footer.php");
?>