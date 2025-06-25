// Funcție pentru a verifica și afișa avertismente pentru deadline-uri
function checkDeadlineWarnings() {
    fetch('check_deadlines.php')
        .then(response => response.json())
        .then(data => {
            const warningsContainer = document.getElementById('deadline-warnings');
            if (!warningsContainer) return;

            if (data.warnings && data.warnings.length > 0) {
                updateWarningsDisplay(data.warnings);
                showNotificationBadge(data.warnings.length);
            }
        })
        .catch(error => console.error('Eroare la verificarea deadline-urilor:', error));
}

// Funcție pentru a actualiza afișarea avertismentelor
function updateWarningsDisplay(warnings) {
    const container = document.getElementById('deadline-warnings');
    if (!container) return;

    warnings.forEach(warning => {
        const existingWarning = document.querySelector(`[data-goal-id="${warning.id}"]`);
        if (!existingWarning) {
            const warningElement = createWarningElement(warning);
            container.appendChild(warningElement);
        }
    });
}

// Funcție pentru a crea elementul de avertizare
function createWarningElement(warning) {
    const warningDiv = document.createElement('div');
    warningDiv.className = 'warning-alert';
    warningDiv.setAttribute('data-goal-id', warning.id);
    
    const progressPercent = (warning.current_amount / warning.target_amount) * 100;
    const remainingAmount = warning.target_amount - warning.current_amount;
    
    warningDiv.innerHTML = `
        <div class="d-flex justify-content-between align-items-center">
            <h6 class="mb-1">${warning.name}</h6>
            <span class="deadline-info">
                ${warning.days_remaining} zile rămase
            </span>
        </div>
        <p class="mb-1">
            Mai sunt necesari ${remainingAmount.toFixed(2)} RON
            până la ${new Date(warning.deadline).toLocaleDateString('ro-RO')}
        </p>
        <div class="progress">
            <div class="progress-bar bg-warning" 
                 role="progressbar" 
                 style="width: ${progressPercent}%"
                 aria-valuenow="${progressPercent}" 
                 aria-valuemin="0" 
                 aria-valuemax="100">
                ${Math.round(progressPercent)}%
            </div>
        </div>
    `;

    // Adăugăm animație de fade-in
    warningDiv.style.opacity = '0';
    setTimeout(() => {
        warningDiv.style.opacity = '1';
        warningDiv.style.transition = 'opacity 0.5s ease-in';
    }, 100);

    return warningDiv;
}

// Funcție pentru a afișa badge-ul de notificări în navbar
function showNotificationBadge(count) {
    const badge = document.getElementById('warnings-badge');
    if (badge) {
        badge.textContent = count;
        badge.style.display = count > 0 ? 'inline-block' : 'none';
    }
}

// Verificăm deadline-urile la fiecare 5 minute
document.addEventListener('DOMContentLoaded', function() {
    checkDeadlineWarnings();
    setInterval(checkDeadlineWarnings, 300000); // 5 minute
});

// Adăugăm stiluri pentru animații
const warningStyles = document.createElement('style');
warningStyles.textContent = `
    .warning-alert {
        transition: opacity 0.5s ease-in;
    }
    
    #warnings-badge {
        position: absolute;
        top: -8px;
        right: -8px;
        background-color: #dc3545;
        color: white;
        border-radius: 50%;
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
        display: none;
    }
`;
document.head.appendChild(warningStyles); 