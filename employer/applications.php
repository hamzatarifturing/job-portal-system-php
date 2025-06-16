<?php
// Start the session
session_start();

/**
 * applications.php - Display and manage job applications for employer's posted jobs
 * 
 * This script displays all job applications submitted for the jobs posted by the
 * current employer. It allows filtering by job and application status, and
 * provides functionality to update application statuses.
 */

// Check if user is logged in and is an employer
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'employer') {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

// Set include path for includes folder
$includePath = "../includes/";

// Include database configuration and header
include_once($includePath . "db_config.php");
include_once($includePath . "header.php");

// Get employer ID from session
$employerId = $_SESSION['user_id'];

// Set default page title
$pageTitle = "Manage Applications";

// Process application status updates if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $applicationId = isset($_POST['application_id']) ? intval($_POST['application_id']) : 0;
    $newStatus = isset($_POST['status']) ? $_POST['status'] : '';
    
    // Validate the status value
    $validStatuses = ['pending', 'reviewed', 'shortlisted', 'rejected', 'hired'];
    if (in_array($newStatus, $validStatuses) && $applicationId > 0) {
        // Update the application status
        $updateQuery = "UPDATE job_applications SET status = ? WHERE id = ? AND 
                      job_id IN (SELECT id FROM job_postings WHERE user_id = ?)";
        $updateStmt = mysqli_prepare($conn, $updateQuery);
        
        if ($updateStmt) {
            mysqli_stmt_bind_param($updateStmt, "sii", $newStatus, $applicationId, $employerId);
            if (mysqli_stmt_execute($updateStmt)) {
                $successMsg = "Application status updated successfully.";
            } else {
                $errorMsg = "Error updating status: " . mysqli_error($conn);
            }
            mysqli_stmt_close($updateStmt);
        } else {
            $errorMsg = "Database error: " . mysqli_error($conn);
        }
    } else {
        $errorMsg = "Invalid status value or application ID.";
    }
}

// Get filter parameters
$jobFilter = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0;
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

// Build the SQL query with filters
// Modified to match the actual schema structure
$query = "SELECT a.*, j.title as job_title, j.description as job_description,
          u.first_name, u.last_name, u.email, u.profile_image, u.phone, u.resume
          FROM job_applications a
          JOIN job_postings j ON a.job_id = j.id
          JOIN users u ON a.user_id = u.id
          WHERE j.user_id = ?";

// Add filters if set
$queryParams = [$employerId];

if ($jobFilter > 0) {
    $query .= " AND j.id = ?";
    $queryParams[] = $jobFilter;
}

if (!empty($statusFilter)) {
    $query .= " AND a.status = ?";
    $queryParams[] = $statusFilter;
}

$query .= " ORDER BY a.application_date DESC";

// Prepare and execute the query
$stmt = mysqli_prepare($conn, $query);

if ($stmt) {
    // Bind parameters dynamically based on the number of parameters
    $types = str_repeat("i", count($queryParams));
    mysqli_stmt_bind_param($stmt, $types, ...$queryParams);
    
    // Execute the query
    if (mysqli_stmt_execute($stmt)) {
        // Get results
        $result = mysqli_stmt_get_result($stmt);
        $applications = mysqli_fetch_all($result, MYSQLI_ASSOC);
    } else {
        $errorMsg = "Error executing query: " . mysqli_error($conn);
    }
    mysqli_stmt_close($stmt);
} else {
    $errorMsg = "Database error: " . mysqli_error($conn);
}

// Get employer's job postings for filter dropdown
$jobsQuery = "SELECT id, title FROM job_postings WHERE user_id = ? ORDER BY created_at DESC";
$jobsStmt = mysqli_prepare($conn, $jobsQuery);

if ($jobsStmt) {
    mysqli_stmt_bind_param($jobsStmt, "i", $employerId);
    mysqli_stmt_execute($jobsStmt);
    $jobsResult = mysqli_stmt_get_result($jobsStmt);
    $jobs = mysqli_fetch_all($jobsResult, MYSQLI_ASSOC);
    mysqli_stmt_close($jobsStmt);
}
?>

