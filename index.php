<?php
session_start();
include 'db_connect.php';
include 'social-links.php'; // Include social links file

// Fetch categories
$categories_query = "SELECT * FROM categories";
$categories_result = $conn->query($categories_query);

// Fetch ads with filtering
$where_clause = "WHERE 1=1";
if (isset($_GET['category']) && !empty($_GET['category'])) {
$category_id = (int)$_GET['category'];
$where_clause .= " AND a.category_id = $category_id";
}

if (isset($_GET['search']) && !empty($_GET['search'])) {
$search = $conn->real_escape_string($_GET['search']);
$where_clause .= " AND (a.title LIKE '%$search%' OR a.description LIKE '%$search%')";
}

// Check if currency column exists in ads table
$check_column_query = "SHOW COLUMNS FROM ads LIKE 'currency'";
$column_result = $conn->query($check_column_query);

// If currency column doesn't exist, add it
if($column_result->num_rows == 0) {
    $alter_table_query = "ALTER TABLE ads ADD COLUMN currency VARCHAR(10) DEFAULT '₹'";
    $conn->query($alter_table_query);
}

$ads_query = "SELECT a.*, c.name as category_name, u.name as seller_name 
          FROM ads a 
          JOIN categories c ON a.category_id = c.id 
          JOIN users u ON a.user_id = u.id 
          $where_clause 
          ORDER BY a.created_at DESC";
