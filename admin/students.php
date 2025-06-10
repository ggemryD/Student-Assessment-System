<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

requireAdmin();

$success = $error = '';

// Handle COR status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update_cor_status') {
        $student_id = sanitize($conn, $_POST['student_id']);
        $status = sanitize($conn, $_POST['status']);
        
        $stmt = $conn->prepare("UPDATE students SET cor_status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $student_id);
        
        if ($stmt->execute()) {
            $success = "COR status updated successfully";
        } else {
            $error = "Error updating COR status";
        }
    }
}

// Get all students with their COR status
$query = "SELECT s.*, u.username, u.email 
          FROM students s 
          JOIN users u ON s.user_id = u.id 
          ORDER BY s.last_name, s.first_name";
$result = $conn->query($query);
$students = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - Student Assessment System</title>
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

        .page-header {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .page-title {
            color: #2d3748;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 12px rgba(0,0,0,0.1);
        }

        .card-body {
            padding: 2rem;
        }

        .alert {
            border: none;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .alert-success {
            background-color: #def7ec;
            color: #03543f;
        }

        .alert-danger {
            background-color: #fde8e8;
            color: #9b1c1c;
        }

        .table {
            border-collapse: separate;
            border-spacing: 0 0.5rem;
            margin: -0.5rem 0;
        }

        .table thead th {
            background: #f8fafc;
            border: none;
            color: #4a5568;
            font-weight: 600;
            padding: 1rem;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
        }

        .table tbody tr {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            transition: all 0.3s ease;
        }

        .table tbody tr:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .table tbody td {
            border: none;
            padding: 1rem;
            vertical-align: middle;
        }

        .badge {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.875rem;
        }

        .badge.bg-success {
            background: #def7ec !important;
            color: #03543f;
        }

        .badge.bg-danger {
            background: #fde8e8 !important;
            color: #9b1c1c;
        }

        .badge.bg-warning {
            background: #fef3c7 !important;
            color: #92400e;
        }

        .badge.bg-secondary {
            background: #edf2f7 !important;
            color: #4a5568;
        }

        .btn {
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-secondary {
            background: #edf2f7;
            border: none;
            color: #4a5568;
        }

        .btn-secondary:hover {
            background: #e2e8f0;
            color: #2d3748;
        }

        .btn-info {
            background: #ebf8ff;
            border: none;
            color: #2c5282;
        }

        .btn-info:hover {
            background: #bee3f8;
            color: #2a4365;
        }

        .dropdown-menu {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 0.5rem;
        }

        .dropdown-item {
            border-radius: 6px;
            padding: 0.5rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
        }

        .dropdown-item:hover {
            background: #f7fafc;
        }

        .dropdown-item.text-success {
            color: #03543f !important;
        }

        .dropdown-item.text-success:hover {
            background: #def7ec;
        }

        .dropdown-item.text-danger {
            color: #9b1c1c !important;
        }

        .dropdown-item.text-danger:hover {
            background: #fde8e8;
        }

        .student-name {
            font-weight: 600;
            color: #2d3748;
        }

        .student-info {
            color: #4a5568;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .student-email {
            color: #4a5568;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
        }

        .student-email i {
            color: #667eea;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            .card-body {
                padding: 1rem;
            }
            
            .table-responsive {
                border-radius: 10px;
            }
            
            .btn-group {
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .btn-group .btn {
                width: 100%;
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
                        <a class="nav-link" href="surveys.php">
                            <i class="fas fa-poll me-2"></i>Surveys
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="students.php">
                            <i class="fas fa-user-graduate me-2"></i>Students
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="semesters.php">
                            <i class="fas fa-calendar-alt me-2"></i>Semesters
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="faculty.php">
                            <i class="fas fa-chalkboard-teacher me-2"></i>Faculty
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="results.php">
                            <i class="fas fa-chart-bar me-2"></i>Results
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
        <div class="page-header">
            <h2 class="page-title">
                <i class="fas fa-user-graduate"></i>
                Manage Students
            </h2>
            <?php if ($success): ?>
                <div class="alert alert-success mt-3 mb-0">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo clean($success); ?>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger mt-3 mb-0">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo clean($error); ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>
                                    <i class="fas fa-id-card me-2"></i>Student Number
                                </th>
                                <th>
                                    <i class="fas fa-user me-2"></i>Name
                                </th>
                                <th>
                                    <i class="fas fa-graduation-cap me-2"></i>Course
                                </th>
                                <th>
                                    <i class="fas fa-layer-group me-2"></i>Year
                                </th>
                                <th>
                                    <i class="fas fa-envelope me-2"></i>Email
                                </th>
                                <th>
                                    <i class="fas fa-file-alt me-2"></i>COR Status
                                </th>
                                <th>
                                    <i class="fas fa-cog me-2"></i>Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td>
                                        <div class="student-info">
                                            <i class="fas fa-id-card text-primary"></i>
                                            <?php echo clean($student['student_number']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="student-name">
                                            <?php echo clean($student['last_name'] . ', ' . $student['first_name']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="student-info">
                                            <i class="fas fa-graduation-cap"></i>
                                            <?php echo clean($student['course']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="student-info">
                                            <i class="fas fa-layer-group"></i>
                                            <?php echo clean($student['year_level']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="student-email">
                                            <i class="fas fa-envelope"></i>
                                            <?php echo clean($student['email']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        $status_class = 'secondary';
                                        $status_icon = 'file-alt';
                                        if ($student['cor_status'] === 'approved') {
                                            $status_class = 'success';
                                            $status_icon = 'check-circle';
                                        } elseif ($student['cor_status'] === 'rejected') {
                                            $status_class = 'danger';
                                            $status_icon = 'times-circle';
                                        } elseif ($student['cor_status'] === 'pending') {
                                            $status_class = 'warning';
                                            $status_icon = 'clock';
                                        }
                                        ?>
                                        <span class="badge bg-<?php echo $status_class; ?>">
                                            <i class="fas fa-<?php echo $status_icon; ?> me-1"></i>
                                            <?php echo $student['cor_file'] ? ucfirst($student['cor_status']) : 'No COR'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($student['cor_file']): ?>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                                    <i class="fas fa-edit me-1"></i>Update Status
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <form method="POST" action="" style="display: inline;">
                                                            <input type="hidden" name="action" value="update_cor_status">
                                                            <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                                            <input type="hidden" name="status" value="approved">
                                                            <button type="submit" class="dropdown-item text-success">
                                                                <i class="fas fa-check-circle"></i> Approve
                                                            </button>
                                                        </form>
                                                    </li>
                                                    <li>
                                                        <form method="POST" action="" style="display: inline;">
                                                            <input type="hidden" name="action" value="update_cor_status">
                                                            <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                                            <input type="hidden" name="status" value="rejected">
                                                            <button type="submit" class="dropdown-item text-danger">
                                                                <i class="fas fa-times-circle"></i> Reject
                                                            </button>
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>
                                            <?php if (file_exists('../uploads/cor/' . $student['cor_file'])): ?>
                                                <a href="../uploads/cor/<?php echo $student['cor_file']; ?>" 
                                                   class="btn btn-info" target="_blank">
                                                    <i class="fas fa-file-pdf"></i> View COR
                                                </a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 