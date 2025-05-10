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
                    <h1 class="text-center mb-4">Privacy Policy</h1>
                    
                    <div class="privacy-content">
                        <section class="mb-4">
                            <h2 class="h4 mb-3">Overview</h2>
                            <p>This privacy policy applies to both the CopeCute website and the CopeCute Chat Bot application (collectively referred to as "Services") that were created by CopeCute (hereby referred to as "Service Provider") as a free service. These services are intended for use "AS IS".</p>
                        </section>

                        <section class="mb-4">
                            <h2 class="h4 mb-3">Information Collection and Use</h2>
                            <p>Our Services collect information when you visit our website or download and use our application. This information may include:</p>
                            <ul class="list-unstyled ps-4">
                                <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i>Your device's Internet Protocol address (e.g. IP address)</li>
                                <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i>The pages of our Services that you visit, the time and date of your visit, the time spent on those pages</li>
                                <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i>The time spent on our Services</li>
                                <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i>The operating system and browser you use</li>
                                <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i>Information you provide when creating an account or using our chat features</li>
                            </ul>
                            <p class="mt-3">Our Services do not gather precise information about the location of your device unless explicitly permitted by you.</p>
                        </section>

                        <section class="mb-4">
                            <h2 class="h4 mb-3">Third Party Access</h2>
                            <p>Only aggregated, anonymized data is periodically transmitted to external services to aid the Service Provider in improving our Services.</p>
                            <div class="mt-3">
                                <p>Please note that our Services utilize third-party services that have their own Privacy Policy about handling data:</p>
                                <ul class="list-unstyled ps-4">
                                    <li class="mb-2"><a href="https://www.google.com/policies/privacy/" target="_blank" rel="noopener noreferrer" class="text-decoration-none">Google Play Services</a></li>
                                    <li class="mb-2"><a href="https://policies.google.com/analytics" target="_blank" rel="noopener noreferrer" class="text-decoration-none">Google Analytics</a></li>
                                </ul>
                            </div>
                        </section>

                        <section class="mb-4">
                            <h2 class="h4 mb-3">Data Retention Policy</h2>
                            <p>The Service Provider will retain User Provided data for as long as you use our Services and for a reasonable time thereafter. If you'd like us to delete User Provided Data that you have provided, please contact us at <a href="mailto:copesocute@gmail.com" class="text-decoration-none">copesocute@gmail.com</a> and we will respond in a reasonable time.</p>
                        </section>

                        <section class="mb-4">
                            <h2 class="h4 mb-3">Children's Privacy</h2>
                            <p>The Service Provider does not use our Services to knowingly solicit data from or market to children under the age of 13.</p>
                            <p>The Services do not address anyone under the age of 13. The Service Provider does not knowingly collect personally identifiable information from children under 13 years of age.</p>
                        </section>

                        <section class="mb-4">
                            <h2 class="h4 mb-3">Security</h2>
                            <p>The Service Provider is concerned about safeguarding the confidentiality of your information. We provide physical, electronic, and procedural safeguards to protect information we process and maintain.</p>
                        </section>

                        <section class="mb-4">
                            <h2 class="h4 mb-3">Changes to This Privacy Policy</h2>
                            <p>This Privacy Policy may be updated from time to time for any reason. The Service Provider will notify you of any changes to the Privacy Policy by updating this page with the new Privacy Policy.</p>
                            <p class="mt-3">This privacy policy is effective as of 2025-05-10</p>
                        </section>

                        <section class="mb-4">
                            <h2 class="h4 mb-3">Contact Us</h2>
                            <p>If you have any questions regarding privacy while using our Services, or have questions about our practices, please contact the Service Provider via email at <a href="mailto:copesocute@gmail.com" class="text-decoration-none">copesocute@gmail.com</a>.</p>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.privacy-content {
    font-size: 1.1rem;
    line-height: 1.6;
}

.privacy-content h2 {
    color: #2c3e50;
    font-weight: 600;
}

.privacy-content p {
    color: #34495e;
}

.privacy-content a {
    color: #3498db;
}

.privacy-content a:hover {
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
