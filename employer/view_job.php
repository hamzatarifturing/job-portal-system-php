<?php
// Start the session
session_start();

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

// Set include path for header/footer
$includePath = "../includes/";
$pageTitle = "View Job | Job Portal";

// Include database config
include_once($includePath . "db_config.php");

// Check if job ID is provided
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // Redirect to job listings if no valid ID
    if($_SESSION['user_type'] == 'employer') {
        header("Location: job_listings.php");
    } else {
        header("Location: ../index.php");
    }
    exit();
}

$jobId = intval($_GET['id']);
$userId = $_SESSION['user_id'];
$isEmployer = ($_SESSION['user_type'] == 'employer');
$isOwner = false;
$job = null;
$error = '';

// Fetch job details
$query = "SELECT j.*, u.email as employer_email, u.first_name, u.last_name 
          FROM job_postings j 
          JOIN users u ON j.user_id = u.id 
          WHERE j.id = ?";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $jobId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($result) > 0) {
    $job = mysqli_fetch_assoc($result);
    
    // Check if the current user is the job owner
    if($isEmployer && $job['user_id'] == $userId) {
        $isOwner = true;
    }
    
    // If user is an employer but not the owner, and job is draft, deny access
    if($isEmployer && !$isOwner && $job['status'] == 'Draft') {
        header("Location: dashboard.php?error=unauthorized");
        exit();
    }
} else {
    $error = "Job not found.";
}

