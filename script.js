/**
 * Skrypt odpowiedzialny za obsługę logowania i generowanie raportu.
 */

// Zmienna określająca stan zalogowania użytkownika
var loggedIn = false;

// Funkcja wywoływana po załadowaniu dokumentu
$(document).ready(function() {
    // Obsługa zdarzenia kliknięcia przycisku "Relogin"
    $('#relogin').click(function(event) {
        event.preventDefault();
        loggedIn = false;
        toggleVisibility();
    });

    // Obsługa zdarzenia kliknięcia przycisku "Zaloguj"
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

        // Wysłanie żądania AJAX do serwera w celu zalogowanie uzytkownika i zwrocenia tokenow
        $.ajax({
            url: './raports/index.php',
            method: 'POST',
            data: loginData,
            success: function(response) {
                var success = JSON.parse(response);
                if(success.success == 1) {
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

    // Obsługa zdarzenia kliknięcia przycisku "Generuj raport"
    $('#btn').click(function(event) {
        event.preventDefault(); // Zapobiega wysłaniu formularza

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

        // Wysłanie żądania AJAX do serwera w celu generowania raportu
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

    // Funkcja odpowiedzialna za przełączanie widoczności kontenerów logowania i raportu
    function toggleVisibility() {
        if (loggedIn) {
            $('#login-container').css('display', 'none');
            $('#raport-container').css('display', 'flex');
        } else {
            $('#login-container').css('display', 'flex');
            $('#raport-container').css('display', 'none');
        }
    }

    // Inicjalna konfiguracja widoczności
    toggleVisibility();
});
