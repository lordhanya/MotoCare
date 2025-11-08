<?php 
include __DIR__ . "/header.php";
?>

<header>
    <nav class="navbar navbar-expand-lg fixed-top mt-2 px-5 py-3" data-aos="slide-down" data-aos-duration="1000">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">Auto<span>Care</span></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse nav-box" id="navbarNavDropdown">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="#home">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Contact</a>
                    </li>
                </ul>
            </div>
            <div class="auth-btn">
                <a href="login.php" class="btn login-btn">Log-In</a>
                <a href="register.php" class="btn signup-btn">Sign-Up</a>
            </div>
        </div>
    </nav>
</header>

<!-- home section starts  -->
<section class="home d-flex align-items-center" id="home">
    <div class="container">
        <div class="row min-vh-100 align-items-center">
            <div class="col content text-center" data-aos="fade-up" data-aos-duration="1200">
                <h1>ADD YOUR <span>VEHICLE</span> NOW!</h1>
                <p>Your Ultimate Car and Bike Maintenance Reminder App</p>
                <div class="row home-btns">
                    <div class="col" data-aos="fade-right" data-aos-duration="1200" data-aos-delay="300">
                        <a href="register.php" type="button" class="btn register-btn">
                            Get Started
                        </a>
                    </div>
                    <div class="col" data-aos="fade-left" data-aos-duration="1200" data-aos-delay="300">
                        <a href="#features" class="btn btn-primary learn-btn d-inline-flex align-items-center gap-2">Learn More<i class="bi bi-arrow-up-right-circle-fill"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- features section starts  -->
<section class="features" id="features">
    <div class="container d-flex align-items-center justify-content-center">
        <div class="row min-vh-100 mt-5 d-flex align-items-center justify-content-center"">
            <div class=" col feature-content text-center">
            <h1 data-aos="fade-up" data-aos-duration="1000">FEATURES</h1>
            <div class="row cards d-flex justify-content-center gap-5 py-4 px-2 m-5">
                <div class="col">
                    <div class="card p-3 d-flex text-center justify-content-center align-items-center" style="width: 18rem; height: 25rem;" data-aos="fade-right" data-aos-duration="1000">
                        <img src="../assets/images/add_car.png" class="card-img-top" alt="add vehicle image">
                        <div class="card-body d-flex align-items-center justify-content-center">
                            <p class="card-text">Add your vehicle details.</p>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card p-3 d-flex text-center justify-content-center align-items-center" style="width: 18rem; height: 25rem;" data-aos="fade-up" data-aos-duration="1000">
                        <img src="../assets/images/maintenance.png" class="card-img-top" alt="Log Maintenance Image">
                        <div class="card-body d-flex align-items-center justify-content-center">
                            <p class="card-text">Log maintenance tasks (service, insurance, pollution check, etc.)</p>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card p-3 d-flex text-center justify-content-center align-items-center" style="width: 18rem; height: 25rem;" data-aos="fade-left" data-aos-duration="1000">
                        <img src="../assets/images/reminder.png" class="card-img-top" alt="Get Reminder Image">
                        <div class="card-body d-flex align-items-center justify-content-center">
                            <p class="card-text">Get reminders (via email or dashboard alerts) before due dates.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- about section starts here -->
<section class="about bg-dark" id="about">
    <div class="container d-flex justify-content-center align-items-center">
        <div class="row min-vh-100 mt-5 d-flex align-items-center justify-content-center">
            <div class="col about-content mt-5 text-center" data-aos="fade-up" data-aos-duration="1000">
                <h1 data-aos="fade-up" data-aos-duration="1000">ABOUT <span>US</span></h1>
                <div class="info mt-5 d-flex align-items-center justify-content-center gap-5">
                    <div class="our-mission" data-aos="fade-right" data-aos-duration="1000">
                        <h4>
                            Our Mission
                            <hr>
                        </h4>
                        <p>
                            To empower vehicle owners with a simple and reliable tool that helps them stay on top of all maintenance tasks, ensuring safer and longer-lasting vehicles.
                        </p>
                    </div>
                    <div class="our-vision" data-aos="fade-left" data-aos-duration="1000">
                        <h4>
                            Our Vision
                            <hr>
                        </h4>
                        <p>
                            To become the go-to platform for vehicle maintenance management, recognized for making auto care effortless, timely, and accessible to everyone.
                        </p>
                    </div>
                </div>
                <div class="commitment mt-4 mb-5 d-flex align-items-center justify-content-center">
                    <div class="our-commitment" data-aos="fade-up" data-aos-duration="1000">
                        <h4>
                            Our Commitment
                            <hr>
                        </h4>
                        <p>
                            We are dedicated to providing intuitive features, timely reminders, and exceptional user support, helping users maintain their vehicles with confidence and ease.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- contact section starts here -->
<section class="contact" id="contact">
    <div class="container d-flex justify-content-center align-items-center">
        <div class="row min-vh-100 d-flex align-items-center justify-content-center">
            <div class="col contact-content mt-5 text-center" data-aos="fade-up" data-aos-duration="1000">
                <h1>CONTACT <span>Us</span></h1>
                <div class="contact-form mt-5 d-flex align-items-center justify-content-center gap-5 p-5">
                    <div class="appreciation p-5 m-5">
                        <p>We'd love to hear from you! Whether you have questions, feedback, or need assistance, our team is here to help.</p>
                        <a class="mt-3" onclick="alertMSG()"><span>Reach out to us <i class="bi bi-arrow-right-circle-fill ms-3 fs-5"></i></span></a>
                        <p id="reachOut" class="mt-3"></p>
                        <script>
                            function alertMSG(){
                                document.getElementById("reachOut").innerHTML = "Fill the form Buddy -`♡´-";
                            }
                        </script>
                    </div>
                    <div class="form-container">
                        <form action="https://formspree.io/f/xrbozyly"
                            method="POST">
                            <div class="mb-3">
                                <label for="name" class="col-form-label">Name:</label>
                                <input type="text" class="form-control" name="name" id="name">
                            </div>
                            <div class="mb-3">
                                <label for="email" class="col-form-label">Email:</label>
                                <input type="email" class="form-control" name="email" id="email">
                            </div>
                            <div class="mb-3">
                                <label for="subject" class="col-form-label">Subject:</label>
                                <input type="text" class="form-control" name="subject" id="subject">
                            </div>
                            <div class="mb-3">
                                <label for="message" class="col-form-label">Message:</label>
                                <textarea class="form-control" name="message" id="message"></textarea>
                            </div>
                            <div class="mb-3">
                                <button type="submit" class="btn submit-btn">Submit</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>


<?php include __DIR__ . "/footer.php"; ?>