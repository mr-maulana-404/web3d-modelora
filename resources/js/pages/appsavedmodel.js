document.addEventListener('click', function (event) {
    // Cek apakah yang diklik adalah tombol delete (atau ikon di dalamnya)
    const btnDelete = event.target.closest('.btn-delete-model');
    
    if (btnDelete) {
        const id = btnDelete.dataset.id;
        const name = btnDelete.dataset.name;

        Swal.fire({
            title: 'Delete Model?',
            text: `Model "${name}" will be permanently deleted!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Delete!',
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById(`delete-form-${id}`).submit();
            }
        });
    }
});