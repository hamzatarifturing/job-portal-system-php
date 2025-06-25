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
$page_title = "Job Applications Management";

// Include header
include_once '../includes/header.php';

// Set default filters
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';
$employer_filter = isset($_GET['employer']) ? mysqli_real_escape_string($conn, $_GET['employer']) : '';
$jobseeker_filter = isset($_GET['jobseeker']) ? mysqli_real_escape_string($conn, $_GET['jobseeker']) : '';
$date_from = isset($_GET['date_from']) ? mysqli_real_escape_string($conn, $_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? mysqli_real_escape_string($conn, $_GET['date_to']) : '';

// Process application status update if requested
if (isset($_POST['action']) && isset($_POST['application_id'])) {
    $applicationId = mysqli_real_escape_string($conn, $_POST['application_id']);
    $action = mysqli_real_escape_string($conn, $_POST['action']);
    $new_status = '';

    switch ($action) {
        case 'mark_reviewed':
            $new_status = 'reviewed';
            break;
        case 'mark_shortlisted':
            $new_status = 'shortlisted';
            break;
        case 'mark_rejected':
            $new_status = 'rejected';
            break;
        case 'mark_hired':
            $new_status = 'hired';
            break;
        case 'mark_pending':
            $new_status = 'pending';
            break;
    }

    if (!empty($new_status)) {
        $updateQuery = "UPDATE job_applications SET status = '$new_status' WHERE id = '$applicationId'";
        if (mysqli_query($conn, $updateQuery)) {
            $success_message = "Application status updated successfully.";
        } else {
            $error_message = "Error updating application status: " . mysqli_error($conn);
        }
    }
}

// Build the query with all necessary joins
$query = "SELECT 
    ja.id, 
    ja.application_date, 
    ja.status as app_status, 
    ja.cover_letter,
    ja.resume_path,
    jp.id as job_id, 
    jp.title as job_title, 
    jp.company_name, 
    jp.job_type, 
    jp.location,
    jp.status as job_status,
    u_applicant.id as applicant_id,
    u_applicant.first_name as applicant_first_name, 
    u_applicant.last_name as applicant_last_name, 
    u_applicant.email as applicant_email,
    u_employer.id as employer_id,
    u_employer.first_name as employer_first_name, 
    u_employer.last_name as employer_last_name, 
    u_employer.company_name as employer_company_name
FROM 
    job_applications ja
JOIN 
    job_postings jp ON ja.job_id = jp.id
JOIN 
    users u_applicant ON ja.user_id = u_applicant.id
JOIN 
    users u_employer ON jp.user_id = u_employer.id
WHERE 1=1";

// Add filters if they are set
if (!empty($status_filter)) {
    $query .= " AND ja.status = '$status_filter'";
}
if (!empty($employer_filter)) {
    $query .= " AND jp.user_id = '$employer_filter'";
}
if (!empty($jobseeker_filter)) {
    $query .= " AND ja.user_id = '$jobseeker_filter'";
}
if (!empty($date_from)) {
    $query .= " AND DATE(ja.application_date) >= '$date_from'";
}
if (!empty($date_to)) {
    $query .= " AND DATE(ja.application_date) <= '$date_to'";
}

// Order by application date (newest first)
$query .= " ORDER BY ja.application_date DESC";

// Execute the query
$result = mysqli_query($conn, $query);

// Fetch employers for the filter dropdown
$employers_query = "SELECT id, first_name, last_name, company_name FROM users WHERE user_type = 'employer' ORDER BY company_name";
$employers_result = mysqli_query($conn, $employers_query);

// Fetch job seekers for the filter dropdown
$jobseekers_query = "SELECT id, first_name, last_name FROM users WHERE user_type = 'jobseeker' ORDER BY first_name, last_name";
$jobseekers_result = mysqli_query($conn, $jobseekers_query);
?>

<div class="container-fluid mt-4">
    <h2 class="mb-4">Job Applications Management</h2>
    
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Filter Applications</h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row">
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="status">Application Status</label>
                        <select class="form-control" id="status" name="status">
                            <option value="">All Statuses</option>
                            <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="reviewed" <?php echo $status_filter == 'reviewed' ? 'selected' : ''; ?>>Reviewed</option>
                            <option value="shortlisted" <?php echo $status_filter == 'shortlisted' ? 'selected' : ''; ?>>Shortlisted</option>
                            <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                            <option value="hired" <?php echo $status_filter == 'hired' ? 'selected' : ''; ?>>Hired</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
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
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="jobseeker">Job Seeker</label>
                        <select class="form-control" id="jobseeker" name="jobseeker">
                            <option value="">All Job Seekers</option>
                            <?php while ($jobseeker = mysqli_fetch_assoc($jobseekers_result)): ?>
                                <option value="<?php echo $jobseeker['id']; ?>" <?php echo $jobseeker_filter == $jobseeker['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($jobseeker['first_name'] . ' ' . $jobseeker['last_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="date_from">Date From</label>
                        <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="date_to">Date To</label>
                        <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
                    </div>
                </div>
                <div class="col-md-12">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="job_applications.php" class="btn btn-secondary">Clear Filters</a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Applications List -->
    <div class="card">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Job Applications</h5>
            <span class="badge badge-light"><?php echo mysqli_num_rows($result); ?> applications found</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Job Seeker</th>
                            <th>Job Title</th>
                            <th>Employer</th>
                            <th>Application Date</th>
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
                                        <a href="view_user.php?id=<?php echo $row['applicant_id']; ?>">
                                            <?php echo htmlspecialchars($row['applicant_first_name'] . ' ' . $row['applicant_last_name']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <a href="job_details.php?id=<?php echo $row['job_id']; ?>">
                                            <?php echo htmlspecialchars($row['job_title']); ?>
                                        </a>
                                        <div class="small text-muted">
                                            <?php echo htmlspecialchars($row['company_name']); ?> • 
                                            <?php echo htmlspecialchars($row['job_type']); ?> • 
                                            <?php echo htmlspecialchars($row['location']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="view_user.php?id=<?php echo $row['employer_id']; ?>">
                                            <?php echo htmlspecialchars($row['employer_company_name'] ?: ($row['employer_first_name'] . ' ' . $row['employer_last_name'])); ?>
                                        </a>
                                    </td>
                                    <td><?php echo date('M d, Y H:i', strtotime($row['application_date'])); ?></td>
                                    <td>
                                        <?php
                                        $status_class = '';
                                        switch ($row['app_status']) {
                                            case 'pending':
                                                $status_class = 'warning';
                                                break;
                                            case 'reviewed':
                                                $status_class = 'info';
                                                break;
                                            case 'shortlisted':
                                                $status_class = 'primary';
                                                break;
                                            case 'rejected':
                                                $status_class = 'danger';
                                                break;
                                            case 'hired':
                                                $status_class = 'success';
                                                break;
                                        }
                                        ?>
                                        <span class="badge badge-<?php echo $status_class; ?>">
                                            <?php echo ucfirst($row['app_status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                Actions
                                            </button>
                                            <div class="dropdown-menu">
                                                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#applicationModal<?php echo $row['id']; ?>">View Details</a>
                                                <?php if (!empty($row['resume_path'])): ?>
                                                    <a class="dropdown-item" href="../uploads/resumes/<?php echo $row['resume_path']; ?>" target="_blank">View Resume</a>
                                                <?php endif; ?>
                                                
                                                <div class="dropdown-divider"></div>
                                                
                                                <form method="POST" class="status-form">
                                                    <input type="hidden" name="application_id" value="<?php echo $row['id']; ?>">
                                                    
                                                    <?php if ($row['app_status'] != 'pending'): ?>
                                                        <button type="submit" name="action" value="mark_pending" class="dropdown-item">Mark as Pending</button>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($row['app_status'] != 'reviewed'): ?>
                                                        <button type="submit" name="action" value="mark_reviewed" class="dropdown-item">Mark as Reviewed</button>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($row['app_status'] != 'shortlisted'): ?>
                                                        <button type="submit" name="action" value="mark_shortlisted" class="dropdown-item">Mark as Shortlisted</button>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($row['app_status'] != 'rejected'): ?>
                                                        <button type="submit" name="action" value="mark_rejected" class="dropdown-item">Mark as Rejected</button>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($row['app_status'] != 'hired'): ?>
                                                        <button type="submit" name="action" value="mark_hired" class="dropdown-item">Mark as Hired</button>
                                                    <?php endif; ?>
                                                </form>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">No applications found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Application Detail Modals -->
<?php 
// Reset the result pointer
mysqli_data_seek($result, 0);
while ($row = mysqli_fetch_assoc($result)): 
?>
<div class="modal fade" id="applicationModal<?php echo $row['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="applicationModalLabel<?php echo $row['id']; ?>" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="applicationModalLabel<?php echo $row['id']; ?>">Application Details</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Job Details</h6>
                        <p><strong>Title:</strong> <?php echo htmlspecialchars($row['job_title']); ?></p>
                        <p><strong>Company:</strong> <?php echo htmlspecialchars($row['company_name']); ?></p>
                        <p><strong>Type:</strong> <?php echo htmlspecialchars($row['job_type']); ?></p>
                        <p><strong>Location:</strong> <?php echo htmlspecialchars($row['location']); ?></p>
                        <p><strong>Job Status:</strong> 
                            <span class="badge badge-<?php echo $row['job_status'] == 'Published' ? 'success' : 'secondary'; ?>">
                                <?php echo $row['job_status']; ?>
                            </span>
                        </p>
                        <p><a href="job_details.php?id=<?php echo $row['job_id']; ?>" class="btn btn-sm btn-info">View Full Job Details</a></p>
                    </div>
                    <div class="col-md-6">
                        <h6>Applicant Details</h6>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($row['applicant_first_name'] . ' ' . $row['applicant_last_name']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($row['applicant_email']); ?></p>
                        <p><strong>Applied On:</strong> <?php echo date('M d, Y H:i', strtotime($row['application_date'])); ?></p>
                        <p><strong>Status:</strong> 
                            <span class="badge badge-<?php echo $status_class; ?>">
                                <?php echo ucfirst($row['app_status']); ?>
                            </span>
                        </p>
                        <p><a href="view_user.php?id=<?php echo $row['applicant_id']; ?>" class="btn btn-sm btn-info">View Applicant Profile</a></p>
                    </div>
                </div>
                
                <hr>
                
                <div class="row">
                    <div class="col-md-12">
                        <h6>Cover Letter</h6>
                        <div class="border p-3 bg-light">
                            <?php if (!empty($row['cover_letter'])): ?>
                                <?php echo nl2br(htmlspecialchars($row['cover_letter'])); ?>
                            <?php else: ?>
                                <p class="text-muted">No cover letter provided</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($row['resume_path'])): ?>
                    <div class="mt-3">
                        <a href="../uploads/resumes/<?php echo $row['resume_path']; ?>" target="_blank" class="btn btn-secondary">View Resume</a>
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="application_id" value="<?php echo $row['id']; ?>">
                    <div class="btn-group">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <?php if ($row['app_status'] != 'reviewed'): ?>
                            <button type="submit" name="action" value="mark_reviewed" class="btn btn-info">Mark as Reviewed</button>
                        <?php endif; ?>
                        
                        <?php if ($row['app_status'] != 'shortlisted'): ?>
                            <button type="submit" name="action" value="mark_shortlisted" class="btn btn-primary">Shortlist</button>
                        <?php endif; ?>
                        
                        <?php if ($row['app_status'] != 'rejected'): ?>
                            <button type="submit" name="action" value="mark_rejected" class="btn btn-danger">Reject</button>
                        <?php endif; ?>
                        
                        <?php if ($row['app_status'] != 'hired'): ?>
                            <button type="submit" name="action" value="mark_hired" class="btn btn-success">Mark as Hired</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endwhile; ?>

<script>
// Enhance the user experience with a confirmation for status changes
document.addEventListener('DOMContentLoaded', function() {
    const statusForms = document.querySelectorAll('.status-form button');
    
    statusForms.forEach(function(button) {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to change the application status?')) {
                e.preventDefault();
            }
        });
    });
});
</script>

<?php
// Include footer
include_once '../includes/footer.php';

// Close database connection
mysqli_close($conn);
?>