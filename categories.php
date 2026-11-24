<?php
session_start();
require_once __DIR__ . "/../db.php";

// Admin protection
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../login.php");
    exit();
}

$message = "";
$error = "";

// Image upload folder
$imageFolder = __DIR__ . "/../assets/images/";

if (!is_dir($imageFolder)) {
    mkdir($imageFolder, 0777, true);
}

// Upload category image
function uploadCategoryImage($file, $imageFolder)
{
    if (!isset($file) || $file["error"] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file["error"] !== UPLOAD_ERR_OK) {
        throw new Exception("Image upload failed.");
    }

    if ($file["size"] > 5 * 1024 * 1024) {
        throw new Exception("Image size must be less than 5 MB.");
    }

    $allowedTypes = [
        "image/jpeg" => "jpg",
        "image/png"  => "png",
        "image/webp" => "webp"
    ];

    $imageInfo = getimagesize($file["tmp_name"]);

    if ($imageInfo === false) {
        throw new Exception("Please select a valid image.");
    }

    $mimeType = $imageInfo["mime"];

    if (!isset($allowedTypes[$mimeType])) {
        throw new Exception("Only JPG, PNG and WEBP images are allowed.");
    }

    $extension = $allowedTypes[$mimeType];
    $fileName = "category_" . bin2hex(random_bytes(8)) . "." . $extension;

    $destination = $imageFolder . $fileName;

    if (!move_uploaded_file($file["tmp_name"], $destination)) {
        throw new Exception("Could not save the image.");
    }

    return $fileName;
}

// Delete image file
function deleteCategoryImage($fileName, $imageFolder)
{
    if (!empty($fileName)) {
        $fileName = basename($fileName);
        $filePath = $imageFolder . $fileName;

        if (is_file($filePath)) {
            unlink($filePath);
        }
    }
}

// ADD CATEGORY
if (isset($_POST["add_category"])) {
    $categoryName = trim($_POST["category_name"]);
    $description = trim($_POST["description"]);

    if ($categoryName === "") {
        $error = "Category name is required.";
    } else {
        try {
            $image = uploadCategoryImage($_FILES["image"], $imageFolder);

            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO categories (category_name, description, image)
                 VALUES (?, ?, ?)"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "sss",
                $categoryName,
                $description,
                $image
            );

            if (mysqli_stmt_execute($stmt)) {
                $message = "Category added successfully!";
            } else {
                if ($image) {
                    deleteCategoryImage($image, $imageFolder);
                }
                $error = "Could not add category. The category name may already exist.";
            }

            mysqli_stmt_close($stmt);

        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// UPDATE CATEGORY
if (isset($_POST["update_category"])) {
    $id = (int) $_POST["category_id"];
    $categoryName = trim($_POST["category_name"]);
    $description = trim($_POST["description"]);

    if ($id <= 0 || $categoryName === "") {
        $error = "Please enter a valid category name.";
    } else {
        $stmt = mysqli_prepare(
            $conn,
            "SELECT image FROM categories WHERE id = ?"
        );
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $oldCategory = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$oldCategory) {
            $error = "Category not found.";
        } else {
            try {
                $newImage = uploadCategoryImage($_FILES["image"], $imageFolder);
                $image = $newImage ?: $oldCategory["image"];

                $stmt = mysqli_prepare(
                    $conn,
                    "UPDATE categories
                     SET category_name = ?, description = ?, image = ?
                     WHERE id = ?"
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    "sssi",
                    $categoryName,
                    $description,
                    $image,
                    $id
                );

                if (mysqli_stmt_execute($stmt)) {
                    if ($newImage) {
                        deleteCategoryImage($oldCategory["image"], $imageFolder);
                    }
                    $message = "Category updated successfully!";
                } else {
                    if ($newImage) {
                        deleteCategoryImage($newImage, $imageFolder);
                    }
                    $error = "Could not update category. Check whether the name already exists.";
                }

                mysqli_stmt_close($stmt);

            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
    }
}

// DELETE CATEGORY
if (isset($_POST["delete_category"])) {
    $id = (int) $_POST["category_id"];

    $stmt = mysqli_prepare(
        $conn,
        "SELECT image FROM categories WHERE id = ?"
    );
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $category = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if ($category) {
        $stmt = mysqli_prepare(
            $conn,
            "DELETE FROM categories WHERE id = ?"
        );
        mysqli_stmt_bind_param($stmt, "i", $id);

        if (mysqli_stmt_execute($stmt)) {
            deleteCategoryImage($category["image"], $imageFolder);
            $message = "Category deleted successfully!";
        } else {
            $error = "Could not delete category.";
        }

        mysqli_stmt_close($stmt);
    } else {
        $error = "Category not found.";
    }
}

// SEARCH
$search = trim($_GET["search"] ?? "");

if ($search !== "") {
    $stmt = mysqli_prepare(
        $conn,
        "SELECT c.*,
                (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) AS product_count
         FROM categories c
         WHERE c.category_name LIKE ?
         ORDER BY c.id DESC"
    );

    $searchValue = "%" . $search . "%";
    mysqli_stmt_bind_param($stmt, "s", $searchValue);
    mysqli_stmt_execute($stmt);
    $categories = mysqli_stmt_get_result($stmt);

} else {
    $categories = mysqli_query(
        $conn,
        "SELECT c.*,
                (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) AS product_count
         FROM categories c
         ORDER BY c.id DESC"
    );
}

$totalResult = mysqli_query($conn, "SELECT COUNT(*) AS total FROM categories");
$totalCategories = mysqli_fetch_assoc($totalResult)["total"];

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Categories</title>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: Arial, sans-serif;
}

body {
    background: #f4f6fb;
    color: #1e293b;
}

.container {
    max-width: 1250px;
    margin: 35px auto;
    padding: 20px;
}

.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    flex-wrap: wrap;
    gap: 15px;
}

.header h1 {
    font-size: 27px;
}

.back-link {
    text-decoration: none;
    background: #1e293b;
    color: white;
    padding: 11px 18px;
    border-radius: 8px;
}

.summary {
    background: white;
    padding: 22px;
    border-radius: 12px;
    margin-bottom: 25px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.05);
}

