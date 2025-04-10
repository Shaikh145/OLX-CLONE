<?php
session_start();
include 'db_connect.php';

$error = '';
$success = '';

// Check if user is already logged in
if(isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Process signup form
if($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate input
    if(empty($name) || empty($email) || empty($phone) || empty($password) || empty($confirm_password)) {
        $error = "Please fill in all fields";
    } elseif($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif(strlen($password) < 6) {
        $error = "Password must be at least 6 characters long";
    } else {
        // Check if email already exists
        $check_query = "SELECT * FROM users WHERE email = '$email'";
        $check_result = $conn->query($check_query);
        
        if($check_result->num_rows > 0) {
            $error = "Email already exists. Please use a different email or login.";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $insert_query = "INSERT INTO users (name, email, phone, password) VALUES ('$name', '$email', '$phone', '$hashed_password')";
            
            if($conn->query($insert_query) === TRUE) {
                $success = "Registration successful! You can now login.";
                
                // Redirect to login page after 2 seconds
                echo "<script>
                    setTimeout(function() {
                        window.location.href = 'login.php';
                    }, 2000);
                </script>";
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
    <title>Sign Up - OLX Clone</title>
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
        
        /* Signup Form */
        .signup-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 0;
        }
        
        .signup-form {
            background-color: white;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
            padding: 30px;
        }
        
        .signup-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .signup-header h1 {
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .signup-header p {
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
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus {
            border-color: #23e5db;
            outline: none;
        }
        
        .password-requirements {
            margin-top: 5px;
            font-size: 12px;
            color: #666;
        }
        
        .signup-btn {
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
        
        .signup-btn:hover {
            background-color: #00474f;
        }
        
        .signup-footer {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }
        
        .signup-footer a {
            color: #002f34;
            text-decoration: none;
            font-weight: 500;
        }
        
        .signup-footer a:hover {
            text-decoration: underline;
        }
        
        .social-signup {
            margin-top: 30px;
            text-align: center;
        }
        
        .social-signup p {
            margin-bottom: 15px;
            position: relative;
        }
        
        .social-signup p::before,
        .social-signup p::after {
            content: "";
            position: absolute;
            top: 50%;
            width: 35%;
            height: 1px;
            background-color: #ddd;
        }
        
        .social-signup p::before {
            left: 0;
        }
        
        .social-signup p::after {
            right: 0;
        }
        
        .social-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        
        .social-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: #f2f4f5;
            color: #002f34;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }
        
        .social-btn:hover {
            background-color: #e6e9ea;
        }
        
        .social-btn i {
            font-size: 20px;
        }
        
        .error-message {
            background-color: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .success-message {
            background-color: #e8f5e9;
            color: #2e7d32;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
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
        
        /* Form columns for desktop */
        .form-row {
            display: flex;
            gap: 20px;
        }
        
        .form-row .form-group {
            flex: 1;
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
        </div>
    </header>
    
    <main class="signup-container">
        <div class="signup-form">
            <div class="signup-header">
                <h1>Create an Account</h1>
                <p>Join our community to buy and sell items</p>
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
            
            <form action="signup.php" method="POST" id="signup-form">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" placeholder="Enter your full name" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" placeholder="Enter your email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" placeholder="Enter your phone number" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Create a password" required>
                    <div class="password-requirements">
                        Password must be at least 6 characters long
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
                </div>
                
                <button type="submit" class="signup-btn">Create Account</button>
            </form>
            
            <div class="signup-footer">
                <p>Already have an account? <a href="login.php">Login</a></p>
            </div>
            
            <div class="social-signup">
                <p>Or sign up with</p>
                <div class="social-buttons">
                    <a href="#" class="social-btn"><i class="fab fa-google"></i></a>
                    <a href="#" class="social-btn"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-btn"><i class="fab fa-twitter"></i></a>
                </div>
            </div>
        </div>
    </main>
    
    <footer>
        <div class="container footer-container">
            <p>&copy; <?php echo date('Y'); ?> OLX Clone. All rights reserved.</p>
        </div>
    </footer>
    
    <script>
        // JavaScript for form validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('signup-form');
            
            form.addEventListener('submit', function(event) {
                const name = document.getElementById('name').value.trim();
                const email = document.getElementById('email').value.trim();
                const phone = document.getElementById('phone').value.trim();
                const password = document.getElementById('password').value;
                const confirmPassword = document.getElementById('confirm_password').value;
                
                // Basic validation
                if(name === '' || email === '' || phone === '' || password === '' || confirmPassword === '') {
                    event.preventDefault();
                    alert('Please fill in all fields');
                    return;
                }
                
                // Password length check
                if(password.length < 6) {
                    event.preventDefault();
                    alert('Password must be at least 6 characters long');
                    return;
                }
                
                // Password match check
                if(password !== confirmPassword) {
                    event.preventDefault();
                    alert('Passwords do not match');
                    return;
                }
                
                // Email format check
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if(!emailRegex.test(email)) {
                    event.preventDefault();
                    alert('Please enter a valid email address');
                    return;
                }
                
                // Phone format check (basic)
                const phoneRegex = /^\d{10}$/;
                if(!phoneRegex.test(phone)) {
                    event.preventDefault();
                    alert('Please enter a valid 10-digit phone number');
                    return;
                }
            });
        });
    </script>
</body>
</html>
