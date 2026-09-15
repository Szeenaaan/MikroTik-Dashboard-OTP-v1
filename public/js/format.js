function formatBytes(bytes) {

    if (bytes < 1024) {
        return bytes + ' Bytes';
    }

    if (bytes < 1024 ** 2) {
        return (bytes / 1024).toFixed(2) + ' KB';
    }

    if (bytes < 1024 ** 3) {
        return (bytes / (1024 ** 2)).toFixed(2) + ' MB';
    }

    if (bytes < 1024 ** 4) {
        return (bytes / (1024 ** 3)).toFixed(2) + ' GB';
    }

    return (bytes / (1024 ** 4)).toFixed(2) + ' TB';
}


function formatSessionTime(seconds) {
    seconds = Number(seconds);

    const hours = Math.floor(seconds / 3600);
    seconds %= 3600;

    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = seconds % 60;

    if (hours > 0) {
        return hours + 'h ' + minutes + 'm ' + remainingSeconds + 's';
    }

    if (minutes > 0) {
        return minutes + 'm ' + remainingSeconds + 's';
    }

    return remainingSeconds + 's';
}


const totalDataElement = document.getElementById('totalData');
const downloadDataElement = document.getElementById('downloadData');
const uploadDataElement = document.getElementById('uploadData');

totalDataElement.textContent = formatBytes(Number(totalDataElement.textContent.trim()));
downloadDataElement.textContent = formatBytes(Number(downloadDataElement.textContent.trim()));
uploadDataElement.textContent = formatBytes(Number(uploadDataElement.textContent.trim()));