<?php
session_start();
include 'db_connect.php';

// Check if ad ID is provided
if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$ad_id = (int)$_GET['id'];

// Fetch ad details
$ad_query = "SELECT a.*, c.name as category_name, u.name as seller_name, u.phone as seller_phone, u.email as seller_email, u.id as seller_id 
             FROM ads a 
             JOIN categories c ON a.category_id = c.id 
             JOIN users u ON a.user_id = u.id 
             WHERE a.id = $ad_id";
$ad_result = $conn->query($ad_query);

// Check if ad exists
if($ad_result->num_rows == 0) {
    header("Location: index.php");
    exit();
}

$ad = $ad_result->fetch_assoc();

// Fetch similar ads
$category_id = $ad['category_id'];
$similar_ads_query = "SELECT a.*, c.name as category_name 
                     FROM ads a 
                     JOIN categories c ON a.category_id = c.id 
                     WHERE a.category_id = $category_id AND a.id != $ad_id 
                     ORDER BY a.created_at DESC 
                     LIMIT 4";
$similar_ads_result = $conn->query($similar_ads_query);

// Process contact form
$message_sent = false;
$error = '';

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_message'])) {
    // Check if user is logged in
    if(!isset($_SESSION['user_id'])) {
        header("Location: login.php?redirect=ad-details.php?id=$ad_id");
        exit();
    }
    
    $sender_id = $_SESSION['user_id'];
    $receiver_id = $ad['seller_id'];
    $message = $conn->real_escape_string($_POST['message']);
    
    if(empty($message)) {
        $error = "Please enter a message";
    } else {
        $insert_query = "INSERT INTO messages (sender_id, receiver_id, ad_id, message) 
                        VALUES ('$sender_id', '$receiver_id', '$ad_id', '$message')";
        
        if($conn->query($insert_query) === TRUE) {
            $message_sent = true;
        } else {
            $error = "Error: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($ad['title']); ?> - OLX Clone</title>
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
            position: sticky;
            top: 0;
            z-index: 100;
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
        
        /* Ad Details */
        .ad-details-container {
            padding: 30px 0;
        }
        
        .ad-details-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }
        
        /* Ad Image */
        .ad-image-container {
            background-color: white;
            border-radius: 4px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .ad-image {
            width: 100%;
            height: 500px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .ad-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        /* Ad Info */
        .ad-info {
            background-color: white;
            border-radius: 4px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .ad-price {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .ad-title {
            font-size: 20px;
            margin-bottom: 10px;
        }
        
        .ad-meta {
            display: flex;
            justify-content: space-between;
            color: #666;
            font-size: 14px;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        /* Seller Info */
        .seller-info {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .seller-info h3 {
            font-size: 18px;
            margin-bottom: 15px;
        }
        
        .seller-details {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .seller-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: #f2f4f5;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 24px;
            color: #002f34;
        }
        
        .seller-name {
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .seller-member-since {
            font-size: 14px;
            color: #666;
        }
        
        .contact-buttons {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .contact-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px;
            border-radius: 4px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .contact-btn i {
            margin-right: 10px;
        }
        
        .call-btn {
            background-color: #23e5db;
            color: #002f34;
        }
        
        .call-btn:hover {
            background-color: #1fcfc6;
        }
        
        .chat-btn {
            background-color: #002f34;
            color: white;
        }
        
        .chat-btn:hover {
            background-color: #00474f;
        }
        
        /* Safety Tips */
        .safety-tips {
            background-color: #fff3cd;
            padding: 15px;
            border-radius: 4px;
            margin-top: 20px;
        }
        
        .safety-tips h3 {
            font-size: 16px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }
        
        .safety-tips h3 i {
            margin-right: 10px;
            color: #856404;
        }
        
        .safety-tips ul {
            list-style: none;
            padding-left: 25px;
        }
        
        .safety-tips ul li {
            margin-bottom: 5px;
            font-size: 14px;
            color: #856404;
        }
        
        /* Ad Description */
        .ad-description {
            background-color: white;
            border-radius: 4px;
            padding: 20px;
            margin-top: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .ad-description h2 {
            font-size: 20px;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .description-content {
            line-height: 1.6;
            color: #333;
            white-space: pre-line;
        }
        
        /* Similar Ads */
        .similar-ads {
            margin-top: 30px;
        }
        
        .similar-ads h2 {
            font-size: 20px;
            margin-bottom: 20px;
        }
        
        .similar-ads-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .ad-card {
            background-color: white;
            border-radius: 4px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            text-decoration: none;
            color: inherit;
        }
        
        .ad-card:hover {
            transform: translateY(-5px);
        }
        
        .ad-card-image {
            height: 180px;
            overflow: hidden;
        }
        
        .ad-card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .ad-card:hover .ad-card-image img {
            transform: scale(1.05);
        }
        
        .ad-card-details {
            padding: 15px;
        }
        
        .ad-card-price {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .ad-card-title {
            font-size: 16px;
            margin-bottom: 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .ad-card-location {
            font-size: 14px;
            color: #666;
        }
        
        /* Contact Form Modal */
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
            margin: 10% auto;
            padding: 30px;
            border-radius: 4px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            position: relative;
        }
        
        .close-modal {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }
        
        .modal-header {
            margin-bottom: 20px;
        }
        
        .modal-header h3 {
            font-size: 20px;
        }
        
        .message-form textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            min-height: 150px;
            resize: vertical;
            margin-bottom: 20px;
        }
        
        .message-form textarea:focus {
            border-color: #23e5db;
            outline: none;
        }
        
        .message-form button {
            background-color: #002f34;
            color: white;
            border: none;
            width: 100%;
            padding: 12px;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        
        .message-form button:hover {
            background-color: #00474f;
        }
        
        .success-message {
            background-color: #e8f5e9;
            color: #2e7d32;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .error-message {
            background-color: #ffebee;
            color: #c62828;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        /* Footer */
        footer {
            background-color: #002f34;
            color: white;
            padding: 40px 0;
            margin-top: 50px;
        }
        
        .footer-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
        }
        
        .footer-column h3 {
            font-size: 18px;
            margin-bottom: 20px;
            color: #23e5db;
        }
        
        .footer-column ul {
            list-style: none;
        }
        
        .footer-column ul li {
            margin-bottom: 10px;
        }
        
        .footer-column ul li a {
            color: white;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .footer-column ul li a:hover {
            color: #23e5db;
        }
        
        .copyright {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .ad-details-grid {
                grid-template-columns: 1fr;
            }
            
            .ad-image {
                height: 400px;
            }
        }
        
        @media (max-width: 768px) {
            .similar-ads-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 576px) {
            .similar-ads-grid {
                grid-template-columns: 1fr;
            }
            
            .ad-image {
                height: 300px;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container header-container">
            <a href="index.php" class="logo">OLX<span>Clone</span></a>
            
            <div class="user-actions">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="my-ads.php">My Ads</a>
                    <a href="messages.php">Messages</a>
                    <a href="profile.php">My Account</a>
                    <a href="logout.php">Logout</a>
                <?php else: ?>
                    <a href="login.php">Login</a>
                    <a href="signup.php">Register</a>
                <?php endif; ?>
                <a href="post-ad.php" class="post-ad-btn">+ SELL</a>
            </div>
        </div>
    </header>
    
    <main class="container ad-details-container">
        <div class="ad-details-grid">
            <div class="ad-details-left">
                <div class="ad-image-container">
                    <div class="ad-image">
                        <img src="<?php echo $ad['image']; ?>" alt="<?php echo htmlspecialchars($ad['title']); ?>">
                    </div>
                </div>
                
                <div class="ad-description">
                    <h2>Description</h2>
                    <div class="description-content">
                        <?php echo nl2br(htmlspecialchars($ad['description'])); ?>
                    </div>
                </div>
            </div>
            
            <div class="ad-details-right">
                <div class="ad-info">
                    <div class="ad-price">₹<?php echo number_format($ad['price']); ?></div>
                    <h1 class="ad-title"><?php echo htmlspecialchars($ad['title']); ?></h1>
                    
                    <div class="ad-meta">
                        <div class="ad-location"><?php echo htmlspecialchars($ad['location']); ?></div>
                        <div class="ad-date">Posted on <?php echo date('d M Y', strtotime($ad['created_at'])); ?></div>
                    </div>
                    
                    <div class="seller-info">
                        <h3>Seller Information</h3>
                        <div class="seller-details">
                            <div class="seller-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <div>
                                <div class="seller-name"><?php echo htmlspecialchars($ad['seller_name']); ?></div>
                                <div class="seller-member-since">Member since <?php echo date('M Y', strtotime($ad['created_at'])); ?></div>
                            </div>
                        </div>
                        
                        <div class="contact-buttons">
                            <a href="tel:<?php echo $ad['seller_phone']; ?>" class="contact-btn call-btn">
                                <i class="fas fa-phone-alt"></i> <?php echo htmlspecialchars($ad['seller_phone']); ?>
                            </a>
                            <a href="#" class="contact-btn chat-btn" id="open-chat-modal">
                                <i class="fas fa-comment-alt"></i> Chat with Seller
                            </a>
                        </div>
                    </div>
                    
                    <div class="safety-tips">
                        <h3><i class="fas fa-shield-alt"></i> Safety Tips</h3>
                        <ul>
                            <li>Meet in a safe, public place</li>
                            <li>Check the item before you buy</li>
                            <li>Pay only after inspecting the item</li>
                            <li>Never share your personal financial information</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if($similar_ads_result && $similar_ads_result->num_rows > 0): ?>
        <div class="similar-ads">
            <h2>Similar Ads</h2>
            <div class="similar-ads-grid">
                <?php while($similar_ad = $similar_ads_result->fetch_assoc()): ?>
                <a href="ad-details.php?id=<?php echo $similar_ad['id']; ?>" class="ad-card">
                    <div class="ad-card-image">
                        <img src="<?php echo $similar_ad['image']; ?>" alt="<?php echo htmlspecialchars($similar_ad['title']); ?>">
                    </div>
                    <div class="ad-card-details">
                        <div class="ad-card-price">₹<?php echo number_format($similar_ad['price']); ?></div>
                        <h3 class="ad-card-title"><?php echo htmlspecialchars($similar_ad['title']); ?></h3>
                        <div class="ad-card-location"><?php echo htmlspecialchars($similar_ad['location']); ?></div>
                    </div>
                </a>
                <?php endwhile; ?>
            </div>
        </div>
        <?php endif; ?>
    </main>
    
    <!-- Chat Modal -->
    <div id="chat-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal" id="close-chat-modal">&times;</span>
            
            <div class="modal-header">
                <h3>Contact <?php echo htmlspecialchars($ad['seller_name']); ?></h3>
            </div>
            
            <?php if($message_sent): ?>
            <div class="success-message">
                Your message has been sent successfully!
            </div>
            <?php endif; ?>
            
            <?php if(!empty($error)): ?>
            <div class="error-message">
                <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <form action="ad-details.php?id=<?php echo $ad_id; ?>" method="POST" class="message-form">
                <textarea name="message" placeholder="Write your message here..." required></textarea>
                <button type="submit" name="send_message">Send Message</button>
            </form>
        </div>
    </div>
    
    <footer>
        <div class="container footer-container">
            <div class="footer-column">
                <h3>POPULAR CATEGORIES</h3>
                <ul>
                    <li><a href="index.php?category=1">Electronics</a></li>
                    <li><a href="index.php?category=2">Vehicles</a></li>
                    <li><a href="index.php?category=3">Property</a></li>
                    <li><a href="index.php?category=4">Furniture</a></li>
                </ul>
            </div>
            
            <div class="footer-column">
                <h3>ABOUT US</h3>
                <ul>
                    <li><a href="#">About OLX Clone</a></li>
                    <li><a href="#">Careers</a></li>
                    <li><a href="#">Contact Us</a></li>
                    <li><a href="#">Our Partners</a></li>
                </ul>
            </div>
            
            <div class="footer-column">
                <h3>OLX CLONE</h3>
                <ul>
                    <li><a href="#">Help</a></li>
                    <li><a href="#">Sitemap</a></li>
                    <li><a href="#">Legal & Privacy</a></li>
                    <li><a href="#">Cookie Policy</a></li>
                </ul>
            </div>
            
            <div class="footer-column">
                <h3>FOLLOW US</h3>
                <ul>
                    <li><a href="#"><i class="fab fa-facebook"></i> Facebook</a></li>
                    <li><a href="#"><i class="fab fa-twitter"></i> Twitter</a></li>
                    <li><a href="#"><i class="fab fa-instagram"></i> Instagram</a></li>
                    <li><a href="#"><i class="fab fa-youtube"></i> YouTube</a></li>
                </ul>
            </div>
        </div>
        
        <div class="container copyright">
            <p>&copy; <?php echo date('Y'); ?> OLX Clone. All rights reserved.</p>
        </div>
    </footer>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('chat-modal');
            const openModalBtn = document.getElementById('open-chat-modal');
            const closeModalBtn = document.getElementById('close-chat-modal');
            
            // Open modal
            openModalBtn.addEventListener('click', function(e) {
                e.preventDefault();
                modal.style.display = 'block';
            });
            
            // Close modal
            closeModalBtn.addEventListener('click', function() {
                modal.style.display = 'none';
            });
            
            // Close modal when clicking outside
            window.addEventListener('click', function(event) {
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            });
            
            <?php if($message_sent || !empty($error)): ?>
            // Show modal if message was sent or there was an error
            modal.style.display = 'block';
            <?php endif; ?>
        });
    </script>
</body>
</html>
