<?php
// Định nghĩa hằng số cho phép truy cập file
if (!defined('ALLOW_ACCESS')) {
    define('ALLOW_ACCESS', true);
}

session_start();
require_once '../includes/config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $base_url . '/login.php');
    exit;
}

// Lấy thông tin người dùng
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$user_level = isset($_SESSION['user_level']) ? $_SESSION['user_level'] : 0;

// Lấy avatar của người dùng
$user_avatar = '/assets/img/avatar/default.png';
try {
    $stmt = $pdo->prepare("SELECT avatar FROM user_infos WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $user_id]);
    $avatar_result = $stmt->fetch();
    if ($avatar_result && !empty($avatar_result['avatar'])) {
        $user_avatar = $avatar_result['avatar'];
    }
} catch (PDOException $e) {
    // Giữ avatar mặc định nếu có lỗi
}

// Lấy cài đặt thời gian trễ và tin nhắn chào mừng từ system_settings
$min_reply_delay = 1;
$max_reply_delay = 2;
$default_greeting = 'Tôi là copecute. Bạn có thể hỏi tôi bất cứ điều gì.'; // Tin nhắn chào mừng mặc định

try {
    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('min_reply_delay', 'max_reply_delay', 'default_greeting')");
    $stmt->execute();
    while ($row = $stmt->fetch()) {
        if ($row['setting_key'] == 'min_reply_delay') {
            $min_reply_delay = floatval($row['setting_value']);
        } elseif ($row['setting_key'] == 'max_reply_delay') {
            $max_reply_delay = floatval($row['setting_value']);
        } elseif ($row['setting_key'] == 'default_greeting') {
            $default_greeting = $row['setting_value'];
        }
    }
} catch (PDOException $e) {
    // Giữ giá trị mặc định nếu có lỗi
}

// Đảm bảo các giá trị hợp lệ
$min_reply_delay = max(0.5, $min_reply_delay); // Tối thiểu 0.5 giây
$max_reply_delay = max($min_reply_delay, $max_reply_delay); // Đảm bảo max > min

// Chuyển đổi giây thành mili giây
$min_reply_delay_ms = $min_reply_delay * 1000;
$max_reply_delay_ms = $max_reply_delay * 1000;

try {
    // Kiểm tra xem người dùng có tồn tại trong cơ sở dữ liệu không
    $stmt = $pdo->prepare('SELECT id FROM users WHERE id = :user_id');
    $stmt->execute(['user_id' => $user_id]);
    $user_exists = $stmt->fetch();
    
    // Nếu người dùng không tồn tại, hủy session và chuyển hướng về trang đăng nhập
    if (!$user_exists) {
        session_destroy();
        header('Location: ' . $base_url . '/login.php?error=user_deleted');
        exit;
    }
    
    // Lấy thông tin người dùng
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :user_id');
    $stmt->execute(['user_id' => $user_id]);
    $user = $stmt->fetch();
    
    // Lấy thông tin profile của người dùng
    $stmt = $pdo->prepare('SELECT * FROM user_infos WHERE user_id = :user_id');
    $stmt->execute(['user_id' => $user_id]);
    $profile = $stmt->fetch();
    
    // Lấy thông tin token/quota của người dùng
    $stmt = $pdo->prepare('SELECT quota FROM user_tokens WHERE user_id = :user_id');
    $stmt->execute(['user_id' => $user_id]);
    $token_info = $stmt->fetch();
    
    $quota = $token_info ? $token_info['quota'] : 0;
    $quota = max(0, $quota); // Đảm bảo quota không âm
    
    // Lấy lịch sử chat của người dùng từ bảng chat_history thay vì messages
    $stmt = $pdo->prepare('
        SELECT id, user_input, bot_response, created_at
        FROM chat_history
        WHERE user_id = :user_id 
        ORDER BY created_at DESC 
        LIMIT 100
    ');
    $stmt->execute(['user_id' => $user_id]);
    $chat_history = $stmt->fetchAll();
    
    // Đảo ngược mảng để hiển thị tin nhắn cũ nhất lên trên
    $chat_history = array_reverse($chat_history);
    
} catch (PDOException $e) {
    $error = 'Lỗi hệ thống: ' . $e->getMessage();
}

// Chức năng đăng xuất
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . $base_url . '/login.php');
    exit;
}

