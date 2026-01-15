<div class="profile-container">
    <div class="profile-header">
        <div class="container-custom">
            <img src="<?= htmlspecialchars($emprow['avatar'] ?? './assets/images/users/avatar-1.jpg') ?>" alt="<?= htmlspecialchars($emprow['name']) ?>" class="profile-avatar">
            <div class="profile-header-info">
                <h1><?= getDisplayName($emprow['name']) ?></h1>
                <p><strong><?= __('employee_id') ?>:</strong> <?= htmlspecialchars($emprow['emp_id']) ?></p>
            </div>
            <?php
                $qrPath = "./assets/qrcodes/" . (($emprow['eid'] ?? '') . $emprow['emp_id']) . ".png";
                if (!empty($emprow['emp_id']) && file_exists($qrPath)):
            ?>
                <img src="<?= $qrPath ?>" alt="QR Code" class="qr-code">
            <?php endif; ?>
            <a href="profile.php?hashcode=<?= htmlspecialchars($emprow['emp_id'] ?? '') ?>&verification=<?= htmlspecialchars($emprow['id'] ?? '') ?>" class="more-actions-btn">
                <i class="fa fa-arrow-left" style="margin-<?= ($is_rtl ?? false) ? 'left' : 'right' ?>: 6px;"></i> <?= __('back_to_profile') ?>
            </a>
        </div>
    </div>
</div>