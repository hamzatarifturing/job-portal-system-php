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
$page_title = "Job Postings Management";

// Include header
include_once '../includes/header.php';

// Set default filters
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';
$employer_filter = isset($_GET['employer']) ? mysqli_real_escape_string($conn, $_GET['employer']) : '';
$job_type_filter = isset($_GET['job_type']) ? mysqli_real_escape_string($conn, $_GET['job_type']) : '';
$location_filter = isset($_GET['location']) ? mysqli_real_escape_string($conn, $_GET['location']) : '';
$date_from = isset($_GET['date_from']) ? mysqli_real_escape_string($conn, $_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? mysqli_real_escape_string($conn, $_GET['date_to']) : '';
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// Process job status update if requested
if (isset($_POST['action']) && isset($_POST['job_id'])) {
    $jobId = mysqli_real_escape_string($conn, $_POST['job_id']);
    $action = mysqli_real_escape_string($conn, $_POST['action']);
    $new_status = '';

    switch ($action) {
        case 'publish':
            $new_status = 'Published';
            break;
        case 'close':
            $new_status = 'Closed';
            break;
        case 'mark_filled':
            $new_status = 'Filled';
            break;
        case 'mark_draft':
            $new_status = 'Draft';
            break;
    }

    if (!empty($new_status)) {
        $updateQuery = "UPDATE job_postings SET status = '$new_status' WHERE id = '$jobId'";
        if (mysqli_query($conn, $updateQuery)) {
            $success_message = "Job status updated successfully.";
        } else {
            $error_message = "Error updating job status: " . mysqli_error($conn);
        }
    }
}

// Build the query with all necessary joins
$query = "SELECT 
    jp.id, 
    jp.title, 
    jp.company_name, 
    jp.job_type,
    jp.location,
    jp.salary_min,
    jp.salary_max,
    jp.salary_period,
    jp.status,
    jp.created_at,
    jp.expiry_date,
    u.id as employer_id,
    u.first_name,
    u.last_name,
    u.company_name as employer_company_name,
    u.company_logo,
    (SELECT COUNT(*) FROM job_applications ja WHERE ja.job_id = jp.id) as application_count
FROM 
    job_postings jp
JOIN 
    users u ON jp.user_id = u.id
WHERE 1=1";

// Add filters if they are set
if (!empty($status_filter)) {
    $query .= " AND jp.status = '$status_filter'";
}
if (!empty($employer_filter)) {
    $query .= " AND jp.user_id = '$employer_filter'";
}
if (!empty($job_type_filter)) {
    $query .= " AND jp.job_type = '$job_type_filter'";
}
if (!empty($location_filter)) {
    $query .= " AND jp.location LIKE '%$location_filter%'";
}
if (!empty($date_from)) {
    $query .= " AND DATE(jp.created_at) >= '$date_from'";
}
if (!empty($date_to)) {
    $query .= " AND DATE(jp.created_at) <= '$date_to'";
}
if (!empty($search)) {
    $query .= " AND (
        jp.title LIKE '%$search%' OR 
        jp.company_name LIKE '%$search%' OR 
        jp.location LIKE '%$search%' OR 
        u.company_name LIKE '%$search%' OR
        CONCAT(u.first_name, ' ', u.last_name) LIKE '%$search%'
    )";
}

// Order by created date (newest first)
$query .= " ORDER BY jp.created_at DESC";

// Execute the query
$result = mysqli_query($conn, $query);

// Fetch employers for the filter dropdown
$employers_query = "SELECT id, first_name, last_name, company_name FROM users WHERE user_type = 'employer' ORDER BY company_name";
$employers_result = mysqli_query($conn, $employers_query);

// Fetch distinct locations for filter dropdown
$locations_query = "SELECT DISTINCT location FROM job_postings WHERE location IS NOT NULL AND location != '' ORDER BY location";
$locations_result = mysqli_query($conn, $locations_query);
?>

