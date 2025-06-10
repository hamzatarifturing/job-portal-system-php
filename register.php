<?php
// Start the session
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Set page title
$pageTitle = "Register - Job Portal";

// Include header
include_once 'includes/header.php';

// Initialize variables
$username = $email = $first_name = $last_name = $user_type = "";
$errors = array();

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get and sanitize input data
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $first_name = mysqli_real_escape_string($conn, trim($_POST['first_name']));
    $last_name = mysqli_real_escape_string($conn, trim($_POST['last_name']));
    $password = mysqli_real_escape_string($conn, trim($_POST['password']));
    $confirm_password = mysqli_real_escape_string($conn, trim($_POST['confirm_password']));
    $user_type = mysqli_real_escape_string($conn, trim($_POST['user_type']));
    
    // Optional fields
    $phone = isset($_POST['phone']) ? mysqli_real_escape_string($conn, trim($_POST['phone'])) : null;
    $company_name = isset($_POST['company_name']) ? mysqli_real_escape_string($conn, trim($_POST['company_name'])) : null;
    
    // Form validation
    if (empty($username)) {
        $errors[] = "Username is required";
    } else {
        // Check if username exists
        $check_username = mysqli_query($conn, "SELECT * FROM users WHERE username = '$username'");
        if (mysqli_num_rows($check_username) > 0) {
            $errors[] = "Username already exists, please choose another";
        }
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address";
    } else {
        // Check if email exists
        $check_email = mysqli_query($conn, "SELECT * FROM users WHERE email = '$email'");
        if (mysqli_num_rows($check_email) > 0) {
            $errors[] = "Email already exists, please use another email";
        }
    }
    
    if (empty($first_name)) {
        $errors[] = "First name is required";
    }
    
    if (empty($last_name)) {
        $errors[] = "Last name is required";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } else if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long";
    }
    
    if ($password != $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    if (empty($user_type)) {
        $errors[] = "Please select your user type";
    }
    
    if ($user_type == 'employer' && empty($company_name)) {
        $errors[] = "Company name is required for employers";
    }
    
    // If no errors, proceed with registration
    if (empty($errors)) {
        // Generate verification token
        $verification_token = md5(uniqid(rand(), true));
        
        // Hash password (using PHP 5 compatible approach)
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Prepare the SQL query based on user type
        if ($user_type == 'employer') {
            $query = "INSERT INTO users (username, email, password, user_type, first_name, last_name, 
                      phone, company_name, verification_token, status) 
                      VALUES ('$username', '$email', '$hashed_password', '$user_type', '$first_name', 
                      '$last_name', '$phone', '$company_name', '$verification_token', 'inactive')";
        } else {
            $query = "INSERT INTO users (username, email, password, user_type, first_name, last_name, 
                      phone, verification_token, status) 
                      VALUES ('$username', '$email', '$hashed_password', '$user_type', '$first_name', 
                      '$last_name', '$phone', '$verification_token', 'inactive')";
        }
        
        // Execute query
        if (mysqli_query($conn, $query)) {
            // Registration successful
            $success_message = "Registration successful! Please verify your email to activate your account.";
            
            // TODO: Send verification email
            // This would be implemented in a production environment
            
            // Reset form fields after successful submission
            $username = $email = $first_name = $last_name = $user_type = "";
        } else {
            $errors[] = "Error: " . $query . "<br>" . mysqli_error($conn);
        }
    }
}
?>

<div class="container">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="content-container">
                <h2 class="text-center mb-4">Create an Account</h2>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success">
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>
                
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="needs-validation" novalidate>
                    <div class="form-group">
                        <label for="user_type">I am a:</label>
                        <select class="form-control" id="user_type" name="user_type" required onchange="toggleCompanyField()">
                            <option value="">Select your role</option>
                            <option value="jobseeker" <?php if ($user_type == 'jobseeker') echo 'selected'; ?>>Job Seeker</option>
                            <option value="employer" <?php if ($user_type == 'employer') echo 'selected'; ?>>Employer</option>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="first_name">First Name:</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo $first_name; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="last_name">Last Name:</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo $last_name; ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="username">Username:</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo $username; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address:</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo $email; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number:</label>
                        <input type="text" class="form-control" id="phone" name="phone" placeholder="(Optional)">
                    </div>
                    
                    <div id="companyField" class="form-group" style="display: none;">
                        <label for="company_name">Company Name:</label>
                        <input type="text" class="form-control" id="company_name" name="company_name">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="password">Password:</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <small class="form-text text-muted">Password must be at least 6 characters long</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="confirm_password">Confirm Password:</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                            <label class="form-check-label" for="terms">
                                I agree to the <a href="#" data-toggle="modal" data-target="#termsModal">Terms and Conditions</a>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group text-center">
                        <button type="submit" class="btn btn-primary btn-lg">Register</button>
                    </div>
                    
                    <div class="text-center">
                        <p>Already have an account? <a href="login.php">Login here</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Terms and Conditions Modal -->
<div class="modal fade" id="termsModal" tabindex="-1" role="dialog" aria-labelledby="termsModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="termsModalLabel">Terms and Conditions</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <h5>User Agreement</h5>
                <p>By using our Job Portal, you agree to the following terms and conditions:</p>
                <ul>
                    <li>All information provided during registration must be accurate and truthful.</li>
                    <li>You are responsible for maintaining the confidentiality of your account information.</li>
                    <li>Job seekers agree to use the platform for legitimate job searches.</li>
                    <li>Employers agree to post genuine job opportunities.</li>
                    <li>Misuse of the platform may result in account suspension or termination.</li>
                    <li>We reserve the right to modify these terms at any time.</li>
                </ul>
                <h5>Privacy Policy</h5>
                <p>Your privacy is important to us. We collect personal information solely for the purpose of facilitating job matching and improving our services.</p>
                <p>For more information, please refer to our full Privacy Policy.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function toggleCompanyField() {
    var userType = document.getElementById('user_type').value;
    var companyField = document.getElementById('companyField');
    
    if (userType == 'employer') {
        companyField.style.display = 'block';
        document.getElementById('company_name').setAttribute('required', '');
    } else {
        companyField.style.display = 'none';
        document.getElementById('company_name').removeAttribute('required');
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleCompanyField();
});
</script>

<?php
// Include footer
include_once 'includes/footer.php';
?>