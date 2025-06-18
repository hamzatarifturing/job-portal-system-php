<?php
// Start the session
session_start();

/**
 * publish_job.php - Publishes a draft job posting
 * 
 * This script handles the publishing of draft job postings.
 * It verifies that:
 * 1. The user is logged in and is an employer
 * 2. A valid job ID is provided in the URL
 * 3. The job belongs to the current employer
 * 4. The job is currently in draft state
 * 
 * After successful update, redirects back to the dashboard with a success message.
 */

// Check if user is logged in and is an employer
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'employer') {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

// Set include path for database configuration
$includePath = "../includes/";

// Include database configuration
include_once($includePath . "db_config.php");

// Get employer ID from session
$employerId = $_SESSION['user_id'];

// Check if job ID is provided in the URL parameters
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: dashboard.php?error=invalid_job_id");
    exit();
}

// Sanitize the job ID
$jobId = intval($_GET['id']);

// Verify that the job belongs to the current employer and is in draft state
$query = "SELECT id, title, status FROM job_postings WHERE id = ? AND user_id = ?";
$stmt = mysqli_prepare($conn, $query);

if($stmt) {
    // Bind parameters
    mysqli_stmt_bind_param($stmt, "ii", $jobId, $employerId);
    
    // Execute the query
    if(mysqli_stmt_execute($stmt)) {
        // Store result
        mysqli_stmt_store_result($stmt);
        
        // If no rows found, job doesn't exist or doesn't belong to this employer
        if(mysqli_stmt_num_rows($stmt) == 0) {
            mysqli_stmt_close($stmt);
            header("Location: dashboard.php?error=unauthorized_job");
            exit();
        }
        
        // Get the job title and status
        mysqli_stmt_bind_result($stmt, $id, $jobTitle, $jobStatus);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
        
        // Verify that the job is in draft state
        if($jobStatus != 'Draft') {
            header("Location: dashboard.php?error=" . urlencode("Only draft jobs can be published. This job is already " . strtolower($jobStatus) . "."));
            exit();
        }
        
        // Update the job status to Published
        $updateQuery = "UPDATE job_postings SET status = 'Published', updated_at = NOW() WHERE id = ?";
        $updateStmt = mysqli_prepare($conn, $updateQuery);
        
        if($updateStmt) {
            // Bind parameter
            mysqli_stmt_bind_param($updateStmt, "i", $jobId);
            
            // Execute the update
            if(mysqli_stmt_execute($updateStmt)) {
                // Job published successfully
                $successMsg = "Job \"" . htmlspecialchars($jobTitle) . "\" has been published successfully!";
                header("Location: dashboard.php?success=" . urlencode($successMsg));
            } else {
                // Error updating job
                $errorMsg = "Error publishing job: " . mysqli_error($conn);
                header("Location: dashboard.php?error=" . urlencode($errorMsg));
            }
            
            // Close the update statement
            mysqli_stmt_close($updateStmt);
        } else {
            // Error preparing update statement
            $errorMsg = "Database error: " . mysqli_error($conn);
            header("Location: dashboard.php?error=" . urlencode($errorMsg));
        }
    } else {
        // Error executing the select query
        $errorMsg = "Database error: " . mysqli_error($conn);
        header("Location: dashboard.php?error=" . urlencode($errorMsg));
        mysqli_stmt_close($stmt);
    }
} else {
    // Error preparing the select statement
    $errorMsg = "Database error: " . mysqli_error($conn);
    header("Location: dashboard.php?error=" . urlencode($errorMsg));
}

// Close database connection
mysqli_close($conn);
?>