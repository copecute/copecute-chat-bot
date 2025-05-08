<?php
require_once '../check_admin.php';

// Kiểm tra đăng nhập và quyền quản trị
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header('Location: ' . $base_url . '/admin/index.php');
    exit;
}

// Kiểm tra quyền thêm từ khóa
$can_manage_keywords = true; // Quản lý và quản trị viên đều có thể quản lý từ khóa

$error = '';
$success = '';

// Xác định loại từ khóa (mặc định là lịch sự)
$type = isset($_GET['type']) && $_GET['type'] === 'impolite' ? 1 : 0;
$type_name = $type ? 'thô lỗ' : 'lịch sự';

// Xử lý đăng xuất
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . $base_url . '/admin/index.php');
    exit;
}

// Xử lý thêm từ khóa mới
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_keyword'])) {
    // Kiểm tra quyền hạn
    if (!$can_manage_keywords) {
        $error = 'Bạn không có quyền thêm từ khóa mới!';
    } else {
        $keyword = trim($_POST['keyword']);
        $reply = trim($_POST['reply']);
        // Lấy loại từ khóa từ radio button
        $impolite = isset($_POST['impolite']) ? (int)$_POST['impolite'] : $type;
        // Lấy loại câu trả lời từ checkbox
        $reply_impolite = isset($_POST['reply_impolite']) ? 1 : $impolite;
        
        // Kiểm tra dữ liệu đầu vào
        if (empty($keyword)) {
            $error = 'Vui lòng nhập từ khóa!';
        } elseif (empty($reply)) {
            $error = 'Vui lòng nhập câu trả lời!';
        } elseif (strlen($keyword) > 250) {
            $error = 'Từ khóa không được vượt quá 250 ký tự!';
        } elseif (strlen($reply) > 250) {
            $error = 'Câu trả lời không được vượt quá 250 ký tự!';
        } else {
            try {
                $pdo->beginTransaction();
                
                // Kiểm tra từ khóa đã tồn tại chưa
                $stmt = $pdo->prepare("SELECT id FROM keywords WHERE keyword = :keyword AND impolite = :impolite");
                $stmt->execute([
                    'keyword' => $keyword,
                    'impolite' => $impolite
                ]);
                $keyword_id = $stmt->fetchColumn();
                
                if (!$keyword_id) {
                    // Thêm từ khóa mới nếu chưa tồn tại
                    $stmt = $pdo->prepare("INSERT INTO keywords (keyword, impolite) VALUES (:keyword, :impolite)");
                    $stmt->execute([
                        'keyword' => $keyword,
                        'impolite' => $impolite
                    ]);
                    $keyword_id = $pdo->lastInsertId();
                }
                
                // Kiểm tra xem câu trả lời đã tồn tại chưa
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM replies WHERE keyword_id = :keyword_id AND reply = :reply AND impolite = :impolite");
                $stmt->execute([
                    'keyword_id' => $keyword_id,
                    'reply' => $reply,
                    'impolite' => $reply_impolite
                ]);
                
                if ($stmt->fetchColumn() > 0) {
                    $error = 'Câu trả lời này đã tồn tại cho từ khóa!';
                    $pdo->rollBack();
                } else {
                    // Thêm câu trả lời mới
                    $stmt = $pdo->prepare("INSERT INTO replies (keyword_id, reply, impolite) VALUES (:keyword_id, :reply, :impolite)");
                    $stmt->execute([
                        'keyword_id' => $keyword_id,
                        'reply' => $reply,
                        'impolite' => $reply_impolite
                    ]);
                    
                    $pdo->commit();
                    $success = 'Đã thêm từ khóa và câu trả lời mới thành công!';
                    
                    // Chuyển hướng sau khi thêm nếu không chọn tiếp tục thêm
                    if (empty($_POST['add_more'])) {
                        header('Location: ' . $base_url . '/admin/keywords/edit.php?id=' . $keyword_id);
                        exit;
                    }
                    
                    // Xóa form sau khi thêm thành công
                    $keyword = '';
                    $reply = '';
                }
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = 'Lỗi hệ thống: ' . $e->getMessage();
            }
        }
    }
}

// Bao gồm header
include_once('../layout/header.php');
?>

<!-- Page heading -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">Thêm từ khóa mới</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?php echo $base_url ?>/admin/dashboard.php">Tổng quan</a></li>
            <li class="breadcrumb-item"><a href="<?php echo $base_url; ?>/admin/keywords/index.php?type=<?php echo $type ? 'impolite' : 'polite'; ?>">Từ khóa & Phản hồi</a></li>
            <li class="breadcrumb-item active" aria-current="page">Thêm từ khóa</li>
        </ol>
    </nav>
</div>

<!-- Thông báo -->
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

<!-- Action buttons -->
<div class="card mb-4">
    <div class="card-body py-3 d-flex justify-content-between align-items-center">
        <div>
            <a href="<?php echo $base_url; ?>/admin/keywords/index.php?type=<?php echo $type ? 'impolite' : 'polite'; ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Quay lại danh sách
            </a>
        </div>
        <div>
            <span class="badge bg-<?php echo $type ? 'danger' : 'success'; ?> fs-6">
                Đang thêm từ khóa <?php echo $type_name; ?>
            </span>
        </div>
    </div>
