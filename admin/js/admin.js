/**
 * Admin Panel JavaScript
 * Hỗ trợ responsive design và mobile interactions
 */

class AdminPanel {
    constructor() {
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupResponsiveHandlers();
        this.setupDropdowns();
        this.setupModals();
        this.setupTooltips();
        this.setupTableResponsive();
        this.setupFormValidation();
    }

    setupEventListeners() {
        // Sidebar toggle
        const sidebarToggle = document.getElementById('sidebarToggle');
        const adminWrapper = document.querySelector('.admin-wrapper');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        
        if (sidebarToggle && adminWrapper) {
            sidebarToggle.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                adminWrapper.classList.toggle('sidebar-open');
            });
        }
        
        // Close sidebar when clicking overlay
        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', () => {
                adminWrapper.classList.remove('sidebar-open');
            });
        }
        
        // Close sidebar on mobile when clicking menu items
        const sidebarMenuLinks = document.querySelectorAll('.sidebar-menu a');
        sidebarMenuLinks.forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 991) {
                    adminWrapper.classList.remove('sidebar-open');
                }
            });
        });
    }

    setupResponsiveHandlers() {
        // Handle window resize
        window.addEventListener('resize', () => {
            const adminWrapper = document.querySelector('.admin-wrapper');
            if (window.innerWidth > 991) {
                adminWrapper.classList.remove('sidebar-open');
            }
            
            // Update table responsive
            this.updateTableResponsive();
        });
        
        // Initial check
        this.updateTableResponsive();
    }

    setupDropdowns() {
        // User menu dropdown
        const userMenuBtn = document.getElementById('userMenuBtn');
        const userDropdown = document.getElementById('userDropdown');
        
        if (userMenuBtn && userDropdown) {
            userMenuBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                userDropdown.classList.toggle('show');
                
                // Close other dropdowns
                const otherDropdowns = document.querySelectorAll('.notification-dropdown');
                otherDropdowns.forEach(dropdown => {
                    dropdown.classList.remove('show');
                });
            });
        }
        
        // Notification dropdown
        const notificationBtn = document.getElementById('notificationBtn');
        const notificationDropdown = document.querySelector('.notification-dropdown');
        
        if (notificationBtn && notificationDropdown) {
            notificationBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                notificationDropdown.classList.toggle('show');
                
                // Close other dropdowns
                if (userDropdown) {
                    userDropdown.classList.remove('show');
                }
            });
        }
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', (e) => {
            if (userDropdown && !userDropdown.contains(e.target) && !userMenuBtn.contains(e.target)) {
                userDropdown.classList.remove('show');
            }
            if (notificationDropdown && !notificationDropdown.contains(e.target) && !notificationBtn.contains(e.target)) {
                notificationDropdown.classList.remove('show');
            }
        });
    }

    setupModals() {
        // Modal handling
        const modals = document.querySelectorAll('.modal');
        const modalTriggers = document.querySelectorAll('[data-modal-target]');
        const modalCloses = document.querySelectorAll('.modal-close, .close');
        
        modalTriggers.forEach(trigger => {
            trigger.addEventListener('click', (e) => {
                e.preventDefault();
                const targetModal = document.querySelector(trigger.dataset.modalTarget);
                if (targetModal) {
                    this.openModal(targetModal);
                }
            });
        });
        
        modalCloses.forEach(close => {
            close.addEventListener('click', () => {
                const modal = close.closest('.modal');
                if (modal) {
                    this.closeModal(modal);
                }
            });
        });
        
        // Close modal when clicking outside
        modals.forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.closeModal(modal);
                }
            });
        });
        
        // Close modal with Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const openModal = document.querySelector('.modal.show');
                if (openModal) {
                    this.closeModal(openModal);
                }
            }
        });
    }

    openModal(modal) {
        modal.style.display = 'block';
        setTimeout(() => {
            modal.classList.add('show');
        }, 10);
        document.body.classList.add('modal-open');
    }

    closeModal(modal) {
        modal.classList.remove('show');
        setTimeout(() => {
            modal.style.display = 'none';
        }, 300);
        document.body.classList.remove('modal-open');
    }

    setupTooltips() {
        // Simple tooltip implementation
        const tooltipTriggers = document.querySelectorAll('[data-tooltip]');
        
        tooltipTriggers.forEach(trigger => {
            trigger.addEventListener('mouseenter', (e) => {
                const tooltipText = trigger.dataset.tooltip;
                const tooltip = document.createElement('div');
                tooltip.className = 'tooltip';
                tooltip.textContent = tooltipText;
                document.body.appendChild(tooltip);
                
                const rect = trigger.getBoundingClientRect();
                tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
                tooltip.style.top = rect.top - tooltip.offsetHeight - 10 + 'px';
                
                setTimeout(() => {
                    tooltip.classList.add('show');
                }, 10);
            });
            
            trigger.addEventListener('mouseleave', () => {
                const tooltip = document.querySelector('.tooltip');
                if (tooltip) {
                    tooltip.classList.remove('show');
                    setTimeout(() => {
                        tooltip.remove();
                    }, 300);
                }
            });
        });
    }

    setupTableResponsive() {
        const tables = document.querySelectorAll('.table');
        
        tables.forEach(table => {
            if (!table.parentElement.classList.contains('table-responsive')) {
                const wrapper = document.createElement('div');
                wrapper.className = 'table-responsive';
                table.parentNode.insertBefore(wrapper, table);
                wrapper.appendChild(table);
            }
        });
    }

    updateTableResponsive() {
        const tableResponsive = document.querySelectorAll('.table-responsive');
        
        tableResponsive.forEach(wrapper => {
            const table = wrapper.querySelector('table');
            if (table) {
                if (window.innerWidth <= 768) {
                    // Add mobile-friendly classes
                    table.classList.add('table-mobile');
                } else {
                    table.classList.remove('table-mobile');
                }
            }
        });
    }

    setupFormValidation() {
        // Basic form validation
        const forms = document.querySelectorAll('.form-validate');
        
        forms.forEach(form => {
            form.addEventListener('submit', (e) => {
                if (!this.validateForm(form)) {
                    e.preventDefault();
                }
            });
        });
    }

    validateForm(form) {
        let isValid = true;
        const requiredFields = form.querySelectorAll('[required]');
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                this.showFieldError(field, 'Trường này là bắt buộc');
                isValid = false;
            } else {
                this.clearFieldError(field);
            }
        });
        
        return isValid;
    }

    showFieldError(field, message) {
        field.classList.add('error');
        
        let errorDiv = field.nextElementSibling;
        if (!errorDiv || !errorDiv.classList.contains('field-error')) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'field-error';
            field.parentNode.insertBefore(errorDiv, field.nextSibling);
        }
        
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';
    }

    clearFieldError(field) {
        field.classList.remove('error');
        
        const errorDiv = field.nextElementSibling;
        if (errorDiv && errorDiv.classList.contains('field-error')) {
            errorDiv.style.display = 'none';
        }
    }

    // Utility methods
    showAlert(message, type = 'info') {
        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.innerHTML = `
            <i class="fas fa-info-circle"></i>
            <span>${message}</span>
            <button type="button" class="alert-close">&times;</button>
        `;
        
        document.body.appendChild(alert);
        
        setTimeout(() => {
            alert.classList.add('show');
        }, 10);
        
        // Auto hide after 5 seconds
        setTimeout(() => {
            this.hideAlert(alert);
        }, 5000);
        
        // Close button
        const closeBtn = alert.querySelector('.alert-close');
        closeBtn.addEventListener('click', () => {
            this.hideAlert(alert);
        });
    }

    hideAlert(alert) {
        alert.classList.remove('show');
        setTimeout(() => {
            alert.remove();
        }, 300);
    }

    showLoading(element) {
        element.classList.add('loading');
        element.disabled = true;
        
        const spinner = document.createElement('div');
        spinner.className = 'loading-spinner';
        element.appendChild(spinner);
    }

    hideLoading(element) {
        element.classList.remove('loading');
        element.disabled = false;
        
        const spinner = element.querySelector('.loading-spinner');
        if (spinner) {
            spinner.remove();
        }
    }

    // AJAX helper
    async request(url, options = {}) {
        try {
            const response = await fetch(url, {
                headers: {
                    'Content-Type': 'application/json',
                    ...options.headers
                },
                ...options
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return await response.json();
        } catch (error) {
            console.error('Request failed:', error);
            this.showAlert('Đã xảy ra lỗi khi thực hiện yêu cầu', 'error');
            throw error;
        }
    }
}

// Initialize AdminPanel when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.adminPanel = new AdminPanel();
});

