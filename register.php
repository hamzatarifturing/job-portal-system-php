<?php
// Start session
session_start();

// Include header
include_once 'includes/header.php';

// Initialize variables
$name = $email = $user_type = "";
$name_err = $email_err = $password_err = $confirm_password_err = $user_type_err = "";
$registration_success = "";

// Process form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validate name
    if (empty(trim($_POST["name"]))) {
        $name_err = "Please enter your name.";
    } else {
        $name = trim($_POST["name"]);
    }
    
    // Validate email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter your email.";
    } else {
        // Prepare a select statement to check if email already exists
        // This will be implemented later when connected to database
        $email = trim($_POST["email"]);
        
        // Simple email validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email_err = "Please enter a valid email address.";
        }
    }
    
    // Validate user type
    if (empty($_POST["user_type"])) {
        $user_type_err = "Please select user type.";
    } else {
        $user_type = $_POST["user_type"];
    }
    
    // Validate password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter a password.";     
    } elseif (strlen(trim($_POST["password"])) < 6) {
        $password_err = "Password must have at least 6 characters.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    // Validate confirm password
    if (empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "Please confirm password.";     
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if (empty($password_err) && ($password != $confirm_password)) {
            $confirm_password_err = "Password did not match.";
        }
    }
    
    // Check input errors before inserting in database
    if (empty($name_err) && empty($email_err) && empty($password_err) && empty($confirm_password_err) && empty($user_type_err)) {
        // This is where we would insert into the database
        // For now, let's just show a success message
        $registration_success = "Registration successful! You can now login.";
        
        // Clear form data after successful submission
        $name = $email = $user_type = "";
    }
}
?>

<div class="container">
    <div class="register-section">
        <h1>Register for Job Portal</h1>
        
        <?php if (!empty($registration_success)) : ?>
            <div class="alert alert-success">
                <?php echo $registration_success; ?>
            </div>
        <?php endif; ?>
        
        <p>Please fill this form to create an account.</p>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="registration-form">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" class="form-control <?php echo (!empty($name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($name); ?>">
                <span class="invalid-feedback"><?php echo $name_err; ?></span>
            </div>    
            
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($email); ?>">
                <span class="invalid-feedback"><?php echo $email_err; ?></span>
            </div>
            
            <div class="form-group">
                <label>User Type</label>
                <div class="radio-group">
                    <label class="radio-inline">
                        <input type="radio" name="user_type" value="jobseeker" <?php echo ($user_type == "jobseeker") ? "checked" : ""; ?>> Job Seeker
                    </label>
                    <label class="radio-inline">
                        <input type="radio" name="user_type" value="employer" <?php echo ($user_type == "employer") ? "checked" : ""; ?>> Employer
                    </label>
                </div>
                <span class="invalid-feedback"><?php echo $user_type_err; ?></span>
            </div>
            
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                <span class="invalid-feedback"><?php echo $password_err; ?></span>
            </div>
            
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>">
                <span class="invalid-feedback"><?php echo $confirm_password_err; ?></span>
            </div>
            
            <div class="form-group">
                <input type="submit" class="btn-submit" value="Register">
            </div>
            
            <p>Already have an account? <a href="#">Login here</a>.</p>
        </form>
    </div>
</div>

<?php
// Include footer
include_once 'includes/footer.php';
?>