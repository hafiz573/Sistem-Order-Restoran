// Global variables
let currentCategory = 'all';

// DOM Content Loaded
document.addEventListener('DOMContentLoaded', function() {
    loadMenuItems();
    loadTables();
    updateCartDisplay();
    
    // Category tab events
    document.querySelectorAll('.tab-button').forEach(tab => {
        tab.addEventListener('click', function() {
            // Remove active class from all tabs
            document.querySelectorAll('.tab-button').forEach(t => t.classList.remove('active'));
            
            // Add active class to clicked tab
            this.classList.add('active');
            
            // Load menu items for selected category
            currentCategory = this.getAttribute('data-category');
            loadMenuItems();
        });
    });
});

// Di bagian loadMenuItems, tambahkan error handling untuk gambar
function loadMenuItems() {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'ajax/get_menu_items.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        if (this.status === 200) {
            document.getElementById('menuItems').innerHTML = this.responseText;
            
            // Add error handling for images
            document.querySelectorAll('.menu-item-image').forEach(img => {
                img.onerror = function() {
                    this.src = 'images/default.jpg';
                };
            });
            
            // Add event listeners to new buttons
            document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const itemId = this.getAttribute('data-id');
                    const itemName = this.getAttribute('data-name');
                    const itemPrice = this.getAttribute('data-price');
                    addToCart(itemId, itemName, itemPrice);
                });
            });
        }
    };
    xhr.onerror = function() {
        console.error('Error loading menu items');
        document.getElementById('menuItems').innerHTML = '<div class="error-message">Error loading menu. Please refresh the page.</div>';
    };
    xhr.send('category_id=' + currentCategory);
}

// Load available tables
function loadTables() {
    const xhr = new XMLHttpRequest();
    xhr.open('GET', 'ajax/get_tables.php', true);
    xhr.onload = function() {
        if (this.status === 200) {
            document.getElementById('tableList').innerHTML = this.responseText;
            
            // Add event listeners to table buttons
            document.querySelectorAll('.table-btn:not(.occupied)').forEach(btn => {
                btn.addEventListener('click', function() {
                    const tableNumber = this.getAttribute('data-table');
                    selectTable(tableNumber);
                });
            });
        }
    };
    xhr.send();
}

// Add item to cart
function addToCart(itemId, itemName, itemPrice) {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'ajax/add_to_cart.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        if (this.status === 200) {
            updateCartDisplay();
            showAlert('Item added to cart!', 'success');
        }
    };
    xhr.send('item_id=' + itemId + '&name=' + encodeURIComponent(itemName) + '&price=' + itemPrice);
}

// Update cart display
function updateCartDisplay() {
    const xhr = new XMLHttpRequest();
    xhr.open('GET', 'ajax/get_cart.php', true);
    xhr.onload = function() {
        if (this.status === 200) {
            const response = JSON.parse(this.responseText);
            document.getElementById('cartItems').innerHTML = response.cart_html;
            document.getElementById('cartTotal').textContent = response.total.toLocaleString();
            document.getElementById('cartCount').textContent = response.item_count;
            
            // Update table info
            if (response.table_number) {
                document.getElementById('tableInfo').innerHTML = `
                    <p><strong>Table:</strong> ${response.table_number}</p>
                    <p><strong>Customer:</strong> ${response.customer_name}</p>
                    <button class="btn-secondary" onclick="showTableModal()">Change Table</button>
                `;
                document.getElementById('placeOrderBtn').disabled = false;
            } else {
                document.getElementById('placeOrderBtn').disabled = true;
            }
            
            // Add event listeners to cart controls
            document.querySelectorAll('.quantity-minus').forEach(btn => {
                btn.addEventListener('click', function() {
                    const itemId = this.getAttribute('data-id');
                    updateQuantity(itemId, -1);
                });
            });
            
            document.querySelectorAll('.quantity-plus').forEach(btn => {
                btn.addEventListener('click', function() {
                    const itemId = this.getAttribute('data-id');
                    updateQuantity(itemId, 1);
                });
            });
            
            document.querySelectorAll('.remove-item').forEach(btn => {
                btn.addEventListener('click', function() {
                    const itemId = this.getAttribute('data-id');
                    removeItem(itemId);
                });
            });
        }
    };
    xhr.send();
}

