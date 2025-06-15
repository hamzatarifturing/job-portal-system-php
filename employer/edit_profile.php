<?php
// Start the session
session_start();

// Check if user is logged in and is an employer
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'employer') {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

// Set include path for header/footer
$includePath = "../includes/";
$pageTitle = "Edit Profile | Job Portal";

// Include header
include_once($includePath . "header.php");

// Get user ID from session
$userId = $_SESSION['user_id'];

// Initialize variables for form fields
$firstName = $lastName = $username = $email = $phone = $address = $city = $state = $zipCode = $country = '';
$message = '';
$error = '';

// If form is submitted, update the profile
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $firstName = trim($_POST['firstName']);
    $lastName = trim($_POST['lastName']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $state = trim($_POST['state']);
    $zipCode = trim($_POST['zipCode']);
    $country = trim($_POST['country']);
    
    // Validate required fields
    if (empty($firstName) || empty($lastName) || empty($email)) {
        $error = "First name, last name, and email are required fields.";
    } else {
        // Check if updating password
        $updatePassword = false;
        $passwordQuery = "";
        
        if (!empty($_POST['newPassword']) && !empty($_POST['confirmPassword'])) {
            if ($_POST['newPassword'] === $_POST['confirmPassword']) {
                $hashedPassword = password_hash($_POST['newPassword'], PASSWORD_DEFAULT);
                $passwordQuery = ", password = '$hashedPassword'";
                $updatePassword = true;
            } else {
                $error = "New passwords don't match.";
            }
        }

        // If no errors, update the profile
        if (empty($error)) {
            // Update user table
            $query = "UPDATE users SET 
                      first_name = '$firstName', 
                      last_name = '$lastName', 
                      username = '$username', 
                      email = '$email', 
                      phone = '$phone', 
                      address = '$address', 
                      city = '$city', 
                      state = '$state', 
                      zip_code = '$zipCode', 
                      country = '$country'
                      $passwordQuery
                      WHERE id = $userId";
                      
            if (mysqli_query($conn, $query)) {
                $message = "Profile updated successfully!";
                
                // Update session variables
                $_SESSION['username'] = $username;
            } else {
                $error = "Error updating profile: " . mysqli_error($conn);
            }
        }
    }
}

// Get current user data
$query = "SELECT * FROM users WHERE id = $userId";
$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    $userData = mysqli_fetch_assoc($result);
    
    // Populate form fields with current data if not set from form submission
    if ($_SERVER['REQUEST_METHOD'] != 'POST') {
        $firstName = $userData['first_name'];
        $lastName = $userData['last_name'];
        $username = $userData['username'];
        $email = $userData['email'];
        $phone = isset($userData['phone']) ? $userData['phone'] : '';
        $address = isset($userData['address']) ? $userData['address'] : '';
        $city = isset($userData['city']) ? $userData['city'] : '';
        $state = isset($userData['state']) ? $userData['state'] : '';
        $zipCode = isset($userData['zip_code']) ? $userData['zip_code'] : '';
        $country = isset($userData['country']) ? $userData['country'] : '';
    }
} else {
    $error = "Error retrieving user data.";
}
?>

<div class="container">
    <div class="row">
        <div class="col-md-3">
            <!-- Sidebar menu for employer pages -->
            <div class="card mb-4">
                <div class="card-header bg-dark text-white">
                    Employer Menu
                </div>
                <div class="list-group list-group-flush">
                    <a href="dashboard.php" class="list-group-item list-group-item-action">Dashboard</a>
                    <a href="post_job.php" class="list-group-item list-group-item-action">Post a Job</a>
                    <a href="job_listings.php" class="list-group-item list-group-item-action">My Job Listings</a>
                    <a href="applications.php" class="list-group-item list-group-item-action">Job Applications</a>
                    <a href="company_profile.php" class="list-group-item list-group-item-action">Company Profile</a>
                    <a href="edit_profile.php" class="list-group-item list-group-item-action active">Edit My Profile</a>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="m-0">Edit Profile</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $message; ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $error; ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <form action="edit_profile.php" method="post" class="needs-validation" novalidate>
                        <h5 class="mb-3">Personal Information</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="firstName">First Name*</label>
                                    <input type="text" class="form-control" id="firstName" name="firstName" value="<?php echo htmlspecialchars($firstName); ?>" required>
                                    <div class="invalid-feedback">First name is required.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="lastName">Last Name*</label>
                                    <input type="text" class="form-control" id="lastName" name="lastName" value="<?php echo htmlspecialchars($lastName); ?>" required>
                                    <div class="invalid-feedback">Last name is required.</div>
                                </div>
                            </div>
                        </div>

                        <h5 class="mt-4 mb-3">Account Information</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="username">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                                    <div class="invalid-feedback">Username is required.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email">Email*</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                                    <div class="invalid-feedback">Valid email is required.</div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="newPassword">New Password</label>
                                    <input type="password" class="form-control" id="newPassword" name="newPassword" placeholder="Leave blank to keep current password">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="confirmPassword">Confirm Password</label>
                                    <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" placeholder="Confirm new password">
                                </div>
                            </div>
                        </div>

                        <h5 class="mt-4 mb-3">Contact Information</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="phone">Phone</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="country">Country</label>
                                    <input type="text" class="form-control" id="country" name="country" value="<?php echo htmlspecialchars($country); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="address">Address</label>
                            <input type="text" class="form-control" id="address" name="address" value="<?php echo htmlspecialchars($address); ?>">
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="city">City</label>
                                    <input type="text" class="form-control" id="city" name="city" value="<?php echo htmlspecialchars($city); ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="state">State/Province</label>
                                    <input type="text" class="form-control" id="state" name="state" value="<?php echo htmlspecialchars($state); ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="zipCode">ZIP/Postal Code</label>
                                    <input type="text" class="form-control" id="zipCode" name="zipCode" value="<?php echo htmlspecialchars($zipCode); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 text-center">
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                            <a href="dashboard.php" class="btn btn-secondary ml-2">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once($includePath . "footer.php");
?>