<?php
session_start();
require_once '../includes/config.php';

// Tài liệu HTML chỉ để hiển thị, không cần kiểm tra quyền truy cập
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xem trước trang bảo trì</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fc;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 300px;
            margin: 0;
            padding: 20px;
        }
        .maintenance-container {
            max-width: 100%;
            text-align: center;
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .maintenance-icon {
            font-size: 50px;
            color: #dc3545;
            margin-bottom: 20px;
        }
        h1 {
            margin-bottom: 15px;
            color: #343a40;
            font-size: 24px;
        }
        p {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 20px;
        }
        .logo {
            max-width: 100px;
            margin-bottom: 20px;
        }
        .btn-wrapper {
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="maintenance-container">
        <?php
        // Lấy đường dẫn logo
        $logo_url = '';
        try {
            $stmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'logo_url'");
            $logo_url = $stmt->fetchColumn() ?: '/assets/img/logo.png';
        } catch (PDOException $e) {
            $logo_url = '/assets/img/logo.png';
        }
        ?>
        <img src="<?php echo $base_url . $logo_url; ?>" alt="Logo" class="logo">
        <i class="fas fa-tools maintenance-icon"></i>
        <h1>Hệ thống đang bảo trì</h1>
        <p>
            Chúng tôi đang nâng cấp và cải thiện hệ thống để mang đến trải nghiệm tốt hơn.
            <br>
            Vui lòng quay lại sau. Cảm ơn sự kiên nhẫn của bạn!
        </p>
        <div class="btn-wrapper">
            <button class="btn btn-primary btn-sm">
                <i class="fas fa-home"></i> Thử lại
            </button>
            <button class="btn btn-outline-secondary btn-sm ms-2">
                <i class="fas fa-envelope"></i> Liên hệ admin
            </button>
        </div>
    </div>
</body>
</html> 