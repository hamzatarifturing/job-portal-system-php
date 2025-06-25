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

// Check if job ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: job_postings.php");
    exit();
}

// Sanitize the job ID
$jobId = mysqli_real_escape_string($conn, $_GET['id']);

// Fetch job details with employer information
$query = "SELECT 
    jp.*,
    u.id as employer_id,
    u.first_name,
    u.last_name,
    u.email as employer_email,
    u.phone as employer_phone,
    u.company_name as employer_company_name,
    u.company_logo,
    u.website as employer_website,
    u.company_description as employer_company_desc,
    (SELECT COUNT(*) FROM job_applications ja WHERE ja.job_id = jp.id) as application_count
FROM 
    job_postings jp
JOIN 
    users u ON jp.user_id = u.id
WHERE 
    jp.id = '$jobId'";

$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) === 0) {
    // Job not found - redirect back to job postings page
    header("Location: job_postings.php");
    exit();
}

$job = mysqli_fetch_assoc($result);

// Process job status update if requested
if (isset($_POST['action'])) {
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
            $success_message = "Job status updated to " . $new_status . " successfully.";
            // Refresh job data
            $result = mysqli_query($conn, $query);
            $job = mysqli_fetch_assoc($result);
        } else {
            $error_message = "Error updating job status: " . mysqli_error($conn);
        }
    }
}

// Fetch job applications
$applications_query = "SELECT 
    ja.*,
    u.first_name,
    u.last_name,
    u.email,
    u.profile_image
FROM 
    job_applications ja
JOIN 
    users u ON ja.user_id = u.id
WHERE 
    ja.job_id = '$jobId'
ORDER BY 
    ja.application_date DESC";

$applications_result = mysqli_query($conn, $applications_query);

// Set page title
$page_title = "Job Details: " . $job['title'];

