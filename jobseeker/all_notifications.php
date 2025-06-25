<?php
/**
 * jobseeker/all_notifications.php
 * Displays all notifications for the currently logged in job seeker
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirect to login if not logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'jobseeker') {
    header("Location: ../login.php");
    exit();
}

// Include database configuration
require_once '../includes/db_config.php';

// Handle marking notification as read via AJAX
if (isset($_POST['mark_read']) && isset($_POST['notification_id'])) {
    $notificationId = $_POST['notification_id'];
    
    $sql = "UPDATE notifications 
            SET is_read = 1, read_at = NOW() 
            WHERE notification_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $notificationId);
    $success = $stmt->execute();
    
    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to mark notification as read']);
    }
    exit;
}

// "Mark all as read" functionality has been removed for better user experience

// Handle deleting a notification via AJAX
if (isset($_POST['delete_notification']) && isset($_POST['notification_id'])) {
    $notificationId = $_POST['notification_id'];
    
    $sql = "DELETE FROM notifications WHERE notification_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $notificationId);
    $success = $stmt->execute();
    
    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete notification']);
    }
    exit;
}

// Get user information
$userId = $_SESSION['user_id'];
$userType = $_SESSION['user_type'];

// Determine which notifications to show
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15; // Notifications per page
$offset = ($page - 1) * $limit;

// Get filter for notification type
$filter = isset($_GET['type']) ? $_GET['type'] : 'all';

// Get total notifications for pagination based on current filter
if ($filter == 'read') {
    $countSql = "SELECT COUNT(*) as total FROM notifications WHERE user_id = ? AND is_read = 1";
} else if ($filter == 'unread') {
    $countSql = "SELECT COUNT(*) as total FROM notifications WHERE user_id = ? AND is_read = 0";
} else if ($filter != 'all') {
    $countSql = "SELECT COUNT(*) as total FROM notifications WHERE user_id = ? AND type = ?";
} else {
    $countSql = "SELECT COUNT(*) as total FROM notifications WHERE user_id = ?";
}

$countStmt = $conn->prepare($countSql);

if ($filter != 'all' && $filter != 'read' && $filter != 'unread') {
    $countStmt->bind_param("is", $userId, $filter);
} else {
    $countStmt->bind_param("i", $userId);
}

$countStmt->execute();
$result = $countStmt->get_result();
$row = $result->fetch_assoc();
$totalNotifications = $row['total'];
$totalPages = ceil($totalNotifications / $limit);

// Set up SQL query based on filter type
if ($filter == 'read') {
    // Show only read notifications
    $sql = "SELECT * FROM notifications 
            WHERE user_id = ? AND is_read = 1
            ORDER BY created_at DESC 
            LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $userId, $limit, $offset);
} else if ($filter == 'unread') {
    // Show only unread notifications
    $sql = "SELECT * FROM notifications 
            WHERE user_id = ? AND is_read = 0
            ORDER BY created_at DESC 
            LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $userId, $limit, $offset);
} else if ($filter != 'all') {
    // Filter by specific notification type
    $sql = "SELECT * FROM notifications 
            WHERE user_id = ? AND type = ?
            ORDER BY created_at DESC 
            LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isis", $userId, $filter, $limit, $offset);
} else {
    // Show all notifications
    $sql = "SELECT * FROM notifications 
            WHERE user_id = ?
            ORDER BY created_at DESC 
            LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $userId, $limit, $offset);
}

$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

// Get notification types for filter dropdown
// Only get types that actually have notifications
$typesSql = "SELECT DISTINCT type FROM notifications WHERE user_id = ?";
$typesStmt = $conn->prepare($typesSql);
$typesStmt->bind_param("i", $userId);
$typesStmt->execute();
$typesResult = $typesStmt->get_result();

$notificationTypes = [];
while ($row = $typesResult->fetch_assoc()) {
    $notificationTypes[] = $row['type'];
}

// Count unread and read notifications
$unreadSql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
$unreadStmt = $conn->prepare($unreadSql);
$unreadStmt->bind_param("i", $userId);
$unreadStmt->execute();
$unreadResult = $unreadStmt->get_result();
$unreadRow = $unreadResult->fetch_assoc();
$unreadCount = $unreadRow['count'];

$readSql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 1";
$readStmt = $conn->prepare($readSql);
$readStmt->bind_param("i", $userId);
$readStmt->execute();
$readResult = $readStmt->get_result();
$readRow = $readResult->fetch_assoc();
$readCount = $readRow['count'];

// Function to convert notification type to a human-readable label
function getTypeLabel($type) {
    switch ($type) {
        case 'job_application':
            return 'Job Application';
        case 'application_status':
            return 'Application Status';
        case 'job_posting':
            return 'Job Posting';
        case 'message':
            return 'Message';
        case 'system':
            return 'System Notification';
        default:
            return ucfirst(str_replace('_', ' ', $type));
    }
}



// Include page header
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row mb-3">
        <div class="col-md-8">
            <h2>Your Notifications</h2>
        </div>
        <div class="col-md-4 text-right">
            <!-- "Mark All as Read" button removed for better user experience -->
        </div>
    </div>

    <!-- Notification filters -->
    <div class="row mb-3">
        <div class="col-md-12">
            <div class="btn-group flex-wrap" role="group">
            <?php 
                // Check if user has any notifications
                $totalCount = $unreadCount + $readCount;
                if ($totalCount > 0): 
                ?>
                    <a href="<?php echo basename($_SERVER['PHP_SELF']); ?>" class="btn <?php echo $filter == 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                        All (<?php echo $totalCount; ?>)
                    </a>
                <?php else: ?>
                    <button type="button" class="btn btn-outline-secondary" disabled>
                        All (0)
                    </button>
                <?php endif; ?>
                
                <?php if ($unreadCount > 0): ?>
                    <a href="<?php echo basename($_SERVER['PHP_SELF']); ?>?type=unread" class="btn <?php echo $filter == 'unread' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                        Unread (<?php echo $unreadCount; ?>)
                    </a>
                <?php else: ?>
                    <button type="button" class="btn btn-outline-secondary" disabled>
                        Unread (0)
                    </button>
                <?php endif; ?>
                
                <?php if ($readCount > 0): ?>
                    <a href="<?php echo basename($_SERVER['PHP_SELF']); ?>?type=read" class="btn <?php echo $filter == 'read' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                        Read (<?php echo $readCount; ?>)
                    </a>
                <?php else: ?>
                    <button type="button" class="btn btn-outline-secondary" disabled>
                        Read (0)
                    </button>
                <?php endif; ?>
                <?php foreach ($notificationTypes as $type): ?>
                    <?php 
                    // Count notifications of this specific type
                    $typeSql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND type = ?";
                    $typeStmt = $conn->prepare($typeSql);
                    $typeStmt->bind_param("is", $userId, $type);
                    $typeStmt->execute();
                    $typeResult = $typeStmt->get_result();
                    $typeRow = $typeResult->fetch_assoc();
                    $typeCount = $typeRow['count'];
                    ?>
                    
                    <?php if ($typeCount > 0): ?>
                        <a href="<?php echo basename($_SERVER['PHP_SELF']); ?>?type=<?php echo $type; ?>" 
                           class="btn <?php echo $filter == $type ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            <?php echo getTypeLabel($type); ?> (<?php echo $typeCount; ?>)
                        </a>
                    <?php else: ?>
                        <button type="button" class="btn btn-outline-secondary" disabled>
                            <?php echo getTypeLabel($type); ?> (0)
                        </button>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Notifications List -->
    <div class="notifications-container">
        <?php if (!empty($notifications)): ?>
            <?php foreach ($notifications as $notification): ?>
                <div class="notification-card <?php echo $notification['is_read'] ? 'read' : 'unread'; ?>" 
                     data-id="<?php echo $notification['notification_id']; ?>"
                     data-reference="<?php echo $notification['reference_id']; ?>"
                     data-type="<?php echo $notification['type']; ?>">
                    <div class="notification-icon">
                        <i class="fa <?php echo getNotificationIcon($notification['type']); ?>"></i>
                    </div>
                    <div class="notification-content">
                        <div class="notification-header">
                            <h5 class="notification-title">
                                <?php echo htmlspecialchars($notification['title']); ?>
                                <?php if (!$notification['is_read']): ?>
                                    <span class="badge badge-primary">New</span>
                                <?php endif; ?>
                            </h5>
                            <span class="notification-type"><?php echo getTypeLabel($notification['type']); ?></span>
                        </div>
                        <div class="notification-message">
                            <?php echo htmlspecialchars($notification['message']); ?>
                        </div>
                        <div class="notification-meta">
                            <span class="notification-time">
                                <i class="fa fa-clock-o"></i> <?php echo date('M d, Y g:i A', strtotime($notification['created_at'])); ?>
                            </span>
                            <?php if ($notification['is_read']): ?>
                                <span class="notification-read-status">
                                    <i class="fa fa-check"></i> Read on <?php echo date('M d, Y g:i A', strtotime($notification['read_at'])); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="notification-actions">
                    <?php if (!$notification['is_read']): ?>
                            <button class="btn btn-sm btn-outline-primary mark-read-btn" data-id="<?php echo $notification['notification_id']; ?>">
                                <i class="fa fa-check"></i> Mark Read
                            </button>
                        <?php else: ?>
                            <span class="btn btn-sm btn-outline-secondary disabled">
                                <i class="fa fa-check-circle"></i> Read
                            </span>
                        <?php endif; ?>
                        <button class="btn btn-sm btn-outline-danger delete-btn" data-id="<?php echo $notification['notification_id']; ?>">
                            <i class="fa fa-trash-o"></i> Delete
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Notification pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="all_notifications.php?page=<?php echo $page - 1; ?><?php echo $filter != 'all' ? '&type=' . $filter : ''; ?>">
                                    <i class="fa fa-chevron-left"></i> Previous
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="all_notifications.php?page=<?php echo $i; ?><?php echo $filter != 'all' ? '&type=' . $filter : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="all_notifications.php?page=<?php echo $page + 1; ?><?php echo $filter != 'all' ? '&type=' . $filter : ''; ?>">
                                    Next <i class="fa fa-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="empty-notifications">
                <div class="empty-icon">
                    <i class="fa fa-bell-slash-o"></i>
                </div>
                <h4>No notifications found</h4>
                <p>
                    <?php if ($filter != 'all'): ?>
                        You don't have any <?php echo $filter; ?> notifications. 
                        <a href="all_notifications.php">View all notifications</a>
                    <?php else: ?>
                        You don't have any notifications yet.
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Notification Page Styles */
.notifications-container {
    margin-top: 20px;
}

