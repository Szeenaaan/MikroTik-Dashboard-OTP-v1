document.addEventListener('DOMContentLoaded', function () {

    const rows = document.querySelectorAll('.users-table tbody tr');

    rows.forEach(row => {

        const downloadCell = row.cells[4];
        const uploadCell = row.cells[5];

        if (downloadCell) {
            downloadCell.textContent = formatBytes(
                Number(downloadCell.textContent.trim())
            );
        }

        if (uploadCell) {
            uploadCell.textContent = formatBytes(
                Number(uploadCell.textContent.trim())
            );
        }

    });

});