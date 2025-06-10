<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

requireAdmin();

if (!isset($_GET['id'])) {
    header('Location: surveys.php');
    exit();
}

$survey_id = sanitize($conn, $_GET['id']);
$success = $error = '';

// Get survey details
$stmt = $conn->prepare("SELECT s.*, sem.name as semester_name 
                       FROM surveys s 
                       LEFT JOIN semesters sem ON s.semester_id = sem.id 
                       WHERE s.id = ?");
$stmt->bind_param("i", $survey_id);
$stmt->execute();
$survey = $stmt->get_result()->fetch_assoc();

if (!$survey) {
    header('Location: surveys.php');
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_question') {
            $category = sanitize($conn, $_POST['category']);
            $question_text = sanitize($conn, $_POST['question_text']);
            $question_type = sanitize($conn, $_POST['question_type']);
            
            // Get max order number
            $stmt = $conn->prepare("SELECT MAX(order_num) as max_order FROM questions WHERE survey_id = ?");
            $stmt->bind_param("i", $survey_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $order_num = ($result['max_order'] ?? 0) + 1;
            
            $stmt = $conn->prepare("INSERT INTO questions (survey_id, category, question_text, question_type, order_num) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("isssi", $survey_id, $category, $question_text, $question_type, $order_num);
            
            if ($stmt->execute()) {
                $success = "Question added successfully";
            } else {
                $error = "Error adding question";
            }
        } elseif ($_POST['action'] === 'delete_question') {
            $question_id = sanitize($conn, $_POST['question_id']);
            
            $stmt = $conn->prepare("DELETE FROM questions WHERE id = ? AND survey_id = ?");
            $stmt->bind_param("ii", $question_id, $survey_id);
            
            if ($stmt->execute()) {
                $success = "Question deleted successfully";
            } else {
                $error = "Error deleting question";
            }
        } elseif ($_POST['action'] === 'update_order') {
            $question_id = sanitize($conn, $_POST['question_id']);
            $direction = sanitize($conn, $_POST['direction']);
            
            // Get current order
            $stmt = $conn->prepare("SELECT order_num FROM questions WHERE id = ?");
            $stmt->bind_param("i", $question_id);
            $stmt->execute();
            $current = $stmt->get_result()->fetch_assoc();
            
            if ($current) {
                $new_order = $direction === 'up' ? $current['order_num'] - 1 : $current['order_num'] + 1;
                
                // Update orders
                $conn->begin_transaction();
                try {
                    // Update the other question that will swap positions
                    $stmt = $conn->prepare("UPDATE questions SET order_num = ? WHERE survey_id = ? AND order_num = ?");
                    $stmt->bind_param("iii", $current['order_num'], $survey_id, $new_order);
                    $stmt->execute();
                    
                    // Update the current question
                    $stmt = $conn->prepare("UPDATE questions SET order_num = ? WHERE id = ?");
                    $stmt->bind_param("ii", $new_order, $question_id);
                    $stmt->execute();
                    
                    $conn->commit();
                    $success = "Question order updated successfully";
                } catch (Exception $e) {
                    $conn->rollback();
                    $error = "Error updating question order";
                }
            }
        }
    }
}

// Get questions
$stmt = $conn->prepare("SELECT * FROM questions WHERE survey_id = ? ORDER BY order_num");
$stmt->bind_param("i", $survey_id);
$stmt->execute();
$questions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Survey Questions - Student Assessment System</title>
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

        .survey-info {
            margin-top: 1rem;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }

        .survey-info p {
            margin: 0;
            color: #4a5568;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            overflow: hidden;
            margin-bottom: 2rem;
            transition: all 0.3s ease;
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

        .form-control, .form-select {
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
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

        .btn-group .btn {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
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

        .btn-danger {
            background: #fde8e8;
            border: none;
            color: #9b1c1c;
        }

        .btn-danger:hover {
            background: #fbd5d5;
            color: #771d1d;
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

        .badge.bg-info {
            background: #ebf8ff !important;
            color: #2c5282;
        }

        .question-text {
            font-weight: 500;
            color: #2d3748;
        }

        .question-category {
            color: #718096;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .order-number {
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #edf2f7;
            color: #4a5568;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            .card-body {
                padding: 1.5rem;
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
                        <a class="nav-link active" href="surveys.php">
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
                <i class="fas fa-list-check"></i>
                Survey Questions
            </h2>
            <div class="survey-info">
                <p>
                    <i class="fas fa-poll me-2"></i>
                    <strong>Survey:</strong> <?php echo clean($survey['title']); ?>
                </p>
                <p class="mt-2">
                    <i class="fas fa-calendar-alt me-2"></i>
                    <strong>Semester:</strong> <?php echo clean($survey['semester_name']); ?>
                </p>
            </div>
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
                        <h5 class="card-title">
                            <i class="fas fa-plus-circle"></i>
                            Add New Question
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="add_question">
                            <div class="mb-3">
                                <label for="category" class="form-label">
                                    <i class="fas fa-tag me-2"></i>Category
                                </label>
                                <input type="text" class="form-control" id="category" name="category" required>
                            </div>
                            <div class="mb-3">
                                <label for="question_text" class="form-label">
                                    <i class="fas fa-question-circle me-2"></i>Question
                                </label>
                                <textarea class="form-control" id="question_text" name="question_text" rows="3" required></textarea>
                            </div>
                            <div class="mb-4">
                                <label for="question_type" class="form-label">
                                    <i class="fas fa-list-ul me-2"></i>Type
                                </label>
                                <select class="form-select" id="question_type" name="question_type" required>
                                    <option value="rating">Rating (1-5)</option>
                                    <option value="text">Text Response</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-plus-circle me-2"></i>Add Question
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
                            Question List
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($questions)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No questions added yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>
                                                <i class="fas fa-sort-numeric-down me-2"></i>Order
                                            </th>
                                            <th>
                                                <i class="fas fa-tag me-2"></i>Category
                                            </th>
                                            <th>
                                                <i class="fas fa-question-circle me-2"></i>Question
                                            </th>
                                            <th>
                                                <i class="fas fa-list-ul me-2"></i>Type
                                            </th>
                                            <th>
                                                <i class="fas fa-cog me-2"></i>Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($questions as $index => $question): ?>
                                            <tr>
                                                <td>
                                                    <div class="order-number">
                                                        <?php echo $question['order_num']; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="question-category">
                                                        <i class="fas fa-tag"></i>
                                                        <?php echo clean($question['category']); ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="question-text">
                                                        <?php echo clean($question['question_text']); ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info">
                                                        <i class="fas fa-<?php echo $question['question_type'] === 'rating' ? 'star' : 'comment'; ?> me-1"></i>
                                                        <?php echo ucfirst($question['question_type']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <?php if ($index > 0): ?>
                                                            <form method="POST" action="" style="display: inline;">
                                                                <input type="hidden" name="action" value="update_order">
                                                                <input type="hidden" name="question_id" value="<?php echo $question['id']; ?>">
                                                                <input type="hidden" name="direction" value="up">
                                                                <button type="submit" class="btn btn-secondary" title="Move Up">
                                                                    <i class="fas fa-arrow-up"></i>
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                        <?php if ($index < count($questions) - 1): ?>
                                                            <form method="POST" action="" style="display: inline;">
                                                                <input type="hidden" name="action" value="update_order">
                                                                <input type="hidden" name="question_id" value="<?php echo $question['id']; ?>">
                                                                <input type="hidden" name="direction" value="down">
                                                                <button type="submit" class="btn btn-secondary" title="Move Down">
                                                                    <i class="fas fa-arrow-down"></i>
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                        <form method="POST" action="" style="display: inline;" data-delete-question>
                                                            <input type="hidden" name="action" value="delete_question">
                                                            <input type="hidden" name="question_id" value="<?php echo $question['id']; ?>">
                                                            <button type="submit" class="btn btn-danger" title="Delete Question">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add confirmation for question deletion
        document.addEventListener('DOMContentLoaded', function() {
            const deleteForms = document.querySelectorAll('form[data-delete-question]');
            deleteForms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    if (confirm('Are you sure you want to delete this question? This action cannot be undone.')) {
                        this.submit();
                    }
                });
            });
        });
    </script>
</body>
</html> 