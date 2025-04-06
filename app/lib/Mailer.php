<?php
/**
 * Mailer Class - Email sending service for Canabidiol Commerce
 * 
 * Handles transactional emails, notifications, and marketing emails
 * with support for templates and attachments.
 */
class Mailer {
    private $mailer;
    private $fromEmail;
    private $fromName;
    private $templatePath;
    private $logger;
    private $testMode = false;
    private $testEmails = [];

    public function __construct($config) {
        // Configure mailer based on environment
        $this->fromEmail = $config['from_email'] ?? 'no-reply@canabidiolcommerce.com.br';
        $this->fromName = $config['from_name'] ?? 'Canabidiol Commerce';
        $this->templatePath = $config['template_path'] ?? __DIR__ . '/../../app/views/emails/';
        $this->testMode = $config['test_mode'] ?? false;
        
        // Initialize PHPMailer
        $this->initializeMailer($config);
        
        // Initialize logger if available
        if (isset($config['logger'])) {
            $this->logger = $config['logger'];
        }
    }

    /**
     * Initialize PHPMailer with configuration
     */
    private function initializeMailer($config) {
        $this->mailer = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Server settings
        if ($config['driver'] === 'smtp') {
            $this->mailer->isSMTP();
            $this->mailer->Host = $config['host'];
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $config['username'];
            $this->mailer->Password = $config['password'];
            $this->mailer->SMTPSecure = $config['encryption'] ?? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = $config['port'] ?? 587;
        } elseif ($config['driver'] === 'sendmail') {
            $this->mailer->isSendmail();
        } else {
            $this->mailer->isMail();
        }
        
        // Default settings
        $this->mailer->CharSet = 'UTF-8';
        $this->mailer->setFrom($this->fromEmail, $this->fromName);
    }

    /**
     * Send email with template
     */
    public function send($to, $subject, $template, $data = [], $attachments = []) {
        try {
            // In test mode, collect emails instead of sending
            if ($this->testMode) {
                $this->testEmails[] = [
                    'to' => $to,
                    'subject' => $subject,
                    'template' => $template,
                    'data' => $data,
                    'attachments' => $attachments
                ];
                return true;
            }

            // Prepare email
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            $this->mailer->clearReplyTos();
            $this->mailer->clearCCs();
            $this->mailer->clearBCCs();

            // Set recipients
            if (is_array($to)) {
                foreach ($to as $email => $name) {
                    $this->mailer->addAddress($email, $name);
                }
            } else {
                $this->mailer->addAddress($to);
            }

            // Set subject
            $this->mailer->Subject = $subject;

            // Render template
            $body = $this->renderTemplate($template, $data);
            $this->mailer->msgHTML($body);
            $this->mailer->AltBody = strip_tags($body);

            // Add attachments
            foreach ($attachments as $attachment) {
                if (is_array($attachment)) {
                    $this->mailer->addAttachment(
                        $attachment['path'],
                        $attachment['name'] ?? '',
                        $attachment['encoding'] ?? 'base64',
                        $attachment['type'] ?? '',
                        $attachment['disposition'] ?? 'attachment'
                    );
                } else {
                    $this->mailer->addAttachment($attachment);
                }
            }

            // Send email
            $this->mailer->send();
            
            // Log successful send
            $this->logEmail($to, $subject, $template, true);
            
            return true;
        } catch (Exception $e) {
            // Log error
            $this->logEmail($to, $subject, $template, false, $e->getMessage());
            
            if ($this->logger) {
                $this->logger->error("Email sending failed: " . $e->getMessage());
            }
            
            throw new Exception("Email could not be sent. Mailer Error: {$this->mailer->ErrorInfo}");
        }
    }

    /**
     * Render email template with data
     */
    private function renderTemplate($template, $data) {
        $templateFile = $this->templatePath . $template . '.php';
        
        if (!file_exists($templateFile)) {
            throw new Exception("Email template {$template} not found");
        }
        
        // Extract data to variables
        extract($data);
        
        // Start output buffering
        ob_start();
        
        // Include template file
        include $templateFile;
        
        // Get contents and clean buffer
        return ob_get_clean();
    }

    /**
     * Log email sending attempt
     */
    private function logEmail($to, $subject, $template, $success, $error = null) {
        if (!$this->logger) {
            return;
        }
        
        $logData = [
            'to' => is_array($to) ? json_encode($to) : $to,
            'subject' => $subject,
            'template' => $template,
            'status' => $success ? 'sent' : 'failed',
            'error' => $error
        ];
        
        $this->logger->info('Email sent', $logData);
    }

    /**
     * Send order confirmation email
     */
    public function sendOrderConfirmation($order, $user) {
        $data = [
            'order' => $order,
            'user' => $user,
            'date' => date('d/m/Y H:i')
        ];
        
        return $this->send(
            $user->email,
            "Confirmação de Pedido #{$order->codigo}",
            'order_confirmation',
            $data
        );
    }

    /**
     * Send prescription approval email
     */
    public function sendPrescriptionApproval($prescription, $user) {
        $data = [
            'prescription' => $prescription,
            'user' => $user
        ];
        
        return $this->send(
            $user->email,
            "Receita Médica Aprovada",
            'prescription_approval',
            $data
        );
    }

    /**
     * Send account activation email
     */
    public function sendActivationEmail($user, $activationLink) {
        $data = [
            'user' => $user,
            'activationLink' => $activationLink
        ];
        
        return $this->send(
            $user->email,
            "Ativação da sua conta",
            'account_activation',
            $data
        );
    }

    /**
     * Send password reset email
     */
    public function sendPasswordReset($user, $resetLink) {
        $data = [
            'user' => $user,
            'resetLink' => $resetLink
        ];
        
        return $this->send(
            $user->email,
            "Redefinição de Senha",
            'password_reset',
            $data
        );
    }

    /**
     * Get emails sent in test mode
     */
    public function getTestEmails() {
        return $this->testEmails;
    }

    /**
     * Set test mode
     */
    public function setTestMode($enabled) {
        $this->testMode = $enabled;
    }
}