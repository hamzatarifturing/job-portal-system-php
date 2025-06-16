<?php
// Start the session
session_start();

/**
 * view_application.php - Display detailed information about a specific job application
 * 
 * This script displays comprehensive details about a job application, including:
 * - Applicant details
 * - Job details
 * - Application timeline
 * - Resume and cover letter
 * - Application status management
 */

// Check if user is logged in and is an employer
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'employer') {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

// Set include path for includes folder
$includePath = "../includes/";

// Include database configuration
include_once($includePath . "db_config.php");

// Get employer ID from session
$employerId = $_SESSION['user_id'];

// Set default page title
$pageTitle = "View Application";

// Check if application ID is provided in the URL parameters
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: applications.php?error=invalid_application_id");
    exit();
}

// Sanitize the application ID
$applicationId = intval($_GET['id']);

// Process application status updates if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $newStatus = isset($_POST['status']) ? $_POST['status'] : '';
    
    // Validate the status value
    $validStatuses = ['pending', 'reviewed', 'shortlisted', 'rejected', 'hired'];
    if (in_array($newStatus, $validStatuses)) {
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
        $errorMsg = "Invalid status value.";
    }
}

// Fetch application details
$query = "SELECT a.*, j.title as job_title, j.description as job_description, 
          j.location as job_location, j.job_type, j.salary_min, j.salary_max, 
          j.created_at as job_posted_date, 
          u.first_name, u.last_name, u.email, u.phone, u.address, 
          u.profile_picture, u.city, u.state, u.country, u.zip_code, 
          u.bio, u.skills, u.education, u.experience
          FROM job_applications a
          JOIN job_postings j ON a.job_id = j.id
          JOIN users u ON a.user_id = u.id
          WHERE a.id = ? AND j.user_id = ?";

$stmt = mysqli_prepare($conn, $query);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ii", $applicationId, $employerId);
    
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $application = mysqli_fetch_assoc($result);
        } else {
            // Application not found or does not belong to this employer
            header("Location: applications.php?error=application_not_found");
            exit();
        }
    } else {
        $errorMsg = "Error executing query: " . mysqli_error($conn);
    }
    
    mysqli_stmt_close($stmt);
} else {
    $errorMsg = "Database error: " . mysqli_error($conn);
}

// Include header
include_once($includePath . "header.php");
?>

