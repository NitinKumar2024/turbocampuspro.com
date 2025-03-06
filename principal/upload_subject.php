<?php 
// Start session and include necessary files
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
$semesters = range(1, 8); // Creates array from 1 to 8
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Syllabus Extraction System</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
     <style>
        :root {
            --primary-color: #3498db;
            --primary-dark: #2980b9;
            --success-color: #2ecc71;
            --error-color: #e74c3c;
            --light-gray: #f8f9fa;
            --dark-gray: #495057;
            --border-color: #dee2e6;
            --box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            --transition: all 0.3s ease;
            --border-radius: 8px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif;
          
            color: #333;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
           
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 2.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        header {
            margin-bottom: 2rem;
            text-align: center;
        }

        h1 {
            color: var(--primary-dark);
            font-size: 2.2rem;
            margin-bottom: 0.5rem;
        }

        .subtitle {
            color: var(--dark-gray);
            font-weight: 400;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--dark-gray);
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #adb5bd;
        }

        input[type="text"],
        input[type="number"] {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            transition: var(--transition);
            font-size: 1rem;
        }

        input[type="file"] {
            width: 100%;
            padding: 0.75rem;
            border: 1px dashed var(--border-color);
            border-radius: 4px;
            transition: var(--transition);
            background-color: var(--light-gray);
        }

        input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.25);
        }

        .file-upload-container {
            border: 2px dashed var(--border-color);
            padding: 2rem;
            text-align: center;
            border-radius: var(--border-radius);
            background-color: var(--light-gray);
            transition: var(--transition);
            cursor: pointer;
            margin-bottom: 1.5rem;
        }

        .file-upload-container:hover {
            border-color: var(--primary-color);
            background-color: rgba(52, 152, 219, 0.05);
        }

        .file-upload-icon {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .file-upload-text {
            color: var(--dark-gray);
        }

        .file-name {
            margin-top: 0.5rem;
            font-weight: 600;
            color: var(--primary-dark);
            display: none;
        }

        button {
            background: var(--primary-color);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        button:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--box-shadow);
        }

        button:disabled {
            background: #bdc3c7;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .secondary-button {
            background: white;
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
        }

        .secondary-button:hover {
            background: rgba(52, 152, 219, 0.1);
            color: var(--primary-dark);
            transform: translateY(-1px);
        }

        .remove-subject {
            background: transparent;
            color: var(--error-color);
            padding: 0.5rem;
            box-shadow: none;
        }

        .remove-subject:hover {
            background: rgba(231, 76, 60, 0.1);
            transform: none;
            box-shadow: none;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 1.5rem;
        }

        #result {
            margin-top: 2rem;
            padding: 1rem;
            border-radius: var(--border-radius);
            display: none;
        }

        .success {
            background: rgba(46, 204, 113, 0.1);
            color: var(--success-color);
            border: 1px solid rgba(46, 204, 113, 0.3);
        }

        .error {
            background: rgba(231, 76, 60, 0.1);
            color: var(--error-color);
            border: 1px solid rgba(231, 76, 60, 0.3);
        }

        .spinner {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .preview {
            margin-top: 2rem;
            padding: 1.5rem;
            background: var(--light-gray);
            border-radius: var(--border-radius);
            border: 1px solid var(--border-color);
            display: none;
        }

        .preview h3 {
            margin-bottom: 1rem;
            color: var(--primary-dark);
        }

        .preview pre {
            display: none; /* Hide the raw JSON */
        }

        .formatted-preview {
            background: white;
            border-radius: 4px;
            border: 1px solid var(--border-color);
            overflow: auto;
            max-height: 400px;
        }

        .subject-card {
            border-bottom: 1px solid var(--border-color);
            padding: 1rem;
            transition: var(--transition);
        }

        .subject-card:hover {
            background-color: rgba(52, 152, 219, 0.05);
        }

        .subject-card:last-child {
            border-bottom: none;
        }

        .subject-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
        }

        .subject-name-code {
            font-weight: 600;
            color: var(--primary-dark);
        }

        .subject-code-badge {
            background: var(--primary-color);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            margin-right: 0.5rem;
        }

        .subject-content {
            margin-top: 1rem;
            padding-left: 1rem;
            border-left: 3px solid var(--primary-color);
            display: none;
        }

        .unit-header {
            font-weight: 600;
            margin-top: 0.5rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
        }

        .unit-number {
            background: var(--dark-gray);
            color: white;
            padding: 0.15rem 0.4rem;
            border-radius: 4px;
            font-size: 0.8rem;
            margin-right: 0.5rem;
        }

        .topic-list {
            list-style-type: none;
            padding-left: 1.5rem;
        }

        .topic-item {
            position: relative;
            padding: 0.3rem 0;
        }

        .topic-item:before {
            content: "•";
            color: var(--primary-color);
            position: absolute;
            left: -1rem;
        }

        .subject-pair {
            display: flex;
            gap: 1rem;
            align-items: center;
            margin-bottom: 1rem;
        }

        .subject-pair .input-wrapper {
            flex: 1;
        }

        .save-button-container {
            margin-top: 1.5rem;
            display: flex;
            justify-content: flex-end;
        }

        #saveBtn {
            background-color: var(--success-color);
        }

        #saveBtn:hover {
            background-color: #27ae60;
        }

        .tooltip {
            position: relative;
            display: inline-block;
            margin-left: 0.5rem;
        }

        .tooltip .tooltiptext {
            visibility: hidden;
            width: 200px;
            background-color: #555;
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 5px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            margin-left: -100px;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .tooltip:hover .tooltiptext {
            visibility: visible;
            opacity: 1;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1.5rem;
            }

            h1 {
                font-size: 1.8rem;
            }

            .file-upload-container {
                padding: 1.5rem;
            }
        }

          .topic-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }

        .topic-actions {
            display: none;
        }

        .topic-item:hover .topic-actions {
            display: flex;
        }

        .edit-topic-btn, .delete-topic-btn, .save-topic-edit-btn, .cancel-topic-edit-btn,
        .save-new-topic-btn, .cancel-new-topic-btn, .edit-chapter-btn,
        .save-chapter-edit-btn, .cancel-chapter-edit-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 3px;
            margin-left: 5px;
            color: var(--text-color);
            opacity: 0.7;
        }

        .edit-topic-btn:hover, .delete-topic-btn:hover, .edit-chapter-btn:hover {
            opacity: 1;
        }

        .edit-topic-input, .new-topic-input, .edit-chapter-input {
            width: calc(100% - 70px);
            padding: 5px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
        }

        .topic-item.editing .topic-actions, .topic-item.new-topic .topic-actions {
            display: flex;
        }

        .add-topic-container {
            margin-top: 10px;
            padding-left: 20px;
        }

        .add-topic-btn {
            background: none;
            border: none;
            color: var(--primary-color);
            cursor: pointer;
            padding: 5px;
            font-size: 0.9em;
        }

        .add-topic-btn:hover {
            text-decoration: underline;
        }

        .unit-header {
            display: flex;
            align-items: center;
        }

        .chapter-name-text {
            margin-left: 10px;
            flex-grow: 1;
        }

        .edit-chapter-btn {
            margin-left: auto;
        }

        .help-text {
            font-size: 0.9em;
            color: var(--text-muted);
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Syllabus Extraction System</h1>
            <p class="subtitle">Upload, extract, and manage your course syllabi easily</p>
        </header>

        <form id="syllabusForm">
            <div class="file-upload-container" id="dropArea">
                <input type="file" id="file" accept=".pdf" required hidden>
                <i class="fas fa-cloud-upload-alt file-upload-icon"></i>
                <p class="file-upload-text">Drag & drop your syllabus PDF here or click to browse</p>
                <p class="file-name" id="fileName"></p>
            </div>

            <div class="form-group">
                <label for="branch_code">Branch Code</label>
                <div class="input-wrapper">
                    <i class="fas fa-code-branch"></i>
                    <input type="text" id="branch_code" placeholder="e.g., CS" required>
                </div>
            </div>

            <div class="form-group">
                <label for="semester">Semester</label>
                <div class="input-wrapper">
                    <i class="fas fa-calendar-alt"></i>
                    <input type="number" id="semester" min="1" max="8" placeholder="1-8" required>
                </div>
            </div>

            <div class="form-group">
                <label for="college_code">College Code</label>
                <div class="input-wrapper">
                    <i class="fas fa-university"></i>
                    <input type="text" id="college_code" placeholder="e.g., ENGC" required>
                </div>
            </div>

            <div class="form-group">
                <label for="known_subjects_container">
                    Known Subjects
                    <span class="tooltip">
                        <i class="fas fa-question-circle"></i>
                        <span class="tooltiptext">Add subject code and name pairs that you already know about</span>
                    </span>
                </label>
                <div id="known_subjects_container">
                    <div class="subject-pair">
                        <div class="input-wrapper">
                            <i class="fas fa-hashtag"></i>
                            <input type="text" class="subject-code" placeholder="Subject Code (e.g., CODE101)" required>
                        </div>
                        <div class="input-wrapper">
                            <i class="fas fa-book"></i>
                            <input type="text" class="subject-name" placeholder="Subject Name (e.g., Introduction to Programming)" required>
                        </div>
                        <button type="button" class="remove-subject" title="Remove this subject"><i class="fas fa-times"></i></button>
                    </div>
                </div>
                <button type="button" id="addSubjectBtn" class="secondary-button">
                    <i class="fas fa-plus"></i> Add Another Subject
                </button>
                <input type="hidden" id="known_subjects" required>
            </div>

            <div class="form-actions">
                <button type="submit" id="extractBtn">
                    <i class="fas fa-file-export"></i>
                    Extract Syllabus
                </button>
            </div>
        </form>

        <div id="result"></div>
        <div id="preview"></div>
    </div>

    <script>
        $(document).ready(function() {
            let extractedData = null;
            const dropArea = document.getElementById('dropArea');
            const fileInput = document.getElementById('file');
            const fileName = document.getElementById('fileName');

            // Handle file selection via click
            dropArea.addEventListener('click', () => {
                fileInput.click();
            });

            // Handle file selection display
            fileInput.addEventListener('change', () => {
                if (fileInput.files.length > 0) {
                    fileName.textContent = fileInput.files[0].name;
                    fileName.style.display = 'block';
                    dropArea.style.borderColor = 'var(--primary-color)';
                }
            });

            // Handle drag and drop
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropArea.addEventListener(eventName, preventDefaults, false);
            });

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            ['dragenter', 'dragover'].forEach(eventName => {
                dropArea.addEventListener(eventName, highlight, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropArea.addEventListener(eventName, unhighlight, false);
            });

            function highlight() {
                dropArea.style.borderColor = 'var(--primary-color)';
                dropArea.style.backgroundColor = 'rgba(52, 152, 219, 0.1)';
            }

            function unhighlight() {
                dropArea.style.borderColor = 'var(--border-color)';
                dropArea.style.backgroundColor = 'var(--light-gray)';
            }

            dropArea.addEventListener('drop', handleDrop, false);

            function handleDrop(e) {
                const dt = e.dataTransfer;
                const files = dt.files;
                fileInput.files = files;

                if (files.length > 0) {
                    fileName.textContent = files[0].name;
                    fileName.style.display = 'block';
                }
            }

            // Handle adding subject pairs
            $('#addSubjectBtn').on('click', function() {
                const newSubjectPair = `
                    <div class="subject-pair">
                        <div class="input-wrapper">
                            <i class="fas fa-hashtag"></i>
                            <input type="text" class="subject-code" placeholder="Subject Code" required>
                        </div>
                        <div class="input-wrapper">
                            <i class="fas fa-book"></i>
                            <input type="text" class="subject-name" placeholder="Subject Name" required>
                        </div>
                        <button type="button" class="remove-subject" title="Remove this subject"><i class="fas fa-times"></i></button>
                    </div>
                `;
                $('#known_subjects_container').append(newSubjectPair);
            });

            // Handle removing subject pairs
            $(document).on('click', '.remove-subject', function() {
                $(this).closest('.subject-pair').fadeOut(300, function() {
                    $(this).remove();
                    updateSubjectsJSON();
                });
            });

            // Update the hidden input with JSON
            function updateSubjectsJSON() {
                const subjectsObj = {};
                $('.subject-pair').each(function() {
                    const codeInput = $(this).find('.subject-code');
                    const nameInput = $(this).find('.subject-name');

                    const code = codeInput.val().trim();
                    const name = nameInput.val().trim();

                    if (code && name) {
                        subjectsObj[code] = name;
                    }
                });

                $('#known_subjects').val(JSON.stringify(subjectsObj));
            }

            // Update JSON when inputs change
            $(document).on('input', '.subject-code, .subject-name', updateSubjectsJSON);

            $('#syllabusForm').on('submit', function(e) {
                e.preventDefault();

                // Update subjects JSON before submission
                updateSubjectsJSON();

                if (!fileInput.files[0]) {
                    showResult('Please select a PDF file', 'error');
                    return;
                }

                // Validate that we have at least one subject pair with both fields filled
                if ($('#known_subjects').val() === '{}') {
                    showResult('Please add at least one known subject with both code and name', 'error');
                    return;
                }

                const formData = new FormData();
                formData.append('file', fileInput.files[0]);
                formData.append('branch_code', $('#branch_code').val());
                formData.append('semester', $('#semester').val());
                formData.append('college_code', $('#college_code').val());
                formData.append('known_subjects', $('#known_subjects').val());

                // Update button to show loading state
                const extractBtn = $('#extractBtn');
                const originalBtnText = extractBtn.html();
                extractBtn.html('<i class="fas fa-spinner spinner"></i> Processing...');
                extractBtn.prop('disabled', true);

                // Hide previous results
                $('#result').hide().removeClass('success error').empty();
                $('#preview').hide().empty();

                // First API call - Extract syllabus
                $.ajax({
                    url: 'https://smarteduai.turbocampuspro.com/api/extract-syllabus',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.status === 'success') {
                            extractedData = response.data;
                            showResult('Syllabus extracted successfully! Review the data below and click to save.', 'success');

                            // Show preview and save button - without raw JSON
                            let previewContent = `
                                <div class="preview-header">
                                    <h3><i class="fas fa-file-alt"></i> Extracted Data Preview</h3>
                                    <p class="help-text">You can edit, add, or delete topics before saving.</p>
                                </div>
                                <div class="formatted-preview">`;

                            // Format the extracted data in a user-friendly way
                            if (extractedData.subjects && extractedData.subjects.length > 0) {
                                extractedData.subjects.forEach(subject => {
                                    previewContent += `
                                        <div class="subject-card" data-subject-code="${subject.subject_code}">
                                            <div class="subject-header" data-subject="${subject.subject_code}">
                                                <div class="subject-name-code">
                                                    <span class="subject-code-badge">${subject.subject_code}</span>
                                                    ${subject.subject_name}
                                                </div>
                                                <i class="fas fa-chevron-down"></i>
                                            </div>
                                            <div class="subject-content" id="content-${subject.subject_code}">`;

                                    if (subject.chapters && subject.chapters.length > 0) {
                                        subject.chapters.forEach((chapter, chapterIndex) => {
                                            previewContent += `
                                                <div class="chapter" data-subject="${subject.subject_code}" data-chapter-index="${chapterIndex}">
                                                    <div class="unit-header">
                                                        <span class="unit-number">Unit ${chapter.unit_number}</span>
                                                        <span class="chapter-name-text">${chapter.chapter_name}</span>
                                                        <button type="button" class="edit-chapter-btn" title="Edit chapter name">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                    </div>`;

                                            if (chapter.topics && chapter.topics.length > 0) {
                                                previewContent += `<ul class="topic-list">`;
                                                chapter.topics.forEach((topic, topicIndex) => {
                                                    previewContent += `
                                                        <li class="topic-item" data-subject="${subject.subject_code}" data-chapter-index="${chapterIndex}" data-topic-index="${topicIndex}">
                                                            <span class="topic-text">${topic}</span>
                                                            <div class="topic-actions">
                                                                <button type="button" class="edit-topic-btn" title="Edit topic">
                                                                    <i class="fas fa-edit"></i>
                                                                </button>
                                                                <button type="button" class="delete-topic-btn" title="Delete topic">
                                                                    <i class="fas fa-trash-alt"></i>
                                                                </button>
                                                            </div>
                                                        </li>`;
                                                });
                                                previewContent += `</ul>`;
                                            } else {
                                                previewContent += `<ul class="topic-list empty-list"><li>No topics available</li></ul>`;
                                            }

                                            previewContent += `
                                                <div class="add-topic-container">
                                                    <button type="button" class="add-topic-btn" data-subject="${subject.subject_code}" data-chapter-index="${chapterIndex}">
                                                        <i class="fas fa-plus"></i> Add Topic
                                                    </button>
                                                </div>
                                            </div>`;
                                        });
                                    } else {
                                        previewContent += `<p>No chapters available</p>`;
                                    }

                                    previewContent += `</div></div>`;
                                });
                            } else {
                                previewContent += `<p>No subjects found in the extracted data</p>`;
                            }

                            previewContent += `</div>
                                <div class="save-button-container">
                                    <button id="saveBtn">
                                        <i class="fas fa-save"></i> Save Syllabus
                                    </button>
                                </div>
                            `;

                            $('#preview').html(previewContent).fadeIn();

                            // Add toggle functionality for subject content
                            $('.subject-header').on('click', function() {
                                const subjectCode = $(this).data('subject');
                                $(`#content-${subjectCode}`).slideToggle();
                                $(this).find('i').toggleClass('fa-chevron-down fa-chevron-up');
                            });

                            // Add edit topic functionality
                            setupTopicEditButtons();
                        } else {
                            showResult('Error: ' + (response.error || 'Failed to extract syllabus'), 'error');
                        }
                    },
                    error: function(xhr) {
                        showResult('Error: ' + (xhr.responseJSON?.error || 'Failed to extract syllabus. Please check your file and try again.'), 'error');
                    },
                    complete: function() {
                        extractBtn.html(originalBtnText);
                        extractBtn.prop('disabled', false);
                    }
                });
            });

            // Setup edit, delete and add topic functionality
            function setupTopicEditButtons() {
                // Edit topic button click
                $(document).on('click', '.edit-topic-btn', function(e) {
                    e.stopPropagation();
                    const topicItem = $(this).closest('.topic-item');
                    const subjectCode = topicItem.data('subject');
                    const chapterIndex = topicItem.data('chapter-index');
                    const topicIndex = topicItem.data('topic-index');
                    const currentText = topicItem.find('.topic-text').text().trim();

                    // Replace with input field
                    topicItem.find('.topic-text').html(`
                        <input type="text" class="edit-topic-input" value="${currentText}">
                        <button type="button" class="save-topic-edit-btn"><i class="fas fa-check"></i></button>
                        <button type="button" class="cancel-topic-edit-btn"><i class="fas fa-times"></i></button>
                    `);

                    topicItem.addClass('editing');
                    topicItem.find('.edit-topic-input').focus();
                });

                // Save topic edit button click
                $(document).on('click', '.save-topic-edit-btn', function() {
                    const topicItem = $(this).closest('.topic-item');
                    const subjectCode = topicItem.data('subject');
                    const chapterIndex = topicItem.data('chapter-index');
                    const topicIndex = topicItem.data('topic-index');
                    const newText = topicItem.find('.edit-topic-input').val().trim();

                    if (newText) {
                        // Update the data model
                        extractedData.subjects.forEach(subject => {
                            if (subject.subject_code === subjectCode) {
                                subject.chapters[chapterIndex].topics[topicIndex] = newText;
                            }
                        });

                        // Update the UI
                        topicItem.find('.topic-text').text(newText);
                        topicItem.removeClass('editing');
                    }
                });

                // Cancel topic edit button click
                $(document).on('click', '.cancel-topic-edit-btn', function() {
                    const topicItem = $(this).closest('.topic-item');
                    const subjectCode = topicItem.data('subject');
                    const chapterIndex = topicItem.data('chapter-index');
                    const topicIndex = topicItem.data('topic-index');
                    const originalText = extractedData.subjects
                                          .find(s => s.subject_code === subjectCode)
                                          .chapters[chapterIndex]
                                          .topics[topicIndex];

                    topicItem.find('.topic-text').text(originalText);
                    topicItem.removeClass('editing');
                });

                // Delete topic button click
                $(document).on('click', '.delete-topic-btn', function(e) {
                    e.stopPropagation();
                    const topicItem = $(this).closest('.topic-item');
                    const subjectCode = topicItem.data('subject');
                    const chapterIndex = topicItem.data('chapter-index');
                    const topicIndex = topicItem.data('topic-index');

                    if (confirm('Are you sure you want to delete this topic?')) {
                        // Update the data model
                        extractedData.subjects.forEach(subject => {
                            if (subject.subject_code === subjectCode) {
                                subject.chapters[chapterIndex].topics.splice(topicIndex, 1);
                            }
                        });

                        // Update the UI
                        topicItem.fadeOut(300, function() {
                            $(this).remove();
                            updateTopicIndexes(subjectCode, chapterIndex);
                        });
                    }
                });

                // Add topic button click
                $(document).on('click', '.add-topic-btn', function() {
                    const subjectCode = $(this).data('subject');
                    const chapterIndex = $(this).data('chapter-index');
                    const topicList = $(this).closest('.chapter').find('.topic-list');

                    // Create a new row for the new topic
                    const newTopicIndex = topicList.children('.topic-item').length;
                    const newTopicHTML = `
                        <li class="topic-item new-topic" data-subject="${subjectCode}" data-chapter-index="${chapterIndex}" data-topic-index="${newTopicIndex}">
                            <input type="text" class="new-topic-input" placeholder="Enter new topic">
                            <div class="topic-actions">
                                <button type="button" class="save-new-topic-btn"><i class="fas fa-check"></i></button>
                                <button type="button" class="cancel-new-topic-btn"><i class="fas fa-times"></i></button>
                            </div>
                        </li>
                    `;

                    // If the list is empty (contains only "No topics available"), clear it first
                    if (topicList.hasClass('empty-list')) {
                        topicList.empty().removeClass('empty-list');
                    }

                    topicList.append(newTopicHTML);
                    topicList.find('.new-topic-input').focus();
                });

                // Save new topic button click
                $(document).on('click', '.save-new-topic-btn', function() {
                    const topicItem = $(this).closest('.topic-item');
                    const subjectCode = topicItem.data('subject');
                    const chapterIndex = topicItem.data('chapter-index');
                    const newText = topicItem.find('.new-topic-input').val().trim();

                    if (newText) {
                        // Update the data model
                        extractedData.subjects.forEach(subject => {
                            if (subject.subject_code === subjectCode) {
                                subject.chapters[chapterIndex].topics.push(newText);
                            }
                        });

                        // Update the UI
                        const newTopicIndex = extractedData.subjects
                                               .find(s => s.subject_code === subjectCode)
                                               .chapters[chapterIndex]
                                               .topics.length - 1;

                        const updatedTopicHTML = `
                            <li class="topic-item" data-subject="${subjectCode}" data-chapter-index="${chapterIndex}" data-topic-index="${newTopicIndex}">
                                <span class="topic-text">${newText}</span>
                                <div class="topic-actions">
                                    <button type="button" class="edit-topic-btn" title="Edit topic">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="delete-topic-btn" title="Delete topic">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </li>
                        `;

                        topicItem.replaceWith(updatedTopicHTML);
                    } else {
                        topicItem.remove();
                    }
                });

                // Cancel new topic button click
                $(document).on('click', '.cancel-new-topic-btn', function() {
                    $(this).closest('.topic-item').remove();
                });

                // Edit chapter name functionality
                $(document).on('click', '.edit-chapter-btn', function(e) {
                    e.stopPropagation();
                    const chapter = $(this).closest('.chapter');
                    const subjectCode = chapter.data('subject');
                    const chapterIndex = chapter.data('chapter-index');
                    const chapterNameElem = chapter.find('.chapter-name-text');
                    const currentText = chapterNameElem.text().trim();

                    chapterNameElem.html(`
                        <input type="text" class="edit-chapter-input" value="${currentText}">
                        <button type="button" class="save-chapter-edit-btn"><i class="fas fa-check"></i></button>
                        <button type="button" class="cancel-chapter-edit-btn"><i class="fas fa-times"></i></button>
                    `);

                    chapter.addClass('editing');
                    chapter.find('.edit-chapter-input').focus();
                });

                // Save chapter edit button click
                $(document).on('click', '.save-chapter-edit-btn', function() {
                    const chapter = $(this).closest('.chapter');
                    const subjectCode = chapter.data('subject');
                    const chapterIndex = chapter.data('chapter-index');
                    const newText = chapter.find('.edit-chapter-input').val().trim();

                    if (newText) {
                        // Update the data model
                        extractedData.subjects.forEach(subject => {
                            if (subject.subject_code === subjectCode) {
                                subject.chapters[chapterIndex].chapter_name = newText;
                            }
                        });

                        // Update the UI
                        chapter.find('.chapter-name-text').text(newText);
                        chapter.removeClass('editing');
                    }
                });

                // Cancel chapter edit button click
                $(document).on('click', '.cancel-chapter-edit-btn', function() {
                    const chapter = $(this).closest('.chapter');
                    const subjectCode = chapter.data('subject');
                    const chapterIndex = chapter.data('chapter-index');
                    const originalText = extractedData.subjects
                                           .find(s => s.subject_code === subjectCode)
                                           .chapters[chapterIndex]
                                           .chapter_name;

                    chapter.find('.chapter-name-text').text(originalText);
                    chapter.removeClass('editing');
                });
            }

            // Update topic indexes after deletion
            function updateTopicIndexes(subjectCode, chapterIndex) {
                $(`.topic-item[data-subject="${subjectCode}"][data-chapter-index="${chapterIndex}"]`).each(function(index) {
                    $(this).attr('data-topic-index', index);
                });
            }

            // Handle save button click
            $(document).on('click', '#saveBtn', function() {
                if (!extractedData) return;

                const saveData = {
                    syllabus_data: extractedData,
                    branch_code: $('#branch_code').val(),
                    semester: $('#semester').val(),
                    college_code: $('#college_code').val()
                };

                // Update button to show loading state
                const saveBtn = $('#saveBtn');
                const originalBtnText = saveBtn.html();
                saveBtn.html('<i class="fas fa-spinner spinner"></i> Saving...');
                saveBtn.prop('disabled', true);

                // Second API call - Save syllabus
                $.ajax({
                    url: 'new_save_syllabus.php',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify(saveData),
                    success: function(response) {
                        if (response.status === 'success') {
                            showResult('Syllabus saved successfully!', 'success');
                            $('#preview').fadeOut(300, function() {
                                $(this).empty();
                            });
                            $('#syllabusForm')[0].reset();
                            fileName.textContent = '';
                            fileName.style.display = 'none';
                            dropArea.style.borderColor = 'var(--border-color)';
                            extractedData = null;
                        } else {
                            showResult('Error: ' + (response.error || 'Failed to save syllabus'), 'error');
                        }
                    },
                    error: function(xhr) {
                        showResult('Error: ' + (xhr.responseJSON?.error || 'Failed to save syllabus. Please try again.'), 'error');
                    },
                    complete: function() {
                        saveBtn.html(originalBtnText);
                        saveBtn.prop('disabled', false);
                    }
                });
            });

            function showResult(message, type) {
                const resultDiv = $('#result');
                resultDiv.removeClass('success error')
                        .addClass(type)
                        .html(`<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${message}`)
                        .fadeIn();
            }
        });
    </script>
</body>
</html>