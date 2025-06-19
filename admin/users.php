<?php
// Start the session
session_start();

// Check if user is logged in and is an admin
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    // Redirect to login page with error message if not admin
    header("Location: ../login.php?error=unauthorized");
    exit();
}

// Include database configuration
include_once("../includes/db_config.php");
include_once("../includes/header.php");

// Initialize variables for messages
$message = '';
$message_type = '';

// Handle user status change if submitted
if(isset($_POST['toggle_status']) && isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];
    $current_status = $_POST['current_status'];
    $new_status = ($current_status == 'active') ? 'inactive' : 'active';
    
    $update_query = "UPDATE users SET status = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, "si", $new_status, $user_id);
    
    if(mysqli_stmt_execute($stmt)) {
        $message = "User status updated successfully!";
        $message_type = "success";
    } else {
        $message = "Failed to update user status: " . mysqli_error($conn);
        $message_type = "danger";
    }
    
    mysqli_stmt_close($stmt);
}

// Setup pagination
$results_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start_from = ($page - 1) * $results_per_page;

// Setup filtering
$filter_type = isset($_GET['filter']) ? $_GET['filter'] : '';
$where_clause = "user_type != 'admin'";

if($filter_type == 'jobseeker' || $filter_type == 'employer') {
    $where_clause .= " AND user_type = '$filter_type'";
}

// Count total records for pagination
$count_query = "SELECT COUNT(*) as total FROM users WHERE $where_clause";
$count_result = mysqli_query($conn, $count_query);
$count_row = mysqli_fetch_assoc($count_result);
$total_records = $count_row['total'];
$total_pages = ceil($total_records / $results_per_page);

// Fetch users with pagination
$query = "SELECT id, username, email, user_type, first_name, last_name, status, 
          DATE(created_at) as register_date, DATE(last_login) as last_login_date 
          FROM users 
          WHERE $where_clause 
          ORDER BY created_at DESC 
          LIMIT ?, ?";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $start_from, $results_per_page);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Count user types for stats
$employer_count_query = "SELECT COUNT(*) as count FROM users WHERE user_type = 'employer'";
$jobseeker_count_query = "SELECT COUNT(*) as count FROM users WHERE user_type = 'jobseeker'";

$employer_result = mysqli_query($conn, $employer_count_query);
$jobseeker_result = mysqli_query($conn, $jobseeker_count_query);

$employer_count = mysqli_fetch_assoc($employer_result)['count'];
$jobseeker_count = mysqli_fetch_assoc($jobseeker_result)['count'];
?>

<div class="container mt-4">
    <h2>User Management</h2>
    
    <!-- Display messages if any -->
    <?php if(!empty($message)): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
        <?php echo $message; ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <?php endif; ?>
    
    <!-- User Statistics -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Users</h5>
                    <h2 class="card-text"><?php echo $total_records; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Employers</h5>
                    <h2 class="card-text"><?php echo $employer_count; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Job Seekers</h5>
                    <h2 class="card-text"><?php echo $jobseeker_count; ?></h2>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filter options -->
    <div class="filter-options mb-3">
        <div class="btn-group" role="group" aria-label="Filter options">
            <a href="users.php" class="btn btn-outline-primary <?php echo $filter_type == '' ? 'active' : ''; ?>">All Users</a>
            <a href="users.php?filter=employer" class="btn btn-outline-primary <?php echo $filter_type == 'employer' ? 'active' : ''; ?>">Employers</a>
            <a href="users.php?filter=jobseeker" class="btn btn-outline-primary <?php echo $filter_type == 'jobseeker' ? 'active' : ''; ?>">Job Seekers</a>
        </div>
    </div>
    
    <!-- Users Table -->
    <div class="card">
        <div class="card-header bg-dark text-white">
            <strong>User List</strong>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>User Type</th>
                            <th>Status</th>
                            <th>Registered</th>
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if(mysqli_num_rows($result) > 0) {
                            while($row = mysqli_fetch_assoc($result)) {
                                $full_name = $row['first_name'] . ' ' . $row['last_name'];
                                $status_class = ($row['status'] == 'active') ? 'success' : 'danger';
                                $status_text = ($row['status'] == 'active') ? 'Active' : 'Inactive';
                                $user_type_class = ($row['user_type'] == 'employer') ? 'primary' : 'info';
                        ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo htmlspecialchars($full_name); ?></td>
                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><span class="badge badge-<?php echo $user_type_class; ?>"><?php echo ucfirst($row['user_type']); ?></span></td>
                            <td>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                    <input type="hidden" name="current_status" value="<?php echo $row['status']; ?>">
                                    <button type="submit" name="toggle_status" class="badge badge-<?php echo $status_class; ?> border-0">
                                        <?php echo $status_text; ?>
                                    </button>
                                </form>
                            </td>
                            <td><?php echo $row['register_date']; ?></td>
                            <td><?php echo $row['last_login_date'] ? $row['last_login_date'] : 'Never'; ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="view_user.php?id=<?php echo $row['id']; ?>" class="btn btn-info btn-sm">
                                        <i class="fa fa-eye"></i> View
                                    </a>
                                    <a href="edit_user.php?id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm">
                                        <i class="fa fa-edit"></i> Edit
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php
                            }
                        } else {
                        ?>
                        <tr>
                            <td colspan="9" class="text-center">No users found</td>
                        </tr>
                        <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if($total_pages > 1): ?>
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <?php if($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="users.php?page=1<?php echo $filter_type ? '&filter=' . $filter_type : ''; ?>" aria-label="First">
                            <span aria-hidden="true">&laquo;&laquo;</span>
                        </a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="users.php?page=<?php echo $page - 1; ?><?php echo $filter_type ? '&filter=' . $filter_type : ''; ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php 
                    // Show page numbers
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $start_page + 4);
                    
                    for($i = $start_page; $i <= $end_page; $i++):
                    ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="users.php?page=<?php echo $i; ?><?php echo $filter_type ? '&filter=' . $filter_type : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                    
                    <?php if($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="users.php?page=<?php echo $page + 1; ?><?php echo $filter_type ? '&filter=' . $filter_type : ''; ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="users.php?page=<?php echo $total_pages; ?><?php echo $filter_type ? '&filter=' . $filter_type : ''; ?>" aria-label="Last">
                            <span aria-hidden="true">&raquo;&raquo;</span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php 
// Include footer
include_once("../includes/footer.php"); 
?>