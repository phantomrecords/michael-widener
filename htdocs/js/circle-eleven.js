
document.addEventListener("DOMContentLoaded", function () {
  const topImage = document.getElementById("va-nes-top-image");
  const bottomImage = document.querySelector("#va-nes .bottom");

  if (topImage && bottomImage) {
    topImage.addEventListener("mouseover", function () {
      topImage.style.opacity = "0";
    });

    topImage.addEventListener("mouseout", function () {
      topImage.style.opacity = "1";
    });
  }
});
