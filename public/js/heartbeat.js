const heartbeatInterval = 5000;

function forceLogin() {
    window.location.replace("/login");
}

function dashboardHeartbeat() {
    if (!navigator.onLine) {
        forceLogin();
        return;
    }

    fetch("/dashboard/heartbeat", {
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": document
                .querySelector('meta[name="csrf-token"]')
                .getAttribute("content"),
            "Accept": "application/json"
        },
        credentials: "same-origin",
        cache: "no-store"
    })
        .then(response => {
            if (!response.ok) {
                forceLogin();
            }
        })
        .catch(() => {
            forceLogin();
        });
}

window.addEventListener("offline", function () {
    forceLogin();
});

window.addEventListener("online", function () {
    forceLogin();
});

window.addEventListener("pageshow", function (event) {
    if (event.persisted) {
        forceLogin();
    }
});

dashboardHeartbeat();

setInterval(dashboardHeartbeat, heartbeatInterval);