// Update cart count badge
function updateCartCount() {
    const xhr = new XMLHttpRequest();
    xhr.open('GET', 'ajax/get_cart_count.php', true);
    xhr.onload = function() {
        if (this.status === 200) {
            document.getElementById('cartCount').textContent = this.responseText;
        }
    };
    xhr.send();
}

// Update item quantity
function updateQuantity(itemId, change) {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'ajax/update_quantity.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        if (this.status === 200) {
            updateCartDisplay();
        }
    };
    xhr.send('item_id=' + itemId + '&change=' + change);
}

// Remove item from cart
function removeItem(itemId) {
    if (confirm('Remove this item from cart?')) {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'ajax/remove_item.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (this.status === 200) {
                updateCartDisplay();
            }
        };
        xhr.send('item_id=' + itemId);
    }
}

// Toggle cart sidebar
function toggleCart() {
    const cartSidebar = document.getElementById('cartSidebar');
    const overlay = document.getElementById('overlay');
    
    cartSidebar.classList.toggle('open');
    overlay.classList.toggle('show');
}

// Show table selection modal
function showTableModal() {
    document.getElementById('tableModal').classList.add('show');
}

// Close modal
function closeModal() {
    document.getElementById('tableModal').classList.remove('show');
}

// Select table
function selectTable(tableNumber) {
    const customerName = prompt('Please enter your name:');
    if (customerName && customerName.trim() !== '') {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'ajax/select_table.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (this.status === 200) {
                updateCartDisplay();
                closeModal();
                showAlert('Table selected successfully!', 'success');
            }
        };
        xhr.send('table_number=' + tableNumber + '&customer_name=' + encodeURIComponent(customerName.trim()));
    }
}

// Place order
function placeOrder() {
    if (confirm('Are you sure you want to place this order?')) {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'ajax/place_order.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (this.status === 200) {
                const response = JSON.parse(this.responseText);
                if (response.success) {
                    showAlert('Order placed successfully! Order #' + response.order_number, 'success');
                    updateCartDisplay();
                    toggleCart();
                } else {
                    showAlert('Error: ' + response.message, 'error');
                }
            }
        };
        xhr.send();
    }
}

// Clear cart
function clearCart() {
    if (confirm('Are you sure you want to clear the cart?')) {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'ajax/clear_cart.php', true);
        xhr.onload = function() {
            if (this.status === 200) {
                updateCartDisplay();
                showAlert('Cart cleared!', 'success');
            }
        };
        xhr.send();
    }
}

// Show alert message
function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;
    
    const main = document.querySelector('.main');
    main.insertBefore(alertDiv, main.firstChild);
    
    // Remove alert after 3 seconds
    setTimeout(() => {
        alertDiv.remove();
    }, 3000);
}

function filterOrders() {
    const filter = document.getElementById('statusFilter').value;
    const rows = document.querySelectorAll('#ordersTableBody tr');
    
    rows.forEach(row => {
        if (filter === 'all') {
            row.style.display = '';
        } else {
            const status = row.getAttribute('data-status');
            row.style.display = status === filter ? '' : 'none';
        }
    });
}

function viewOrder(orderId) {
    // You can create a order_details.php page later
    alert('View order details for ID: ' + orderId);
    // window.open(`order_details.php?id=${orderId}`, '_blank');
}

function completeOrder(orderId) {
    if (confirm('Mark this order as completed and free up the table?')) {
        fetch(`ajax/update_order_status.php?id=${orderId}&status=completed`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Order completed successfully!', 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showAlert('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showAlert('Network error: ' + error, 'error');
            });
    }
}

function cancelOrder(orderId) {
    if (confirm('Are you sure you want to cancel this order? This action cannot be undone.')) {
        fetch(`ajax/update_order_status.php?id=${orderId}&status=cancelled`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Order cancelled successfully!', 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showAlert('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showAlert('Network error: ' + error, 'error');
            });
    }
}

// Utility function to show alerts
function showAlert(message, type) {
    // Remove existing alerts
    const existingAlerts = document.querySelectorAll('.alert-floating');
    existingAlerts.forEach(alert => alert.remove());
    
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert-floating alert-${type}`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="close-alert" onclick="this.parentElement.remove()">&times;</button>
    `;
    
    document.body.appendChild(alertDiv);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        if (alertDiv.parentElement) {
            alertDiv.remove();
        }
    }, 3000);
}

// Close cart when clicking overlay
document.getElementById('overlay').addEventListener('click', toggleCart);