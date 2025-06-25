<?php
/**
 * notifications_display.php
 * This file handles retrieving and displaying notifications for a user
 * It can be included in any page where user notifications should be shown
 */

// Ensure session is started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Function to get unread notifications for a user
function getUnreadNotifications($userId) {
    global $conn; // Assuming $conn is your database connection
    
    $sql = "SELECT * FROM notifications 
            WHERE user_id = ? 
            AND is_read = 0 
            ORDER BY created_at DESC 
            LIMIT 10";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
    
    return $notifications;
}

// Function to mark a notification as read
function markNotificationAsRead($notificationId) {
    global $conn;
    
    $sql = "UPDATE notifications 
            SET is_read = 1, read_at = NOW() 
            WHERE notification_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $notificationId);
    
    return $stmt->execute();
}

// Function to mark all notifications as read for a user
function markAllNotificationsAsRead($userId) {
    global $conn;
    
    $sql = "UPDATE notifications 
            SET is_read = 1, read_at = NOW() 
            WHERE user_id = ? AND is_read = 0";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    
    return $stmt->execute();
}

// Function to count unread notifications
function countUnreadNotifications($userId) {
    global $conn;
    
    $sql = "SELECT COUNT(*) as count FROM notifications 
            WHERE user_id = ? AND is_read = 0";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['count'];
}

// Function to get notification type icon
function getNotificationIcon($type) {
    switch($type) {
        case 'job_application':
            return 'fa-file-text-o';
        case 'application_status':
            return 'fa-check-circle-o';
        case 'job_posting':
            return 'fa-briefcase';
        case 'message':
            return 'fa-envelope-o';
        case 'system':
            return 'fa-exclamation-circle';
        default:
            return 'fa-bell-o';
    }
}

// Get notification badge HTML
function getNotificationBadge($userId) {
    $count = countUnreadNotifications($userId);
    $badgeHtml = '';
    
    if ($count > 0) {
        $badgeHtml = '<span class="notification-badge">' . $count . '</span>';
    }
    
    return $badgeHtml;
}

// Only continue if user is logged in
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    
    // Handle AJAX request for marking notification as read
    if (isset($_POST['mark_read']) && isset($_POST['notification_id'])) {
        $notificationId = $_POST['notification_id'];
        $success = markNotificationAsRead($notificationId);
        
        if ($success) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to mark notification as read']);
        }
        exit;
    }
    
    // Handle AJAX request for marking all notifications as read
    if (isset($_POST['mark_all_read'])) {
        $success = markAllNotificationsAsRead($userId);
        
        if ($success) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to mark all notifications as read']);
        }
        exit;
    }
    
    // Get unread notifications
    $unreadNotifications = getUnreadNotifications($userId);
    $notificationCount = count($unreadNotifications);
}
?>

<!-- Notification Bell Icon with Badge -->
<div class="notification-bell-container">
    <a href="javascript:void(0)" id="notification-bell">
        <i class="fa fa-bell"></i>
        <?php if (isset($userId)) echo getNotificationBadge($userId); ?>
    </a>
    
    <!-- Notification Dropdown -->
    <div id="notification-dropdown" class="notification-dropdown">
        <div class="notification-header">
            <h3>Notifications</h3>
            <?php if (isset($notificationCount) && $notificationCount > 0): ?>
                <a href="javascript:void(0)" id="mark-all-read">Mark all as read</a>
            <?php endif; ?>
        </div>
        
        <div class="notification-list">
            <?php if (isset($unreadNotifications) && !empty($unreadNotifications)): ?>
                <?php foreach ($unreadNotifications as $notification): ?>
                    <div class="notification-item" data-id="<?php echo $notification['notification_id']; ?>">
                        <div class="notification-icon">
                            <i class="fa <?php echo getNotificationIcon($notification['type']); ?>"></i>
                        </div>
                        <div class="notification-content">
                            <div class="notification-title"><?php echo htmlspecialchars($notification['title']); ?></div>
                            <div class="notification-message"><?php echo htmlspecialchars($notification['message']); ?></div>
                            <div class="notification-time">
                                <?php echo date('M d, Y g:i A', strtotime($notification['created_at'])); ?>
                            </div>
                        </div>
                        <div class="notification-close">
                            <i class="fa fa-times mark-read" data-id="<?php echo $notification['notification_id']; ?>"></i>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-notifications">
                    <p>No new notifications</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="notification-footer">
            <a href="all_notifications.php">View All</a>
        </div>
    </div>
</div>

<style>
/* Notification Styles */
.notification-bell-container {
    position: relative;
    display: inline-block;
    margin-right: 20px;
}

#notification-bell {
    font-size: 18px;
    color: #555;
    position: relative;
    cursor: pointer;
    padding: 5px;
}

.notification-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background-color: #f44336;
    color: white;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    font-size: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.notification-dropdown {
    position: absolute;
    right: 0;
    top: 40px;
    background-color: #fff;
    border-radius: 5px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
    width: 350px;
    z-index: 1000;
    display: none;
    overflow: hidden;
}

.notification-header {
    padding: 15px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.notification-header h3 {
    margin: 0;
    font-size: 16px;
    color: #333;
}

.notification-header a {
    color: #3498db;
    text-decoration: none;
    font-size: 12px;
}

.notification-list {
    max-height: 350px;
    overflow-y: auto;
}

.notification-item {
    padding: 12px 15px;
    border-bottom: 1px solid #f1f1f1;
    display: flex;
    background-color: #f8f9fa;
    transition: background-color 0.2s ease;
}

.notification-item:hover {
    background-color: #f0f0f0;
}

.notification-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background-color: #e1e1e1;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
}

