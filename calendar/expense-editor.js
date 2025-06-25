let expenseModal = null;
let editExpenseModal = null;
let currentDate = null;

document.addEventListener('DOMContentLoaded', function() {
    console.log('Initializing expense editor...');
    
    // Initialize modals
    const expenseModalEl = document.getElementById('expenseModal');
    const editExpenseModalEl = document.getElementById('editExpenseModal');
    
    if (!expenseModalEl || !editExpenseModalEl) {
        console.error('Modal elements not found:', {
            expenseModal: expenseModalEl ? expenseModalEl.id : 'not found',
            editExpenseModal: editExpenseModalEl ? editExpenseModalEl.id : 'not found'
        });
        return;
    }
    
    try {
        // Create modal instances
        expenseModal = new bootstrap.Modal(expenseModalEl, {
            backdrop: 'static',
            keyboard: false
        });
        
        editExpenseModal = new bootstrap.Modal(editExpenseModalEl, {
            backdrop: 'static',
            keyboard: false
        });
        
        console.log('Modals initialized successfully:', {
            expenseModal: expenseModal ? 'created' : 'failed',
            editExpenseModal: editExpenseModal ? 'created' : 'failed'
        });
    } catch (error) {
        console.error('Error initializing modals:', error);
    }
});

function showExpenseModal(date) {
    console.log('Showing expense modal for date:', date);
    currentDate = date;
    document.getElementById('selectedDate').textContent = formatDate(date);
    document.getElementById('expenseDate').value = date;
    
    // Reset add form
    document.getElementById('expenseForm').reset();
    
    // Fetch existing expenses
    fetch(`../fetch_expenses.php?date=${date}`)
        .then(response => response.json())
        .then(expenses => {
            const expensesList = document.getElementById('expensesList');
            expensesList.innerHTML = '';
            
            if (expenses.length === 0) {
                expensesList.innerHTML = '<p class="text-muted">Nu există cheltuieli pentru această zi.</p>';
                return;
            }
            
            const table = document.createElement('table');
            table.className = 'table table-sm';
            table.innerHTML = `
                <thead>
                    <tr>
                        <th>Categorie</th>
                        <th>Sumă</th>
                        <th>Descriere</th>
                        <th>Acțiuni</th>
                    </tr>
                </thead>
                <tbody></tbody>
            `;
            
            expenses.forEach(expense => {
                const row = table.querySelector('tbody').insertRow();
                const description = expense.description.replace(/'/g, "\\'").replace(/"/g, '&quot;');
                row.innerHTML = `
                    <td>${expense.category_name}</td>
                    <td>${expense.amount} RON</td>
                    <td>${expense.description}</td>
                    <td>
                        <button type="button" class="btn btn-sm btn-primary me-1" onclick="editExpense(${expense.id}, '${expense.category_name}', ${expense.amount}, '${description}', '${expense.date}', ${expense.category_id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-danger" onclick="deleteExpense(${expense.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                `;
            });
            
            expensesList.appendChild(table);
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('expensesList').innerHTML = '<p class="text-danger">Eroare la încărcarea cheltuielilor.</p>';
        });
    
    expenseModal.show();
}

function editExpense(id, categoryName, amount, description, date, categoryId) {
    console.log('Editing expense:', { id, categoryName, amount, description, date, categoryId });
    
    // Populate edit form with the correct values
    document.getElementById('editExpenseId').value = id;
    document.getElementById('editExpenseCategory').value = categoryId;
    document.getElementById('editExpenseAmount').value = amount;
    document.getElementById('editExpenseDescription').value = description;
    document.getElementById('editExpenseDate').value = date;
    
    // Also update the display date field if it exists
    const dateDisplay = document.getElementById('editExpenseDateDisplay');
    if (dateDisplay) {
        dateDisplay.value = date;
    }
    
    // Hide expense modal and show edit modal
    if (expenseModal) {
        expenseModal.hide();
    }
    
    // Show the edit modal
    if (editExpenseModal) {
        editExpenseModal.show();
    } else {
        console.error('Edit expense modal not initialized');
    }
}

