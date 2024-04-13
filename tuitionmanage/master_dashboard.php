<?php
include 'master.php';
include 'update_invoices.php';
// Function to check if an admin already exists with the given username
function isUsernameExists($conn, $adminUsername) {
    $query = "SELECT COUNT(*) FROM branch_admins WHERE username = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $adminUsername);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    return $count > 0;
}

function isPasswordExists($conn, $adminPassword) {
    // Note: Storing passwords as MD5 hashes is not recommended for security reasons.
    // Use a stronger and more secure hashing algorithm like bcrypt.

    $hashedPassword = md5($adminPassword);

    $query = "SELECT COUNT(*) FROM branch_admins WHERE password = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $hashedPassword);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    return $count > 0;
}
if (isset($_POST['addAdmin'])) {
    $adminUsername = $_POST['adminUsername'];
    $adminPassword = $_POST['adminPassword'];
    $adminBranch = $_POST['adminBranch'];

    // Check if the username and password already exist
    if (isUsernameExists($conn, $adminUsername)) {
        echo '<script>alert("Admin with this username already exists!");</script>';
    } else {
        // If the username doesn't exist, proceed with checking the password
        if (isPasswordExists($conn, $adminPassword)) {
            echo '<script>alert("Admin with this password already exists!");</script>';
        } else {
            // If the password doesn't exist, proceed with adding the admin
            $adminPassword = md5($adminPassword);
            addAdmin($conn, $adminUsername, $adminPassword, $adminBranch);
        }
    }
}




// Function to check if a branch is already assigned to an admin
function isBranchAssignedToAdmin($conn, $branchId) {
    $query = "SELECT COUNT(*) FROM branch_admins WHERE branch_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $branchId);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    return $count > 0;
}

// Function to add an admin to the branch_admins table
function addAdmin($conn, $adminUsername, $adminPassword, $adminBranch) {
    $query = "INSERT INTO branch_admins (username, password, branch_id) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sss", $adminUsername, $adminPassword, $adminBranch);

    if ($stmt->execute()) {
        echo '<script>alert("Admin added successfully!");</script>';
    } else {
        echo '<script>alert("Error adding admin: ' . $stmt->error . '");</script>';
    }

    $stmt->close();
}



if (isset($_POST['addSubject'])) {
    $subjectName = $_POST['subjectName'];
    $fees = $_POST['fees'];
    $tax = $_POST['tax'];
    $selectedBranches = $_POST['branches'];

//     echo "selectedBranches: ";
// print_r($selectedBranches);

// echo "subject". $subjectName;
// echo "fees". $fees;
// echo "tax". $tax;

    // Check if at least one branch is selected
    if (empty($selectedBranches)) {
        // Handle the case where no branches are selected
        echo "Please select at least one branch for the subject.";
        exit();
    }

    $subjectExists = false;
foreach ($selectedBranches as $branchId) {
    if (isSubjectExists($conn, $subjectName, $branchId)) {
        // Subject already exists in this branch
        $subjectExists = true;
        break; // Exit the loop if subject already exists
    }
}

// If validation passed, call addSubject function
if (!$subjectExists) {
    addSubject($conn, $subjectName, $fees, $tax, $selectedBranches);
} else {

    $already = "subject already exists!!";
    // echo '<script>showSubjectExistsError();</script>';
}
    
   // addSubject($conn, $subjectName, $fees, $tax, $selectedBranches);

}
// Function to check if a subject already exists in a specific branch
function isSubjectExists($conn, $subjectName, $branchId) {
    $query = "SELECT COUNT(*) FROM subjects WHERE subject_name = ? AND branch_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $subjectName, $branchId);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    return $count > 0;
}


// Function to get the student count for a specific branch
function getBranchStudentCount($conn, $branchId) {
    // Use prepared statements to avoid SQL injection
    $query = "SELECT COUNT(*) FROM branch_students WHERE branch_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $branchId);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    return $count;
}

