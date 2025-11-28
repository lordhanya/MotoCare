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
