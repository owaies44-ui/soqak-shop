<?php
session_start();
include('config.php');

if (!isset($_SESSION['user_id'])) {
    header('location: login.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $ad_id = (int)$_GET['id'];

    // First fetch the image paths so we can delete the files from disk
    $img_stmt = mysqli_prepare($con, "SELECT image_path FROM ad_images WHERE ad_id = ?");
    mysqli_stmt_bind_param($img_stmt, 'i', $ad_id);
    mysqli_stmt_execute($img_stmt);
    $img_result = mysqli_stmt_get_result($img_stmt);
    $image_files = [];
    while ($img = mysqli_fetch_assoc($img_result)) {
        $image_files[] = $img['image_path'];
    }
    mysqli_stmt_close($img_stmt);

    // Delete ad (ON DELETE CASCADE removes ad_images rows automatically)
    $del_stmt = mysqli_prepare($con, "DELETE FROM ads WHERE id = ? AND user_id = ?");
    mysqli_stmt_bind_param($del_stmt, 'ii', $ad_id, $user_id);
    mysqli_stmt_execute($del_stmt);

    if (mysqli_stmt_affected_rows($del_stmt) > 0) {
        // Remove image files from disk
        foreach ($image_files as $path) {
            // Safety: only delete files inside the images/ directory
            $real = realpath($path);
            $base = realpath('images');
            if ($real && $base && strpos($real, $base) === 0) {
                @unlink($real);
            }
        }
    }
    mysqli_stmt_close($del_stmt);
}

header('location: products.php');
exit();