// Additional CSS for responsive enhancements
const additionalCSS = `
    /* Tooltip styles */
    .tooltip {
        position: absolute;
        background: rgba(0, 0, 0, 0.8);
        color: white;
        padding: 5px 10px;
        border-radius: 4px;
        font-size: 12px;
        opacity: 0;
        transform: translateY(5px);
        transition: all 0.3s ease;
        z-index: 1000;
        pointer-events: none;
    }
    
    .tooltip.show {
        opacity: 1;
        transform: translateY(0);
    }
    
    /* Alert styles */
    .alert {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 8px;
        color: white;
        font-size: 14px;
        opacity: 0;
        transform: translateX(100%);
        transition: all 0.3s ease;
        z-index: 1000;
        min-width: 300px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }
    
    .alert.show {
        opacity: 1;
        transform: translateX(0);
    }
    
    .alert-info {
        background: #3498db;
    }
    
    .alert-success {
        background: #27ae60;
    }
    
    .alert-warning {
        background: #f39c12;
    }
    
    .alert-error {
        background: #e74c3c;
    }
    
    .alert-close {
        background: none;
        border: none;
        color: white;
        font-size: 18px;
        float: right;
        cursor: pointer;
        margin-left: 10px;
    }
    
    /* Form validation styles */
    .form-control.error {
        border-color: #e74c3c;
        box-shadow: 0 0 0 2px rgba(231, 76, 60, 0.2);
    }
    
    .field-error {
        color: #e74c3c;
        font-size: 12px;
        margin-top: 5px;
        display: none;
    }
    
    /* Mobile table styles */
    @media (max-width: 768px) {
        .table-mobile {
            font-size: 12px;
        }
        
        .table-mobile th,
        .table-mobile td {
            padding: 8px 6px;
        }
        
        .table-mobile .btn {
            padding: 4px 8px;
            font-size: 11px;
        }
    }
    
    /* Modal responsive */
    @media (max-width: 768px) {
        .modal-content {
            width: 95%;
            margin: 10% auto;
        }
    }
    
    /* Loading state */
    .loading {
        position: relative;
        pointer-events: none;
    }
    
    .loading::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.8);
        z-index: 1;
    }
    
    .loading .loading-spinner {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 2;
    }
`;

// Inject additional CSS
const style = document.createElement('style');
style.textContent = additionalCSS;
document.head.appendChild(style); 