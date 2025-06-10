<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

requireAdmin();

$success = $error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'create_survey') {
            $title = sanitize($conn, $_POST['title']);
            $description = sanitize($conn, $_POST['description']);
            $semester_id = sanitize($conn, $_POST['semester_id']);
            $start_date = sanitize($conn, $_POST['start_date']);
            $end_date = sanitize($conn, $_POST['end_date']);
            $use_default = isset($_POST['use_default']) ? true : false;
            
            $stmt = $conn->prepare("INSERT INTO surveys (title, description, semester_id, start_date, end_date, status) VALUES (?, ?, ?, ?, ?, 'draft')");
            $stmt->bind_param("ssiss", $title, $description, $semester_id, $start_date, $end_date);
            
            if ($stmt->execute()) {
                $survey_id = $conn->insert_id;
                
                if ($use_default) {
                    // Add default questions
                    $questions = [
                        ['Teaching Effectiveness', 'The instructor explains the subject matter clearly and effectively'],
                        ['Teaching Effectiveness', 'The instructor uses appropriate teaching methods and materials'],
                        ['Teaching Effectiveness', 'The instructor encourages student participation and engagement'],
                        ['Class Management', 'The instructor starts and ends the class on time'],
                        ['Class Management', 'The instructor maintains order and discipline in the classroom'],
                        ['Student Development', 'The instructor provides helpful feedback on student work'],
                        ['Student Development', 'The instructor shows concern for student learning and progress'],
                        ['Professional Qualities', 'The instructor demonstrates mastery of the subject matter'],
                        ['Professional Qualities', 'The instructor is professional in appearance and behavior'],
                        ['Overall Assessment', 'Overall, how would you rate this instructor?']
                    ];
                    
                    $stmt = $conn->prepare("INSERT INTO questions (survey_id, category, question_text, question_type, order_num) VALUES (?, ?, ?, 'rating', ?)");
                    foreach ($questions as $index => $question) {
                        $category = $question[0];
                        $question_text = $question[1];
                        $order = $index + 1;
                        $stmt->bind_param("issi", $survey_id, $category, $question_text, $order);
                        $stmt->execute();
                    }
                    $success = "Survey created successfully with default questions. You can now customize the questions.";
                } else {
                    $success = "Survey created successfully. Please add your questions now.";
                }
                // Redirect to questions page
                header("Location: survey_questions.php?id=" . $survey_id);
                exit();
            } else {
                $error = "Error creating survey";
            }
        } elseif ($_POST['action'] === 'update_status') {
            $survey_id = sanitize($conn, $_POST['survey_id']);
            $status = sanitize($conn, $_POST['status']);
            
            $stmt = $conn->prepare("UPDATE surveys SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $status, $survey_id);
            
            if ($stmt->execute()) {
                $success = "Survey status updated successfully";
            } else {
                $error = "Error updating survey status";
            }
        } elseif ($_POST['action'] === 'delete_survey') {
            $survey_id = sanitize($conn, $_POST['survey_id']);
            
            // Check if survey exists and has no responses
            $stmt = $conn->prepare("SELECT s.*, (SELECT COUNT(*) FROM responses r WHERE r.survey_id = s.id) as response_count FROM surveys s WHERE s.id = ?");
            $stmt->bind_param("i", $survey_id);
            $stmt->execute();
            $survey = $stmt->get_result()->fetch_assoc();
            
            if ($survey && $survey['response_count'] == 0) {
                $conn->begin_transaction();
                try {
                    // Delete associated questions
                    $stmt = $conn->prepare("DELETE FROM questions WHERE survey_id = ?");
                    $stmt->bind_param("i", $survey_id);
                    $stmt->execute();
                    
                    // Delete the survey
                    $stmt = $conn->prepare("DELETE FROM surveys WHERE id = ?");
                    $stmt->bind_param("i", $survey_id);
                    $stmt->execute();
                    
                    $conn->commit();
                    $success = "Survey deleted successfully";
                } catch (Exception $e) {
                    $conn->rollback();
                    $error = "Error deleting survey";
                }
            } else {
                $error = "Cannot delete survey that has responses";
            }
        }
    }
}

