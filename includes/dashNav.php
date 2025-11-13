<!-- Dashboard Navbar -->
<nav class="navbar navbar-expand-lg border-body fixed-top px-4 py-3 dashNav">
    <div class="container-fluid">
        <!-- Left Section: Sidebar Toggle + Logo -->
        <div class="d-flex align-items-center gap-3">
            <a class="btn sidebarToggle-btn" data-bs-toggle="offcanvas" href="#offcanvasExample" role="button" aria-controls="offcanvasExample">
                <i class="bi bi-list"></i>
            </a>
            <a class="navbar-brand" href="dashboard.php">Auto<span>Care</span></a>
        </div>

        <!-- Right Section: Profile + Logout -->
        <div class="d-flex align-items-center gap-3 ms-auto">
            <div class="dashNavProfile-section">
                <div class="profile-icon">
                    <i class="bi bi-person-fill"></i>
                </div>
                <div class="profile-text">
                    <span class="profile-greeting">Welcome</span>
                    <span class="profile-name">User</span>
                </div>
            </div>
            
            <div class="nav-divider"></div>
            
            <a class="logout-btn" href="logout.php">
                <i class="bi bi-box-arrow-right"></i>
                Logout
            </a>
        </div>
    </div>
</nav>