// Include header
include_once($includePath . "header.php");
?>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <?php if(!empty($error)): ?>
                <div class="alert alert-danger mt-4"><?php echo $error; ?></div>
                <div class="text-center">
                    <a href="<?php echo $isEmployer ? 'dashboard.php' : '../index.php'; ?>" class="btn btn-primary">Back to Dashboard</a>
                </div>
            <?php elseif($job): ?>
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <h1><?php echo htmlspecialchars($job['title']); ?></h1>
                    
                    <?php if($isOwner): ?>
                        <div>
                            <a href="edit_job.php?id=<?php echo $jobId; ?>" class="btn btn-outline-primary">
                                <i class="fa fa-edit"></i> Edit Job
                            </a>
                            <?php if($job['status'] == 'Published'): ?>
                                <a href="close_job.php?id=<?php echo $jobId; ?>" class="btn btn-outline-danger ml-2" onclick="return confirm('Are you sure you want to close this job posting?');">
                                    <i class="fa fa-times-circle"></i> Close Job
                                </a>
                            <?php elseif($job['status'] == 'Draft'): ?>
                                <a href="publish_job.php?id=<?php echo $jobId; ?>" class="btn btn-success ml-2">
                                    <i class="fa fa-check-circle"></i> Publish Job
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?php echo $isEmployer ? 'dashboard.php' : '../index.php'; ?>">
                            <?php echo $isEmployer ? 'Dashboard' : 'Home'; ?>
                        </a></li>
                        <?php if($isEmployer): ?>
                            <li class="breadcrumb-item"><a href="job_listings.php">My Job Listings</a></li>
                        <?php else: ?>
                            <li class="breadcrumb-item"><a href="../jobs.php">Jobs</a></li>
                        <?php endif; ?>
                        <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($job['title']); ?></li>
                    </ol>
                </nav>
                
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h4 class="m-0"><?php echo htmlspecialchars($job['title']); ?></h4>
                        <span class="badge badge-light">
                            <?php 
                            switch($job['status']) {
                                case 'Draft':
                                    echo '<span class="text-secondary">Draft</span>';
                                    break;
                                case 'Published':
                                    echo '<span class="text-success">Active</span>';
                                    break;
                                case 'Closed':
                                    echo '<span class="text-danger">Closed</span>';
                                    break;
                                case 'Filled':
                                    echo '<span class="text-info">Filled</span>';
                                    break;
                                default:
                                    echo htmlspecialchars($job['status']);
                            }
                            ?>
                        </span>
                    </div>
                    
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <h5>Company</h5>
                                <p class="lead"><?php echo htmlspecialchars($job['company_name']); ?></p>
                                
                                <?php if(!empty($job['location'])): ?>
                                    <h5>Location</h5>
                                    <p><?php echo htmlspecialchars($job['location']); ?></p>
                                <?php endif; ?>
                                
                                <h5>Job Type</h5>
                                <p><span class="badge badge-primary"><?php echo htmlspecialchars($job['job_type']); ?></span></p>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h5>Salary</h5>
                                        <?php if(!empty($job['salary_min']) || !empty($job['salary_max'])): ?>
                                            <p>
                                                <?php 
                                                if(!empty($job['salary_min']) && !empty($job['salary_max'])) {
                                                    echo '$' . number_format($job['salary_min'], 2) . ' - $' . number_format($job['salary_max'], 2);
                                                } elseif(!empty($job['salary_min'])) {
                                                    echo 'From $' . number_format($job['salary_min'], 2);
                                                } elseif(!empty($job['salary_max'])) {
                                                    echo 'Up to $' . number_format($job['salary_max'], 2);
                                                }
                                                
                                                if(!empty($job['salary_period'])) {
                                                    echo ' per ' . strtolower($job['salary_period']);
                                                }
                                                ?>
                                            </p>
                                        <?php else: ?>
                                            <p>Not specified</p>
                                        <?php endif; ?>
                                        
                                        <h5>Posted</h5>
                                        <p><?php echo date('F j, Y', strtotime($job['created_at'])); ?></p>
                                        
                                        <?php if(!empty($job['expiry_date'])): ?>
                                            <h5>Application Deadline</h5>
                                            <p><?php echo date('F j, Y', strtotime($job['expiry_date'])); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="job-description mt-4">
                            <h4>Job Description</h4>
                            <div class="p-3 bg-light rounded">
                                <?php echo nl2br(htmlspecialchars($job['description'])); ?>
                            </div>
                        </div>
                        
                        <?php if(!empty($job['requirements'])): ?>
                            <div class="job-requirements mt-4">
                                <h4>Requirements</h4>
                                <div class="p-3 bg-light rounded">
                                    <?php echo nl2br(htmlspecialchars($job['requirements'])); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if(!$isEmployer && $job['status'] == 'Published'): ?>
                            <div class="application-section text-center mt-5">
                                <a href="../apply_job.php?id=<?php echo $jobId; ?>" class="btn btn-success btn-lg">Apply for this job</a>
                            </div>
                        <?php endif; ?>
                        
                        <?php if($isOwner): ?>
                            <div class="owner-info card mt-5">
                                <div class="card-header bg-secondary text-white">
                                    <h5 class="m-0">Employer Controls</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h5>Posting Information</h5>
                                            <ul class="list-group">
                                                <li class="list-group-item d-flex justify-content-between">
                                                    <span>Status:</span>
                                                    <strong><?php echo htmlspecialchars($job['status']); ?></strong>
                                                </li>
                                                <li class="list-group-item d-flex justify-content-between">
                                                    <span>Created:</span>
                                                    <strong><?php echo date('M j, Y g:i A', strtotime($job['created_at'])); ?></strong>
                                                </li>
                                                <li class="list-group-item d-flex justify-content-between">
                                                    <span>Last Updated:</span>
                                                    <strong><?php echo date('M j, Y g:i A', strtotime($job['updated_at'])); ?></strong>
                                                </li>
                                                <?php if(!empty($job['expiry_date'])): ?>
                                                    <li class="list-group-item d-flex justify-content-between">
                                                        <span>Expires:</span>
                                                        <strong><?php echo date('M j, Y', strtotime($job['expiry_date'])); ?></strong>
                                                    </li>
                                                <?php endif; ?>
                                            </ul>
                                        </div>
                                        <div class="col-md-6">
                                            <h5>Actions</h5>
                                            <div class="list-group">
                                                <a href="edit_job.php?id=<?php echo $jobId; ?>" class="list-group-item list-group-item-action">
                                                    <i class="fa fa-edit"></i> Edit Job Details
                                                </a>
                                                <?php if($job['status'] == 'Published'): ?>
                                                    <a href="job_applications.php?job_id=<?php echo $jobId; ?>" class="list-group-item list-group-item-action">
                                                        <i class="fa fa-users"></i> View Applications
                                                    </a>
                                                    <a href="close_job.php?id=<?php echo $jobId; ?>" class="list-group-item list-group-item-action list-group-item-danger"
                                                       onclick="return confirm('Are you sure you want to close this job posting?');">
                                                        <i class="fa fa-times-circle"></i> Close Job Posting
                                                    </a>
                                                <?php elseif($job['status'] == 'Draft'): ?>
                                                    <a href="publish_job.php?id=<?php echo $jobId; ?>" class="list-group-item list-group-item-action list-group-item-success">
                                                        <i class="fa fa-check-circle"></i> Publish Job
                                                    </a>
                                                    <a href="delete_job.php?id=<?php echo $jobId; ?>" class="list-group-item list-group-item-action list-group-item-danger"
                                                       onclick="return confirm('Are you sure you want to delete this draft job posting? This cannot be undone.');">
                                                        <i class="fa fa-trash"></i> Delete Draft
                                                    </a>
                                                <?php elseif($job['status'] == 'Closed' || $job['status'] == 'Filled'): ?>
                                                    <a href="reopen_job.php?id=<?php echo $jobId; ?>" class="list-group-item list-group-item-action list-group-item-success">
                                                        <i class="fa fa-redo"></i> Reopen Job
                                                    </a>
                                                    <a href="job_applications.php?job_id=<?php echo $jobId; ?>" class="list-group-item list-group-item-action">
                                                        <i class="fa fa-users"></i> View Applications
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card-footer">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <?php if(!$isEmployer && $job['status'] == 'Published'): ?>
                                    <a href="../apply_job.php?id=<?php echo $jobId; ?>" class="btn btn-success">Apply Now</a>
                                <?php endif; ?>
                            </div>
                            <div>
                                <a href="javascript:history.back()" class="btn btn-outline-secondary">
                                    <i class="fa fa-arrow-left"></i> Back
                                </a>
                                <?php if($isOwner): ?>
                                    <a href="job_listings.php" class="btn btn-outline-primary ml-2">My Job Listings</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if(!$isEmployer): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="m-0">About <?php echo htmlspecialchars($job['company_name']); ?></h5>
                        </div>
                        <div class="card-body">
                            <p>Contact the employer through:</p>
                            <a href="mailto:<?php echo htmlspecialchars($job['employer_email']); ?>" class="btn btn-outline-primary">
                                <i class="fa fa-envelope"></i> Email Employer
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Include footer
include_once($includePath . "footer.php");
?>