.notification-card {
    display: flex;
    border: 1px solid #e0e0e0;
    border-radius: 5px;
    margin-bottom: 15px;
    padding: 15px;
    transition: all 0.2s ease;
    position: relative;
    cursor: pointer;
}

.notification-card:hover {
    background-color: #f9f9f9;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.notification-card.unread {
    background-color: #f0f7ff;
    border-left: 4px solid #3498db;
}

.notification-icon {
    width: 50px;
    height: 50px;
    background-color: #e9ecef;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 20px;
    flex-shrink: 0;
}

.notification-icon i {
    font-size: 22px;
    color: #495057;
}

.notification-content {
    flex: 1;
}

.notification-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 5px;
}

.notification-title {
    margin: 0;
    font-size: 16px;
    color: #333;
    font-weight: 600;
}

.notification-type {
    font-size: 12px;
    background-color: #e9ecef;
    color: #6c757d;
    padding: 2px 8px;
    border-radius: 12px;
}

.notification-message {
    color: #555;
    font-size: 14px;
    margin-bottom: 8px;
    line-height: 1.4;
}

.notification-meta {
    display: flex;
    font-size: 12px;
    color: #888;
}

.notification-time {
    margin-right: 15px;
}

.notification-read-status {
    color: #28a745;
}

.notification-actions {
    display: flex;
    flex-direction: column;
    margin-left: 15px;
}

