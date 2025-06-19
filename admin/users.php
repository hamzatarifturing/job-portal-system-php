<?php
// Start the session
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

// Include database configuration and header
include_once("../includes/db_config.php");
include_once("../includes/header.php");

// Handle user status toggle if submitted
if (isset($_POST['toggle_status']) && isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];
    $current_status = $_POST['current_status'];
    $new_status = ($current_status == 'active') ? 'inactive' : 'active';
    
    $update_query = "UPDATE users SET status = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, "si", $new_status, $user_id);
    
    if (mysqli_stmt_execute($stmt)) {
        if ($new_status == 'active') {
            $success_message = "User successfully activated!";
        } else {
            $success_message = "User successfully deactivated!";
        }
    } else {
        $error_message = "Failed to update user status: " . mysqli_error($conn);
    }
    
    mysqli_stmt_close($stmt);
}



// Configure pagination
$records_per_page = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $records_per_page;

// Configure filtering
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';
$where_clause = "user_type != 'admin'";

if ($filter == 'employer' || $filter == 'jobseeker') {
    $where_clause .= " AND user_type = '$filter'";
}

// Count total users for pagination
$count_sql = "SELECT COUNT(*) as total FROM users WHERE $where_clause";
$count_result = mysqli_query($conn, $count_sql);
$row = mysqli_fetch_assoc($count_result);
$total_users = $row['total'];
$total_pages = ceil($total_users / $records_per_page);

// Fetch users
$sql = "SELECT id, username, email, first_name, last_name, user_type, status, 
        DATE_FORMAT(created_at, '%Y-%m-%d') as register_date, 
        DATE_FORMAT(last_login, '%Y-%m-%d') as last_login 
        FROM users 
        WHERE $where_clause 
        ORDER BY id DESC 
        LIMIT ?, ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $offset, $records_per_page);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="m-0">User Management</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <strong>Success!</strong> <?php echo $success_message; ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>Error!</strong> <?php echo $error_message; ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <!-- Filter buttons -->
                    <div class="mb-3">
                        <div class="btn-group" role="group">
                            <a href="users.php" class="btn <?php echo $filter == '' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                All Users
                            </a>
                            <a href="users.php?filter=employer" class="btn <?php echo $filter == 'employer' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                Employers
                            </a>
                            <a href="users.php?filter=jobseeker" class="btn <?php echo $filter == 'jobseeker' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                Job Seekers
                            </a>
                        </div>
                    </div>

                    <!-- Users table -->
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead class="thead-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>User Type</th>
                                    <th>Status</th>
                                    <th>Register Date</th>
                                    <th>Last Login</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($result) > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                        <tr>
                                            <td><?php echo $row['id']; ?></td>
                                            <td>
                                                <?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $row['user_type'] == 'employer' ? 'primary' : 'info'; ?>">
                                                    <?php echo ucfirst($row['user_type']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?php echo $row['status'] == 'active' ? 'success' : 'danger'; ?>">
                                                    <?php echo ucfirst($row['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $row['register_date']; ?></td>
                                            <td><?php echo $row['last_login']; ?></td>
                                            <td>
                                                <form method="post">
                                                    <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                                    <input type="hidden" name="current_status" value="<?php echo $row['status']; ?>">
                                                    <button type="submit" name="toggle_status" class="btn btn-sm <?php echo $row['status'] == 'active' ? 'btn-warning' : 'btn-success'; ?> btn-block">
                                                        <?php echo $row['status'] == 'active' ? 'Deactivate' : 'Activate'; ?>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" class="text-center">No users found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav>
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="users.php?page=<?php echo $page-1; ?><?php echo $filter ? '&filter='.$filter : ''; ?>">
                                            Previous
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="users.php?page=<?php echo $i; ?><?php echo $filter ? '&filter='.$filter : ''; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="users.php?page=<?php echo $page+1; ?><?php echo $filter ? '&filter='.$filter : ''; ?>">
                                            Next
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once("../includes/footer.php");
?>
