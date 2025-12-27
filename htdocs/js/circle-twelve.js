$(document).ready(function () {
  $("#animated-image-twelve").click(function () {
    $("#circle-twelve-top-image").toggleClass("transparent");
    $("#soundtrack1")[0].play();
  });

  $("#animated-image-twelve").dblclick(function () {
    $("#soundtrack1")[0].pause();
  });
});
