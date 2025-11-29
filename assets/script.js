// ========================================
// AUTOCARE - MAIN JAVASCRIPT FILE
// ========================================

// Wait for DOM to be fully loaded
document.addEventListener("DOMContentLoaded", function () {
  // ========================================
  // 1. INITIALIZE AOS (Animate On Scroll)
  // ========================================
  if (typeof AOS !== "undefined") {
    AOS.init({
      once: true,
      duration: 1000,
      offset: 100,
      easing: "ease-in-out",
    });
  }

  // ========================================
  // 2. NAVBAR SCROLL EFFECT
  // ========================================
  const navbar = document.querySelector(".navbar");

  function handleNavbarScroll() {
    if (window.scrollY > 50) {
      navbar?.classList.add("scrolled");
    } else {
      navbar?.classList.remove("scrolled");
    }
  }

  // Initial check
  handleNavbarScroll();

  // Listen to scroll events
  window.addEventListener("scroll", handleNavbarScroll);

  // ========================================
  // 3. ACTIVE NAV LINK ON SCROLL (Landing Page)
  // ========================================
  const sections = document.querySelectorAll("section[id]");
  const navLinks = document.querySelectorAll("header nav .nav-link");

  function updateActiveNavLink() {
    let currentSection = "";
    const scrollPosition = window.scrollY;

    // Find which section is currently in view
    sections.forEach((section) => {
      const sectionTop = section.offsetTop - 200; // Offset for better UX
      const sectionHeight = section.offsetHeight;
      const sectionId = section.getAttribute("id");

      if (
        scrollPosition >= sectionTop &&
        scrollPosition < sectionTop + sectionHeight
      ) {
        currentSection = sectionId;
      }
    });

    // Update active class on nav links
    navLinks.forEach((link) => {
      link.classList.remove("active");
      const href = link.getAttribute("href");

      if (href && href.includes("#" + currentSection)) {
        link.classList.add("active");
      }
    });
  }

  // Only run if we have sections (landing page)
  if (sections.length > 0) {
    window.addEventListener("scroll", updateActiveNavLink);
    // Initial call
    updateActiveNavLink();
  }

  // ========================================
  // 4. SIDEBAR ACTIVE STATE (Dashboard Pages)
  // ========================================
  const sidebarLinks = document.querySelectorAll(
    ".offcanvas-body .sidebarItems .sidebarLink"
  );

  function updateActiveSidebarLink() {
    // Get current page filename
    const currentPage =
      window.location.pathname.split("/").pop() || "index.php";

    sidebarLinks.forEach((link) => {
      const linkHref = link.getAttribute("href");
      const linkPage = linkHref ? linkHref.split("/").pop() : "";

      // Remove active class from parent li
      const parentLi = link.closest(".listItems");

      if (parentLi) {
        if (linkPage === currentPage) {
          parentLi.classList.add("active");
        } else {
          parentLi.classList.remove("active");
        }
      }
    });
  }

  // Only run if sidebar exists
  if (sidebarLinks.length > 0) {
    updateActiveSidebarLink();
  }

  // ========================================
  // 5. SMOOTH SCROLL FOR ANCHOR LINKS
  // ========================================
  document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
    anchor.addEventListener("click", function (e) {
      const href = this.getAttribute("href");

      // Only prevent default for valid hash links
      if (href !== "#" && href !== "") {
        const targetElement = document.querySelector(href);

        if (targetElement) {
          e.preventDefault();
          targetElement.scrollIntoView({
            behavior: "smooth",
            block: "start",
          });

          // Close mobile menu if open
          const navbarCollapse = document.querySelector(".navbar-collapse");
          if (navbarCollapse && navbarCollapse.classList.contains("show")) {
            const bsCollapse = new bootstrap.Collapse(navbarCollapse);
            bsCollapse.hide();
          }
        }
      }
    });
  });

  // ========================================
  // 6. FORM VALIDATION ENHANCEMENT
  // ========================================
  const forms = document.querySelectorAll("form");

  forms.forEach((form) => {
    form.addEventListener("submit", function (e) {
      // Add Bootstrap validation classes
      if (!form.checkValidity()) {
        e.preventDefault();
        e.stopPropagation();
      }
      form.classList.add("was-validated");
    });
  });

  // ========================================
  // 7. PASSWORD TOGGLE (if password fields exist)
  // ========================================
  const passwordToggles = document.querySelectorAll(".password-toggle");

  passwordToggles.forEach((toggle) => {
    toggle.addEventListener("click", function () {
      const targetId = this.getAttribute("data-target");
      const passwordField = document.getElementById(targetId);
      const icon = this.querySelector("i");

      if (passwordField) {
        if (passwordField.type === "password") {
          passwordField.type = "text";
          icon?.classList.replace("bi-eye", "bi-eye-slash");
        } else {
          passwordField.type = "password";
          icon?.classList.replace("bi-eye-slash", "bi-eye");
        }
      }
    });
  });

  // ========================================
  // 8. AUTO-DISMISS ALERTS
  // ========================================
  const alerts = document.querySelectorAll(".alert:not(.alert-permanent)");

  alerts.forEach((alert) => {
    setTimeout(() => {
      const bsAlert = new bootstrap.Alert(alert);
      bsAlert.close();
    }, 5000); // Auto dismiss after 5 seconds
  });

  // ========================================
  // 9. CONFIRMATION DIALOGS
  // ========================================
  const deleteButtons = document.querySelectorAll("[data-confirm]");

  deleteButtons.forEach((button) => {
    button.addEventListener("click", function (e) {
      const message = this.getAttribute("data-confirm") || "Are you sure?";
      if (!confirm(message)) {
        e.preventDefault();
        return false;
      }
    });
  });

  // ========================================
  // 10. LOADING SPINNER FOR FORMS
  // ========================================
  const formsWithSpinner = document.querySelectorAll("form[data-loading]");

  formsWithSpinner.forEach((form) => {
    form.addEventListener("submit", function () {
      const submitBtn = this.querySelector('[type="submit"]');
      if (submitBtn && this.checkValidity()) {
        submitBtn.disabled = true;
        submitBtn.innerHTML =
          '<span class="spinner-border spinner-border-sm me-2"></span>Loading...';
      }
    });
  });

  // ========================================
  // 11. TOOLTIP INITIALIZATION
  // ========================================
  const tooltipTriggerList = [].slice.call(
    document.querySelectorAll('[data-bs-toggle="tooltip"]')
  );
  tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });

  // ========================================
  // 12. POPOVER INITIALIZATION
  // ========================================
  const popoverTriggerList = [].slice.call(
    document.querySelectorAll('[data-bs-toggle="popover"]')
  );
  popoverTriggerList.map(function (popoverTriggerEl) {
    return new bootstrap.Popover(popoverTriggerEl);
  });
});

