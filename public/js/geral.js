$(document).on('click', '.editModal', function (event) {
    event.preventDefault();

    let route = $(this).data('route');
    let target = $(this).data('target');
    let modal = $(`${target}`);

    $.get(route, function (data) {
        if (!data) {
            return;
        }

        modal.html(data);
    });

});

$(document).on('click', '.btnExcluir', function (event) {
    event.preventDefault();

    let route = $(this).data('route');
    let title = $(this).data('title');
    let msg = $(this).data('msg');

    Swal.fire({
        icon: 'question',
        title: `${title}`,
        text: `${msg}`,
        confirmButtonText: 'Sim',
        showCancelButton: true,
        cancelButtonText: 'Cancelar',
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = route;
        }
    });

});


$(document).on('click', '.btnConfirmar', function (event) {
    event.preventDefault();

    let route = $(this).data('route');
    let title = $(this).data('title');
    let msg = $(this).data('msg');

    Swal.fire({
        icon: 'info',
        title: `${title}`,
        text: `${msg}`,
        confirmButtonText: 'Sim',
        showCancelButton: true,
        cancelButtonText: 'Cancelar',
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = route;
        }
    });

});


// document.addEventListener('DOMContentLoaded', function () {
//     setTimeout(function () {
//         let alerts = document.querySelectorAll('.alert-dismissible');
//         alerts.forEach(function (alert) {
//             let bsAlert = new bootstrap.Alert(alert);
//             bsAlert.close();
//         });
//     }, 5000);
// });