<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Manager</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

    <div class="container my-4">
        <h1 class="text-center mb-4">Task Manager</h1>

        <!-- Success/Failure Messages -->
        <div id="message" class="alert d-none"></div>

        <!-- Form to create a new task -->
        <form id="task-form" class="mb-4">
            @csrf
            <div class="mb-3">
                <input type="text" name="name" class="form-control" placeholder="Task Name" required>
            </div>
            <div class="mb-3">
                <textarea name="description" class="form-control" placeholder="Task Description"></textarea>
            </div>
            <div class="mb-3">
                <input type="datetime-local" name="deadline" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Add Task</button>
        </form>

        <!-- Task Table -->
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">Task Name</th>
                    <th scope="col">Description</th>
                    <th scope="col">Deadline</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody id="taskTableBody">
                @foreach($tasks as $task)
                    <tr class="task" data-id="{{ $task->id }}">
                        <th scope="row">{{ $task->id }}</th>
                        <td>{{ $task->name }}</td>
                        <td>{{ $task->description }}</td>
                        <td>
                            <span class="countdown" data-deadline="{{ $task->deadline }}">{{ $task->deadline }}</span>
                        </td>
                        <td>
                            <button class="btn btn-warning btn-sm edit-task" data-bs-toggle="modal" data-bs-target="#editModal" data-id="{{ $task->id }}">Edit</button>
                            <button class="btn btn-danger btn-sm delete-task">Delete</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Pagination Controls -->
        <div class="d-flex justify-content-between">
            <div>
                <span>Showing {{ $tasks->firstItem() }} to {{ $tasks->lastItem() }} of {{ $tasks->total() }} tasks</span>
            </div>
            <div>
                {{ $tasks->links() }}
            </div>
        </div>
    </div>

    <!-- Edit Task Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Edit Task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="edit-errors" class="alert alert-danger d-none"></div>
                    <form id="edit-form">
                        @csrf
                        <input type="hidden" id="edit-task-id">

                        <div class="mb-3">
                            <label for="edit-task-name" class="form-label">Task Name</label>
                            <input type="text" id="edit-task-name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit-task-description" class="form-label">Description</label>
                            <textarea id="edit-task-description" class="form-control"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="edit-task-deadline" class="form-label">Deadline</label>
                            <input type="datetime-local" id="edit-task-deadline" class="form-control" required>
                        </div>
                        <div id="edit-errors" class="alert alert-danger d-none"></div>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>  

    <script>

        function updateCountdown() {
            const countdownElements = document.querySelectorAll('.countdown');
            countdownElements.forEach(function(element) {
                let deadline = new Date(element.dataset.deadline);
                let now = new Date();
                let timeRemaining = deadline - now;

                if (timeRemaining <= 0) {
                    element.textContent = "Task expired";
                } else {
                    let hours = Math.floor(timeRemaining / (1000 * 60 * 60));
                    let minutes = Math.floor((timeRemaining % (1000 * 60 * 60)) / (1000 * 60));
                    let seconds = Math.floor((timeRemaining % (1000 * 60)) / 1000);
                    element.textContent = `${hours}h ${minutes}m ${seconds}s`;
                }
            });
        }
        setInterval(updateCountdown, 1000);

        // Add Task (AJAX using Vanilla JS)
        document.getElementById('task-form').addEventListener('submit', function(event) {
            event.preventDefault();
            const formData = new FormData(this);
            const data = new URLSearchParams(formData).toString();

            const deadlineInput = this.querySelector('input[name="deadline"]');
            const deadlineValue = new Date(deadlineInput.value);
            const today = new Date();

            if (deadlineValue <= today) {
                showError('Deadline must be a future date.');
                return;
            }

            fetch('/tasks', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: data
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('Task created successfully!', 'success');
                    location.reload();
                } else {
                    showError('Error creating task. Please try again.');
                }
            })
            .catch(error => console.error('Error:', error));
        });

        // Delete Task (AJAX)
        document.querySelectorAll('.delete-task').forEach(button => {
            button.addEventListener('click', function() {
                const taskId = this.closest('.task').dataset.id;

                fetch('/tasks/' + taskId, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessage('Task deleted successfully!', 'success');
                        this.closest('.task').remove();
                    } else {
                        showError('Error deleting task. Please try again.');
                    }
                })
                .catch(error => console.error('Error:', error));
            });
        });

        function showError(message) {
            const messageElement = document.getElementById('message');
            messageElement.classList.remove('d-none', 'alert-success');
            messageElement.classList.add('alert-danger');
            messageElement.textContent = message;
        }

        function showMessage(message, type) {
            const messageElement = document.getElementById('message');
            messageElement.classList.remove('d-none', 'alert-danger');
            messageElement.classList.add(`alert-${type}`);
            messageElement.textContent = message;
            setTimeout(() => messageElement.classList.add('d-none'), 3000);
        }

        // Fetch Task Data In Edit Model (AJAX)
        document.querySelectorAll('.edit-task').forEach(button => {
            button.addEventListener('click', function() {
                const taskId = this.dataset.id;

                fetch('/tasks/' + taskId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const task = data.task;
                            document.getElementById('edit-task-id').value = task.id;
                            document.getElementById('edit-task-name').value = task.name;
                            document.getElementById('edit-task-description').value = task.description;
                            document.getElementById('edit-task-deadline').value = task.deadline;
                        }
                    })
                    .catch(error => console.error('Error:', error));
            });
        });

        // Save Edit Task (AJAX)
        function openEditModal(task) {
            const taskId = task.id;
            const taskName = task.name;
            const taskDescription = task.description;
            const taskDeadline = task.deadline;

            document.getElementById('edit-task-id').value = taskId;
            document.getElementById('edit-task-name').value = taskName;
            document.getElementById('edit-task-description').value = taskDescription;

            const formattedDeadline = new Date(taskDeadline).toISOString().slice(0, 16);
            document.getElementById('edit-task-deadline').value = formattedDeadline;

            const editModal = new bootstrap.Modal(document.getElementById('editModal'));
            editModal.show();
        }

        document.getElementById('edit-form').addEventListener('submit', async function (event) {
            event.preventDefault();

            const taskId = document.getElementById('edit-task-id').value;
            const taskName = document.getElementById('edit-task-name').value;
            const taskDescription = document.getElementById('edit-task-description').value;
            const taskDeadline = document.getElementById('edit-task-deadline').value;

            const deadlineValue = new Date(taskDeadline);
            const today = new Date();

            const errorDiv = document.getElementById('edit-errors');
            errorDiv.classList.add('d-none');
            errorDiv.innerText = '';

            if (deadlineValue <= today) {
                errorDiv.classList.remove('d-none');
                errorDiv.innerText = 'Deadline must be a future date.';
                return;
            }

            const formData = {
                name: taskName,
                description: taskDescription,
                deadline: taskDeadline,
                _method: 'PUT',
                _token: '{{ csrf_token() }}'
            };

            try {
                const response = await fetch(`/tasks/${taskId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(formData)
                });

                const data = await response.json();
                if (data.success) {
                    showMessage('Task updated successfully!', 'success');
                    location.reload();
                } else {
                    showError('An error occurred. Please try again.');
                }
            } catch (error) {
                console.error('Error:', error);
                showError('Failed to update the task. Please try again later.');
            }
        });

    </script>

</body>
</html>