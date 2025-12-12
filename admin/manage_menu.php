<?php
require_once '../config/db.php';
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header("Location: ../auth/login_admin.php");
    exit();
}

// Handle add/edit item submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $category_id = intval($_POST['category_id']);
    $base_price = floatval($_POST['base_price']);
    $is_best_seller = isset($_POST['is_best_seller']) ? 1 : 0;
    $is_new_arrival = isset($_POST['is_new_arrival']) ? 1 : 0;

    // Handle image upload
    $image_url = '';
    if (!empty($_FILES['image']['name'])) {
        $target_dir = "../public/images/menu/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);
        $target_file = $target_dir . basename($_FILES['image']['name']);
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            $image_url = 'public/images/menu/' . basename($_FILES['image']['name']);
        }
    }

    if (isset($_POST['item_id'])) {
        // Update existing item
        $item_id = intval($_POST['item_id']);
        if ($image_url) {
            $stmt = $conn->prepare("UPDATE menu_items SET name=?, description=?, category_id=?, base_price=?, is_best_seller=?, is_new_arrival=?, image_url=? WHERE item_id=?");
            $stmt->bind_param("ssiddisi", $name, $description, $category_id, $base_price, $is_best_seller, $is_new_arrival, $image_url, $item_id);
        } else {
            $stmt = $conn->prepare("UPDATE menu_items SET name=?, description=?, category_id=?, base_price=?, is_best_seller=?, is_new_arrival=? WHERE item_id=?");
            $stmt->bind_param("ssidiii", $name, $description, $category_id, $base_price, $is_best_seller, $is_new_arrival, $item_id);
        }
        $stmt->execute();
        $stmt->close();
        $_SESSION['success'] = "Menu item updated successfully!";
    } else {
        // Add new item
        $stmt = $conn->prepare("INSERT INTO menu_items (name, description, category_id, base_price, is_best_seller, is_new_arrival, image_url) VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param("ssiddis", $name, $description, $category_id, $base_price, $is_best_seller, $is_new_arrival, $image_url);
        $stmt->execute();
        $stmt->close();
        $_SESSION['success'] = "Menu item added successfully!";
    }
    header("Location: manage_menu.php");
    exit();
}

