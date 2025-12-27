<?php
require_once '../config.php';

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'teacher') {
    header('Location: ../index.php');
    exit();
}

// Get selected quiz if any
$selected_quiz = isset($_GET['quiz_id']) ? (int)$_GET['quiz_id'] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Results - Online Quiz System</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <style>
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .filter-form {
            display: flex;
            gap: 15px;
            align-items: flex-end;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 14px;
            font-weight: 500;
        }
        .status-passed {
            background-color: #e6f4ea;
            color: #1e7e34;
        }
        .status-failed {
            background-color: #fde7e9;
            color: #dc3545;
        }
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .view-details-btn {
            background-color: #1a73e8;
            color: white;
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        .view-details-btn:hover {
            background-color: #1557b0;
            text-decoration: none;
            color: white;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <nav class="sidebar">
            <h2>Teacher Dashboard</h2>
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="create_quiz.php">Create Quiz</a></li>
                <li><a href="manage_quizzes.php">Manage Quizzes</a></li>
                <li><a href="view_results.php" class="active">View Results</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </nav>
        <main class="content">
            <h1>View Results</h1>

            <div class="filter-section">
                <form method="GET" class="filter-form">
                    <div class="form-group" style="flex: 1;">
                        <label for="quiz_id">Select Quiz:</label>
                        <select name="quiz_id" id="quiz_id" onchange="this.form.submit()">
                            <option value="">All Quizzes</option>
                            <?php
                            $quizzes = mysqli_query($conn, 
                                "SELECT * FROM quizzes WHERE teacher_id = " . $_SESSION['user_id'] . " 
                                ORDER BY created_at DESC");
                            while ($quiz = mysqli_fetch_assoc($quizzes)) {
                                $selected = ($selected_quiz == $quiz['id']) ? 'selected' : '';
                                echo "<option value='{$quiz['id']}' {$selected}>" . 
                                    htmlspecialchars($quiz['title']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                </form>
            </div>

            <?php if ($selected_quiz): ?>
            <?php
            $quiz_info = mysqli_fetch_assoc(mysqli_query($conn, 
                "SELECT * FROM quizzes WHERE id = " . $selected_quiz));
            
            $stats = mysqli_fetch_assoc(mysqli_query($conn, 
                "SELECT 
                    COUNT(*) as total_attempts,
                    COUNT(DISTINCT student_id) as total_students,
                    AVG(score) as avg_score,
                    MAX(score) as highest_score,
                    MIN(score) as lowest_score
                FROM quiz_attempts 
                WHERE quiz_id = " . $selected_quiz));
            ?>
            <div class="summary-cards">
                <div class="stat-card">
                    <h3>Total Attempts</h3>
                    <p class="stat-number"><?php echo $stats['total_attempts']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Total Students</h3>
                    <p class="stat-number"><?php echo $stats['total_students']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Average Score</h3>
                    <p class="stat-number"><?php echo number_format($stats['avg_score'] ?? 0, 1); ?>%</p>
                </div>
                <div class="stat-card">
                    <h3>Score Range</h3>
                    <p class="stat-number">
                        <?php 
                        echo number_format($stats['lowest_score'] ?? 0, 1) . "% - " . 
                             number_format($stats['highest_score'] ?? 0, 1) . "%"; 
                        ?>
                    </p>
                </div>
            </div>
            <?php endif; ?>

            <div class="recent-activity">
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Quiz</th>
                            <th>Score</th>
                            <th>Passing Score</th>
                            <th>Attempt Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT qa.*, q.title, q.passing_score, u.username 
                                FROM quiz_attempts qa 
                                JOIN quizzes q ON qa.quiz_id = q.id 
                                JOIN users u ON qa.student_id = u.id
                                WHERE q.teacher_id = " . $_SESSION['user_id'];

                        if ($selected_quiz) {
                            $query .= " AND q.id = " . $selected_quiz;
                        }

                        $query .= " ORDER BY qa.attempt_date DESC";
                        $results = mysqli_query($conn, $query);

                        while ($result = mysqli_fetch_assoc($results)) {
                            $status_class = $result['score'] >= $result['passing_score'] ? 'status-passed' : 'status-failed';
                            $status_text = $result['score'] >= $result['passing_score'] ? 'Passed' : 'Failed';
                            
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($result['username']) . "</td>";
                            echo "<td>" . htmlspecialchars($result['title']) . "</td>";
                            echo "<td>" . $result['score'] . "%</td>";
                            echo "<td>" . $result['passing_score'] . "%</td>";
                            echo "<td>" . date('M d, Y h:i A', strtotime($result['attempt_date'])) . "</td>";
                            echo "<td><span class='status-badge {$status_class}'>{$status_text}</span></td>";
                            echo "<td><a href='view_attempt.php?attempt_id=" . $result['id'] . "' class='view-details-btn'>View Details</a></td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>

                <?php if (mysqli_num_rows($results) == 0): ?>
                <div style="text-align: center; padding: 20px;">
                    <h3>No Results Found</h3>
                    <p>There are no quiz attempts to display.</p>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html> 