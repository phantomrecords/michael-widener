document.addEventListener("DOMContentLoaded", function () {
  const topImage = document.getElementById("circle-twelve-top-image");

  if (topImage) {
    topImage.addEventListener("click", function () {
      topImage.classList.toggle("transparent");
    });
  }
});