</div>

<!-- Form thêm từ khóa -->
<div class="row">
    <div class="col-lg-12">
        <div class="card mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold">Thêm từ khóa và câu trả lời</h6>
            </div>
            <div class="card-body">
                <form method="post" action="">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="keyword" class="form-label">Từ khóa <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="keyword" name="keyword" value="<?php echo isset($keyword) ? htmlspecialchars($keyword) : ''; ?>" maxlength="250" required>
                                <div class="form-text">Nhập từ khóa (câu hỏi) mà người dùng sẽ nhắn (tối đa 250 ký tự)</div>
                                <div id="keywordCounter" class="form-text text-end">Còn lại: <span>250</span> ký tự</div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label d-block">Loại từ khóa</label>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="impolite" id="impolite_polite" value="0" <?php echo $type === 0 ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="impolite_polite">Lịch sự</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="impolite" id="impolite_impolite" value="1" <?php echo $type === 1 ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="impolite_impolite">Thô lỗ</label>
                                </div>
                                <div class="form-text">Chọn phong cách từ khóa</div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="add_more" name="add_more" checked>
                                    <label class="form-check-label" for="add_more">
                                        Tiếp tục thêm từ khóa khác sau khi lưu
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="reply" class="form-label">Câu trả lời <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="reply" name="reply" rows="7" maxlength="250" required><?php echo isset($reply) ? htmlspecialchars($reply) : ''; ?></textarea>
                                <div class="form-text">Nhập câu trả lời cho từ khóa này (tối đa 250 ký tự)</div>
                                <div id="replyCounter" class="form-text text-end">Còn lại: <span>250</span> ký tự</div>
                            </div>
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="reply_impolite" name="reply_impolite" <?php echo $type ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="reply_impolite">Câu trả lời thô lỗ</label>
                                    <div class="form-text">Mặc định theo loại từ khóa, bạn có thể thay đổi.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-end mt-3">
                        <button type="reset" class="btn btn-secondary me-2">
                            <i class="fas fa-undo me-1"></i> Làm mới
                        </button>
                        <button type="submit" name="add_keyword" class="btn btn-success">
                            <i class="fas fa-save me-1"></i> Lưu từ khóa
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Hướng dẫn -->
<div class="card mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold">Hướng dẫn thêm từ khóa</h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6><i class="fas fa-lightbulb text-warning me-2"></i> Cách thêm từ khóa hiệu quả</h6>
                <ul>
                    <li>Mỗi từ khóa nên ngắn gọn và đặc trưng</li>
                    <li>Nên thêm nhiều câu trả lời cho một từ khóa để tăng sự đa dạng</li>
                    <li>Để ý chọn đúng loại phản hồi (lịch sự/thô lỗ)</li>
                    <li>Câu trả lời nên tự nhiên và phù hợp với từ khóa</li>
                </ul>
            </div>
            <div class="col-md-6">
                <h6><i class="fas fa-exclamation-triangle text-danger me-2"></i> Lưu ý</h6>
                <ul>
                    <li>Có thể thêm nhiều câu trả lời cho một từ khóa sau khi đã tạo từ khóa</li>
                    <li>Từ khóa có thể chứa nhiều từ (câu hỏi, cụm từ...)</li>
                    <li>Nếu từ khóa đã tồn tại, hệ thống sẽ thêm câu trả lời vào từ khóa đó</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript cho bộ đếm ký tự -->
<script>
function updateCounter(input, counter) {
    const maxLength = input.maxLength;
    const currentLength = input.value.length;
    const remaining = maxLength - currentLength;
    const counterSpan = counter.querySelector('span');
    counterSpan.textContent = remaining;
    
    // Thêm class cảnh báo khi còn ít ký tự
    if (remaining <= 50) {
        counterSpan.classList.add('text-warning');
    } else {
        counterSpan.classList.remove('text-warning');
    }
    
    // Thêm class nguy hiểm khi gần hết ký tự
    if (remaining <= 10) {
        counterSpan.classList.remove('text-warning');
        counterSpan.classList.add('text-danger');
    } else {
        counterSpan.classList.remove('text-danger');
    }
}

// Khởi tạo bộ đếm cho từ khóa
const keywordInput = document.getElementById('keyword');
const keywordCounter = document.getElementById('keywordCounter');
keywordInput.addEventListener('input', () => updateCounter(keywordInput, keywordCounter));
updateCounter(keywordInput, keywordCounter);

// Khởi tạo bộ đếm cho câu trả lời
const replyInput = document.getElementById('reply');
const replyCounter = document.getElementById('replyCounter');
replyInput.addEventListener('input', () => updateCounter(replyInput, replyCounter));
updateCounter(replyInput, replyCounter);
</script>

<?php include_once('../layout/footer.php'); ?> 