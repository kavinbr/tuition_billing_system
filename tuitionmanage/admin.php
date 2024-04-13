<?php
include 'config.php';
include 'session_helper.php';

// Check if the function already exists before declaring it
if (!function_exists('addStudent')) {
    function addStudent($conn, $branchID, $studentName, $contact, $address, $parentName) {
        // Modify your SQL query to include "branch_id"
        $sql = "INSERT INTO branch_students (branch_id, student_name, contact, address, parent_name) VALUES ('$branchID', '$studentName', '$contact', '$address', '$parentName')";
       
        // Execute the query
        if ($conn->query($sql) === TRUE) {
            // Add any additional logic as needed

            // Redirect to branch_admin_dashboard.php after successful insertion
            header("Location: branch_admin_dashboard.php");
            exit(); // Ensure that the script stops executing after the header redirect
        } else {
            // Handle the case where the insertion fails
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    }
}


?>
