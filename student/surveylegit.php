<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

requireStudent();

if (!isset($_GET['id'])) {
    header('Location: surveys.php');
    exit();
}

$survey_id = sanitize($conn, $_GET['id']);
$user_id = $_SESSION['user_id'];

// Get student info
$stmt = $conn->prepare("SELECT * FROM students WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

// Get survey details
$stmt = $conn->prepare("
    SELECT s.*, sem.name as semester_name 
    FROM surveys s 
    JOIN semesters sem ON s.semester_id = sem.id 
    WHERE s.id = ? AND s.status = 'active'
");
$stmt->bind_param("i", $survey_id);
$stmt->execute();
$survey = $stmt->get_result()->fetch_assoc();

if (!$survey) {
    header('Location: surveys.php');
    exit();
}

// Get questions
$stmt = $conn->prepare("SELECT * FROM questions WHERE survey_id = ? ORDER BY order_num");
$stmt->bind_param("i", $survey_id);
$stmt->execute();
$questions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get faculty members
$stmt = $conn->prepare("
    SELECT * FROM faculty 
    WHERE status = 'active' 
    AND id NOT IN (
        SELECT faculty_id 
        FROM responses 
        WHERE survey_id = ? AND student_id = ?
    )
    ORDER BY last_name, first_name
");
$stmt->bind_param("ii", $survey_id, $student['id']);
$stmt->execute();
$faculty_members = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $faculty_id = sanitize($conn, $_POST['faculty_id']);
    
    // Start transaction
    $conn->begin_transaction();
    try {
        // Create response record
        $stmt = $conn->prepare("INSERT INTO responses (student_id, faculty_id, survey_id, semester_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiii", $student['id'], $faculty_id, $survey_id, $survey['semester_id']);
        $stmt->execute();
        $response_id = $conn->insert_id;
        
        // Save responses for each question
        $stmt = $conn->prepare("INSERT INTO response_details (response_id, question_id, rating_value, text_answer) VALUES (?, ?, ?, ?)");
        foreach ($questions as $question) {
            $question_id = $question['id'];
            $rating = isset($_POST['rating'][$question_id]) ? sanitize($conn, $_POST['rating'][$question_id]) : null;
            $text = isset($_POST['text'][$question_id]) ? sanitize($conn, $_POST['text'][$question_id]) : null;
            $stmt->bind_param("iiis", $response_id, $question_id, $rating, $text);
            $stmt->execute();
        }
        
        $conn->commit();
        header('Location: surveys.php?success=1');
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error submitting survey response";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Rating Survey - Student Assessment System</title>
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

        .semester-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--primary-gradient);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.9rem;
            margin-top: 1rem;
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
            padding: 2rem;
        }

        .alert {
            border: none;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .alert-danger {
            background-color: #fde8e8;
            color: #9b1c1c;
        }

        .alert-warning {
            background-color: #fef3c7;
            color: #92400e;
        }

        .alert-info {
            background-color: #e1effe;
            color: #1e429f;
        }

        .form-label {
            color: #4a5568;
            font-weight: 600;
            margin-bottom: 0.75rem;
        }

        .form-select {
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
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

        .question-category {
            background: #f8fafc;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .question-category h5 {
            color: #2d3748;
            font-weight: 600;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .question-item {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }

        .rating-group {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .rating-group input {
            display: none;
        }

        .rating-group label {
            cursor: pointer;
            font-size: 2rem;
            color: #e2e8f0;
            transition: all 0.3s ease;
        }

        .rating-group label:hover,
        .rating-group label:hover ~ label,
        .rating-group input:checked ~ label {
            color: #fbbf24;
            transform: scale(1.1);
        }

        .btn {
            font-weight: 600;
            padding: 0.8rem 1.5rem;
            border-radius: 10px;
            transition: all 0.3s ease;
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
            background: #e2e8f0;
            border: none;
            color: #4a5568;
        }

        .btn-secondary:hover {
            background: #cbd5e0;
            color: #2d3748;
            transform: translateY(-2px);
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            .card-body {
                padding: 1.5rem;
            }
            
            .question-category {
                padding: 1rem;
            }
            
            .question-item {
                padding: 1rem;
            }
            
            .rating-group {
                justify-content: center;
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
                <i class="fas fa-poll"></i>
                <?php echo clean($survey['title']); ?>
            </h2>
            <div class="semester-badge">
                <i class="fas fa-calendar-alt"></i>
                <?php echo clean($survey['semester_name']); ?>
            </div>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger mt-3 mb-0">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo clean($error); ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if (empty($faculty_members)): ?>
            <?php
            // Check if any faculty exists at all
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM faculty WHERE status = 'active'");
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $total_faculty = $result['count'];
            
            if ($total_faculty === 0): ?>
                <div class="alert alert-warning">
                    <h4 class="alert-heading">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        No Faculty Available
                    </h4>
                    <p>There are currently no faculty members available for rating. Please contact the administrator.</p>
                    <hr>
                    <p class="mb-0">
                        <a href="surveys.php" class="btn btn-primary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Surveys
                        </a>
                    </p>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <h4 class="alert-heading">
                        <i class="fas fa-check-circle me-2"></i>
                        All Done!
                    </h4>
                    <p>You have completed rating all faculty members for this survey.</p>
                    <hr>
                    <p class="mb-0">
                        <a href="surveys.php" class="btn btn-primary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Surveys
                        </a>
                    </p>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="fas fa-star"></i>
                        Faculty Rating Form
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="mb-4">
                            <label for="faculty_id" class="form-label">
                                <i class="fas fa-user-tie me-2"></i>Select Faculty Member to Rate
                            </label>
                            <select class="form-select" id="faculty_id" name="faculty_id" required>
                                <option value="">Choose faculty member...</option>
                                <?php foreach ($faculty_members as $faculty): ?>
                                    <option value="<?php echo $faculty['id']; ?>">
                                        <?php echo clean($faculty['last_name'] . ', ' . $faculty['first_name'] . ' (' . $faculty['department'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <?php
                        $current_category = '';
                        foreach ($questions as $question):
                            if ($question['category'] !== $current_category):
                                if ($current_category !== '') echo '</div>';
                                $current_category = $question['category'];
                        ?>
                            <div class="question-category">
                                <h5>
                                    <i class="fas fa-clipboard-list"></i>
                                    <?php echo clean($question['category']); ?>
                                </h5>
                        <?php endif; ?>

                            <div class="question-item">
                                <label class="form-label"><?php echo clean($question['question_text']); ?></label>
                                <?php if ($question['question_type'] === 'rating'): ?>
                                    <div class="rating-group">
                                        <?php for ($i = 5; $i >= 1; $i--): ?>
                                            <input type="radio" 
                                                   id="rating<?php echo $question['id']; ?>-<?php echo $i; ?>" 
                                                   name="rating[<?php echo $question['id']; ?>]" 
                                                   value="<?php echo $i; ?>" 
                                                   required>
                                            <label for="rating<?php echo $question['id']; ?>-<?php echo $i; ?>">★</label>
                                        <?php endfor; ?>
                                    </div>
                                <?php else: ?>
                                    <textarea class="form-control" 
                                              name="text[<?php echo $question['id']; ?>]" 
                                              rows="3" 
                                              placeholder="Enter your response here..."
                                              required></textarea>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        </div>

                        <div class="form-actions">
                            <a href="surveys.php" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i>Submit Rating
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 