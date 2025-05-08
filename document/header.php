<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tài liệu API SimSimi Chatbot</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            padding-top: 20px;
            padding-bottom: 40px;
            background-color: #f8f9fa;
        }
        .navbar-brand {
            font-weight: 700;
        }
        pre {
            background-color: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
        code {
            color: #d63384;
        }
        .method {
            font-weight: bold;
            color: #fff;
            padding: 3px 8px;
            border-radius: 4px;
        }
        .method-post {
            background-color: #28a745;
        }
        .method-get {
            background-color: #0d6efd;
        }
        .sidebar {
            position: sticky;
            top: 20px;
        }
        .endpoint {
            background-color: #343a40;
            color: #fff;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-family: monospace;
        }
        .json-key {
            color: #d63384;
        }
        .json-string {
            color: #28a745;
        }
        .json-number {
            color: #0d6efd;
        }
        .json-boolean {
            color: #dc3545;
        }
        .json-null {
            color: #6c757d;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="index.php">SimSimi API Docs</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Trang chủ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="authentication.php">Xác thực</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            API
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="login.php">Đăng nhập</a></li>
                            <li><a class="dropdown-item" href="register.php">Đăng ký</a></li>
                            <li><a class="dropdown-item" href="chat.php">Chat</a></li>
                            <li><a class="dropdown-item" href="teach.php">Dạy Bot</a></li>
                            <li><a class="dropdown-item" href="history.php">Lịch sử Chat</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</body>
</html> 