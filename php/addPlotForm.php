<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Collect form data
    $name = $_POST['name'];
    $workingGroup = $_POST['workingGroup'];
    $plotName = $_POST['plotName'];
    $description = $_POST['description'];
    $instructions = $_POST['instructions'];

    // Collect file data
    $files = $_FILES['plotFile'];

    // Email setup
    $to = 'roark@jlab.org';
    $subject = 'New Plot Type Submission';
    $boundary = md5(uniqid(time()));

    // Email headers
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";

    // Email body
    $message = "--{$boundary}\r\n";
    $message .= "Content-Type: text/html; charset=UTF-8\r\n";
    $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $message .= "<p><strong>Name:</strong> $name</p>
                 <p><strong>Working Group:</strong> $workingGroup</p>
                 <p><strong>Plot Name:</strong> $plotName</p>
                 <p><strong>Description:</strong> $description</p>
                 <p><strong>Instructions:</strong> $instructions</p>\r\n";
    $message .= "--{$boundary}\r\n";

    // Attach files
    for ($i = 0; $i < count($files['name']); $i++) {
        if ($files['error'][$i] == UPLOAD_ERR_OK) {
            $fileName = $files['name'][$i];
            $fileType = $files['type'][$i];
            $fileContent = chunk_split(base64_encode(file_get_contents($files['tmp_name'][$i])));

            $message .= "Content-Type: {$fileType}; name=\"{$fileName}\"\r\n";
            $message .= "Content-Disposition: attachment; filename=\"{$fileName}\"\r\n";
            $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $message .= "{$fileContent}\r\n";
            $message .= "--{$boundary}\r\n";
        }
    }

    // Send email
    if (mail($to, $subject, $message, $headers)) {
        echo json_encode(['status' => 'success', 'message' => 'Message has been sent']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Message could not be sent']);
    }
}
?>