if (isset($_POST['logout'])) {
    // Perform any additional logout actions if needed
    // For example, destroying the session
    session_destroy();

    // Redirect to the login page after logging out
    header("Location: master_login.php");
    exit();
}

// Check if Master is logged in, otherwise redirect to login page
// You should implement a session-based login system for security

// Fetch branches for dropdown
$branches = getMasterBranches($conn);

// // Fetch Master dashboard data
// $dashboardData = getMasterDashboardData($conn);

// Get the count of total subjects
$totalSubjectsCount = $conn->query("SELECT COUNT(*) FROM subjects")->fetch_row()[0];

//Get the count of total subjects
$totalStudentCount = $conn->query("SELECT COUNT(*) FROM branch_students")->fetch_row()[0];

// Create Branch
function createBranch($conn, $branchName) {
    // Check if the branch name already exists
    $checkExistingBranch = "SELECT * FROM branches WHERE branch_name = '$branchName'";
    $result = $conn->query($checkExistingBranch);

    if ($result->num_rows > 0) {
        echo '<script>alert("Branch already exists!");</script>';
    }
   else{
    // Branch name doesn't exist, proceed with inserting
    $sql = "INSERT INTO branches (branch_name) VALUES ('$branchName')";
    $conn->query($sql);

   }
}

if (isset($_POST['createBranch'])) {
    $branchName = $_POST['branchName'];
    $result = createBranch($conn, $branchName);
    header("Location: master_dashboard.php");

}
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Dashboard</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="masterstyles.css"> <!-- Add your custom styles if needed -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="./style/master_dashboard.css">
    <style>

body {
    background-image: url('./assests/white.jpg');
    background-size: cover;
    background-repeat: no-repeat;
    background-position: top center;
    width: auto;
    overflow-y: scroll; /* Add vertical scrollbar */
    margin: 0; /* Remove default body margin */
    padding: 0; /* Remove default body padding */
}


