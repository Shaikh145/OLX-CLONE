<?php
session_start();
include 'db_connect.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=my-ads.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle success and error messages
$error = '';
$success = '';

if(isset($_GET['error'])) {
    $error = $_GET['error'];
}

if(isset($_GET['success'])) {
    $success = $_GET['success'];
}

// Fetch user's ads
$ads_query = "SELECT a.*, c.name as category_name 
              FROM ads a 
              JOIN categories c ON a.category_id = c.id 
              WHERE a.user_id = $user_id 
              ORDER BY a.created_at DESC";
$ads_result = $conn->query($ads_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Ads - OLX Clone</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f2f4f5;
            color: #002f34;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        /* Header Styles */
        header {
            background-color: #ffffff;
            box-shadow: 0 1px 5px rgba(0, 0, 0, 0.1);
        }
        
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
        }
        
        .logo {
            font-size: 32px;
            font-weight: bold;
            color: #002f34;
            text-decoration: none;
        }
        
        .logo span {
            color: #23e5db;
        }
        
        .user-actions {
            display: flex;
            align-items: center;
        }
        
        .user-actions a {
            margin-left: 15px;
            text-decoration: none;
            color: #002f34;
            font-weight: 500;
        }
        
        .post-ad-btn {
            background-color: #fff;
            color: #002f34;
            border: 2px solid #002f34;
            padding: 8px 20px;
            border-radius: 30px;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .post-ad-btn:hover {
            background-color: #002f34;
            color: white;
        }
        
        /* My Ads Section */
        .my-ads-container {
            padding: 30px 0;
            flex: 1;
        }
        
        .my-ads-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .my-ads-header h1 {
            font-size: 24px;
        }
        
        .success-message {
            background-color: #e8f5e9;
            color: #2e7d32;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .error-message {
            background-color: #ffebee;
            color: #c62828;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .ads-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        
        .ad-card {
            background-color: white;
            border-radius: 4px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            position: relative;
        }
        
        .ad-image {
            height: 180px;
            overflow: hidden;
        }
        
        .ad-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .ad-details {
            padding: 15px;
        }
        
        .ad-price {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .ad-title {
            font-size: 16px;
            margin-bottom: 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .ad-meta {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            color: #666;
            margin-bottom: 15px;
        }
        
        .ad-actions {
            display: flex;
            gap: 10px;
        }
        
        .ad-action-btn {
            flex: 1;
            padding: 8px 0;
            text-align: center;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .edit-btn {
            background-color: #f2f4f5;
            color: #002f34;
        }
        
        .edit-btn:hover {
            background-color: #e6e9ea;
        }
        
        .delete-btn {
            background-color: #ffebee;
            color: #c62828;
        }
        
        .delete-btn:hover {
            background-color: #ffcdd2;
        }
        
        .ad-status {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-active {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        
        .status-sold {
            background-color: #ffebee;
            color: #c62828;
        }
        
        .status-expired {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .no-ads {
            background-color: white;
            border-radius: 4px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .no-ads i {
            font-size: 48px;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .no-ads h2 {
            font-size: 20px;
            margin-bottom: 10px;
        }
        
        .no-ads p {
            color: #666;
            margin-bottom: 20px;
        }
        
        /* Delete Confirmation Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 30px;
            border-radius: 4px;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            text-align: center;
        }
        
        .modal-content h3 {
            margin-bottom: 20px;
        }
        
        .modal-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        
        .modal-btn {
            padding: 10px 20px;
            border-radius: 4px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .cancel-btn {
            background-color: #f2f4f5;
            color: #002f34;
            border: none;
        }
        
        .cancel-btn:hover {
            background-color: #e6e9ea;
        }
        
        .confirm-btn {
            background-color: #c62828;
            color: white;
            border: none;
        }
        
        .confirm-btn:hover {
            background-color: #b71c1c;
        }
        
        /* Footer */
        footer {
            background-color: #002f34;
            color: white;
            padding: 20px 0;
            margin-top: auto;
        }
        
        .footer-container {
            text-align: center;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .ads-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 576px) {
            .ads-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container header-container">
            <a href="index.php" class="logo">OLX<span>Clone</span></a>
            
            <div class="user-actions">
                <a href="my-ads.php">My Ads</a>
                <a href="messages.php">Messages</a>
                <a href="profile.php">My Account</a>
                <a href="logout.php">Logout</a>
                <a href="post-ad.php" class="post-ad-btn">+ SELL</a>
            </div>
        </div>
    </header>
    
    <main class="my-ads-container">
        <div class="container">
            <div class="my-ads-header">
                <h1>My Ads</h1>
                <a href="post-ad.php" class="post-ad-btn">+ Post New Ad</a>
            </div>
            
            <?php if(!empty($success)): ?>
            <div class="success-message">
                <?php echo $success; ?>
            </div>
            <?php endif; ?>
            
            <?php if(!empty($error)): ?>
            <div class="error-message">
                <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <?php if($ads_result && $ads_result->num_rows > 0): ?>
            <div class="ads-grid">
                <?php while($ad = $ads_result->fetch_assoc()): ?>
                <div class="ad-card">
                    <div class="ad-status status-<?php echo $ad['status']; ?>"><?php echo ucfirst($ad['status']); ?></div>
                    <div class="ad-image">
                        <img src="<?php echo $ad['image']; ?>" alt="<?php echo htmlspecialchars($ad['title']); ?>">
                    </div>
                    <div class="ad-details">
                        <div class="ad-price">₹<?php echo number_format($ad['price']); ?></div>
                        <h3 class="ad-title"><?php echo htmlspecialchars($ad['title']); ?></h3>
                        <div class="ad-meta">
                            <div class="ad-location"><?php echo htmlspecialchars($ad['location']); ?></div>
                            <div class="ad-date"><?php echo date('d M', strtotime($ad['created_at'])); ?></div>
                        </div>
                        <div class="ad-actions">
                            <a href="edit-ad.php?id=<?php echo $ad['id']; ?>" class="ad-action-btn edit-btn">Edit</a>
                            <a href="#" class="ad-action-btn delete-btn" data-id="<?php echo $ad['id']; ?>">Delete</a>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            <?php else: ?>
            <div class="no-ads">
                <i class="fas fa-ad"></i>
                <h2>No ads yet</h2>
                <p>You haven't posted any ads yet. Start selling now!</p>
                <a href="post-ad.php" class="post-ad-btn">+ Post New Ad</a>
            </div>
            <?php endif; ?>
        </div>
    </main>
    
    <!-- Delete Confirmation Modal -->
    <div id="delete-modal" class="modal">
        <div class="modal-content">
            <h3>Are you sure you want to delete this ad?</h3>
            <p>This action cannot be undone.</p>
            <div class="modal-buttons">
                <button class="modal-btn cancel-btn" id="cancel-delete">Cancel</button>
                <a href="#" class="modal-btn confirm-btn" id="confirm-delete">Delete</a>
            </div>
        </div>
    </div>
    
    <footer>
        <div class="container footer-container">
            <p>&copy; <?php echo date('Y'); ?> OLX Clone. All rights reserved.</p>
        </div>
    </footer>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('delete-modal');
            const cancelBtn = document.getElementById('cancel-delete');
            const confirmBtn = document.getElementById('confirm-delete');
            const deleteButtons = document.querySelectorAll('.delete-btn');
            
            // Open modal when delete button is clicked
            deleteButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const adId = this.getAttribute('data-id');
                    modal.style.display = 'block';
                    confirmBtn.href = 'delete-ad.php?id=' + adId;
                });
            });
            
            // Close modal when cancel button is clicked
            cancelBtn.addEventListener('click', function() {
                modal.style.display = 'none';
            });
            
            // Close modal when clicking outside
            window.addEventListener('click', function(event) {
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>
