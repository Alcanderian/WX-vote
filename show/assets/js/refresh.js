var first;
var refresh_id;

var refresh = function () {
    $.ajax({
        type: "post",
        data: {
            first: first
        },
        url: "./load_vote.php",
        success: function (msg) {

            var data = JSON.parse(msg);
            console.log(data);

            if (data["status"] == true) {

                for (var i = 0; i < data["msg"].length; ++i) {
                    if (first == true) {
                        $("#team" + data["msg"][i]["tid"])
                            .html(data["msg"][i]["name"] + " - &lt;" + data["msg"][i]["work"] + "&gt;");
                    }

                    $("#vote" + data["msg"][i]["tid"])
                        .html(data["msg"][i]["voted"]);

                    $("#group" + data["msg"][i]["tid"])
                        .attr("style", "width: " + data["msg"][i]["percent"] + "%");
                }

                first = false;

            } else if (data["status"] == false) {

                alert(data["msg"]);
                clearInterval(refresh_id);

            }
        },
        error: function () {

            alert("Cannot connect server");
            clearInterval(refresh_id);
        }
    });
};


$(document).ready(function () {
    $("#list-container").attr("style", "display: block;");
    for (var i = 1; i <= 10; ++i) {
        $("#list").append(
            "<div>" +
                "<div class='row'>" +
                    "<div class='col-sm-8'>" +
                        "<div class='label form-control' id='team" + i + "'></div>" +
                    "</div>" +
                    "<div class='col-sm-4 text-right'>" +
                        "<div class='label' id='vote" + i + "'>&nbsp;</div>" +
                    "</div>" +
                "</div>" +
                "<div class='progress'>" +
                    "<div class='progress-bar progress-bar-warning progress-bar-striped active'" +
                        " id='group" + i + "'" +
                        "aria-valuemin='0' aria-valuemax='100' style='width: 50%'>" +
                    "</div>" +
                "</div>" +
            "</div>"
        )
    }
    first = true;
    refresh_id = setInterval(refresh, 1000);
});