.modal-content {
    animation: fadeIn 0.5s ease-in-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-50px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Add hover effect to top buttons */
.top-buttons button:hover {
    transform: scale(1.05);
    transition: transform 0.3s ease-in-out;
}

/* Add transition to badge */
.card-title .badge {
    transition: background-color 0.3s ease-in-out;
}

/* Add transition to modal close button */
.close {
    transition: color 0.3s ease-in-out;
}

/* Add hover effect to cards */
.card:hover {
    transform: scale(1.05);
    transition: transform 0.3s ease-in-out;
}

/* Add transition to card title */
.card-title {
    transition: color 0.3s ease-in-out;
}

/* Add hover effect to links inside cards */
.card a:hover {
    color: #17a2b8; /* Change the color to your preference */
}

/* Add animation to the logout button */
.btn-outline-danger.btn-sm:hover {
    animation: shake 0.5s ease-in-out;
}

@keyframes shake {
    0% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    50% { transform: translateX(5px); }
    75% { transform: translateX(-5px); }
    100% { transform: translateX(0); }
}

.custom-btn {
    width: 10%;
    margin-bottom: 10px;
}

.top-buttons {
    display: flex;
    justify-content: center;
    margin-bottom: 30px;
}

.top-buttons button {
    margin: 0 10px; /* Add space between buttons */
    padding: 15px 30px; /* Adjust these values to control height and width */
    font-size: 16px; /* You can adjust font size based on your preference */
}

.logout-button {
    margin-left: 10px; /* Adjust the margin as needed */
}

.logout-button button {
    padding: 15px 30px; /* Adjust the padding for the desired button size */
}

.top-buttons .btn {
    padding: 15px 30px; /* You can adjust padding based on your preference */
    font-size: 16px; /* You can adjust font size based on your preference */
}
.top-buttons .btn {
    padding: 15px 30px; /* Adjust padding for height and width */
    font-size: 16px; /* Adjust font size */
}
.btn-lg {
    position: relative;
    overflow: hidden;
    background-color: transparent;
    color: #000000;
    font-size: 20px;
    font-weight: bold;
    transition: color 0.3s ease;
}

.btn-lg:before {
    content: "";
    position: absolute;
    bottom: 0;
    right: 0;
    width: 0;
    height: 0;
    background-color: #3498db;
    transition: width 0.3s ease, height 0.3s ease;
    z-index: -1; /* Move the pseudo-element behind the content */
}

.btn-lg:hover:before {
    width: 100%;
    height: 100%;
}

.btn-lg span {
    position: relative;
    z-index: 2; /* Ensure the text appears on top */
    display: block;
}

.btn-lg:hover span {
    color: #f9f9f9;
    font-weight: bold;
}
.btn-blue {
    border: 3px solid #3498db; /* Increase border width */
}

.btn-green {
    border: 3px solid #2ecc71;
}

.btn-yellow {
    border: 3px solid #f39c12;
}

.btn-red {
    border: 3px solid #e74c3c;
}

.btn-purple {
    border: 3px solid #9b59b6;
}
        </style>
</head>

<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-6 offset-md-6 text-right">
                <form action="" method="post">
                    <button type="submit" class="btn btn-outline-danger btn-sm" name="logout">Logout</button>
                </form>
            </div>
        </div>

        <h1 class="text-center mb-4">Welcome, Master!</h1>


    <!-- Modal for displaying login error -->
<div class="modal fade" id="subjectErrorModal" tabindex="-1" role="dialog" aria-labelledby="subjectErrorModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="subjectErrorModalLabel">subject exists</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="text-danger" id="subjectErrorMessage"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>    

    <!-- Modal for displaying login error -->
    <div class="modal fade" id="branchErrorModal" tabindex="-1" role="dialog" aria-labelledby="branchErrorModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="branchErrorModalLabel">Branch Name already exists!!</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="text-danger" id="branchErrorMessage"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>  

<!-- Top Buttons Row -->
<!-- Top Buttons Row -->

   


<div class="top-buttons text-center">
    <button class="btn btn-info my-1 btn-text btn-lg btn-blue" onclick="viewBranchSubjects()">View Subjects</button>
    <button class="btn btn-primary my-1 btn-text btn-lg btn-green" onclick="toggleFormVisibility()">Add Subject</button>
    <button class="btn btn-success my-1 btn-text btn-lg btn-yellow" onclick="toggleBranchFormVisibility()">Add Branch</button>
    <button class="btn btn-warning  my-1 btn-text btn-lg btn-red" onclick="toggleAdminFormVisibility()">Add Admin</button>
    <div class="row">
        <div class="col-md-8">
            <!-- Change the form action in master_dashboard.php -->
            <form action="view_invoice_report.php" method="get">
                <div class="form-group">
                    <label for="branch">Select Branch:</label>
                    <select class="form-control" name="branch" required style="width: 100%;">
                        <?php foreach ($branches as $branch) : ?>
                            <option value="<?php echo $branch['branch_name']; ?>"><?php echo $branch['branch_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-info my-1 btn-text" style="width: 200%;">View Invoice Report</button>
            </div>
        </form>
    </div>
</div>


<div class="row mb-4">
<div class="col-md-3">
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Total Students</h5>
            <a href="view_student_details.php" class="btn btn-link stretched-link">
                <?php echo $totalStudentCount; ?>
            </a>
            <?php foreach ($branches as $branch) : ?>
                <div onclick="showBranchStudents('<?php echo $branch['branch_id']; ?>')">
                    <h6><?php echo $branch['branch_name'] . ' :' . getBranchStudentCount($conn, $branch['branch_id']); ?></h6>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="col-md-3">
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Total Subjects and Tax</h5>
            <a href="view_subject.php" class="btn btn-link stretched-link">
                <?php echo $totalSubjectsCount; ?>
            </a>
        </div>
    </div>
</div>
  
<!-- Inside the "Add Subject Form Modal" -->
<div id="addSubjectModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="toggleFormVisibility()">&times;</span>
        <form action="master_dashboard.php" method="post">
            <div class="form-group">
                <label for="subjectName">Subject Name:</label>
                <input type="text" class="form-control" name="subjectName" id="subjectName" required>
                <p class="text-danger" id="subjectError"></p>
            </div>

            <div class="form-group">
                <label for="fees">Fees:</label>
                <input type="number" class="form-control" name="fees" id="fees" required>
                <p class="text-danger" id="feesError"></p>
            </div>

            <div class="form-group">
                <label for="tax">Tax:</label>
                <input type="number" class="form-control" name="tax" id="tax" required>
                <p class="text-danger" id="taxError"></p>
            </div>

            <div class="form-group">
                <label>Select Branch(es):</label><br>
                <!-- Add the necessary code for branches selection -->

                <!-- For demonstration purposes, assuming $branches is an array containing branch information -->
                <?php foreach ($branches as $branch) : ?>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="branches[]"
                            value="<?php echo $branch['branch_id']; ?>"
                            id="branch_<?php echo $branch['branch_id']; ?>">
                        <label class="form-check-label"
                            for="branch_<?php echo $branch['branch_id']; ?>"><?php echo $branch['branch_name']; ?></label>
                    </div>
                <?php endforeach; ?>
            </div>

            <button type="submit" class="btn btn-primary btn-block" name="addSubject" id="addSubjectBtn" disabled>Add Subject</button>
        </form>
    </div>
</div>

        <div class="container mt-4">
            <div class="row">
                <!-- Create a card for each branch -->
                <?php foreach ($branches as $branch) : ?>
                    <div class="col-md-3 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <?php echo $branch['branch_name']; ?>
                                    <span
                                        class="badge badge-secondary"><?php echo count(getBranchSubjects($conn, $branch['branch_id'])); ?></span>
                                </h5>
                                <!-- List subjects under the current branch -->
                                <?php $branchSubjects = getBranchSubjects($conn, $branch['branch_id']); ?>
                                <ul class="list-group">
                                    <?php foreach ($branchSubjects as $subject) : ?>
                                        <li class="list-group-item"><?php echo $subject['subject_name']; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Add Branch Form Modal -->
        <div id="createBranchModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="toggleBranchFormVisibility()">&times;</span>
                <form action="master_dashboard.php" method="post">
                    <div class="form-group">
                        <label for="branchName">Branch Name:</label>
                        <input type="text" class="form-control" name="branchName" required>
                    </div>
                    <button type="submit" class="btn btn-success btn-block" name="createBranch">Create Branch</button>
                </form>
            </div>
        </div>

       
 <!-- Inside the "Add Admin Form Modal" -->
 <div class="modal" id="addAdminModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <!-- Modal Header -->
                <div class="modal-header">
                    <h4 class="modal-title">Add Admin</h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>

                <!-- Modal Body -->
                <div class="modal-body">
                    <form action="master_dashboard.php" method="post">
                        <!-- Your form fields go here -->
                        <div class="form-group">
                            <label for="adminUsername">Username:</label>
                            <input type="text" class="form-control" name="adminUsername" required>
                        </div>
                        <div class="form-group">
                <label for="adminPassword">Password:</label>
                <input type="password" class="form-control" name="adminPassword" required>
            </div>

            <div class="form-group">
                <label for="adminBranch">Select Branch:</label>
                <select class="form-control" name="adminBranch" required>
                    <?php foreach ($branches as $branch) : ?>
                        <?php if (!isBranchAssignedToAdmin($conn, $branch['branch_id'])) : ?>
                            <option value="<?php echo $branch['branch_id']; ?>"><?php echo $branch['branch_name']; ?></option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
                        <!-- ... (other form fields) -->
                        <button type="submit" class="btn btn-warning btn-block" name="addAdmin">Add Admin</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
        <?php if (isset($loginError)) : ?>
            <p class="mt-4 text-danger"><?php echo $loginError; ?></p>
        <?php endif; ?>
    </div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
           function toggleFormVisibility() {
        var modal = document.getElementById("addSubjectModal");

        if (modal.style.visibility === "hidden" || modal.style.visibility === "") {
            modal.style.visibility = "visible";
            modal.style.opacity = 1;
        } else {
            modal.style.visibility = "hidden";
            modal.style.opacity = 0;
        }
    }

    function toggleBranchFormVisibility() {
        var modal = document.getElementById("createBranchModal");

        if (modal.style.visibility === "hidden" || modal.style.visibility === "") {
            modal.style.visibility = "visible";
            modal.style.opacity = 1;
        } else {
            modal.style.visibility = "hidden";
            modal.style.opacity = 0;
        }
    }

    function viewBranchSubjects() {
        var branchName = prompt("Enter Branch Name:");

        if (branchName) {
            // Use AJAX to fetch the branch ID corresponding to the entered branch name
            $.ajax({
                url: 'get_branch_id.php',
                type: 'POST',
                data: { branchName: branchName },
                success: function (data) {
                    console.log('Branch ID:', data);
                    window.location.href = "http://localhost/tuitionmanage/view_branch_subjects.php?branch_id=" + data;
                },
                error: function (xhr, status, error) {
                    console.error("Error fetching branch ID: " + error);
                }
            });
        }
    }

    // Document ready function
    $(document).ready(function () {
        // Initialize Bootstrap's modal
        $('#addAdminModal').modal({ show: false });
    });

    function toggleAdminFormVisibility() {
        // Show/hide the modal using Bootstrap's modal function
        $('#addAdminModal').modal('toggle');
    }

        function validateForm() {
        var subjectName = document.getElementById('subjectName').value;
        var fees = document.getElementById('fees').value;
        var tax = document.getElementById('tax').value;

        // Regex for checking if subjectName contains only letters
        var lettersRegex = /^[a-zA-Z]+$/;

        // Regex for checking if fees and tax contain only numbers
        var numbersRegex = /^\d+$/;

        // Error messages
        var subjectError = "";
        var feesError = "";
        var taxError = "";

        // Validate Subject Name
        if (!lettersRegex.test(subjectName)) {
            subjectError = "Subject Name should contain only letters.";
        }

        // Validate Fees
        if (!numbersRegex.test(fees)) {
            feesError = "Fees should contain only numbers.";
        }

        // Validate Tax
        if (!numbersRegex.test(tax)) {
            taxError = "Tax should contain only numbers.";
        }

        // Display error messages
        document.getElementById('subjectError').innerHTML = subjectError;
        document.getElementById('feesError').innerHTML = feesError;
        document.getElementById('taxError').innerHTML = taxError;

        // Check if all fields are valid to enable/disable the Add Subject button
        var branchesChecked = document.querySelectorAll('input[name="branches[]"]:checked').length > 0;

        if (subjectError === "" && feesError === "" && taxError === "" && branchesChecked) {
            document.getElementById('addSubjectBtn').disabled = false;
        } else {
            document.getElementById('addSubjectBtn').disabled = true;
        }
    }

    // Add event listeners to the form fields to trigger validation on input change
    document.getElementById('subjectName').addEventListener('input', validateForm);
    document.getElementById('fees').addEventListener('input', validateForm);
    document.getElementById('tax').addEventListener('input', validateForm);

    // Add event listener for branch checkboxes
    var branchCheckboxes = document.querySelectorAll('input[name="branches[]"]');
    branchCheckboxes.forEach(function (checkbox) {
        checkbox.addEventListener('change', validateForm);
    });


    <?php if (isset($already)) : ?>
        $(document).ready(function () {
            $('#subjectErrorModal').modal('show');
            $('#subjectErrorMessage').text('<?php echo $already; ?>');
        });
    <?php endif; ?>

    <?php
    // Check if the result is an error message for creating a branch
    if (isset($result) && is_string($result) && strpos($result, 'Error') !== false) {
        echo '$(document).ready(function () {';
        echo '$("#branchErrorModal").modal("show");';
        echo '$("#branchErrorMessage").text("' . $result . '");';
        echo '});';
    }
    ?>
    </script>
</body>

</html>