// Handle delete
if (isset($_GET['delete'])) {
    $item_id = intval($_GET['delete']);
    $stmt = $conn->prepare("UPDATE menu_items SET is_active=0 WHERE item_id=?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $stmt->close();
    $_SESSION['success'] = "Menu item deleted!";
    header("Location: manage_menu.php");
    exit();
}

// Handle toggle status
if (isset($_GET['toggle_status'])) {
    $item_id = intval($_GET['toggle_status']);
    $stmt = $conn->prepare("UPDATE menu_items SET is_active=NOT is_active WHERE item_id=?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $stmt->close();
    $_SESSION['success'] = "Menu item status updated!";
    header("Location: manage_menu.php");
    exit();
}

// Fetch menu items
$menu_items = $conn->query("SELECT * FROM menu_items ORDER BY category_id, display_order");

// Category map
$category_map = [1=>'Milktea',2=>'Coffee',3=>'Add Ons'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Menu - Brew & Bubble</title>
<link href="../public/bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="../public/css/style.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
/* ====== COLORS & FONTS ====== */
:root{
    --caramel:#A67C52; --milk-tea:#D4B996; --boba:#815A3B; --coffee:#6F4E37; --light-cream:#F7F0E0; --dark-chocolate:#3C2A1E;
}
body {background-color:var(--light-cream); color:var(--dark-chocolate); font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; min-height:100vh;}
.sidebar {background:linear-gradient(135deg,var(--dark-chocolate)0%,var(--coffee)100%) !important; min-height:calc(100vh - 73px); padding-top:20px;}
.sidebar .nav-link{color:rgba(255,255,255,0.9)!important; padding:15px 20px; margin:5px 10px; border-radius:10px; transition:all 0.3s;}
.sidebar .nav-link:hover,.sidebar .nav-link.active{background-color:rgba(255,255,255,0.1)!important; color:white!important; transform:translateX(5px);}
.main-content{padding:30px;}
.card{border:none;border-radius:15px;box-shadow:0 5px 15px rgba(0,0,0,0.08); margin-bottom:20px;}
.card-header{background:linear-gradient(135deg,var(--caramel)0%,var(--coffee)100%);color:white;border-radius:15px 15px 0 0;padding:20px;}
.card-body{padding:25px;}
.btn-primary{background-color:var(--boba);border-color:var(--boba); border-radius:30px;font-weight:600;padding:10px 25px;transition:all 0.3s;}
.btn-primary:hover{background-color:var(--dark-chocolate); border-color:var(--dark-chocolate); transform:translateY(-3px);}
.table{border-radius:10px; overflow:hidden; box-shadow:0 5px 15px rgba(0,0,0,0.08);}
.table thead th{background:linear-gradient(135deg,var(--caramel)0%,var(--coffee)100%); color:white; border:none; font-weight:600; padding:15px;}
.table tbody td{padding:15px; vertical-align:middle; border-color:rgba(0,0,0,0.05);}
.item-image{width:60px;height:60px;object-fit:cover;border-radius:10px;border:2px solid var(--milk-tea);}
.badge{font-size:0.75rem;padding:6px 12px;border-radius:15px;font-weight:600;}
.alert-success{background-color:rgba(25,135,84,0.1);border-color:#198754;color:#0f5132;border-radius:15px;}
.category-badge{background-color:var(--milk-tea); color:var(--dark-chocolate); padding:4px 10px; border-radius:15px; font-size:0.8rem;font-weight:600;}
</style>
</head>
<body>
<?php include '../partials/header.php'; ?>
<div class="container-fluid">
<div class="row">
    <!-- Sidebar -->
    <div class="col-md-3 col-lg-2 sidebar">
        <div class="position-sticky pt-3">
            <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_menu.php">
                                <i class="fas fa-utensils"></i>
                                Manage Menu
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_users.php">
                                <i class="fas fa-users"></i>
                                Manage Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="view_orders.php">
                                <i class="fas fa-shopping-cart"></i>
                                View Orders
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../auth/logout.php">
                                <i class="fas fa-sign-out-alt"></i>
                                Logout
                            </a>
                        </li>
                    </ul>
        </div>
    </div>

    <!-- Main content -->
    <div class="col-md-9 col-lg-10 main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-utensils me-2"></i>Manage Menu</h2>
            <!-- Add Modal Trigger -->
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus me-1"></i> Add New Item</button>
        </div>

        <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-list me-2"></i>Menu Items</h5></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Image</th><th>Name</th><th>Category</th><th>Price</th><th>Status</th><th>Features</th><th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if($menu_items->num_rows>0): ?>
                            <?php while($item=$menu_items->fetch_assoc()): ?>
                            <tr>
                                <td><img src="<?php echo $item['image_url']?'../'.$item['image_url']:'../public/images/placeholder.png'; ?>" class="item-image"></td>
                                <td><strong><?php echo htmlspecialchars($item['name']); ?></strong><br><small class="text-muted"><?php echo htmlspecialchars($item['description']); ?></small></td>
                                <td><span class="category-badge"><?php echo $category_map[$item['category_id']] ?? 'Uncategorized'; ?></span></td>
                                <td><strong>₱<?php echo number_format($item['base_price'],2); ?></strong></td>
                                <td><span class="badge <?php echo $item['is_active']?'bg-success':'bg-danger'; ?>"><?php echo $item['is_active']?'Active':'Inactive'; ?></span></td>
                                <td>
                                    <?php if($item['is_best_seller']): ?><span class="badge bg-warning me-1"><i class="fas fa-star me-1"></i>Best Seller</span><?php endif; ?>
                                    <?php if($item['is_new_arrival']): ?><span class="badge bg-success me-1"><i class="fas fa-bell me-1"></i>New</span><?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $item['item_id']; ?>"><i class="fas fa-edit"></i></button>
                                        <a href="?toggle_status=<?php echo $item['item_id']; ?>" class="btn btn-sm <?php echo $item['is_active']?'btn-outline-warning':'btn-outline-success'; ?>"><i class="fas <?php echo $item['is_active']?'fa-eye-slash':'fa-eye'; ?>"></i></a>
                                        <a href="?delete=<?php echo $item['item_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?')"><i class="fas fa-trash"></i></a>
                                    </div>
                                </td>
                            </tr>

                            <!-- Edit Modal -->
                            <div class="modal fade" id="editModal<?php echo $item['item_id']; ?>" tabindex="-1">
                              <div class="modal-dialog">
                                <div class="modal-content">
                                  <div class="modal-header" style="background:linear-gradient(135deg,var(--caramel)0%,var(--coffee)100%);color:white;">
                                    <h5 class="modal-title">Edit Item</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                  </div>
                                  <form method="POST" enctype="multipart/form-data">
                                  <div class="modal-body">
                                    <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                    <div class="mb-3"><label>Name</label><input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($item['name']); ?>" required></div>
                                    <div class="mb-3"><label>Description</label><textarea class="form-control" name="description"><?php echo htmlspecialchars($item['description']); ?></textarea></div>
                                    <div class="mb-3"><label>Category</label>
                                        <select class="form-control" name="category_id">
                                            <?php foreach($category_map as $k=>$v): ?>
                                            <option value="<?php echo $k; ?>" <?php echo $item['category_id']==$k?'selected':''; ?>><?php echo $v; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3"><label>Price</label><input type="number" step="0.01" class="form-control" name="base_price" value="<?php echo $item['base_price']; ?>" required></div>
                                    <div class="mb-3"><label>Image</label><input type="file" class="form-control" name="image"></div>
                                    <div class="form-check mb-3">
                                        <input type="checkbox" class="form-check-input" name="is_best_seller" <?php echo $item['is_best_seller']?'checked':''; ?>>
                                        <label class="form-check-label">Best Seller</label>
                                    </div>
                                    <div class="form-check mb-3">
                                        <input type="checkbox" class="form-check-input" name="is_new_arrival" <?php echo $item['is_new_arrival']?'checked':''; ?>>
                                        <label class="form-check-label">New Arrival</label>
                                    </div>
                                  </div>
                                  <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-primary">Save Changes</button>
                                  </div>
                                  </form>
                                </div>
                              </div>
                            </div>

                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center py-4"><i class="fas fa-utensils fa-3x text-muted mb-3"></i><p class="text-muted">No menu items found. Add your first menu item!</p></td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<!-- Add Item Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header" style="background:linear-gradient(135deg,var(--caramel)0%,var(--coffee)100%);color:white;">
        <h5 class="modal-title">Add New Menu Item</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" enctype="multipart/form-data">
      <div class="modal-body">
        <div class="mb-3"><label>Name</label><input type="text" class="form-control" name="name" required></div>
        <div class="mb-3"><label>Description</label><textarea class="form-control" name="description"></textarea></div>
        <div class="mb-3"><label>Category</label>
            <select class="form-control" name="category_id">
                <?php foreach($category_map as $k=>$v): ?>
                <option value="<?php echo $k; ?>"><?php echo $v; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3"><label>Price</label><input type="number" step="0.01" class="form-control" name="base_price" required></div>
        <div class="mb-3"><label>Image</label><input type="file" class="form-control" name="image"></div>
        <div class="form-check mb-3"><input type="checkbox" class="form-check-input" name="is_best_seller"><label class="form-check-label">Best Seller</label></div>
        <div class="form-check mb-3"><input type="checkbox" class="form-check-input" name="is_new_arrival"><label class="form-check-label">New Arrival</label></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary">Add Item</button>
      </div>
      </form>
    </div>
  </div>
</div>

<script src="../public/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
