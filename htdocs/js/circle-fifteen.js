
document.getElementById('circle-fifteen-top-image').addEventListener('click', function () {
  var bottomImage = this.previousElementSibling;
  if (bottomImage.style.display === 'none' || bottomImage.style.display === '') {
    bottomImage.style.display = 'block';
  } else {
    bottomImage.style.display = 'none';
  }
});
