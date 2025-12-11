// DevTech Systems - Main JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Menu Toggle
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    const mobileMenu = document.getElementById('mobileMenu');
    
    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }
    
    if (mobileMenu) {
        mobileMenu.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }
    
    // Close sidebar when clicking outside
    document.addEventListener('click', function(e) {
        if (sidebar && sidebar.classList.contains('active')) {
            if (!sidebar.contains(e.target) && !menuToggle.contains(e.target) && !mobileMenu.contains(e.target)) {
                sidebar.classList.remove('active');
            }
        }
    });
    
    // Dropdown functionality
    const dropdowns = document.querySelectorAll('.dropdown');
    
    dropdowns.forEach(dropdown => {
        const btn = dropdown.querySelector('.nav-btn, .user-btn');
        
        if (btn) {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                
                // Close other dropdowns
                dropdowns.forEach(d => {
                    if (d !== dropdown) {
                        d.classList.remove('active');
                    }
                });
                
                dropdown.classList.toggle('active');
            });
        }
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function() {
        dropdowns.forEach(dropdown => {
            dropdown.classList.remove('active');
        });
    });
    
    // Global search functionality
    const globalSearch = document.getElementById('globalSearch');
    
    if (globalSearch) {
        let searchTimeout;
        
        globalSearch.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            
            if (query.length >= 2) {
                searchTimeout = setTimeout(() => {
                    searchProducts(query);
                }, 300);
            }
        });
    }
    
    // Form validation
    const forms = document.querySelectorAll('form[data-validate]');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(form)) {
                e.preventDefault();
            }
        });
    });
    
    // Auto-hide alerts
    const alerts = document.querySelectorAll('.alert');
    
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 300);
        }, 5000);
    });
    
    // Confirm delete actions
    const deleteButtons = document.querySelectorAll('[data-confirm]');
    
    deleteButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            const message = this.dataset.confirm || 'Are you sure you want to delete this item?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
    
    // Number formatting
    const currencyInputs = document.querySelectorAll('[data-currency]');
    
    currencyInputs.forEach(input => {
        input.addEventListener('blur', function() {
            const value = parseFloat(this.value);
            if (!isNaN(value)) {
                this.value = value.toFixed(2);
            }
        });
    });
    
    // Mark notifications as read
    const notificationItems = document.querySelectorAll('.notification-item');
    
    notificationItems.forEach(item => {
        item.addEventListener('click', function() {
            const notifId = this.dataset.notificationId;
            if (notifId) {
                markNotificationAsRead(notifId);
            }
        });
    });
});

// Search products function
function searchProducts(query) {
    fetch(`${window.location.origin}/devtech-systems/api/search.php?q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            displaySearchResults(data);
        })
        .catch(error => {
            console.error('Search error:', error);
        });
}

// Display search results
function displaySearchResults(results) {
    // Create or get results container
    let resultsContainer = document.getElementById('searchResults');
    
    if (!resultsContainer) {
        resultsContainer = document.createElement('div');
        resultsContainer.id = 'searchResults';
        resultsContainer.className = 'search-results';
        document.querySelector('.nav-search').appendChild(resultsContainer);
    }
    
    if (results.length === 0) {
        resultsContainer.innerHTML = '<div class="search-result-item">No products found</div>';
    } else {
        resultsContainer.innerHTML = results.map(product => `
            <a href="${window.location.origin}/devtech-systems/inventory/edit-product.php?id=${product.product_id}" class="search-result-item">
                <div>
                    <strong>${product.product_name}</strong>
                    <small>${product.product_code}</small>
                </div>
                <span>Stock: ${product.quantity}</span>
            </a>
        `).join('');
    }
    
    resultsContainer.style.display = 'block';
}

// Form validation
function validateForm(form) {
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            isValid = false;
            field.classList.add('error');
            showFieldError(field, 'This field is required');
        } else {
            field.classList.remove('error');
            removeFieldError(field);
        }
    });
    
    // Email validation
    const emailFields = form.querySelectorAll('[type="email"]');
    
    emailFields.forEach(field => {
        if (field.value && !isValidEmail(field.value)) {
            isValid = false;
            field.classList.add('error');
            showFieldError(field, 'Please enter a valid email');
        }
    });
    
    return isValid;
}

// Show field error
function showFieldError(field, message) {
    removeFieldError(field);
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.textContent = message;
    errorDiv.style.color = 'var(--danger)';
    errorDiv.style.fontSize = '12px';
    errorDiv.style.marginTop = '4px';
    
    field.parentNode.appendChild(errorDiv);
}

// Remove field error
function removeFieldError(field) {
    const existingError = field.parentNode.querySelector('.field-error');
    if (existingError) {
        existingError.remove();
    }
}

// Validate email
function isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Mark notification as read
function markNotificationAsRead(notificationId) {
    fetch(`${window.location.origin}/devtech-systems/api/mark-notification-read.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ notification_id: notificationId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update badge count
            const badge = document.querySelector('.nav-btn .badge');
            if (badge) {
                const currentCount = parseInt(badge.textContent);
                if (currentCount > 1) {
                    badge.textContent = currentCount - 1;
                } else {
                    badge.remove();
                }
            }
        }
    })
    .catch(error => {
        console.error('Error marking notification as read:', error);
    });
}

