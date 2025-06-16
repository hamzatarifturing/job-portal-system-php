<?php
// Start the session
session_start();

/**
 * dashboard.php - Dashboard for job seekers
 * 
 * This script displays the job seeker's dashboard with:
 * 1. User profile information
 * 2. Quick stats (applications, saved jobs, profile views)
 * 3. List of applied jobs in descending order
 * 4. Recommended jobs based on user profile
 */

// Check if user is logged in and is a jobseeker
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'jobseeker') {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

// Set include path for includes folder
$includePath = "../includes/";

// Include database configuration
include_once($includePath . "db_config.php");

// Get user ID from session
$userId = $_SESSION['user_id'];

// Get user data
$userQuery = "SELECT * FROM users WHERE id = ? AND user_type = 'jobseeker'";
$userStmt = mysqli_prepare($conn, $userQuery);
mysqli_stmt_bind_param($userStmt, "i", $userId);
mysqli_stmt_execute($userStmt);
$userResult = mysqli_stmt_get_result($userStmt);

// Check if user exists
if(mysqli_num_rows($userResult) == 0) {
    // User not found or not a jobseeker
    session_destroy();
    header("Location: ../login.php?error=invalid_user");
    exit();
}

// Fetch user data
$userData = mysqli_fetch_assoc($userResult);
mysqli_stmt_close($userStmt);

// Set page title
$pageTitle = "Dashboard - " . $userData['first_name'] . " " . $userData['last_name'];

// Count total applications
$appCountQuery = "SELECT COUNT(*) as count FROM job_applications WHERE user_id = ?";
$appCountStmt = mysqli_prepare($conn, $appCountQuery);
mysqli_stmt_bind_param($appCountStmt, "i", $userId);
mysqli_stmt_execute($appCountStmt);
$appCountResult = mysqli_stmt_get_result($appCountStmt);
$appCount = mysqli_fetch_assoc($appCountResult)['count'];
mysqli_stmt_close($appCountStmt);

// Get applied jobs with details - ordered by application_date in descending order
$appliedJobsQuery = "SELECT ja.id as application_id, ja.application_date, ja.status, 
                        jp.id as job_id, jp.title, jp.location, jp.job_type, jp.min_salary, jp.max_salary,
                        u.company_name, u.company_logo
                    FROM job_applications ja
                    JOIN job_postings jp ON ja.job_id = jp.id
                    JOIN users u ON jp.user_id = u.id
                    WHERE ja.user_id = ?
                    ORDER BY ja.application_date DESC";

$appliedJobsStmt = mysqli_prepare($conn, $appliedJobsQuery);
mysqli_stmt_bind_param($appliedJobsStmt, "i", $userId);
mysqli_stmt_execute($appliedJobsStmt);
$appliedJobsResult = mysqli_stmt_get_result($appliedJobsStmt);

// Include header
include_once($includePath . "header.php");
?>

<div class="container mt-4">
    <div class="jumbotron bg-primary text-white">
        <div class="container">
            <h1 class="display-4">Welcome, <?php echo htmlspecialchars($userData['first_name']); ?>!</h1>
            <p class="lead">Manage your job applications and discover new opportunities.</p>
            <div class="mt-4">
                <a class="btn btn-light btn-lg" href="jobs.php" role="button">Browse Jobs</a>
                <a class="btn btn-outline-light btn-lg" href="edit_profile.php" role="button">Edit Profile</a>
            </div>
        </div>
    </div>
    
    <!-- Dashboard stats -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <h5 class="m-0">Applications</h5>
                </div>
                <div class="card-body text-center">
                    <p class="card-text display-4"><?php echo $appCount; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="m-0">Profile Completion</h5>
                </div>
                <div class="card-body text-center">
                    <?php
                    // Simple profile completion percentage calculation
                    $fields = array($userData['skills'], $userData['education'], $userData['experience'], $userData['resume']);
                    $filledFields = 0;
                    foreach($fields as $field) {
                        if(!empty($field)) {
                            $filledFields++;
                        }
                    }
                    $completionPercentage = ($filledFields / count($fields)) * 100;
                    ?>
                    <div class="progress mb-3">
                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $completionPercentage; ?>%;" 
                            aria-valuenow="<?php echo $completionPercentage; ?>" aria-valuemin="0" aria-valuemax="100">
                            <?php echo round($completionPercentage); ?>%
                        </div>
                    </div>
                    <a href="edit_profile.php" class="btn btn-outline-success">Complete Profile</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-info">
                <div class="card-header bg-info text-white">
                    <h5 class="m-0">Job Alerts</h5>
                </div>
                <div class="card-body text-center">
                    <p class="card-text display-4">0</p>
                    <a href="job_alerts.php" class="btn btn-outline-info">Set Alerts</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Applied Jobs Section -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="m-0">Your Job Applications</h5>
            <a href="jobs.php" class="btn btn-light btn-sm">Find More Jobs</a>
        </div>
        <div class="card-body">
            <?php if(mysqli_num_rows($appliedJobsResult) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th>Job Title</th>
                                <th>Company</th>
                                <th>Location</th>
                                <th>Applied On</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($job = mysqli_fetch_assoc($appliedJobsResult)): ?>
                                <tr>
                                    <td>
                                        <a href="job_details.php?id=<?php echo $job['job_id']; ?>">
                                            <?php echo htmlspecialchars($job['title']); ?>
                                        </a>
                                        <span class="badge badge-<?php 
                                            echo $job['job_type'] == 'Full-time' ? 'primary' : 
                                                ($job['job_type'] == 'Part-time' ? 'success' : 
                                                    ($job['job_type'] == 'Contract' ? 'warning' : 'secondary')); 
                                        ?> ml-2"><?php echo htmlspecialchars($job['job_type']); ?></span>
                                    </td>
                                    <td>
                                        <?php if(!empty($job['company_logo'])): ?>
                                            <img src="../uploads/logos/<?php echo htmlspecialchars($job['company_logo']); ?>" 
                                                alt="<?php echo htmlspecialchars($job['company_name']); ?>" 
                                                class="company-logo-sm mr-2">
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($job['company_name']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($job['location']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($job['application_date'])); ?></td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo $job['status'] == 'pending' ? 'warning' : 
                                                ($job['status'] == 'shortlisted' ? 'info' : 
                                                    ($job['status'] == 'hired' ? 'success' : 
                                                        ($job['status'] == 'rejected' ? 'danger' : 'secondary'))); 
                                        ?>">
                                            <?php echo ucfirst(htmlspecialchars($job['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="job_details.php?id=<?php echo $job['job_id']; ?>" class="btn btn-sm btn-outline-primary">
                                            View Job
                                        </a>
                                        <?php if($job['status'] == 'pending'): ?>
                                            <a href="withdraw_application.php?id=<?php echo $job['application_id']; ?>" 
                                               class="btn btn-sm btn-outline-danger"
                                               onclick="return confirm('Are you sure you want to withdraw this application?');">
                                                Withdraw
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <p>You haven't applied to any jobs yet.</p>
                    <a href="jobs.php" class="btn btn-primary">Browse Jobs Now</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Job Recommendations -->
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="m-0">Recommended Jobs</h5>
        </div>
        <div class="card-body">
            <?php
            // Get recommended jobs based on user skills or job preferences
            $recommendedJobsQuery = "SELECT jp.id, jp.title, jp.location, jp.job_type, jp.date_posted,
                                    u.company_name, u.company_logo
                                FROM job_postings jp
                                JOIN users u ON jp.user_id = u.id
                                WHERE jp.status = 'Published'
                                AND (jp.expiry_date IS NULL OR jp.expiry_date >= CURDATE())
                                AND jp.id NOT IN (SELECT job_id FROM job_applications WHERE user_id = ?)
                                ORDER BY jp.date_posted DESC
                                LIMIT 5";
            
            $recommendedJobsStmt = mysqli_prepare($conn, $recommendedJobsQuery);
            mysqli_stmt_bind_param($recommendedJobsStmt, "i", $userId);
            mysqli_stmt_execute($recommendedJobsStmt);
            $recommendedJobsResult = mysqli_stmt_get_result($recommendedJobsStmt);
            
            if(mysqli_num_rows($recommendedJobsResult) > 0):
            ?>
                <div class="row">
                    <?php while($recJob = mysqli_fetch_assoc($recommendedJobsResult)): ?>
                        <div class="col-md-6 mb-3">
                            <div class="card h-100 border-light">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h5 class="card-title mb-1">
                                                <a href="job_details.php?id=<?php echo $recJob['id']; ?>">
                                                    <?php echo htmlspecialchars($recJob['title']); ?>
                                                </a>
                                            </h5>
                                            <h6 class="card-subtitle text-muted mb-2">
                                                <?php echo htmlspecialchars($recJob['company_name']); ?>
                                            </h6>
                                        </div>
                                        <?php if(!empty($recJob['company_logo'])): ?>
                                            <img src="../uploads/logos/<?php echo htmlspecialchars($recJob['company_logo']); ?>" 
                                                alt="<?php echo htmlspecialchars($recJob['company_name']); ?>" 
                                                class="company-logo-sm">
                                        <?php endif; ?>
                                    </div>
                                    <p class="card-text">
                                        <i class="fa fa-map-marker-alt text-secondary"></i> <?php echo htmlspecialchars($recJob['location']); ?><br>
                                        <span class="badge badge-<?php 
                                            echo $recJob['job_type'] == 'Full-time' ? 'primary' : 
                                                ($recJob['job_type'] == 'Part-time' ? 'success' : 
                                                    ($recJob['job_type'] == 'Contract' ? 'warning' : 'secondary')); 
                                        ?>"><?php echo htmlspecialchars($recJob['job_type']); ?></span>
                                        <small class="text-muted ml-2">Posted <?php echo time_elapsed_string($recJob['date_posted']); ?></small>
                                    </p>
                                </div>
                                <div class="card-footer bg-white border-top-0">
                                    <a href="apply_job.php?id=<?php echo $recJob['id']; ?>" class="btn btn-primary btn-sm">Apply Now</a>
                                    <a href="job_details.php?id=<?php echo $recJob['id']; ?>" class="btn btn-outline-secondary btn-sm">View Details</a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <p>Complete your profile to get personalized job recommendations.</p>
                    <a href="edit_profile.php" class="btn btn-success">Update Profile</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Helper function to format date
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

// Close the database connection
mysqli_close($conn);

// Include footer
include_once($includePath . "footer.php");
?>