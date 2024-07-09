<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Manual sanitization of the inputs
    $firstName = htmlspecialchars(stripslashes(trim($_POST['firstName'])));
    $lastName = htmlspecialchars(stripslashes(trim($_POST['lastName'])));
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $message = htmlspecialchars(stripslashes(trim($_POST['message'])));

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Invalid email format";
        exit;
    }

    // Set your email address
    $to = 'roark@jlab.org';
    $subject = 'New Contact Form Submission';

    // Prepare the email body
    $email_content = "First Name: $firstName\n";
    $email_content .= "Last Name: $lastName\n";
    $email_content .= "Email: $email\n";
    $email_content .= "Message: $message\n";

    // Set the email headers
    $headers = "From: $email";

    // Send the email
    if(mail($to, $subject, $email_content, $headers)) {
        echo "Thank You! Your message has been sent.";
    } else {
        echo "Oops! Something went wrong and we couldn't send your message.";
    }
} else {
    // Not a POST request, set a 403 (forbidden) response code.
    http_response_code(403);
    echo "There was a problem with your submission, please try again.";
}
?>
