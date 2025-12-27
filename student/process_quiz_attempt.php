<?php
require_once '../config.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'student') {
    header('Location: ../index.php');
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] != 'POST' || !isset($_POST['quiz_id'])) {
    header('Location: available_quizzes.php');
    exit();
}

// Validate required fields
if (!isset($_POST['start_time']) || !isset($_POST['answers']) || !is_array($_POST['answers'])) {
    $_SESSION['error'] = "Invalid form submission. Please try again.";
    header('Location: available_quizzes.php');
    exit();
}

$quiz_id = (int)$_POST['quiz_id'];
$student_id = $_SESSION['user_id'];
$start_time = (int)$_POST['start_time'];
$end_time = time();
$time_taken = $end_time - $start_time;

// Get quiz details
$quiz_sql = "SELECT * FROM quizzes WHERE id = ?";
$stmt = mysqli_prepare($conn, $quiz_sql);
mysqli_stmt_bind_param($stmt, "i", $quiz_id);
mysqli_stmt_execute($stmt);
$quiz = mysqli_stmt_get_result($stmt)->fetch_assoc();

if (!$quiz) {
    $_SESSION['error'] = "Quiz not found.";
    header('Location: available_quizzes.php');
    exit();
}

// Check if student has already attempted this quiz
$attempt_check_sql = "SELECT id FROM quiz_attempts WHERE quiz_id = ? AND student_id = ?";
$stmt = mysqli_prepare($conn, $attempt_check_sql);
mysqli_stmt_bind_param($stmt, "ii", $quiz_id, $student_id);
mysqli_stmt_execute($stmt);
if (mysqli_stmt_get_result($stmt)->num_rows > 0) {
    $_SESSION['error'] = "You have already attempted this quiz.";
    header('Location: available_quizzes.php');
    exit();
}

// Check if time limit was exceeded
if ($time_taken > ($quiz['time_limit'] * 60 + 30)) { // Add 30 seconds grace period
    $_SESSION['error'] = "Time limit exceeded. Your answers were not saved.";
    header('Location: available_quizzes.php');
    exit();
}

// Get all questions and their valid answers for this quiz
$questions_sql = "SELECT q.id as question_id, GROUP_CONCAT(a.id) as valid_answer_ids
                 FROM questions q
                 LEFT JOIN answers a ON q.id = a.question_id
                 WHERE q.quiz_id = ?
                 GROUP BY q.id";
$stmt = mysqli_prepare($conn, $questions_sql);
mysqli_stmt_bind_param($stmt, "i", $quiz_id);
mysqli_stmt_execute($stmt);
$questions_result = mysqli_stmt_get_result($stmt);

$question_ids = [];
$valid_answers = [];
while ($row = mysqli_fetch_assoc($questions_result)) {
    $question_ids[] = $row['question_id'];
    $valid_answers[$row['question_id']] = explode(',', $row['valid_answer_ids']);
}

// Validate that all questions are answered and answers are valid
$answers = $_POST['answers'];
foreach ($question_ids as $q_id) {
    if (!isset($answers[$q_id])) {
        $_SESSION['error'] = "Please answer all questions.";
        header('Location: take_quiz.php?quiz_id=' . $quiz_id);
        exit();
    }
    
    // Check if the submitted answer is valid for this question
    if (!in_array($answers[$q_id], $valid_answers[$q_id])) {
        $_SESSION['error'] = "Invalid answer detected. Please try again.";
        header('Location: take_quiz.php?quiz_id=' . $quiz_id);
        exit();
    }
}

// Get correct answers
$correct_answers_sql = "SELECT q.id, a.id as correct_answer_id 
                       FROM questions q 
                       JOIN answers a ON q.id = a.question_id 
                       WHERE q.quiz_id = ? AND a.is_correct = 1";
$stmt = mysqli_prepare($conn, $correct_answers_sql);
mysqli_stmt_bind_param($stmt, "i", $quiz_id);
mysqli_stmt_execute($stmt);
$correct_answers_result = mysqli_stmt_get_result($stmt);

$total_questions = mysqli_num_rows($correct_answers_result);
$correct_count = 0;

// Calculate score
while ($row = mysqli_fetch_assoc($correct_answers_result)) {
    if (isset($answers[$row['id']]) && $answers[$row['id']] == $row['correct_answer_id']) {
        $correct_count++;
    }
}

$score = ($total_questions > 0) ? round(($correct_count / $total_questions) * 100, 1) : 0;

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Save attempt
    $attempt_sql = "INSERT INTO quiz_attempts (quiz_id, student_id, score, time_taken, attempt_date) 
                   VALUES (?, ?, ?, ?, NOW())";
    $stmt = mysqli_prepare($conn, $attempt_sql);
    mysqli_stmt_bind_param($stmt, "iidi", $quiz_id, $student_id, $score, $time_taken);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Failed to save quiz attempt: " . mysqli_stmt_error($stmt));
    }
    
    $attempt_id = mysqli_insert_id($conn);

    // Save student answers
    $answer_sql = "INSERT INTO student_answers (attempt_id, question_id, answer_id) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($conn, $answer_sql);
    
    foreach ($answers as $question_id => $answer_id) {
        mysqli_stmt_bind_param($stmt, "iii", $attempt_id, $question_id, $answer_id);
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Failed to save answer for question {$question_id}: " . mysqli_stmt_error($stmt));
        }
    }

    // Commit transaction
    mysqli_commit($conn);
    
    // Redirect to results page
    header('Location: quiz_results.php?attempt_id=' . $attempt_id);
    exit();

} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    error_log("Quiz submission error: " . $e->getMessage());
    
    // Provide more specific error message based on the exception
    if (strpos($e->getMessage(), 'foreign key constraint fails') !== false) {
        $_SESSION['error'] = "Invalid answer selection detected. Please try again.";
    } else {
        $_SESSION['error'] = "An error occurred while saving your answers. Please try again.";
    }
    
    header('Location: take_quiz.php?quiz_id=' . $quiz_id);
    exit();
} 