// Hàm lấy avatar ngẫu nhiên
function getRandomAvatar() {
    $avatars = glob("../assets/img/copecute/cope*.png");
    return $avatars ? $avatars[array_rand($avatars)] : "../assets/img/avatar/default.png";
}

// Lấy thời gian hiện tại
function getUserTimeOfDay() {
    $hour = date('H');
    if ($hour < 12) {
        return "Chào buổi sáng";
    } elseif ($hour < 18) {
        return "Chào buổi chiều";
    } else {
        return "Chào buổi tối";
    }
}

// Hiển thị tên vai trò người dùng
if (!function_exists('getUserRoleName')) {
function getUserRoleName($level) {
    switch ($level) {
        case 2:
            return "Quản trị viên";
        case 1:
            return "Quản lý";
        case 0:
        default:
            return "Người dùng";
        }
    }
}

// Hiển thị thông báo lỗi nếu có
$error_msg = '';
if (isset($_GET['error']) && $_GET['error'] === 'access_denied') {
    $error_msg = 'Bạn không có quyền truy cập vào trang quản trị!';
}

// Include header
require_once '../includes/layouts/header.php';
?>

<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- AOS - Animate On Scroll -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>

<!-- Container cho chat -->
    <div class="container">
        <?php if (!empty($error_msg)): ?>
        <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
            <?php echo $error_msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <div class="chat-container">
            <div class="chat-header">
                <div>
                <h4>copecute</h4>
                    <small class="text-muted">Đang hoạt động</small>
                </div>
                <div class="d-flex align-items-center">
                <button id="teachBotBtn" class="btn btn-outline-primary btn-sm me-2">
                    <i class="fas fa-graduation-cap"></i> <span class="d-none d-sm-inline">Dạy Bot</span>
                </button>
                <button id="clearAllMessages" class="btn btn-outline-danger btn-sm me-3">
                    <i class="fas fa-trash-alt"></i> <span class="d-none d-sm-inline">Xóa tất cả</span>
                    </button>
                    <div class="quota-display">
                        <i class="fas fa-bolt"></i> Quota: <?php echo $quota; ?>
                    </div>
                </div>
            </div>
            
            <div class="chat-messages" id="chatMessages">
                <!-- Bot chào mừng -->
                <div class="message bot-message">
                    <img src="<?php echo getRandomAvatar(); ?>" class="bot-avatar">
                    <div class="message-content">
                    <?php echo getUserTimeOfDay(); ?>, <?php echo htmlspecialchars($username); ?>!
                    <?php echo htmlspecialchars($default_greeting); ?>
                        <div class="message-time">Hôm nay, <?php echo date('H:i'); ?></div>
                    </div>
                </div>
                
            <?php foreach ($chat_history as $msg): ?>
            <!-- User message -->
            <div class="message user-message">
                <div class="message-content">
                    <?php echo htmlspecialchars($msg['user_input']); ?>
                    <div class="message-time"><?php echo date('d/m/Y H:i', strtotime($msg['created_at'])); ?></div>
                </div>
                <img src="<?php echo $user_avatar; ?>" class="user-avatar">
            </div>

                        <!-- Bot message -->
                        <div class="message bot-message">
                            <img src="<?php echo getRandomAvatar(); ?>" class="bot-avatar">
                            <div class="message-content">
                    <?php echo htmlspecialchars($msg['bot_response']); ?>
                                <div class="message-time"><?php echo date('d/m/Y H:i', strtotime($msg['created_at'])); ?></div>
                            </div>
                        </div>
                <?php endforeach; ?>
            </div>
            
            <div class="chat-input">
                <input type="text" id="userInput" class="form-control" placeholder="Nhập tin nhắn..." maxlength="250" autocomplete="off">
                <button id="sendButton" class="btn btn-primary ms-2">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>
    
