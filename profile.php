<?php
ob_start();
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include 'sesion-check/check.php';
include 'plugins/bootstrap.html';
//include 'database/db.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Profile - Budget Master</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/material-design-iconic-font/2.2.0/css/material-design-iconic-font.min.css" integrity="sha256-3sPp8BkKUE7QyPSl6VfBByBroQbKxKG7tsusY2mhbVY=" crossorigin="anonymous" />
    <style>
       @import "https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700";

        body{
            background: #f7f7ff;
            margin-top:20px;
        }
        .card {
            position: relative;
            display: flex;
            flex-direction: column;
            min-width: 0;
            word-wrap: break-word;
            background-color: #fff;
            background-clip: border-box;
            border-radius: 35px;
            border: 0 solid transparent;
            border-radius: .25rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 6px 0 rgb(218 218 253 / 65%), 0 2px 6px 0 rgb(206 206 238 / 54%);
        }
        .me-2 {
            margin-right: .5rem!important;
        }

        p {
            font-family: 'Poppins', sans-serif;
            font-size: 1.1em;
            font-weight: 300;
            line-height: 1.7em;
            color: #999;
        }

        a,
        a:hover,
        a:focus {
            color: inherit;
            text-decoration: none;
            transition: all 0.3s;
        }

        .navbar {
            padding: 0px 0px;
            border: none;
            border-radius: 0;
            margin-bottom: 0px;
            box-shadow: 1px 1px 3px rgba(0, 0, 0, 0.1);
        }

        .wrapper {
            display: flex;
            align-items: stretch;
        }

        #main {
            width: 100%;
            padding: 20px;
            min-height: 100vh;
            transition: all 0.3s;
        }

        .moveos-image {
            width: 50%;
        }

        .welcome-image {
            width: 84%;
        }

        .chart-table-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chart-container {
            max-width: 50%;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: -10px;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: #f5f5f5;
            width: 50%;
            padding: 15px;
            border: 1px solid #888;
        }

        .close {
            color: #aaaaaa;
            position: absolute;
            right: 15px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover,
        .close:focus {
            color: #000;
            text-decoration: none;
            cursor: pointer;
        }

        .closed {
            color: #aaaaaa;
            position: absolute;
            right: 15px;
            font-size: 20px;
            font-weight: bold;
            cursor: pointer;
        }

        .closed:hover,
        .closed:focus {
            color: #000;
            text-decoration: none;
            cursor: pointer;
        }

        .admindashboard-image {

            width: 80%;
            align-items: center;
        }

        .moveos-image {
            width: 50%;
        }

        .require {
            color: #666;
        }
        label small {
            color: #999;
            font-weight: normal;
        }

        .button3 {
            right: auto;
            top: auto;
        }

        table {
            table-layout: fixed;
            border-collapse: collapse;
            text-align: center;
        }

        td {
            overflow: hidden;
            text-overflow: ellipsis;
            word-wrap: break-word;
        }

        @media only screen and (max-width: 480px) {
            .tablemobile {
                overflow-x: auto;
                display: block;
            }
        }

        tr {
            background-color: #f0f0f0;
        }
    </style>
<?php
$username = $_SESSION['username'];
$email = $_SESSION['email'];
?>
</head>
<body>
<?php
include 'navbar/sidebar_old.php';
$sql = "SELECT * FROM users WHERE username = '$username'";
$result = mysqli_query($conn, $sql);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $role = $row['role'];
        $register_date = $row['register_date'];
        $email = $row['email'];
    }
} else {
    echo "Error: " . mysqli_error($conn);
}
$register_date_formated = date("d-M-Y", strtotime($register_date));
?>