// Get active semester
$stmt = $conn->prepare("SELECT * FROM semesters WHERE is_active = 1 LIMIT 1");
$stmt->execute();
$active_semester = $stmt->get_result()->fetch_assoc();

// Get all semesters for dropdown
$result = $conn->query("SELECT * FROM semesters ORDER BY start_date DESC");
$semesters = $result->fetch_all(MYSQLI_ASSOC);

// Get all surveys with semester info
$query = "SELECT s.*, sem.name as semester_name,
          (SELECT COUNT(*) FROM responses r WHERE r.survey_id = s.id) as response_count
          FROM surveys s
          LEFT JOIN semesters sem ON s.semester_id = sem.id
          ORDER BY s.created_at DESC";
$result = $conn->query($query);
$surveys = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Surveys - Student Assessment System</title>
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

        .form-check-input {
            border: 2px solid #e2e8f0;
        }

        .form-check-input:checked {
            background-color: #667eea;
            border-color: #667eea;
        }

        .form-text {
            color: #718096;
            font-size: 0.875rem;
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

        .btn-info {
            background: #ebf8ff;
            border: none;
            color: #2c5282;
        }

        .btn-info:hover {
            background: #bee3f8;
            color: #2a4365;
        }

        .btn-success {
            background: #def7ec;
            border: none;
            color: #03543f;
        }

        .btn-success:hover {
            background: #bcf0da;
            color: #014737;
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

        .badge.bg-success {
            background: #def7ec !important;
            color: #03543f;
        }

        .badge.bg-danger {
            background: #fde8e8 !important;
            color: #9b1c1c;
        }

        .badge.bg-secondary {
            background: #edf2f7 !important;
            color: #4a5568;
        }

        .survey-period {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #718096;
            font-size: 0.875rem;
        }

        .survey-responses {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: #edf2f7;
            color: #4a5568;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
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
                <i class="fas fa-poll"></i>
                Manage Surveys
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
                        <h5 class="card-title">
                            <i class="fas fa-plus-circle"></i>
                            Create New Survey
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="create_survey">
                            <div class="mb-3">
                                <label for="title" class="form-label">
                                    <i class="fas fa-heading me-2"></i>Survey Title
                                </label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">
                                    <i class="fas fa-align-left me-2"></i>Description
                                </label>
                                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="semester_id" class="form-label">
                                    <i class="fas fa-calendar-alt me-2"></i>Semester
                                </label>
                                <select class="form-select" id="semester_id" name="semester_id" required>
                                    <option value="">Select Semester</option>
                                    <?php foreach ($semesters as $semester): ?>
                                        <option value="<?php echo $semester['id']; ?>"
                                                <?php echo $semester['is_active'] ? 'selected' : ''; ?>>
                                            <?php echo clean($semester['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="start_date" class="form-label">
                                    <i class="fas fa-calendar-plus me-2"></i>Start Date
                                </label>
                                <input type="date" class="form-control" id="start_date" name="start_date" required>
                            </div>
                            <div class="mb-3">
                                <label for="end_date" class="form-label">
                                    <i class="fas fa-calendar-minus me-2"></i>End Date
                                </label>
                                <input type="date" class="form-control" id="end_date" name="end_date" required>
                            </div>
                            <div class="mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="use_default" name="use_default" value="1" checked>
                                    <label class="form-check-label" for="use_default">
                                        <i class="fas fa-tasks me-2"></i>Include default assessment questions
                                    </label>
                                    <div class="form-text">You can add or modify questions after creating the survey</div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-plus-circle me-2"></i>Create Survey
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
                            Survey List
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>
                                            <i class="fas fa-poll me-2"></i>Title
                                        </th>
                                        <th>
                                            <i class="fas fa-calendar-alt me-2"></i>Semester
                                        </th>
                                        <th>
                                            <i class="fas fa-clock me-2"></i>Period
                                        </th>
                                        <th>
                                            <i class="fas fa-info-circle me-2"></i>Status
                                        </th>
                                        <th>
                                            <i class="fas fa-users me-2"></i>Responses
                                        </th>
                                        <th>
                                            <i class="fas fa-cog me-2"></i>Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($surveys as $survey): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo clean($survey['title']); ?></strong>
                                            </td>
                                            <td><?php echo clean($survey['semester_name']); ?></td>
                                            <td>
                                                <div class="survey-period">
                                                    <i class="fas fa-calendar-alt"></i>
                                                    <span>
                                                        <?php echo date('M j, Y', strtotime($survey['start_date'])); ?> to 
                                                        <?php echo date('M j, Y', strtotime($survey['end_date'])); ?>
                                                    </span>
                                                </div>
                                            </td>
                                            <td>
                                                <?php
                                                $status_class = 'secondary';
                                                $status_icon = 'clock';
                                                if ($survey['status'] === 'active') {
                                                    $status_class = 'success';
                                                    $status_icon = 'check-circle';
                                                } elseif ($survey['status'] === 'closed') {
                                                    $status_class = 'danger';
                                                    $status_icon = 'times-circle';
                                                }
                                                ?>
                                                <span class="badge bg-<?php echo $status_class; ?>">
                                                    <i class="fas fa-<?php echo $status_icon; ?> me-1"></i>
                                                    <?php echo ucfirst($survey['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="survey-responses">
                                                    <i class="fas fa-users"></i>
                                                    <?php echo $survey['response_count']; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="survey_questions.php?id=<?php echo $survey['id']; ?>" 
                                                       class="btn btn-info">
                                                        <i class="fas fa-list-check me-1"></i> Questions
                                                    </a>
                                                    <?php if ($survey['status'] === 'draft'): ?>
                                                        <form method="POST" action="" style="display: inline;">
                                                            <input type="hidden" name="action" value="update_status">
                                                            <input type="hidden" name="survey_id" value="<?php echo $survey['id']; ?>">
                                                            <input type="hidden" name="status" value="active">
                                                            <button type="submit" class="btn btn-success">
                                                                <i class="fas fa-play me-1"></i> Activate
                                                            </button>
                                                        </form>
                                                    <?php elseif ($survey['status'] === 'active'): ?>
                                                        <form method="POST" action="" style="display: inline;">
                                                            <input type="hidden" name="action" value="update_status">
                                                            <input type="hidden" name="survey_id" value="<?php echo $survey['id']; ?>">
                                                            <input type="hidden" name="status" value="closed">
                                                            <button type="submit" class="btn btn-danger">
                                                                <i class="fas fa-stop me-1"></i> Close
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    <?php if ($survey['response_count'] == 0): ?>
                                                        <form method="POST" action="" style="display: inline;" data-delete-survey>
                                                            <input type="hidden" name="action" value="delete_survey">
                                                            <input type="hidden" name="survey_id" value="<?php echo $survey['id']; ?>">
                                                            <button type="submit" class="btn btn-danger">
                                                                <i class="fas fa-trash me-1"></i> Delete
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
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
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add confirmation for survey deletion
        document.addEventListener('DOMContentLoaded', function() {
            const deleteForms = document.querySelectorAll('form[data-delete-survey]');
            deleteForms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    if (confirm('Are you sure you want to delete this survey? This action cannot be undone.')) {
                        this.submit();
                    }
                });
            });

            // Date validation
            const startDate = document.getElementById('start_date');
            const endDate = document.getElementById('end_date');

            if (startDate && endDate) {
                startDate.addEventListener('change', function() {
                    endDate.min = this.value;
                });

                endDate.addEventListener('change', function() {
                    startDate.max = this.value;
                });

                // Set min date to today for start date
                const today = new Date().toISOString().split('T')[0];
                startDate.min = today;
            }
        });
    </script>
</body>
</html> 