<!-- Modal Dạy Bot -->
<div class="modal fade" id="teachBotModal" tabindex="-1" aria-labelledby="teachBotModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="teachBotModalLabel">
                    <i class="fas fa-graduation-cap me-2"></i> Dạy Bot
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="teachBotForm">
                    <div class="mb-3">
                        <label for="teachKeyword" class="form-label">Từ khóa <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="teachKeyword" name="keyword" 
                               placeholder="Nhập từ khóa hoặc cụm từ mà người dùng có thể hỏi" 
                               maxlength="250" required>
                        <div class="form-text">Từ khóa hoặc cụm từ mà người dùng có thể hỏi bot (tối đa 250 ký tự)</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="teachReply" class="form-label">Câu trả lời <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="teachReply" name="reply" rows="4" maxlength="250"
                                 placeholder="Nhập câu trả lời mà bot sẽ phản hồi khi người dùng hỏi từ khóa trên" required></textarea>
                        <div class="form-text">Câu trả lời của bot cho từ khóa này (tối đa 250 ký tự)</div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="teachImpolite" name="impolite">
                        <label class="form-check-label" for="teachImpolite">
                            Đây là nội dung thô lỗ/không lịch sự
                        </label>
                        <div class="form-text">Đánh dấu nếu câu trả lời chứa ngôn ngữ thô lỗ hoặc không phù hợp cho mọi đối tượng</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-primary" id="submitTeachBot">
                    <i class="fas fa-paper-plane me-2"></i> Gửi đề xuất
                </button>
            </div>
            </div>
        </div>
    </div>
    
<style>
    .chat-container {
        max-width: 800px;
        margin: 20px auto;
        background: white;
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        height: calc(100vh - 240px);
        /* Điều chỉnh để phù hợp với header/footer */
        display: flex;
        flex-direction: column;
    }

    .chat-header {
        padding: 15px;
        border-bottom: 1px solid #e9ecef;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .chat-messages {
        flex: 1;
        overflow-y: auto;
        padding: 15px;
    }

    .chat-input {
        padding: 15px;
        border-top: 1px solid #e9ecef;
        display: flex;
    }

    .message {
        margin-bottom: 15px;
        display: flex;
    }

    .message.user-message {
        justify-content: flex-end;
    }

    .message.bot-message {
        justify-content: flex-start;
    }

    .message-content {
        max-width: 70%;
        padding: 10px 15px;
        border-radius: 18px;
        position: relative;
    }

    .user-message .message-content {
        background-color: #007bff;
        color: white;
        border-bottom-right-radius: 5px;
    }

    .bot-message .message-content {
        background-color: #e9ecef;
        color: #212529;
        border-bottom-left-radius: 5px;
    }

    .message-time {
        font-size: 0.7rem;
        margin-top: 5px;
        color: #6c757d;
        text-align: right;
    }

    .user-message .message-content .message-time {
        color: #eaeaea;
    }

    .bot-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        margin-right: 10px;
    }

    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        margin-left: 10px;
    }

    .quota-display {
        background-color: rgba(78, 115, 223, 0.1);
        color: var(--primary-color);
        padding: 8px 15px;
        border-radius: 50px;
        font-size: 0.9rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        transition: all 0.3s ease;
        border: 1px solid rgba(78, 115, 223, 0.2);
    }

    .quota-display i {
        margin-right: 5px;
        color: var(--primary-color);
    }

    /* Hiệu ứng đang nhập... */
    .typing-indicator {
        display: flex;
        align-items: center;
        padding: 5px 0;
    }

    .typing-indicator span {
        height: 8px;
        width: 8px;
        margin: 0 2px;
        background-color: #bbb;
        display: block;
        border-radius: 50%;
        opacity: 0.4;
        animation: typing 1.5s infinite;
    }

    .typing-indicator span:nth-of-type(1) {
        animation-delay: 0s;
    }

    .typing-indicator span:nth-of-type(2) {
        animation-delay: 0.2s;
    }

    .typing-indicator span:nth-of-type(3) {
        animation-delay: 0.4s;
    }

    <blade keyframes|%20typing%20%7B>0% {
        transform: scale(1);
        opacity: 0.4;
    }

    50% {
        transform: scale(1.2);
        opacity: 1;
    }

    100% {
        transform: scale(1);
        opacity: 0.4;
    }
    }

    /* Nút xóa tin nhắn */
    #clearAllMessages {
        font-size: 0.85rem;
        transition: all 0.3s ease;
        border-radius: 20px;
    }

    #clearAllMessages:hover {
        background-color: #dc3545;
        color: white;
    }
