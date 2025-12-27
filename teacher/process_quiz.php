<?php
require_once '../config.php';

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'teacher') {
    header('Location: ../index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get quiz details
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $time_limit = (int)$_POST['time_limit'];
    $passing_score = (int)$_POST['passing_score'];
    $teacher_id = $_SESSION['user_id'];

    // Start transaction
    mysqli_begin_transaction($conn);

    try {
        // Insert quiz
        $quiz_sql = "INSERT INTO quizzes (title, description, teacher_id, time_limit, passing_score) 
                     VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $quiz_sql);
        mysqli_stmt_bind_param($stmt, "ssiii", $title, $description, $teacher_id, $time_limit, $passing_score);
        mysqli_stmt_execute($stmt);
        $quiz_id = mysqli_insert_id($conn);

        // Process questions
        foreach ($_POST['questions'] as $q_index => $question) {
            // Insert question
            $question_sql = "INSERT INTO questions (quiz_id, question_text, question_type) VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($conn, $question_sql);
            mysqli_stmt_bind_param($stmt, "iss", $quiz_id, $question['text'], $question['type']);
            mysqli_stmt_execute($stmt);
            $question_id = mysqli_insert_id($conn);

            // Process answers
            foreach ($question['answers'] as $a_index => $answer) {
                $is_correct = ($question['correct'] == $a_index) ? 1 : 0;
                $answer_sql = "INSERT INTO answers (question_id, answer_text, is_correct) VALUES (?, ?, ?)";
                $stmt = mysqli_prepare($conn, $answer_sql);
                mysqli_stmt_bind_param($stmt, "isi", $question_id, $answer['text'], $is_correct);
                mysqli_stmt_execute($stmt);
            }
        }

        // Commit transaction
        mysqli_commit($conn);
        header('Location: manage_quizzes.php?success=1');
        exit();

    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        header('Location: create_quiz.php?error=1');
        exit();
    }
} 