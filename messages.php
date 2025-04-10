<?php
session_start();
include 'db_connect.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=messages.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch conversations
$conversations_query = "
    SELECT 
        m.id, 
        m.sender_id, 
        m.receiver_id, 
        m.ad_id, 
        m.message, 
        m.created_at,
        a.title as ad_title,
        a.image as ad_image,
        CASE 
            WHEN m.sender_id = $user_id THEN u_receiver.name
            ELSE u_sender.name
        END as contact_name,
        CASE 
            WHEN m.sender_id = $user_id THEN u_receiver.id
            ELSE u_sender.id
        END as contact_id
    FROM messages m
    JOIN ads a ON m.ad_id = a.id
    JOIN users u_sender ON m.sender_id = u_sender.id
    JOIN users u_receiver ON m.receiver_id = u_receiver.id
    WHERE m.sender_id = $user_id OR m.receiver_id = $user_id
    ORDER BY m.created_at DESC
";

$conversations_result = $conn->query($conversations_query);

// Group messages by conversation (contact + ad)
$conversations = [];
if($conversations_result && $conversations_result->num_rows > 0) {
    while($row = $conversations_result->fetch_assoc()) {
        $conversation_key = $row['contact_id'] . '_' . $row['ad_id'];
        
        if(!isset($conversations[$conversation_key])) {
            $conversations[$conversation_key] = [
                'contact_id' => $row['contact_id'],
                'contact_name' => $row['contact_name'],
                'ad_id' => $row['ad_id'],
                'ad_title' => $row['ad_title'],
                'ad_image' => $row['ad_image'],
                'last_message' => $row['message'],
                'last_message_time' => $row['created_at'],
                'is_sender' => $row['sender_id'] == $user_id
            ];
        }
    }
}

// Get selected conversation messages if conversation is selected
$selected_conversation = null;
$messages = [];

if(isset($_GET['contact']) && isset($_GET['ad'])) {
    $contact_id = (int)$_GET['contact'];
    $ad_id = (int)$_GET['ad'];
    
    // Get conversation details
    $conversation_key = $contact_id . '_' . $ad_id;
    if(isset($conversations[$conversation_key])) {
        $selected_conversation = $conversations[$conversation_key];
        
        // Fetch messages for this conversation
        $messages_query = "
            SELECT 
                m.*, 
                u_sender.name as sender_name
            FROM messages m
            JOIN users u_sender ON m.sender_id = u_sender.id
            WHERE 
                ((m.sender_id = $user_id AND m.receiver_id = $contact_id) OR 
                (m.sender_id = $contact_id AND m.receiver_id = $user_id))
                AND m.ad_id = $ad_id
            ORDER BY m.created_at ASC
        ";
        
        $messages_result = $conn->query($messages_query);
        
        if($messages_result && $messages_result->num_rows > 0) {
            while($message = $messages_result->fetch_assoc()) {
                $messages[] = $message;
            }
        }
        
        // Mark messages as read
        $update_query = "
            UPDATE messages 
            SET is_read = 1 
            WHERE receiver_id = $user_id AND sender_id = $contact_id AND ad_id = $ad_id
        ";
        $conn->query($update_query);
    }
}