</style>

    <script>
    $(document).ready(function () {
            // Cuộn xuống cuối
            function scrollToBottom() {
                var chatMessages = document.getElementById('chatMessages');
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
            
            scrollToBottom();
            
            // Hàm lấy thời điểm trong ngày
            function getUserTimeOfDay() {
                var hour = new Date().getHours();
                if (hour < 12) {
                    return "Chào buổi sáng";
                } else if (hour < 18) {
                    return "Chào buổi chiều";
                } else {
                    return "Chào buổi tối";
                }
            }
            
            // Xử lý gửi tin nhắn
            function sendMessage() {
                var userInput = $('#userInput').val().trim();
                if (userInput === '') return;
                
                // Kiểm tra độ dài tin nhắn
                if (userInput.length > 250) {
                    alert('Bạn nói dài quá, tóm tắt lại được không?');
                    return;
                }
                
                // Hiển thị tin nhắn người dùng
                $('#chatMessages').append(`
                    <div class="message user-message">
                        <div class="message-content">
                            ${userInput}
                            <div class="message-time">Vừa xong</div>
                        </div>
                        <img src="<?php echo $user_avatar; ?>" class="user-avatar">
                    </div>
                `);
                
                // Xóa input
                $('#userInput').val('');
                
                // Cuộn xuống
                scrollToBottom();
                
            // Hiển thị loading "đang nhập..."
                $('#chatMessages').append(`
                    <div class="message bot-message" id="loadingMessage">
                        <img src="${getRandomBotAvatar()}" class="bot-avatar">
                        <div class="message-content">
                            <div class="typing-indicator">
                                <span></span>
                                <span></span>
                                <span></span>
                            </div>
                            <div class="message-time"></div>
                        </div>
                    </div>
                `);
                scrollToBottom();
                
                // Gửi yêu cầu AJAX
                $.ajax({
                    url: '<?php echo $base_url; ?>/chat/process_chat.php',
                    type: 'POST',
                    data: {
                        user_input: userInput
                    },
                success: function (response) {
                    // Xử lý phản hồi sau một khoảng thời gian trễ để tạo hiệu ứng tự nhiên
                    setTimeout(function () {
                        // Xóa tin nhắn loading
                        $('#loadingMessage').remove();
                        
                        // Kiểm tra nếu có lỗi liên quan đến người dùng bị xóa
                        if (response.error === 'user_deleted') {
                            alert('Tài khoản của bạn không tồn tại hoặc đã bị xóa. Vui lòng đăng nhập lại.');
                            window.location.href = '../login.php?error=user_deleted';
                            return;
                        }
                        
                        // Hiển thị phản hồi từ bot và nút dạy bot nếu cần
                        var botMessage = `
                            <div class="message bot-message">
                                <img src="${getRandomBotAvatar()}" class="bot-avatar">
                                <div class="message-content">
                                    ${response.bot_response}
                                    <div class="message-time">Vừa xong</div>
                                </div>
                            </div>`;

                        // Nếu là default response, thêm nút dạy bot ngay dưới tin nhắn
                        if (response.is_default_response) {
                            botMessage += `
                                <div class="text-center mt-2 mb-3">
                                    <button class="btn btn-sm btn-outline-primary teach-this" data-keyword="${userInput}">
                                        <i class="fas fa-graduation-cap me-1"></i> Dạy bot câu trả lời
                                    </button>
                                </div>`;
                        }
                        
                        $('#chatMessages').append(botMessage);
                        
                        // Cập nhật quota
                        $('.quota-display').html('<i class="fas fa-bolt"></i> Quota: ' + response.quota);
                            $('#header_quota').text(response.quota);
                        
                        scrollToBottom();
                    }, Math.random() * ( <?php echo $max_reply_delay_ms; ?> - <?php echo $min_reply_delay_ms; ?> ) + <?php echo $min_reply_delay_ms; ?>);
                    },
                error: function (xhr) {
                    // Xử lý lỗi sau một khoảng thời gian trễ tương tự
                    setTimeout(function () {
                        // Xóa tin nhắn loading
                        $('#loadingMessage').remove();
                        
                        // Kiểm tra nếu lỗi là do người dùng bị xóa
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.error === 'user_deleted') {
                                alert('Tài khoản của bạn không tồn tại hoặc đã bị xóa. Vui lòng đăng nhập lại.');
                                window.location.href = '<?php echo $base_url; ?>/login.php?error=user_deleted';
                                return;
                            }
                        } catch (e) {
                            // Không làm gì nếu không phân tích được lỗi
                        }
                        
                        // Hiển thị thông báo lỗi và nút dạy bot
                        var errorMessage = `
                            <div class="message bot-message">
                                <img src="${getRandomBotAvatar()}" class="bot-avatar">
                                <div class="message-content">
                                    Xin lỗi, có lỗi xảy ra. Vui lòng thử lại sau.
                                    <div class="message-time">Vừa xong</div>
                                </div>
                            </div>
                            <div class="text-center mt-2 mb-3">
                                <button class="btn btn-sm btn-outline-primary teach-this" data-keyword="${userInput}">
                                    <i class="fas fa-graduation-cap me-1"></i> Dạy bot câu trả lời
                                </button>
                            </div>`;
                        
                        $('#chatMessages').append(errorMessage);
                        scrollToBottom();
                    }, 1000);
                    }
                });
            }
            
            // Gửi tin nhắn khi nhấn nút
        $('#sendButton').click(function () {
                sendMessage();
            });
            
            // Gửi tin nhắn khi nhấn Enter
        $('#userInput').keypress(function (e) {
            if (e.which == 13) {
                    sendMessage();
                    return false;
                }
            });
            
            // Hàm lấy avatar bot ngẫu nhiên
            function getRandomBotAvatar() {
                var avatars = [ <?php
                    $avatars = glob("../assets/img/copecute/cope*.png");
                    foreach($avatars as $avatar) {
                        echo '"'.$avatar.
                        '", ';
                    } ?>
                ];
                return avatars[Math.floor(Math.random() * avatars.length)];
            }
            
            // Hàm lấy thời điểm trong ngày
            function getUserTimeOfDay() {
                var hour = new Date().getHours();
                if (hour < 12) {
                    return "Chào buổi sáng";
                } else if (hour < 18) {
                    return "Chào buổi chiều";
                } else {
                    return "Chào buổi tối";
                }
            }
            
        // User dropdown functionality
        $('#userDropdownToggle').click(function (e) {
            e.stopPropagation();
            $('#userDropdownMenu').toggleClass('show');
        });

        // Close dropdown when clicking elsewhere
        $(document).click(function (e) {
            if (!$(e.target).closest('.user-dropdown').length) {
                $('#userDropdownMenu').removeClass('show');
            }
        });

        // Clear messages button functionality
        $('#clearAllMessages').click(function (e) {
            e.preventDefault();
                if (confirm('Bạn có chắc chắn muốn xóa tất cả tin nhắn không?')) {
                    $.ajax({
                        url: '<?php echo $base_url; ?>/chat/clear_messages.php',
                        type: 'POST',
                    success: function (response) {
                            if (response.success) {
                                // Xóa tất cả tin nhắn trên giao diện
                                $('#chatMessages').empty();
                                
                            // Hiển thị tin nhắn "đang nhập..." của bot
                                $('#chatMessages').append(`
                                    <div class="message bot-message" id="loadingMessage">
                                        <img src="${getRandomBotAvatar()}" class="bot-avatar">
                                        <div class="message-content">
                                        <div class="typing-indicator">
                                            <span></span>
                                            <span></span>
                                            <span></span>
                                        </div>
                                        <div class="message-time"></div>
                                        </div>
                                    </div>
                                `);
                                
                                // Cuộn xuống
                                scrollToBottom();
                                
                            // Sau 2 giây hiển thị tin nhắn phản hồi của bot
                            setTimeout(function () {
                                    // Xóa tin nhắn loading
                                    $('#loadingMessage').remove();
                                    
                                    // Lấy lời chào theo thời gian hiện tại
                                    var greeting = '';
                                    var currentHour = new Date().getHours();
                                    if (currentHour < 12) {
                                        greeting = "Chào buổi sáng";
                                    } else if (currentHour < 18) {
                                        greeting = "Chào buổi chiều";
                                    } else {
                                        greeting = "Chào buổi tối";
                                    }
                                    
                                    // Hiển thị phản hồi từ bot
                                    $('#chatMessages').append(`
                                        <div class="message bot-message">
                                            <img src="${getRandomBotAvatar()}" class="bot-avatar">
                                            <div class="message-content">
                                            ${greeting}, <?php echo htmlspecialchars($username); ?>! <?php echo htmlspecialchars($default_greeting); ?>
                                                <div class="message-time">Vừa xong</div>
                                            </div>
                                        </div>
                                    `);
                                    
                                    // Cuộn xuống một lần nữa
                                    scrollToBottom();
                            }, 2000);
                            } else {
                                alert('Có lỗi xảy ra: ' + response.error);
                            }
                        },
                    error: function () {
                            alert('Có lỗi xảy ra khi kết nối đến máy chủ!');
                        }
                    });
            }
        });

        // Xử lý hiển thị modal dạy bot
        $('#teachBotBtn').click(function() {
            $('#teachBotModal').modal('show');
        });

        // Xử lý nút dạy bot cho câu hỏi cụ thể
        $(document).on('click', '.teach-this', function() {
            var keyword = $(this).data('keyword');
            $('#teachKeyword').val(keyword);
            $('#teachBotModal').modal('show');
        });

        // Xử lý submit form dạy bot
        $('#submitTeachBot').click(function() {
            var form = $('#teachBotForm');
            var keyword = $('#teachKeyword').val().trim();
            var reply = $('#teachReply').val().trim();
            var impolite = $('#teachImpolite').prop('checked') ? 1 : 0;
            
            console.log("Checkbox checked:", $('#teachImpolite').prop('checked'));
            console.log("Impolite value:", impolite);

            if (!keyword || !reply) {
                alert('Vui lòng nhập đầy đủ từ khóa và câu trả lời!');
                return;
            }

            $.ajax({
                url: '<?php echo $base_url; ?>/chat/teachbot_controller.php',
                type: 'POST',
                data: {
                    keyword: keyword,
                    reply: reply,
                    impolite: impolite
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.message);
                        $('#teachBotModal').modal('hide');
                        // Reset form và checkbox
                        form[0].reset();
                        $('#teachImpolite').prop('checked', false);
                    } else {
                        alert(response.error || 'Có lỗi xảy ra, vui lòng thử lại sau!');
                    }
                },
                error: function() {
                    alert('Có lỗi xảy ra khi kết nối đến máy chủ!');
                }
            });
        });

        // Xử lý phản hồi từ bot và hiển thị nút dạy bot khi không tìm thấy câu trả lời
        function handleBotResponse(response) {
            // Xóa tin nhắn loading
            $('#loadingMessage').remove();
            
            // Kiểm tra nếu có lỗi liên quan đến người dùng bị xóa
            if (response.error === 'user_deleted') {
                alert('Tài khoản của bạn không tồn tại hoặc đã bị xóa. Vui lòng đăng nhập lại.');
                window.location.href = '../login.php?error=user_deleted';
                return;
            }
            
            // Hiển thị phản hồi từ bot
            var botMessage = `
                <div class="message bot-message">
                    <img src="${getRandomBotAvatar()}" class="bot-avatar">
                    <div class="message-content">
                        ${response.bot_response}
                        <div class="message-time">Vừa xong</div>
                    </div>
                </div>`;

            // Nếu là default response, thêm nút dạy bot
            if (response.is_default_response) {
                botMessage += `
                    <div class="text-center mt-2 mb-3">
                        <button class="btn btn-sm btn-outline-primary teach-this" data-keyword="${$('#userInput').val()}">
                            <i class="fas fa-graduation-cap me-1"></i> Dạy bot câu trả lời
                        </button>
                    </div>`;
            }
            
            $('#chatMessages').append(botMessage);
            
            // Cập nhật quota
            $('.quota-display').html('<i class="fas fa-bolt"></i> Quota: ' + response.quota);
            $('#header_quota').text(response.quota);
            
            scrollToBottom();
        }
        });
    </script>

<script>
    // Khởi tạo AOS (Animate On Scroll)
    AOS.init({
        duration: 800,
        once: true
        });
    </script>
</body>

</html> 