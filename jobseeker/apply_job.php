<?php
// Start the session
session_start();

/**
 * apply_job.php - Handles job application submission
 * 
 * This script allows job seekers to apply for jobs by submitting a form with
 * their cover letter and resume. It validates that:
 * 1. The user is logged in and is a job seeker
 * 2. A valid job ID is provided in the URL
 * 3. The user hasn't already applied for this job
 * 
 * After successful submission, displays a success message on the same page.
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

// Get user ID from session
$userId = $_SESSION['user_id'];

// Check if job ID is provided in the URL parameters
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: dashboard.php?error=invalid_job_id");
    exit();
}

// Sanitize the job ID
$jobId = intval($_GET['id']);

// Initialize variables
$coverLetter = "";
$resumePath = "";
$errorMsg = "";
$successMsg = "";
$jobDetails = null;

// Check if the job exists and is active
$jobQuery = "SELECT j.id, j.title, j.company_name, j.location FROM job_postings j 
             WHERE j.id = ? AND j.status = 'Published' 
             AND (j.expiry_date IS NULL OR j.expiry_date >= CURDATE())";

$jobStmt = mysqli_prepare($conn, $jobQuery);

if ($jobStmt) {
    mysqli_stmt_bind_param($jobStmt, "i", $jobId);
    
    if (mysqli_stmt_execute($jobStmt)) {
        $jobResult = mysqli_stmt_get_result($jobStmt);
        
        if (mysqli_num_rows($jobResult) > 0) {
            $jobDetails = mysqli_fetch_assoc($jobResult);
        } else {
            // Job doesn't exist or is not active
            mysqli_stmt_close($jobStmt);
            header("Location: jobs.php?error=job_not_found");
            exit();
        }
    }
    
    mysqli_stmt_close($jobStmt);
} else {
    header("Location: jobs.php?error=database_error");
    exit();
}

// Check if the user has already applied for this job
$checkQuery = "SELECT id FROM job_applications WHERE job_id = ? AND user_id = ?";
$checkStmt = mysqli_prepare($conn, $checkQuery);

if ($checkStmt) {
    mysqli_stmt_bind_param($checkStmt, "ii", $jobId, $userId);
    
    if (mysqli_stmt_execute($checkStmt)) {
        mysqli_stmt_store_result($checkStmt);
        
        if (mysqli_stmt_num_rows($checkStmt) > 0) {
            // User has already applied
            $errorMsg = "You have already applied for this job.";
        }
    }
    
    mysqli_stmt_close($checkStmt);
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && empty($errorMsg)) {
    // Get cover letter from form
    $coverLetter = trim($_POST['cover_letter']);
    
    // Handle file upload for resume
    if (isset($_FILES['resume']) && $_FILES['resume']['error'] == 0) {
        $allowedTypes = array('application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
        $uploadedFileType = finfo_file($fileInfo, $_FILES['resume']['tmp_name']);
        finfo_close($fileInfo);
        
        if (!in_array($uploadedFileType, $allowedTypes)) {
            $errorMsg = "Invalid file type. Only PDF and Word documents are allowed.";
        } else {
            // Create upload directory if it doesn't exist
            $uploadDir = "../uploads/resumes/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Generate a unique filename
            $fileName = $userId . '_' . time() . '_' . basename($_FILES['resume']['name']);
            $targetFilePath = $uploadDir . $fileName;
            
            // Move the file to the uploads directory
            if (move_uploaded_file($_FILES['resume']['tmp_name'], $targetFilePath)) {
                $resumePath = 'uploads/resumes/' . $fileName;
            } else {
                $errorMsg = "Failed to upload the file.";
            }
        }
    } else if ($_FILES['resume']['error'] != 4) { // 4 means no file was uploaded
        $errorMsg = "Error uploading file. Error code: " . $_FILES['resume']['error'];
    } else {
        $errorMsg = "Resume is required.";
    }
    
    // Insert the job application into the database if no errors
    if (empty($errorMsg)) {
        $insertQuery = "INSERT INTO job_applications (job_id, user_id, cover_letter, resume_path) VALUES (?, ?, ?, ?)";
        $insertStmt = mysqli_prepare($conn, $insertQuery);
        
        if ($insertStmt) {
            mysqli_stmt_bind_param($insertStmt, "iiss", $jobId, $userId, $coverLetter, $resumePath);
            
            if (mysqli_stmt_execute($insertStmt)) {
                $successMsg = "Your application for '" . htmlspecialchars($jobDetails['title']) . "' has been submitted successfully.";
            } else {
                $errorMsg = "Error submitting your application: " . mysqli_error($conn);
            }
            
            mysqli_stmt_close($insertStmt);
        } else {
            $errorMsg = "Database error: " . mysqli_error($conn);
        }
    }
}

// Set page title
$pageTitle = "Apply for Job: " . (isset($jobDetails) ? htmlspecialchars($jobDetails['title']) : "");

// Include header
include_once($includePath . "header.php");
?>
<div>
<div class="row">
    <div class="col-md-8 mx-auto">
<nav>
<ol>
<li>
<a>
Dashboard

</a>
</li>
<li>
<a>
Job Listings

</a>
</li>
<li>
<a>
">Job Details

</a>
</li>
<li>
Apply

</li>
</ol>
</nav>
        <?php if (!empty($successMsg)): ?>
            <div class="alert alert-success" role="alert">
                <?php echo $successMsg; ?>
                <hr>
<p>
You can view your application status on your

<a>
dashboard

</a>
.

</p>
</div>
        <?php endif; ?>
        
        <?php if (!empty($errorMsg)): ?>
<div>
                <?php echo $errorMsg; ?>
</div>
        <?php endif; ?>
        
        <?php if (empty($successMsg)): ?>
<div>
                <div class="card-header bg-primary text-white">
<h4>
Apply for: <?php echo htmlspecialchars($jobDetails['title']); ?>

</h4>
</div>
<div>
                    <div class="mb-4">
<h5>
Job Information

</h5>
<table>
<tr>
<th>
Position:

</th>
<td>
<?php echo htmlspecialchars($jobDetails['title']); ?>
</td>
</tr>
<tr>
<th>
Company:

</th>
<td>
<?php echo htmlspecialchars($jobDetails['company_name']); ?>
</td>
</tr>
<tr>
<th>
Location:

</th>
<td>
<?php echo htmlspecialchars($jobDetails['location']); ?>
</td>
</tr>
</table>
</div>
<form>
" method="POST" enctype="multipart/form-data">

<div>
<label>
<strong>
Cover Letter

</strong>
</label>
<textarea>
<?php echo htmlspecialchars($coverLetter); ?>
</textarea>
<small>
Explain why you're a good fit for this position.

</small>
</div>
<div>
<label>
<strong>
Resume/CV

</strong>
</label>
                            <input type="file" class="form-control-file" id="resume" name="resume" required>
<small>
Upload your resume in PDF or Word format (Max 2MB).

</small>
</div>
<div>
<button>
Submit Application

</button>
<a>
" class="btn btn-outline-secondary">Cancel

</a>
</div>
</form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
</div> <?php // Close database connection 
mysqli_close($conn); // Include footer 
include_once($includePath . "footer.php"); ?>