<div class="container mt-5 mb-5">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1>Application Details</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="applications.php">Applications</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Application #<?php echo $applicationId; ?></li>
                </ol>
            </nav>
        </div>
        <div class="col-md-4 text-right">
            <a href="applications.php" class="btn btn-secondary">
                <i class="fa fa-arrow-left"></i> Back to Applications
            </a>
        </div>
    </div>
    
    <?php if(isset($errorMsg)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($errorMsg); ?></div>
    <?php endif; ?>
    
    <?php if(isset($successMsg)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($successMsg); ?></div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-8">
            <!-- Job and Applicant Information -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="m-0">Job Application Overview</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Job Details</h5>
                            <table class="table">
                                <tr>
                                    <th>Position:</th>
                                    <td><?php echo htmlspecialchars($application['job_title']); ?></td>
                                </tr>
                                <tr>
                                    <th>Location:</th>
                                    <td><?php echo htmlspecialchars($application['job_location']); ?></td>
                                </tr>
                                <tr>
                                    <th>Type:</th>
                                    <td><?php echo htmlspecialchars($application['job_type']); ?></td>
                                </tr>
                                <tr>
                                    <th>Salary Range:</th>
                                    <td>
                                        <?php if(!empty($application['salary_min']) && !empty($application['salary_max'])): ?>
                                            $<?php echo htmlspecialchars(number_format($application['salary_min'])); ?> - 
                                            $<?php echo htmlspecialchars(number_format($application['salary_max'])); ?>
                                        <?php else: ?>
                                            Not specified
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Posted On:</th>
                                    <td><?php echo date('M d, Y', strtotime($application['job_posted_date'])); ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5>Applicant Information</h5>
                            <div class="d-flex align-items-center mb-3">
                                <?php if(!empty($application['profile_picture'])): ?>
                                    <img src="<?php echo htmlspecialchars($application['profile_picture']); ?>" 
                                         class="rounded-circle mr-3" style="width: 64px; height: 64px;" alt="Profile Picture">
                                <?php else: ?>
                                    <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center mr-3" 
                                         style="width: 64px; height: 64px; font-size: 28px;">
                                        <?php echo strtoupper(substr($application['first_name'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <h4 class="mb-0"><?php echo htmlspecialchars($application['first_name'] . ' ' . $application['last_name']); ?></h4>
                                    <p class="text-muted mb-0"><?php echo htmlspecialchars($application['email']); ?></p>
                                </div>
                            </div>
                            <table class="table">
                                <?php if(!empty($application['phone'])): ?>
                                <tr>
                                    <th>Phone:</th>
                                    <td><?php echo htmlspecialchars($application['phone']); ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if(!empty($application['address']) || !empty($application['city'])): ?>
                                <tr>
                                    <th>Location:</th>
                                    <td>
                                        <?php
                                        $location = [];
                                        if(!empty($application['city'])) $location[] = htmlspecialchars($application['city']);
                                        if(!empty($application['state'])) $location[] = htmlspecialchars($application['state']);
                                        if(!empty($application['country'])) $location[] = htmlspecialchars($application['country']);
                                        echo !empty($location) ? implode(', ', $location) : 'Not specified';
                                        ?>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <th>Applied On:</th>
                                    <td><?php echo date('M d, Y', strtotime($application['application_date'])); ?></td>
                                </tr>
                                <tr>
                                    <th>Status:</th>
                                    <td>
                                        <span class="badge badge-pill badge-<?php 
                                            echo ($application['status'] === 'pending') ? 'warning' : 
                                                 (($application['status'] === 'reviewed') ? 'info' :
                                                 (($application['status'] === 'shortlisted') ? 'primary' :
                                                 (($application['status'] === 'hired') ? 'success' : 'danger'))); ?>">
                                            <?php echo ucfirst(htmlspecialchars($application['status'])); ?>
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Cover Letter -->
            <?php if(!empty($application['cover_letter'])): ?>
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="m-0">Cover Letter</h5>
                </div>
                <div class="card-body">
                    <div class="cover-letter">
                        <?php echo nl2br(htmlspecialchars($application['cover_letter'])); ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Applicant Resume/CV -->
            <?php if(!empty($application['resume_path'])): ?>
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="m-0">Resume/CV</h5>
                </div>
                <div class="card-body">
                    <p>The applicant has uploaded a resume/CV file.</p>
                    <a href="<?php echo htmlspecialchars($application['resume_path']); ?>" class="btn btn-primary" target="_blank">
                        <i class="fa fa-download"></i> Download Resume
                    </a>
                    
                    <?php
                    // Display PDF preview if it's a PDF file
                    $fileExtension = pathinfo($application['resume_path'], PATHINFO_EXTENSION);
                    if (strtolower($fileExtension) === 'pdf'):
                    ?>
                    <div class="mt-4">
                        <h6>PDF Preview:</h6>
                        <div class="embed-responsive embed-responsive-16by9">
                            <iframe class="embed-responsive-item" src="<?php echo htmlspecialchars($application['resume_path']); ?>" allowfullscreen></iframe>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Skills and Experience -->
            <?php if(!empty($application['skills']) || !empty($application['experience'])): ?>
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="m-0">Skills & Experience</h5>
                </div>
                <div class="card-body">
                    <?php if(!empty($application['skills'])): ?>
                    <h5>Skills</h5>
                    <div class="mb-4">
                        <?php
                        $skills = explode(',', $application['skills']);
                        foreach($skills as $skill):
                            $skill = trim($skill);
                            if(!empty($skill)):
                        ?>
                            <span class="badge badge-pill badge-primary p-2 m-1"><?php echo htmlspecialchars($skill); ?></span>
                        <?php
                            endif;
                        endforeach;
                        ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if(!empty($application['experience'])): ?>
                    <h5>Experience</h5>
                    <div>
                        <?php echo nl2br(htmlspecialchars($application['experience'])); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Education -->
            <?php if(!empty($application['education'])): ?>
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="m-0">Education</h5>
                </div>
                <div class="card-body">
                    <?php echo nl2br(htmlspecialchars($application['education'])); ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Bio -->
            <?php if(!empty($application['bio'])): ?>
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="m-0">About the Applicant</h5>
                </div>
                <div class="card-body">
                    <?php echo nl2br(htmlspecialchars($application['bio'])); ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="col-md-4">
            <!-- Application Status -->
            <div class="card mb-4 sticky-top" style="top: 20px;">
                <div class="card-header bg-primary text-white">
                    <h5 class="m-0">Manage Application</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="view_application.php?id=<?php echo $applicationId; ?>">
                        <div class="form-group">
                            <label for="status"><strong>Application Status:</strong></label>
                            <select name="status" id="status" class="form-control">
                                <option value="pending" <?php echo ($application['status'] === 'pending') ? 'selected' : ''; ?>>Pending</option>
                                <option value="reviewed" <?php echo ($application['status'] === 'reviewed') ? 'selected' : ''; ?>>Reviewed</option>
                                <option value="shortlisted" <?php echo ($application['status'] === 'shortlisted') ? 'selected' : ''; ?>>Shortlisted</option>
                                <option value="rejected" <?php echo ($application['status'] === 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                                <option value="hired" <?php echo ($application['status'] === 'hired') ? 'selected' : ''; ?>>Hired</option>
                            </select>
                        </div>
                        <button type="submit" name="update_status" class="btn btn-primary btn-block">Update Status</button>
                    </form>
                    
                    <hr>
                    
                    <div class="action-buttons">
                        <a href="mailto:<?php echo $application['email']; ?>" class="btn btn-success btn-block mb-2">
                            <i class="fa fa-envelope"></i> Contact Applicant
                        </a>
                        
                        <?php if(!empty($application['phone'])): ?>
                        <a href="tel:<?php echo $application['phone']; ?>" class="btn btn-info btn-block mb-2">
                            <i class="fa fa-phone"></i> Call Applicant
                        </a>
                        <?php endif; ?>
                        
                        <a href="applications.php" class="btn btn-secondary btn-block">
                            <i class="fa fa-arrow-left"></i> Back to Applications
                        </a>
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