<?php
include 'admin.php';
include 'session_helper.php';

// Fetch contact numbers for students in the specified branch
$branchID = $_SESSION['branch_id'];


$contactNumbers = fetchStudentContactNumbers($conn, $branchID);
$subjectnames = fetchSubjectNames($conn, $branchID);
// Initialize variables for student details
$studentName = $address = $parentName = '';
$price = $tax = '';


if (isset($_POST['addNewInvoice'])) {
    // Fetch data from the form
    $branchID = $_SESSION['branch_id'];
    $contact = $_POST['contact'];

    // Check if the contact number already exists in invoices
    $checkContactQuery = "SELECT COUNT(*) as count FROM invoices WHERE contact_number = '$contact'";
    $checkContactResult = $conn->query($checkContactQuery);
    $contactCount = $checkContactResult->fetch_assoc()['count'];

    if ($contactCount > 0) {
        // Contact number already exists in invoices
        echo '<script>alert("Contact number already present in invoice!");</script>';
    } else {
        // Continue with inserting the invoice
        $studentID = getStudentIDByContact($conn, $contact);
        $invoiceDate = $_POST['invoiceDate'];
        $grandTotal = $_POST['grandTotal'];
        $paidAmount = $_POST['paidAmount'];
        $address = $_POST['address'];
        $studentName = $_POST['studentName'];
        $parentName = $_POST['parentName'];

        $subjectNamesString = json_encode($_POST['subjectName']);
        $subjectTotalAmounts = $_POST['total'];

        // Calculate due amount
        $dueamount = ($grandTotal - $paidAmount);
        $balance_amount = 0;

        // If paid amount is greater than grand total, update paid amount and set due amount to 0
        if ($paidAmount > $grandTotal) {
            $balance_amount = $paidAmount - $grandTotal;
            $dueamount = 0;
        }

        if ($dueamount == 0) {
            if ($balance_amount > 0) {
                $invoiceStatus = 'Advance paid'; // Consider it as an advance payment
            } else {
                $invoiceStatus = 'Paid';
            }
        } else {
            $invoiceStatus = 'Due';
        }

        // Generate the invoice number
        $invoiceNumber = generateInvoiceNumber($conn, $branchID, $invoiceDate);

        $sql = "INSERT INTO invoices (branch_id, student_id, invoice_number, grand_total, invoice_date, student_name, contact_number, address, paid_amount, due_amount, balance_amount, invoice_status, subject_name, time)
        VALUES ('$branchID', '$studentID', '$invoiceNumber', '$grandTotal', CAST('$invoiceDate' AS DATE), '$studentName', '$contact', '$address', '$paidAmount', '$dueamount','$balance_amount', '$invoiceStatus', '$subjectNamesString', CURRENT_TIME())";

        if ($conn->query($sql) === TRUE) {
            // Invoice details inserted successfully
            echo '<script>alert("New invoice added successfully!");</script>';
        } else {
            // Error inserting invoice details
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    }
}

// Function to get student ID by contact number
function getStudentIDByContact($conn, $contact) {
    $result = $conn->query("SELECT id FROM branch_students WHERE contact = '$contact'");
    $row = $result->fetch_assoc();
    return $row['id'];
}

// Function to generate invoice number
function generateInvoiceNumber($conn, $branchID, $invoiceDate) {
    // Format the invoice date (assuming it's in YYYY-MM-DD format)
    $formattedInvoiceDate = date('Ymd', strtotime($invoiceDate));

    // Query to get the last used sequence number for the given date
    $result = $conn->query("SELECT MAX(SUBSTRING(invoice_number, -2)) as max_sequence 
                            FROM invoices 
                            WHERE branch_id = '$branchID' 
                            AND invoice_date = '$formattedInvoiceDate'");
    $row = $result->fetch_assoc();
    $maxSequence = ($row['max_sequence']) ? intval($row['max_sequence']) : 0;

    // Increment the sequence number or restart from 01 if the date is different
    $newSequence = ($maxSequence >= 99) ? 1 : $maxSequence + 1;

    // Pad the sequence number with leading zeros
    $paddedSequence = str_pad($newSequence, 2, '0', STR_PAD_LEFT);

    // Concatenate branch ID, formatted date, and padded sequence to create the invoice number
    $invoiceNumber = $branchID . $formattedInvoiceDate . $paddedSequence;

    return $invoiceNumber;
}


// Function to fetch contact numbers for students in the specified branch
function fetchStudentContactNumbers($conn, $branchID) {
    $contactNumbers = array();

    $result = $conn->query("SELECT DISTINCT contact FROM branch_students WHERE branch_id = '$branchID'");
    while ($row = $result->fetch_assoc()) {
        $contactNumbers[] = $row['contact'];
    }

    return $contactNumbers;
}

function fetchSubjectNames($conn, $branchID) {
    $subjectnames = array();

    $result = $conn->query("SELECT DISTINCT subject_name FROM subjects WHERE branch_id = '$branchID'");
    while ($row = $result->fetch_assoc()) {
        $subjectnames[] = $row['subject_name'];
    }

    return $subjectnames;
}

// Other functions and code as needed
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Dashboard</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="./style/invoice_operation.css">
</head>

<body>


<!-- Warning Message -->
<div class="alert alert-warning mt-3" id="warningMessage" style="display: none;">
</div>

<div class="container mt-5">
        <!-- Add New Invoice Form -->
        <form action="invoice_operations.php" method="post" id="newInvoiceForm" class="row mx-auto">

            <!-- Contact Dropdown -->
            <div class="form-group col-md-4">
                <label for="contact">Contact:</label>
                <select class="form-control" name="contact" id="contact" required>
                    <?php foreach ($contactNumbers as $contactNumber) : ?>
                        <option value="<?php echo $contactNumber; ?>"><?php echo $contactNumber; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Student Name -->
            <div class="form-group col-md-4">
                <label for="studentName">Student Name:</label>
                <input type="text" class="form-control" name="studentName" id="studentName" required readonly value="<?php echo $studentName; ?>">
            </div>

            <!-- Address -->
            <div class="form-group col-md-4">
                <label for="address">Address:</label>
                <input type="text" class="form-control" name="address" id="address" required readonly value="<?php echo $address; ?>">
            </div>

            <!-- Parent Name -->
            <div class="form-group col-md-4">
                <label for="parentName">Parent Name:</label>
                <input type="text" class="form-control" name="parentName" id="parentName" required readonly value="<?php echo $parentName; ?>">
            </div>

            <!-- Invoice Date -->
            <div class="form-group col-md-4">
                <label for="invoiceDate">Invoice Date:</label>
                <input type="date" class="form-control" name="invoiceDate" id="invoiceDate" required>
            </div>
             <!-- Subject details rows -->
             <div id="subjectRowsContainer"></div>
             <div class="form-group col-md-12">
              <button type="button" id="addRow" class="btn btn-secondary">Add Row</button>
             </div>


        <!-- Grand Total --> 
              <div class="form-group col-md-4">
              <label for="grandTotal">Grand Total:</label>
               <input type="text" class="form-control" name="grandTotal" id="grandTotal" readonly>
              </div>


             <!-- Paid Amount -->
<div class="form-group col-md-4">
    <label for="paidAmount">Paid Amount:</label>
    <input type="text" class="form-control" name="paidAmount" id="paidAmount" required oninput="validatePaidAmount();">
    <span id="paidAmountError" class="error"></span>
</div>

             <!-- Due Amount -->
           <div class="form-group col-md-4">
            <label for="dueAmount">Due Amount:</label>
             <input type="text" class="form-control" name="dueAmount" id="dueAmount" readonly>
           </div>
            <!-- Add New Invoice Button -->
            <div class="form-group col-md-12">
                <button type="submit" name="addNewInvoice" id ="addNewInvoice" class="btn btn-primary">Add New Invoice</button>
            </div>
    </form>

        <a href="branch_admin_dashboard.php" class="btn btn-primary">Back to admin Dashboard</a>
    <!-- ... Your existing PHP code ... -->

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            fetchStudentsByContact();
        });
        document.getElementById('contact').addEventListener('change', function () {
            fetchStudentsByContact();
        });

        function fetchStudentsByContact() {
            var contact = document.getElementById('contact').value;

            // Make an AJAX request to fetch students based on contact number
            $.ajax({
                type: 'POST',
                url: 'fetch_student_details.php', // Create this file with the server-side logic
                data: { contact: contact },
                success: function (response) {
                    // Parse the JSON response
                    var studentDetails = JSON.parse(response);

                    // Populate the form fields with the retrieved information
                    document.getElementById('studentName').value = studentDetails.studentName;
                    document.getElementById('address').value = studentDetails.address;
                    document.getElementById('parentName').value = studentDetails.parentName;
                },
                error: function (error) {
                    console.error('Error fetching students by contact:', error);
                }
            });
        }


 

        document.getElementById('addRow').addEventListener('click', function () {
       addNewSubjectRow();
  });

  function addNewSubjectRow() {
    // Clone the existing subject row template
    var clonedRow = document.getElementById('subjectRowTemplate').content.cloneNode(true);

    // Modify the IDs and names of the cloned row elements to avoid conflicts
    var rowIdx = document.getElementById('subjectRowsContainer').childElementCount;
    clonedRow.querySelectorAll('[id]').forEach(function (element) {
        element.id += '_' + rowIdx;
    });
    clonedRow.querySelectorAll('[name]').forEach(function (element) {
        element.name += '[]';
    });

    // Set default placeholder for the "Select Subject" option in the cloned row
    var subjectNameSelect = clonedRow.querySelector('.subject-name');
    var defaultOption = document.createElement('option');
    defaultOption.text = 'Select Subject';
    defaultOption.value = '';
    subjectNameSelect.add(defaultOption);
    subjectNameSelect.selectedIndex = subjectNameSelect.options.length - 1;

    // Add a delete button to the cloned row
    var deleteButton = document.createElement('button');
    deleteButton.type = 'button';
    deleteButton.className = 'btn btn-danger  mt-2 ml-4 align-self-center';
    deleteButton.style.height = '35px'; // Set a custom height
    deleteButton.style.width = '63px';  // Set a custom width
    deleteButton.textContent = 'Delete';
    deleteButton.addEventListener('click', function () {
        // Remove the corresponding row when the delete button is clicked
        document.getElementById('subjectRowsContainer').removeChild(clonedRowContainer);
        updateGrandTotal();
    });
    
    // Append the delete button to the cloned row
    clonedRow.appendChild(deleteButton);

    // Append the cloned row to the form
    var clonedRowContainer = document.createElement('div');
    clonedRowContainer.className = 'row';
    clonedRowContainer.appendChild(clonedRow);
    document.getElementById('subjectRowsContainer').appendChild(clonedRowContainer);

    // Fetch subject details for the new row
    fetchSubjectByName(rowIdx);
  }

 
  // Modify the function to accept a parameter for row index
  function fetchSubjectByName(rowIdx) {
    var subjectNameSelect = document.getElementsByClassName('subject-name')[rowIdx];
    var priceInput = document.getElementsByClassName('price')[rowIdx];
    var taxInput = document.getElementsByClassName('tax')[rowIdx];
    var totalInput = document.getElementsByClassName('total')[rowIdx];

    // Attach an event listener to the subjectNameSelect dropdown to handle changes
    subjectNameSelect.addEventListener('change', function () {
        // Fetch subject details for the new row when subject name changes
        var subjectName = subjectNameSelect.value;

        $.ajax({
            type: 'POST',
            url: 'fetch_subject_details.php',
            data: { subjectName: subjectName },
            success: function (response) {
                var subjectDetails = JSON.parse(response);

                // Populate the form fields with the retrieved information for the specific row
                priceInput.value = subjectDetails.price;
                taxInput.value = subjectDetails.tax;
                calculateTotal(rowIdx);
            },
            error: function (error) {
                console.error('Error fetching details by subject name:', error);
            }
        });
    });
 }




 function calculateTotal(rowIdx) {
    // Get the price and tax values for the specific row
    var price = parseFloat(document.getElementsByClassName('price')[rowIdx].value) || 0;
    var tax = parseFloat(document.getElementsByClassName('tax')[rowIdx].value) || 0;

    // Calculate the total
    var total = price + ((tax/100)*price);

    // Display the total in the corresponding field for the specific row
    document.getElementsByClassName('total')[rowIdx].value = total;

    updateGrandTotal();
    updateDueAmount(); // Call the function to update Due Amount
 }


 // Function to calculate and update the Grand Total
  function updateGrandTotal() {
    var grandTotal = 0;

    // Iterate through all rows and sum up the totals
    var totalFields = document.getElementsByClassName('total');
    for (var i = 0; i < totalFields.length; i++) {
        grandTotal += parseFloat(totalFields[i].value) || 0;
    }

    // Display the calculated grand total
    document.getElementById('grandTotal').value = grandTotal.toFixed(2);
 }


 // Function to calculate and update the Due Amount
   function updateDueAmount() {
    var grandTotal = parseFloat(document.getElementById('grandTotal').value) || 0;
    var paidAmount = parseFloat(document.getElementById('paidAmount').value) || 0;

    var dueAmount = grandTotal - paidAmount;
    if(dueAmount < 0){

    // Display the calculated due amount
    document.getElementById('dueAmount').value = 0;
    }
    else{
    // Display the calculated due amount
    document.getElementById('dueAmount').value = Math.abs(dueAmount.toFixed(2));
    }
 }

 // Attach an event listener to the 'Paid Amount' field for real-time updates
 document.getElementById('paidAmount').addEventListener('input', function () {
    updateDueAmount();
 });


  // Attach an event listener to the 'Paid Amount' field for real-time updates
 document.getElementById('paidAmount').addEventListener('input', function () {
    updateDueAmount();
    checkPaidAmountValidity(); // Call the function to check paid amount validity
 });


