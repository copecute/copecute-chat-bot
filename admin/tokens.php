<?php
require_once 'check_admin.php';

$error = '';
$success = '';

// Xử lý tạo token mới
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_token'])) {
    $qty = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    $quota = isset($_POST['quota']) ? intval($_POST['quota']) : 100;
    
    if ($qty < 1 || $qty > 50) {
        $error = 'Số lượng token phải trong khoảng từ 1 đến 50!';
    } else if ($quota < 1) {
        $error = 'Quota phải lớn hơn 0!';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Tạo mảng lưu token mới
            $new_tokens = [];
            
            for ($i = 0; $i < $qty; $i++) {
                // Tạo token ngẫu nhiên
                $token = bin2hex(random_bytes(32));
                $new_tokens[] = $token;
                
                // Thêm vào cơ sở dữ liệu
                $stmt = $pdo->prepare('INSERT INTO api_tokens (token, quota, status) VALUES (:token, :quota, 1)');
                $stmt->execute([
                    'token' => $token,
                    'quota' => $quota
                ]);
            }
            
            $pdo->commit();
            $_SESSION['new_tokens'] = $new_tokens; // Lưu vào session để hiển thị
            $success = 'Đã tạo ' . $qty . ' token mới với quota ' . $quota . ' thành công!';
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Lỗi hệ thống: ' . $e->getMessage();
        }
    }
}

// Xử lý vô hiệu hóa token
if (isset($_GET['disable']) && is_numeric($_GET['disable'])) {
    $token_id = $_GET['disable'];
    try {
        $stmt = $pdo->prepare('UPDATE api_tokens SET status = 0 WHERE id = :id');
        $stmt->execute(['id' => $token_id]);
        
        if ($stmt->rowCount() > 0) {
            $success = 'Đã vô hiệu hóa token thành công!';
        } else {
            $error = 'Không tìm thấy token cần vô hiệu hóa!';
        }
    } catch (PDOException $e) {
        $error = 'Lỗi hệ thống: ' . $e->getMessage();
    }
}

// Xử lý kích hoạt token
if (isset($_GET['enable']) && is_numeric($_GET['enable'])) {
    $token_id = $_GET['enable'];
    try {
        $stmt = $pdo->prepare('UPDATE api_tokens SET status = 1 WHERE id = :id');
        $stmt->execute(['id' => $token_id]);
        
        if ($stmt->rowCount() > 0) {
            $success = 'Đã kích hoạt token thành công!';
        } else {
            $error = 'Không tìm thấy token cần kích hoạt!';
        }
    } catch (PDOException $e) {
        $error = 'Lỗi hệ thống: ' . $e->getMessage();
    }
}

// Xử lý xóa token
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $token_id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare('DELETE FROM api_tokens WHERE id = :id');
        $stmt->execute(['id' => $token_id]);
        
        if ($stmt->rowCount() > 0) {
            $success = 'Đã xóa token thành công!';
        } else {
            $error = 'Không tìm thấy token cần xóa!';
        }
    } catch (PDOException $e) {
        $error = 'Lỗi hệ thống: ' . $e->getMessage();
    }
}

// Lấy danh sách token có phân trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

