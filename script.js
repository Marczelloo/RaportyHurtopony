var loggedIn = false;

$(document).ready(function() {
    $('#relogin').click(function(event) {
        event.preventDefault();
        loggedIn = false;
        toggleVisibility();
    });

    $('#loginBtn').click(function(event){
        event.preventDefault();
        var login = $('#login').val();
        var haslo = $('#haslo').val();

        console.log(login, haslo);

        var loginData = {
            request: 'getToken',
            login: login,
            haslo: haslo
        };

        $.ajax({
            url: 'index.php',
            method: 'POST',
            data: loginData,
            success: function(response) {
                var success = JSON.parse(response);
                if(success.success == 1) {
                    console.log(response);
                    console.log(success);
                    loggedIn = true;
                    $('#output').html(response);
                    toggleVisibility();
                } else {
                    console.log(response);
                    $('#output').html(response);
                }
            },
            error: function(xhr, status, error) {
                console.log('AJAX request error:', error);
            }
        });
    });

    $('#btn').click(function(event) {
        event.preventDefault(); // Prevent form submission

        var request = $('#request').val();
        var range = $('#range').val();
        var dateFrom = $('#dateFrom').val();
        var dateTo = $('#dateTo').val();
        var indeks = $('#indeks').val();
        var token = $('#token').val();

        if(range == "range"){
            range = null;
        }
        var data = {
            request: request,
            range: range,
            dateFrom: dateFrom,
            dateTo: dateTo,
            indeks: indeks,
            token: token
        };

        $.ajax({
            url: 'index.php',
            method: 'POST',
            data: data,
            success: function(response) {
                var success = JSON.parse(response);
                if(success.success == 1) {
                    loggedIn = true;
                    $('#output').html(response);
                    toggleVisibility();
                } else {
                    console.log(output);
                    $('#output').html(response);
                }
            },
            error: function(xhr, status, error) {
                console.log('AJAX request error:', error);
            }
        });
    });

    // Function to toggle the visibility of login and raport containers
    function toggleVisibility() {
        if (loggedIn) {
            $('#login-container').css('display', 'none');
            $('#raport-container').css('display', 'flex');
        } else {
            $('#login-container').css('display', 'flex');
            $('#raport-container').css('display', 'None');
        }
    }

    // Initial visibility setup
    toggleVisibility();
});