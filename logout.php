<?php
// Start session if not already started
session_start();

// Check if user is logged in
if(isset($_SESSION['user_id'])) {
    // Store username for the logout message
    $username = isset($_SESSION['username']) ? $_SESSION['username'] : '';
    
    // Unset all session variables
    $_SESSION = array();
    
    // If a session cookie is used, destroy it
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session itself
    session_destroy();
    
    // Redirect to home page with logout message
    header("Location: index.php?message=".urlencode("You have been successfully logged out" . ($username ? ", $username" : "") . "."));
} else {
    // If not logged in, just redirect to home
    header("Location: index.php");
}
exit();
?>