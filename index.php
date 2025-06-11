<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

requireLogin();

// Get user info
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

if ($role === 'student') {
    $stmt = $conn->prepare("SELECT * FROM students WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_info = $stmt->get_result()->fetch_assoc();
}

// Get active semester
$stmt = $conn->prepare("SELECT * FROM semesters WHERE is_active = 1 LIMIT 1");
$stmt->execute();
$active_semester = $stmt->get_result()->fetch_assoc();

// Get active surveys for students
if ($role === 'student' && $active_semester) {
    $stmt = $conn->prepare("
        SELECT s.*, 
            (SELECT COUNT(*) FROM responses r 
             WHERE r.survey_id = s.id 
             AND r.student_id = ? 
             AND r.semester_id = ?) as has_responded
        FROM surveys s 
        WHERE s.semester_id = ? 
        AND s.status = 'active'
    ");
    $student_id = $user_info['id'];
    $semester_id = $active_semester['id'];
    $stmt->bind_param("iii", $student_id, $semester_id, $semester_id);
    $stmt->execute();
    $active_surveys = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// bag o ning ubos

// Add this PHP code after getting the active surveys (around line 30 in your dashboard)
// if ($role === 'student' && $active_semester) {
//     // Get faculty that student has rated
//     $stmt = $conn->prepare("
//         SELECT DISTINCT f.first_name, f.last_name, f.faculty_number, f.department,
//                r.created_at as rating_date
//         FROM responses r 
//         JOIN faculty f ON r.faculty_id = f.id 
//         WHERE r.student_id = ? 
//         AND r.semester_id = ?
//         ORDER BY r.created_at DESC
//     ");
//     $student_id = $user_info['id'];
//     $semester_id = $active_semester['id'];
//     $stmt->bind_param("ii", $student_id, $semester_id);
//     $stmt->execute();
//     $rated_faculty = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
// }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Student Assessment System</title>
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

        .dropdown-menu {
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border-radius: 10px;
        }

        .dropdown-item {
            padding: 0.7rem 1.5rem;
            transition: all 0.3s ease;
        }

        .dropdown-item:hover {
            background: #f8f9fa;
            color: #667eea;
        }

        .welcome-section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .welcome-title {
            color: #2d3748;
            font-weight: 700;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .semester-badge {
            background: var(--primary-gradient);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 10px;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .dashboard-card {
            background: white;
            border: none;
            border-radius: 15px;
            transition: all 0.3s ease;
            height: 100%;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        }

        .dashboard-card .card-body {
            padding: 2rem;
        }

        .card-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .btn-dashboard {
            background: var(--primary-gradient);
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-dashboard:hover {
            background: var(--hover-gradient);
            transform: translateY(-2px);
            color: white;
        }

        .list-group-item {
            border: none;
            margin-bottom: 0.5rem;
            border-radius: 10px !important;
            transition: all 0.3s ease;
        }

        .list-group-item:hover {
            background: #f8f9fa;
            transform: translateX(5px);
        }

        .badge {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 500;
        }

        .badge.bg-success {
            background: #48bb78 !important;
        }

        .badge.bg-warning {
            background: #ecc94b !important;
            color: #744210;
        }

        .card-header {
            background: transparent;
            border-bottom: none;
            padding: 1.5rem 1.5rem 0.5rem;
        }

        .card-header h5 {
            color: #2d3748;
            font-weight: 700;
        }

        .alert {
            border: none;
            border-radius: 10px;
            padding: 1rem 1.5rem;
        }

        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            height: 100%;
            transition: all 0.3s ease;
            border: none;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        }

        .stats-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            .welcome-section {
                padding: 1.5rem;
            }
            
            .dashboard-card .card-body {
                padding: 1.5rem;
            }
        }

        /* bag.o */
        /* .rated-faculty-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
        }

        .rated-faculty-card:hover {
            background: #e9ecef;
            transform: translateY(-2px);
        }

        .faculty-avatar {
            width: 40px;
            height: 40px;
            background: var(--primary-gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }

        .faculty-info h6 {
            color: #2d3748;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .rated-faculty-card {
                margin-bottom: 1rem;
            }
        } */
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-graduation-cap me-2"></i>
                Student Assessment System
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <?php if (isAdmin()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="admin/surveys.php">
                                <i class="fas fa-poll me-2"></i>Surveys
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin/students.php">
                                <i class="fas fa-user-graduate me-2"></i>Students
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin/semesters.php">
                                <i class="fas fa-calendar-alt me-2"></i>Semesters
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin/faculty.php">
                                <i class="fas fa-chalkboard-teacher me-2"></i>Faculty
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin/results.php">
                                <i class="fas fa-chart-bar me-2"></i>Results
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php if (isStudent()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="student/cor.php">
                                <i class="fas fa-file-alt me-2"></i>Submit COR
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="student/surveys.php">
                                <i class="fas fa-star me-2"></i>Faculty Rating
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="student/rated.php">
                                <i class="fas fa-check-circle me-2"></i>Rated Faculty
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
                <div class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-2"></i><?php echo clean($_SESSION['username']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                </div>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="welcome-section">
            <h2 class="welcome-title">
                <i class="fas fa-hand-wave"></i>
                Welcome, <?php echo clean($_SESSION['username']); ?>!
            </h2>
            <?php if ($active_semester): ?>
                <div class="semester-badge">
                    <i class="fas fa-calendar-alt"></i>
                    Current Semester: <?php echo clean($active_semester['name']); ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Add this HTML right after the welcome-section div and before the student dashboard cards -->
        <!-- <?php if (isStudent() && !empty($rated_faculty)): ?>
        <div class="row g-4 mb-4">
            <div class="col-12">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-star me-2"></i>Faculty You've Rated This Semester
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <?php foreach ($rated_faculty as $faculty): ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="rated-faculty-card">
                                        <div class="d-flex align-items-center">
                                            <div class="faculty-avatar me-3">
                                                <i class="fas fa-user-tie"></i>
                                            </div>
                                            <div class="faculty-info">
                                                <h6 class="mb-1"><?php echo clean($faculty['first_name'] . ' ' . $faculty['last_name']); ?></h6>
                                                <small class="text-muted"><?php echo clean($faculty['department']); ?></small>
                                                <div class="text-success small">
                                                    <i class="fas fa-check-circle me-1"></i>
                                                    Rated on <?php echo date('M j, Y', strtotime($faculty['rating_date'])); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?> -->

        <?php if (isAdmin()): ?>
        <div class="row g-4">
            <div class="col-md-4 col-lg-3">
                <div class="dashboard-card">
                    <div class="card-body text-center">
                        <i class="fas fa-poll card-icon"></i>
                        <h5 class="card-title">Surveys</h5>
                        <p class="card-text text-muted">Manage Assessment Surveys</p>
                        <a href="admin/surveys.php" class="btn btn-dashboard">
                            <i class="fas fa-arrow-right me-2"></i>View Surveys
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-lg-3">
                <div class="dashboard-card">
                    <div class="card-body text-center">
                        <i class="fas fa-user-graduate card-icon"></i>
                        <h5 class="card-title">Students</h5>
                        <p class="card-text text-muted">Manage Student Records</p>
                        <a href="admin/students.php" class="btn btn-dashboard">
                            <i class="fas fa-arrow-right me-2"></i>View Students
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-lg-3">
                <div class="dashboard-card">
                    <div class="card-body text-center">
                        <i class="fas fa-calendar-alt card-icon"></i>
                        <h5 class="card-title">Semesters</h5>
                        <p class="card-text text-muted">Manage Semesters</p>
                        <a href="admin/semesters.php" class="btn btn-dashboard">
                            <i class="fas fa-arrow-right me-2"></i>View Semesters
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-lg-3">
                <div class="dashboard-card">
                    <div class="card-body text-center">
                        <i class="fas fa-chalkboard-teacher card-icon"></i>
                        <h5 class="card-title">Faculty</h5>
                        <p class="card-text text-muted">Manage Faculty Members</p>
                        <a href="admin/faculty.php" class="btn btn-dashboard">
                            <i class="fas fa-arrow-right me-2"></i>View Faculty
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-lg-3">
                <div class="dashboard-card">
                    <div class="card-body text-center">
                        <i class="fas fa-chart-bar card-icon"></i>
                        <h5 class="card-title">Results</h5>
                        <p class="card-text text-muted">View Assessment Results</p>
                        <a href="admin/results.php" class="btn btn-dashboard">
                            <i class="fas fa-arrow-right me-2"></i>View Results
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (isStudent()): ?>
        <div class="row g-4">
            <div class="col-md-6">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-tasks me-2"></i>Active Surveys
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($active_surveys)): ?>
                            <div class="text-center text-muted">
                                <i class="fas fa-clipboard-list fa-3x mb-3"></i>
                                <p>No active surveys at the moment.</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($active_surveys as $survey): ?>
                                    <a href="student/survey.php?id=<?php echo $survey['id']; ?>" 
                                       class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                        <span>
                                            <i class="fas fa-poll me-2"></i>
                                            <?php echo clean($survey['title']); ?>
                                        </span>
                                        <?php if ($survey['has_responded']): ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-check me-1"></i>Completed
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">
                                                <i class="fas fa-clock me-1"></i>Pending
                                            </span>
                                        <?php endif; ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-file-alt me-2"></i>COR Status
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($user_info['cor_file']): ?>
                            <div class="alert <?php echo $user_info['cor_status'] === 'approved' ? 'alert-success' : ($user_info['cor_status'] === 'rejected' ? 'alert-danger' : 'alert-warning'); ?>">
                                <i class="fas <?php echo $user_info['cor_status'] === 'approved' ? 'fa-check-circle' : ($user_info['cor_status'] === 'rejected' ? 'fa-times-circle' : 'fa-clock'); ?> me-2"></i>
                                Status: <?php echo ucfirst($user_info['cor_status']); ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center">
                                <i class="fas fa-file-upload fa-3x mb-3 text-warning"></i>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                    Please submit your Certificate of Registration (COR)
                                </div>
                                <a href="student/cor.php" class="btn btn-dashboard">
                                    <i class="fas fa-upload me-2"></i>Submit COR
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 