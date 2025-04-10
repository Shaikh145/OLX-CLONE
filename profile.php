<?php
session_start();
include 'db_connect.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=profile.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user data
$user_query = "SELECT * FROM users WHERE id = $user_id";
$user_result = $conn->query($user_query);
$user = $user_result->fetch_assoc();

// Fetch user's ads count
$ads_count_query = "SELECT COUNT(*) as total FROM ads WHERE user_id = $user_id";
$ads_count_result = $conn->query($ads_count_query);
$ads_count = $ads_count_result->fetch_assoc()['total'];

// Process profile update
$error = '';
$success = '';

if($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $conn->real_escape_string($_POST['name']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate input
    if(empty($name) || empty($phone)) {
        $error = "Name and phone number are required";
    } else {
        // Check if password change is requested
        if(!empty($current_password) || !empty($new_password) || !empty($confirm_password)) {
            // Verify current password
            if(password_verify($current_password, $user['password'])) {
                // Check if new passwords match
                if($new_password === $confirm_password) {
                    // Check password length
                    if(strlen($new_password) < 6) {
                        $error = "New password must be at least 6 characters long";
                    } else {
                        // Hash new password
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        
                        // Update user with new password
                        $update_query = "UPDATE users SET name = '$name', phone = '$phone', password = '$hashed_password' WHERE id = $user_id";
                    }
                } else {
                    $error = "New passwords do not match";
                }
            } else {
                $error = "Current password is incorrect";
            }
        } else {
            // Update user without changing password
            $update_query = "UPDATE users SET name = '$name', phone = '$phone' WHERE id = $user_id";
        }
        
        // Execute update if no errors
        if(empty($error)) {
            if($conn->query($update_query) === TRUE) {
                $success = "Profile updated successfully";
                
                // Update session data
                $_SESSION['user_name'] = $name;
                
                // Refresh user data
                $user_result = $conn->query($user_query);
                $user = $user_result->fetch_assoc();
            } else {
                $error = "Error updating profile: " . $conn->error;
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
    <title>My Profile - OLX Clone</title>
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
        
        /* Profile Section */
        .profile-container {
            padding: 40px 0;
            flex: 1;
        }
        
        .profile-grid {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 30px;
        }
        
        /* Profile Sidebar */
        .profile-sidebar {
            background-color: white;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background-color: #f2f4f5;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 48px;
            color: #002f34;
        }
        
        .profile-name {
            font-size: 20px;
            font-weight: 600;
            text-align: center;
            margin-bottom: 5px;
        }
        
        .profile-email {
            font-size: 14px;
            color: #666;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .profile-stats {
            padding: 15px 0;
            border-top: 1px solid #eee;
            border-bottom: 1px solid #eee;
            margin-bottom: 20px