function updateExpense() {
    const form = document.getElementById('editExpenseForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const formData = new FormData(form);
    const expenseDate = formData.get('date');
    
    console.log('Updating expense with data:', Object.fromEntries(formData));

    fetch('../edit_expense.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(err => Promise.reject(err));
        }
        return response.json();
    })
    .then(data => {
        console.log('Update response:', data);
        if (data.success) {
            // Hide the edit modal
            if (editExpenseModal) {
                editExpenseModal.hide();
            }
            
            // Show the expense modal again with refreshed data
            showExpenseModal(expenseDate);
            
            // Refresh the specific calendar cell
            const calendarCell = document.querySelector(`.calendar-day[data-date="${expenseDate}"]`);
            if (calendarCell) {
                fetch(`../fetch_expenses.php?date=${expenseDate}`)
                    .then(response => response.json())
                    .then(expenses => {
                        // Remove existing expense divs
                        const existingEvents = calendarCell.querySelectorAll('.event, .expense-item');
                        existingEvents.forEach(event => event.remove());
                        
                        // Add updated expense divs
                        let total = 0;
                        expenses.forEach(expense => {
                            const eventDiv = document.createElement('div');
                            eventDiv.className = 'expense-item';
                            eventDiv.textContent = `${expense.category_name}: ${expense.amount} RON`;
                            calendarCell.appendChild(eventDiv);
                            total += parseFloat(expense.amount);
                        });
                        
                        // Update total if it exists
                        const existingTotal = calendarCell.querySelector('.expense-total');
                        if (existingTotal) {
                            existingTotal.remove();
                        }
                        if (total > 0) {
                            const totalDiv = document.createElement('div');
                            totalDiv.className = 'expense-total';
                            totalDiv.textContent = `Total: ${total.toFixed(2)} RON`;
                            calendarCell.appendChild(totalDiv);
                        }
                    })
                    .catch(error => {
                        console.error('Error refreshing calendar cell:', error);
                        alert('Eroare la actualizarea afișării: ' + error.message);
                    });
            }
        } else {
            throw new Error(data.message || 'A apărut o eroare la actualizarea cheltuielii.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert(error.message || 'A apărut o eroare la actualizarea cheltuielii.');
    });
}

function saveExpense() {
    const form = document.getElementById('expenseForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const formData = new FormData(form);
    formData.append('action', 'add_expense'); // Add the action parameter
    
    console.log('Saving expense with data:', Object.fromEntries(formData));

    fetch('save-expense.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            expenseModal.hide();
            // Refresh only the affected calendar cell instead of reloading the page
            const expenseDate = formData.get('date');
            const calendarCell = document.querySelector(`.calendar-day[data-date="${expenseDate}"]`);
            if (calendarCell) {
                fetch(`fetch_expenses.php?date=${expenseDate}`)
                    .then(response => response.json())
                    .then(expenses => {
                        // Remove existing expense divs
                        const existingEvents = calendarCell.querySelectorAll('.event');
                        existingEvents.forEach(event => event.remove());
                        
                        // Add updated expense divs
                        expenses.forEach(expense => {
                            const eventDiv = document.createElement('div');
                            eventDiv.className = 'event';
                            eventDiv.textContent = `${expense.category_name}: ${expense.amount} RON`;
                            calendarCell.appendChild(eventDiv);
                        });
                    })
                    .catch(error => {
                        console.error('Error refreshing calendar cell:', error);
                    });
            }
        } else {
            throw new Error(data.message || 'A apărut o eroare la salvarea cheltuielii.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert(error.message || 'A apărut o eroare la salvarea cheltuielii.');
    });
}

function deleteExpense(id) {
    if (confirm('Sigur doriți să ștergeți această cheltuială?')) {
        fetch(`delete_expense.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Eroare la ștergerea cheltuielii: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('A apărut o eroare la ștergerea cheltuielii.');
            });
    }
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('ro-RO', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
} 