// ========================================
// GLOBAL UTILITY FUNCTIONS
// ========================================

// Alert Message Function (for contact form)
function alertMSG() {
  const element = document.getElementById("reachOut");
  if (element) {
    element.innerHTML = "Fill the form Buddy -`♡´-";
    element.style.animation = "fadeIn 0.5s ease";
  }
}

// Show Toast Notification
function showToast(message, type = "success") {
  const toastContainer = document.getElementById("toastContainer");

  if (!toastContainer) {
    console.warn("Toast container not found");
    return;
  }

  const toastId = "toast-" + Date.now();
  const bgClass =
    type === "success"
      ? "bg-success"
      : type === "error"
      ? "bg-danger"
      : "bg-info";

  const toastHTML = `
        <div id="${toastId}" class="toast align-items-center text-white ${bgClass} border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;

  toastContainer.insertAdjacentHTML("beforeend", toastHTML);

  const toastElement = document.getElementById(toastId);
  const toast = new bootstrap.Toast(toastElement);
  toast.show();

  // Remove after hidden
  toastElement.addEventListener("hidden.bs.toast", function () {
    this.remove();
  });
}

// Format Date
function formatDate(dateString) {
  const options = { year: "numeric", month: "long", day: "numeric" };
  return new Date(dateString).toLocaleDateString("en-US", options);
}

// Debounce Function (for search, etc.)
function debounce(func, wait) {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
}

// ========================================
// PROFILE PICTURE SELECTION - JAVASCRIPT
// ========================================

document.addEventListener("DOMContentLoaded", function () {
  // ========================================
  // ELEMENTS
  // ========================================
  const fileInput = document.getElementById("fileInput");
  const fileName = document.getElementById("fileName");
  const clearBtn = document.getElementById("clearBtn");
  const profileForm = document.getElementById("profileForm");
  const previewContainer = document.getElementById("previewContainer");
  const imagePreview = document.getElementById("imagePreview");
  const removePreview = document.getElementById("removePreview");
  const avatarOptions = document.querySelectorAll(
    '.avatar-option input[type="radio"]'
  );
  const avatarGrid = document.getElementById("avatarGrid");

  // ========================================
  // FILE INPUT HANDLER
  // ========================================
  fileInput.addEventListener("change", function (e) {
    const file = e.target.files[0];

    if (file) {
      // Update file name display
      fileName.textContent = file.name;

      // Show image preview
      const reader = new FileReader();
      reader.onload = function (e) {
        imagePreview.src = e.target.result;
        previewContainer.style.display = "block";
      };
      reader.readAsDataURL(file);

      // Deselect all avatars when file is uploaded
      avatarOptions.forEach((radio) => {
        radio.checked = false;
      });
    }
  });

  // ========================================
  // CLEAR BUTTON HANDLER
  // ========================================
  clearBtn.addEventListener("click", function () {
    // Reset form
    profileForm.reset();

    // Reset file name
    fileName.textContent = "Choose an image file";

    // Hide preview
    previewContainer.style.display = "none";
    imagePreview.src = "";
  });

  // ========================================
  // REMOVE PREVIEW BUTTON
  // ========================================
  removePreview.addEventListener("click", function () {
    // Clear file input
    fileInput.value = "";

    // Reset file name
    fileName.textContent = "Choose an image file";

    // Hide preview
    previewContainer.style.display = "none";
    imagePreview.src = "";
  });

  // ========================================
  // AVATAR SELECTION HANDLER
  // ========================================
  avatarOptions.forEach((radio) => {
    radio.addEventListener("change", function () {
      if (this.checked) {
        // Clear file input when avatar is selected
        fileInput.value = "";
        fileName.textContent = "Choose an image file";

        // Hide preview
        previewContainer.style.display = "none";
        imagePreview.src = "";
      }
    });
  });

  // ========================================
  // AVATAR CLICK ANIMATION
  // ========================================
  avatarGrid.addEventListener("click", function (e) {
    const avatarOption = e.target.closest(".avatar-option");
    if (avatarOption) {
      const radio = avatarOption.querySelector('input[type="radio"]');
      if (radio) {
        // Trigger radio button
        radio.checked = true;

        // Dispatch change event
        const event = new Event("change", { bubbles: true });
        radio.dispatchEvent(event);

        // Add animation
        const wrapper = avatarOption.querySelector(".avatar-wrapper");
        wrapper.style.animation = "none";
        setTimeout(() => {
          wrapper.style.animation = "selectPulse 0.3s ease";
        }, 10);
      }
    }
  });

  // ========================================
  // FORM VALIDATION
  // ========================================
  profileForm.addEventListener("submit", function (e) {
    const fileSelected = fileInput.files.length > 0;
    const avatarSelected = Array.from(avatarOptions).some(
      (radio) => radio.checked
    );

    if (!fileSelected && !avatarSelected) {
      e.preventDefault();
      alert("Please select a profile picture or choose a default avatar.");
      return false;
    }
  });

  // ========================================
  // DRAG AND DROP SUPPORT
  // ========================================
  const fileLabel = document.querySelector(".file-label");

  fileLabel.addEventListener("dragover", function (e) {
    e.preventDefault();
    this.style.borderColor = "var(--accent-color)";
    this.style.background = "var(--bg-card-hover)";
  });

  fileLabel.addEventListener("dragleave", function (e) {
    e.preventDefault();
    this.style.borderColor = "var(--border-color)";
    this.style.background = "var(--bg-secondary)";
  });

  fileLabel.addEventListener("drop", function (e) {
    e.preventDefault();
    this.style.borderColor = "var(--border-color)";
    this.style.background = "var(--bg-secondary)";

    const files = e.dataTransfer.files;
    if (files.length > 0) {
      fileInput.files = files;

      // Trigger change event
      const event = new Event("change", { bubbles: true });
      fileInput.dispatchEvent(event);
    }
  });

  // ========================================
  // FILE TYPE VALIDATION
  // ========================================
  fileInput.addEventListener("change", function (e) {
    const file = e.target.files[0];

    if (file) {
      // Check file type
      const validTypes = [
        "image/jpeg",
        "image/jpg",
        "image/png",
        "image/gif",
        "image/webp",
      ];
      if (!validTypes.includes(file.type)) {
        alert("Please select a valid image file (JPEG, PNG, GIF, or WebP)");
        fileInput.value = "";
        fileName.textContent = "Choose an image file";
        return;
      }

      // Check file size (max 5MB)
      const maxSize = 5 * 1024 * 1024; // 5MB in bytes
      if (file.size > maxSize) {
        alert("File size must be less than 5MB");
        fileInput.value = "";
        fileName.textContent = "Choose an image file";
        return;
      }
    }
  });
});

// ========================================
// CSS ANIMATION (Add to style if needed)
// ========================================
const style = document.createElement("style");
style.textContent = `
    @keyframes selectPulse {
        0%, 100% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.05);
        }
    }
