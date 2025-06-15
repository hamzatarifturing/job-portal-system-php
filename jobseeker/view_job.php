<?php
// Start the session
session_start();

/**
 * view_job.php - Display detailed information about a specific job
 * 
 * This script retrieves and displays comprehensive information about a job posting
 * based on the job ID provided in the URL parameter.
 */

// Check if user is logged in and is a job seeker
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'jobseeker') {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

// Set include path for includes folder
$includePath = "../includes/";

// Include database configuration
include_once($includePath . "db_config.php");
include_once($includePath . "header.php");

// Set default page title
$pageTitle = "Job Details";

// Get user ID from session
$userId = $_SESSION['user_id'];

// Check if job ID is provided in the URL parameters
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo '<div class="container mt-5">';
    echo '<div class="alert alert-danger">Invalid job ID provided. <a href="jobs.php">Return to job listings</a></div>';
    echo '</div>';
    include_once($includePath . "footer.php");
    exit();
}

// Sanitize the job ID
$jobId = intval($_GET['id']);

// Query to fetch the job details including employer information
$query = "SELECT j.*, 
            u.company_name, u.company_logo, u.website, u.industry, 
            u.company_size, u.company_description, u.location as company_location
        FROM job_postings j
        LEFT JOIN users u ON j.user_id = u.id
        WHERE j.id = ? AND j.status = 'Published' 
              AND (j.expiry_date IS NULL OR j.expiry_date >= CURDATE())";

$stmt = mysqli_prepare($conn, $query);

if (!$stmt) {
    // Handle database error
    echo '<div class="container mt-5">';
    echo '<div class="alert alert-danger">Database error occurred. Please try again later. <a href="jobs.php">Return to job listings</a></div>';
    echo '</div>';
    include_once($includePath . "footer.php");
    exit();
}

// Bind parameters
mysqli_stmt_bind_param($stmt, "i", $jobId);

// Execute the query
if (!mysqli_stmt_execute($stmt)) {
    // Handle execution error
    mysqli_stmt_close($stmt);
    echo '<div class="container mt-5">';
    echo '<div class="alert alert-danger">Failed to retrieve job details. Please try again later. <a href="jobs.php">Return to job listings</a></div>';
    echo '</div>';
    include_once($includePath . "footer.php");
    exit();
}

$result = mysqli_stmt_get_result($stmt);

// Check if job exists and is published
if (mysqli_num_rows($result) == 0) {
    mysqli_stmt_close($stmt);
    echo '<div class="container mt-5">';
    echo '<div class="alert alert-danger">Job not found or no longer available. <a href="jobs.php">Return to job listings</a></div>';
    echo '</div>';
    include_once($includePath . "footer.php");
    exit();
}

// Fetch job details
$job = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Update the page title with job title
$pageTitle = htmlspecialchars($job['title']) . " - Job Details";

// Check if the user has already applied for this job
$hasApplied = false;
$applyQuery = "SELECT id FROM job_applications WHERE job_id = ? AND user_id = ?";
$applyStmt = mysqli_prepare($conn, $applyQuery);

if ($applyStmt) {
    mysqli_stmt_bind_param($applyStmt, "ii", $jobId, $userId);
    if (mysqli_stmt_execute($applyStmt)) {
        mysqli_stmt_store_result($applyStmt);
        $hasApplied = (mysqli_stmt_num_rows($applyStmt) > 0);
    }
    mysqli_stmt_close($applyStmt);
}

// Calculate days remaining until expiry
$daysRemaining = null;
if ($job['expiry_date']) {
    $expiryDate = new DateTime($job['expiry_date']);
    $today = new DateTime();
    $interval = $today->diff($expiryDate);
    $daysRemaining = $interval->days;
}

// Format salary information
$salaryDisplay = "Not specified";
if (!empty($job['salary_min']) && !empty($job['salary_max'])) {
    $salaryDisplay = '$' . number_format($job['salary_min']) . ' - $' . number_format($job['salary_max']);
} elseif (!empty($job['salary_min'])) {
    $salaryDisplay = '$' . number_format($job['salary_min']) . '+';
} elseif (!empty($job['salary_max'])) {
    $salaryDisplay = 'Up to $' . number_format($job['salary_max']);
}

// Get job posted date
$postedDate = new DateTime($job['created_at']);
$postedDaysAgo = $postedDate->diff(new DateTime())->days;
$postedDisplay = ($postedDaysAgo == 0) ? "Today" : (($postedDaysAgo == 1) ? "Yesterday" : $postedDaysAgo . " days ago");
?>