.summary h2 {
    color: #2563eb;
    font-size: 28px;
    margin-top: 8px;
}

.content {
    display: grid;
    grid-template-columns: 350px 1fr;
    gap: 22px;
    align-items: start;
}

.panel {
    background: white;
    padding: 24px;
    border-radius: 12px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.05);
}

.panel h2 {
    font-size: 20px;
    margin-bottom: 20px;
}

.form-group {
    margin-bottom: 16px;
}

label {
    display: block;
    font-weight: bold;
    margin-bottom: 7px;
    font-size: 14px;
}

input, textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #dbe1ea;
    border-radius: 7px;
    font-size: 14px;
}

textarea {
    min-height: 100px;
    resize: vertical;
}

input:focus, textarea:focus {
    outline: none;
    border-color: #2563eb;
}

button {
    border: none;
    cursor: pointer;
    padding: 10px 15px;
    border-radius: 7px;
    font-weight: bold;
}

.primary-btn {
    background: #2563eb;
    color: white;
    width: 100%;
    padding: 13px;
}

.update-btn {
    background: #2563eb;
    color: white;
}

.delete-btn {
    background: #dc2626;
    color: white;
}

.edit-btn {
    background: #e0e7ff;
    color: #1d4ed8;
}

.message {
    padding: 13px;
    border-radius: 7px;
    margin-bottom: 18px;
    background: #dcfce7;
    color: #166534;
}

.error {
    padding: 13px;
    border-radius: 7px;
    margin-bottom: 18px;
    background: #fee2e2;
    color: #991b1b;
}

.search-form {
    display: flex;
    gap: 8px;
    margin-bottom: 18px;
}

.search-form input {
    flex: 1;
}

.search-btn {
    background: #1e293b;
    color: white;
}

.table-wrapper {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 650px;
}

th, td {
    padding: 13px 10px;
    border-bottom: 1px solid #e5e7eb;
    text-align: left;
    font-size: 14px;
}

th {
    background: #f8fafc;
}

.category-image {
    width: 65px;
    height: 55px;
    object-fit: cover;
    border-radius: 7px;
    border: 1px solid #e5e7eb;
}

.no-image {
    width: 65px;
    height: 55px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f1f5f9;
    border-radius: 7px;
    font-size: 24px;
}

.actions {
    display: flex;
    gap: 7px;
    flex-wrap: wrap;
}

.edit-form {
    display: none;
    background: #f8fafc;
    padding: 10px;
    border-radius: 8px;
    margin-top: 10px;
    min-width: 220px;
}