$ads_result = $conn->query($ads_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>OLX Clone - Buy and Sell Anything</title>
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
    
    .search-bar {
        flex-grow: 1;
        margin: 0 20px;
        position: relative;
    }
    
    .search-bar form {
        display: flex;
    }
    
    .search-bar input {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #002f34;
        border-radius: 4px 0 0 4px;
        font-size: 16px;
        outline: none;
    }
    
    .search-bar button {
        background-color: #002f34;
        color: white;
        border: none;
        padding: 0 20px;
        border-radius: 0 4px 4px 0;
        cursor: pointer;
        font-size: 16px;
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
    
    /* Categories Section */
    .categories {
        background-color: white;
        padding: 15px 0;
        margin-bottom: 30px;
        box-shadow: 0 1px 5px rgba(0, 0, 0, 0.1);
    }
    
    .categories-container {
        display: flex;
        overflow-x: auto;
        padding-bottom: 10px;
    }
    
    .category-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        margin-right: 25px;
        text-decoration: none;
        color: #002f34;
        min-width: 80px;
    }
    
    .category-icon {
        width: 60px;
        height: 60px;
        background-color: #f2f4f5;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 8px;
        transition: all 0.3s ease;
    }
    
    .category-icon i {
        font-size: 24px;
    }
    
    .category-item:hover .category-icon {
        background-color: #23e5db;
        color: #002f34;
    }
    
    .category-name {
        font-size: 14px;
        text-align: center;
    }
    
    /* Ads Grid */
    .ads-heading {
        margin: 20px 0;
        font-size: 24px;
        font-weight: 600;
    }
    
    .ads-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 40px;
    }
    
    .ad-card {
        background-color: white;
        border-radius: 4px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease;
    }
    
    .ad-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .ad-image {
        height: 180px;
        overflow: hidden;
        position: relative;
    }
    
    .ad-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }
    
    .ad-card:hover .ad-image img {
        transform: scale(1.05);
    }
    
    .ad-details {
        padding: 15px;
    }
    
    .ad-price {
        font-size: 20px;
        font-weight: bold;
        margin-bottom: 8px;
        color: #002f34;
    }
    
    .ad-title {
        font-size: 16px;
        margin-bottom: 8px;
        color: #002f34;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .ad-location {
        font-size: 14px;
        color: #666;
        margin-bottom: 5px;
    }
    
    .ad-date {
        font-size: 12px;
        color: #999;
    }
    
    .featured-badge {
        position: absolute;
        top: 10px;
        left: 10px;
        background-color: #ffce32;
        color: #002f34;
        padding: 5px 10px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: bold;
    }
    
    .category-badge {
        position: absolute;
        bottom: 10px;
        right: 10px;
        background-color: rgba(0, 47, 52, 0.7);
        color: white;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 12px;
    }
    
    /* Footer */
    footer {
        background-color: #002f34;
        color: white;
        padding: 40px 0;
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
        display: flex;
        align-items: center;
    }
    
    .footer-column ul li a i {
        margin-right: 10px;
        width: 20px;
        text-align: center;
    }
    
    .footer-column ul li a:hover {
        color: #23e5db;
    }
    
    .social-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background-color: rgba(255, 255, 255, 0.1);
        margin-right: 10px;
        transition: background-color 0.3s ease;
    }
    
    .footer-column ul li a:hover .social-icon {
        background-color: rgba(255, 255, 255, 0.2);
    }
    
    .copyright {
        text-align: center;
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .header-container {
            flex-direction: column;
        }
        
        .search-bar {
            margin: 15px 0;
            width: 100%;
        }
        
        .ads-grid {
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        }
    }
    
    @media (max-width: 576px) {
        .ads-grid {
            grid-template-columns: 1fr;
        }
    }
    
    /* No ads message */
    .no-ads {
        text-align: center;
        padding: 50px 0;
        background-color: white;
        border-radius: 4px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
    
    .no-ads i {
        font-size: 50px;
        color: #999;
        margin-bottom: 20px;
    }
    
    .no-ads h3 {
        font-size: 24px;
        margin-bottom: 10px;
    }
    
    .no-ads p {
        color: #666;
        margin-bottom: 20px;
    }
</style>
</head>
<body>
<header>
    <div class="container header-container">
        <a href="index.php" class="logo">OLX<span>Clone</span></a>
        
        <div class="search-bar">
            <form action="index.php" method="GET">
                <?php if(isset($_GET['category'])): ?>
                <input type="hidden" name="category" value="<?php echo $_GET['category']; ?>">
                <?php endif; ?>
                <input type="text" name="search" placeholder="Find Cars, Mobile Phones, and more..." 
                       value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>
        </div>
        
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

<section class="categories">
    <div class="container categories-container">
        <a href="index.php" class="category-item">
            <div class="category-icon">
                <i class="fas fa-border-all"></i>
            </div>
            <div class="category-name">All Categories</div>
        </a>
        
        <?php while($category = $categories_result->fetch_assoc()): ?>
        <a href="index.php?category=<?php echo $category['id']; ?>" class="category-item">
            <div class="category-icon">
                <i class="fas fa-<?php echo $category['icon']; ?>"></i>
            </div>
            <div class="category-name"><?php echo $category['name']; ?></div>
        </a>
        <?php endwhile; ?>
    </div>
</section>

<main class="container">
    <h2 class="ads-heading">
        <?php 
        if(isset($_GET['category'])) {
            $cat_id = (int)$_GET['category'];
            $cat_query = "SELECT name FROM categories WHERE id = $cat_id";
            $cat_result = $conn->query($cat_query);
            if($cat_result && $cat_result->num_rows > 0) {
                $cat = $cat_result->fetch_assoc();
                echo htmlspecialchars($cat['name']) . " - ";
            }
        }
        
        if(isset($_GET['search']) && !empty($_GET['search'])) {
            echo 'Search results for "' . htmlspecialchars($_GET['search']) . '"';
        } else {
            echo 'Fresh recommendations';
        }
        ?>
    </h2>
    
    <?php if($ads_result && $ads_result->num_rows > 0): ?>
    <div class="ads-grid">
        <?php while($ad = $ads_result->fetch_assoc()): ?>
        <a href="ad-details.php?id=<?php echo $ad['id']; ?>" class="ad-card">
            <div class="ad-image">
                <img src="<?php echo $ad['image']; ?>" alt="<?php echo htmlspecialchars($ad['title']); ?>">
                <div class="category-badge"><?php echo htmlspecialchars($ad['category_name']); ?></div>
            </div>
            <div class="ad-details">
                <div class="ad-price">
                    <?php 
                    // Display the correct currency symbol
                    $currency = isset($ad['currency']) ? $ad['currency'] : '₹';
                    echo $currency . number_format($ad['price']); 
                    ?>
                </div>
                <h3 class="ad-title"><?php echo htmlspecialchars($ad['title']); ?></h3>
                <div class="ad-location"><?php echo htmlspecialchars($ad['location']); ?></div>
                <div class="ad-date"><?php echo date('d M', strtotime($ad['created_at'])); ?></div>
            </div>
        </a>
        <?php endwhile; ?>
    </div>
    <?php else: ?>
    <div class="no-ads">
        <i class="fas fa-search"></i>
        <h3>No ads found</h3>
        <p>We couldn't find any ads matching your search criteria.</p>
        <a href="index.php" class="post-ad-btn">Clear Filters</a>
    </div>
    <?php endif; ?>
</main>

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
                <li>
                    <a href="<?php echo getSocialLink('facebook'); ?>" target="_blank">
                        <span class="social-icon"><i class="fab fa-facebook-f"></i></span> Facebook
                    </a>
                </li>
                <li>
                    <a href="<?php echo getSocialLink('twitter'); ?>" target="_blank">
                        <span class="social-icon"><i class="fab fa-twitter"></i></span> Twitter
                    </a>
                </li>
                <li>
                    <a href="<?php echo getSocialLink('instagram'); ?>" target="_blank">
                        <span class="social-icon"><i class="fab fa-instagram"></i></span> Instagram
                    </a>
                </li>
                <li>
                    <a href="<?php echo getSocialLink('youtube'); ?>" target="_blank">
                        <span class="social-icon"><i class="fab fa-youtube"></i></span> YouTube
                    </a>
                </li>
            </ul>
        </div>
    </div>
    
    <div class="container copyright">
        <p>&copy; <?php echo date('Y'); ?> OLX Clone. All rights reserved.</p>
    </div>
</footer>

<script>
    // JavaScript for any dynamic functionality
    document.addEventListener('DOMContentLoaded', function() {
        // Add any JavaScript functionality here
    });
</script>
</body>
</html>