try {
    // Tổng số token
    $stmt = $pdo->query('SELECT COUNT(*) FROM user_tokens');
    $total_tokens = $stmt->fetchColumn();
    $total_pages = ceil($total_tokens / $limit);
    
    // Danh sách token có phân trang
    $stmt = $pdo->prepare('
        SELECT ut.id, ut.token, ut.quota, ut.created_at, u.username, u.id as user_id
        FROM user_tokens ut
        JOIN users u ON ut.user_id = u.id
        ORDER BY ut.id DESC
        LIMIT :limit OFFSET :offset
    ');
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $tokens = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = 'Lỗi hệ thống: ' . $e->getMessage();
}

// Xử lý tìm kiếm token
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
if (!empty($search)) {
    try {
        // Tìm kiếm token
        $search_term = "%$search%";
        $stmt = $pdo->prepare('
            SELECT ut.id, ut.token, ut.quota, ut.created_at, u.username, u.id as user_id
            FROM user_tokens ut
            JOIN users u ON ut.user_id = u.id
            WHERE ut.token LIKE :search OR u.username LIKE :search
            ORDER BY ut.id DESC
        ');
        $stmt->bindValue(':search', $search_term);
        $stmt->execute();
        $tokens = $stmt->fetchAll();
        
        // Số kết quả tìm được
        $total_tokens = count($tokens);
        $total_pages = 1; // Không phân trang khi tìm kiếm
    } catch (PDOException $e) {
        $error = 'Lỗi hệ thống: ' . $e->getMessage();
    }
}

// Import header
require_once 'layout/header.php';
?>

<!-- Page heading -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">Quản lý Token</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?php echo $base_url ?>/admin/dashboard.php">Tổng quan</a></li>
            <li class="breadcrumb-item active" aria-current="page">Quản lý Token</li>
        </ol>
    </nav>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<!-- Hiển thị token mới tạo -->
<?php if (isset($_SESSION['new_tokens']) && !empty($_SESSION['new_tokens'])): ?>
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="fas fa-key"></i> Token mới đã tạo</h5>
        </div>
        <div class="card-body">
            <p>Đây là danh sách token vừa tạo. Hãy lưu lại ngay vì bạn sẽ không thể xem lại sau này!</p>
            <div class="row">
                <?php foreach ($_SESSION['new_tokens'] as $token): ?>
                    <div class="col-md-6">
                        <div class="token-card">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge bg-success">Mới</span>
                                <button class="btn btn-sm btn-outline-primary copy-btn" data-token="<?php echo $token; ?>">
                                    <i class="fas fa-copy"></i> Sao chép
                                </button>
                            </div>
                            <div class="text-break">
                                <?php echo $token; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php unset($_SESSION['new_tokens']); ?>
<?php endif; ?>

<!-- Token Actions -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Tạo Token Mới</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="quantity" class="form-label">Số lượng token cần tạo</label>
                        <input type="number" class="form-control" id="quantity" name="quantity" min="1" max="50" value="1" required>
                        <div class="form-text">Bạn có thể tạo từ 1 đến 50 token cùng lúc</div>
                    </div>
                    <div class="mb-3">
                        <label for="quota" class="form-label">Quota (số lượt sử dụng)</label>
                        <input type="number" class="form-control" id="quota" name="quota" min="1" value="100" required>
                    </div>
                    <button type="submit" name="generate_token" class="btn btn-primary">
                        <i class="fas fa-key"></i> Tạo Token
                    </button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Tìm kiếm Token</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="search-form w-100">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Nhập mã token hoặc tên người dùng..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Tìm kiếm
                        </button>
                    </div>
                </form>
                <?php if (!empty($search)): ?>
                    <div class="mt-3">
                        <a href="tokens.php" class="btn btn-sm btn-secondary">
                            <i class="fas fa-times"></i> Xóa bộ lọc
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Danh sách token -->
<div class="card">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-list"></i> Danh sách Token</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Người dùng</th>
                        <th>Token</th>
                        <th>Quota</th>
                        <th>Ngày tạo</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(isset($tokens) && !empty($tokens)): ?>
                        <?php foreach($tokens as $token): ?>
                            <tr>
                                <td><?php echo $token['id']; ?></td>
                                <td>
                                    <a href="users/edit.php?id=<?php echo $token['user_id']; ?>">
                                        <?php echo htmlspecialchars($token['username']); ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="text-muted token-truncate"><?php echo substr($token['token'], 0, 15) . '...'; ?></span>
                                        <button class="btn btn-sm btn-outline-primary ms-2 copy-btn" data-token="<?php echo $token['token']; ?>">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge <?php echo $token['quota'] > 50 ? 'bg-success' : ($token['quota'] > 10 ? 'bg-warning' : 'bg-danger'); ?>">
                                        <?php echo $token['quota']; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($token['created_at'])); ?></td>
                                <td>
                                    <a href="users/edit.php?id=<?php echo $token['user_id']; ?>" class="btn btn-sm btn-info" title="Chỉnh sửa người dùng">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center">Không có token nào.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if (empty($search) && $total_pages > 1): ?>
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page - 1; ?>" aria-label="Previous">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?>" aria-label="Next">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Copy to clipboard buttons
    const copyButtons = document.querySelectorAll('.copy-btn');
    copyButtons.forEach(button => {
        button.addEventListener('click', function() {
            const token = this.getAttribute('data-token');
            navigator.clipboard.writeText(token).then(() => {
                // Thay đổi nội dung nút tạm thời
                const originalHTML = this.innerHTML;
                this.innerHTML = '<i class="fas fa-check"></i> Đã sao chép';
                this.classList.remove('btn-outline-primary');
                this.classList.add('btn-success');
                
                // Khôi phục sau 2 giây
                setTimeout(() => {
                    this.innerHTML = originalHTML;
                    this.classList.remove('btn-success');
                    this.classList.add('btn-outline-primary');
                }, 2000);
            });
        });
    });
});
</script>

<?php
// Import footer
require_once 'layout/footer.php';
?> 