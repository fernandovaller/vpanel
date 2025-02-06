$(document).on('show.bs.modal', '#modalCreate', function (event) {
    const button = event.relatedTarget;

    let route = button.getAttribute('data-route');
    let target = button.getAttribute('data-target');

    $.get(route, function (data) {
        $(target).html(data ?? '');
    });
});

$(document).on('show.bs.modal', '#modalEdit', function (event) {
    const button = event.relatedTarget;

    let route = button.getAttribute('data-route');
    let target = button.getAttribute('data-target');

    $.get(route, function (data) {
        $(target).html(data ?? '');
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