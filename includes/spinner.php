<?php 
include __DIR__ . "/header.php";
?>

<div id="pageLoader">
  <div class="spinner-ring"></div>
</div>

<script>
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

</script>