<?php
header('Content-Type: application/json');
require_once '../config.php';  // Make sure this contains your MySQLi connection ($conn)

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to log debug information
function debugLog($message, $data = null) {
    $logMessage = date('Y-m-d H:i:s') . " - " . $message;
    if ($data !== null) {
        $logMessage .= "\n" . print_r($data, true);
    }
    file_put_contents('debug_syllabus.log', $logMessage . "\n\n", FILE_APPEND);
}

try {
    // Get and decode JSON data
    $jsonData = file_get_contents('php://input');
    debugLog("Received JSON data:", $jsonData);
    
    $saveData = json_decode($jsonData, true);
    debugLog("Decoded data:", $saveData);
    
    if ($saveData === null) {
        throw new Exception('Invalid JSON data received: ' . json_last_error_msg());
    }

    debugLog("Started transaction");

    // Validate main structure
    if (!isset($saveData['syllabus_data']) || !isset($saveData['syllabus_data']['subjects'])) {
        throw new Exception('Missing syllabus_data or subjects array');
    }

    // Prevent duplicate subjects in JSON
    $uniqueSubjects = [];
    foreach ($saveData['syllabus_data']['subjects'] as $subject) {
        $key = $subject['subject_code'] . '-' . $saveData['branch_code'] . '-' . $saveData['semester'] . '-' . $saveData['college_code'];
        
        if (!isset($uniqueSubjects[$key])) {
            $uniqueSubjects[$key] = $subject;
        }
    }
    $saveData['syllabus_data']['subjects'] = array_values($uniqueSubjects);

    // Start transaction
    $conn->begin_transaction();

    foreach ($saveData['syllabus_data']['subjects'] as $subject) {
        debugLog("Processing subject:", $subject);
        
        if (!isset($subject['subject_code']) || !isset($subject['subject_name'])) {
            throw new Exception('Missing subject code or name');
        }

        // Check if subject already exists
        $stmt = $conn->prepare("
            SELECT subject_id FROM allsubject 
            WHERE Subject_Code = ? AND Branch_Code = ? AND semester = ? AND college_code = ?
        ");
        $stmt->bind_param("ssss", 
            $subject['subject_code'], 
            $saveData['branch_code'], 
            $saveData['semester'], 
            $saveData['college_code']
        );
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($subjectId);
            $stmt->fetch();
            debugLog("Skipping duplicate subject:", $subject['subject_code']);
        } else {
            // Insert subject
            $stmt = $conn->prepare("
                INSERT INTO allsubject (Subject_Code, Subject, Branch_Code, semester, college_code)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("sssss", 
                $subject['subject_code'], 
                $subject['subject_name'], 
                $saveData['branch_code'], 
                $saveData['semester'], 
                $saveData['college_code']
            );
            $stmt->execute();
            $subjectId = $conn->insert_id;
            debugLog("Inserted subject with ID:", $subjectId);
        }

        // Process chapters
        if (!isset($subject['chapters']) || !is_array($subject['chapters'])) {
            debugLog("No chapters found for subject:", $subject['subject_code']);
            continue;
        }

        foreach ($subject['chapters'] as $chapter) {
            debugLog("Processing chapter:", $chapter);

            if (!isset($chapter['chapter_name'])) {
                throw new Exception('Missing chapter name');
            }

            $stmt = $conn->prepare("
                INSERT INTO chapters (chapter_name, subject_id)
                VALUES (?, ?)
            ");
            $stmt->bind_param("si", $chapter['chapter_name'], $subjectId);
            $stmt->execute();
            $chapterId = $conn->insert_id;
            debugLog("Inserted chapter with ID:", $chapterId);

            // Process topics
            if (!isset($chapter['topics']) || !is_array($chapter['topics'])) {
                debugLog("No topics found for chapter:", $chapter['chapter_name']);
                continue;
            }

            foreach ($chapter['topics'] as $topic) {
                debugLog("Processing topic:", $topic);

                $stmt = $conn->prepare("
                    INSERT INTO topics (topic_name, chapter_id)
                    VALUES (?, ?)
                ");
                $stmt->bind_param("si", $topic, $chapterId);
                $stmt->execute();
                debugLog("Inserted topic");
            }
        }
    }

    // Commit transaction
    $conn->commit();
    debugLog("Transaction committed successfully");

    echo json_encode([
        'status' => 'success',
        'message' => 'Syllabus saved successfully'
    ]);

} catch (mysqli_sql_exception $e) {
    if ($conn->errno == 1062) { // Duplicate entry error
        debugLog("Duplicate entry error:", $e->getMessage());
    } else {
        debugLog("Database error - Rolling back transaction:", $e->getMessage());
    }
    $conn->rollback();
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'error' => 'Database error: ' . $e->getMessage()
    ]);

} catch (Exception $e) {
    $conn->rollback();
    debugLog("General error - Rolling back transaction:", $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'error' => $e->getMessage()
    ]);
}
?>
