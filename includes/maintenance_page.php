<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bảo trì hệ thống - copecute</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .maintenance-container {
            max-width: 600px;
            text-align: center;
            background-color: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .maintenance-icon {
            font-size: 80px;
            color: #dc3545;
            margin-bottom: 20px;
        }
        h1 {
            margin-bottom: 20px;
            color: #343a40;
        }
        p {
            font-size: 18px;
            color: #6c757d;
            margin-bottom: 30px;
        }
        .logo {
            max-width: 200px;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="maintenance-container">
        <?php
        // Lấy đường dẫn gốc của site
        $root_url = '';
        try {
            $stmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'base_url'");
            $root_url = $stmt->fetchColumn();
        } catch (PDOException $e) {
            $root_url = '/';
        }
        ?>
        <img src="<?php echo $root_url; ?>/logo.png" alt="copecute Logo" class="logo">
        <i class="fas fa-tools maintenance-icon"></i>
        <h1>Hệ thống đang bảo trì</h1>
        <p>
            Chúng tôi đang nâng cấp và cải thiện hệ thống để mang đến trải nghiệm tốt hơn.
            <br>
            Vui lòng quay lại sau. Cảm ơn sự kiên nhẫn của bạn!
        </p>
        <div class="mt-4">
            <a href="<?php echo $root_url; ?>" class="btn btn-primary">
                <i class="fas fa-home"></i> Thử lại
            </a>
            <a href="mailto:admin@example.com" class="btn btn-outline-secondary ms-2">
                <i class="fas fa-envelope"></i> Liên hệ admin
            </a>
        </div>
    </div>
</body>
</html> 