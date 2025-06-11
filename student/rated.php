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

// Get rated faculty for active semester
$rated_faculty = [];
if ($active_semester && $student) {
    $stmt = $conn->prepare("
        SELECT f.*, s.title as survey_title, r.created_at as rated_at,
               AVG(rd.rating_value) as average_rating,
               COUNT(rd.id) as total_questions
        FROM responses r
        JOIN faculty f ON r.faculty_id = f.id
        JOIN surveys s ON r.survey_id = s.id
        JOIN response_details rd ON r.id = rd.response_id
        WHERE r.student_id = ? AND r.semester_id = ?
        GROUP BY f.id, s.id, r.id
        ORDER BY r.created_at DESC
    ");
    $stmt->bind_param("ii", $student['id'], $active_semester['id']);
    $stmt->execute();
    $rated_faculty = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Get all completed surveys with faculty counts
$survey_stats = [];
if ($student) {
    $stmt = $conn->prepare("
        SELECT s.id, s.title, sem.name as semester_name,
               COUNT(DISTINCT r.faculty_id) as faculty_count,
               AVG(rd.rating_value) as overall_average
        FROM responses r
        JOIN surveys s ON r.survey_id = s.id
        JOIN semesters sem ON s.semester_id = sem.id
        JOIN response_details rd ON r.id = rd.response_id
        WHERE r.student_id = ?
        GROUP BY s.id
        ORDER BY r.created_at DESC
    ");
    $stmt->bind_param("i", $student['id']);
    $stmt->execute();
    $survey_stats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getRatingText($rating) {
    if ($rating >= 4.5) return 'Excellent';
    if ($rating >= 3.5) return 'Very Good';
    if ($rating >= 2.5) return 'Good';
    if ($rating >= 1.5) return 'Fair';
    return 'Needs Improvement';
}

function getRatingColor($rating) {
    if ($rating >= 4.5) return 'success';
    if ($rating >= 3.5) return 'info';
    if ($rating >= 2.5) return 'primary';
    if ($rating >= 1.5) return 'warning';
    return 'danger';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rated Faculty - Student Assessment System</title>
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

        .faculty-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            border-left: 4px solid #667eea;
        }

        .faculty-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .faculty-info {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .faculty-details h5 {
            color: #2d3748;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .faculty-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 0.5rem;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #718096;
            font-size: 0.9rem;
        }

        .rating-display {
            text-align: center;
            min-width: 120px;
        }

        .rating-score {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .rating-text {
            font-size: 0.85rem;
            font-weight: 500;
        }

        .stars {
            color: #ffd700;
            margin: 0.5rem 0;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .alert {
            border: none;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .alert-info {
            background-color: #dbeafe;
            color: #1e40af;
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

        .filter-tabs {
            margin-bottom: 2rem;
        }

        .nav-pills .nav-link {
            color: #6b7280;
            background: white;
            border: 1px solid #e5e7eb;
            margin-right: 0.5rem;
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
        }

        .nav-pills .nav-link.active {
            background: var(--primary-gradient);
            border-color: transparent;
            color: white;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            .faculty-info {
                flex-direction: column;
                align-items: stretch;
            }
            
            .rating-display {
                text-align: left;
                min-width: auto;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
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
                        <a class="nav-link" href="surveys.php">
                            <i class="fas fa-star me-2"></i>Faculty Rating
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="#">
                            <i class="fas fa-check-circle me-2"></i>Rated Faculty
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
                <i class="fas fa-check-circle"></i>
                Rated Faculty Members
            </h2>
            <p class="text-muted mt-2 mb-0">View your completed faculty evaluations and ratings</p>
        </div>

        <?php if (empty($rated_faculty) && empty($survey_stats)): ?>
            <div class="alert alert-info">
                <h4 class="alert-heading">
                    <i class="fas fa-info-circle me-2"></i>
                    No Ratings Yet
                </h4>
                <p>You haven't rated any faculty members yet. Start by participating in active surveys.</p>
                <hr>
                <p class="mb-0">
                    <a href="surveys.php" class="btn btn-primary">
                        <i class="fas fa-star me-2"></i>View Active Surveys
                    </a>
                </p>
            </div>
        <?php else: ?>
            <!-- Statistics Overview -->
            <?php if (!empty($survey_stats)): ?>
                <div class="stats-grid">
                    <?php 
                    $total_faculty = array_sum(array_column($survey_stats, 'faculty_count'));
                    $total_surveys = count($survey_stats);
                    $overall_avg = !empty($survey_stats) ? array_sum(array_column($survey_stats, 'overall_average')) / count($survey_stats) : 0;
                    ?>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $total_faculty; ?></div>
                        <div class="stat-label">
                            <i class="fas fa-users me-1"></i>Total Faculty Rated
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $total_surveys; ?></div>
                        <div class="stat-label">
                            <i class="fas fa-poll me-1"></i>Surveys Completed
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo number_format($overall_avg, 1); ?></div>
                        <div class="stat-label">
                            <i class="fas fa-star me-1"></i>Average Rating Given
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Current Semester Rated Faculty -->
            <?php if (!empty($rated_faculty) && $active_semester): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-calendar-alt"></i>
                            Current Semester - <?php echo clean($active_semester['name']); ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($rated_faculty as $faculty): ?>
                            <div class="faculty-card">
                                <div class="faculty-info">
                                    <div class="faculty-details">
                                        <h5>
                                            <i class="fas fa-user-tie me-2"></i>
                                            <?php echo clean($faculty['first_name'] . ' ' . $faculty['last_name']); ?>
                                        </h5>
                                        <div class="faculty-meta">
                                            <div class="meta-item">
                                                <i class="fas fa-id-badge"></i>
                                                <span><?php echo clean($faculty['faculty_number']); ?></span>
                                            </div>
                                            <div class="meta-item">
                                                <i class="fas fa-building"></i>
                                                <span><?php echo clean($faculty['department']); ?></span>
                                            </div>
                                            <div class="meta-item">
                                                <i class="fas fa-clock"></i>
                                                <span>Rated on <?php echo date('M j, Y', strtotime($faculty['rated_at'])); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="rating-display">
                                        <div class="rating-score text-<?php echo getRatingColor($faculty['average_rating']); ?>">
                                            <?php echo number_format($faculty['average_rating'], 1); ?>
                                        </div>
                                        <div class="stars">
                                            <?php 
                                            $rating = round($faculty['average_rating']);
                                            for ($i = 1; $i <= 5; $i++) {
                                                if ($i <= $rating) {
                                                    echo '<i class="fas fa-star"></i>';
                                                } else {
                                                    echo '<i class="far fa-star"></i>';
                                                }
                                            }
                                            ?>
                                        </div>
                                        <div class="rating-text text-<?php echo getRatingColor($faculty['average_rating']); ?>">
                                            <?php echo getRatingText($faculty['average_rating']); ?>
                                        </div>
                                        <small class="text-muted">
                                            <?php echo $faculty['total_questions']; ?> questions answered
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Survey Summary -->
            <!-- <?php if (!empty($survey_stats)): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-chart-bar"></i>
                            Survey Summary
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>
                                            <i class="fas fa-poll me-2"></i>Survey
                                        </th>
                                        <th>
                                            <i class="fas fa-calendar-alt me-2"></i>Semester
                                        </th>
                                        <th>
                                            <i class="fas fa-users me-2"></i>Faculty Rated
                                        </th>
                                        <th>
                                            <i class="fas fa-star me-2"></i>Average Rating
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($survey_stats as $stat): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo clean($stat['title']); ?></strong>
                                            </td>
                                            <td><?php echo clean($stat['semester_name']); ?></td>
                                            <td>
                                                <span class="badge bg-primary">
                                                    <i class="fas fa-users me-1"></i>
                                                    <?php echo $stat['faculty_count']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="me-2 fw-bold text-<?php echo getRatingColor($stat['overall_average']); ?>">
                                                        <?php echo number_format($stat['overall_average'], 1); ?>
                                                    </span>
                                                    <div class="text-warning">
                                                        <?php 
                                                        $rating = round($stat['overall_average']);
                                                        for ($i = 1; $i <= 5; $i++) {
                                                            if ($i <= $rating) {
                                                                echo '<i class="fas fa-star"></i>';
                                                            } else {
                                                                echo '<i class="far fa-star"></i>';
                                                            }
                                                        }
                                                        ?>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?> -->
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>