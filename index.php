<?php
require_once 'db.php';

$errors = [];
$success_msg = '';
$general_error = '';

// Initialize sticky input variables
$full_name = $email = $phone = $matric_number = $dob = $department = $level = $gender = '';
$accuracy_confirmed = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and capture input values (Task 2)
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $matric_number = trim($_POST['matric_number'] ?? '');
    $dob = trim($_POST['dob'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $level = trim($_POST['level'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $accuracy_confirmed = isset($_POST['accuracy_confirmed']);

    // --- VALIDATIONS (Task 2) ---
    if (empty($full_name)) {
        $errors['full_name'] = 'Full name is required.';
    }

    if (empty($email)) {
        $errors['email'] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format.';
    }

    if (empty($phone)) {
        $errors['phone'] = 'Phone number is required.';
    } elseif (!preg_match('/^[0-9]{8,15}$/', $phone)) {
        $errors['phone'] = 'Enter a valid phone number (digits only, e.g., 6XXXXXXXX).';
    }

    if (empty($matric_number)) {
        $errors['matric_number'] = 'Matriculation number is required.';
    } elseif (!preg_match('/^[A-Z]{2}[0-9]{7}$/', $matric_number)) {
        $errors['matric_number'] = 'Format must match e.g. SE2026001 (2 letters + 7 numbers).';
    }

    if (empty($dob)) {
        $errors['dob'] = 'Date of birth is required.';
    } else {
        $dob_date = strtotime($dob);
        if ($dob_date > time()) {
            $errors['dob'] = 'Date of birth cannot be in the future.';
        }
    }

    if (empty($department)) {
        $errors['department'] = 'Please select a department.';
    }

    if (empty($level)) {
        $errors['level'] = 'Please select a level.';
    }

    if (empty($gender)) {
        $errors['gender'] = 'Please select a gender.';
    }

    if (empty($password)) {
        $errors['password'] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters long.';
    }

    if ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }

    if (!$accuracy_confirmed) {
        $errors['accuracy'] = 'You must confirm that the information is accurate.';
    }

    // --- DATABASE INSERTION (Task 4) ---
    if (empty($errors)) {
        try {
            // Check for existing email or matriculation number
            $checkStmt = $pdo->prepare("SELECT email, matric_number FROM students WHERE email = :email OR matric_number = :matric_number");
            $checkStmt->execute(['email' => $email, 'matric_number' => $matric_number]);
            $existingUser = $checkStmt->fetch();

            if ($existingUser) {
                if ($existingUser['email'] === $email) {
                    $errors['email'] = 'This email is already registered.';
                }
                if ($existingUser['matric_number'] === $matric_number) {
                    $errors['matric_number'] = 'This matriculation number is already registered.';
                }
            } else {
                // Securely hash password
                $password_hash = password_hash($password, PASSWORD_DEFAULT);

                // Insert user
                $sql = "INSERT INTO students (full_name, email, phone, matric_number, date_of_birth, department, level, gender, password_hash) 
                        VALUES (:full_name, :email, :phone, :matric_number, :dob, :department, :level, :gender, :password_hash)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':full_name' => $full_name,
                    ':email' => $email,
                    ':phone' => $phone,
                    ':matric_number' => $matric_number,
                    ':dob' => $dob,
                    ':department' => $department,
                    ':level' => $level,
                    ':gender' => $gender,
                    ':password_hash' => $password_hash
                ]);

                $success_msg = "Registration successful! Record saved.";
                
                // Clear fields on success
                $full_name = $email = $phone = $matric_number = $dob = $department = $level = $gender = '';
                $accuracy_confirmed = false;
            }
        } catch (PDOException $e) {
            $general_error = "A database error occurred. Please try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
    <div class="header">
        <h2>STUDENT REGISTRATION</h2>
    </div>

    <div class="form-body">
        <p class="form-instruction">Complete all required fields marked *</p>

        <?php if (!empty($success_msg)): ?>
            <div class="alert-success"><?= htmlspecialchars($success_msg) ?></div>
        <?php endif; ?>

        <?php if (!empty($general_error)): ?>
            <div class="alert-error"><?= htmlspecialchars($general_error) ?></div>
        <?php endif; ?>

        <form action="index.php" method="POST" novalidate>
            
            <div class="form-group">
                <label>Full name *</label>
                <input type="text" name="full_name" placeholder="e.g. Ada Nfor" value="<?= htmlspecialchars($full_name) ?>">
                <?php if (isset($errors['full_name'])): ?><span class="error"><?= $errors['full_name'] ?></span><?php endif; ?>
            </div>

            <div class="form-group">
                <label>Email address *</label>
                <input type="email" name="email" placeholder="student@example.com" value="<?= htmlspecialchars($email) ?>">
                <?php if (isset($errors['email'])): ?><span class="error"><?= $errors['email'] ?></span><?php endif; ?>
            </div>

            <div class="form-group">
                <label>Phone number *</label>
                <input type="text" name="phone" placeholder="e.g. 6XXXXXXXX" value="<?= htmlspecialchars($phone) ?>">
                <?php if (isset($errors['phone'])): ?><span class="error"><?= $errors['phone'] ?></span><?php endif; ?>
            </div>

            <div class="form-group">
                <label>Matriculation number *</label>
                <input type="text" name="matric_number" placeholder="e.g. SE2026001" value="<?= htmlspecialchars($matric_number) ?>">
                <?php if (isset($errors['matric_number'])): ?><span class="error"><?= $errors['matric_number'] ?></span><?php endif; ?>
            </div>

            <div class="form-group">
                <label>Date of birth *</label>
                <input type="date" name="dob" value="<?= htmlspecialchars($dob) ?>">
                <?php if (isset($errors['dob'])): ?><span class="error"><?= $errors['dob'] ?></span><?php endif; ?>
            </div>

            <div class="form-group">
                <label>Department *</label>
                <select name="department">
                    <option value="">[ Select department ]</option>
                    <option value="Software Engineering" <?= $department === 'Software Engineering' ? 'selected' : '' ?>>Software Engineering</option>
                    <option value="Computer Science" <?= $department === 'Computer Science' ? 'selected' : '' ?>>Computer Science</option>
                    <option value="Cybersecurity" <?= $department === 'Cybersecurity' ? 'selected' : '' ?>>Cybersecurity</option>
                </select>
                <?php if (isset($errors['department'])): ?><span class="error"><?= $errors['department'] ?></span><?php endif; ?>
            </div>

            <div class="form-group">
                <label>Level *</label>
                <select name="level">
                    <option value="">[ Select level ]</option>
                    <option value="100" <?= $level === '100' ? 'selected' : '' ?>>100</option>
                    <option value="200" <?= $level === '200' ? 'selected' : '' ?>>200</option>
                    <option value="300" <?= $level === '300' ? 'selected' : '' ?>>300</option>
                    <option value="400" <?= $level === '400' ? 'selected' : '' ?>>400</option>
                </select>
                <?php if (isset($errors['level'])): ?><span class="error"><?= $errors['level'] ?></span><?php endif; ?>
            </div>

            <div class="form-group">
                <label>Gender *</label>
                <div class="radio-group">
                    <label><input type="radio" name="gender" value="Female" <?= $gender === 'Female' ? 'checked' : '' ?>> Female</label>
                    <label><input type="radio" name="gender" value="Male" <?= $gender === 'Male' ? 'checked' : '' ?>> Male</label>
                    <label><input type="radio" name="gender" value="Prefer not to say" <?= $gender === 'Prefer not to say' ? 'checked' : '' ?>> Prefer not to say</label>
                </div>
                <?php if (isset($errors['gender'])): ?><span class="error"><?= $errors['gender'] ?></span><?php endif; ?>
            </div>

            <div class="form-group">
                <label>Password *</label>
                <input type="password" name="password">
                <?php if (isset($errors['password'])): ?><span class="error"><?= $errors['password'] ?></span><?php endif; ?>
            </div>

            <div class="form-group">
                <label>Confirm password *</label>
                <input type="password" name="confirm_password">
                <?php if (isset($errors['confirm_password'])): ?><span class="error"><?= $errors['confirm_password'] ?></span><?php endif; ?>
            </div>

            <div class="checkbox-group">
                <input type="checkbox" name="accuracy_confirmed" id="accuracy" <?= $accuracy_confirmed ? 'checked' : '' ?>>
                <label for="accuracy" style="font-weight: normal;">I confirm that the information provided is accurate.</label>
            </div>
            <?php if (isset($errors['accuracy'])): ?><span class="error" style="margin-top:-20px; margin-bottom:15px;"><?= $errors['accuracy'] ?></span><?php endif; ?>

            <button type="submit" class="btn-submit">SUBMIT REGISTRATION</button>
        </form>
    </div>
</div>

</body>
</html>