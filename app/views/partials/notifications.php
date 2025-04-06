<?php if (isset($_SESSION['notifications']) || isset($_SESSION['error']) || isset($_SESSION['success']) || isset($_SESSION['warning'])): ?>
    <div class="notification-container position-fixed top-0 end-0 p-3" style="z-index: 1100">
        <?php
        // Process standard flash messages first
        $flashTypes = [
            'error' => 'danger',
            'success' => 'success',
            'warning' => 'warning',
            'info' => 'info'
        ];
        
        foreach ($flashTypes as $sessionKey => $alertType) {
            if (isset($_SESSION[$sessionKey])) {
                $messages = (array)$_SESSION[$sessionKey];
                foreach ($messages as $message): ?>
                    <div class="alert alert-<?= $alertType ?> alert-dismissible fade show mb-3 shadow" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="fas <?= [
                                'danger' => 'fa-exclamation-circle',
                                'success' => 'fa-check-circle',
                                'warning' => 'fa-exclamation-triangle',
                                'info' => 'fa-info-circle'
                            ][$alertType] ?> me-2"></i>
                            <div><?= htmlspecialchars($message) ?></div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php 
                endforeach;
                unset($_SESSION[$sessionKey]);
            }
        }
        
        // Process structured notifications if they exist
        if (isset($_SESSION['notifications'])): 
            foreach ($_SESSION['notifications'] as $notification): ?>
                <div class="alert alert-<?= htmlspecialchars($notification['type']) ?> alert-dismissible fade show mb-3 shadow" role="alert">
                    <div class="d-flex align-items-start">
                        <?php if (!empty($notification['icon'])): ?>
                            <i class="fas <?= htmlspecialchars($notification['icon']) ?> me-2 mt-1"></i>
                        <?php endif; ?>
                        <div>
                            <?php if (!empty($notification['title'])): ?>
                                <h6 class="alert-heading mb-1"><?= htmlspecialchars($notification['title']) ?></h6>
                            <?php endif; ?>
                            <?= $notification['html'] ?? htmlspecialchars($notification['message']) ?>
                            
                            <?php if (!empty($notification['actions'])): ?>
                                <div class="mt-2">
                                    <?php foreach ($notification['actions'] as $action): ?>
                                        <a href="<?= htmlspecialchars($action['url']) ?>" 
                                           class="btn btn-sm btn-<?= htmlspecialchars($action['style'] ?? 'outline-' . $notification['type']) ?> me-2">
                                            <?= htmlspecialchars($action['label']) ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if (empty($notification['persistent'])): ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    <?php endif; ?>
                </div>
            <?php endforeach;
            unset($_SESSION['notifications']);
        endif;
        ?>
    </div>

    <script>
    // Auto-dismiss alerts after delay (except those with .no-autohide)
    document.addEventListener('DOMContentLoaded', function() {
        const alerts = document.querySelectorAll('.notification-container .alert:not(.no-autohide)');
        alerts.forEach(alert => {
            const delay = alert.dataset.delay || 5000;
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, delay);
        });
    });
    </script>
<?php endif; ?>ss