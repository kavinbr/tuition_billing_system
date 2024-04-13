<?php
include 'admin.php';
include 'session_helper.php';

// Function to get invoice details by invoice number
function getInvoiceDetailsByNumber($conn, $invoiceNumber) {
    $invoiceNumber = mysqli_real_escape_string($conn, $invoiceNumber);
    $result = $conn->query("SELECT * FROM invoices WHERE invoice_number = '$invoiceNumber'");

    if ($result === false) {
        die('Error in query: ' . $conn->error);
    }

    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }

    return null;
}

// Function to generate a new invoice number
function generateNewInvoiceNumber($conn, $branchId, $invoiceDate) {
    $invoiceDateFormatted = date("Ymd", strtotime($invoiceDate));

    // Get the latest sequence number for the given branch_id and invoice_date
    $result = $conn->query("SELECT MAX(SUBSTRING(invoice_number, -2)) AS max_sequence FROM invoices WHERE branch_id = '$branchId' AND invoice_date = '$invoiceDate'");

    if ($result === false) {
        die('Error in query: ' . $conn->error);
    }

    $row = $result->fetch_assoc();
    $maxSequence = ($row['max_sequence'] != null) ? $row['max_sequence'] : 0;

    // If the invoice_date is different, start a new sequence from 01
    if ($maxSequence >= 99) {
        $newSequence = 1;
    } else {
        // Increment the sequence number
        $newSequence = $maxSequence + 1;
    }

    // Format the new sequence number with leading zeros
    $formattedSequence = str_pad($newSequence, 2, '0', STR_PAD_LEFT);

    // Combine branch_id, invoice_date, and formatted sequence to create the new invoice number
    $newInvoiceNumber = $branchId . $invoiceDateFormatted . $formattedSequence;

    return $newInvoiceNumber;
}

if (isset($_POST['payDue'])) {
    $invoiceNumber = mysqli_real_escape_string($conn, $_POST['invoiceNumber']);
    $actualAmount = mysqli_real_escape_string($conn, $_POST['actualAmount']);

    // Get invoice details
    $invoiceDetails = getInvoiceDetailsByNumber($conn, $invoiceNumber);

    if ($invoiceDetails) {
        $dueAmount = $invoiceDetails['due_amount'];
       
        // Check if actual amount is greater than due amount
        if ($actualAmount > $dueAmount) {
            $balanceAmount = $actualAmount - $dueAmount;
            $newDueAmount = 0; // Set due amount to 0 if paid in full
            $status = 'Advance Paid';
        } else if($actualAmount == $dueAmount) {

            $balanceAmount = $actualAmount - $dueAmount;
            $newDueAmount = 0; // Set due amount to 0 if paid in full
            $status = 'Paid';
        } else{
            $balanceAmount = 0;
            $newDueAmount = $dueAmount - $actualAmount;
            $status = 'Due';
        }

        $branchId = $invoiceDetails['branch_id'];
        // Set $invoiceDate to the current date
        $invoiceDate = date("Y-m-d");

        // Generate a new invoice number
        $newInvoiceNumber = generateNewInvoiceNumber($conn, $branchId, $invoiceDate);

        // Update invoices table with new due amount, balance amount, status, and time
        $updateQuery = "UPDATE invoices SET invoice_date = ?, invoice_number = ?, due_amount = ?, balance_amount = ?, invoice_status = ?, time =TIME(CURRENT_TIMESTAMP) WHERE invoice_number = ?";
        $stmt = $conn->prepare($updateQuery);

        if (!$stmt) {
            die('Error in prepare statement: ' . $conn->error);
        }

        $stmt->bind_param("ssdsss", $invoiceDate, $newInvoiceNumber, $newDueAmount, $balanceAmount, $status, $invoiceNumber);

        if (!$stmt->execute()) {
            die('Error in execute statement: ' . $stmt->error);
        }

        $stmt->close();

        // Redirect or provide success response
        header("Location: branch_admin_dashboard.php");
        exit();
    } else {
        echo "Error: Invoice not found.";
    }
} else {
    echo "Error: Invalid request.";
}
?>