<div class="container-fluid mt-4">
    <h2 class="mb-4">Job Postings Management</h2>
    
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Filter Job Postings</h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="search">Search</label>
                        <input type="text" class="form-control" id="search" name="search" placeholder="Title, company, location..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="status">Job Status</label>
                        <select class="form-control" id="status" name="status">
                            <option value="">All Statuses</option>
                            <option value="Draft" <?php echo $status_filter == 'Draft' ? 'selected' : ''; ?>>Draft</option>
                            <option value="Published" <?php echo $status_filter == 'Published' ? 'selected' : ''; ?>>Published</option>
                            <option value="Closed" <?php echo $status_filter == 'Closed' ? 'selected' : ''; ?>>Closed</option>
                            <option value="Filled" <?php echo $status_filter == 'Filled' ? 'selected' : ''; ?>>Filled</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="employer">Employer</label>
                        <select class="form-control" id="employer" name="employer">
                            <option value="">All Employers</option>
                            <?php while ($employer = mysqli_fetch_assoc($employers_result)): ?>
                                <option value="<?php echo $employer['id']; ?>" <?php echo $employer_filter == $employer['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($employer['company_name'] ?: ($employer['first_name'] . ' ' . $employer['last_name'])); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="job_type">Job Type</label>
                        <select class="form-control" id="job_type" name="job_type">
                            <option value="">All Types</option>
                            <option value="Full-time" <?php echo $job_type_filter == 'Full-time' ? 'selected' : ''; ?>>Full-time</option>
                            <option value="Part-time" <?php echo $job_type_filter == 'Part-time' ? 'selected' : ''; ?>>Part-time</option>
                            <option value="Contract" <?php echo $job_type_filter == 'Contract' ? 'selected' : ''; ?>>Contract</option>
                            <option value="Internship" <?php echo $job_type_filter == 'Internship' ? 'selected' : ''; ?>>Internship</option>
                            <option value="Temporary" <?php echo $job_type_filter == 'Temporary' ? 'selected' : ''; ?>>Temporary</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="location">Location</label>
                        <select class="form-control" id="location" name="location">
                            <option value="">All Locations</option>
                            <?php while ($location = mysqli_fetch_assoc($locations_result)): ?>
                                <option value="<?php echo htmlspecialchars($location['location']); ?>" <?php echo $location_filter == $location['location'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($location['location']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="date_from">Posted From</label>
                        <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="date_to">Posted To</label>
                        <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group d-flex align-items-end h-100">
                        <button type="submit" class="btn btn-primary mr-2">Apply Filters</button>
                        <a href="job_postings.php" class="btn btn-secondary">Clear Filters</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Job Postings List -->
    <div class="card">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Job Postings</h5>
            <span class="badge badge-light"><?php echo mysqli_num_rows($result); ?> jobs found</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Job Title</th>
                            <th>Company</th>
                            <th>Employer</th>
                            <th>Location</th>
                            <th>Type</th>
                            <th>Salary</th>
                            <th>Applications</th>
                            <th>Posted Date</th>
                            <th>Expiry Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><?php echo $row['id']; ?></td>
                                    <td>
                                        <a href="job_details.php?id=<?php echo $row['id']; ?>">
                                            <?php echo htmlspecialchars($row['title']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['company_logo']) && $row['company_logo'] != 'default.jpg'): ?>
                                            <img src="../uploads/company_logos/<?php echo $row['company_logo']; ?>" alt="Company Logo" class="img-thumbnail mr-2" style="max-width: 30px; max-height: 30px;">
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($row['company_name']); ?>
                                    </td>
                                    <td>
                                        <a href="view_user.php?id=<?php echo $row['employer_id']; ?>">
                                            <?php echo htmlspecialchars($row['employer_company_name'] ?: ($row['first_name'] . ' ' . $row['last_name'])); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['location']); ?></td>
                                    <td><?php echo htmlspecialchars($row['job_type']); ?></td>
                                    <td>
                                        <?php if (!empty($row['salary_min']) || !empty($row['salary_max'])): ?>
                                            <?php
                                                $salary = '';
                                                if (!empty($row['salary_min']) && !empty($row['salary_max'])) {
                                                    $salary = number_format($row['salary_min']) . ' - ' . number_format($row['salary_max']);
                                                } elseif (!empty($row['salary_min'])) {
                                                    $salary = 'From ' . number_format($row['salary_min']);
                                                } elseif (!empty($row['salary_max'])) {
                                                    $salary = 'Up to ' . number_format($row['salary_max']);
                                                }
                                                
                                                if (!empty($row['salary_period'])) {
                                                    $salary .= ' / ' . $row['salary_period'];
                                                }
                                                
                                                echo htmlspecialchars($salary);
                                            ?>
                                        <?php else: ?>
                                            Not specified
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="job_applications.php?employer=<?php echo $row['employer_id']; ?>&job=<?php echo $row['id']; ?>">
                                            <?php echo $row['application_count']; ?>
                                        </a>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                    <td>
                                        <?php if (!empty($row['expiry_date'])): ?>
                                            <?php echo date('M d, Y', strtotime($row['expiry_date'])); ?>
                                            <?php
                                                $today = new DateTime();
                                                $expiry = new DateTime($row['expiry_date']);
                                                $diff = $today->diff($expiry);
                                                
                                                if ($today > $expiry) {
                                                    echo '<span class="badge badge-danger">Expired</span>';
                                                } elseif ($diff->days <= 7) {
                                                    echo '<span class="badge badge-warning">Expiring soon</span>';
                                                }
                                            ?>
                                        <?php else: ?>
                                            No expiry
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                            $statusClass = '';
                                            switch ($row['status']) {
                                                case 'Published':
                                                    $statusClass = 'success';
                                                    break;
                                                case 'Draft':
                                                    $statusClass = 'secondary';
                                                    break;
                                                case 'Closed':
                                                    $statusClass = 'danger';
                                                    break;
                                                case 'Filled':
                                                    $statusClass = 'info';
                                                    break;
                                            }
                                        ?>
                                        <span class="badge badge-<?php echo $statusClass; ?>">
                                            <?php echo $row['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                Actions
                                            </button>
                                            <div class="dropdown-menu">
                                                <a class="dropdown-item" href="job_details.php?id=<?php echo $row['id']; ?>">View Details</a>
                                                <a class="dropdown-item" href="job_applications.php?job=<?php echo $row['id']; ?>">View Applications (<?php echo $row['application_count']; ?>)</a>
                                                
                                                <div class="dropdown-divider"></div>
                                                
                                                <form method="POST" class="status-form">
                                                    <input type="hidden" name="job_id" value="<?php echo $row['id']; ?>">
                                                    
                                                    <?php if ($row['status'] != 'Published'): ?>
                                                        <button type="submit" name="action" value="publish" class="dropdown-item">Publish Job</button>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($row['status'] != 'Closed'): ?>
                                                        <button type="submit" name="action" value="close" class="dropdown-item">Close Job</button>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($row['status'] != 'Filled'): ?>
                                                        <button type="submit" name="action" value="mark_filled" class="dropdown-item">Mark as Filled</button>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($row['status'] != 'Draft'): ?>
                                                        <button type="submit" name="action" value="mark_draft" class="dropdown-item">Move to Draft</button>
                                                    <?php endif; ?>
                                                </form>
                                                
                                                <div class="dropdown-divider"></div>
                                                <a class="dropdown-item text-danger" href="#" onclick="confirmDelete(<?php echo $row['id']; ?>)">Delete Job</a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="12" class="text-center">No job postings found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Delete Job Modal Confirmation -->
<div class="modal fade" id="deleteJobModal" tabindex="-1" role="dialog" aria-labelledby="deleteJobModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteJobModalLabel">Confirm Delete</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this job posting? This action cannot be undone.</p>
                <p>All associated job applications will also be deleted.</p>
            </div>
            <div class="modal-footer">
                <form id="deleteJobForm" method="POST" action="delete_job.php">
                    <input type="hidden" id="deleteJobId" name="job_id" value="">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Job</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Enhance the user experience with a confirmation for status changes
document.addEventListener('DOMContentLoaded', function() {
    const statusForms = document.querySelectorAll('.status-form button');
    
    statusForms.forEach(function(button) {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to change the job status?')) {
                e.preventDefault();
            }
        });
    });
});

// Function to handle delete confirmation
function confirmDelete(jobId) {
    document.getElementById('deleteJobId').value = jobId;
    $('#deleteJobModal').modal('show');
}
</script>

<?php
// Include footer
include_once '../includes/footer.php';

// Close database connection
mysqli_close($conn);
?>