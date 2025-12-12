<?php
$loggedin = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
$admin_loggedin = isset($_SESSION['admin_loggedin']) && $_SESSION['admin_loggedin'] === true;
$first_name = $loggedin ? $_SESSION['first_name'] : '';
$admin_username = $admin_loggedin ? $_SESSION['admin_username'] : '';
?>
<nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, var(--coffee) 0%, var(--caramel) 100%);">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center" href="/milktea/index.php">
            <svg width="40" height="40" viewBox="0 0 200 200" class="me-2">
                <defs>
                    <linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" style="stop-color:#6F4E37;stop-opacity:1" />
                        <stop offset="100%" style="stop-color:#A67C52;stop-opacity:1" />
                    </linearGradient>
                </defs>
                <circle cx="100" cy="100" r="90" fill="url(#gradient)" />
                <path d="M70,70 Q100,40 130,70 Q160,100 130,130 Q100,160 70,130 Q40,100 70,70" fill="none" stroke="#F7F0E0" stroke-width="8" />
                <circle cx="100" cy="100" r="30" fill="#3C2A1E" />
                <circle cx="100" cy="70" r="5" fill="#F7F0E0" />
                <circle cx="120" cy="90" r="5" fill="#F7F0E0" />
                <circle cx="90" cy="110" r="5" fill="#F7F0E0" />
                <circle cx="110" cy="125" r="5" fill="#F7F0E0" />
            </svg>
            Brew & Bubble
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="/milktea/index.php">
                        <i class="fas fa-home me-1"></i> Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/milktea/index.php#menu">
                        <i class="fas fa-coffee me-1"></i> Menu
                    </a>
                </li>
                <?php if ($loggedin): ?>
                <li class="nav-item">
                    <a class="nav-link" href="/milktea/orders/order_history.php">
                        <i class="fas fa-history me-1"></i> My Orders
                    </a>
                </li>
                <?php endif; ?>
                <?php if ($admin_loggedin): ?>
                <li class="nav-item">
                    <a class="nav-link" href="/milktea/admin/dashboard.php">
                        <i class="fas fa-chart-line me-1"></i> Admin Dashboard
                    </a>
                </li>
                <?php endif; ?>
            </ul>
            
            <ul class="navbar-nav">
                <?php if ($loggedin): ?>
                <li class="nav-item me-2">
                    <a class="nav-link position-relative" href="/milktea/cart/view_cart.php">
                        <i class="fas fa-shopping-cart fs-5"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill" style="background-color: var(--boba);" id="cart-count">0</span>
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <div class="rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; background-color: var(--milk-tea); color: var(--dark-chocolate);">
                            <i class="fas fa-user"></i>
                        </div>
                        <?php echo htmlspecialchars($first_name); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="/milktea/profile.php"><i class="fas fa-user me-2"></i> Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="/milktea/auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                    </ul>
                </li>
                <?php elseif ($admin_loggedin): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
                        <div class="rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; background-color: var(--milk-tea); color: var(--dark-chocolate);">
                            <i class="fas fa-crown"></i>
                        </div>
                        Admin: <?php echo htmlspecialchars($admin_username); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="/milktea/admin/dashboard.php"><i class="fas fa-chart-line me-2"></i> Dashboard</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="/milktea/auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                    </ul>
                </li>
                <?php else: ?>
                <li class="nav-item">
                    <a class="nav-link" href="/milktea/auth/login.php">
                        <i class="fas fa-sign-in-alt me-1"></i> Login
                    </a>
                </li>
                <li class="nav-item">
                    <a class="btn btn-outline-light ms-2" href="/milktea/auth/register.php">
                        <i class="fas fa-user-plus me-1"></i> Register
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<?php if ($loggedin): ?>
<script>
// Function to update cart count
function updateCartCount() {
    fetch('/milktea/cart/get_cart_count.php')
        .then(response => response.json())
        .then(data => {
            document.getElementById('cart-count').textContent = data.count;
        });
}

// Update cart count on page load
document.addEventListener('DOMContentLoaded', updateCartCount);
</script>
<?php endif; ?>