<?php
// Start session
session_start();

// Include header and database config
include_once 'includes/header.php';
include_once 'includes/config.php';

// Compatibility for password_hash in PHP < 5.5
if (!function_exists('password_hash')) {
    /**
     * Hash the password using the specified algorithm
     *
     * @param string $password The password to hash
     * @param int $algo The algorithm to use (Defined by PASSWORD_* constants)
     * @param array $options The options for the algorithm
     * @return string|false The hashed password, or false on error
     */
    function password_hash($password, $algo, array $options = array()) {
        // Use SHA-256 with a random salt
        $salt = mcrypt_create_iv(22, MCRYPT_DEV_URANDOM);
        $salt = base64_encode($salt);
        $salt = str_replace('+', '.', $salt);
        $hash = crypt($password, '$5$rounds=5000$' . $salt . '$');
        return $hash;
    }
}

// Compatibility for password_verify in PHP < 5.5
if (!function_exists('password_verify')) {
    /**
     * Verifies that a password matches a hash
     * 
     * @param string $password The password to verify
     * @param string $hash The hash to verify against
     * @return boolean Returns TRUE if the password and hash match, or FALSE otherwise
     */
    function password_verify($password, $hash) {
        return (crypt($password, $hash) === $hash);
    }
}

// Initialize variables
$name = $email = $user_type = "";
$name_err = $email_err = $password_err = $confirm_password_err = $user_type_err = "";
$registration_success = "";
$registration_err = "";

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
        $email = trim($_POST["email"]);
        
        // Simple email validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email_err = "Please enter a valid email address.";
        } else {
            // Check if email already exists in database
            $sql = "SELECT id FROM users WHERE email = ?";
            
            if ($stmt = mysqli_prepare($conn, $sql)) {
                // Bind variables to the prepared statement as parameters
                mysqli_stmt_bind_param($stmt, "s", $param_email);
                
                // Set parameters
                $param_email = $email;
                
                // Attempt to execute the prepared statement
                if (mysqli_stmt_execute($stmt)) {
                    // Store result
                    mysqli_stmt_store_result($stmt);
                    
                    if (mysqli_stmt_num_rows($stmt) == 1) {
                        $email_err = "This email is already registered.";
                    }
                } else {
                    $registration_err = "Oops! Something went wrong. Please try again later.";
                }
                
                // Close statement
                mysqli_stmt_close($stmt);
            }
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
        
        // Begin transaction for database operations
        mysqli_begin_transaction($conn);
        
        try {
            // Prepare an insert statement for users table
            $sql = "INSERT INTO users (name, email, password, user_type) VALUES (?, ?, ?, ?)";
            
            if ($stmt = mysqli_prepare($conn, $sql)) {
                // Bind variables to the prepared statement as parameters
                mysqli_stmt_bind_param($stmt, "ssss", $param_name, $param_email, $param_password, $param_user_type);
                
                // Set parameters
                $param_name = $name;
                $param_email = $email;
                // Create a password hash
                $param_password = password_hash($password, PASSWORD_DEFAULT);
                $param_user_type = $user_type;
                
                // Attempt to execute the prepared statement
                if (mysqli_stmt_execute($stmt)) {
                    // Get the user ID of the newly created user
                    $user_id = mysqli_insert_id($conn);
                    
                    // Create appropriate profile record based on user type
                    if ($user_type == "jobseeker") {
                        // Create empty job seeker profile
                        $profile_sql = "INSERT INTO profile_jobseeker (user_id) VALUES (?)";
                    } else { // employer
                        // Create empty employer profile
                        $profile_sql = "INSERT INTO profile_employer (user_id, company_name) VALUES (?, 'Unnamed Company')";
                    }
                    
                    if ($profile_stmt = mysqli_prepare($conn, $profile_sql)) {
                        // Bind variables to the prepared statement as parameters
                        mysqli_stmt_bind_param($profile_stmt, "i", $user_id);
                        
                        // Attempt to execute the prepared statement
                        if (mysqli_stmt_execute($profile_stmt)) {
                            // Commit the transaction
                            if (function_exists('mysqli_commit')) {
                                mysqli_commit($conn);
                            } else {
                                mysqli_query($conn, "COMMIT");
                            }
                            $registration_success = "Registration successful! You can now login.";
                            
                            // Clear form data after successful submission
                            $name = $email = $user_type = "";
                        } else {
                            // Rollback if profile creation fails
                            if (function_exists('mysqli_rollback')) {
                                mysqli_rollback($conn);
                            } else {
                                mysqli_query($conn, "ROLLBACK");
                            }
                            $registration_err = "Something went wrong creating your profile. Please try again.";
                        }
                        
                        // Close profile statement
                        mysqli_stmt_close($profile_stmt);
                    }
                } else {
                    // Rollback if user creation fails
                    if (function_exists('mysqli_rollback')) {
                        mysqli_rollback($conn);
                    } else {
                        mysqli_query($conn, "ROLLBACK");
                    }
                    $registration_err = "Something went wrong. Please try again.";
                }
                
                // Close user statement
                mysqli_stmt_close($stmt);
            }
        } catch (Exception $e) {
            // Rollback on exception
            if (function_exists('mysqli_rollback')) {
                mysqli_rollback($conn);
            } else {
                mysqli_query($conn, "ROLLBACK");
            }
            $registration_err = "Database error: " . $e->getMessage();
        }
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
        
        <?php if (!empty($registration_err)) : ?>
            <div class="alert alert-danger">
                <?php echo $registration_err; ?>
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
                <select name="user_type" class="form-control <?php echo (!empty($user_type_err)) ? 'is-invalid' : ''; ?>">
                    <option value="">-- Select User Type --</option>
                    <option value="jobseeker" <?php echo ($user_type == "jobseeker") ? "selected" : ""; ?>>Job Seeker</option>
                    <option value="employer" <?php echo ($user_type == "employer") ? "selected" : ""; ?>>Employer</option>
                </select>
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