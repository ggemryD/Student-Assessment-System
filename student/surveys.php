<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

requireStudent();

$user_id = $_SESSION['user_id'];

// Get student info
$stmt = $conn->prepare("SELECT * FROM students WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

// Get active semester
$stmt = $conn->prepare("SELECT * FROM semesters WHERE is_active = 1 LIMIT 1");
$stmt->execute();
$active_semester = $stmt->get_result()->fetch_assoc();

// Get active surveys with response status
if ($active_semester) {
    $stmt = $conn->prepare("
        SELECT s.*, 
            (SELECT COUNT(*) FROM responses r 
             WHERE r.survey_id = s.id 
             AND r.student_id = ? 
             AND r.semester_id = ?) as faculty_rated,
            (SELECT COUNT(*) FROM faculty f WHERE f.status = 'active') as total_faculty
        FROM surveys s 
        WHERE s.semester_id = ? 
        AND s.status = 'active'
        ORDER BY s.end_date ASC
    ");
    $semester_id = $active_semester['id'];
    $stmt->bind_param("iii", $student['id'], $semester_id, $semester_id);
    $stmt->execute();
    $active_surveys = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Get completed surveys
$stmt = $conn->prepare("
    SELECT s.*, sem.name as semester_name,
           COUNT(DISTINCT r.faculty_id) as faculty_rated
    FROM responses r
    JOIN surveys s ON r.survey_id = s.id
    JOIN semesters sem ON s.semester_id = sem.id
    WHERE r.student_id = ?
    GROUP BY s.id
    ORDER BY r.created_at DESC
");
$stmt->bind_param("i", $student['id']);
$stmt->execute();
$completed_surveys = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Rating Surveys - Student Assessment System</title>
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
            margin-bottom: 2rem;
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
            padding: 1.5rem;
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

        .alert-warning {
            background-color: #fef3c7;
            color: #92400e;
        }

        .btn-warning {
            background: #fbbf24;
            border: none;
            color: #92400e;
            font-weight: 600;
            padding: 0.8rem 1.5rem;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .btn-warning:hover {
            background: #f59e0b;
            color: #78350f;
            transform: translateY(-2px);
        }

        .btn-primary {
            background: var(--primary-gradient);
            border: none;
            color: white;
            font-weight: 600;
            padding: 0.8rem 1.5rem;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: var(--hover-gradient);
            color: white;
            transform: translateY(-2px);
        }

        .list-group-item {
            border: none;
            border-radius: 10px !important;
            margin-bottom: 1rem;
            padding: 1.5rem;
            transition: all 0.3s ease;
            background: #f8fafc;
        }

        .list-group-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .list-group-item h5 {
            color: #2d3748;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .badge {
            padding: 0.8rem 1.2rem;
            border-radius: 8px;
            font-weight: 500;
        }

        .badge.bg-success {
            background: #48bb78 !important;
        }

        .survey-meta {
            display: flex;
            gap: 2rem;
            margin-top: 0.5rem;
        }

        .survey-meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #718096;
        }

        .table {
            border-collapse: separate;
            border-spacing: 0 0.5rem;
        }

        .table thead th {
            background: #f8fafc;
            border: none;
            color: #4a5568;
            font-weight: 600;
            padding: 1rem;
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

        .progress {
            height: 0.5rem;
            border-radius: 1rem;
            background: #e2e8f0;
        }

        .progress-bar {
            background: var(--primary-gradient);
            border-radius: 1rem;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            .survey-meta {
                flex-direction: column;
                gap: 0.5rem;
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
                        <a class="nav-link" href="cor.php">
                            <i class="fas fa-file-alt me-2"></i>Submit COR
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="surveys.php">
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
        <div class="page-header">
            <h2 class="page-title">
                <i class="fas fa-star"></i>
                Faculty Rating Surveys
            </h2>
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success mt-3 mb-0">
                    <i class="fas fa-check-circle me-2"></i>
                    Survey response submitted successfully!
                </div>
            <?php endif; ?>
        </div>

        <?php if ($student['cor_status'] !== 'approved'): ?>
            <div class="alert alert-warning">
                <h4 class="alert-heading">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    COR Approval Required
                </h4>
                <p>You need to submit and get your Certificate of Registration (COR) approved before you can participate in faculty rating surveys.</p>
                <hr>
                <p class="mb-0">
                    <a href="cor.php" class="btn btn-warning">
                        <i class="fas fa-upload me-2"></i>Submit COR
                    </a>
                </p>
            </div>
        <?php else: ?>
            <?php if ($active_semester): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-clock"></i>
                            Active Surveys - <?php echo clean($active_semester['name']); ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($active_surveys)): ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-clipboard-list fa-3x mb-3"></i>
                                <p class="mb-0">No active surveys at the moment.</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($active_surveys as $survey): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between align-items-start">
                                            <div>
                                                <h5>
                                                    <i class="fas fa-poll me-2"></i>
                                                    <?php echo clean($survey['title']); ?>
                                                </h5>
                                                <div class="survey-meta">
                                                    <div class="survey-meta-item">
                                                        <i class="fas fa-calendar-alt"></i>
                                                        <span>Until <?php echo date('F j, Y', strtotime($survey['end_date'])); ?></span>
                                                    </div>
                                                    <div class="survey-meta-item">
                                                        <i class="fas fa-users"></i>
                                                        <span>Faculty rated: <?php echo $survey['faculty_rated']; ?> of <?php echo $survey['total_faculty']; ?></span>
                                                    </div>
                                                </div>
                                                <div class="progress mt-3" style="width: 200px;">
                                                    <div class="progress-bar" role="progressbar" 
                                                         style="width: <?php echo ($survey['faculty_rated'] / $survey['total_faculty']) * 100; ?>%">
                                                    </div>
                                                </div>
                                            </div>
                                            <?php if ($survey['faculty_rated'] >= $survey['total_faculty']): ?>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check me-1"></i>All Faculty Rated
                                                </span>
                                            <?php else: ?>
                                                <a href="survey.php?id=<?php echo $survey['id']; ?>" class="btn btn-primary">
                                                    <i class="fas fa-star me-2"></i>Continue Rating
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($completed_surveys)): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-check-circle"></i>
                            Completed Surveys
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>
                                            <i class="fas fa-poll me-2"></i>Survey
                                        </th>
                                        <th>
                                            <i class="fas fa-calendar-alt me-2"></i>Semester
                                        </th>
                                        <th>
                                            <i class="fas fa-users me-2"></i>Faculty Members Rated
                                        </th>
                                        <th>
                                            <i class="fas fa-clock me-2"></i>Completion Date
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($completed_surveys as $survey): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo clean($survey['title']); ?></strong>
                                            </td>
                                            <td><?php echo clean($survey['semester_name']); ?></td>
                                            <td>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check me-1"></i><?php echo $survey['faculty_rated']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('F j, Y', strtotime($survey['created_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 