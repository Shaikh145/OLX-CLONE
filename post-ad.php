<?php
session_start();
include 'db_connect.php';
include 'social-links.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=post-ad.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch categories for dropdown
$categories_query = "SELECT * FROM categories ORDER BY name";
$categories_result = $conn->query($categories_query);

// Initialize variables
$title = $description = $price = $location = $category_id = "";
$currency = "₹"; // Default currency
$errors = [];
$success_message = "";

// Create uploads directory if it doesn't exist
$upload_dir = "uploads/";
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Process form submission
if($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate inputs
    $title = $conn->real_escape_string($_POST['title']);
    $description = $conn->real_escape_string($_POST['description']);
    $price = (float)$_POST['price'];
    $location = $conn->real_escape_string($_POST['location']);
    $category_id = (int)$_POST['category_id'];
    $currency = $conn->real_escape_string($_POST['currency']);
    
    // Validate title
    if(empty($title)) {
        $errors[] = "Title is required";
    } elseif(strlen($title) < 5 || strlen($title) > 100) {
        $errors[] = "Title must be between 5 and 100 characters";
    }
    
    // Validate description
    if(empty($description)) {
        $errors[] = "Description is required";
    } elseif(strlen($description) < 20) {
        $errors[] = "Description must be at least 20 characters";
    }
    
    // Validate price
    if(empty($price) || $price <= 0) {
        $errors[] = "Please enter a valid price";
    }
    
    // Validate location
    if(empty($location)) {
        $errors[] = "Location is required";
    }
    
    // Validate category
    if(empty($category_id)) {
        $errors[] = "Please select a category";
    }
    
    // Validate currency
    if(!in_array($currency, ['₹', '$'])) {
        $currency = '₹'; // Default to rupee if invalid
    }
    
    // Handle image upload
    $image_path = "";
    if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
        $file_type = $_FILES['image']['type'];
        $file_size = $_FILES['image']['size'];
        $file_tmp = $_FILES['image']['tmp_name'];
        
        // Validate file type
        if(!in_array($file_type, $allowed_types)) {
            $errors[] = "Only JPG, JPEG, PNG and GIF files are allowed";
        }
        
        // Validate file size (max 5MB)
        if($file_size > 5242880) {
            $errors[] = "File size must be less than 5MB";
        }
        
        // Generate unique filename
        $file_name = uniqid() . '_' . $_FILES['image']['name'];
        $upload_path = $upload_dir . $file_name;
        
        // Move uploaded file
        if(empty($errors)) {
            if(move_uploaded_file($file_tmp, $upload_path)) {
                $image_path = $upload_path;
            } else {
                $errors[] = "Failed to upload image. Please try again.";
            }
        }
    } else {
        $errors[] = "Please select an image for your ad";
    }
    
    // If no errors, insert ad into database
    if(empty($errors)) {
        // First, check if the ads table has a currency column
        $check_column_query = "SHOW COLUMNS FROM ads LIKE 'currency'";
        $column_result = $conn->query($check_column_query);
        
        // If currency column doesn't exist, add it
        if($column_result->num_rows == 0) {
            $alter_table_query = "ALTER TABLE ads ADD COLUMN currency VARCHAR(10) DEFAULT '₹'";
            $conn->query($alter_table_query);
        }
        
        $insert_query = "INSERT INTO ads (user_id, category_id, title, description, price, currency, location, image, created_at) 
                        VALUES ($user_id, $category_id, '$title', '$description', $price, '$currency', '$location', '$image_path', NOW())";
        
        if($conn->query($insert_query) === TRUE) {
            $ad_id = $conn->insert_id;
            $success_message = "Ad posted successfully!";
            
            // Clear form data after successful submission
            $title = $description = $price = $location = $category_id = "";
            $currency = "₹";
            
            // Redirect to the ad details page after 2 seconds
            header("refresh:2;url=ad-details.php?id=$ad_id");
        } else {
            $errors[] = "Error: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post an Ad - OLX Clone</title>
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
        
        /* Post Ad Form */
        .post-ad-container {
            padding: 30px 0;
        }
        
        .post-ad-header {
            margin-bottom: 30px;
        }
        
        .post-ad-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .post-ad-header p {
            color: #666;
        }
        
        .post-ad-form {
            background-color: white;
            border-radius: 4px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        .form-group input[type="text"]:focus,
        .form-group input[type="number"]:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            border-color: #23e5db;
            outline: none;
        }
        
        .form-group textarea {
            min-height: 150px;
            resize: vertical;
        }
        
        .form-group select {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 15px;
        }
        
        .form-group .help-text {
            margin-top: 5px;
            font-size: 14px;
            color: #666;
        }
        
        .price-container {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .currency-select {
            width: 100px !important;
        }
        
        .price-input {
            flex: 1;
        }
        
        .image-upload-container {
            border: 2px dashed #ddd;
            border-radius: 4px;
            padding: 30px;
            text-align: center;
            position: relative;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .image-upload-container:hover {
            border-color: #23e5db;
        }
        
        .image-upload-container i {
            font-size: 48px;
            color: #ddd;
            margin-bottom: 15px;
        }
        
        .image-upload-container h3 {
            margin-bottom: 10px;
        }
        
        .image-upload-container p {
            color: #666;
            margin-bottom: 15px;
        }
        
        .image-upload-btn {
            display: inline-block;
            background-color: #f2f4f5;
            color: #002f34;
            padding: 8px 20px;
            border-radius: 4px;
            font-weight: 500;
            cursor: pointer;
        }
        
        .image-upload-container input[type="file"] {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }
        
        .image-preview {
            margin-top: 20px;
            display: none;
        }
        
        .image-preview img {
            max-width: 100%;
            max-height: 300px;
            border-radius: 4px;
        }
        
        .submit-btn {
            background-color: #002f34;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        
        .submit-btn:hover {
            background-color: #00474f;
        }
        
        /* Alerts */
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert ul {
            margin: 10px 0 0 20px;
        }
        
        /* Footer */
        footer {
            background-color: #002f34;
            color: white;
            padding: 20px 0;
            margin-top: 50px;
        }
        
        .footer-container {
            text-align: center;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
            }
            
            .user-actions {
                margin-top: 15px;
            }
            
            .post-ad-form {
                padding: 20px;
            }
            
            .price-container {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .currency-select {
                width: 100% !important;
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
    
    <main class="post-ad-container">
        <div class="container">
            <div class="post-ad-header">
                <h1>Post Your Ad</h1>
                <p>Fill in the details below to post your ad</p>
            </div>
            
            <?php if(!empty($success_message)): ?>
            <div class="alert alert-success">
                <?php echo $success_message; ?>
            </div>
            <?php endif; ?>
            
            <?php if(!empty($errors)): ?>
            <div class="alert alert-danger">
                <strong>Please fix the following errors:</strong>
                <ul>
                    <?php foreach($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <form action="post-ad.php" method="POST" class="post-ad-form" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="category_id">Category *</label>
                    <select id="category_id" name="category_id" required>
                        <option value="">Select a category</option>
                        <?php 
                        if($categories_result && $categories_result->num_rows > 0) {
                            while($category = $categories_result->fetch_assoc()) {
                                $selected = ($category_id == $category['id']) ? 'selected' : '';
                                echo '<option value="' . $category['id'] . '" ' . $selected . '>' . htmlspecialchars($category['name']) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="title">Title *</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($title); ?>" required>
                    <div class="help-text">Keep it short and catchy. 5-100 characters.</div>
                </div>
                
                <div class="form-group">
                    <label for="description">Description *</label>
                    <textarea id="description" name="description" required><?php echo htmlspecialchars($description); ?></textarea>
                    <div class="help-text">Include condition, features, and reason for selling. Minimum 20 characters.</div>
                </div>
                
                <div class="form-group">
                    <label for="price">Price *</label>
                    <div class="price-container">
                        <select id="currency" name="currency" class="currency-select">
                            <option value="₹" <?php echo ($currency == '₹') ? 'selected' : ''; ?>>₹ (INR)</option>
                            <option value="$" <?php echo ($currency == '$') ? 'selected' : ''; ?>>$ (USD)</option>
                        </select>
                        <input type="number" id="price" name="price" class="price-input" value="<?php echo htmlspecialchars($price); ?>" min="1" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="location">Location *</label>
                    <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($location); ?>" required>
                    <div class="help-text">Enter your city or neighborhood.</div>
                </div>
                
                <div class="form-group">
                    <label>Upload Image *</label>
                    <div class="image-upload-container" id="image-upload-container">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <h3>Upload Image</h3>
                        <p>JPG, PNG or GIF. Max size 5MB.</p>
                        <div class="image-upload-btn">Choose File</div>
                        <input type="file" id="image" name="image" accept="image/*" required>
                    </div>
                    <div class="image-preview" id="image-preview">
                        <img id="preview-img" src="#" alt="Preview">
                    </div>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="submit-btn">Post Ad</button>
                </div>
            </form>
        </div>
    </main>
    
    <footer>
        <div class="container footer-container">
            <p>&copy; <?php echo date('Y'); ?> OLX Clone. All rights reserved.</p>
        </div>
    </footer>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Image preview functionality
            const imageInput = document.getElementById('image');
            const imagePreview = document.getElementById('image-preview');
            const previewImg = document.getElementById('preview-img');
            const uploadContainer = document.getElementById('image-upload-container');
            
            imageInput.addEventListener('change', function() {
                if(this.files && this.files[0]) {
                    const file = this.files[0];
                    
                    // Check file type
                    const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
                    if(!validTypes.includes(file.type)) {
                        alert('Only JPG, JPEG, PNG and GIF files are allowed');
                        this.value = '';
                        return;
                    }
                    
                    // Check file size (max 5MB)
                    if(file.size > 5242880) {
                        alert('File size must be less than 5MB');
                        this.value = '';
                        return;
                    }
                    
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        previewImg.src = e.target.result;
                        imagePreview.style.display = 'block';
                        uploadContainer.style.borderColor = '#23e5db';
                    }
                    
                    reader.readAsDataURL(file);
                }
            });
            
            // Form validation
            const form = document.querySelector('.post-ad-form');
            
            form.addEventListener('submit', function(e) {
                let valid = true;
                
                // Validate title
                const title = document.getElementById('title').value.trim();
                if(title.length < 5 || title.length > 100) {
                    valid = false;
                    alert('Title must be between 5 and 100 characters');
                }
                
                // Validate description
                const description = document.getElementById('description').value.trim();
                if(description.length < 20) {
                    valid = false;
                    alert('Description must be at least 20 characters');
                }
                
                // Validate price
                const price = document.getElementById('price').value;
                if(price <= 0) {
                    valid = false;
                    alert('Please enter a valid price');
                }
                
                // Validate category
                const category = document.getElementById('category_id').value;
                if(!category) {
                    valid = false;
                    alert('Please select a category');
                }
                
                // Validate image
                const image = document.getElementById('image').value;
                if(!image) {
                    valid = false;
                    alert('Please select an image for your ad');
                }
                
                if(!valid) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>
