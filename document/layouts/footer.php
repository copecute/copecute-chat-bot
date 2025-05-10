            </div>
        </div>
    </div>

    <footer class="bg-light mt-5 py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>CopeCute API</h5>
                    <p class="text-muted">Tài liệu API cho ứng dụng chat bot CopeCute</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">
                        <a href="https://copecute.minhgiang.pro" class="text-decoration-none" target="_blank">
                            <i class="fas fa-globe me-1"></i> Website
                        </a>
                        <span class="mx-2">|</span>
                        <a href="mailto:copesocute@gmail.com" class="text-decoration-none">
                            <i class="fas fa-envelope me-1"></i> Liên hệ
                        </a>
                    </p>
                    <p class="text-muted mt-2 mb-0">
                        &copy; <?php echo date('Y'); ?> CopeCute. All rights reserved.
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Highlight.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/highlight.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', (event) => {
            document.querySelectorAll('pre code').forEach((block) => {
                hljs.highlightBlock(block);
            });
        });
    </script>
</body>
</html>
