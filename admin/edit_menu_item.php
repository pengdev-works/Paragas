<?php
require_once '../config/db.php';
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header("Location: ../auth/login_admin.php");
    exit();
}

// Get item ID from URL
if (!isset($_GET['id'])) {
    header("Location: manage_menu.php");
    exit();
}

$item_id = intval($_GET['id']);

// Get categories for dropdown
$categories = $conn->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY name");

// Get current item data
$item_query = $conn->prepare("SELECT * FROM menu_items WHERE item_id = ?");
$item_query->bind_param("i", $item_id);
$item_query->execute();
$item_result = $item_query->get_result();
$item = $item_result->fetch_assoc();
$item_query->close();

if (!$item) {
    header("Location: manage_menu.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize_input($_POST['name']);
    $category_id = intval($_POST['category_id']);
    $description = sanitize_input($_POST['description']);
    $base_price = floatval($_POST['base_price']);
    $is_caffeinated = isset($_POST['is_caffeinated']) ? 1 : 0;
    $allergens = sanitize_input($_POST['allergens']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $is_best_seller = isset($_POST['is_best_seller']) ? 1 : 0;
    $is_new_arrival = isset($_POST['is_new_arrival']) ? 1 : 0;
    $display_order = intval($_POST['display_order']);
    
    $errors = [];

    // Handle image upload
    $image_url = $item['image_url']; // default to current image
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['image_file']['tmp_name'];
        $fileName = $_FILES['image_file']['name'];
        $fileSize = $_FILES['image_file']['size'];
        $fileType = $_FILES['image_file']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($fileExtension, $allowedExtensions)) {
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            $uploadFileDir = '../public/images/menu/';
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }
            $dest_path = $uploadFileDir . $newFileName;
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $image_url = 'public/images/menu/' . $newFileName;
            } else {
                $errors[] = "Error uploading image.";
            }
        } else {
            $errors[] = "Invalid image type. Allowed: jpg, jpeg, png, gif.";
        }
    }
    
    if (empty($name) || empty($base_price)) {
        $errors[] = "Name and price are required.";
    }
    
    if ($base_price < 0) {
        $errors[] = "Price cannot be negative.";
    }
    
    if (empty($errors)) {
        $stmt = $conn->prepare("
            UPDATE menu_items 
            SET category_id = ?, name = ?, description = ?, base_price = ?, image_url = ?, 
                is_caffeinated = ?, allergens = ?, is_active = ?, is_best_seller = ?, 
                is_new_arrival = ?, display_order = ? 
            WHERE item_id = ?
        ");
        $stmt->bind_param("issdsisiiiii", $category_id, $name, $description, $base_price, $image_url, 
                         $is_caffeinated, $allergens, $is_active, $is_best_seller, $is_new_arrival, $display_order, $item_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Menu item updated successfully!";
            header("Location: manage_menu.php");
            exit();
        } else {
            $errors[] = "Error updating menu item. Please try again.";
        }
        $stmt->close();
    }
    
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Menu Item - Milk Tea Shop</title>
    <link href="../public/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../public/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include '../partials/header.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 bg-dark sidebar">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link text-white" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white active" href="manage_menu.php">
                                <i class="fas fa-utensils me-2"></i>
                                Manage Menu
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="view_orders.php">
                                <i class="fas fa-shopping-cart me-2"></i>
                                View Orders
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="../auth/logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>
                                Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Main content -->
            <div class="col-md-9 col-lg-10 ms-sm-auto px-4 py-4">
                <h2 class="mb-4">Edit Menu Item</h2>
                
                <?php if (isset($_SESSION['errors'])): ?>
                    <div class="alert alert-danger">
                        <?php 
                        foreach ($_SESSION['errors'] as $error) {
                            echo "<p>$error</p>";
                        }
                        unset($_SESSION['errors']);
                        ?>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="name">Item Name *</label>
                                        <input type="text" class="form-control" id="name" name="name" 
                                               value="<?php echo htmlspecialchars($item['name']); ?>" required>
                                    </div>
                                    
                                    <div class="form-group mb-3">
                                        <label for="category_id">Category</label>
                                        <select class="form-control" id="category_id" name="category_id">
                                            <option value="">Select Category</option>
                                            <?php while ($category = $categories->fetch_assoc()): ?>
                                                <option value="<?php echo $category['category_id']; ?>" 
                                                    <?php echo $category['category_id'] == $item['category_id'] ? 'selected' : ''; ?> >
                                                    <?php echo htmlspecialchars($category['name']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group mb-3">
                                        <label for="base_price">Price *</label>
                                        <input type="number" class="form-control" id="base_price" name="base_price" 
                                               step="0.01" min="0" value="<?php echo $item['base_price']; ?>" required>
                                    </div>
                                    
                                    <div class="form-group mb-3">
                                        <label>Current Image</label><br>
                                        <?php if (!empty($item['image_url'])): ?>
                                            <img src="../<?php echo $item['image_url']; ?>" alt="Current Image" style="width:100px; height:100px; object-fit:cover;">
                                        <?php else: ?>
                                            <span>No image uploaded.</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="form-group mb-3">
                                        <label for="image_file">Upload New Image</label>
                                        <input type="file" class="form-control" id="image_file" name="image_file" accept="image/*">
                                    </div>
                                </div>

                                <!-- Other fields -->
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="description">Description</label>
                                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($item['description']); ?></textarea>
                                    </div>
                                    
                                    <div class="form-group mb-3">
                                        <label for="allergens">Allergens</label>
                                        <input type="text" class="form-control" id="allergens" name="allergens" 
                                               value="<?php echo htmlspecialchars($item['allergens'] ?? ''); ?>" 
                                               placeholder="Milk, Nuts, etc.">
                                    </div>
                                    
                                    <div class="form-group mb-3">
                                        <label for="display_order">Display Order</label>
                                        <input type="number" class="form-control" id="display_order" name="display_order" 
                                               value="<?php echo $item['display_order']; ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check mb-3">
                                        <input type="checkbox" class="form-check-input" id="is_caffeinated" name="is_caffeinated" value="1" 
                                            <?php echo $item['is_caffeinated'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="is_caffeinated">Contains Caffeine</label>
                                    </div>
                                    
                                    <div class="form-check mb-3">
                                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" 
                                            <?php echo $item['is_active'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="is_active">Active</label>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-check mb-3">
                                        <input type="checkbox" class="form-check-input" id="is_best_seller" name="is_best_seller" value="1" 
                                            <?php echo $item['is_best_seller'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="is_best_seller">Best Seller</label>
                                    </div>
                                    
                                    <div class="form-check mb-3">
                                        <input type="checkbox" class="form-check-input" id="is_new_arrival" name="is_new_arrival" value="1" 
                                            <?php echo $item['is_new_arrival'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="is_new_arrival">New Arrival</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="manage_menu.php" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">Update Item</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../public/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