<div class="container mt-5 mb-5">
    <h1 class="mb-4">Manage Job Applications</h1>
    
    <?php if(isset($errorMsg)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($errorMsg); ?></div>
    <?php endif; ?>
    
    <?php if(isset($successMsg)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($successMsg); ?></div>
    <?php endif; ?>
    
    <!-- Filter options -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="m-0">Filter Applications</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="applications.php" class="row">
                <div class="col-md-5 mb-2">
                    <label for="job_id">Filter by Job:</label>
                    <select name="job_id" id="job_id" class="form-control">
                        <option value="0">All Jobs</option>
                        <?php foreach($jobs as $job): ?>
                            <option value="<?php echo $job['id']; ?>" <?php echo ($jobFilter == $job['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($job['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5 mb-2">
                    <label for="status">Filter by Status:</label>
                    <select name="status" id="status" class="form-control">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php echo ($statusFilter === 'pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="reviewed" <?php echo ($statusFilter === 'reviewed') ? 'selected' : ''; ?>>Reviewed</option>
                        <option value="shortlisted" <?php echo ($statusFilter === 'shortlisted') ? 'selected' : ''; ?>>Shortlisted</option>
                        <option value="rejected" <?php echo ($statusFilter === 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                        <option value="hired" <?php echo ($statusFilter === 'hired') ? 'selected' : ''; ?>>Hired</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2 align-self-end">
                    <button type="submit" class="btn btn-primary btn-block">Filter</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Applications list -->
    <?php if(empty($applications)): ?>
        <div class="alert alert-info">
            <h4>No applications found</h4>
            <p>There are no job applications matching your filter criteria. You can try changing your filters or check back later.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="thead-dark">
                    <tr>
                        <th>Applicant</th>
                        <th>Job Title</th>
                        <th>Applied On</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($applications as $application): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <?php if(!empty($application['profile_image']) && $application['profile_image'] != 'default.jpg'): ?>
                                        <img src="<?php echo '../uploads/profile_images/' . htmlspecialchars($application['profile_image']); ?>" 
                                             class="rounded-circle mr-2" width="40" height="40" alt="Profile Picture">
                                    <?php else: ?>
                                        <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center mr-2" 
                                             style="width: 40px; height: 40px;">
                                            <?php echo strtoupper(substr($application['first_name'], 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <strong><?php echo htmlspecialchars($application['first_name'] . ' ' . $application['last_name']); ?></strong><br>
                                        <small><?php echo htmlspecialchars($application['email']); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($application['job_title']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($application['application_date'])); ?></td>
                            <td>
                                <span class="badge badge-pill badge-<?php 
                                    echo ($application['status'] === 'pending') ? 'warning' : 
                                         (($application['status'] === 'reviewed') ? 'info' :
                                         (($application['status'] === 'shortlisted') ? 'primary' :
                                         (($application['status'] === 'hired') ? 'success' : 'danger'))); ?>">
                                    <?php echo ucfirst(htmlspecialchars($application['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <!-- Resume download button - checks for both resume in users table and resume_path in application -->
                                    <?php if(!empty($application['resume_path']) || !empty($application['resume'])): ?>
                                        <a href="<?php echo !empty($application['resume_path']) ? 
                                            htmlspecialchars($application['resume_path']) : 
                                            '../uploads/resumes/' . htmlspecialchars($application['resume']); ?>" 
                                           class="btn btn-sm btn-success" download>
                                            <i class="fa fa-download"></i> Resume
                                        </a>
                                    <?php endif; ?>
                                    
                                    <!-- Cover letter modal trigger -->
                                    <?php if(!empty($application['cover_letter'])): ?>
                                    <button type="button" class="btn btn-sm btn-info" data-toggle="modal" 
                                            data-target="#coverLetterModal<?php echo $application['id']; ?>">
                                        <i class="fa fa-file-text"></i> Cover Letter
                                    </button>
                                    <?php endif; ?>
                                    
                                    <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" 
                                            data-target="#statusModal<?php echo $application['id']; ?>">
                                        <i class="fa fa-edit"></i> Update Status
                                    </button>
                                </div>
                                
                                <!-- Cover Letter Modal -->
                                <?php if(!empty($application['cover_letter'])): ?>
                                <div class="modal fade" id="coverLetterModal<?php echo $application['id']; ?>" tabindex="-1" role="dialog" 
                                     aria-labelledby="coverLetterModalLabel<?php echo $application['id']; ?>" aria-hidden="true">
                                    <div class="modal-dialog modal-lg" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="coverLetterModalLabel<?php echo $application['id']; ?>">
                                                    Cover Letter - <?php echo htmlspecialchars($application['first_name'] . ' ' . $application['last_name']); ?>
                                                </h5>
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                <?php echo nl2br(htmlspecialchars($application['cover_letter'])); ?>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Status Update Modal -->
                                <div class="modal fade" id="statusModal<?php echo $application['id']; ?>" tabindex="-1" role="dialog" 
                                     aria-labelledby="statusModalLabel<?php echo $application['id']; ?>" aria-hidden="true">
                                    <div class="modal-dialog" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="statusModalLabel<?php echo $application['id']; ?>">
                                                    Update Application Status
                                                </h5>
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                <form method="POST" action="applications.php">
                                                    <input type="hidden" name="application_id" value="<?php echo $application['id']; ?>">
                                                    <div class="form-group">
                                                        <label for="status<?php echo $application['id']; ?>">Select new status:</label>
                                                        <select name="status" id="status<?php echo $application['id']; ?>" class="form-control">
                                                            <option value="pending" <?php echo ($application['status'] === 'pending') ? 'selected' : ''; ?>>Pending</option>
                                                            <option value="reviewed" <?php echo ($application['status'] === 'reviewed') ? 'selected' : ''; ?>>Reviewed</option>
                                                            <option value="shortlisted" <?php echo ($application['status'] === 'shortlisted') ? 'selected' : ''; ?>>Shortlisted</option>
                                                            <option value="rejected" <?php echo ($application['status'] === 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                                                            <option value="hired" <?php echo ($application['status'] === 'hired') ? 'selected' : ''; ?>>Hired</option>
                                                        </select>
                                                    </div>
                                                    <div class="text-right">
                                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                        <button type="submit" name="update_status" class="btn btn-primary">Save Changes</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- End Modal -->
                                
                                <!-- Applicant Contact Modal -->
                                <div class="modal fade" id="contactModal<?php echo $application['id']; ?>" tabindex="-1" role="dialog" 
                                     aria-labelledby="contactModalLabel<?php echo $application['id']; ?>" aria-hidden="true">
                                    <div class="modal-dialog" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="contactModalLabel<?php echo $application['id']; ?>">
                                                    Contact <?php echo htmlspecialchars($application['first_name'] . ' ' . $application['last_name']); ?>
                                                </h5>
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row mb-3">
                                                    <div class="col-md-12">
                                                        <p><strong>Email:</strong> <?php echo htmlspecialchars($application['email']); ?></p>
                                                        <?php if(!empty($application['phone'])): ?>
                                                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($application['phone']); ?></p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <a href="mailto:<?php echo htmlspecialchars($application['email']); ?>" 
                                                           class="btn btn-primary btn-block">
                                                            <i class="fa fa-envelope"></i> Send Email
                                                        </a>
                                                    </div>
                                                    <?php if(!empty($application['phone'])): ?>
                                                    <div class="col-md-6">
                                                        <a href="tel:<?php echo htmlspecialchars($application['phone']); ?>" 
                                                           class="btn btn-info btn-block">
                                                            <i class="fa fa-phone"></i> Call
                                                        </a>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- End Contact Modal -->
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    
    <!-- Application stats summary -->
    <div class="card mt-4">
        <div class="card-header bg-secondary text-white">
            <h5 class="m-0">Application Statistics</h5>
        </div>
        <div class="card-body">
            <?php
            // Calculate statistics
            $totalApplications = count($applications);
            $pendingCount = 0;
            $reviewedCount = 0;
            $shortlistedCount = 0;
            $rejectedCount = 0;
            $hiredCount = 0;
            
            foreach ($applications as $app) {
                switch($app['status']) {
                    case 'pending':
                        $pendingCount++;
                        break;
                    case 'reviewed':
                        $reviewedCount++;
                        break;
                    case 'shortlisted':
                        $shortlistedCount++;
                        break;
                    case 'rejected':
                        $rejectedCount++;
                        break;
                    case 'hired':
                        $hiredCount++;
                        break;
                }
            }
            ?>
            <div class="row">
                <div class="col-md-2">
                    <div class="card bg-light mb-3">
                        <div class="card-body text-center">
                            <h5>Total</h5>
                            <h3><?php echo $totalApplications; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-warning text-white mb-3">
                        <div class="card-body text-center">
                            <h5>Pending</h5>
                            <h3><?php echo $pendingCount; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-info text-white mb-3">
                        <div class="card-body text-center">
                            <h5>Reviewed</h5>
                            <h3><?php echo $reviewedCount; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-primary text-white mb-3">
                        <div class="card-body text-center">
                            <h5>Shortlisted</h5>
                            <h3><?php echo $shortlistedCount; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-danger text-white mb-3">
                        <div class="card-body text-center">
                            <h5>Rejected</h5>
                            <h3><?php echo $rejectedCount; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-success text-white mb-3">
                        <div class="card-body text-center">
                            <h5>Hired</h5>
                            <h3><?php echo $hiredCount; ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once($includePath . "footer.php");
?>