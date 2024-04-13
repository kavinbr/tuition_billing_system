<?php
session_start();
include 'master.php';
include 'session_helper.php';


if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $password = md5($password);

    // Check if it's a master login
    if (masterLogin($conn, $username, $password)) {
        $_SESSION['username'] = $username; // Set the username in the session
        header("Location: master_dashboard.php");
        exit();
    }

    // Check if it's an admin login
    $adminLoginResult = adminLogin($conn, $username, $password);
    if ($adminLoginResult['success']) {
        $_SESSION['username'] = $username; // Set the username in the session
        $_SESSION['branch_id'] = $adminLoginResult['branch_id'];
        header("Location: branch_admin_dashboard.php");
        exit();
    } else {
        //echo "Login failed";
    }

    $loginError = "Invalid username or password";
}
// ...

// ...

// // Fetch branches for dropdown
// $branches = getMasterBranches($conn);

// // Fetch Master dashboard data
// $dashboardData = getMasterDashboardData($conn);

// // Check if Master is logged in, otherwise redirect to login page
// if (!isset($_SESSION['branch_id'])) {
//     // Redirect to login if branch_id is not set
//     header("Location: master_login.php");
//     exit();
// }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="./style/ms_login.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  
</head>
<body >

<!-- Modal for displaying login error -->
<div class="modal fade" id="loginErrorModal" tabindex="-1" role="dialog" aria-labelledby="loginErrorModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="loginErrorModalLabel">Login Error</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="text-danger" id="loginErrorMessage"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>



    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white text-center">
                        <h1 class="mb-0">Login</h1>
                    </div>
                    <div class="card-body">
                        <!-- Common Login Form -->
                        <form action="master_login.php" method="post">
                            <div class="form-group">
                                <label for="username">Username:</label>
                                <input type="text" class="form-control" name="username" required>
                            </div>

                            <div class="form-group">
                                <label for="password">Password:</label>
                                <input type="password" class="form-control" name="password" required>
                            </div>

                            <button type="submit" class="btn btn-primary btn-block" name="login">Login</button>
                        </form>

                      
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
    <?php if (isset($loginError)) : ?>
        $(document).ready(function () {
            $('#loginErrorModal').modal('show');
            $('#loginErrorMessage').text('<?php echo $loginError; ?>');
        });
    <?php endif; ?>
</script>
</body>
</html>
