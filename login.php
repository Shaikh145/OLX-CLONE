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

// Process login form
if($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    
    if(empty($email) || empty($password)) {
        $error = "Please fill in all fields";
    } else {
        // Check if email exists
        $query = "SELECT id, name, email, password FROM users WHERE email = '$email'";
        $result = $conn->query($query);
        
        if($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if(password_verify($password, $user['password'])) {
                // Password is correct, create session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                
                // Redirect to homepage
                header("Location: index.php");
                exit();
            } else {
                $error = "Invalid email or password";
            }
        } else {
            $error = "Invalid email or password";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - OLX Clone</title>
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
        
        /* Login Form */
        .login-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 0;
        }
        
        .login-form {
            background-color: white;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            padding: 30px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h1 {
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .login-header p {
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
        
        .login-btn {
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
        
        .login-btn:hover {
            background-color: #00474f;
        }
        
        .login-footer {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }
        
        .login-footer a {
            color: #002f34;
            text-decoration: none;
            font-weight: 500;
        }
        
        .login-footer a:hover {
            text-decoration: underline;
        }
        
        .social-login {
            margin-top: 30px;
            text-align: center;
        }
        
        .social-login p {
            margin-bottom: 15px;
            position: relative;
        }
        
        .social-login p::before,
        .social-login p::after {
            content: "";
            position: absolute;
            top: 50%;
            width: 35%;
            height: 1px;
            background-color: #ddd;
        }
        
        .social-login p::before {
            left: 0;
        }
        
        .social-login p::after {
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
    </style>
</head>
<body>
    <header>
        <div class="container header-container">
            <a href="index.php" class="logo">OLX<span>Clone</span></a>
        </div>
    </header>
    
    <main class="login-container">
        <div class="login-form">
            <div class="login-header">
                <h1>Welcome Back</h1>
                <p>Log in to manage your account</p>
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
            
            <form action="login.php" method="POST">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>
                
                <button type="submit" class="login-btn">Login</button>
            </form>
            
            <div class="login-footer">
                <p>Don't have an account? <a href="signup.php">Sign up</a></p>
                <p><a href="forgot-password.php">Forgot password?</a></p>
            </div>
            
            <div class="social-login">
                <p>Or login with</p>
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
            const form = document.querySelector('form');
            
            form.addEventListener('submit', function(event) {
                const email = document.getElementById('email').value.trim();
                const password = document.getElementById('password').value.trim();
                
                if(email === '' || password === '') {
                    event.preventDefault();
                    alert('Please fill in all fields');
                }
            });
        });
    </script>
</body>
</html>
