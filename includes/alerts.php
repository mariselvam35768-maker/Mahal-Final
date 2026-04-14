<!-- GLOBAL ALERT MODAL -->
<div id="globalAlertModal" class="alert-modal-overlay">
    <div class="alert-modal-box">
        <div class="alert-modal-close" onclick="closeAlertModal()"><i class="fas fa-times"></i></div>
        <div id="alertModalIcon" class="alert-modal-icon"></div>
        <h3 id="alertModalTitle" class="alert-modal-title"></h3>
        <p id="alertModalMessage" class="alert-modal-message"></p>
        <div class="alert-modal-footer">
            <button onclick="closeAlertModal()" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 1.1rem; border-radius: 16px; font-weight: 700; font-size: 1rem; letter-spacing: 0.02em;">OK, Got it</button>
        </div>
    </div>
</div>

<script>
function showAlertModal(type, title, message) {
    const modal = document.getElementById('globalAlertModal');
    if (!modal) return;
    const icon = document.getElementById('alertModalIcon');
    const titleEl = document.getElementById('alertModalTitle');
    const msgEl = document.getElementById('alertModalMessage');

    icon.className = 'alert-modal-icon ' + type;
    if (type === 'success') icon.innerHTML = '<i class="fas fa-check-circle"></i>';
    else if (type === 'error') icon.innerHTML = '<i class="fas fa-times-circle"></i>';
    else if (type === 'warning') icon.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
    else icon.innerHTML = '<i class="fas fa-info-circle"></i>';

    titleEl.textContent = title;
    msgEl.textContent = message;
    modal.classList.add('active');
}

function closeAlertModal() {
    const modal = document.getElementById('globalAlertModal');
    if (modal) modal.classList.remove('active');
}

document.addEventListener('DOMContentLoaded', function() {
    // 1. Handle PHP Session Messages
    <?php if (isset($_SESSION['success'])): ?>
        showAlertModal('success', 'Success!', '<?php echo addslashes($_SESSION['success']); ?>');
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        showAlertModal('error', 'Oops!', '<?php echo addslashes($_SESSION['error']); ?>');
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['msg'])): ?>
        showAlertModal('info', 'Notification', '<?php echo addslashes($_SESSION['msg']); ?>');
        <?php unset($_SESSION['msg']); ?>
    <?php endif; ?>

    // 2. Handle Inline .alert elements (Automatic Conversion)
    const alerts = document.querySelectorAll('.alert:not(.modalized)');
    if (alerts.length > 0) {
        alerts.forEach(alert => {
            let type = 'info';
            if (alert.classList.contains('alert-success')) type = 'success';
            else if (alert.classList.contains('alert-danger') || alert.classList.contains('alert-error')) type = 'error';
            else if (alert.classList.contains('alert-warning')) type = 'warning';

            const title = type === 'success' ? 'Success!' : (type === 'error' ? 'Oops!' : (type === 'warning' ? 'Warning' : 'Notification'));
            const message = alert.textContent.trim();

            if (message) {
                showAlertModal(type, title, message);
                alert.style.display = 'none';
                alert.classList.add('modalized');
            }
        });
    }
    // 3. Clear URL query parameters after showing alerts so they don't reappear on refresh
    if (window.location.search.includes('success=') || window.location.search.includes('error=') || window.location.search.includes('msg=')) {
        setTimeout(() => {
            const url = new URL(window.location);
            url.searchParams.delete('success');
            url.searchParams.delete('error');
            url.searchParams.delete('msg');
            window.history.replaceState({}, '', url);
        }, 500);
    }
});
</script>
