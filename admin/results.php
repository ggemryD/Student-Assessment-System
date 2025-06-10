<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

requireAdmin();

// Get semesters
$result = $conn->query("SELECT * FROM semesters ORDER BY start_date DESC");
$semesters = $result->fetch_all(MYSQLI_ASSOC);

// Get selected semester
$selected_semester = null;
if (isset($_GET['semester_id'])) {
    $semester_id = sanitize($conn, $_GET['semester_id']);
    $stmt = $conn->prepare("SELECT * FROM semesters WHERE id = ?");
    $stmt->bind_param("i", $semester_id);
    $stmt->execute();
    $selected_semester = $stmt->get_result()->fetch_assoc();
}

// Get surveys for selected semester
$surveys = [];
if ($selected_semester) {
    $stmt = $conn->prepare("SELECT * FROM surveys WHERE semester_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $selected_semester['id']);
    $stmt->execute();
    $surveys = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Get selected survey
$selected_survey = null;
$faculty_results = [];
if (isset($_GET['survey_id'])) {
    $survey_id = sanitize($conn, $_GET['survey_id']);
    $stmt = $conn->prepare("SELECT * FROM surveys WHERE id = ?");
    $stmt->bind_param("i", $survey_id);
    $stmt->execute();
    $selected_survey = $stmt->get_result()->fetch_assoc();

    if ($selected_survey) {
        // Get questions first
        $stmt = $conn->prepare("SELECT * FROM questions WHERE survey_id = ? ORDER BY order_num");
        $stmt->bind_param("i", $survey_id);
        $stmt->execute();
        $questions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Get unique faculty who have responses for this survey
        $query = "
            SELECT DISTINCT
                f.id,
                f.faculty_number,
                f.first_name,
                f.last_name,
                f.department,
                f.status
            FROM faculty f
            INNER JOIN responses r ON f.id = r.faculty_id
            WHERE r.survey_id = ?
            ORDER BY f.last_name, f.first_name
        ";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $survey_id);
        $stmt->execute();
        $faculty_results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Calculate metrics for each faculty separately
        foreach ($faculty_results as &$faculty) {
            // Get total unique student responses for this faculty
            $stmt = $conn->prepare("
                SELECT COUNT(DISTINCT student_id) as total_responses
                FROM responses 
                WHERE faculty_id = ? AND survey_id = ?
            ");
            $stmt->bind_param("ii", $faculty['id'], $survey_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $faculty['total_responses'] = $result['total_responses'] ?? 0;
            
            // Get average rating for this faculty
            $stmt = $conn->prepare("
                SELECT ROUND(AVG(rd.rating_value), 2) as average_rating
                FROM responses r
                JOIN response_details rd ON r.id = rd.response_id
                JOIN questions q ON rd.question_id = q.id
                WHERE r.faculty_id = ? 
                AND r.survey_id = ? 
                AND q.question_type = 'rating'
                AND rd.rating_value IS NOT NULL
            ");
            $stmt->bind_param("ii", $faculty['id'], $survey_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $faculty['average_rating'] = $result['average_rating'];

            // Get detailed ratings for each question
            $query = "
                SELECT 
                    q.id as question_id,
                    q.category,
                    q.question_text,
                    q.question_type,
                    CASE 
                        WHEN q.question_type = 'rating' 
                        THEN ROUND(AVG(rd.rating_value), 2)
                        ELSE NULL
                    END as average_rating,
                    COUNT(DISTINCT r.student_id) as total_responses,
                    GROUP_CONCAT(
                        DISTINCT CASE 
                            WHEN rd.text_answer IS NOT NULL AND TRIM(rd.text_answer) != '' 
                            THEN TRIM(rd.text_answer)
                            ELSE NULL 
                        END 
                        SEPARATOR '||'
                    ) as text_responses
                FROM questions q
                LEFT JOIN response_details rd ON q.id = rd.question_id
                LEFT JOIN responses r ON rd.response_id = r.id 
                    AND r.faculty_id = ? 
                    AND r.survey_id = ?
                WHERE q.survey_id = ?
                GROUP BY q.id, q.category, q.question_text, q.question_type, q.order_num
                ORDER BY q.order_num
            ";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("iii", $faculty['id'], $survey_id, $survey_id);
            $stmt->execute();
            $faculty['question_ratings'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
        unset($faculty); // Break the reference to avoid issues
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Survey Results - Student Assessment System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../admin/css/results.css">
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
                        <a class="nav-link" href="faculty.php">
                            <i class="fas fa-chalkboard-teacher me-2"></i>Faculty
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="results.php">
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
                <i class="fas fa-chart-bar"></i>
                Survey Results
            </h2>
        </div>

        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-filter"></i>
                            Filter Results
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="" class="row g-3">
                            <div class="col-md-6">
                                <label for="semester_id" class="form-label">
                                    <i class="fas fa-calendar-alt"></i>
                                    Select Semester
                                </label>
                                <select class="form-select" id="semester_id" name="semester_id" onchange="this.form.submit()">
                                    <option value="">Choose semester</option>
                                    <?php foreach ($semesters as $semester): ?>
                                        <option value="<?php echo $semester['id']; ?>"
                                                <?php echo (isset($_GET['semester_id']) && $_GET['semester_id'] == $semester['id']) ? 'selected' : ''; ?>>
                                            <?php echo clean($semester['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php if ($selected_semester): ?>
                                <div class="col-md-6">
                                    <label for="survey_id" class="form-label">
                                        <i class="fas fa-poll"></i>
                                        Select Survey
                                    </label>
                                    <select class="form-select" id="survey_id" name="survey_id" onchange="this.form.submit()">
                                        <option value="">Choose survey</option>
                                        <?php foreach ($surveys as $survey): ?>
                                            <option value="<?php echo $survey['id']; ?>"
                                                    <?php echo (isset($_GET['survey_id']) && $_GET['survey_id'] == $survey['id']) ? 'selected' : ''; ?>>
                                                <?php echo clean($survey['title']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($selected_survey && !empty($faculty_results)): ?>
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="fas fa-star"></i>
                                <!-- Faculty Ratings Summary -->
                                 General SAS Results
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>
                                                <i class="fas fa-user me-2"></i>Faculty Member
                                            </th>
                                            <th>
                                                <i class="fas fa-building me-2"></i>Department
                                            </th>
                                            <th>
                                                <i class="fas fa-star me-2"></i>Average Rating
                                            </th>
                                            <th>
                                                <i class="fas fa-users me-2"></i>Total Student Respondents
                                            </th>
                                            <th>
                                                <i class="fas fa-cog me-2"></i>Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($faculty_results as $faculty): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-user-circle text-primary me-2"></i>
                                                        <?php echo clean($faculty['last_name'] . ', ' . $faculty['first_name']); ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-building text-secondary me-2"></i>
                                                        <?php echo clean($faculty['department']); ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php if ($faculty['average_rating']): ?>
                                                        <div class="d-flex align-items-center gap-2">
                                                            <div class="flex-grow-1">
                                                                <div class="rating-bar">
                                                                    <div class="rating-fill" style="width: <?php echo ($faculty['average_rating'] / 5) * 100; ?>%"></div>
                                                                </div>
                                                            </div>
                                                            <span class="text-primary fw-bold">
                                                                <?php echo number_format($faculty['average_rating'], 2); ?>
                                                            </span>
                                                            <small class="text-muted">/5.00</small>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted">No ratings</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-users text-info me-2"></i>
                                                        <?php echo $faculty['total_responses']; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <button class="btn btn-info" type="button" 
                                                            data-bs-toggle="collapse" 
                                                            data-bs-target="#faculty<?php echo $faculty['id']; ?>">
                                                        <i class="fas fa-list"></i>
                                                        View Details
                                                    </button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan="5" class="p-0">
                                                    <div class="collapse" id="faculty<?php echo $faculty['id']; ?>">
                                                        <div class="card card-body m-3">
                                                            <h6 class="mb-4 d-flex align-items-center gap-2">
                                                                <i class="fas fa-clipboard-list"></i>
                                                                <!-- Detailed Ratings -->
                                                                <!-- Ratings Summary -->
                                                                 Summary of Rating:
                                                                 <div class="d-flex align-items-center">
                                                                    <!-- <i class="fas fa-user-circle text-primary me-2"></i> -->
                                                                    <?php echo clean($faculty['last_name'] . ', ' . $faculty['first_name']); ?>
                                                                </div>
                                                            </h6>
                                                            <?php
                                                            $current_category = '';
                                                            foreach ($faculty['question_ratings'] as $rating):
                                                                if ($rating['category'] !== $current_category):
                                                                    if ($current_category !== '') echo '</div>';
                                                                    $current_category = $rating['category'];
                                                            ?>
                                                                <div class="mb-4">
                                                                    <h6 class="text-primary mb-3 d-flex align-items-center gap-2">
                                                                        <i class="fas fa-folder"></i>
                                                                        <?php echo clean($rating['category']); ?>
                                                                    </h6>
                                                            <?php endif; ?>
                                                                    <div class="mb-3">
                                                                        <p class="mb-2 fw-semibold">
                                                                            <?php echo clean($rating['question_text']); ?>
                                                                        </p>
                                                                        <?php if ($rating['average_rating']): ?>
                                                                            <div class="d-flex align-items-center gap-3 mb-2">
                                                                                <div class="flex-grow-1">
                                                                                    <div class="rating-bar">
                                                                                        <div class="rating-fill" style="width: <?php echo ($rating['average_rating'] / 5) * 100; ?>%"></div>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="text-nowrap">
                                                                                    <span class="text-primary fw-bold">
                                                                                        <?php echo number_format($rating['average_rating'], 2); ?>
                                                                                    </span>
                                                                                    <small class="text-muted">/5.00</small>
                                                                                </div>
                                                                                <small class="text-muted">
                                                                                    (<?php echo $rating['total_responses']; ?> responses)
                                                                                </small>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                        <?php if ($rating['text_responses']): ?>
                                                                            <div class="comment-section">
                                                                                <h6 class="mb-3 d-flex align-items-center gap-2">
                                                                                    <i class="fas fa-comments"></i>
                                                                                    Comments
                                                                                </h6>
                                                                                <?php foreach (explode('||', $rating['text_responses']) as $comment): ?>
                                                                                    <?php if ($comment): ?>
                                                                                        <div class="comment-item">
                                                                                            <i class="fas fa-comment-alt text-muted me-2"></i>
                                                                                            <?php echo clean($comment); ?>
                                                                                        </div>
                                                                                    <?php endif; ?>
                                                                                <?php endforeach; ?>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                    </div>
                                                            <?php endforeach; ?>
                                                            </div>
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
                </div>
            </div>
        <?php elseif ($selected_survey): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle fa-lg"></i>
                <span>No responses recorded for this survey yet.</span>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 