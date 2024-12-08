<?php
session_start();
include 'db/connection.php';

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room_id = intval($_POST['room_id']);
    $timeslot_id = intval($_POST['timeslot_id']);
    $user_email = $_SESSION['email'];

    // Check if the timeslot is still available
    $sql_check = "SELECT * FROM timeslots WHERE timeslot_id = ? AND is_available = TRUE";
    $stmt_check = mysqli_prepare($conn, $sql_check);
    mysqli_stmt_bind_param($stmt_check, 'i', $timeslot_id);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);

    if (mysqli_num_rows($result_check) > 0) {
        // Book the timeslot
        $sql_book = "INSERT INTO booking (room_id, timeslot_id, user_email) VALUES (?, ?, ?)";
        $stmt_book = mysqli_prepare($conn, $sql_book);
        mysqli_stmt_bind_param($stmt_book, 'iis', $room_id, $timeslot_id, $user_email);
        if (mysqli_stmt_execute($stmt_book)) {
            // Update timeslot availability to false
            $sql_update = "UPDATE timeslots SET is_available = FALSE WHERE timeslot_id = ?";
            $stmt_update = mysqli_prepare($conn, $sql_update);
            mysqli_stmt_bind_param($stmt_update, 'i', $timeslot_id);
            mysqli_stmt_execute($stmt_update);

            // Store success message in session and redirect
            $_SESSION['success_message'] = "Room successfully booked!";
            header("Location: booking_interface.php");
            exit();

        } else {
            // Log and display error if booking fails
            error_log("Error executing booking: " . mysqli_error($conn));
            $_SESSION['error_message'] = "Error booking room. Please try again later.";
            header("Location: booking_interface.php");
            exit();
        }
    } else {
        // Timeslot unavailable message
        $_SESSION['error_message'] = "The selected timeslot is no longer available.";
        header("Location: booking_interface.php");
        exit();
    }
}
?>
