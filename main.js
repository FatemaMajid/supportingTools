function get_Activities() {
    var date = document.getElementById('date').value;

    var xhr = new XMLHttpRequest();
    xhr.onreadystatechange = function() {
        if (xhr.readyState === XMLHttpRequest.DONE) {
            if (xhr.status === 200) {
                document.getElementById('suggested_activities').innerHTML = xhr.responseText;
            } else {
                alert('There was a problem with the request.');
            }
        }
    };

    xhr.open('GET', 'get_activities.php?date=' + date, true);
    xhr.send();
}

function addActivityToField(activityName) {
    document.getElementById('activity_name').value = activityName;
}
// Populate activity_name select options based on the date selected
function populateActivityNameSelect() {
    var date = document.getElementById('date').value;

    var xhr = new XMLHttpRequest();
    xhr.onreadystatechange = function() {
        if (xhr.readyState === XMLHttpRequest.DONE) {
            if (xhr.status === 200) {
                document.getElementById('activity_name').innerHTML = xhr.responseText;
            } else {
                alert('There was a problem with the request.');
            }
        }
    };

    xhr.open('GET', 'get_activities.php?date=' + date, true);
    xhr.send();
}

// Populate suggested activities list based on the date selected
function populateSuggestedActivities() {
    var date = document.getElementById('date').value;

    var xhr = new XMLHttpRequest();
    xhr.onreadystatechange = function() {
        if (xhr.readyState === XMLHttpRequest.DONE) {
            if (xhr.status === 200) {
                document.getElementById('suggested_activities').innerHTML = xhr.responseText;
            } 
        }
    };

    xhr.open('GET', 'get_activities.php?date=' + date, true);
    xhr.send();
}

// Add click event listeners to suggested activity items
document.getElementById('suggested_activities').addEventListener('click', function(event) {
    if (event.target.tagName === 'LI') {
        var activityName = event.target.textContent;
        addActivityToField(activityName);
    }
});

// Populate activity_name select options on page load
window.addEventListener('load', populateActivityNameSelect);