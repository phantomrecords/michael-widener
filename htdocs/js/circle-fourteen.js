$(document).ready(function(){
    $("#circle-fourteen-top-image").click(function(){
        $("#animated-image-fourteen img.top").toggleClass("transparent");
        $("#soundtrack14")[0].play();
        $("#soundtrack1")[0].pause();
        $("#soundtrack2")[0].pause();
        $("#soundtrack3")[0].pause();
        $("#soundtrack4")[0].pause();
        $("#soundtrack5")[0].pause();
        $("#soundtrack6")[0].pause();
        $("#soundtrack7")[0].pause();
        $("#soundtrack8")[0].pause();
        $("#soundtrack9")[0].pause();
        $("#soundtrack10")[0].pause();
        $("#soundtrack11")[0].pause();
        $("#soundtrack12")[0].pause();
        $("#soundtrack13")[0].pause();
    });

    $("#circle-fourteen-top-image").dblclick(function(){
        $("#soundtrack14")[0].pause();
    });
});
