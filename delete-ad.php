<?php
session_start();
include 'db_connect.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if ad ID is provided
if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: my-ads.php?error=No ad specified");
    exit();
}

$ad_id = (int)$_GET['id'];

// Check if ad belongs to user
$check_query = "SELECT * FROM ads WHERE id = $ad_id AND user_id = $user_id";
$check_result = $conn->query($check_query);

if($check_result->num_rows == 0) {
    // Ad doesn't exist or doesn't belong to user
    header("Location: my-ads.php?error=You don't have permission to delete this ad");
    exit();
}

$ad = $check_result->fetch_assoc();

// Delete image file if exists and not default
if(file_exists($ad['image']) && $ad['image'] != 'uploads/default.jpg') {
    unlink($ad['image']);
}

// Delete ad from database
$delete_query = "DELETE FROM ads WHERE id = $ad_id";

if($conn->query($delete_query) === TRUE) {
    // Also delete any messages related to this ad
    $delete_messages_query = "DELETE FROM messages WHERE ad_id = $ad_id";
    $conn->query($delete_messages_query);
    
    header("Location: my-ads.php?success=Ad deleted successfully");
} else {
    header("Location: my-ads.php?error=Error deleting ad: " . $conn->error);
}
exit();
?>
