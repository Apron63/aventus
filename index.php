<?php

?>
<html lang="ru">
<head>
    <title>Test</title>
    <link
            rel="stylesheet"
            href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css"
            integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm"
            crossorigin="anonymous"
    >
    <script
            src="https://code.jquery.com/jquery-3.6.0.min.js"
            integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4="
            crossorigin="anonymous">
    </script>
    <script
            src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"
            integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q"
            crossorigin="anonymous">

    </script>
    <script
            src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"
            integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl"
            crossorigin="anonymous">

    </script>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.7.1/css/bootstrap-datepicker.min.css"
          rel="stylesheet"/>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.7.1/js/bootstrap-datepicker.min.js"></script>

</head>
<body>

<div class="container">
    <div class="row">
        <input name="date" id="outputDate" class="col-sm-2" placeholder="Укажите дату">
        <select id="sort" class="col-sm-2">
            <option value="1">Наименование</option>
            <option value="2">Дата</option>
            <option value="3">Кось мось</option>
            <option>Пункт 2</option>
        </select>
        <button id="get-data" class="col-sm-2">Вывести</button>
    </div>
    <hr>
    <div class="row" id="output">

    </div>
</div>

<div class="modal fade" id="moreInfoModal" tabindex="-1" role="dialog" aria-labelledby="moreInfoModalTitle"
     aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="moreInfoModalLongTitle">Расширенная информация</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="movie-name"></div>
                <div id="image">
                    <img src="">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>

    let BASE_URL = 'http://www.world-art.ru/cinema/';
    let IMAGE_PATH = 'image';

    window.onload = function () {

        requestData(null, 1);

        $("#get-data").on("click", function () {
            let outputDate = $("#outputDate").val();
            requestData(outputDate);
        })

        $("#outputDate").datepicker({
            format: 'dd.mm.yyyy',
            uiLibrary: 'bootstrap4',
            weekStart: 1,
            daysOfWeekHighlighted: "6,0",
            modal: true,
            footer: true,
            autoclose: true,
            todayHighlight: true,
        });
    }

    function requestData(outputDate, sort) {
        $.ajax({
            url: "get-data.php",
            data: {date: outputDate, sort: sort}
        }).done(function (data) {
            drawTop(data);
        }).fail(function () {
            console.log("fail");
        });
    }

    function drawTop(data) {
        let result = JSON.parse(data);
        let output = '';
        result.forEach(function (el) {
            output = output
                + "<div class='card' style='width: 18rem;'>"
                + "<div class='card-body'>"
                + "<button class='btn btn-primary more-info' data-movie-id='" + el.movieId + "'"
                + " data-image='/" + IMAGE_PATH + "/" + el.image + "'"
                + "'>" + el.name + "</button>"
                + "</div>"
                + "</div>"
            ;
        });
        $("#output").html(output);
    }

    $("#output").on("click", function (e) {
        if ($(e.target).hasClass("more-info")) {
            let id = $(e.target).attr("data-movie-id");
            let image = $(e.target).attr("data-image");
            $("#movie-name").html(id);
            $("#image").find("img").attr("src", image);
            $("#moreInfoModal").modal("show");
        }
    })

</script>
</body>
</html>
