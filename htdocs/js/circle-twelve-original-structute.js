$(document).ready(function(){
    $("#circle-twelve-top-image").click(function(){
        $("#animated-image-twelve img.top").toggleClass("transparent");
        $("#soundtrack1")[0].play();
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
    });

    $("#circle-twelve-top-image").dblclick(function(){
        $("#soundtrack1")[0].pause();
    });
});