.notification-actions button, 
.notification-actions .btn {
    margin-bottom: 5px;
    min-width: 110px; /* Ensure buttons are wide enough to display text */
    text-align: left;
    white-space: nowrap;
}

.empty-notifications {
    text-align: center;
    padding: 50px 0;
    color: #6c757d;
}

.empty-icon {
    font-size: 48px;
    margin-bottom: 15px;
    color: #dee2e6;
}

/* Badge styles */
.badge-primary {
    background-color: #3498db;
    color: white;
    font-size: 10px;
    padding: 3px 6px;
    border-radius: 10px;
    vertical-align: middle;
    margin-left: 5px;
}

/* Ensure filter buttons have appropriate spacing */
.btn-group .btn {
    margin-right: 0;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mark notification as read
    const markReadBtns = document.querySelectorAll('.mark-read-btn');
    markReadBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const notificationId = this.getAttribute('data-id');
            markAsRead(notificationId, this);
        });
    });

    // "Mark all as read" functionality has been removed for better user experience
    // Delete notification
    const deleteBtns = document.querySelectorAll('.delete-btn');
    deleteBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            
            if (confirm('Are you sure you want to delete this notification?')) {
                const notificationId = this.getAttribute('data-id');
                const notificationCard = document.querySelector(`.notification-card[data-id="${notificationId}"]`);
                
                // AJAX request to delete notification
                const xhr = new XMLHttpRequest();
                xhr.open('POST', window.location.href, true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            // Remove the card from the DOM
                            if (notificationCard) {
                                notificationCard.remove();
                            }
                            
                            // Check if there are no more notifications
                            const remainingCards = document.querySelectorAll('.notification-card');
                            if (remainingCards.length === 0) {
                                // Create empty state message
                                const emptyNotifications = document.createElement('div');
                                emptyNotifications.className = 'empty-notifications';
                                emptyNotifications.innerHTML = `
                                    <div class="empty-icon">
                                        <i class="fa fa-bell-slash-o"></i>
                                    </div>
                                    <h4>No notifications found</h4>
                                    <p>You don't have any notifications yet.</p>
                                `;
                                document.querySelector('.notifications-container').appendChild(emptyNotifications);
                            }
                        }
                    }
                };
                xhr.send(`delete_notification=true&notification_id=${notificationId}`);
            }
        });
    });

    // Handle clicking on notification (for navigation)
    const notificationCards = document.querySelectorAll('.notification-card');
    notificationCards.forEach(card => {
        card.addEventListener('click', function(e) {
            // Don't navigate if clicking a button
            if (e.target.closest('button')) return;
            
            const notificationId = this.getAttribute('data-id');
            const referenceId = this.getAttribute('data-reference');
            const type = this.getAttribute('data-type');
            
           // If notification is unread, mark it as read
           if (this.classList.contains('unread')) {
                markAsRead(notificationId);
            }
        });
    });

    function markAsRead(notificationId, btnElement = null) {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', window.location.href, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    // Update UI
                    const notificationCard = document.querySelector(`.notification-card[data-id="${notificationId}"]`);
                    if (notificationCard) {
                        notificationCard.classList.remove('unread');
                        notificationCard.classList.add('read');
                        
                        // Remove "New" badge
                        const badge = notificationCard.querySelector('.badge-primary');
                        if (badge) badge.remove();
                        
                        // Add read timestamp
                        const metaDiv = notificationCard.querySelector('.notification-meta');
                        if (metaDiv) {
                            const now = new Date();
                            const formattedDate = `${now.toLocaleString('default', { month: 'short' })} ${now.getDate()}, ${now.getFullYear()} ${now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true })}`;
                            
                            const readStatus = document.createElement('span');
                            readStatus.className = 'notification-read-status';
                            readStatus.innerHTML = `<i class="fa fa-check"></i> Read on ${formattedDate}`;
                            metaDiv.appendChild(readStatus);
                        }
                    }
                    
                    // Remove the button if it exists
                    if (btnElement) {
                        btnElement.remove();
                    }
                    
                    // No need to check for unread notifications for "Mark all as read" button
                                    // since that feature has been 
                }
            }
        };
        xhr.send(`mark_read=true&notification_id=${notificationId}`);
    }
});
</script>

<?php
// Include page footer
include '../includes/footer.php';
?>