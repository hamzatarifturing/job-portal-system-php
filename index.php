<?php
// Start the session
session_start();

// Include header
include_once 'includes/header.php';
?>

<div class="container">
    <?php 
    // Display message if present in URL
    if(isset($_GET['message'])) {
        echo '<div class="alert alert-success alert-dismissible fade show mt-3" role="alert">';
        echo htmlspecialchars($_GET['message']);
        echo '<button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>';
        echo '</div>';
    }
    ?>
    
    <div class="jumbotron text-center">
        <h1>Welcome to Job Portal</h1>
        <p>Find your dream job or hire the best talent</p>
        <div class="buttons">
            <a href="jobseeker/register.php" class="btn btn-primary btn-lg">I'm a Job Seeker</a>
            <a href="employer/register.php" class="btn btn-success btn-lg">I'm an Employer</a>
        </div>
    </div>
</div>

<?php
// Include footer
include_once 'includes/footer.php';
?>