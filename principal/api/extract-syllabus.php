<?php
// syllabus_management.php
session_start();
include('header.php');
require_once '../config.php';

$college_code = $_SESSION['college_code'];

// Fetch branches
$branch_query = "SELECT branch_name, branch_code FROM branch WHERE college_code = ?";
$stmt = $conn->prepare($branch_query);
$stmt->bind_param("s", $college_code);
$stmt->execute();
$branch_result = $stmt->get_result();
$branches = $branch_result->fetch_all(MYSQLI_ASSOC);

// Fetch semesters
$semesters = range(1, 8);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Syllabus Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .subject-list {
            margin-bottom: 20px;
        }
        .subject-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        .subject-item input {
            margin-right: 10px;
        }
        .preview-container {
            margin-top: 20px;
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 5px;
        }
        .chapter-container {
            margin-left: 20px;
        }
        .topic-container {
            margin-left: 40px;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h2>Syllabus Management</h2>
        
        <!-- Step 1: Initial Form -->
        <div id="initial-form">
            <form id="uploadForm" class="mb-4">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="branch" class="form-label">Branch</label>
                        <select class="form-select" id="branch" name="branch" required>
                            <option value="">Select Branch</option>
                            <?php foreach ($branches as $branch): ?>
                                <option value="<?php echo $branch['branch_code']; ?>">
                                    <?php echo $branch['branch_name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="semester" class="form-label">Semester</label>
                        <select class="form-select" id="semester" name="semester" required>
                            <option value="">Select Semester</option>
                            <?php foreach ($semesters as $sem): ?>
                                <option value="<?php echo $sem; ?>">Semester <?php echo $sem; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Known Subjects</label>
                    <div id="subjectList" class="subject-list">
                        <div class="subject-item">
                            <input type="text" class="form-control" placeholder="Subject Code" name="subject_codes[]">
                            <input type="text" class="form-control" placeholder="Subject Name" name="subject_names[]">
                            <button type="button" class="btn btn-danger btn-sm ms-2 remove-subject">Remove</button>
                        </div>
                    </div>
                    <button type="button" class="btn btn-secondary" id="addSubject">Add Subject</button>
                </div>

                <div class="mb-3">
                    <label for="syllabusFile" class="form-label">Upload Syllabus PDF</label>
                    <input type="file" class="form-control" id="syllabusFile" name="file" accept=".pdf" required>
                </div>

                <button type="submit" class="btn btn-primary">Extract Syllabus</button>
            </form>
        </div>

        <!-- Step 2: Preview and Edit -->
        <div id="preview-container" class="preview-container" style="display: none;">
            <h3>Preview and Edit Syllabus</h3>
            <div id="syllabusPreview"></div>
            <div class="mt-3">
                <button type="button" class="btn btn-primary" id="saveButton">Save Syllabus</button>
                <button type="button" class="btn btn-secondary" id="backButton">Back</button>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Add Subject
            $('#addSubject').click(function() {
                const newSubject = `
                    <div class="subject-item">
                        <input type="text" class="form-control" placeholder="Subject Code" name="subject_codes[]">
                        <input type="text" class="form-control" placeholder="Subject Name" name="subject_names[]">
                        <button type="button" class="btn btn-danger btn-sm ms-2 remove-subject">Remove</button>
                    </div>
                `;
                $('#subjectList').append(newSubject);
            });

            // Remove Subject
            $(document).on('click', '.remove-subject', function() {
                $(this).closest('.subject-item').remove();
            });

            // Handle form submission
            $('#uploadForm').submit(function(e) {
                e.preventDefault();
                
                const formData = new FormData();
                formData.append('file', $('#syllabusFile')[0].files[0]);
                formData.append('branch_code', $('#branch').val());
                formData.append('semester', $('#semester').val());
                formData.append('college_code', '<?php echo $college_code; ?>');

                // Prepare known subjects
                const knownSubjects = [];
                const subjectCodes = $('input[name="subject_codes[]"]').map(function() {
                    return $(this).val();
                }).get();
                const subjectNames = $('input[name="subject_names[]"]').map(function() {
                    return $(this).val();
                }).get();

                for (let i = 0; i < subjectCodes.length; i++) {
                    if (subjectCodes[i] && subjectNames[i]) {
                        knownSubjects.push({
                            code: subjectCodes[i],
                            name: subjectNames[i]
                        });
                    }
                }

                formData.append('known_subjects', JSON.stringify(knownSubjects));

                $.ajax({
                    url: 'syllabus_api.php?action=extract',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.status === 'success') {
                            displaySyllabusPreview(response.data);
                            $('#initial-form').hide();
                            $('#preview-container').show();
                        } else {
                            alert('Error: ' + response.error);
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('Error: ' + error);
                    }
                });
            });

            // Handle save button click
            $('#saveButton').click(function() {
                const syllabusData = collectEditedData();
                const data = {
                    syllabus_data: syllabusData,
                    branch_code: $('#branch').val(),
                    semester: $('#semester').val(),
                    college_code: '<?php echo $college_code; ?>'
                };

                $.ajax({
                    url: 'syllabus_api.php?action=save',
                    type: 'POST',
                    data: JSON.stringify(data),
                    contentType: 'application/json',
                    success: function(response) {
                        if (response.status === 'success') {
                            alert('Syllabus saved successfully!');
                            window.location.reload();
                        } else {
                            alert('Error: ' + response.error);
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('Error: ' + error);
                    }
                });
            });

            // Handle back button click
            $('#backButton').click(function() {
                $('#preview-container').hide();
                $('#initial-form').show();
            });

            // Function to display syllabus preview
            function displaySyllabusPreview(data) {
                let html = '';
                data.subjects.forEach((subject, subjectIndex) => {
                    html += `
                        <div class="subject-section mb-4">
                            <h4>Subject: ${subject.subject_name} (${subject.subject_code})</h4>
                            <div class="chapters-container">
                    `;
                    
                    subject.chapters.forEach((chapter, chapterIndex) => {
                        html += `
                            <div class="chapter-container mb-3">
                                <h5>Unit ${chapter.unit_number}: 
                                    <input type="text" class="form-control d-inline-block w-auto" 
                                           value="${chapter.chapter_name}"
                                           data-subject="${subjectIndex}"
                                           data-chapter="${chapterIndex}">
                                </h5>
                                <div class="topic-container">
                                    <ul class="list-group">
                        `;
                        
                        chapter.topics.forEach((topic, topicIndex) => {
                            html += `
                                <li class="list-group-item">
                                    <input type="text" class="form-control"
                                           value="${topic}"
                                           data-subject="${subjectIndex}"
                                           data-chapter="${chapterIndex}"
                                           data-topic="${topicIndex}">
                                </li>
                            `;
                        });
                        
                        html += `
                                    </ul>
                                </div>
                            </div>
                        `;
                    });
                    
                    html += `
                            </div>
                        </div>
                    `;
                });
                
                $('#syllabusPreview').html(html);
            }

            // Function to collect edited data
            function collectEditedData() {
                const syllabusData = { subjects: [] };
                
                $('.subject-section').each(function(subjectIndex) {
                    const subject = {
                        subject_code: $(this).find('h4').text().match(/\((.*?)\)/)[1],
                        subject_name: $(this).find('h4').text().split(' (')[0].replace('Subject: ', ''),
                        chapters: []
                    };
                    
                    $(this).find('.chapter-container').each(function() {
                        const chapter = {
                            unit_number: parseInt($(this).find('h5').text().match(/Unit (\d+):/)[1]),
                            chapter_name: $(this).find('h5 input').val(),
                            topics: []
                        };
                        
                        $(this).find('.topic-container input').each(function() {
                            chapter.topics.push($(this).val());
                        });
                        
                        subject.chapters.push(chapter);
                    });
                    
                    syllabusData.subjects.push(subject);
                });
                
                return syllabusData;
            }
        });
    </script>
</body>
</html>