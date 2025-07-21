<?php
session_start();

// Configuration
$user_password = "KakaliP"; // User password
$admin_password = "Admin@123"; // Admin password
$upload_dir = "uploads/"; // Directory to store PDFs

// Handle login
if (isset($_POST['password'])) {
    if ($_POST['password'] === $user_password) {
        $_SESSION['authenticated'] = true;
        $_SESSION['role'] = 'user';
    } elseif ($_POST['password'] === $admin_password) {
        $_SESSION['authenticated'] = true;
        $_SESSION['role'] = 'admin';
    }
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// Handle file upload (admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pdf_file']) && 
    isset($_SESSION['authenticated']) && $_SESSION['role'] === 'admin') {
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
    
    $target_file = $upload_dir . basename($_FILES["pdf_file"]["name"]);
    $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    
    if ($file_type === "pdf" && move_uploaded_file($_FILES["pdf_file"]["tmp_name"], $target_file)) {
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    }
}

// Handle file deletion (admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_file']) && 
    isset($_SESSION['authenticated']) && $_SESSION['role'] === 'admin') {
    $file_to_delete = $upload_dir . basename($_POST['delete_file']);
    if (file_exists($file_to_delete) && unlink($file_to_delete)) {
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    }
}

// Check authentication
$authenticated = isset($_SESSION['authenticated']) && $_SESSION['authenticated'];
$is_admin = $authenticated && $_SESSION['role'] === 'admin';

// Get PDF files
$pdf_files = [];
if (is_dir($upload_dir)) {
    $files = scandir($upload_dir);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'pdf') {
            $pdf_files[] = $file;
        }
    }
    rsort($pdf_files); // Newest first
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF Share Portal</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        body {
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }
        .container {
            max-width: 100%;
            padding: 20px;
            margin: 0 auto;
        }
        .login-box {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin: 50px auto;
            max-width: 400px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        .logo {
            margin-bottom: 25px;
        }
        .logo i {
            font-size: 50px;
            color: #e74c3c;
            margin-bottom: 10px;
        }
        .logo h1 {
            color: #2c3e50;
            font-size: 24px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        input[type="password"], input[type="file"] {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        .btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #2980b9;
        }
        .upload-btn {
            background: #2ecc71;
        }
        .upload-btn:hover {
            background: #27ae60;
        }
        .delete-btn {
            background: #e74c3c;
        }
        .delete-btn:hover {
            background: #c0392b;
        }
        header {
            background: #2c3e50;
            color: white;
            padding: 15px 0;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 15px;
        }
        .header-content h1 {
            font-size: 20px;
        }
        .logout-btn {
            color: white;
            font-size: 20px;
            text-decoration: none;
        }
        .pdf-list h2, .upload-section h2 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 18px;
            display: flex;
            align-items: center;
        }
        .pdf-list h2 i, .upload-section h2 i {
            margin-right: 10px;
        }
        .pdf-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
        }
        .pdf-item {
            background: white;
            border-radius: 8px;
            padding: 15px;
            display: flex;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .pdf-icon {
            margin-right: 15px;
        }
        .pdf-icon i {
            font-size: 30px;
            color: #e74c3c;
        }
        .pdf-info {
            flex-grow: 1;
        }
        .pdf-info h3 {
            font-size: 16px;
            margin-bottom: 5px;
            word-break: break-all;
        }
        .pdf-actions {
            display: flex;
            gap: 10px;
        }
        .view-btn {
            background: #3498db;
            padding: 8px 12px;
            font-size: 14px;
            width: auto;
            text-decoration: none;
            display: inline-block;
        }
        .empty-message {
            text-align: center;
            padding: 20px;
            color: #7f8c8d;
        }
        .upload-form {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        @media (min-width: 768px) {
            .pdf-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="container">
        <?php if (!$authenticated): ?>
            <div class="login-box">
                <div class="logo">
                    <i class="fas fa-file-pdf"></i>
                    <h1>PDF Share</h1>
                </div>
                <form method="post">
                    <div class="form-group">
                        <input type="password" name="password" placeholder="Enter Password" required>
                    </div>
                    <button type="submit" class="btn">Access Files</button>
                </form>
            </div>
        <?php else: ?>
            <header>
                <div class="header-content">
                    <h1><i class="fas fa-file-pdf"></i> PDF Files</h1>
                    <a href="?logout=1" class="logout-btn"><i class="fas fa-sign-out-alt"></i></a>
                </div>
            </header>

            <main>
                <?php if ($is_admin): ?>
                    <div class="upload-section">
                        <h2><i class="fas fa-upload"></i> Upload New PDF</h2>
                        <form method="post" enctype="multipart/form-data" class="upload-form">
                            <div class="form-group">
                                <input type="file" name="pdf_file" accept=".pdf" required>
                            </div>
                            <button type="submit" class="btn upload-btn"><i class="fas fa-cloud-upload-alt"></i> Upload</button>
                        </form>
                    </div>
                <?php endif; ?>

                <div class="pdf-list">
                    <h2><i class="fas fa-list"></i> Available Files</h2>
                    <?php if (empty($pdf_files)): ?>
                        <p class="empty-message">No PDF files available yet.</p>
                    <?php else: ?>
                        <div class="pdf-grid">
                            <?php foreach ($pdf_files as $file): ?>
                                <div class="pdf-item">
                                    <div class="pdf-icon">
                                        <i class="fas fa-file-pdf"></i>
                                    </div>
                                    <div class="pdf-info">
                                        <h3><?php echo htmlspecialchars($file); ?></h3>
                                        <div class="pdf-actions">
                                            <a href="<?php echo $upload_dir . urlencode($file); ?>" download class="btn view-btn">
                                                <i class="fas fa-download"></i> Download
                                            </a>
                                            <?php if ($is_admin): ?>
                                                <form method="post">
                                                    <input type="hidden" name="delete_file" value="<?php echo htmlspecialchars($file); ?>">
                                                    <button type="submit" class="btn delete-btn" onclick="return confirm('Are you sure?')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        <?php endif; ?>
    </div>
</body>
</html>