// Function to check the validity of the paid amount
function checkPaidAmountValidity() {
    var grandTotal = parseFloat(document.getElementById('grandTotal').value) || 0;
    var paidAmount = parseFloat(document.getElementById('paidAmount').value) || 0;

    var minValidPaidAmount = grandTotal * 0.5; // 50% of the grand total

    // Check if paidAmount is less than 50% of grandTotal
    if (paidAmount < minValidPaidAmount) {
        // Display the warning message
        document.getElementById('warningMessage').innerText = 'Your paid amount is less than 50% of the grand total. Please pay at least 50% of the grand total.';
        document.getElementById('warningMessage').style.display = 'block';
        // Disable the submit button
        document.getElementById('addNewInvoice').disabled = true;
    } else if (paidAmount > grandTotal && paidAmount % grandTotal !== 0) {
        // Display the warning message for invalid multiple of grand total
        document.getElementById('warningMessage').innerText = 'Your paid amount is greater than the grand total, but it must be a multiple of the grand total.';
        document.getElementById('warningMessage').style.display = 'block';
        // Disable the submit button
        document.getElementById('addNewInvoice').disabled = true;
    } else {
        // Hide the warning message
        document.getElementById('warningMessage').style.display = 'none';

        // Enable the submit button
        document.getElementById('addNewInvoice').disabled = false;
    }
}

  // Attach an event listener to each row's total field for real-time updates
  document.getElementById('subjectRowsContainer').addEventListener('input', function (event) {
    if (event.target.classList.contains('total')) {
        updateGrandTotal();
    }
  });
 
  function validatePaidAmount() {
        const paidAmountInput = document.getElementById("paidAmount");
        const paidAmountError = document.getElementById("paidAmountError");

        const regex = /^\d+$/;

        if (!regex.test(paidAmountInput.value)) {
            paidAmountError.textContent = "Paid Amount should contain only numbers.";
            return false;
        } else {
            paidAmountError.textContent = "";
            return true;
        }
    }

    </script>

    <!-- Subject Row Template -->
  <template id="subjectRowTemplate">
    <div class="row">
        <!-- Subject Name -->
        <div class="form-group col-md-3">
            <label for="subjectName">Subject :</label>
            <select class="form-control subject-name" name="subjectName[]" required>
                <?php foreach ($subjectnames as $subjectname) : ?>
                    <option value="<?php echo $subjectname; ?>"><?php echo $subjectname; ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Price -->
        <div class="form-group col-md-3">
            <label for="price">Price:</label>
            <input type="text" class="form-control price" name="price[]" required readonly>
        </div>

        <!-- Tax -->
        <div class="form-group col-md-3">
            <label for="tax">Tax:</label>
            <input type="text" class="form-control tax" name="tax[]" required readonly>
        </div>

        <!-- Total -->
        <div class="form-group col-md-3">
            <label for="total">Total:</label>
            <input type="text" class="form-control total" name="total[]" required readonly>
        </div>
    </div>
 </template>
</div>
</body>

</html>