// Print function
function printReceipt(elementId) {
    const printContent = document.getElementById(elementId);
    const originalContent = document.body.innerHTML;
    
    document.body.innerHTML = printContent.innerHTML;
    window.print();
    document.body.innerHTML = originalContent;
    location.reload();
}

// Export to Excel (basic CSV export)
function exportToExcel(tableId, filename) {
    const table = document.getElementById(tableId);
    let csv = [];
    
    // Get headers
    const headers = Array.from(table.querySelectorAll('thead th')).map(th => th.textContent);
    csv.push(headers.join(','));
    
    // Get rows
    const rows = table.querySelectorAll('tbody tr');
    rows.forEach(row => {
        const cells = Array.from(row.querySelectorAll('td')).map(td => {
            let text = td.textContent.trim();
            // Escape quotes and wrap in quotes if contains comma
            if (text.includes(',') || text.includes('"')) {
                text = '"' + text.replace(/"/g, '""') + '"';
            }
            return text;
        });
        csv.push(cells.join(','));
    });
    
    // Download
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename + '.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}

// Calculate sale total
function calculateSaleTotal() {
    const quantity = parseFloat(document.getElementById('quantity').value) || 0;
    const unitPrice = parseFloat(document.getElementById('unit_price').value) || 0;
    const total = quantity * unitPrice;
    
    const totalField = document.getElementById('total_amount');
    if (totalField) {
        totalField.value = total.toFixed(2);
    }
}

// Add sale item to cart (for multi-item sales)
function addToCart(productId, productName, quantity, price) {
    let cart = JSON.parse(localStorage.getItem('saleCart') || '[]');
    
    const existingItem = cart.find(item => item.productId === productId);
    
    if (existingItem) {
        existingItem.quantity += quantity;
    } else {
        cart.push({
            productId,
            productName,
            quantity,
            price,
            total: quantity * price
        });
    }
    
    localStorage.setItem('saleCart', JSON.stringify(cart));
    updateCartDisplay();
}

// Update cart display
function updateCartDisplay() {
    const cart = JSON.parse(localStorage.getItem('saleCart') || '[]');
    const cartContainer = document.getElementById('cartItems');
    
    if (!cartContainer) return;
    
    if (cart.length === 0) {
        cartContainer.innerHTML = '<tr><td colspan="5" class="text-center">Cart is empty</td></tr>';
        return;
    }
    
    let total = 0;
    
    cartContainer.innerHTML = cart.map((item, index) => {
        total += item.total;
        return `
            <tr>
                <td>${item.productName}</td>
                <td>${item.quantity}</td>
                <td>${item.price.toFixed(2)}</td>
                <td>${item.total.toFixed(2)}</td>
                <td>
                    <button onclick="removeFromCart(${index})" class="btn btn-danger btn-sm">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    }).join('');
    
    document.getElementById('cartTotal').textContent = total.toFixed(2);
}

// Remove from cart
function removeFromCart(index) {
    let cart = JSON.parse(localStorage.getItem('saleCart') || '[]');
    cart.splice(index, 1);
    localStorage.setItem('saleCart', JSON.stringify(cart));
    updateCartDisplay();
}

// Clear cart
function clearCart() {
    localStorage.removeItem('saleCart');
    updateCartDisplay();
}