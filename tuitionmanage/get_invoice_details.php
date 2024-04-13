<?php
include 'admin.php';
include 'session_helper.php';

if (isset($_POST['invoiceNumber'])) {
    $invoiceNumber = mysqli_real_escape_string($conn, $_POST['invoiceNumber']);

    // Query to get invoice details
    $query = "SELECT student_name, due_amount, grand_total FROM invoices WHERE invoice_number = '$invoiceNumber'";
    $result = $conn->query($query);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $response = [
            'studentName' => $row['student_name'],
            'dueAmount' => $row['due_amount'],
            'grandtotal' => $row['grand_total'],
        ];
        echo json_encode($response);
    } else {
        echo json_encode(['error' => 'Invoice not found']);
    }
} else {
    echo json_encode(['error' => 'Invalid request']);
}
?>
