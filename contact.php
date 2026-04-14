<?php
require_once 'includes/auth_functions.php';
require_once 'includes/db.php'; // Include database connection
require_once __DIR__ . '/includes/PHPMailer/Exception.php';
require_once __DIR__ . '/includes/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/includes/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;


$form_success = false;
$form_error = '';

// Simple contact form handler (stores in database and sends email)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send'])) {
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    // $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($message)) {
        $form_error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $form_error = 'Please enter a valid email address.';
    } else {
        try {
            // Insert into contact table
            $stmt = $pdo->prepare("
                INSERT INTO contact(name, email, phone, message)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$name, $email, $phone, $message]);

            $logo = $pdo->prepare("SELECT setting_value FROM settings WHERE id = ?");
            $logo->execute([3]);
            $image_path = $logo->fetchColumn();

            // die($brand_logo);




            $mail = new PHPMailer(true);

            $to = "srvnkmrmarimuthu@gmail.com";
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'srvnkmrmarimuthu@gmail.com';
            $mail->Password = 'nqdm ktju anvb zoqf';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('srvnkmrmarimuthu@gmail.com', $brand_name);
            $mail->addAddress($to);
            $mail->addReplyTo('srvnkmrmarimuthu@gmail.com', $name);

            $mail->isHTML(true);
            $mail->Subject = 'New Contact Form Inquiry: ';
            date_default_timezone_set('Asia/Kolkata');

            // Email body with contact form details
            $mail->Body = '
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px; }
        .card { background-color: white; max-width: 600px; margin: 0 auto; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.05); overflow: hidden; }
        .card-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px; text-align: center; }
        .card-body { padding: 30px; }
        .info-section { margin-bottom: 25px; }
        .info-label { font-size: 0.85em; text-transform: uppercase; color: #888; margin-bottom: 5px; border-bottom: 1px solid #eee; padding-bottom: 3px; }
        .info-value { font-size: 1.1em; margin-bottom: 15px; font-weight: 500; padding-left: 10px; }
        .message-box { background-color: #f9f9f9; padding: 15px; border-radius: 8px; margin-top: 10px; }
        .footer { text-align: center; font-size: 0.8em; color: #aaa; padding: 20px; border-top: 1px solid #eee; }
    </style>
</head>
<body>
    <div class="card">
        <div class="card-header">
            <h1 style="margin:0;">📬 New Contact Form Submission</h1>
        </div>
        <div class="card-body">
            <p>Hello Admin,</p>
            <p>You have received a new inquiry from the contact form. Here are the details:</p>
            
            <div class="info-section">
                <div class="info-label">Name</div>
                <div class="info-value">' . htmlspecialchars($name) . '</div>
                
                <div class="info-label">Email Address</div>
                <div class="info-value">' . htmlspecialchars($email) . '</div>
                
                <div class="info-label">Phone Number</div>
                <div class="info-value">' . ($phone ? htmlspecialchars($phone) : 'Not provided') . '</div>
                
                <div class="info-label">Message</div>
                <div class="message-box">' . nl2br(htmlspecialchars($message)) . '</div>
                
                <div class="info-label">Submitted On</div>
                <div class="info-value">' . date("M j, Y, g:i a") . '</div>
            </div>
        </div>
        <div class="footer">
            This is an automated message from ' . htmlspecialchars($brand_name) . ' System.
        </div>
    </div>
</body>
</html>
';

            // Plain text alternative for non-HTML email clients
            $mail->AltBody = "New Contact Form Submission\n\n" .
                "Name: $name\n" .
                "Email: $email\n" .
                "Phone: " . ($phone ?: 'Not provided') . "\n" .
                "Message: $message\n\n" .
                "Submitted on: " . date("M j, Y, g:i a");

            $mail->send();
            $form_success = true;
        } catch (\Exception $e) {
            // Check if it's a database error (table might not exist)
            if (strpos($e->getMessage(), '1146') !== false || strpos($e->getMessage(), 'contact') !== false) {
                // Table doesn't exist - create it
                try {
                    $pdo->exec("
                        CREATE TABLE IF NOT EXISTS contact(
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            name VARCHAR(100) NOT NULL,
                            email VARCHAR(100) NOT NULL,
                            phone VARCHAR(20),
                            message TEXT NOT NULL,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                    ");

                    // Try inserting again
                    $stmt = $pdo->prepare("
                        INSERT INTO contact (name, email, phone, message)
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([$name, $email, $phone, $message]);

                    // Try sending email again
                    $mail->send();
                    $form_success = true;
                } catch (\Exception $e2) {
                    $form_error = 'Message could not be sent. Error: ' . $e2->getMessage();
                }
            } else {
                $form_error = 'Message could not be sent. Error: ' . $e->getMessage();
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us | <?php echo $brand_name; ?></title>
    <meta name="description" content="Get in touch with Sri Lakshmi Residency & Mahal – we're here to help with all your hall booking queries.">
    <link rel="stylesheet" href="assets/css/style.css?v=rose2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            padding-top: 75px;
        }

        .contact-info-card {
            display: flex;
            align-items: flex-start;
            gap: 1.25rem;
            padding: 1.5rem;
            background: white;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            transition: var(--transition);
        }

        .contact-info-card:hover {
            box-shadow: var(--shadow-md);
            border-color: var(--primary);
        }

        .ci-icon {
            width: 52px;
            height: 52px;
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .faq-item {
            padding: 1.25rem;
            background: white;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            cursor: pointer;
            transition: var(--transition);
        }

        .faq-item:hover {
            border-color: var(--primary);
        }

        .faq-answer {
            display: none;
            margin-top: 0.75rem;
            color: var(--gray);
            font-size: 0.875rem;
            line-height: 1.7;
        }

        .faq-item.open .faq-answer {
            display: block;
        }

        .faq-item.open {
            border-color: var(--primary);
            background: var(--primary-light);
        }

        .contact-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .contact-main-grid {
            display: grid;
            grid-template-columns: 1fr 1.4fr;
            gap: 2rem;
            align-items: start;
        }

        @media (max-width: 992px) {
            .contact-main-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .contact-form-grid {
                grid-template-columns: 1fr;
            }

            .contact-info-card {
                padding: 1rem;
                gap: 0.9rem;
            }

            .ci-icon {
                width: 44px;
                height: 44px;
                font-size: 1rem;
            }

            .contact-form-header {
                padding: 1.25rem !important;
            }

            .contact-form-body {
                padding: 1.25rem !important;
            }

            .cta-banner {
                padding: 2.5rem 1.5rem !important;
            }

            .cta-banner h2 {
                font-size: 1.3rem !important;
            }

            .cta-banner p {
                font-size: 0.875rem !important;
            }
        }
    </style>
</head>

<body>
    <?php include 'includes/navbar.php'; ?>

    <!-- HERO -->
    <!-- <div class="page-header" style="text-align:center;">
        <div class="container" style="position:relative;z-index:1;">
            <div class="section-label" style="display:inline-flex;margin-bottom:1rem;"><i class="fas fa-headset"></i> We're Here to Help</div>
            <h1 style="color:white;font-size:3rem;">Contact <span style="color:#a78bfa;">Us</span></h1>
            <p style="color:rgba(255,255,255,0.75);max-width:520px;margin:0 auto;font-size:1.05rem;">Experience premium comfort and grand celebrations in the heart of Srivilliputhur. We're here to help you plan your perfect event.</p>
        </div>
    </div> -->


    <!-- CONTACT INFO + FORM -->
    <section class="section" style="background:white;">
        <div class="container">
            <div class="contact-main-grid">

                <!-- LEFT: CONTACT INFO -->
                <div>
                    <div class="section-label"><i class="fas fa-address-book"></i> Get in Touch</div>
                    <h2 class="section-heading">We'd Love to <span>Hear From You</span></h2>
                    <p style="color:var(--gray);font-size:0.9rem;line-height:1.7;margin-bottom:2rem;">Reach out via phone, email, or fill the form. Our support team responds within 24 hours on business days.</p>

                    <div style="display:flex;flex-direction:column;gap:1rem;margin-bottom:2rem;">
                        <?php
                        $contacts = [
                            ['fas fa-phone',         '#e91e63','#ede9fe','Call Us Directly',    '+91 98765 43210',              'Mon-Sat, 9:00 AM - 6:00 PM',       'Available for instant enquiries'],
                            ['fas fa-envelope',      '#e91e63','#ede9fe','Email Support',        'srilakshmimahal@gmail.com',    'contact@srilakshmimahal.com',       'We reply within 24 hours'],
                            ['fas fa-map-marker-alt','#e91e63','#ede9fe','Our Location',         'Sri Lakshmi Residency & Mahal','Srivilliputhur, Tamil Nadu',        'In the heart of the city'],
                            ['fab fa-whatsapp',      '#e91e63','#ede9fe','WhatsApp Booking',     'Easy chat-based booking',      'Fast & convenient',                 'Message us anytime'],
                        ];
                        foreach ($contacts as [$icon, $col, $bg, $label, $line1, $line2, $line3]): ?>
                            <div class="contact-info-card">
                                <div class="ci-icon" style="background:<?php echo $bg; ?>;color:<?php echo $col; ?>;">
                                    <i class="<?php echo $icon; ?>"></i>
                                </div>
                                <div>
                                    <div style="font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--gray-light);margin-bottom:0.3rem;"><?php echo $label; ?></div>
                                    <div style="font-weight:700;font-size:0.95rem;color:var(--dark);"><?php echo $line1; ?></div>
                                    <div style="font-size:0.82rem;color:var(--gray);margin-top:0.15rem;"><?php echo $line2; ?></div>
                                    <div style="font-size:0.75rem;color:var(--gray-light);margin-top:0.1rem;"><?php echo $line3; ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Social Links -->
                    <div>
                        <div style="font-weight:700;font-size:0.85rem;margin-bottom:0.75rem;color:var(--dark-2);">Follow Us</div>
                        <div style="display:flex;gap:0.75rem;flex-wrap:wrap;">
                            <?php foreach ([
                                ['fab fa-facebook-f','#e91e63','Facebook'],
                                ['fab fa-instagram', '#e91e63','Instagram'],
                                ['fab fa-whatsapp',  '#e91e63','WhatsApp'],
                                ['fab fa-youtube',   '#e91e63','YouTube'],
                            ] as [$ic,$col,$lbl]): ?>
                                <a href="#" title="<?php echo $lbl; ?>" style="width:42px;height:42px;border-radius:50%;background:white;border:1px solid var(--border);display:flex;align-items:center;justify-content:center;color:<?php echo $col; ?>;font-size:1rem;transition:var(--transition);"
                                    onmouseover="this.style.background='<?php echo $col; ?>';this.style.color='white';this.style.borderColor='<?php echo $col; ?>'"
                                    onmouseout="this.style.background='white';this.style.color='<?php echo $col; ?>';this.style.borderColor='var(--border)'">
                                    <i class="<?php echo $ic; ?>"></i>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- RIGHT: CONTACT FORM -->
                <div>
                    <div style="background:white;border-radius:var(--radius-xl);border:1px solid var(--border);box-shadow:var(--shadow-md);overflow:hidden;">
                        <div class="contact-form-header" style="background:var(--gradient-primary);padding:1.75rem 2rem;color:white;">
                            <h3 style="color:white;margin-bottom:0.25rem;"><i class="fas fa-paper-plane"></i> Send Us a Message</h3>
                            <p style="color:rgba(255,255,255,0.8);font-size:0.875rem;margin:0;">Fill the form and we'll get back to you shortly.</p>
                        </div>
                        <div class="contact-form-body" style="padding:2rem;">
                            <?php if ($form_success): ?>
                                <!-- Hidden trigger for global alert popup -->
                                <div class="alert alert-success" style="display:none;">Message Sent! Thank you for reaching out. Our team will respond within 24 hours.</div>
                                
                                <div style="text-align:center;padding:3rem 1rem;">
                                    <div style="width:72px;height:72px;background:#d1fae5;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem;">
                                        <i class="fas fa-check-circle" style="font-size:2rem;color:#10b981;"></i>
                                    </div>
                                    <h3 style="margin-bottom:0.5rem;">Message Sent!</h3>
                                    <p style="color:var(--gray);margin-bottom:1.5rem;">Thank you for reaching out. Our team will respond within 24 hours.</p>
                                    <a href="contact.php" class="btn btn-primary"><i class="fas fa-redo"></i> Send Another Message</a>
                                </div>
                            <?php else: ?>
                                <?php if ($form_error): ?>
                                    <div class="alert alert-danger" style="background:#fee2e2;color:#991b1b;padding:0.85rem 1rem;border-radius:var(--radius);margin-bottom:1.25rem;font-size:0.875rem;">
                                        <i class="fas fa-exclamation-circle"></i> <?php echo $form_error; ?>
                                    </div>
                                <?php endif; ?>
                                <form method="POST">
                                    <div class="contact-form-grid">
                                        <div class="form-group">
                                            <label>Your Name <span style="color:var(--danger)">*</span></label>
                                            <div class="input-icon-wrap">
                                                <i class="fas fa-user"></i>
                                                <input type="text" name="name" data-validate="name" class="form-control" placeholder="Full Name" required value="<?php echo htmlspecialchars($_POST['name'] ?? (isLoggedIn() ? $_SESSION['user_name'] : '')); ?>">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>Phone Number</label>
                                            <div class="input-icon-wrap">
                                                <i class="fas fa-phone"></i>
                                                <input type="tel" name="phone" data-validate="phone" class="form-control" placeholder="10-digit mobile" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Email Address <span style="color:var(--danger)">*</span></label>
                                        <div class="input-icon-wrap">
                                            <i class="fas fa-envelope"></i>
                                            <input type="email" name="email" class="form-control" placeholder="your@email.com" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                        </div>
                                    </div>


                                    <!-- <div class="form-group">
                                        <label>Subject</label>
                                        <select name="subject" class="form-control">
                                            <option value="">Select a topic...</option>
                                            <option value="Hall Booking Inquiry" <?php echo ($_POST['subject'] ?? '') === 'Hall Booking Inquiry' ? 'selected' : ''; ?>>Hall Booking Inquiry</option>
                                            <option value="Pricing & Packages" <?php echo ($_POST['subject'] ?? '') === 'Pricing & Packages' ? 'selected' : ''; ?>>Pricing & Packages</option>
                                            <option value="Slot Availability" <?php echo ($_POST['subject'] ?? '') === 'Slot Availability' ? 'selected' : ''; ?>>Slot Availability</option>
                                            <option value="Booking Cancellation" <?php echo ($_POST['subject'] ?? '') === 'Booking Cancellation' ? 'selected' : ''; ?>>Booking Cancellation</option>
                                            <option value="Technical Issue" <?php echo ($_POST['subject'] ?? '') === 'Technical Issue' ? 'selected' : ''; ?>>Technical Issue</option>
                                            <option value="General Enquiry" <?php echo ($_POST['subject'] ?? '') === 'General Enquiry' ? 'selected' : ''; ?>>General Enquiry</option>
                                            <option value="List My Hall" <?php echo ($_POST['subject'] ?? '') === 'List My Hall' ? 'selected' : ''; ?>>List My Hall on Sri Lakshmi Residency & Mahal</option>
                                        </select>
                                    </div> -->

                                    <div class="form-group">
                                        <label>Message <span style="color:var(--danger)">*</span></label>
                                        <textarea name="message" class="form-control" rows="5" placeholder="Tell us how we can help you..." required style="resize:vertical;"><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                                    </div>

                                    <button type="submit" name="send" class="btn btn-primary btn-lg" style="width:100%;justify-content:center;">
                                        <i class="fas fa-paper-plane"></i> Send Message
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- FAQ -->
    <section class="section">
        <div class="container">
            <div class="text-center" style="margin-bottom:3rem;">
                <div class="section-label"><i class="fas fa-question-circle"></i> Common Questions</div>
                <h2 class="section-heading">Frequently Asked <span>Questions</span></h2>
            </div>
            <div style="max-width:750px;margin:0 auto;display:flex;flex-direction:column;gap:0.75rem;">
                <?php
                $faqs = [
                    ['How do I book a hall on Sri Lakshmi Residency & Mahal?', 'Browse halls, click on any hall to view details, select your event date and time slot (Full Day / Morning / Evening), fill in your event name, and click "Confirm Booking". You need to be logged in to book.'],
                    ['What are the available time slots?', 'We offer 3 booking types: Full Day (all day), Morning Slot (9:00 AM – 4:00 PM), and Evening Slot (4:00 PM – 12:00 AM). Availability depends on the specific hall.'],
                    ['How much advance payment is required?', 'Each hall has its own advance amount set by the hall owner. This amount is shown clearly in the booking form before you confirm. The balance is paid on the event day.'],
                    ['Can I cancel my booking?', 'Please contact our support team to discuss cancellation. Cancellation policies vary by hall. Once confirmed by the admin, cancellations may be subject to the hall\'s cancellation policy.'],
                    ['How do I know if my booking is confirmed?', 'After submitting your booking, it will show as "Pending" in My Bookings. Our admin team reviews and confirms it. You will see the status change to "Confirmed" in your booking dashboard.'],
                    ['How do I list my hall on Sri Lakshmi Residency & Mahal?', 'Contact us using the form above with subject "List My Hall on Sri Lakshmi Residency & Mahal". Our team will review and add your hall after verification.'],
                ];
                foreach ($faqs as $i => [$q, $a]): ?>
                    <div class="faq-item" id="faq<?php echo $i; ?>" onclick="toggleFaq(<?php echo $i; ?>)">
                        <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;">
                            <div style="font-weight:700;font-size:0.9rem;"><?php echo $q; ?></div>
                            <i class="fas fa-chevron-down faq-icon-<?php echo $i; ?>" style="color:var(--primary);flex-shrink:0;transition:transform 0.3s;"></i>
                        </div>
                        <div class="faq-answer"><?php echo $a; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="section" style="background:white;">
        <div class="container">
            <div class="cta-banner" style="background:var(--gradient-primary);border-radius:var(--radius-xl);padding:4rem 3rem;text-align:center;position:relative;overflow:hidden;">
                <div style="position:relative;z-index:1;">
                    <h2 style="color:white;font-size:2.25rem;margin-bottom:0.75rem;">Ready to Book Your Venue?</h2>
                    <p style="color:rgba(255,255,255,0.8);margin-bottom:2rem;font-size:1.05rem;">Browse our collection of verified halls and make your event unforgettable.</p>
                    <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">
                        <a href="halls.php" class="btn btn-white btn-lg"><i class="fas fa-building"></i> Browse Halls</a>
                        <a href="about.php" class="btn btn-lg" style="background:rgba(255,255,255,0.15);color:white;border:2px solid rgba(255,255,255,0.3);"><i class="fas fa-info-circle"></i> About Us</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
    <?php include 'includes/modals.php'; ?>
    <?php include 'includes/chatbot.php'; ?>

    <script src="assets/js/validation.js"></script>
    <script>
        function toggleFaq(i) {

            const item = document.getElementById('faq' + i);
            const icon = item.querySelector('.faq-icon-' + i);
            const isOpen = item.classList.contains('open');
            document.querySelectorAll('.faq-item').forEach((el, idx) => {
                el.classList.remove('open');
                el.querySelector('[class*="faq-icon-"]').style.transform = 'rotate(0deg)';
            });
            if (!isOpen) {
                item.classList.add('open');
                icon.style.transform = 'rotate(180deg)';
            }
        }
    </script>
</body>

</html>