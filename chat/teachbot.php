<?php
require_once '../includes/config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $base_url . '/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Lấy thông tin người dùng
try {
    $stmt = $pdo->prepare("SELECT u.*, ut.token, ut.quota FROM users u LEFT JOIN user_tokens ut ON u.id = ut.user_id WHERE u.id = :user_id");
    $stmt->execute(['user_id' => $user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        header('Location: ' . $base_url . '/logout.php');
        exit;
    }
} catch (PDOException $e) {
    $error = 'Lỗi hệ thống: ' . $e->getMessage();
}

$error = '';
$success = '';

// Xử lý form dạy bot
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['teach'])) {
    $keyword = trim($_POST['keyword']);
    $reply = trim($_POST['reply']);
    $impolite = isset($_POST['impolite']) ? 1 : 0;
    
    // Kiểm tra dữ liệu đầu vào
    if (empty($keyword) || empty($reply)) {
        $error = 'Vui lòng nhập đầy đủ từ khóa và câu trả lời.';
    } elseif (strlen($keyword) > 250) {
        $error = 'Từ khóa không được vượt quá 250 ký tự.';
    } elseif (strlen($reply) > 250) {
        $error = 'Câu trả lời không được vượt quá 250 ký tự.';
    } else {
        try {
            // Thêm yêu cầu dạy bot mới
            $stmt = $pdo->prepare("INSERT INTO teachbot_requests (user_id, keyword, reply, impolite) VALUES (:user_id, :keyword, :reply, :impolite)");
            $stmt->execute([
                'user_id' => $user_id,
                'keyword' => $keyword,
                'reply' => $reply,
                'impolite' => $impolite
            ]);
            
            $success = 'Cảm ơn bạn đã dạy bot! Yêu cầu của bạn đã được gửi và sẽ được xem xét bởi quản trị viên.';
            
            // Xóa dữ liệu form sau khi gửi thành công
            $keyword = '';
            $reply = '';
            $impolite = 0;
        } catch (PDOException $e) {
            $error = 'Lỗi hệ thống: ' . $e->getMessage();
        }
    }
}

// Lấy danh sách các yêu cầu đã gửi của người dùng
try {
    $stmt = $pdo->prepare("
        SELECT tr.*, 
               CASE 
                   WHEN tr.status = 'pending' THEN 'Đang chờ duyệt'
                   WHEN tr.status = 'approved' THEN 'Đã duyệt'
                   WHEN tr.status = 'rejected' THEN 'Đã từ chối'
               END as status_text
        FROM teachbot_requests tr 
        WHERE tr.user_id = :user_id 
        ORDER BY tr.created_at DESC
        LIMIT 10
    ");
    $stmt->execute(['user_id' => $user_id]);
    $requests = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Lỗi hệ thống: ' . $e->getMessage();
    $requests = [];
}

// Lấy các thiết lập cài đặt
$settings = [];
try {
    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM system_settings");
    $stmt->execute();
    $settings_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($settings_results as $setting) {
        $settings[$setting['setting_key']] = $setting['setting_value'];
    }
} catch (PDOException $e) {
    // Xử lý lỗi
    $error = 'Lỗi khi lấy thiết lập: ' . $e->getMessage();
}

// Lấy thông tin người dùng từ user_infos
try {
    $stmt = $pdo->prepare("SELECT * FROM user_infos WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $user_id]);
    $user_info = $stmt->fetch();
} catch (PDOException $e) {
    $error = 'Lỗi hệ thống: ' . $e->getMessage();
    $user_info = [];
}

$page_title = "Dạy Bot - " . htmlspecialchars($settings['site_name'] ?? 'Chat Bot');
include_once '../includes/layouts/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="m-0">
                        <i class="fas fa-robot me-2"></i> Dạy Bot
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mb-4">
                        <p class="text-muted">
                            Dạy bot hiểu những từ khóa mới và cách trả lời phù hợp. 
                            Tất cả các đề xuất sẽ được xem xét bởi quản trị viên trước khi được thêm vào hệ thống.
                        </p>
                    </div>
                    
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="keyword" class="form-label">Từ khóa <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="keyword" name="keyword" 
                                   placeholder="Nhập từ khóa hoặc cụm từ mà người dùng có thể hỏi" 
                                   maxlength="250" required 
                                   value="<?php echo isset($keyword) ? htmlspecialchars($keyword) : ''; ?>">
                            <div class="form-text">Từ khóa hoặc cụm từ mà người dùng có thể hỏi bot (tối đa 250 ký tự)</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="reply" class="form-label">Câu trả lời <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="reply" name="reply" rows="4" maxlength="250"
                                     placeholder="Nhập câu trả lời mà bot sẽ phản hồi khi người dùng hỏi từ khóa trên" required><?php echo isset($reply) ? htmlspecialchars($reply) : ''; ?></textarea>
                            <div class="form-text">Câu trả lời của bot cho từ khóa này (tối đa 250 ký tự)</div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="impolite" name="impolite" 
                                  <?php echo isset($impolite) && $impolite ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="impolite">
                                Đây là nội dung thô lỗ/không lịch sự
                            </label>
                            <div class="form-text">Đánh dấu nếu câu trả lời chứa ngôn ngữ thô lỗ hoặc không phù hợp cho mọi đối tượng</div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" name="teach" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i> Gửi đề xuất
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Danh sách yêu cầu đã gửi -->
            <div class="card shadow-sm mt-4">
                <div class="card-header">
                    <h5 class="m-0">Các đề xuất gần đây của bạn</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($requests)): ?>
                    <div class="text-center text-muted p-4">
                        <i class="fas fa-inbox fa-3x mb-3"></i>
                        <p>Bạn chưa gửi đề xuất nào. Hãy dạy bot để nó thông minh hơn!</p>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Từ khóa</th>
                                    <th>Câu trả lời</th>
                                    <th>Trạng thái</th>
                                    <th>Ngày gửi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requests as $request): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($request['keyword']); ?></td>
                                    <td><?php echo nl2br(htmlspecialchars($request['reply'])); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $request['status'] === 'pending' ? 'warning' : 
                                                ($request['status'] === 'approved' ? 'success' : 'danger'); 
                                        ?>">
                                            <?php echo $request['status_text']; ?>
                                        </span>
                                        <?php if ($request['status'] === 'rejected' && !empty($request['admin_notes'])): ?>
                                        <i class="fas fa-info-circle text-info ms-1" 
                                           data-bs-toggle="tooltip" 
                                           title="<?php echo htmlspecialchars($request['admin_notes']); ?>"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo date('d/m/Y H:i', strtotime($request['created_at'])); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Xóa toàn bộ phần đếm ký tự cũ
});
</script>

<?php include_once '../includes/layouts/footer.php'; ?> 