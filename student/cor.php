<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

requireStudent();

$success = $error = '';
$user_id = $_SESSION['user_id'];

// Get student info
$stmt = $conn->prepare("SELECT * FROM students WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['cor_file']) && $_FILES['cor_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['cor_file'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_types = ['pdf', 'jpg', 'jpeg', 'png'];
        
        if (!in_array($file_ext, $allowed_types)) {
            $error = "Only PDF, JPG, JPEG, and PNG files are allowed";
        } else {
            $upload_dir = '../uploads/cor/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $new_filename = $student['student_number'] . '_' . time() . '.' . $file_ext;
            $target_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($file['tmp_name'], $target_path)) {
                // Update database
                $stmt = $conn->prepare("UPDATE students SET cor_file = ?, cor_status = 'pending' WHERE id = ?");
                $stmt->bind_param("si", $new_filename, $student['id']);
                
                if ($stmt->execute()) {
                    $success = "COR file uploaded successfully";
                    // Refresh student info
                    $stmt = $conn->prepare("SELECT * FROM students WHERE user_id = ?");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $student = $stmt->get_result()->fetch_assoc();
                } else {
                    $error = "Error updating database";
                }
            } else {
                $error = "Error uploading file";
            }
        }
    } else {
        $error = "Please select a file to upload";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit COR - Student Assessment System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --hover-gradient: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }
        
        body {
            background: #f8f9fa;
            min-height: 100vh;
        }

        .navbar {
            background: var(--primary-gradient) !important;
            padding: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
        }

        .nav-link {
            font-weight: 500;
            padding: 0.5rem 1rem !important;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            background: rgba(255,255,255,0.1);
        }

        .nav-link.active {
            background: rgba(255,255,255,0.2);
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            overflow: hidden;
        }

        .card-header {
            background: white;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding: 1.5rem;
        }

        .card-title {
            color: #2d3748;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .card-body {
            padding: 2rem;
        }

        .alert {
            border: none;
            border-radius: 10px;
            padding: 1rem 1.5rem;
        }

        .alert-success {
            background-color: #def7ec;
            color: #03543f;
        }

        .alert-danger {
            background-color: #fde8e8;
            color: #9b1c1c;
        }

        .alert-info {
            background-color: #e1effe;
            color: #1e429f;
        }

        .alert-warning {
            background-color: #fef3c7;
            color: #92400e;
        }

        .cor-status {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(0,0,0,0.05);
        }

        .cor-status h5 {
            color: #2d3748;
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 500;
            margin-bottom: 1rem;
        }

        .status-badge.pending {
            background-color: #fef3c7;
            color: #92400e;
        }

        .status-badge.approved {
            background-color: #def7ec;
            color: #03543f;
        }

        .status-badge.rejected {
            background-color: #fde8e8;
            color: #9b1c1c;
        }

        .file-info {
            background: #f8fafc;
            padding: 1rem;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .file-icon {
            font-size: 2rem;
            color: #4a5568;
        }

        .upload-zone {
            border: 2px dashed #e2e8f0;
            border-radius: 10px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            margin-bottom: 1.5rem;
        }

        .upload-zone:hover {
            border-color: #667eea;
            background: #f8fafc;
        }

        .upload-icon {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 1rem;
        }

        .btn-submit {
            background: var(--primary-gradient);
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            background: var(--hover-gradient);
            transform: translateY(-2px);
            color: white;
        }

        .form-text {
            color: #718096;
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }

        #cor_file {
            display: none;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            .card-body {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-graduation-cap me-2"></i>
                Student Assessment System
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link active" href="cor.php">
                            <i class="fas fa-file-alt me-2"></i>Submit COR
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="surveys.php">
                            <i class="fas fa-star me-2"></i>Faculty Rating
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-file-upload"></i>
                            Submit Certificate of Registration (COR)
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo clean($success); ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?php echo clean($error); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($student['cor_file']): ?>
                            <div class="cor-status">
                                <h5>
                                    <i class="fas fa-info-circle"></i>
                                    Current COR Status
                                </h5>
                                <div class="status-badge <?php echo $student['cor_status']; ?>">
                                    <i class="fas <?php echo $student['cor_status'] === 'approved' ? 'fa-check-circle' : ($student['cor_status'] === 'rejected' ? 'fa-times-circle' : 'fa-clock'); ?>"></i>
                                    Status: <?php echo ucfirst($student['cor_status']); ?>
                                </div>
                                <div class="file-info">
                                    <i class="fas <?php 
                                        $ext = strtolower(pathinfo($student['cor_file'], PATHINFO_EXTENSION));
                                        echo $ext === 'pdf' ? 'fa-file-pdf' : 'fa-file-image';
                                    ?> file-icon"></i>
                                    <div>
                                        <strong>Uploaded File:</strong><br>
                                        <?php echo clean($student['cor_file']); ?>
                                    </div>
                                </div>
                            </div>
                            <?php if ($student['cor_status'] === 'rejected'): ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Your COR was rejected. Please submit a new one.
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if (!$student['cor_file'] || $student['cor_status'] === 'rejected'): ?>
                            <form method="POST" action="" enctype="multipart/form-data">
                                <div class="upload-zone" onclick="document.getElementById('cor_file').click()">
                                    <i class="fas fa-cloud-upload-alt upload-icon"></i>
                                    <h5>Click to Upload COR File</h5>
                                    <p class="text-muted mb-0">or drag and drop your file here</p>
                                    <input type="file" class="form-control" id="cor_file" name="cor_file" accept=".pdf,.jpg,.jpeg,.png" required>
                                </div>
                                <div class="selected-file text-center mb-3" style="display: none;">
                                    <i class="fas fa-file-alt me-2"></i>
                                    <span id="file-name">No file selected</span>
                                </div>
                                <div class="form-text text-center mb-4">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Allowed file types: PDF, JPG, JPEG, PNG
                                </div>
                                <div class="text-center">
                                    <button type="submit" class="btn btn-submit">
                                        <i class="fas fa-upload me-2"></i>Submit COR
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // File upload preview
        document.getElementById('cor_file').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name || 'No file selected';
            document.getElementById('file-name').textContent = fileName;
            document.querySelector('.selected-file').style.display = 'block';
        });

        // Drag and drop functionality
        const uploadZone = document.querySelector('.upload-zone');
        
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadZone.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults (e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            uploadZone.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            uploadZone.addEventListener(eventName, unhighlight, false);
        });

        function highlight(e) {
            uploadZone.style.borderColor = '#667eea';
            uploadZone.style.backgroundColor = '#f8fafc';
        }

        function unhighlight(e) {
            uploadZone.style.borderColor = '#e2e8f0';
            uploadZone.style.backgroundColor = '';
        }

        uploadZone.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            document.getElementById('cor_file').files = files;
            
            const fileName = files[0]?.name || 'No file selected';
            document.getElementById('file-name').textContent = fileName;
            document.querySelector('.selected-file').style.display = 'block';
        }
    </script>
</body>
</html> 