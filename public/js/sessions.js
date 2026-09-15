document.addEventListener('DOMContentLoaded', function () {

    const rows = document.querySelectorAll('.sessions-table tbody tr');

    rows.forEach(row => {

        const downloadCell = row.cells[5];
        const uploadCell = row.cells[6];

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
document.addEventListener('DOMContentLoaded', function () {

    const rows = document.querySelectorAll('.sessions-table tbody tr');

    rows.forEach(row => {

        const sessionTimeCell = row.cells[7];

        if (sessionTimeCell) {
            sessionTimeCell.textContent = formatSessionTime(
                Number(sessionTimeCell.textContent.trim())
            );
        }

    });

});