// Send new message
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_message'])) {
    $contact_id = (int)$_POST['contact_id'];
    $ad_id = (int)$_POST['ad_id'];
    $message = $conn->real_escape_string($_POST['message']);
    
    if(!empty($message)) {
        $insert_query = "
            INSERT INTO messages (sender_id, receiver_id, ad_id, message) 
            VALUES ($user_id, $contact_id, $ad_id, '$message')
        ";
        
        if($conn->query($insert_query) === TRUE) {
            // Redirect to avoid form resubmission
            header("Location: messages.php?contact=$contact_id&ad=$ad_id");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - OLX Clone</title>
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
        
        /* Messages Section */
        .messages-container {
            padding: 30px 0;
            flex: 1;
        }
        
        .messages-header {
            margin-bottom: 30px;
        }
        
        .messages-header h1 {
            font-size: 24px;
        }
        
        .messages-grid {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 30px;
            background-color: white;
            border-radius: 4px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            height: 70vh;
        }
        
        /* Conversations List */
        .conversations-list {
            border-right: 1px solid #eee;
            overflow-y: auto;
        }
        
        .conversation-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background-color 0.3s ease;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        
        .conversation-item:hover {
            background-color: #f9f9f9;
        }
        
        .conversation-item.active {
            background-color: #f2f4f5;
            border-left: 3px solid #23e5db;
        }
        
        .conversation-header {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .conversation-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #f2f4f5;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            font-size: 16px;
            color: #002f34;
        }
        
        .conversation-info {
            flex: 1;
        }
        
        .conversation-name {
            font-weight: 500;
            margin-bottom: 3px;
        }
        
        .conversation-time {
            font-size: 12px;
            color: #666;
        }
        
        .conversation-ad {
            display: flex;
            align-items: center;
        }
        
        .conversation-ad-image {
            width: 50px;
            height: 50px;
            border-radius: 4px;
            overflow: hidden;
            margin-right: 10px;
        }
        
        .conversation-ad-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .conversation-ad-title {
            font-size: 14px;
            color: #666;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 200px;
        }
        
        .conversation-last-message {
            font-size: 14px;
            color: #666;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-top: 5px;
        }
        
        /* Chat Area */
        .chat-area {
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .chat-header {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
        }
        
        .chat-header-info {
            flex: 1;
        }
        
        .chat-header-name {
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .chat-header-ad {
            font-size: 14px;
            color: #666;
        }
        
        .chat-header-ad a {
            color: #002f34;
            text-decoration: none;
        }
        
        .chat-header-ad a:hover {
            text-decoration: underline;
        }
        
        .chat-messages {
            flex: 1;
            padding: 15px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }
        
        .message {
            max-width: 70%;
            padding: 10px 15px;
            border-radius: 18px;
            margin-bottom: 10px;
            position: relative;
            word-wrap: break-word;
        }
        
        .message-time {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
            text-align: right;
        }
        
        .message-received {
            background-color: #f2f4f5;
            align-self: flex-start;
            border-bottom-left-radius: 5px;
        }
        
        .message-sent {
            background-color: #dcf8c6;
            align-self: flex-end;
            border-bottom-right-radius: 5px;
        }
        
        .chat-input {
            padding: 15px;
            border-top: 1px solid #eee;
        }
        
        .chat-form {
            display: flex;
            align-items: center;
        }
        
        .chat-form input {
            flex: 1;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 30px;
            font-size: 16px;
            outline: none;
        }
        
        .chat-form input:focus {
            border-color: #23e5db;
        }
        
        .chat-form button {
            background-color: #002f34;
            color: white;
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-left: 10px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .chat-form button:hover {
            background-color: #00474f;
        }
        
        .no-conversation-selected {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #666;
            text-align: center;
            padding: 20px;
        }
        
        .no-conversation-selected i {
            font-size: 48px;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .no-conversation-selected h2 {
            font-size: 20px;
            margin-bottom: 10px;
        }
        
        .no-conversations {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #666;
            text-align: center;
            padding: 20px;
        }
        
        .no-conversations i {
            font-size: 48px;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .no-conversations h2 {
            font-size: 20px;
            margin-bottom: 10px;
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
        @media (max-width: 992px) {
            .messages-grid {
                grid-template-columns: 1fr;
                height: auto;
            }
            
            .conversations-list {
                border-right: none;
                border-bottom: 1px solid #eee;
                max-height: 300px;
            }
            
            .chat-area {
                height: 500px;
            }
        }
        
        @media (max-width: 576px) {
            .conversation-ad-title {
                max-width: 150px;
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
    
    <main class="messages-container">
        <div class="container">
            <div class="messages-header">
                <h1>Messages</h1>
            </div>
            
            <div class="messages-grid">
                <div class="conversations-list">
                    <?php if(count($conversations) > 0): ?>
                        <?php foreach($conversations as $key => $conversation): ?>
                            <?php 
                            $is_active = isset($_GET['contact']) && isset($_GET['ad']) && 
                                        $_GET['contact'] == $conversation['contact_id'] && 
                                        $_GET['ad'] == $conversation['ad_id'];
                            ?>
                            <a href="messages.php?contact=<?php echo $conversation['contact_id']; ?>&ad=<?php echo $conversation['ad_id']; ?>" 
                               class="conversation-item <?php echo $is_active ? 'active' : ''; ?>">
                                <div class="conversation-header">
                                    <div class="conversation-avatar">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div class="conversation-info">
                                        <div class="conversation-name"><?php echo htmlspecialchars($conversation['contact_name']); ?></div>
                                        <div class="conversation-time"><?php echo date('d M, H:i', strtotime($conversation['last_message_time'])); ?></div>
                                    </div>
                                </div>
                                <div class="conversation-ad">
                                    <div class="conversation-ad-image">
                                        <img src="<?php echo $conversation['ad_image']; ?>" alt="<?php echo htmlspecialchars($conversation['ad_title']); ?>">
                                    </div>
                                    <div class="conversation-ad-title"><?php echo htmlspecialchars($conversation['ad_title']); ?></div>
                                </div>
                                <div class="conversation-last-message">
                                    <?php if($conversation['is_sender']): ?>
                                        <span>You: </span>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars(substr($conversation['last_message'], 0, 50)) . (strlen($conversation['last_message']) > 50 ? '...' : ''); ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-conversations">
                            <i class="fas fa-comments"></i>
                            <h2>No messages yet</h2>
                            <p>When you contact sellers or receive messages, you'll see them here.</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="chat-area">
                    <?php if($selected_conversation): ?>
                        <div class="chat-header">
                            <div class="chat-header-info">
                                <div class="chat-header-name"><?php echo htmlspecialchars($selected_conversation['contact_name']); ?></div>
                                <div class="chat-header-ad">
                                    About: <a href="ad-details.php?id=<?php echo $selected_conversation['ad_id']; ?>"><?php echo htmlspecialchars($selected_conversation['ad_title']); ?></a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="chat-messages" id="chat-messages">
                            <?php foreach($messages as $message): ?>
                                <div class="message <?php echo $message['sender_id'] == $user_id ? 'message-sent' : 'message-received'; ?>">
                                    <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                                    <div class="message-time"><?php echo date('H:i', strtotime($message['created_at'])); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="chat-input">
                            <form action="messages.php?contact=<?php echo $selected_conversation['contact_id']; ?>&ad=<?php echo $selected_conversation['ad_id']; ?>" method="POST" class="chat-form">
                                <input type="hidden" name="contact_id" value="<?php echo $selected_conversation['contact_id']; ?>">
                                <input type="hidden" name="ad_id" value="<?php echo $selected_conversation['ad_id']; ?>">
                                <input type="text" name="message" placeholder="Type a message..." required autocomplete="off">
                                <button type="submit" name="send_message"><i class="fas fa-paper-plane"></i></button>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="no-conversation-selected">
                            <i class="fas fa-comments"></i>
                            <h2>Select a conversation</h2>
                            <p>Choose a conversation from the list to view messages.</p>
                        </div>
                    <?php endif; ?>
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
        document.addEventListener('DOMContentLoaded', function() {
            // Scroll to bottom of chat messages
            const chatMessages = document.getElementById('chat-messages');
            if(chatMessages) {
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
        });
    </script>
</body>
</html>