`;
document.head.appendChild(style);

// ========================================
// EXTRACTED JAVASCRIPT FROM PHP FILES
// ========================================

// Page Loader Hider
window.addEventListener('load', function() {
    console.log("Page fully loaded, hiding loader...");
    const loader = document.getElementById('pageLoader');
    if (loader) {
      loader.classList.add('hidden');
      console.log("Loader hidden");
    } else {
      console.log("Loader element not found");
    }
});

// Password Toggle Function
function togglePassword(inputId, button) {
    const input = document.getElementById(inputId);
    const icon = button.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}

// Password Strength Indicator
document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('password');
    const strengthFill = document.getElementById('strength-fill');

    if (passwordInput && strengthFill) {
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;

            // Check password strength
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
            if (password.match(/\d/)) strength++;
            if (password.match(/[^a-zA-Z\d]/)) strength++;

            // Update strength bar
            strengthFill.className = 'strength-fill';
            if (strength === 1) {
                strengthFill.classList.add('strength-weak');
            } else if (strength === 2 || strength === 3) {
                strengthFill.classList.add('strength-medium');
            } else if (strength >= 4) {
                strengthFill.classList.add('strength-strong');
            }
        });
    }

    // Password Match Validation
    const password2Input = document.getElementById('password2');
    if (password2Input) {
        password2Input.addEventListener('input', function() {
            if (this.value && this.value !== passwordInput.value) {
                this.style.borderColor = 'var(--danger-color)';
            } else {
                this.style.borderColor = 'var(--border-color)';
            }
        });
    }

    // Email verification form loading state
    const verificationForm = document.getElementById('verificationForm');
    if (verificationForm) {
        verificationForm.addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            btn.classList.add('loading');
            btn.innerHTML = "<i class='bi bi-hourglass-split'></i> Sending...";
        });
    }

    // Email input validation border color feedback
    const emailInput = document.getElementById('email');
    if (emailInput) {
        emailInput.addEventListener('input', function() {
            if (this.validity.valid) {
                this.style.borderColor = 'var(--success-color)';
            } else if (this.value.length > 0) {
                this.style.borderColor = 'var(--accent-color)';
            } else {
                this.style.borderColor = 'var(--border-color)';
            }
        });
    }
});

// ========================================
// SCHEDULE LIST - FILTER & EXPORT
// ========================================

// Toggle filter panel visibility
function toggleFilters() {
    const filterPanel = document.getElementById("filterPanel");

    if (!filterPanel) {
        createFilterPanel();
    } else {
        filterPanel.classList.toggle("active");
    }
}

// Create filter panel dynamically for schedule list
function createFilterPanel() {
    const tableCard = document.querySelector(".table-card");
    const tableHeader = document.querySelector(".table-header");

    // Create filter panel HTML
    const filterPanelHTML = `
