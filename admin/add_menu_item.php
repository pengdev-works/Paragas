<?php
session_start();
require_once '../config/db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header("Location: ../auth/login_admin.php");
    exit();
}

// Hardcoded categories
$categories = [
    ['id' => 1, 'name' => 'Milk Tea'],
    ['id' => 2, 'name' => 'Coffee'],
    ['id' => 3, 'name' => 'Add Ons']
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $category_id = intval($_POST['category_id']);
    $description = trim($_POST['description']);
    $base_price = floatval($_POST['base_price']);
    $is_caffeinated = isset($_POST['is_caffeinated']) ? 1 : 0;
    $allergens = trim($_POST['allergens']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $is_best_seller = isset($_POST['is_best_seller']) ? 1 : 0;
    $is_new_arrival = isset($_POST['is_new_arrival']) ? 1 : 0;
    $display_order = intval($_POST['display_order']);
    
    $errors = [];

    // Image upload
    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($_FILES['image']['type'], $allowed_types)) {
            $target_dir = "../uploads/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);

            $filename = time() . '_' . basename($_FILES['image']['name']);
            $target_file = $target_dir . $filename;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image_path = 'uploads/' . $filename; // relative path to save in DB
            } else {
                $errors[] = "Failed to upload image.";
            }
        } else {
            $errors[] = "Invalid image type. Only JPG, PNG, GIF allowed.";
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
            INSERT INTO menu_items (category_id, name, description, base_price, image_url, is_caffeinated, allergens, is_active, is_best_seller, is_new_arrival, display_order) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("issdsisiiii", $category_id, $name, $description, $base_price, $image_path, $is_caffeinated, $allergens, $is_active, $is_best_seller, $is_new_arrival, $display_order);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Menu item added successfully!";
            header("Location: manage_menu.php");
            exit();
        } else {
            $errors[] = "Error adding menu item. Please try again.";
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
<title>Add Menu Item - Milk Tea Shop</title>
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
                    <li class="nav-item"><a class="nav-link text-white" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link text-white active" href="manage_menu.php">Manage Menu</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="view_orders.php">View Orders</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="../auth/logout.php">Logout</a></li>
                </ul>
            </div>
        </div>

        <!-- Main content -->
        <div class="col-md-9 col-lg-10 ms-sm-auto px-4 py-4">
            <h2 class="mb-4">Add New Menu Item</h2>

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
                                <div class="mb-3">
                                    <label for="name">Item Name *</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>

                                <div class="mb-3">
                                    <label for="category_id">Category</label>
                                    <select class="form-control" id="category_id" name="category_id">
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="base_price">Price *</label>
                                    <input type="number" class="form-control" id="base_price" name="base_price" step="0.01" min="0" required>
                                </div>

                                <div class="mb-3">
                                    <label for="image">Upload Image</label>
                                    <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="description">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="allergens">Allergens</label>
                                    <input type="text" class="form-control" id="allergens" name="allergens" placeholder="Milk, Nuts, etc.">
                                </div>

                                <div class="mb-3">
                                    <label for="display_order">Display Order</label>
                                    <input type="number" class="form-control" id="display_order" name="display_order" value="0">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check mb-3">
                                    <input type="checkbox" class="form-check-input" id="is_caffeinated" name="is_caffeinated" value="1">
                                    <label class="form-check-label" for="is_caffeinated">Contains Caffeine</label>
                                </div>

                                <div class="form-check mb-3">
                                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" checked>
                                    <label class="form-check-label" for="is_active">Active</label>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-check mb-3">
                                    <input type="checkbox" class="form-check-input" id="is_best_seller" name="is_best_seller" value="1">
                                    <label class="form-check-label" for="is_best_seller">Best Seller</label>
                                </div>

                                <div class="form-check mb-3">
                                    <input type="checkbox" class="form-check-input" id="is_new_arrival" name="is_new_arrival" value="1">
                                    <label class="form-check-label" for="is_new_arrival">New Arrival</label>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="manage_menu.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Add Item</button>
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
