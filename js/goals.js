function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `${message}`;
    document.querySelector('.container').insertBefore(alertDiv, document.querySelector('.container').firstChild);
}

function showMilestoneNotification(message) {
    const notification = document.createElement('div');
    notification.className = 'alert alert-success alert-dismissible fade show milestone-alert';
    notification.innerHTML = `
        <i class="fas fa-trophy"></i> Felicitări!
        ${message}
    `;
    const container = document.querySelector('.container');
    container.insertBefore(notification, container.firstChild);
    setTimeout(() => notification.classList.add('show-notification'), 100);
    setTimeout(() => {
        notification.classList.remove('show-notification');
        setTimeout(() => notification.remove(), 300);
    }, 5000);
}

function updateMilestoneProgress(goalId, currentAmount) {
    const progressContainer = document.querySelector(`#goal-${goalId} .progress-container`);
    const milestoneMarkers = progressContainer.querySelectorAll('.milestone-marker');
    const messagesContainer = progressContainer.querySelector('.milestone-messages');

    milestoneMarkers.forEach(marker => {
        const amount = parseFloat(marker.getAttribute('data-amount').replace(/[^0-9.,]/g, '').replace(',', '.'));
        if (currentAmount >= amount && !marker.classList.contains('completed')) {
            marker.classList.add('completed');
            const messages = [
                `Felicitări! Ai atins milestone-ul de ${marker.getAttribute('data-amount')}! Continuă tot așa!`,
                "Bravo! Încă un pas spre obiectivul tău! Keep pushing!",
                "Fantastic! Ești mai aproape de țelul tău! Nu te opri aici!",
                "Amazing progress! Fiecare milestone te aduce mai aproape de vis!",
                "Superb! Determinarea ta dă roade! Înainte spre următorul milestone!"
            ];
            const messageDiv = document.createElement('div');
            messageDiv.className = 'milestone-message';
            messageDiv.innerHTML = `<i class="fas fa-star"></i> ${messages[Math.floor(Math.random() * messages.length)]}`;
            if (messagesContainer) {
                messagesContainer.insertBefore(messageDiv, messagesContainer.firstChild);
            }
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    // Actualizăm milestone-urile când se adaugă o contribuție
    document.querySelectorAll('.contribution-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const goalId = this.querySelector('[name="goal_id"]').value;
            const currentAmount = parseFloat(this.querySelector('.goal-summary').textContent.match(/Progres actual: ([0-9.,]+)/)[1].replace(',', '.'));
            const addAmount = parseFloat(this.querySelector('[name="amount"]').value);
            setTimeout(() => updateMilestoneProgress(goalId, currentAmount + addAmount), 100);
        });
    });

    // Actualizăm milestone-urile când se face o retragere
    document.querySelectorAll('.withdrawal-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const goalId = this.querySelector('[name="goal_id"]').value;
            const currentAmount = parseFloat(this.querySelector('.goal-summary').textContent.match(/Sumă acumulată: ([0-9.,]+)/)[1].replace(',', '.'));
            const withdrawAmount = parseFloat(this.querySelector('[name="amount"]').value);
            setTimeout(() => updateMilestoneProgress(goalId, currentAmount - withdrawAmount), 100);
        });
    });

    // Inițializăm tooltips pentru milestone markers
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

function addMilestone() {
    const container = document.getElementById('milestones-container');
    const newEntry = document.createElement('div');
    newEntry.className = 'milestone-entry d-flex gap-2 mb-2';
    newEntry.innerHTML = `
        <input type="number" name="milestone_amounts[]" class="form-control" placeholder="Sumă (RON)" step="0.01" min="0" required>
        <input type="text" name="milestone_descriptions[]" class="form-control" placeholder="Descriere (opțional)">
        <button type="button" class="btn btn-danger" onclick="removeMilestone(this)"><i class="fas fa-trash"></i></button>
    `;
    container.appendChild(newEntry);
}

function removeMilestone(button) {
    button.closest('.milestone-entry').remove();
}

