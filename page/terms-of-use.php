<?php
// Định nghĩa hằng số cho phép truy cập file
if (!defined('ALLOW_ACCESS')) {
    define('ALLOW_ACCESS', true);
}

// Kết nối đến config
require_once '../includes/config.php';

// Include header
require_once '../includes/layouts/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h1 class="text-center mb-4">Terms of Use</h1>
                    
                    <div class="terms-content">
                        <section class="mb-4">
                            <h2 class="h4 mb-3">1. Introduction</h2>
                            <p>Welcome to CopeCute. By accessing and using the CopeCute website and application (collectively referred to as "Services"), you agree to be bound by and comply with the following terms and conditions.</p>
                        </section>

                        <section class="mb-4">
                            <h2 class="h4 mb-3">2. Terms of Service</h2>
                            <p>When using our Services, you agree to:</p>
                            <ul class="list-unstyled ps-4">
                                <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i>Provide accurate and complete information</li>
                                <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i>Maintain the security of your account</li>
                                <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i>Not use the Services for any illegal purposes</li>
                                <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i>Not violate others' intellectual property rights</li>
                                <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i>Not disrupt or interfere with the Services</li>
                            </ul>
                        </section>

                        <section class="mb-4">
                            <h2 class="h4 mb-3">3. Intellectual Property Rights</h2>
                            <p>All content, design, graphics, compilation, digital information, software, services, and all other elements of the Services are owned by CopeCute or are licensed for use.</p>
                        </section>

                        <section class="mb-4">
                            <h2 class="h4 mb-3">4. Limitation of Liability</h2>
                            <p>The Services are provided "as is" and "as available." We do not guarantee that the Services will be uninterrupted or error-free. We are not liable for any damages arising from the use of the Services.</p>
                        </section>

                        <section class="mb-4">
                            <h2 class="h4 mb-3">5. Changes to Terms</h2>
                            <p>We reserve the right to change these terms at any time. Continued use of the Services after changes take effect means you accept the new terms.</p>
                        </section>

                        <section class="mb-4">
                            <h2 class="h4 mb-3">6. Termination</h2>
                            <p>We reserve the right to terminate or suspend your access to the Services at any time, for any reason, without prior notice.</p>
                        </section>

                        <section class="mb-4">
                            <h2 class="h4 mb-3">7. Governing Law</h2>
                            <p>These terms shall be governed by and construed in accordance with Vietnamese law.</p>
                        </section>

                        <section class="mb-4">
                            <h2 class="h4 mb-3">8. Contact</h2>
                            <p>If you have any questions about these terms, please contact us at: <a href="mailto:copesocute@gmail.com" class="text-decoration-none">copesocute@gmail.com</a>.</p>
                        </section>

                        <section class="mb-4">
                            <p class="text-muted">These terms are effective as of: 2025-05-10</p>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.terms-content {
    font-size: 1.1rem;
    line-height: 1.6;
}

.terms-content h2 {
    color: #2c3e50;
    font-weight: 600;
}

.terms-content p {
    color: #34495e;
}

.terms-content a {
    color: #3498db;
}

.terms-content a:hover {
    color: #2980b9;
}

.card {
    border: none;
    border-radius: 15px;
}

.shadow-sm {
    box-shadow: 0 .125rem .25rem rgba(0,0,0,.075)!important;
}
</style>

<?php
// Include footer
require_once '../includes/layouts/footer.php';
?>
