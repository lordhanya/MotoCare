<?php 
include "header.php"; 
include "../db/connection.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    if ($password !== $confirm_password) {
        echo "<script>alert('Buddy, passwords do not match!');</script>";
        exit;
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            echo "<script>alert('Email already registered. Please try logging in.');</script>";
            exit;
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            $insert = $conn->prepare("INSERT INTO users (first_name, last_name, email, password) 
                                      VALUES (:first_name, :last_name, :email, :password)");
            $insert->bindParam(':first_name', $first_name);
            $insert->bindParam(':last_name', $last_name);
            $insert->bindParam(':email', $email);
            $insert->bindParam(':password', $hashed);

            if ($insert->execute()) {
                echo "<script>alert('Registration successful! Redirecting to login page...'); 
                      window.location='login.php';</script>";
            } else {
                echo "<script>alert('Registration failed. Please try again.');</script>";
            }
        }
    }
}
?>


<section class="registerForm-section d-flex align-items-center justify-content-center">
    <div class="registerForm-container d-flex align-items-center justify-content-center">
        <form class="registerForm" action="register.php" method="POST">
            <p class="title">Register </p>
            <p class="message">Signup now and get full access to our app. </p>
            <div class="flex">
                <label>
                    <input class="input" name="first_name" type="text" placeholder="" required>
                    <span>Firstname</span>
                </label>

                <label>
                    <input class="input" name="last_name" type="text" placeholder="" required>
                    <span>Lastname</span>
                </label>
            </div>

            <label>
                <input class="input" name="email" type="email" placeholder="" required>
                <span>Email</span>
            </label>

            <label>
                <input class="input" name="password" type="password" placeholder="" required>
                <span>Password</span>
            </label>
            <label>
                <input class="input" name="confirm_password" type="password" placeholder="" required>
                <span>Confirm password</span>
            </label>
            <button class="submit" type="submit">Submit</button>
            <p class="signin">Already have an acount ? <a href="login.php">Signin</a> </p>
        </form>
    </div>
</section>

<?php include "footer.php";?>