<div id="filterPanel" class="filter-panel active">
  <div class="filter-grid">
    <!-- Vehicle Filter -->
    <div class="filter-group">
      <label for="filterVehicle">Vehicle</label>
      <select id="filterVehicle" class="filter-input">
        <option value="">All Vehicles</option>
      </select>
    </div>
    
    <!-- Service Type Filter -->
    <div class="filter-group">
      <label for="filterServiceType">Service Type</label>
      <input type="text" id="filterServiceType" class="filter-input" placeholder="e.g., Oil Change">
    </div>
    
    <!-- Status Filter -->
    <div class="filter-group">
      <label for="filterStatus">Status</label>
      <select id="filterStatus" class="filter-input">
        <option value="">All Statuses</option>
        <option value="pending">Pending</option>
        <option value="completed">Completed</option>
        <option value="missed">Missed</option>
      </select>
    </div>
    
    <!-- Priority Filter -->
    <div class="filter-group">
      <label for="filterPriority">Priority</label>
      <select id="filterPriority" class="filter-input">
        <option value="">All Priorities</option>
        <option value="overdue">Overdue</option>
        <option value="urgent">Urgent</option>
        <option value="soon">Soon</option>
        <option value="normal">Normal</option>
      </select>
    </div>
    
    <!-- Date Range Filter -->
    <div class="filter-group">
      <label for="filterDateFrom">Due Date From</label>
      <input type="date" id="filterDateFrom" class="filter-input">
    </div>
    
    <div class="filter-group">
      <label for="filterDateTo">Due Date To</label>
      <input type="date" id="filterDateTo" class="filter-input">
    </div>
    
    <!-- Days Left Filter -->
    <div class="filter-group">
      <label for="filterDaysLeft">Max Days Left</label>
      <input type="number" id="filterDaysLeft" class="filter-input" placeholder="e.g., 30" min="0">
    </div>
    
    <!-- Due KM Filter -->
    <div class="filter-group">
      <label for="filterDueKmMin">Min Due KM</label>
      <input type="number" id="filterDueKmMin" class="filter-input" placeholder="0" min="0">
    </div>
    
    <!-- Filter Actions -->
    <div class="filter-actions">
      <button class="btn-apply-filter" onclick="applyScheduleFilters()">
        <i class="bi bi-check-circle"></i> Apply Filters
      </button>
      <button class="btn-reset-filter" onclick="resetScheduleFilters()">
        <i class="bi bi-x-circle"></i> Reset
      </button>
    </div>
  </div>
