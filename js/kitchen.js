let refreshCountdown = 30;

// Load orders
function loadOrders() {
    const xhr = new XMLHttpRequest();
    xhr.open('GET', 'ajax/get_kitchen_orders.php', true);
    xhr.onload = function() {
        if (this.status === 200) {
            document.getElementById('ordersContainer').innerHTML = this.responseText;
            resetCountdown();
            
            // Add event listeners to action buttons
            document.querySelectorAll('.start-preparing-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const itemId = this.getAttribute('data-item-id');
                    updateOrderItem(itemId, 'preparing');
                });
            });
            
            document.querySelectorAll('.mark-ready-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const itemId = this.getAttribute('data-item-id');
                    updateOrderItem(itemId, 'ready');
                });
            });
        }
    };
    xhr.send();
}

// Update order item status
function updateOrderItem(itemId, status) {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'ajax/update_order_item.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        if (this.status === 200) {
            loadOrders();
        }
    };
    xhr.send('item_id=' + itemId + '&status=' + status);
}

// Reset countdown timer
function resetCountdown() {
    refreshCountdown = 30;
}

// Auto refresh countdown
setInterval(function() {
    refreshCountdown--;
    document.getElementById('countdown').textContent = refreshCountdown;
    
    if (refreshCountdown <= 0) {
        loadOrders();
    }
}, 1000);

// Initial load
loadOrders();