.notification-icon i {
    color: #555;
}

.notification-content {
    flex: 1;
}

.notification-title {
    font-weight: 600;
    color: #333;
    margin-bottom: 3px;
}

.notification-message {
    font-size: 13px;
    color: #666;
    margin-bottom: 5px;
    line-height: 1.3;
}

.notification-time {
    font-size: 11px;
    color: #999;
}

.notification-close {
    align-self: flex-start;
    opacity: 0.6;
    cursor: pointer;
    padding: 3px;
}

.notification-close:hover {
    opacity: 1;
}

.no-notifications {
    padding: 25px;
    text-align: center;
    color: #999;
}

.notification-footer {
    padding: 10px;
    text-align: center;
    border-top: 1px solid #eee;
}

.notification-footer a {
    color: #3498db;
    text-decoration: none;
    font-size: 14px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle notification dropdown
    const notificationBell = document.getElementById('notification-bell');
    const notificationDropdown = document.getElementById('notification-dropdown');
    
    if (notificationBell) {
        notificationBell.addEventListener('click', function(e) {
            e.stopPropagation();
            notificationDropdown.style.display = notificationDropdown.style.display === 'block' ? 'none' : 'block';
        });
    }
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (notificationDropdown && notificationDropdown.style.display === 'block' && 
            !notificationDropdown.contains(e.target) && e.target !== notificationBell) {
            notificationDropdown.style.display = 'none';
        }
    });
    
    // Mark individual notification as read
    const markReadButtons = document.querySelectorAll('.mark-read');
    markReadButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            const notificationId = this.getAttribute('data-id');
            const notificationItem = document.querySelector(`.notification-item[data-id="${notificationId}"]`);
            
            // AJAX request to mark as read
            const xhr = new XMLHttpRequest();
            xhr.open('POST', window.location.href, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        // Remove notification from list
                        if (notificationItem) {
                            notificationItem.remove();
                        }
                        
                        // Update notification count
                        updateNotificationBadge();
                        
                        // Show "No notifications" if all are gone
                        const remainingItems = document.querySelectorAll('.notification-item');
                        if (remainingItems.length === 0) {
                            const noNotifications = document.createElement('div');
                            noNotifications.className = 'no-notifications';
                            noNotifications.innerHTML = '<p>No new notifications</p>';
                            document.querySelector('.notification-list').appendChild(noNotifications);
                            
                            // Hide "Mark all as read" link
                            const markAllReadLink = document.getElementById('mark-all-read');
                            if (markAllReadLink) {
                                markAllReadLink.style.display = 'none';
                            }
                        }
                    }
                }
            };
            xhr.send(`mark_read=true&notification_id=${notificationId}`);
        });
    });
    
    // Mark all notifications as read
    const markAllReadButton = document.getElementById('mark-all-read');
    if (markAllReadButton) {
        markAllReadButton.addEventListener('click', function(e) {
            e.preventDefault();
            
            // AJAX request to mark all as read
            const xhr = new XMLHttpRequest();
            xhr.open('POST', window.location.href, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        // Clear notifications list
                        const notificationList = document.querySelector('.notification-list');
                        if (notificationList) {
                            notificationList.innerHTML = '<div class="no-notifications"><p>No new notifications</p></div>';
                        }
                        
                        // Update notification badge
                        const badge = document.querySelector('.notification-badge');
                        if (badge) {
                            badge.remove();
                        }
                        
                        // Hide "Mark all as read" link
                        markAllReadButton.style.display = 'none';
                    }
                }
            };
            xhr.send('mark_all_read=true');
        });
    }
    
    // Function to update notification badge count
    function updateNotificationBadge() {
        const badge = document.querySelector('.notification-badge');
        if (badge) {
            const currentCount = parseInt(badge.textContent);
            if (currentCount > 1) {
                badge.textContent = currentCount - 1;
            } else {
                badge.remove();
            }
        }
    }
    
    // Make notification items clickable to view details
    const notificationItems = document.querySelectorAll('.notification-item');
    notificationItems.forEach(item => {
        item.addEventListener('click', function(e) {
            if (!e.target.classList.contains('mark-read')) {
                const notificationId = this.getAttribute('data-id');
                const notificationType = this.querySelector('.notification-icon i').className;
                const referenceId = this.getAttribute('data-reference-id');
                
                // Mark as read first
                const xhr = new XMLHttpRequest();
                xhr.open('POST', window.location.href, true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            // Update notification count
                            updateNotificationBadge();
                            
                            // Redirect based on notification type
                            if (notificationType.includes('application_status')) {
                                window.location.href = 'jobseeker/job_application.php?id=' + referenceId;
                            } else if (notificationType.includes('job_posting')) {
                                window.location.href = 'jobseeker/job_details.php?id=' + referenceId;
                            } else if (notificationType.includes('message')) {
                                window.location.href = 'messages.php';
                            } else {
                                // Default: view all notifications
                                window.location.href = 'all_notifications.php';
                            }
                        }
                    }
                };
                xhr.send(`mark_read=true&notification_id=${notificationId}`);
            }
        });
    });
});
</script>