</div>
`;

    // Insert filter panel after table header
    tableHeader.insertAdjacentHTML("afterend", filterPanelHTML);

    // Populate vehicle dropdown
    populateScheduleVehicleFilter();
}

// Populate vehicle filter dropdown
function populateScheduleVehicleFilter() {
    const filterVehicle = document.getElementById("filterVehicle");
    const vehicleCells = document.querySelectorAll(
        ".schedule-table .vehicle-cell span"
    );

    if (!filterVehicle || !vehicleCells.length) return;

    // Get unique vehicles
    const vehicles = new Set();
    vehicleCells.forEach((cell) => {
        vehicles.add(cell.textContent.trim());
    });

    // Add options to dropdown
    vehicles.forEach((vehicle) => {
        const option = document.createElement("option");
        option.value = vehicle;
        option.textContent = vehicle;
        filterVehicle.appendChild(option);
    });
}

// Apply filters to schedule table
function applyScheduleFilters() {
    const table = document.querySelector(".schedule-table tbody");
    if (!table) return;

    const rows = table.querySelectorAll("tr");
    let visibleCount = 0;

    // Get filter values
    const filters = {
        vehicle: document.getElementById("filterVehicle")?.value.toLowerCase() || "",
        serviceType: document.getElementById("filterServiceType")?.value.toLowerCase() || "",
        status: document.getElementById("filterStatus")?.value.toLowerCase() || "",
        priority: document.getElementById("filterPriority")?.value.toLowerCase() || "",
        dateFrom: document.getElementById("filterDateFrom")?.value || "",
        dateTo: document.getElementById("filterDateTo")?.value || "",
        daysLeft: parseInt(document.getElementById("filterDaysLeft")?.value) || Infinity,
        dueKmMin: parseFloat(document.getElementById("filterDueKmMin")?.value) || 0,
    };

    // Filter each row
    rows.forEach((row) => {
        const vehicle =
            row.querySelector(".vehicle-cell span")?.textContent.toLowerCase() || "";
        const serviceType =
            row.querySelector(".service-type")?.textContent.toLowerCase() || "";
        const statusBadge =
            row.querySelector(".status-badge")?.textContent.toLowerCase() || "";
        const priorityBadge =
            row.querySelector(".priority-badge")?.textContent.toLowerCase() || "";
        const dateText = row.cells[2]?.textContent || "";
        const dueKmText = row.cells[3]?.textContent.replace(/[,km]/g, "") || "0";
        const daysLeftText = row.querySelector(".days-left")?.textContent || "";

        // Extract days left number
        const daysLeftMatch = daysLeftText.match(/(\d+)/);
        const daysLeftValue = daysLeftMatch ? parseInt(daysLeftMatch[1]) : 0;
        const isOverdue = daysLeftText.toLowerCase().includes("overdue");

        const dueKm = parseFloat(dueKmText);
        const rowDate = convertDateToISO(dateText);

        // Check all filters
        const matchesVehicle = !filters.vehicle || vehicle.includes(filters.vehicle);
        const matchesServiceType = !filters.serviceType || serviceType.includes(filters.serviceType);
        const matchesStatus = !filters.status || statusBadge.includes(filters.status);
        const matchesPriority = !filters.priority || priorityBadge.includes(filters.priority);
        const matchesDateFrom = !filters.dateFrom || rowDate >= filters.dateFrom;
        const matchesDateTo = !filters.dateTo || rowDate <= filters.dateTo;
        const matchesDaysLeft = isOverdue ?
            false :
            daysLeftValue <= filters.daysLeft;
        const matchesDueKm = dueKm >= filters.dueKmMin;

        const isVisible =
            matchesVehicle &&
            matchesServiceType &&
            matchesStatus &&
            matchesPriority &&
            matchesDateFrom &&
            matchesDateTo &&
            matchesDaysLeft &&
            matchesDueKm;

        row.style.display = isVisible ? "" : "none";
        if (isVisible) visibleCount++;
    });

    // Show message if no results
    showFilterResults(visibleCount);
}

// Convert date text to ISO format
function convertDateToISO(dateText) {
    const months = {
        Jan: "01",
        Feb: "02",
        Mar: "03",
        Apr: "04",
        May: "05",
        Jun: "06",
        Jul: "07",
        Aug: "08",
        Sep: "09",
        Oct: "10",
        Nov: "11",
        Dec: "12",
    };

    const parts = dateText.split(" ");
    if (parts.length !== 3) return "";

    const month = months[parts[0]];
    const day = parts[1].replace(",", "").padStart(2, "0");
    const year = parts[2];

    return `${year}-${month}-${day}`;
}

// Show filter results message
function showFilterResults(count) {
    // Remove existing message
    const existingMsg = document.querySelector(".filter-result-message");
    if (existingMsg) existingMsg.remove();

    // Create new message
    const filterPanel = document.getElementById("filterPanel");
    if (!filterPanel) return;

    const message = document.createElement("div");
    message.className = "filter-result-message";
    message.style.cssText = `
