<?php
// Start the session
session_start();

/**
 * withdraw_application.php - Handles job application withdrawal for job seekers
 * 
 * This script:
 * 1. Verifies the user is logged in as a jobseeker
 * 2. Validates the application ID from the URL parameter
 * 3. Checks if the application belongs to the current user
 * 4. Deletes the application from the database
 * 5. Redirects back to the dashboard with a success message
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

// Check if application ID is provided in the URL parameters
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: dashboard.php?error=invalid_application");
    exit();
}

// Sanitize the application ID
$applicationId = intval($_GET['id']);

// First, check if the application exists and belongs to the current user
$checkQuery = "SELECT ja.id, ja.status, jp.title, jp.company_name
               FROM job_applications ja
               JOIN job_postings jp ON ja.job_id = jp.id
               WHERE ja.id = ? AND ja.user_id = ?";

$checkStmt = mysqli_prepare($conn, $checkQuery);

if(!$checkStmt) {
    // Database error
    header("Location: dashboard.php?error=database_error");
    exit();
}

// Bind parameters
mysqli_stmt_bind_param($checkStmt, "ii", $applicationId, $userId);

// Execute the query
if(!mysqli_stmt_execute($checkStmt)) {
    mysqli_stmt_close($checkStmt);
    header("Location: dashboard.php?error=database_error");
    exit();
}

$result = mysqli_stmt_get_result($checkStmt);

// Check if application exists and belongs to this user
if(mysqli_num_rows($result) == 0) {
    mysqli_stmt_close($checkStmt);
    header("Location: dashboard.php?error=application_not_found");
    exit();
}

// Get application details for success message
$applicationData = mysqli_fetch_assoc($result);
mysqli_stmt_close($checkStmt);

// Check if application status is not 'pending'
if($applicationData['status'] != 'pending') {
    header("Location: dashboard.php?error=cannot_withdraw&message=" . urlencode("Only pending applications can be withdrawn."));
    exit();
}

// Delete the application
$deleteQuery = "DELETE FROM job_applications WHERE id = ? AND user_id = ?";
$deleteStmt = mysqli_prepare($conn, $deleteQuery);

if(!$deleteStmt) {
    header("Location: dashboard.php?error=database_error");
    exit();
}

// Bind parameters
mysqli_stmt_bind_param($deleteStmt, "ii", $applicationId, $userId);

// Execute the delete query
if(mysqli_stmt_execute($deleteStmt)) {
    // Success
    $jobTitle = htmlspecialchars($applicationData['title']);
    $companyName = htmlspecialchars($applicationData['company_name']);
    mysqli_stmt_close($deleteStmt);
    mysqli_close($conn);
    
    // Redirect with success message
    $successMessage = "Your application for '$jobTitle' at '$companyName' has been withdrawn successfully.";
    header("Location: dashboard.php?success=" . urlencode($successMessage));
    exit();
} else {
    // Error
    $errorMessage = "Error withdrawing application: " . mysqli_error($conn);
    mysqli_stmt_close($deleteStmt);
    mysqli_close($conn);
    header("Location: dashboard.php?error=" . urlencode($errorMessage));
    exit();
}
?>