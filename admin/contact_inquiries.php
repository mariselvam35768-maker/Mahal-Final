<?php
require_once '../includes/auth_functions.php';
require_once '../includes/db.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: adminlogin.php');
    exit();
}



// Delete inquiry
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        $pdo->prepare("DELETE FROM contact WHERE id = ?")->execute([$id]);
        echo "<script>
                alert('Deleted successfully!');
                window.location.href='./contact_inquiries.php';
        </script>";
    } catch (Exception $e) {
        $error = 'Error deleting inquiry: ' . $e->getMessage();
    }
}

$query = "SELECT * FROM contact";

$inquiries = [];
try {
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $inquiries = $stmt->fetchAll();
} catch (Exception $e) {
    $error = 'Error fetching inquiries: ' . $e->getMessage();
}

$total_inquiries = count($inquiries);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Inquiries | Sri Lakshmi Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=rose2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: var(--bg); }
        .avatar { 
            width: 38px; 
            height: 38px; 
            border-radius: 50%; 
            background: var(--gradient-primary); 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            color: white; 
            font-weight: 700; 
            font-size: 0.9rem; 
            flex-shrink: 0; 
        }
        .message-preview {
            max-width: 250px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: var(--gray);
            font-size: 0.85rem;
        }
        .view-message {
            cursor: pointer;
            color: var(--primary);
            text-decoration: none;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .view-message:hover {
            text-decoration: underline;
        }
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 2rem;
            border-radius: var(--radius-lg);
            max-width: 500px;
            position: relative;
        }
        .close-modal {
            position: absolute;
            right: 1.5rem;
            top: 1rem;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--gray);
        }
        .close-modal:hover {
            color: var(--dark);
        }
    </style>
</head>
<body>
<div class="admin-layout">
    <?php include '_sidebar.php'; ?>
    
    <!-- Message Modal -->
    <div id="messageModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal()">&times;</span>
            <h3 style="margin-bottom:1rem; color:var(--primary);">Full Message</h3>
            <div id="fullMessage" style="line-height:1.6; max-height:400px; overflow-y:auto;"></div>
        </div>
    </div>

    <div class="admin-main">
        <div class="admin-topbar">
            <div style="display:flex; align-items:center; gap:0.75rem;">
                <h2 style="font-weight:700; font-size:1.1rem; margin:0; color:var(--dark);">Contact Inquiries</h2>
                <span style="font-size:0.78rem; color:var(--gray); margin-top:0.2rem;"><?php echo $total_inquiries; ?> total inquiries</span>
            </div>
        </div>

        <div class="admin-content">

            <!-- Stats -->
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin-bottom:1.5rem;">
                <div class="admin-stat-card">
                    <div class="stat-icon" style="background:#fce7f3;color:#e91e63;width:44px;height:44px;font-size:1.1rem;"><i class="fas fa-envelope"></i></div>
                    <div>
                        <div style="font-size:0.7rem;color:var(--gray);text-transform:uppercase;">Total Inquiries</div>
                        <div style="font-size:1.5rem;font-weight:800;font-family:'Poppins',sans-serif;"><?php echo $total_inquiries; ?></div>
                    </div>
                </div>
            </div>

            <!-- Inquiries Table -->
            <div class="admin-table-card">
                <div style="overflow-x:auto;">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>S.No</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Message</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($inquiries)): ?>
                                <tr><td colspan="7" style="text-align:center;color:var(--gray-light);padding:4rem;">No inquiries found.</td></tr>
                            <?php else: 
                                $serial = 1;
                                foreach ($inquiries as $inquiry): 
                            ?>
                                <tr>
                                    <td style="font-weight:600; color:var(--gray);"><?php echo $serial++; ?></td>
                                    <td>
                                        <div style="display:flex;align-items:center;gap:0.75rem;">
                                            <div class="avatar"><?php echo strtoupper(substr($inquiry['name'], 0, 1)); ?></div>
                                            <div style="font-weight:600; font-size:0.9rem;"><?php echo htmlspecialchars($inquiry['name']); ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="mailto:<?php echo htmlspecialchars($inquiry['email']); ?>" style="color:var(--primary); text-decoration:none; font-size:0.85rem;">
                                            <?php echo htmlspecialchars($inquiry['email']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php if (!empty($inquiry['phone'])): ?>
                                            <a href="tel:<?php echo htmlspecialchars($inquiry['phone']); ?>" style="color:var(--gray); text-decoration:none; font-size:0.85rem;">
                                                <i class="fas fa-phone-alt" style="font-size:0.7rem; color:var(--primary);"></i>
                                                <?php echo htmlspecialchars($inquiry['phone']); ?>
                                            </a>
                                        <?php else: ?>
                                            <span style="color:var(--gray-light); font-size:0.8rem;">Not provided</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="message-preview"><?php echo htmlspecialchars($inquiry['message']); ?></div>
                                        <a href="#" class="view-message" onclick="viewFullMessage('<?php echo addslashes(htmlspecialchars($inquiry['message'])); ?>', '<?php echo addslashes(htmlspecialchars($inquiry['name'])); ?>'); return false;">
                                            <i class="fas fa-eye"></i> View full message
                                        </a>
                                    </td>
                                    <td>
                                        <div style="display:flex;gap:0.35rem;">
                                            <a href="mailto:<?php echo htmlspecialchars($inquiry['email']); ?>" 
                                               class="btn btn-sm" 
                                               style="background:var(--primary-light);color:var(--primary);"
                                               title="Reply via Email">
                                                <i class="fas fa-reply"></i>
                                            </a>
                                            <a href="?delete=<?php echo $inquiry['id']; ?>"
                                               class="btn btn-danger btn-sm"
                                               title="Delete Inquiry"
                                               onclick="return confirm('Delete this inquiry? This action cannot be undone.')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function viewFullMessage(message, name) {
    document.getElementById('fullMessage').innerHTML = '<strong>From: ' + name + '</strong><br><br>' + message.replace(/\n/g, '<br>');
    document.getElementById('messageModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('messageModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    var modal = document.getElementById('messageModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}
</script>
</body>
</html>
