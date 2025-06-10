<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

requireAdmin();

$success = $error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $faculty_number = sanitize($conn, $_POST['faculty_number']);
            $first_name = sanitize($conn, $_POST['first_name']);
            $last_name = sanitize($conn, $_POST['last_name']);
            $department = sanitize($conn, $_POST['department']);
            
            // Check if faculty number exists
            $stmt = $conn->prepare("SELECT id FROM faculty WHERE faculty_number = ?");
            $stmt->bind_param("s", $faculty_number);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $error = "Faculty number already exists";
            } else {
                $stmt = $conn->prepare("INSERT INTO faculty (faculty_number, first_name, last_name, department, status) VALUES (?, ?, ?, ?, 'active')");
                $stmt->bind_param("ssss", $faculty_number, $first_name, $last_name, $department);
                
                if ($stmt->execute()) {
                    $success = "Faculty member added successfully";
                } else {
                    $error = "Error adding faculty member";
                }
            }
        } elseif ($_POST['action'] === 'update_status') {
            $faculty_id = sanitize($conn, $_POST['faculty_id']);
            $status = sanitize($conn, $_POST['status']);
            
            $stmt = $conn->prepare("UPDATE faculty SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $status, $faculty_id);
            
            if ($stmt->execute()) {
                $success = "Faculty status updated successfully";
            } else {
                $error = "Error updating faculty status";
            }
        } elseif ($_POST['action'] === 'add_test_faculty') {
            $conn->begin_transaction();
            try {
                // Insert faculty members
                $faculty_data = [
                    ['F001', 'John', 'Smith', 'Computer Science'],
                    ['F002', 'Maria', 'Garcia', 'Mathematics'],
                    ['F003', 'David', 'Wilson', 'Physics'],
                    ['F004', 'Sarah', 'Johnson', 'English']
                ];
                
                $stmt = $conn->prepare("INSERT INTO faculty (faculty_number, first_name, last_name, department) VALUES (?, ?, ?, ?)");
                foreach ($faculty_data as $faculty) {
                    $stmt->bind_param("ssss", $faculty[0], $faculty[1], $faculty[2], $faculty[3]);
                    $stmt->execute();
                }
                
                $conn->commit();
                $success = "Test faculty members added successfully";
            } catch (Exception $e) {
                $conn->rollback();
                $error = "Error adding test faculty members: " . $e->getMessage();
            }
        }
    }
}

// Get all faculty members
$result = $conn->query("SELECT * FROM faculty ORDER BY last_name, first_name");
$faculty_members = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Faculty - Student Assessment System</title>
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
            height: 100%;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 12px rgba(0,0,0,0.1);
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

        .form-label {
            color: #4a5568;
            font-weight: 600;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-control {
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .btn {
            font-weight: 600;
            padding: 0.8rem 1.5rem;
            border-radius: 10px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: var(--primary-gradient);
            border: none;
            color: white;
        }

        .btn-primary:hover {
            background: var(--hover-gradient);
            transform: translateY(-2px);
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

        .btn-success {
            background: #def7ec;
            border: none;
            color: #03543f;
        }

        .btn-success:hover {
            background: #bcf0da;
            color: #03543f;
        }

        .btn-warning {
            background: #fef3c7;
            border: none;
            color: #92400e;
        }

        .btn-warning:hover {
            background: #fde68a;
            color: #92400e;
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
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .badge.bg-success {
            background: #def7ec !important;
            color: #03543f;
        }

        .badge.bg-secondary {
            background: #edf2f7 !important;
            color: #4a5568;
        }

        .faculty-number {
            font-weight: 600;
            color: #2d3748;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .faculty-name {
            color: #4a5568;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
        }

        .faculty-department {
            color: #4a5568;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
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
                        <a class="nav-link" href="students.php">
                            <i class="fas fa-user-graduate me-2"></i>Students
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="semesters.php">
                            <i class="fas fa-calendar-alt me-2"></i>Semesters
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="faculty.php">
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
                <i class="fas fa-chalkboard-teacher"></i>
                Manage Faculty
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

        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title">
                                <i class="fas fa-plus-circle"></i>
                                Add New Faculty
                            </h5>
                            <!-- <form method="POST" action="" style="display: inline;">
                                <input type="hidden" name="action" value="add_test_faculty">
                                <button type="submit" class="btn btn-secondary">
                                    <i class="fas fa-vial me-2"></i>Add Test Faculty
                                </button>
                            </form> -->
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="add">
                            <div class="mb-3">
                                <label for="faculty_number" class="form-label">
                                    <i class="fas fa-id-card me-2"></i>Faculty Number
                                </label>
                                <input type="text" class="form-control" id="faculty_number" name="faculty_number" required>
                            </div>
                            <div class="mb-3">
                                <label for="first_name" class="form-label">
                                    <i class="fas fa-user me-2"></i>First Name
                                </label>
                                <input type="text" class="form-control" id="first_name" name="first_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="last_name" class="form-label">
                                    <i class="fas fa-user me-2"></i>Last Name
                                </label>
                                <input type="text" class="form-control" id="last_name" name="last_name" required>
                            </div>
                            <div class="mb-4">
                                <label for="department" class="form-label">
                                    <i class="fas fa-building me-2"></i>Department
                                </label>
                                <input type="text" class="form-control" id="department" name="department" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-plus-circle me-2"></i>Add Faculty
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-list"></i>
                            Faculty List
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>
                                            <i class="fas fa-id-card me-2"></i>Faculty Number
                                        </th>
                                        <th>
                                            <i class="fas fa-user me-2"></i>Name
                                        </th>
                                        <th>
                                            <i class="fas fa-building me-2"></i>Department
                                        </th>
                                        <th>
                                            <i class="fas fa-circle me-2"></i>Status
                                        </th>
                                        <th>
                                            <i class="fas fa-cog me-2"></i>Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($faculty_members as $faculty): ?>
                                        <tr>
                                            <td>
                                                <div class="faculty-number">
                                                    <i class="fas fa-id-card text-primary"></i>
                                                    <?php echo clean($faculty['faculty_number']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="faculty-name">
                                                    <i class="fas fa-user"></i>
                                                    <?php echo clean($faculty['last_name'] . ', ' . $faculty['first_name']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="faculty-department">
                                                    <i class="fas fa-building"></i>
                                                    <?php echo clean($faculty['department']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($faculty['status'] === 'active'): ?>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check-circle"></i>
                                                        Active
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">
                                                        <i class="fas fa-circle"></i>
                                                        Inactive
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <form method="POST" action="" style="display: inline;">
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="faculty_id" value="<?php echo $faculty['id']; ?>">
                                                    <input type="hidden" name="status" 
                                                           value="<?php echo $faculty['status'] === 'active' ? 'inactive' : 'active'; ?>">
                                                    <button type="submit" class="btn <?php echo $faculty['status'] === 'active' ? 'btn-warning' : 'btn-success'; ?>">
                                                        <?php if ($faculty['status'] === 'active'): ?>
                                                            <i class="fas fa-ban"></i>
                                                            Deactivate
                                                        <?php else: ?>
                                                            <i class="fas fa-check-circle"></i>
                                                            Activate
                                                        <?php endif; ?>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 