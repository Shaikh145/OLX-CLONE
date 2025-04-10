<?php
session_start();
include 'db_connect.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=edit-ad.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if ad ID is provided
if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: my-ads.php");
    exit();
}

$ad_id = (int)$_GET['id'];

// Check if ad belongs to user
$ad_query = "SELECT * FROM ads WHERE id = $ad_id AND user_id = $user_id";
$ad_result = $conn->query($ad_query);

if($ad_result->num_rows == 0) {
    header("Location: my-ads.php");
    exit();
}

$ad = $ad_result->fetch_assoc();

// Fetch categories for dropdown
$categories_query = "SELECT * FROM categories ORDER BY name";
$categories_result = $conn->query($categories_query);

$error = '';
$success = '';

// Process form submission
if($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $conn->real_escape_string($_POST['title']);
    $description = $conn->real_escape_string($_POST['description']);
    $price = (float)$_POST['price'];
    $category_id = (int)$_POST['category'];
    $location = $conn->real_escape_string($_POST['location']);
    $status = $conn->real_escape_string($_POST['status']);
    
    // Validate input
    if(empty($title) || empty($description) || empty($price) || empty($category_id) || empty($location)) {
        $error = "Please fill in all required fields";
    } else {
        $image_path = $ad['image']; // Default to existing image
        
        // Handle image upload if a new image is provided
        if(isset($_FILES["image"]) && $_FILES["image"]["error"] == 0) {
            $target_dir = "uploads/";
            
            // Create directory if it doesn't exist
            if(!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $allowed_types = array("jpg" => "image/jpg", "jpeg" => "image/jpeg", "png" => "image/png");
            $file_name = $_FILES["image"]["name"];
            $file_type = $_FILES["image"]["type"];
            $file_size = $_FILES["image"]["size"];
            
            // Verify file extension
            $ext = pathinfo($file_name, PATHINFO_EXTENSION);
            
            if(!array_key_exists($ext, $allowed_types)) {
                $error = "Error: Please select a valid file format (JPG, JPEG, PNG).";
            } else if($file_size > 5242880) { // 5MB max
                $error = "Error: File size is too large. Max 5MB allowed.";
            } else {
                // Generate unique file name
                $new_file_name = uniqid() . "." . $ext;
                $target_file = $target_dir . $new_file_name;
                
                // Upload file
                if(move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                    // Delete old image if it exists and is not the default
                    if(file_exists($ad['image']) && $ad['image'] != 'uploads/default.jpg') {
                        unlink($ad['image']);
                    }
                    
                    $image_path = $target_file;
                } else {
                    $error = "Error: There was a problem uploading your file. Please try again.";
                }
            }
        }
        
        // If no errors, update ad in database
        if(empty($error)) {
            $update_query = "UPDATE ads SET 
                            title = '$title', 
                            description = '$description', 
                            price = '$price', 
                            category_id = '$category_id', 
                            location = '$location', 
                            image = '$image_path',
                            status = '$status'
                            WHERE id = $ad_id";
            
            if($conn->query($update_query) === TRUE) {
                $success = "Your ad has been updated successfully!";
                
                // Refresh ad data
                $ad_result = $conn->query($ad_query);
                $ad = $ad_result->fetch_assoc();
            } else {
                $error = "Error: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Ad - OLX Clone</title>
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
            display: flex;
            flex-direction: column;
            min-height: 100vh;
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
        
        /* Edit Ad Form */
        .edit-ad-container {
            flex: 1;
            padding: 40px 0;
        }
        
        .edit-ad-form {
            background-color: white;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            padding: 30px;
        }
        
        .edit-ad-header {
            margin-bottom: 30px;
        }
        
        .edit-ad-header h1 {
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .edit-ad-header p {
            color: #666;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
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
        
        .form-group .required:after {
            content: " *";
            color: #e53935;
        }
        
        .image-upload {
            border: 2px dashed #ddd;
            padding: 20px;
            text-align: center;
            border-radius: 4px;
            cursor: pointer;
            transition: border-color 0.3s ease;
        }
        
        .image-upload:hover {
            border-color: #23e5db;
        }
        
        .image-upload i {
            font-size: 48px;
            color: #ddd;
            margin-bottom: 10px;
        }
        
        .image-upload p {
            margin-bottom: 5px;
        }
        
        .image-upload .file-info {
            font-size: 12px;
            color: #666;
        }
        
        .current-image {
            margin-top: 15px;
            text-align: center;
        }
        
        .current-image img {
            max-width: 100%;
            max-height: 200px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        
        .current-image p {
            margin-top: 5px;
            font-size: 14px;
            color: #666;
        }
        
        .image-preview {
            margin-top: 15px;
            display: none;
            text-align: center;
        }
        
        .image-preview img {
            max-width: 100%;
            max-height: 200px;
            border-radius: 4px;
        }
        
        .submit-btn {
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
        
        .submit-btn:hover {
            background-color: #00474f;
        }
        
        .error-message {
            background-color: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .success-message {
            background-color: #e8f5e9;
            color: #2e7d32;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        /* Form columns for desktop */
        .form-row {
            display: flex;
            gap: 20px;
        }
        
        .form-row .form-group {
            flex: 1;
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
            .form-row {
                flex-direction: column;
                gap: 0;
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
            </div>
        </div>
    </header>
    
    <main class="edit-ad-container">
        <div class="container">
            <div class="edit-ad-form">
                <div class="edit-ad-header">
                    <h1>Edit Ad</h1>
                    <p>Update your ad details below</p>
                </div>
                
                <?php if(!empty($error)): ?>
                <div class="error-message">
                    <?php echo $error; ?>
                </div>
                <?php endif; ?>
                
                <?php if(!empty($success)): ?>
                <div class="success-message">
                    <?php echo $success; ?>
                </div>
                <?php endif; ?>
                
                <form action="edit-ad.php?id=<?php echo $ad_id; ?>" method="POST" enctype="multipart/form-data" id="edit-ad-form">
                    <div class="form-group">
                        <label for="title" class="required">Ad Title</label>
                        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($ad['title']); ?>" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="category" class="required">Category</label>
                            <select id="category" name="category" required>
                                <?php 
                                // Reset pointer to beginning
                                $categories_result->data_seek(0);
                                while($category = $categories_result->fetch_assoc()): 
                                ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo ($category['id'] == $ad['category_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="price" class="required">Price (₹)</label>
                            <input type="number" id="price" name="price" value="<?php echo $ad['price']; ?>" min="0" step="0.01" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description" class="required">Description</label>
                        <textarea id="description" name="description" required><?php echo htmlspecialchars($ad['description']); ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="location" class="required">Location</label>
                            <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($ad['location']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="status" class="required">Status</label>
                            <select id="status" name="status" required>
                                <option value="active" <?php echo ($ad['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                                <option value="sold" <?php echo ($ad['status'] == 'sold') ? 'selected' : ''; ?>>Sold</option>
                                <option value="expired" <?php echo ($ad['status'] == 'expired') ? 'selected' : ''; ?>>Expired</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="image">Upload New Image (Optional)</label>
                        <div class="image-upload" id="image-upload-area">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Click to upload a new image or drag and drop</p>
                            <p class="file-info">Max file size: 5MB (JPG, JPEG, PNG)</p>
                            <input type="file" id="image" name="image" accept="image/jpeg, image/jpg, image/png" style="display: none;">
                        </div>
                        
                        <div class="current-image">
                            <p>Current Image:</p>
                            <img src="<?php echo $ad['image']; ?>" alt="<?php echo htmlspecialchars($ad['title']); ?>">
                        </div>
                        
                        <div class="image-preview" id="image-preview">
                            <p>New Image Preview:</p>
                            <img id="preview-img" src="#" alt="Preview">
                        </div>
                    </div>
                    
                    <button type="submit" class="submit-btn">Update Ad</button>
                </form>
            </div>
        </div>
    </main>
    
    <footer>
        <div class="container footer-container">
            <p>&copy; <?php echo date('Y'); ?> OLX Clone. All rights reserved.</p>
        </div>
    </footer>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('edit-ad-form');
            const imageUploadArea = document.getElementById('image-upload-area');
            const imageInput = document.getElementById('image');
            const imagePreview = document.getElementById('image-preview');
            const previewImg = document.getElementById('preview-img');
            
            // Handle image upload area click
            imageUploadArea.addEventListener('click', function() {
                imageInput.click();
            });
            
            // Handle image selection
            imageInput.addEventListener('change', function() {
                if(this.files && this.files[0]) {
                    const file = this.files[0];
                    
                    // Check file size
                    if(file.size > 5242880) { // 5MB
                        alert('File size is too large. Max 5MB allowed.');
                        this.value = '';
                        return;
                    }
                    
                    // Check file type
                    const fileType = file.type;
                    if(fileType !== 'image/jpeg' && fileType !== 'image/jpg' && fileType !== 'image/png') {
                        alert('Please select a valid file format (JPG, JPEG, PNG).');
                        this.value = '';
                        return;
                    }
                    
                    // Show preview
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewImg.src = e.target.result;
                        imagePreview.style.display = 'block';
                    }
                    reader.readAsDataURL(file);
                }
            });
            
            // Form validation
            form.addEventListener('submit', function(event) {
                const title = document.getElementById('title').value.trim();
                const category = document.getElementById('category').value;
                const price = document.getElementById('price').value;
                const description = document.getElementById('description').value.trim();
                const location = document.getElementById('location').value.trim();
                
                if(title === '' || category === '' || price === '' || description === '' || location === '') {
                    event.preventDefault();
                    alert('Please fill in all required fields');
                    return;
                }
            });
        });
    </script>
</body>
</html>
