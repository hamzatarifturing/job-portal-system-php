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
// Modified to match the actual schema structure
$query = "SELECT a.*, 
          j.title as job_title, j.description as job_description, 
          j.location as job_location, j.job_type, j.salary_min, j.salary_max, j.salary_period,
          j.created_at as job_posted_date, j.company_name,
          u.first_name, u.last_name, u.email, u.phone, u.address, 
          u.profile_pic as profile_picture, u.city, u.state, u.country, u.postal_code as zip_code, 
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
<div>
<div class="row mb-4">
    <div class="col-md-8">
<h1>
Application Details

</h1>
<nav>
<ol>
<li>
<a>
Dashboard

</a>
</li>
<li>
<a>
Applications

</a>
</li>
<li>
Application #<?php echo $applicationId; ?>

</li>
</ol>
</nav>
</div>
<div>
<a>
<i class="fa fa-arrow-left"></i>

Back to Applications

</a>
</div>
</div>

<?php if(isset($errorMsg)): ?>
<div>
<?php echo htmlspecialchars($errorMsg); ?>
</div>
<?php endif; ?>

<?php if(isset($successMsg)): ?>
<div>
<?php echo htmlspecialchars($successMsg); ?>
</div>
<?php endif; ?>
<div>
    <div class="col-md-8">
        <!-- Job and Applicant Information -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
<h5>
Job Application Overview

</h5>
</div>
<div>
                <div class="row">
                    <div class="col-md-6">
<h5>
Job Details

</h5>
<table>
<tr>
<th>
Position:

</th>
<td>
<?php echo htmlspecialchars($application['job_title']); ?>
</td>
</tr>
<tr>
<th>
Company:

</th>
<td>
<?php echo htmlspecialchars($application['company_name']); ?>
</td>
</tr>
<tr>
<th>
Location:

</th>
<td>
<?php echo htmlspecialchars($application['job_location']); ?>
</td>
</tr>
<tr>
<th>
Type:

</th>
<td>
<?php echo htmlspecialchars($application['job_type']); ?>
</td>
</tr>
<tr>
<th>
Salary Range:

</th>
<td>
                                    <?php if(!empty($application['salary_min']) && !empty($application['salary_max'])): ?>
                                        $<?php echo htmlspecialchars(number_format($application['salary_min'])); ?> - 
                                        $<?php echo htmlspecialchars(number_format($application['salary_max'])); ?>
                                        <?php if(!empty($application['salary_period'])): ?>
                                            (<?php echo htmlspecialchars($application['salary_period']); ?>)
                                        <?php endif; ?>
                                    <?php else: ?>
                                        Not specified
                                    <?php endif; ?>
</td>
</tr>
<tr>
<th>
Posted On:

</th>
<td>
<?php echo date('M d, Y', strtotime($application['job_posted_date'])); ?>
</td>
</tr>
</table>
</div>
<div>
<h5>
Applicant Information

</h5>
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
<h4>
<?php echo htmlspecialchars($application['first_name'] . ' ' . $application['last_name']); ?>
</h4>
<p>
<?php echo htmlspecialchars($application['email']); ?>
</p>
</div>
                        </div>
<table>
                            <?php if(!empty($application['phone'])): ?>
<tr>
<th>
Phone:

</th>
<td>
<?php echo htmlspecialchars($application['phone']); ?>
</td>
</tr>
                            <?php endif; ?>
                            <?php if(!empty($application['address']) || !empty($application['city'])): ?>
<tr>
<th>
Location:

</th>
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
<th>
Applied On:

</th>
<td>
<?php echo date('M d, Y', strtotime($application['application_date'])); ?>
</td>
</tr>
<tr>
<th>
Status:

</th>
<td>
<span>
">
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
<div>
            <div class="card-header bg-info text-white">
<h5>
Cover Letter

</h5>
</div>
<div>
                <div class="cover-letter">
                    <?php echo nl2br(htmlspecialchars($application['cover_letter'])); ?>
</div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Applicant Resume/CV -->
        <?php if(!empty($application['resume_path'])): ?>
<div>
            <div class="card-header bg-info text-white">
<h5>
Resume/CV

</h5>
</div>
<div>
<p>
The applicant has uploaded a resume/CV file.

</p>
<a>
" class="btn btn-primary" target="_blank">

<i class="fa fa-download"></i>

Download Resume

</a>
                <?php
                // Display PDF preview if it's a PDF file
                $fileExtension = pathinfo($application['resume_path'], PATHINFO_EXTENSION);
                if (strtolower($fileExtension) === 'pdf'):
                ?>
                <div class="mt-4">
<h6>
PDF Preview:

</h6>
                    <div class="embed-responsive embed-responsive-16by9">
<iframe>
" allowfullscreen>

</iframe>
</div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Skills and Experience -->
        <?php if(!empty($application['skills']) || !empty($application['experience'])): ?>
<div>
            <div class="card-header bg-info text-white">
<h5>
Skills & Experience

</h5>
</div>
<div>
                <?php if(!empty($application['skills'])): ?>
<h5>
Skills

</h5>
                <div class="mb-4">
                    <?php
                    $skills = explode(',', $application['skills']);
                    foreach($skills as $skill):
                        $skill = trim($skill);
                        if(!empty($skill)):
                    ?>
<span>
<?php echo htmlspecialchars($skill); ?>
</span>
                    <?php
                        endif;
                    endforeach;
                    ?>
</div>
                <?php endif; ?>
                
                <?php if(!empty($application['experience'])): ?>
<h5>
Experience

</h5>
<div>
                    <?php echo nl2br(htmlspecialchars($application['experience'])); ?>
</div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Education -->
        <?php if(!empty($application['education'])): ?>
<div>
            <div class="card-header bg-info text-white">
<h5>
Education

</h5>
</div>
<div>
                <?php echo nl2br(htmlspecialchars($application['education'])); ?>
</div>
        </div>
        <?php endif; ?>
        
        <!-- Bio -->
        <?php if(!empty($application['bio'])): ?>
<div>
            <div class="card-header bg-info text-white">
<h5>
About the Applicant

</h5>
</div>
<div>
                <?php echo nl2br(htmlspecialchars($application['bio'])); ?>
</div>
        </div>
        <?php endif; ?>
    </div>
<div>
        <!-- Application Status -->
        <div class="card mb-4 sticky-top" style="top: 20px;">
            <div class="card-header bg-primary text-white">
<h5>
Manage Application

</h5>
</div>
<div>
                <form method="POST" action="view_application.php?id=<?php echo $applicationId; ?>">
                    <div class="form-group">
<label>
<strong>
Application Status:

</strong>
</label>
<select>
<option>
Pending

</option>
<option>
Reviewed

</option>
<option>
Shortlisted

</option>
<option>
Rejected

</option>
<option>
Hired

</option>
</select>
</div>
<button>
Update Status

</button>
                </form>
                
                <hr>
<div>
<a>
" class="btn btn-success btn-block mb-2">

<i class="fa fa-envelope"></i>

Contact Applicant

</a>
                    <?php if(!empty($application['phone'])): ?>
<a>
" class="btn btn-info btn-block mb-2">

<i class="fa fa-phone"></i>

Call Applicant

</a>
                    <?php endif; ?>
<a>
<i class="fa fa-arrow-left"></i>

Back to Applications

</a>
</div>
            </div>
        </div>
    </div>
</div>
</div> <?php // Include footer 
include_once($includePath . "footer.php"); ?>