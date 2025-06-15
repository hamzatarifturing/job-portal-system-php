<?php
        // Start the session
        session_start();

        /**
         * close_job.php - Deletes a job posting from the database
         * 
         * This script handles the deletion of job postings from the database.
         * It verifies that:
         * 1. The user is logged in and is an employer
         * 2. A valid job ID is provided in the URL
         * 3. The job belongs to the current employer before deleting
         * 
         * After successful deletion, redirects back to post_job.php with a success message.
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
            header("Location: post_job.php?error=invalid_job_id");
            exit();
        }

        // Sanitize the job ID
        $jobId = intval($_GET['id']);

        // Verify that the job belongs to the current employer
        $query = "SELECT id, title FROM job_postings WHERE id = ? AND user_id = ?";
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
                    header("Location: post_job.php?error=unauthorized_job");
                    exit();
                }
                
                // Get the job title for the success message
                mysqli_stmt_bind_result($stmt, $id, $jobTitle);
                mysqli_stmt_fetch($stmt);
                mysqli_stmt_close($stmt);
                
                // Delete the job posting
                $deleteQuery = "DELETE FROM job_postings WHERE id = ?";
                $deleteStmt = mysqli_prepare($conn, $deleteQuery);
                
                if($deleteStmt) {
                    // Bind parameter
                    mysqli_stmt_bind_param($deleteStmt, "i", $jobId);
                    
                    // Execute the deletion
                    if(mysqli_stmt_execute($deleteStmt)) {
                        // Job deleted successfully
                        $successMsg = "Job \"" . htmlspecialchars($jobTitle) . "\" has been deleted successfully.";
                        header("Location: post_job.php?success=" . urlencode($successMsg));
                    } else {
                        // Error deleting job
                        $errorMsg = "Error deleting job: " . mysqli_error($conn);
                        header("Location: post_job.php?error=" . urlencode($errorMsg));
                    }
                    
                    // Close the delete statement
                    mysqli_stmt_close($deleteStmt);
                } else {
                    // Error preparing delete statement
                    $errorMsg = "Database error: " . mysqli_error($conn);
                    header("Location: post_job.php?error=" . urlencode($errorMsg));
                }
            } else {
                // Error executing the select query
                $errorMsg = "Database error: " . mysqli_error($conn);
                header("Location: post_job.php?error=" . urlencode($errorMsg));
                mysqli_stmt_close($stmt);
            }
        } else {
            // Error preparing the select statement
            $errorMsg = "Database error: " . mysqli_error($conn);
            header("Location: post_job.php?error=" . urlencode($errorMsg));
        }

        // Close database connection
        mysqli_close($conn);
        ?>