<div class="wrapper">
   <div id="main">
        <div class="container">
            <div class="main-body">
                <div class="row">
                    <div class="col-lg-4">
                        <form action="profile.php" method="post" enctype="multipart/form-data">
                            <div class="card shadow-lg">
                                <div class="card-body">
                                    <div class="d-flex flex-column align-items-center text-center">
                                        <input class="btn btn-transparent btn-sm rounded-pill" type="file" name="profile_photo" accept="image/*">
                                        <?php
                                        $query = "SELECT * FROM users WHERE username = ?";
                                        $stmt = mysqli_prepare($conn, $query);
                                        mysqli_stmt_bind_param($stmt, 's', $username);
                                        mysqli_stmt_execute($stmt);
                                        $result = mysqli_stmt_get_result($stmt);
                                        if (!$result) {
                                            echo "Error fetching data from users table: " . mysqli_error($conn);
                                        } else {
                                            // Fetch the users details
                                            $result_user = mysqli_fetch_assoc($result);
                                            $photoPath = $result_user['profile_photo'];
                                            // Check if the user has uploaded a photo
                                            if (!empty($photoPath) && file_exists($photoPath)) {
                                                echo '<img src="' . $photoPath . '" alt="Profile Photo" class="rounded-circle p-1 bg-primary" width="110">';
                                            } else {
                                                // If no photo uploaded or file not found, display the default photo
                                                echo '<img src="https://www.pngitem.com/pimgs/m/130-1300253_female-user-icon-png-download-user-image-color.png" alt="Admin" class="rounded-circle p-1 bg-primary" width="110">';
                                            }
                                        }
                                        ?>
                                        <div class="mt-1">
                                            <h4><?php echo $username?></h4>
                                            <p class="text-secondary mb-1"><?php echo $register_date_formated; ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm rounded-pill">Upload Photo</button>
                        </form>

                    </div>
                    <div class="col-lg-8">
                        <div class="card card-from shadow-lg mb-3">
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-sm-3">
                                        <h6 class="mb-0">Numele tau:</h6>
                                    </div>
                                    <div class="col-sm-9 text-secondary">
                                        <input type="text" class="form-control" value="<?php echo $username?>" readonly>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-sm-3">
                                        <h6 class="mb-0">Email:</h6>
                                    </div>
                                    <div class="col-sm-9 text-secondary">
                                        <input type="text" class="form-control" value="<?php echo $email;?>" readonly>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-sm-3">
                                        <h6 class="mb-0">Data inregistrare:</h6>
                                    </div>
                                    <div class="col-sm-9 text-secondary">
                                        <input type="text" class="form-control" value="<?php echo $register_date_formated ?>" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <hr>
            <?php



            // Query inventory
            $inventoryQuery = "SELECT * FROM login_logs WHERE username = '$username' ORDER BY login_date DESC LIMIT 10";
            $inventoryResult = mysqli_query($conn, $inventoryQuery);

            if (mysqli_num_rows($inventoryResult) > 0) {
                echo '<h3>Ultimele 10 inregistari<h3><hr>';
                echo "<table class='table table-hover tablemobile shadow-lg'>";
                echo "<tr>";
                echo "<th>IP</th>";
                echo "<th>Sistem operare</th>";
                echo "<th>Browser</th>";
                echo "<th>Device</th>";
                echo "<th>Data login</th>";
                echo "</tr>";

                while ($item = mysqli_fetch_assoc($inventoryResult)) {
                    echo "<tr>";
                    echo "<td>" . $item['ip'] . "</td>";
                    echo "<td>" . $item['os'] . "</td>";
                    echo "<td>" . $item['browser'] . "</td>";
                    echo "<td>" . $item['device'] . "</td>";
                    $date = date("d-m-Y", strtotime($item['login_date']));
                    echo "<td>" . $date . "</td>";
                    echo "</tr>";
                }

                echo "</table>";
            } else {
                echo "Nu au fost gasite inregistrari.";
            }

            // Check if a file has been uploaded
            if(isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
                // Define a directory to store uploaded photos
                $uploadDir = 'profile-photos/';

                // Generate a unique filename for the uploaded photo
                $filename = uniqid() . '_' . $_FILES['profile_photo']['name'];

                // Move the uploaded file to the designated directory
                if(move_uploaded_file($_FILES['profile_photo']['tmp_name'], $uploadDir . $filename)) {
                    // Save the file path in the database
                    $photoPath = $uploadDir . $filename;
                    // Update the user's profile with the photo path
                    $updateQuery = "UPDATE users SET profile_photo = ? WHERE username = ?";
                    $stmt = mysqli_prepare($conn, $updateQuery);
                    mysqli_stmt_bind_param($stmt, 'ss', $photoPath, $username);
                    mysqli_stmt_execute($stmt);
                } else {
                    echo "Error uploading photo.";
                }
            }
            mysqli_close($conn);
            ?>
        </div>
        <br><hr>
</body>
<?php include 'footer/footer.html';?>
</html>