// Include header
include_once '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row mb-3">
        <div class="col-md-8">
            <a href="job_postings.php" class="btn btn-secondary"><i class="fa fa-arrow-left"></i> Back to Job Postings</a>
        </div>
        <div class="col-md-4 text-right">
            <?php if ($job['application_count'] > 0): ?>
                <a href="job_applications.php?job=<?php echo $jobId; ?>" class="btn btn-info">
                    View Applications (<?php echo $job['application_count']; ?>)
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (isset($success_message)): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <div class="row">
        <!-- Job Details -->
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Job Details</h5>
                    <div>
                        <?php
                            $statusClass = '';
                            switch ($job['status']) {
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
                        <span class="badge badge-<?php echo $statusClass; ?> p-2">
                            <?php echo $job['status']; ?>
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-4">
                        <?php if (!empty($job['company_logo']) && $job['company_logo'] != 'default.jpg'): ?>
                            <img src="../uploads/company_logos/<?php echo $job['company_logo']; ?>" alt="Company Logo" class="img-thumbnail mr-3" style="max-width: 80px; max-height: 80px;">
                        <?php elseif (!empty($job['company_name'])): ?>
                            <div class="company-logo-placeholder mr-3 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px; background-color: #f8f9fa; border-radius: 5px;">
                                <span class="h4 mb-0"><?php echo substr($job['company_name'], 0, 1); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <div>
                            <h3 class="mb-1"><?php echo htmlspecialchars($job['title']); ?></h3>
                            <p class="text-muted mb-0">at <?php echo htmlspecialchars($job['company_name']); ?></p>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-4 mb-3">
                            <div class="d-flex align-items-center">
                                <i class="fa fa-map-marker-alt text-primary mr-2" style="width: 20px;"></i>
                                <div>
                                    <small class="text-muted">Location</small>
                                    <div><?php echo htmlspecialchars($job['location'] ?: 'Not specified'); ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="d-flex align-items-center">
                                <i class="fa fa-briefcase text-primary mr-2" style="width: 20px;"></i>
                                <div>
                                    <small class="text-muted">Job Type</small>
                                    <div><?php echo htmlspecialchars($job['job_type']); ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="d-flex align-items-center">
                                <i class="fa fa-calendar-alt text-primary mr-2" style="width: 20px;"></i>
                                <div>
                                    <small class="text-muted">Posted On</small>
                                    <div><?php echo date('F d, Y', strtotime($job['created_at'])); ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="d-flex align-items-center">
                                <i class="fa fa-money-bill-wave text-primary mr-2" style="width: 20px;"></i>
                                <div>
                                    <small class="text-muted">Salary</small>
                                    <div>
                                        <?php if (!empty($job['salary_min']) || !empty($job['salary_max'])): ?>
                                            <?php
                                                $salary = '';
                                                if (!empty($job['salary_min']) && !empty($job['salary_max'])) {
                                                    $salary = number_format($job['salary_min']) . ' - ' . number_format($job['salary_max']);
                                                } elseif (!empty($job['salary_min'])) {
                                                    $salary = 'From ' . number_format($job['salary_min']);
                                                } elseif (!empty($job['salary_max'])) {
                                                    $salary = 'Up to ' . number_format($job['salary_max']);
                                                }
                                                
                                                if (!empty($job['salary_period'])) {
                                                    $salary .= ' / ' . $job['salary_period'];
                                                }
                                                
                                                echo htmlspecialchars($salary);
                                            ?>
                                        <?php else: ?>
                                            Not specified
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="d-flex align-items-center">
                                <i class="fa fa-hourglass-end text-primary mr-2" style="width: 20px;"></i>
                                <div>
                                    <small class="text-muted">Expiry Date</small>
                                    <div>
                                        <?php if (!empty($job['expiry_date'])): ?>
                                            <?php 
                                                echo date('F d, Y', strtotime($job['expiry_date'])); 
                                            
                                                $today = new DateTime();
                                                $expiry = new DateTime($job['expiry_date']);
                                                
                                                if ($today > $expiry) {
                                                    echo ' <span class="badge badge-danger">Expired</span>';
                                                } elseif ($today->diff($expiry)->days <= 7) {
                                                    echo ' <span class="badge badge-warning">Expiring soon</span>';
                                                }
                                            ?>
                                        <?php else: ?>
                                            No expiry set
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="d-flex align-items-center">
                                <i class="fa fa-users text-primary mr-2" style="width: 20px;"></i>
                                <div>
                                    <small class="text-muted">Applications</small>
                                    <div><?php echo $job['application_count']; ?> applicants</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="job-action-buttons mb-4">
                        <form method="POST" class="d-inline-block mr-2 mb-2">
                            <?php if ($job['status'] != 'Published'): ?>
                                <button type="submit" name="action" value="publish" class="btn btn-success">
                                    <i class="fa fa-globe"></i> Publish Job
                                </button>
                            <?php endif; ?>
                            
                            <?php if ($job['status'] != 'Closed'): ?>
                                <button type="submit" name="action" value="close" class="btn btn-danger">
                                    <i class="fa fa-times-circle"></i> Close Job
                                </button>
                            <?php endif; ?>
                            
                            <?php if ($job['status'] != 'Filled'): ?>
                                <button type="submit" name="action" value="mark_filled" class="btn btn-info">
                                    <i class="fa fa-check-circle"></i> Mark as Filled
                                </button>
                            <?php endif; ?>
                            
                            <?php if ($job['status'] != 'Draft'): ?>
                                <button type="submit" name="action" value="mark_draft" class="btn btn-secondary">
                                    <i class="fa fa-edit"></i> Move to Draft
                                </button>
                            <?php endif; ?>
                        </form>
                        
                        <button type="button" class="btn btn-danger mb-2" data-toggle="modal" data-target="#deleteJobModal">
                            <i class="fa fa-trash"></i> Delete Job
                        </button>
                    </div>
                    
                    <div class="job-description mb-4">
                        <h5>Job Description</h5>
                        <div class="border rounded p-3 bg-light">
                            <?php echo nl2br(htmlspecialchars($job['description'])); ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($job['requirements'])): ?>
                    <div class="job-requirements mb-4">
                        <h5>Requirements</h5>
                        <div class="border rounded p-3 bg-light">
                            <?php echo nl2br(htmlspecialchars($job['requirements'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Recent Applications Section -->
            <?php if (mysqli_num_rows($applications_result) > 0): ?>
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Applications</h5>
                    <a href="job_applications.php?job=<?php echo $jobId; ?>" class="btn btn-sm btn-light">View All</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Applicant</th>
                                    <th>Date Applied</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $count = 0;
                                while ($application = mysqli_fetch_assoc($applications_result)): 
                                    if ($count >= 5) break; // Show only 5 recent applications
                                    $count++;
                                ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if (!empty($application['profile_image']) && $application['profile_image'] != 'default.jpg'): ?>
                                                    <img src="../uploads/profile/<?php echo $application['profile_image']; ?>" alt="Profile" class="rounded-circle mr-2" style="width: 30px; height: 30px;">
                                                <?php else: ?>
                                                    <div class="rounded-circle mr-2 d-flex align-items-center justify-content-center text-white bg-secondary" style="width: 30px; height: 30px;">
                                                        <?php echo substr($application['first_name'], 0, 1); ?>
                                                    </div>
                                                <?php endif; ?>
                                                <a href="view_user.php?id=<?php echo $application['user_id']; ?>">
                                                    <?php echo htmlspecialchars($application['first_name'] . ' ' . $application['last_name']); ?>
                                                </a>
                                            </div>
                                        </td>
                                        <td><?php echo date('M d, Y H:i', strtotime($application['application_date'])); ?></td>
                                        <td>
                                            <?php
                                                $status_class = '';
                                                switch ($application['status']) {
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
                                                <?php echo ucfirst($application['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="#" class="btn btn-sm btn-info" data-toggle="modal" data-target="#applicationModal<?php echo $application['id']; ?>">
                                                View Details
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Employer Information Sidebar -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Employer Information</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <?php if (!empty($job['company_logo']) && $job['company_logo'] != 'default.jpg'): ?>
                            <img src="../uploads/company_logos/<?php echo $job['company_logo']; ?>" alt="Company Logo" class="img-fluid mb-3" style="max-height: 100px;">
                        <?php endif; ?>
                        
                        <h5><?php echo htmlspecialchars($job['employer_company_name'] ?: ($job['first_name'] . ' ' . $job['last_name'])); ?></h5>
                        <a href="view_user.php?id=<?php echo $job['employer_id']; ?>" class="btn btn-sm btn-outline-primary mt-2">
                            View Employer Profile
                        </a>
                    </div>
                    
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <i class="fa fa-user text-primary mr-2"></i>
                            <?php echo htmlspecialchars($job['first_name'] . ' ' . $job['last_name']); ?>
                        </li>
                        <li class="list-group-item">
                            <i class="fa fa-envelope text-primary mr-2"></i>
                            <a href="mailto:<?php echo $job['employer_email']; ?>"><?php echo htmlspecialchars($job['employer_email']); ?></a>
                        </li>
                        <?php if (!empty($job['employer_phone'])): ?>
                        <li class="list-group-item">
                            <i class="fa fa-phone text-primary mr-2"></i>
                            <?php echo htmlspecialchars($job['employer_phone']); ?>
                        </li>
                        <?php endif; ?>
                        <?php if (!empty($job['employer_website'])): ?>
                        <li class="list-group-item">
                            <i class="fa fa-globe text-primary mr-2"></i>
                            <a href="<?php echo htmlspecialchars($job['employer_website']); ?>" target="_blank"><?php echo htmlspecialchars($job['employer_website']); ?></a>
                        </li>
                        <?php endif; ?>
                    </ul>
                    
                    <?php if (!empty($job['employer_company_desc'])): ?>
                    <div class="mt-3">
                        <h6>About the Company</h6>
                        <div class="border rounded p-3 bg-light">
                            <?php echo nl2br(htmlspecialchars($job['employer_company_desc'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Other Jobs by this Employer</h5>
                </div>
                <div class="card-body">
                    <?php
                    // Fetch other jobs by this employer
                    $other_jobs_query = "SELECT id, title, job_type, location, status, created_at FROM job_postings 
                                        WHERE user_id = '{$job['user_id']}' AND id != '$jobId'
                                        ORDER BY created_at DESC LIMIT 5";
                    $other_jobs_result = mysqli_query($conn, $other_jobs_query);
                    
                    if (mysqli_num_rows($other_jobs_result) > 0): 
                    ?>
                        <div class="list-group">
                            <?php while ($other_job = mysqli_fetch_assoc($other_jobs_result)): ?>
                                <a href="job_details.php?id=<?php echo $other_job['id']; ?>" class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($other_job['title']); ?></h6>
                                        <?php
                                            $status_class = '';
                                            switch ($other_job['status']) {
                                                case 'Published':
                                                    $status_class = 'success';
                                                    break;
                                                case 'Draft':
                                                    $status_class = 'secondary';
                                                    break;
                                                case 'Closed':
                                                    $status_class = 'danger';
                                                    break;
                                                case 'Filled':
                                                    $status_class = 'info';
                                                    break;
                                            }
                                        ?>
                                        <small class="badge badge-<?php echo $status_class; ?>"><?php echo $other_job['status']; ?></small>
                                    </div>
                                    <p class="mb-1">
                                        <small>
                                            <i class="fa fa-map-marker-alt mr-1"></i> <?php echo htmlspecialchars($other_job['location'] ?: 'Not specified'); ?>
                                            <i class="fa fa-briefcase ml-2 mr-1"></i> <?php echo htmlspecialchars($other_job['job_type']); ?>
                                        </small>
                                    </p>
                                    <small class="text-muted">Posted <?php echo date('M d, Y', strtotime($other_job['created_at'])); ?></small>
                                </a>
                            <?php endwhile; ?>
                        </div>
                        <div class="mt-3">
                            <a href="job_postings.php?employer=<?php echo $job['employer_id']; ?>" class="btn btn-sm btn-outline-primary btn-block">
                                View All Jobs by this Employer
                            </a>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">No other job postings found for this employer.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Application Modals -->
<?php
// Reset the result pointer
if (mysqli_num_rows($applications_result) > 0) {
    mysqli_data_seek($applications_result, 0);
    while ($application = mysqli_fetch_assoc($applications_result)): 
?>
<div class="modal fade" id="applicationModal<?php echo $application['id']; ?>" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Application Details</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Applicant Information</h6>
                        <p>
                            <strong>Name:</strong> 
                            <a href="view_user.php?id=<?php echo $application['user_id']; ?>">
                                <?php echo htmlspecialchars($application['first_name'] . ' ' . $application['last_name']); ?>
                            </a>
                        </p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($application['email']); ?></p>
                        <p><strong>Applied On:</strong> <?php echo date('F d, Y H:i', strtotime($application['application_date'])); ?></p>
                        <p>
                            <strong>Status:</strong> 
                            <span class="badge badge-<?php echo $status_class; ?>">
                                <?php echo ucfirst($application['status']); ?>
                            </span>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h6>Application Status</h6>
                        <form method="POST" action="job_applications.php">
                            <input type="hidden" name="application_id" value="<?php echo $application['id']; ?>">
                            <input type="hidden" name="redirect_url" value="job_details.php?id=<?php echo $jobId; ?>">
                            
                            <div class="btn-group btn-group-sm mb-3" role="group">
                                <?php if ($application['status'] != 'pending'): ?>
                                    <button type="submit" name="action" value="mark_pending" class="btn btn-outline-warning">Pending</button>
                                <?php endif; ?>
                                
                                <?php if ($application['status'] != 'reviewed'): ?>
                                    <button type="submit" name="action" value="mark_reviewed" class="btn btn-outline-info">Reviewed</button>
                                <?php endif; ?>
                                
                                <?php if ($application['status'] != 'shortlisted'): ?>
                                    <button type="submit" name="action" value="mark_shortlisted" class="btn btn-outline-primary">Shortlist</button>
                                <?php endif; ?>
                                
                                <?php if ($application['status'] != 'rejected'): ?>
                                    <button type="submit" name="action" value="mark_rejected" class="btn btn-outline-danger">Reject</button>
                                <?php endif; ?>
                                
                                <?php if ($application['status'] != 'hired'): ?>
                                    <button type="submit" name="action" value="mark_hired" class="btn btn-outline-success">Hire</button>
                                <?php endif; ?>
                            </div>
                        </form>
                        
                        <?php if (!empty($application['resume_path'])): ?>
                            <a href="../uploads/resumes/<?php echo $application['resume_path']; ?>" target="_blank" class="btn btn-sm btn-secondary">
                                <i class="fa fa-file-pdf"></i> View Resume
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <hr>
                
                <div class="cover-letter mt-3">
                    <h6>Cover Letter</h6>
                    <div class="border rounded p-3 bg-light">
                        <?php if (!empty($application['cover_letter'])): ?>
                            <?php echo nl2br(htmlspecialchars($application['cover_letter'])); ?>
                        <?php else: ?>
                            <p class="text-muted">No cover letter provided</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <a href="job_applications.php?id=<?php echo $application['id']; ?>" class="btn btn-primary">View Full Application</a>
            </div>
        </div>
    </div>
</div>
<?php 
    endwhile; 
}
?>

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
                <p>Are you sure you want to delete this job posting?</p>
                <p><strong>Title:</strong> <?php echo htmlspecialchars($job['title']); ?></p>
                <p><strong>Company:</strong> <?php echo htmlspecialchars($job['company_name']); ?></p>
                <p class="text-danger"><strong>Warning:</strong> This action cannot be undone. All associated job applications will also be deleted.</p>
            </div>
            <div class="modal-footer">
                <form action="delete_job.php" method="POST">
                    <input type="hidden" name="job_id" value="<?php echo $jobId; ?>">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Job</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once '../includes/footer.php';

// Close database connection
mysqli_close($conn);
?>
