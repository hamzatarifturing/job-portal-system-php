<?php
// Start the session
session_start();

// Check if user is logged in and is an employer
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'employer') {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

// Include database configuration and header
include_once("../includes/db_config.php");
include_once("../includes/header.php");

// Get employer ID from session
$employer_id = $_SESSION['user_id'];

// Get filter parameters
$job_filter = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0;
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Build the SQL query with filters
$query = "SELECT ja.*, jp.title AS job_title, u.first_name, u.last_name, u.email 
          FROM job_applications ja
          JOIN job_postings jp ON ja.job_id = jp.id
          JOIN users u ON ja.user_id = u.id
          WHERE jp.user_id = ?";

// Add filters if set
$params = array($employer_id);
$types = "i";

if ($job_filter > 0) {
    $query .= " AND jp.id = ?";
    $params[] = $job_filter;
    $types .= "i";
}

if (!empty($status_filter)) {
    $query .= " AND ja.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

// Order by application date (most recent first)
$query .= " ORDER BY ja.application_date DESC";

// Prepare and execute the query
$stmt = mysqli_prepare($conn, $query);

// Check if statement preparation was successful
if ($stmt) {
    // Bind parameters - PHP5 compatible way
    call_user_func_array('mysqli_stmt_bind_param', array_merge(array($stmt, $types), refValues($params)));
    
    // Execute the query
    mysqli_stmt_execute($stmt);
    
    // Get results
    $result = mysqli_stmt_get_result($stmt);
    $applications = array();
    while($row = mysqli_fetch_assoc($result)) {
        $applications[] = $row;
    }
    
    // Close statement
    mysqli_stmt_close($stmt);
} else {
    $error_message = "Database error: " . mysqli_error($conn);
}

// Get employer's job postings for filter dropdown
$jobs_query = "SELECT id, title FROM job_postings WHERE user_id = ? ORDER BY created_at DESC";
$jobs_stmt = mysqli_prepare($conn, $jobs_query);

$jobs = array();
if ($jobs_stmt) {
    mysqli_stmt_bind_param($jobs_stmt, "i", $employer_id);
    mysqli_stmt_execute($jobs_stmt);
    $jobs_result = mysqli_stmt_get_result($jobs_stmt);
    while($row = mysqli_fetch_assoc($jobs_result)) {
        $jobs[] = $row;
    }
    mysqli_stmt_close($jobs_stmt);
}

// Helper function for PHP5 compatibility - this allows passing parameters by reference
function refValues($arr) {
    $refs = array();
    foreach ($arr as $key => $value) {
        $refs[$key] = &$arr[$key];
    }
    return $refs;
}

// Function to get status badge color - PHP5 compatible way
function getStatusColor($status) {
    switch($status) {
        case 'pending':
            return 'warning';
        case 'reviewed':
            return 'info';
        case 'shortlisted':
            return 'primary';
        case 'hired':
            return 'success';
        case 'rejected':
            return 'danger';
        default:
            return 'secondary';
    }
}
?>

<div class="container mt-4">
    <h1>Job Applications</h1>
    
    <?php if(isset($error_message)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>
    
    <!-- Filter section -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="m-0">Filter Applications</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="job_applications.php" class="row">
                <div class="col-md-5 mb-2">
                    <label for="job_id">Filter by Job:</label>
                    <select name="job_id" id="job_id" class="form-control">
                        <option value="0">All Jobs</option>
                        <?php foreach($jobs as $job): ?>
                            <option value="<?php echo $job['id']; ?>" <?php echo ($job_filter == $job['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($job['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5 mb-2">
                    <label for="status">Filter by Status:</label>
                    <select name="status" id="status" class="form-control">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php echo ($status_filter === 'pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="reviewed" <?php echo ($status_filter === 'reviewed') ? 'selected' : ''; ?>>Reviewed</option>
                        <option value="shortlisted" <?php echo ($status_filter === 'shortlisted') ? 'selected' : ''; ?>>Shortlisted</option>
                        <option value="rejected" <?php echo ($status_filter === 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                        <option value="hired" <?php echo ($status_filter === 'hired') ? 'selected' : ''; ?>>Hired</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2 align-self-end">
                    <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Applications list -->
    <?php if(empty($applications)): ?>
        <div class="alert alert-info">
            <h4>No applications found</h4>
            <p>There are no job applications matching your criteria. Try changing your filters or check back later.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Applicant Name</th>
                        <th>Job Position</th>
                        <th>Email</th>
                        <th>Applied Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($applications as $application): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($application['first_name'] . ' ' . $application['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($application['job_title']); ?></td>
                            <td><?php echo htmlspecialchars($application['email']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($application['application_date'])); ?></td>
                            <td>
                                <?php 
                                $status = isset($application['status']) ? $application['status'] : 'pending';
                                $statusColor = getStatusColor($status);
                                ?>
                                <span class="badge bg-<?php echo $statusColor; ?>">
                                    <?php echo ucfirst(htmlspecialchars($status)); ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="view_application.php?id=<?php echo $application['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        View Details
                                    </a>
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                            data-toggle="dropdown">
                                        <span class="caret"></span>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <form action="update_status.php" method="POST">
                                                <input type="hidden" name="application_id" value="<?php echo $application['id']; ?>">
                                                <input type="hidden" name="status" value="reviewed">
                                                <button type="submit" class="dropdown-item">Mark as Reviewed</button>
                                            </form>
                                        </li>
                                        <li>
                                            <form action="update_status.php" method="POST">
                                                <input type="hidden" name="application_id" value="<?php echo $application['id']; ?>">
                                                <input type="hidden" name="status" value="shortlisted">
                                                <button type="submit" class="dropdown-item">Shortlist</button>
                                            </form>
                                        </li>
                                        <li>
                                            <form action="update_status.php" method="POST">
                                                <input type="hidden" name="application_id" value="<?php echo $application['id']; ?>">
                                                <input type="hidden" name="status" value="hired">
                                                <button type="submit" class="dropdown-item">Mark as Hired</button>
                                            </form>
                                        </li>
                                        <li class="divider"></li>
                                        <li>
                                            <form action="update_status.php" method="POST">
                                                <input type="hidden" name="application_id" value="<?php echo $application['id']; ?>">
                                                <input type="hidden" name="status" value="rejected">
                                                <button type="submit" class="dropdown-item text-danger">Reject</button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php
// Include footer
include_once("../includes/footer.php");
?>
