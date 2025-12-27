$(document).ready(function(){
    $("#recording-studio-image-top").click(function(){
        $("#recording-studio img.top").toggleClass("transparent");
        $("#soundtrack13")[0].play();
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
        $("#soundtrack14")[0].pause();
    });

    $("#recording-studio-image-top").dblclick(function(){
        $("#soundtrack13")[0].pause();
    });
});
