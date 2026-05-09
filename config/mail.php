<?php
/**
 * mail.php
 * Smart Attendance System - Email Utility
 * Handles SMTP config and email sending using PHPMailer with Composer
 */

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendSMTPMail($to, $subject, $body, $embeddedImages = []) {
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        error_log('PHPMailer not found. Please run composer install.');
        return false;
    }
    
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = '0173cs221120@gmail.com';
        $mail->Password = 'ubdk hjjw dhwd zedl';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        $mail->setFrom('0173cs221120@gmail.com', 'QuickMark System');
        $mail->addAddress($to);
        
        foreach ($embeddedImages as $cid => $path) {
            if (file_exists($path)) {
                $mail->addEmbeddedImage($path, $cid);
            }
        }
        
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Mail Error: ' . $mail->ErrorInfo);
        return false;
    }
}

class Mail {
    // Common subjects
    public const WELCOME_SUBJECT = 'Welcome to QuickMark System';
    public const CREDENTIALS_SUBJECT = 'Your Login Credentials - QuickMark';

    // Main method to send email
    public static function sendEmail($to_email, $to_name, $subject, $body) {
        return sendSMTPMail($to_email, $subject, $body);
    }
}

function getWelcomeEmailHTML($name, $role, $email, $password, $login_url) {
    $roleLabel = ($role === 'admin') ? 'Admin Access' : 'Faculty Access';
    
    return "
    <html>
    <head>
      <style>
        .container { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; max-width: 600px; margin: 0 auto; background: #f8f9fa; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .header { background: #0B1C3A; padding: 40px 20px; text-align: center; color: white; }
        .header img { max-width: 80px; border-radius: 15px; margin-bottom: 15px; }
        .content { padding: 40px; background: white; color: #333; line-height: 1.6; }
        .role-badge { display: inline-block; background: #C89C5D; color: #0B1C3A; padding: 6px 18px; border-radius: 20px; font-weight: bold; font-size: 13px; text-transform: uppercase; letter-spacing: 1px; }
        .credentials { background: #f4f7f9; border-left: 4px solid #C89C5D; padding: 20px; margin: 25px 0; border-radius: 0 8px 8px 0; }
        .btn { display: inline-block; padding: 14px 30px; background: #0B1C3A; color: white !important; text-decoration: none; border-radius: 8px; font-weight: bold; margin-top: 25px; box-shadow: 0 4px 10px rgba(11,28,58,0.2); }
        .footer { text-align: center; padding: 25px; font-size: 12px; color: #999; background: #f8f9fa; }
      </style>
    </head>
    <body>
      <div class='container'>
        <div class='header'>
          <h1 style='margin: 0; font-size: 24px;'>QuickMark</h1>
          <p style='margin: 5px 0 20px 0; opacity: 0.8;'>Smart Attendance Automation</p>
          <div class='role-badge'>$roleLabel</div>
        </div>
        <div class='content'>
          <p>Dear <strong>$name</strong>,</p>
          <p>Welcome to QuickMark! Your professional account has been created and is ready for use.</p>
          
          <div class='credentials'>
            <p style='margin: 5px 0;'><strong>Username/Email:</strong> <span style='color: #0B1C3A;'>$email</span></p>
            <p style='margin: 5px 0;'><strong>Temporary Password:</strong> <span style='color: #0B1C3A;'>$password</span></p>
          </div>
          
          <p>You can access your dashboard by clicking the button below. Please update your password upon first login.</p>
          
          <div style='text-align: center;'>
            <a href='$login_url' class='btn'>Login to Dashboard</a>
          </div>
        </div>
        <div class='footer'>
          <p>QuickMark - Presented by Satish & team...</p>
          <p>&copy; " . date('Y') . " All rights reserved.</p>
        </div>
      </div>
    </body>
    </html>
    ";
}