padding: 1rem;
margin-top: 1rem;
background: var(--bg-card);
border: 2px solid var(--accent-color);
border-radius: 10px;
color: var(--accent-color);
font-weight: 600;
text-align: center;
`;
    message.innerHTML = `<i class="bi bi-funnel-fill me-2"></i>Showing ${count} schedule(s)`;

    filterPanel.appendChild(message);
}

// Reset all filters
function resetScheduleFilters() {
    // Reset all filter inputs
    document.getElementById("filterVehicle").value = "";
    document.getElementById("filterServiceType").value = "";
    document.getElementById("filterStatus").value = "";
    document.getElementById("filterPriority").value = "";
    document.getElementById("filterDateFrom").value = "";
    document.getElementById("filterDateTo").value = "";
    document.getElementById("filterDaysLeft").value = "";
    document.getElementById("filterDueKmMin").value = "";

    // Show all rows
    const rows = document.querySelectorAll(".schedule-table tbody tr");
    rows.forEach((row) => {
        row.style.display = "";
    });

    // Remove filter result message
    const message = document.querySelector(".filter-result-message");
    if (message) message.remove();
}

// Export schedule table data to CSV
function exportToCSV() {
    const table = document.querySelector(".schedule-table");
    if (!table) return;

    let csv = [];
    const rows = table.querySelectorAll("tr");

    rows.forEach((row, index) => {
        const cols = row.querySelectorAll("td, th");
        const csvRow = [];

        cols.forEach((col, colIndex) => {
            // Skip Actions column (last column)
            if (colIndex === cols.length - 1 && index !== 0) return;

            let cellText = col.textContent.trim();
            // Clean up text
            cellText = cellText.replace(/\s+/g, " ");
            // Escape quotes
            cellText = cellText.replace(/"/g, '""');
            // Wrap in quotes
            csvRow.push(`"${cellText}"`);
        });

        csv.push(csvRow.join(","));
    });

    // Create CSV file
    const csvContent = csv.join("\n");
    const blob = new Blob([csvContent], {
        type: "text/csv;charset=utf-8;"
    });
    const link = document.createElement("a");
    const url = URL.createObjectURL(blob);

    link.setAttribute("href", url);
    link.setAttribute("download", `maintenance_schedule_${Date.now()}.csv`);
    link.style.visibility = "hidden";

    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    // Show success message
    showExportMessage("CSV file exported successfully!");
}

// Export using print dialog (PDF)
function exportToPDF() {
    window.print();
}

// Show export success message
function showExportMessage(message) {
    const alertDiv = document.createElement("div");
    alertDiv.className =
        "alert text-center text-success border-1 rounded-3 border-success my-3 ms-auto me-auto d-flex align-items-center justify-content-center gap-2";
    alertDiv.style.cssText = `
position: fixed;
top: 20px;
right: 20px;
z-index: 9999;
max-width: 400px;
animation: slideIn 0.3s ease-out;
`;
    alertDiv.innerHTML = `
<i class="bi bi-check-circle-fill"></i>
${message}
`;

    document.body.appendChild(alertDiv);

    // Auto remove after 3 seconds
    setTimeout(() => {
        alertDiv.style.animation = "slideOut 0.3s ease-out";
        setTimeout(() => alertDiv.remove(), 300);
    }, 3000);
}

// Create export dropdown menu
function createExportDropdown() {
    const exportBtn = document.getElementById("exportBtn");
    if (!exportBtn) return;

    // Remove default click event
    exportBtn.onclick = null;

    // Create dropdown menu
    const dropdown = document.createElement("div");
    dropdown.className = "export-dropdown";
    dropdown.style.cssText = `
position: absolute;
top: 100%;
right: 0;
margin-top: 0.5rem;
background: var(--bg-card);
border: 2px solid var(--border-color);
border-radius: 10px;
box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
display: none;
z-index: 1000;
min-width: 180px;
`;

    dropdown.innerHTML = `
<button class="export-option" onclick="exportToCSV()">
  <i class="bi bi-filetype-csv"></i>
  Export as CSV
</button>
<button class="export-option" onclick="exportToPDF()">
  <i class="bi bi-filetype-pdf"></i>
  Export as PDF
</button>
`;

    // Add styles for dropdown options
    const style = document.createElement("style");
    style.textContent = `
.export-option {
  width: 100%;
  padding: 0.875rem 1.25rem;
  background: transparent;
  border: none;
  color: var(--text-primary);
  font-weight: 600;
  font-size: 0.9rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  transition: all 0.3s ease;
  text-align: left;
}

.export-option:hover {
  background: var(--bg-secondary);
  color: var(--accent-color);
}

.export-option i {
  font-size: 1.1rem;
}

