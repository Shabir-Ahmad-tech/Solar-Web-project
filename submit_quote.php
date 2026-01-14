<?php
/**
 * KABAL SOLAR SYSTEM - QUOTE SUBMISSION HANDLER
 * 
 * This file processes quote request form submissions.
 * It validates input, stores data in database, and sends response.
 */

// Include configuration
require_once 'config.php';

// Set headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSONResponse(false, 'Invalid request method. Only POST is allowed.');
}

try {
    // Rate limiting check (5 submissions per hour from same IP)
    $clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if (!checkRateLimit($clientIP, 5, 3600)) {
        sendJSONResponse(false, 'Too many submissions. Please try again later.');
    }
    
    // Get and sanitize form data
    $name = isset($_POST['name']) ? sanitizeInput($_POST['name']) : '';
    $phone = isset($_POST['phone']) ? sanitizeInput($_POST['phone']) : '';
    $email = isset($_POST['email']) ? sanitizeInput($_POST['email']) : '';
    $address = isset($_POST['address']) ? sanitizeInput($_POST['address']) : '';
    $serviceType = isset($_POST['service']) ? sanitizeInput($_POST['service']) : '';
    $propertyType = isset($_POST['property']) ? sanitizeInput($_POST['property']) : '';
    $message = isset($_POST['message']) ? sanitizeInput($_POST['message']) : '';
    
    // Validation errors array
    $errors = [];
    
    // Validate name
    if (empty($name) || strlen($name) < 3) {
        $errors[] = 'Please enter your full name (minimum 3 characters)';
    }
    
    // Validate phone
    if (empty($phone) || !validatePhoneNumber($phone)) {
        $errors[] = 'Please enter a valid Pakistani phone number';
    }
    
    // Validate email (optional but if provided, must be valid)
    if (!empty($email) && !validateEmail($email)) {
        $errors[] = 'Please enter a valid email address';
    }
    
    // Validate address
    if (empty($address) || strlen($address) < 10) {
        $errors[] = 'Please enter your complete address';
    }
    
    // Validate service type
    $validServices = ['solar', 'geyser', 'both'];
    if (empty($serviceType) || !in_array($serviceType, $validServices)) {
        $errors[] = 'Please select a valid service type';
    }
    
    // Validate property type
    $validProperties = ['residential', 'commercial'];
    if (empty($propertyType) || !in_array($propertyType, $validProperties)) {
        $errors[] = 'Please select a valid property type';
    }
    
    // If there are validation errors, return them
    if (!empty($errors)) {
        sendJSONResponse(false, implode('. ', $errors));
    }
    
    // Get database connection
    $pdo = getDBConnection();
    
    // Check for duplicate submission (same phone in last 5 minutes)
    $stmt = $pdo->prepare(
        "SELECT id FROM clients 
         WHERE phone = :phone 
         AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)"
    );
    $stmt->execute([':phone' => $phone]);
    
    if ($stmt->fetch()) {
        sendJSONResponse(false, 'You have already submitted a quote request recently. Please wait a few minutes before submitting again.');
    }
    
    // Insert quote request into database
    $stmt = $pdo->prepare(
        "INSERT INTO clients (name, phone, email, address, service_type, property_type, message) 
         VALUES (:name, :phone, :email, :address, :service_type, :property_type, :message)"
    );
    
    $result = $stmt->execute([
        ':name' => $name,
        ':phone' => $phone,
        ':email' => $email ?: null,
        ':address' => $address,
        ':service_type' => $serviceType,
        ':property_type' => $propertyType,
        ':message' => $message ?: null
    ]);
    
    if ($result) {
        $clientId = $pdo->lastInsertId();
        
        // Log activity
        logActivity($clientId, 'quote_submitted', "New quote request from {$name}");
        
        // Optional: Send email notification to admin
        // sendEmailNotification($name, $phone, $email, $serviceType);
        
        // Optional: Send SMS confirmation to client
        // sendSMSConfirmation($phone, $name);
        
        sendJSONResponse(
            true, 
            'Thank you! Your quote request has been submitted successfully. We will contact you within 24 hours.',
            [
                'quote_id' => $clientId,
                'service_type' => $serviceType
            ]
        );
    } else {
        throw new Exception('Failed to save quote request');
    }
    
} catch (PDOException $e) {
    // Log database error
    error_log("Database Error in submit_quote.php: " . $e->getMessage());
    sendJSONResponse(false, 'Database error. Please try again or contact us directly at +92 346 3499302');
    
} catch (Exception $e) {
    // Log general error
    error_log("Error in submit_quote.php: " . $e->getMessage());
    sendJSONResponse(false, 'An error occurred while processing your request. Please contact us directly at +92 346 3499302');
}

/**
 * Send email notification to admin (optional enhancement)
 * Requires mail configuration in server
 */
function sendEmailNotification($name, $phone, $email, $serviceType) {
    $to = 'sohailahmad074@gmail.com';
    $subject = 'New Quote Request - Kabal Solar System';
    
    $messageBody = "
        <html>
        <head><title>New Quote Request</title></head>
        <body>
            <h2>New Quote Request Received</h2>
            <p><strong>Name:</strong> {$name}</p>
            <p><strong>Phone:</strong> {$phone}</p>
            <p><strong>Email:</strong> {$email}</p>
            <p><strong>Service Type:</strong> {$serviceType}</p>
            <p><strong>Time:</strong> " . date('Y-m-d H:i:s') . "</p>
            <hr>
            <p>Please follow up within 24 hours.</p>
        </body>
        </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: noreply@kabalsolarsystem.com" . "\r\n";
    
    @mail($to, $subject, $messageBody, $headers);
}

/**
 * Send SMS confirmation to client (optional enhancement)
 * Requires SMS API integration (e.g., Twilio, Nexmo, local Pakistani SMS service)
 */
function sendSMSConfirmation($phone, $name) {
    $message = "Thank you {$name}! Your quote request has been received. We will contact you within 24 hours. - Kabal Solar System";
    
    // Implement SMS API call here
    // Example: Use Pakistani SMS service API
    
    return true;
}
?>
