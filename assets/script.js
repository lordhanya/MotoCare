//Navbar navigation links active state handling
let navLinks = document.querySelectorAll("header nav .nav-link");
let sections = document.querySelectorAll("section");

window.onscroll = () => {
  sections.forEach((section) => {
    let top = window.scrollY;
    let offset = section.offsetTop - 150;
    let height = section.offsetHeight;
    let id = section.getAttribute("id");

    if (top >= offset && top < offset + height) {
      navLinks.forEach((links) => {
        links.classList.remove("active");
        document
          .querySelector("header nav a[href*=" + id + "]")
          ?.classList.add("active");
      });
    }
  });
};

// Sidebar Items navigation active state handling
let sidebarLinks = document.querySelectorAll(".offcanvas-body .sidebarItems .sidebarLink");

window.onload = () => {
  const currentPage = window.location.pathname.split("/").pop(); 
  sidebarLinks.forEach((itemLink) => {
    itemLink.classList.remove("active");

    const linkPage = itemLink.getAttribute("href");

    if (linkPage === currentPage) {
      itemLink.parentElement.classList.add("active");
    } else {
      itemLink.parentElement.classList.remove("active");
    }
  });
};

