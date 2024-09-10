<?php
// Database connection
$host = 'localhost'; // Database host
$db   = 'your_database'; // Database name
$user = 'your_username'; // Database username
$pass = 'your_password'; // Database password

// Connect to database
$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle file upload form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reserved_quote_Id'])) {
    // Get the file ID from the form (hidden input field)
    $fileId = $_POST['reserved_quote_Id'];

    // Get the uploaded file
    $file = $_FILES['0.filename'];

    // File validation
    if ($file['error'] === UPLOAD_ERR_OK) {
        // Extract file information
        $fileTmpPath = $file['tmp_name'];
        $fileName = $file['name'];
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);

        // Allowed file extensions
        $allowedExtensions = ['jpg', 'png', 'pdf', 'docx', 'txt'];

        // Check if the file has a valid extension
        if (in_array(strtolower($fileExtension), $allowedExtensions)) {
            // Generate new file name using fileId as the base
            $newFileName = $fileId . '.' . $fileExtension;

            // Define the upload directory
            $uploadDir = 'uploads/';

            // Create directory if it doesn't exist
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            // Define the upload path
            $uploadFilePath = $uploadDir . $newFileName;

            // Move the file to the server
            if (move_uploaded_file($fileTmpPath, $uploadFilePath)) {
                // File successfully uploaded, now update the database
                $filePath = $conn->real_escape_string($uploadFilePath);

                // Check if the file ID exists in the database
                $checkQuery = "SELECT id FROM fileUploads WHERE id = ?";
                $stmt = $conn->prepare($checkQuery);
                $stmt->bind_param('i', $fileId);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    // Update the existing file path
                    $updateQuery = "UPDATE fileUploads SET file_path = ? WHERE id = ?";
                    $updateStmt = $conn->prepare($updateQuery);
                    $updateStmt->bind_param('si', $filePath, $fileId);
                    $updateStmt->execute();
                } else {
                    // Insert a new record into the fileUploads table
                    $insertQuery = "INSERT INTO fileUploads (id, file_id, file_path) VALUES (?, ?, ?)";
                    $insertStmt = $conn->prepare($insertQuery);
                    $insertStmt->bind_param('iis', $fileId, $fileId, $filePath);
                    $insertStmt->execute();
                }

                echo "<div class='alert alert-success'>File uploaded and database updated successfully!</div>";
            } else {
                echo "<div class='alert alert-danger'>Error moving the file!</div>";
            }
        } else {
            echo "<div class='alert alert-danger'>Invalid file extension!</div>";
        }
    } else {
        echo "<div class='alert alert-danger'>Error uploading file!</div>";
    }
}

// Handle file deletion (optional functionality)
if (isset($_GET['delete_id'])) {
    $deleteId = $_GET['delete_id'];

    // Get the file path to delete
    $fileQuery = "SELECT file_path FROM fileUploads WHERE id = ?";
    $stmt = $conn->prepare($fileQuery);
    $stmt->bind_param('i', $deleteId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $file = $result->fetch_assoc();

        // Delete the file from the server
        if (file_exists($file['file_path'])) {
            unlink($file['file_path']);
        }

        // Delete the file record from the database
        $deleteQuery = "DELETE FROM fileUploads WHERE id = ?";
        $deleteStmt = $conn->prepare($deleteQuery);
        $deleteStmt->bind_param('i', $deleteId);
        $deleteStmt->execute();

        echo "<div class='alert alert-success'>File deleted successfully!</div>";
    }
}

// Fetch all uploaded files for display (optional)
$query = "SELECT id, file_id, file_path FROM fileUploads";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Connecting COMESA to Global Markets</title>
    <meta charset="UTF-8">
    <meta name="copyright" content="Copyright 2004 - Julius Magala, Comesatradehub">
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    
    <!-- Additional Styles -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/vanillajs-datepicker@1.2.0/dist/css/datepicker.min.css">
    <link href="zz_default_1.css" type="text/css" rel="stylesheet">

    <style>
        body {
            padding: 10px 0;
        }
        .table-container {
            padding: 10px 0;
        }
        .table-container td, .table-container th {
            border: 2px solid #FFF;
            padding-left: 2px;
            font-weight: normal;
        }
        .table-container tr td:last-child, .table-container tr th:last-child {
            padding: 0 5px;
        }
        hr {
            border-top: 1px solid #C68E17;
        }
        .btn-primary {
            color: #fff;
            background-color: #ff6600;
            border-color: #ff6600;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- File Upload Form -->
        <div class="row">
            <div class="col-lg-12 text-center">
                <font size="3" color="black"><br>PROCESS RESERVED QUOTES</font>
            </div>
        </div>
        <hr align="center" noshade="noshade" size="1" width="100%">
        
        <form action="file_manager.php" method="POST" enctype="multipart/form-data">
            <div class="table-container">        
                <table width="100%" align="center">
                    <thead>
                        <tr bgcolor="#ADA96E">
                            <th align="left">
                                <font color="#FFFFFF">Id</font>
                            </th>
                            <th align="left">
                                <font color="#FFFFFF">Upload Supplier Invoice / Quote</font>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td bgcolor="#eeeecc" valign="top">
                                <input type="hidden" name="reserved_quote_Id" value="4">4
                            </td>
                            <td valign="top" bgcolor="#eeeecc">
                                <input class="form-control" name="0.filename" id="input-file" type="file" accept="image/*">
                            </td>
                        </tr>
                        <tr>
                            <td bgcolor="#eeeecc" valign="top">
                                <input type="hidden" name="reserved_quote_Id" value="3">3
                            </td>
                            <td valign="top" bgcolor="#eeeecc">
                                <input class="form-control" name="1.filename" id="input-file" type="file" accept="image/*">
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <table width="100%" align="center">
                    <tr>
                        <td colspan="8" height="10">&nbsp;</td>
                    </tr>
                    <tr>
                        <td class="hidden-xs" colspan="3">&nbsp;</td>
                        <td align="right">
                            <button type="submit" class="btn btn-primary btn-sm"><b>PROCESS RESERVED QUOTE</b></button>
                        </td>
                    </tr>
                </table>
            </div>
        </form>

        <!-- Display Uploaded Files -->
        <div class="table-container">
            <table width="100%" align="center">
                <thead>
                    <tr bgcolor="#ADA96E">
                        <th align="left"><font color="#FFFFFF">Id</font></th>
                        <th align="left"><font color="#FFFFFF">File Path</font></th>
                        <th align="left"><font color="#FFFFFF">Action</font></th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()) { ?>
                    <tr>
                        <td bgcolor="#eeeecc"><?php echo $row['id']; ?></td>
                        <td bgcolor="#eeeecc"><a href="<?php echo $row['file_path']; ?>" target="_blank"><?php echo basename($row['file_path']); ?></a></td>
                        <td bgcolor="#eeeecc">
                            <a href="file_manager.php?delete_id=<?php echo $row['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this file?');">Delete</a>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/js/bootstrap.min.js"></script>
</body>
</html>

<?php $conn->close(); ?>