.export-option:first-child {
  border-radius: 8px 8px 0 0;
}

.export-option:last-child {
  border-radius: 0 0 8px 8px;
}
`;
    document.head.appendChild(style);

    // Make export button container relative
    exportBtn.parentElement.style.position = "relative";
    exportBtn.parentElement.appendChild(dropdown);

    // Toggle dropdown on click
    exportBtn.addEventListener("click", (e) => {
        e.stopPropagation();
        dropdown.style.display =
            dropdown.style.display === "none" ? "block" : "none";
    });

    // Close dropdown when clicking outside
    document.addEventListener("click", () => {
        dropdown.style.display = "none";
    });
}

// ========================================
// MAINTENANCE LIST - FILTER & EXPORT
// ========================================

// Create filter panel dynamically for maintenance list
function createMaintenanceFilterPanel() {
    const tableCard = document.querySelector(".maintenance-list-section .container .table-card");
    const tableHeader = document.querySelector(".maintenance-list-section .container .table-header");

    // Create filter panel HTML
    const filterPanelHTML = `
<div id="filterPanel" class="filter-panel active">
  <div class="filter-grid">
    <!-- Vehicle Filter -->
    <div class="filter-group">
      <label for="filterVehicle">Vehicle</label>
      <select id="filterVehicle" class="filter-input">
        <option value="">All Vehicles</option>
      </select>
    </div>
    
    <!-- Service Type Filter -->
    <div class="filter-group">
      <label for="filterServiceType">Service Type</label>
      <input type="text" id="filterServiceType" class="filter-input" placeholder="e.g., Oil Change">
    </div>
    
    <!-- Status Filter -->
    <div class="filter-group">
      <label for="filterStatus">Status</label>
      <select id="filterStatus" class="filter-input">
        <option value="">All Statuses</option>
        <option value="completed">Completed</option>
        <option value="pending">Pending</option>
        <option value="scheduled">Scheduled</option>
      </select>
    </div>
    
    <!-- Date Range Filter -->
    <div class="filter-group">
      <label for="filterDateFrom">Date From</label>
      <input type="date" id="filterDateFrom" class="filter-input">
    </div>
    
    <div class="filter-group">
      <label for="filterDateTo">Date To</label>
      <input type="date" id="filterDateTo" class="filter-input">
    </div>
    
    <!-- Cost Range Filter -->
    <div class="filter-group">
      <label for="filterCostMin">Min Cost</label>
      <input type="number" id="filterCostMin" class="filter-input" placeholder="0" min="0">
    </div>
    
    <div class="filter-group">
      <label for="filterCostMax">Max Cost</label>
      <input type="number" id="filterCostMax" class="filter-input" placeholder="10000" min="0">
    </div>
    
    <!-- Odometer Range Filter -->
    <div class="filter-group">
      <label for="filterOdometerMin">Min Odometer</label>
      <input type="number" id="filterOdometerMin" class="filter-input" placeholder="0" min="0">
    </div>

    <div class="filter-group">
      <label for="filterOdometerMax">Max Odometer</label>
      <input type="number" id="filterOdometerMax" class="filter-input" placeholder="100000" min="0">
    </div>

    <!-- Filter Actions -->
    <div class="filter-actions">
      <button class="btn-apply-filter" onclick="applyMaintenanceFilters()">
        <i class="bi bi-check-circle"></i> Apply Filters
      </button>
      <button class="btn-reset-filter" onclick="resetMaintenanceFilters()">
        <i class="bi bi-x-circle"></i> Reset
      </button>
    </div>
  </div>