function addMilestoneEdit(goalId) {
    const container = document.getElementById(`milestones-container-${goalId}`);
    const newEntry = document.createElement('div');
    newEntry.className = 'milestone-entry d-flex gap-2 mb-2 align-items-center';
    newEntry.innerHTML = `
        <input type="number" name="milestone_amounts[]" class="form-control" placeholder="Sumă (RON)" step="0.01" min="0" required>
        <input type="text" name="milestone_descriptions[]" class="form-control" placeholder="Descriere (opțional)">
        <button type="button" class="btn btn-danger" onclick="removeMilestoneEdit(this, ${goalId})"><i class="fas fa-trash"></i></button>
    `;
    container.appendChild(newEntry);
}

function removeMilestoneEdit(button, goalId) {
    button.closest('.milestone-entry').remove();
}

function validateEditForm(form) {
    const goalId = form.querySelector('[name="goal_id"]').value;
    const targetAmount = parseFloat(form.querySelector('[name="target_amount"]').value);
    const currentAmount = parseFloat(form.querySelector('[name="current_amount"]').value);
    const milestoneAmounts = Array.from(form.querySelectorAll('[name="milestone_amounts[]"]'))
        .map(input => parseFloat(input.value))
        .filter(amount => !isNaN(amount));

    if (currentAmount > targetAmount) {
        alert('Suma acumulată nu poate depăși suma țintă!');
        return false;
    }

    for (let i = 1; i < milestoneAmounts.length; i++) {
        if (milestoneAmounts[i] <= milestoneAmounts[i-1]) {
            alert('Milestone-urile trebuie să fie în ordine crescătoare!');
            return false;
        }
    }

    if (milestoneAmounts.some(amount => amount > targetAmount)) {
        alert('Milestone-urile nu pot depăși suma țintă!');
        return false;
    }

    return true;
}

function scrollToGoal(goalId) {
    const goalElement = document.getElementById('goal-' + goalId);
    if (goalElement) {
        goalElement.scrollIntoView({ behavior: 'smooth' });
        goalElement.classList.add('highlight-goal');
        setTimeout(() => {
            goalElement.classList.remove('highlight-goal');
        }, 2000);
    }
}

function triggerConfetti() {
    var duration = 1500;
    var end = Date.now() + duration;
    (function frame() {
        confetti({
            particleCount: 7,
            angle: 60,
            spread: 55,
            origin: { x: 0 }
        });
        confetti({
            particleCount: 7,
            angle: 120,
            spread: 55,
            origin: { x: 1 }
        });
        if (Date.now() < end) {
            requestAnimationFrame(frame);
        }
    }());
}

// Adăugăm stiluri pentru animații
const style = document.createElement('style');
style.textContent = `
    .milestone-alert {
        transform: translateY(-20px);
        opacity: 0;
        transition: all 0.3s ease;
    }
    .milestone-alert.show-notification {
        transform: translateY(0);
        opacity: 1;
    }
    .milestone-marker {
        transition: background-color 0.3s ease;
    }
    .milestone-item {
        transition: background-color 0.3s ease;
    }
`;
document.head.appendChild(style);

function deleteGoal(goalId) {
    if (confirm('Ești sigur că vrei să ștergi acest obiectiv?')) {
        // Create form data
        const formData = new FormData();
        formData.append('goal_id', goalId);

        // Send delete request
        fetch('delete_goal.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(() => {
            // Find and remove the goal card
            const goalCard = document.getElementById(`goal-card-${goalId}`);
            if (goalCard) {
                goalCard.remove();
                
                // Show success message
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-success alert-dismissible fade show';
                alertDiv.innerHTML = `
                    Obiectivul a fost șters cu succes!
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                document.querySelector('.container').insertBefore(alertDiv, document.querySelector('.container').firstChild);
                
                // Reload page after 1 second to update statistics
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-danger alert-dismissible fade show';
            alertDiv.innerHTML = `
                A apărut o eroare la ștergerea obiectivului.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            document.querySelector('.container').insertBefore(alertDiv, document.querySelector('.container').firstChild);
        });
    }
}