<div class="container mt-4 mb-5">
    <!-- Back to jobs navigation -->
    <div class="mb-4">
        <a href="jobs.php" class="btn btn-outline-secondary">
            <i class="fa fa-arrow-left"></i> Back to Job Listings
        </a>
    </div>

    <!-- Job details card -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="m-0"><?php echo htmlspecialchars($job['title']); ?></h3>
                <span class="badge badge-light"><?php echo htmlspecialchars($job['job_type']); ?></span>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <!-- Left column: Main job details -->
                <div class="col-lg-8">
                    <div class="d-flex align-items-center mb-3">
                        <?php if (!empty($job['company_logo'])): ?>
                        <div class="mr-3">
                            <img src="<?php echo htmlspecialchars($job['company_logo']); ?>" alt="<?php echo htmlspecialchars($job['company_name']); ?> Logo" class="img-fluid" style="max-height: 60px; max-width: 120px;">
                        </div>
                        <?php endif; ?>
                        <div>
                            <h4 class="mb-1"><?php echo htmlspecialchars($job['company_name']); ?></h4>
                            <p class="text-muted mb-0">
                                <?php echo !empty($job['location']) ? htmlspecialchars($job['location']) : 'Location not specified'; ?>
                            </p>
                        </div>
                    </div>

                    <!-- Key details section -->
                    <div class="row mb-4">
                        <div class="col-md-6 mb-2">
                            <div class="d-flex align-items-center">
                                <i class="fa fa-money-bill-alt text-success mr-2"></i>
                                <div>
                                    <small class="text-muted d-block">Salary</small>
                                    <strong><?php echo $salaryDisplay; ?></strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-2">
                            <div class="d-flex align-items-center">
                                <i class="fa fa-calendar-alt text-primary mr-2"></i>
                                <div>
                                    <small class="text-muted d-block">Job Type</small>
                                    <strong><?php echo htmlspecialchars($job['job_type']); ?></strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-2">
                            <div class="d-flex align-items-center">
                                <i class="fa fa-clock text-info mr-2"></i>
                                <div>
                                    <small class="text-muted d-block">Posted</small>
                                    <strong><?php echo $postedDisplay; ?></strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-2">
                            <div class="d-flex align-items-center">
                                <i class="fa fa-hourglass-half text-warning mr-2"></i>
                                <div>
                                    <small class="text-muted d-block">Application Deadline</small>
                                    <strong>
                                        <?php 
                                        if ($job['expiry_date']) {
                                            echo date('M d, Y', strtotime($job['expiry_date']));
                                            if ($daysRemaining > 0) {
                                                echo " ({$daysRemaining} days left)";
                                            } elseif ($daysRemaining == 0) {
                                                echo " (Last day)";
                                            }
                                        } else {
                                            echo "Open until filled";
                                        }
                                        ?>
                                    </strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Job description -->
                    <div class="mb-4">
                        <h5>Job Description</h5>
                        <div class="job-description">
                            <?php echo nl2br(htmlspecialchars($job['description'])); ?>
                        </div>
                    </div>

                    <!-- Requirements -->
                    <?php if (!empty($job['requirements'])): ?>
                    <div class="mb-4">
                        <h5>Requirements</h5>
                        <div class="job-requirements">
                            <?php echo nl2br(htmlspecialchars($job['requirements'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Benefits -->
                    <?php if (!empty($job['benefits'])): ?>
                    <div class="mb-4">
                        <h5>Benefits</h5>
                        <div class="job-benefits">
                            <?php echo nl2br(htmlspecialchars($job['benefits'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Right column: Apply button and company details -->
                <div class="col-lg-4">
                    <!-- Apply button section -->
                    <div class="card mb-4">
                        <div class="card-body text-center">
                            <?php if ($hasApplied): ?>
                                <div class="alert alert-success mb-3">
                                    <i class="fa fa-check-circle"></i> You have already applied to this job
                                </div>
                                <a href="my_applications.php" class="btn btn-outline-primary">View My Applications</a>
                            <?php else: ?>
                                <a href="apply_job.php?id=<?php echo $jobId; ?>" class="btn btn-success btn-lg btn-block">
                                    <i class="fa fa-paper-plane"></i> Apply Now
                                </a>
                                <p class="text-muted mt-2 small">
                                    Apply before <?php echo $job['expiry_date'] ? date('M d, Y', strtotime($job['expiry_date'])) : 'the position is filled'; ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Company information -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="m-0">Company Information</h5>
                        </div>
                        <div class="card-body">
                            <h6><?php echo htmlspecialchars($job['company_name']); ?></h6>
                            
                            <?php if (!empty($job['industry'])): ?>
                                <p><strong>Industry:</strong> <?php echo htmlspecialchars($job['industry']); ?></p>
                            <?php endif; ?>
                            
                            <?php if (!empty($job['company_size'])): ?>
                                <p><strong>Company Size:</strong> <?php echo htmlspecialchars($job['company_size']); ?></p>
                            <?php endif; ?>
                            
                            <?php if (!empty($job['company_location'])): ?>
                                <p><strong>Headquarters:</strong> <?php echo htmlspecialchars($job['company_location']); ?></p>
                            <?php endif; ?>
                            
                            <?php if (!empty($job['website'])): ?>
                                <p><strong>Website:</strong> 
                                    <a href="<?php echo htmlspecialchars($job['website']); ?>" target="_blank">
                                        <?php echo htmlspecialchars($job['website']); ?>
                                    </a>
                                </p>
                            <?php endif; ?>
                            
                            <?php if (!empty($job['company_description'])): ?>
                                <p><strong>About:</strong><br> 
                                    <?php echo nl2br(htmlspecialchars($job['company_description'])); ?>
                                </p>
                            <?php endif; ?>

                            <a href="company_profile.php?id=<?php echo $job['user_id']; ?>" class="btn btn-outline-primary btn-sm btn-block mt-2">
                                View Company Profile
                            </a>
                        </div>
                    </div>

                    <!-- Share job -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="m-0">Share This Job</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-around">
                                <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>&title=<?php echo urlencode($job['title'] . ' at ' . $job['company_name']); ?>" target="_blank" class="btn btn-outline-primary">
                                    <i class="fab fa-linkedin"></i>
                                </a>
                                <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>&text=<?php echo urlencode('Check out this job: ' . $job['title'] . ' at ' . $job['company_name']); ?>" target="_blank" class="btn btn-outline-info">
                                    <i class="fab fa-twitter"></i>
                                </a>
                                <a href="mailto:?subject=<?php echo urlencode('Job Opportunity: ' . $job['title'] . ' at ' . $job['company_name']); ?>&body=<?php echo urlencode('I found this job opening that might interest you: ' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" class="btn btn-outline-secondary">
                                    <i class="fa fa-envelope"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer bg-light">
            <div class="d-flex justify-content-between align-items-center">
                <span class="text-muted">Job ID: <?php echo $jobId; ?></span>
                <span class="text-muted">Posted on: <?php echo date('M d, Y', strtotime($job['created_at'])); ?></span>
            </div>
        </div>
    </div>

    <!-- Related jobs section -->
    <?php
    // Get related jobs based on the same job type or category
    $relatedJobsQuery = "SELECT id, title, company_name, location, job_type, created_at 
                        FROM job_postings j
                        LEFT JOIN users u ON j.user_id = u.id
                        WHERE j.id != ? 
                        AND j.status = 'Published' 
                        AND (j.expiry_date IS NULL OR j.expiry_date >= CURDATE())
                        AND (j.job_type = ? OR j.category = ?)
                        ORDER BY j.created_at DESC
                        LIMIT 3";
                        
    $relStmt = mysqli_prepare($conn, $relatedJobsQuery);
    
    if ($relStmt) {
        mysqli_stmt_bind_param($relStmt, "iss", $jobId, $job['job_type'], $job['category']);
        mysqli_stmt_execute($relStmt);
        $relatedResult = mysqli_stmt_get_result($relStmt);
        
        if (mysqli_num_rows($relatedResult) > 0) {
            echo '<div class="card mb-4">';
            echo '<div class="card-header bg-light"><h5 class="m-0">Similar Jobs You Might Like</h5></div>';
            echo '<div class="card-body"><div class="row">';
            
            while ($relatedJob = mysqli_fetch_assoc($relatedResult)) {
                $relPostedDate = new DateTime($relatedJob['created_at']);
                $relPostedDays = $relPostedDate->diff(new DateTime())->days;
                $relPostedText = ($relPostedDays == 0) ? "Today" : (($relPostedDays == 1) ? "Yesterday" : $relPostedDays . " days ago");
                
                echo '<div class="col-md-4 mb-3">';
                echo '<div class="card h-100 border-light">';
                echo '<div class="card-body">';
                echo '<h6 class="card-title">' . htmlspecialchars($relatedJob['title']) . '</h6>';
                echo '<p class="card-text mb-1">' . htmlspecialchars($relatedJob['company_name']) . '</p>';
                echo '<p class="card-text mb-1 small text-muted"><i class="fa fa-map-marker-alt"></i> ' . 
                      htmlspecialchars($relatedJob['location']) . '</p>';
                echo '<p class="card-text mb-2 small text-muted"><i class="fa fa-clock"></i> Posted ' . $relPostedText . '</p>';
                echo '<span class="badge badge-pill badge-secondary">' . htmlspecialchars($relatedJob['job_type']) . '</span>';
                echo '</div>';
                echo '<div class="card-footer bg-white border-top-0">';
                echo '<a href="view_job.php?id=' . $relatedJob['id'] . '" class="btn btn-sm btn-outline-primary btn-block">View Job</a>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
            }
            
            echo '</div></div>';
            echo '<div class="card-footer text-center">';
            echo '<a href="jobs.php?job_type=' . urlencode($job['job_type']) . '" class="btn btn-link">View More ' . htmlspecialchars($job['job_type']) . ' Jobs</a>';
            echo '</div>';
            echo '</div>';
        }
        
        mysqli_stmt_close($relStmt);
    }
    ?>

    <!-- Call to action for more jobs -->
    <div class="mb-4 text-center">
        <hr>
        <h5>Looking for more opportunities?</h5>
        <div class="mt-3">
            <a href="jobs.php" class="btn btn-primary mx-2">Browse All Jobs</a>
            <a href="saved_jobs.php" class="btn btn-outline-secondary mx-2">View Saved Jobs</a>
            <a href="my_applications.php" class="btn btn-outline-secondary mx-2">My Applications</a>
        </div>
    </div>
</div>

<?php
// Close database connection
mysqli_close($conn);

// Include footer
include_once($includePath . "footer.php");
?>