</div>
`;

    // Insert filter panel after table header
    tableHeader.insertAdjacentHTML("afterend", filterPanelHTML);

    // Populate vehicle dropdown
    populateMaintenanceVehicleFilter();
}

// Populate vehicle filter dropdown with unique vehicles from table
function populateMaintenanceVehicleFilter() {
    const filterVehicle = document.getElementById("filterVehicle");
    const vehicleCells = document.querySelectorAll(".vehicle-cell span");

    if (!filterVehicle || !vehicleCells.length) return;

    // Get unique vehicles
    const vehicles = new Set();
    vehicleCells.forEach((cell) => {
        vehicles.add(cell.textContent.trim());
    });

    // Add options to dropdown
    vehicles.forEach((vehicle) => {
        const option = document.createElement("option");
        option.value = vehicle;
        option.textContent = vehicle;
        filterVehicle.appendChild(option);
    });
}

// Apply filters to maintenance table
function applyMaintenanceFilters() {
    const table = document.querySelector(".maintenance-table tbody");
    if (!table) return;

    const rows = table.querySelectorAll("tr");
    let visibleCount = 0;

    // Get filter values
    const filters = {
        vehicle: document.getElementById("filterVehicle")?.value.toLowerCase() || "",
        serviceType: document.getElementById("filterServiceType")?.value.toLowerCase() || "",
        status: document.getElementById("filterStatus")?.value.toLowerCase() || "",
        dateFrom: document.getElementById("filterDateFrom")?.value || "",
        dateTo: document.getElementById("filterDateTo")?.value || "",
        costMin: parseFloat(document.getElementById("filterCostMin")?.value) || 0,
        costMax: parseFloat(document.getElementById("filterCostMax")?.value) || Infinity,
        odometerMin: parseFloat(document.getElementById("filterOdometerMin")?.value) || 0,
        odometerMax: parseFloat(document.getElementById("filterOdometerMax")?.value) || Infinity,
    };

    // Filter each row
    rows.forEach((row) => {
        const vehicle =
            row.querySelector(".vehicle-cell span")?.textContent.toLowerCase() || "";
        const serviceType =
            row.querySelector(".service-type")?.textContent.toLowerCase() || "";
        const statusBadge =
            row.querySelector(".status-badge")?.textContent.toLowerCase() || "";
        const dateText = row.cells[2]?.textContent || "";
        const costText =
            row.querySelector(".cost-cell")?.textContent.replace(/[₹,]/g, "") || "0";
        const odometerText = row.cells[3]?.textContent.replace(/[,km]/g, "") || "0";

        const cost = parseFloat(costText);
        const odometer = parseFloat(odometerText);
        const rowDate = convertDateToISO(dateText);

        // Check all filters
        const matchesVehicle = !filters.vehicle || vehicle.includes(filters.vehicle);
        const matchesServiceType = !filters.serviceType || serviceType.includes(filters.serviceType);
        const matchesStatus = !filters.status || statusBadge.includes(filters.status);
        const matchesDateFrom = !filters.dateFrom || rowDate >= filters.dateFrom;
        const matchesDateTo = !filters.dateTo || rowDate <= filters.dateTo;
        const matchesCostMin = cost >= filters.costMin;
        const matchesCostMax = cost <= filters.costMax;
        const matchesOdometerMin = odometer >= filters.odometerMin;
        const matchesOdometerMax = odometer <= filters.odometerMax;

        const isVisible =
            matchesVehicle &&
            matchesServiceType &&
            matchesStatus &&
            matchesDateFrom &&
            matchesDateTo &&
            matchesCostMin &&
            matchesCostMax &&
            matchesOdometerMin &&
            matchesOdometerMax;

        row.style.display = isVisible ? "" : "none";
        if (isVisible) visibleCount++;
    });

    // Show message if no results
    showMaintenanceFilterResults(visibleCount);
}

// Show maintenance filter results message
function showMaintenanceFilterResults(count) {
    // Remove existing message
    const existingMsg = document.querySelector(".filter-result-message");
    if (existingMsg) existingMsg.remove();

    // Create new message
    const filterPanel = document.getElementById("filterPanel");
    if (!filterPanel) return;

    const message = document.createElement("div");
    message.className = "filter-result-message";
    message.style.cssText = `
padding: 1rem;
margin-top: 1rem;
background: var(--bg-card);
border: 2px solid var(--accent-color);
border-radius: 10px;
color: var(--accent-color);
font-weight: 600;
text-align: center;
`;
    message.innerHTML = `<i class="bi bi-funnel-fill me-2"></i>Showing ${count} record(s)`;

    filterPanel.appendChild(message);
}

// Reset all maintenance filters
function resetMaintenanceFilters() {
    // Reset all filter inputs
    document.getElementById("filterVehicle").value = "";
    document.getElementById("filterServiceType").value = "";
    document.getElementById("filterStatus").value = "";
    document.getElementById("filterDateFrom").value = "";
    document.getElementById("filterDateTo").value = "";
    document.getElementById("filterCostMin").value = "";
    document.getElementById("filterCostMax").value = "";
    document.getElementById("filterOdometerMin").value = "";
    document.getElementById("filterOdometerMax").value = "";

    // Show all rows
    const rows = document.querySelectorAll(".maintenance-table tbody tr");
    rows.forEach((row) => {
        row.style.display = "";
    });

    // Remove filter result message
    const message = document.querySelector(".filter-result-message");
    if (message) message.remove();
}

// Initialize on page load
document.addEventListener("DOMContentLoaded", () => {
    // Create export dropdown if export button exists
    if (document.getElementById("exportBtn")) {
        createExportDropdown();
    }

    // Add CSS animations
    const animStyle = document.createElement("style");
    animStyle.textContent = `
@keyframes slideIn {
  from {
    transform: translateX(100%);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

@keyframes slideOut {
  from {
    transform: translateX(0);
    opacity: 1;
  }
  to {
    transform: translateX(100%);
    opacity: 0;
  }
}
`;
    document.head.appendChild(animStyle);
});
