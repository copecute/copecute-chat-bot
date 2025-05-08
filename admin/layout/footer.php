        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle sidebar
        const toggleSidebarBtn = document.getElementById('toggleSidebar');
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('main-content');
        
        if (toggleSidebarBtn) {
            toggleSidebarBtn.addEventListener('click', function() {
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('expanded');
                
                // Lưu trạng thái sidebar vào localStorage
                if (sidebar.classList.contains('collapsed')) {
                    localStorage.setItem('sidebar-collapsed', 'true');
                } else {
                    localStorage.setItem('sidebar-collapsed', 'false');
                }
            });
        }
        
        // Kiểm tra trạng thái sidebar từ localStorage
        if (localStorage.getItem('sidebar-collapsed') === 'true') {
            sidebar.classList.add('collapsed');
            mainContent.classList.add('expanded');
        }
        
        // Mobile sidebar toggle
        const toggleMobileBtn = document.querySelector('.toggle-sidebar');
        if (toggleMobileBtn && window.innerWidth < 768) {
            toggleMobileBtn.addEventListener('click', function() {
                sidebar.classList.toggle('mobile-shown');
            });
            
            // Đóng sidebar khi click bên ngoài
            document.addEventListener('click', function(e) {
                if (window.innerWidth < 768 && 
                    !sidebar.contains(e.target) && 
                    e.target !== toggleMobileBtn &&
                    sidebar.classList.contains('mobile-shown')) {
                    sidebar.classList.remove('mobile-shown');
                }
            });
        }
        
        // Tự động ẩn thông báo sau 5 giây
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
            alerts.forEach(function(alert) {
                if (alert && alert.parentNode) {
                    alert.classList.add('fade');
                    setTimeout(() => {
                        if (alert.parentNode) {
                            alert.parentNode.removeChild(alert);
                        }
                    }, 300);
                }
            });
        }, 5000);
        
        // Dropdown menus in sidebar
        const dropdownItems = document.querySelectorAll('.nav-item.dropdown');
        dropdownItems.forEach(item => {
            const link = item.querySelector('.dropdown-toggle');
            if (link) {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const dropdown = this.nextElementSibling;
                    if (dropdown) {
                        if (window.innerWidth >= 768) {
                            if (dropdown.style.display === 'block') {
                                dropdown.style.display = 'none';
                                this.classList.remove('show');
                            } else {
                                dropdown.style.display = 'block';
                                this.classList.add('show');
                            }
                        } else {
                            dropdown.classList.toggle('show');
                            this.classList.toggle('show');
                        }
                    }
                });
            }
        });
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.dropdown')) {
                document.querySelectorAll('.dropdown-menu').forEach(dropdown => {
                    dropdown.style.display = 'none';
                });
                document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
                    toggle.classList.remove('show');
                });
            }
        });
        
        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>
</body>
</html> 