<?php
// Start the session
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Set page title
$pageTitle = "Login - Job Portal";

// Include header
include_once 'includes/header.php';

// Initialize variables
$email = "";
$error_message = "";
$success_message = "";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get and sanitize input data
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = mysqli_real_escape_string($conn, trim($_POST['password']));
    
    // Validate input
    if (empty($email)) {
        $error_message = "Email is required";
    } else if (empty($password)) {
        $error_message = "Password is required";
    } else {
        // Check if email exists
        $query = "SELECT * FROM users WHERE email = '$email'";
        $result = mysqli_query($conn, $query);
        
        if (mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Check if account is active
                if ($user['status'] == 'active') {
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_type'] = $user['user_type'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['first_name'] = $user['first_name'];
                    $_SESSION['last_name'] = $user['last_name'];
                    
                    // Update last login time
                    $update_query = "UPDATE users SET last_login = NOW() WHERE id = " . $user['id'];
                    mysqli_query($conn, $update_query);
                    
                    // Redirect based on user type
                    if ($user['user_type'] == 'jobseeker') {
                        header("Location: jobseeker/dashboard.php");
                    } else {
                        header("Location: employer/dashboard.php");
                    }
                    exit();
                } else if ($user['status'] == 'inactive') {
                    $error_message = "Your account is not verified. Please check your email for verification link.";
                } else {
                    $error_message = "Your account has been suspended. Please contact admin.";
                }
            } else {
                $error_message = "Invalid email or password";
            }
        } else {
            $error_message = "Invalid email or password";
        }
    }
}
?>

<div class="container">
    <div class="row">
        <div class="col-md-6 offset-md-3">
            <div class="content-container">
                <h2 class="text-center mb-4">Login to Your Account</h2>
                
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success">
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>
                
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="needs-validation" novalidate>
                    <div class="form-group">
                        <label for="email">Email Address:</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo $email; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password:</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    
                    <div class="form-group form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>
                    
                    <div class="form-group text-center">
                        <button type="submit" class="btn btn-primary btn-lg">Login</button>
                    </div>
                    
                    <div class="text-center mt-3">
                        <p><a href="forgot_password.php">Forgot Password?</a></p>
                        <p>Don't have an account? <a href="register.php">Register Now</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Join Job Portal Today</h5>
                    <p class="card-text">Whether you're looking for your dream job or seeking to hire talented professionals, Job Portal connects you with the right opportunities.</p>
                    <div class="text-center">
                        <a href="register.php" class="btn btn-outline-success">Create an Account</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Simple form validation
document.addEventListener('DOMContentLoaded', function() {
    var forms = document.querySelectorAll('.needs-validation');
    
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        }, false);
    });
    
    // If there was a redirect with a message, show it and then remove it from URL
    const urlParams = new URLSearchParams(window.location.search);
    const message = urlParams.get('message');
    
    if (message) {
        // Create alert with message from URL
        var alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-info';
        alertDiv.textContent = decodeURIComponent(message);
        
        // Insert it at the beginning of the form
        var form = document.querySelector('form');
        form.parentNode.insertBefore(alertDiv, form);
        
        // Remove the parameter from URL without refreshing
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});
</script>

<?php
// Include footer
include_once 'includes/footer.php';
?>