.edit-form.show {
    display: block;
}

.edit-form input,
.edit-form textarea {
    margin-bottom: 8px;
}

@media(max-width: 850px) {
    .content {
        grid-template-columns: 1fr;
    }
}

</style>
</head>

<body>

<div class="container">

    <div class="header">
        <h1>Category Management</h1>
        <a href="dashboard.php" class="back-link">← Dashboard</a>
    </div>

    <?php if ($message): ?>
        <div class="message">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="summary">
        <p>Total Categories</p>
        <h2><?php echo (int)$totalCategories; ?></h2>
    </div>

    <div class="content">

        <!-- ADD CATEGORY FORM -->
        <div class="panel">
            <h2>Add New Category</h2>

            <form method="POST" enctype="multipart/form-data">

                <div class="form-group">
                    <label>Category Name</label>
                    <input type="text"
                           name="category_name"
                           placeholder="Enter category name"
                           required>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description"
                              placeholder="Enter category description"></textarea>
                </div>

                <div class="form-group">
                    <label>Category Image</label>
                    <input type="file"
                           name="image"
                           accept=".jpg,.jpeg,.png,.webp">
                    <small>JPG, PNG or WEBP. Maximum 5 MB.</small>
                </div>

                <button type="submit"
                        name="add_category"
                        class="primary-btn">
                    + Add Category
                </button>

            </form>
        </div>

        <!-- CATEGORY LIST -->
        <div class="panel">

            <h2>All Categories</h2>

            <form method="GET" class="search-form">
                <input type="text"
                       name="search"
                       placeholder="Search category..."
                       value="<?php echo htmlspecialchars($search); ?>">

                <button type="submit" class="search-btn">
                    Search
                </button>
            </form>

            <div class="table-wrapper">
                <table>

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Products</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if ($categories && mysqli_num_rows($categories) > 0): ?>

                        <?php while ($row = mysqli_fetch_assoc($categories)): ?>

                            <tr>
                                <td><?php echo (int)$row["id"]; ?></td>

                                <td>
                                    <?php if (!empty($row["image"])): ?>
                                        <img class="category-image"
                                             src="../assets/images/<?php echo htmlspecialchars(basename($row["image"])); ?>"
                                             alt="Category image">
                                    <?php else: ?>
                                        <div class="no-image">📦</div>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($row["category_name"]); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($row["description"] ?? ""); ?>
                                </td>

                                <td>
                                    <?php echo (int)$row["product_count"]; ?>
                                </td>

                                <td>
                                    <div class="actions">

                                        <button type="button"
                                                class="edit-btn"
                                                onclick="toggleEdit(<?php echo (int)$row['id']; ?>)">
                                            Edit
                                        </button>

                                        <form method="POST"
                                              onsubmit="return confirm('Are you sure you want to delete this category?');">

                                            <input type="hidden"
                                                   name="category_id"
                                                   value="<?php echo (int)$row['id']; ?>">

                                            <button type="submit"
                                                    name="delete_category"
                                                    class="delete-btn">
                                                Delete
                                            </button>
                                        </form>

                                    </div>

                                    <!-- EDIT FORM -->
                                    <div id="edit-<?php echo (int)$row['id']; ?>"
                                         class="edit-form">

                                        <form method="POST"
                                              enctype="multipart/form-data">

                                            <input type="hidden"
                                                   name="category_id"
                                                   value="<?php echo (int)$row['id']; ?>">

                                            <label>Category Name</label>
                                            <input type="text"
                                                   name="category_name"
                                                   value="<?php echo htmlspecialchars($row['category_name']); ?>"
                                                   required>

                                            <label>Description</label>
                                            <textarea name="description"><?php echo htmlspecialchars($row['description'] ?? ""); ?></textarea>

                                            <label>Change Image</label>
                                            <input type="file"
                                                   name="image"
                                                   accept=".jpg,.jpeg,.png,.webp">

                                            <button type="submit"
                                                    name="update_category"
                                                    class="update-btn">
                                                Save Changes
                                            </button>

                                        </form>
                                    </div>

                                </td>
                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="6" style="text-align:center;">
                                No categories found.
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>
                </table>
            </div>

        </div>

    </div>

</div>

<script>
function toggleEdit(id) {
    const form = document.getElementById("edit-" + id);
    form.classList.toggle("show");
}
</script>

</body>
</html>
