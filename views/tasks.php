<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/classes/Constants.php';
require_once __DIR__ . '/../src/classes/Task.php';
require_once __DIR__ . '/../src/classes/Project.php';

$taskObj = new Task($pdo);
$userId = (int) ($_SESSION['user_id'] ?? 0);
$userRole = $_SESSION['user_role'] ?? null;
$isAdmin = ($userRole === Constants::ROLE_ADMIN);

$tasks = $isAdmin ? $taskObj->getTasks() : $taskObj->getTasks($userId);

$summary = [
  'total' => count($tasks),
  'completed' => 0,
  'overdue' => 0,
  'dueSoon' => 0,
];

$today = new DateTimeImmutable('today');
$dueSoonThreshold = $today->modify('+7 days');

foreach ($tasks as $task) {
    $status = $task['task_status'] ?? '';
    if ($status === Constants::TASK_STATUS_COMPLETED) {
        $summary['completed']++;
    }

    $dueDateString = $task['task_due_date'] ?? null;
  if ($dueDateString && $status === Constants::TASK_STATUS_DUE) {
        $dueDateObject = DateTimeImmutable::createFromFormat('Y-m-d', $dueDateString);
        if ($dueDateObject === false) {
            $dueDateObject = new DateTimeImmutable($dueDateString);
        }

        if ($dueDateObject < $today) {
            $summary['overdue']++;
        } elseif ($dueDateObject <= $dueSoonThreshold) {
            $summary['dueSoon']++;
        }
    }
}

$projects = [];
$employees = [];

if ($isAdmin) {
    $projectObj = new Project($pdo);
    $projects = $projectObj->getAllProjects();

    $employeeStmt = $pdo->query(
        "SELECT user_id, CONCAT(COALESCE(user_first_name, ''), ' ', COALESCE(user_last_name, '')) AS full_name
         FROM users
         WHERE user_status = '" . Constants::USER_STATUS_ACTIVE . "'
         ORDER BY full_name"
    );
    $employees = $employeeStmt->fetchAll(PDO::FETCH_ASSOC);
}

$statusOptions = Constants::TASK_STATUSES;

$messages = [
  'success' => [
    'task_created' => 'Task assigned successfully.',
    Constants::MSG_TASK_COMPLETED => 'Task marked as completed.',
    Constants::MSG_TASK_UPDATED => 'Task updated successfully.',
  ],
  'error' => [
    'unauthorized' => 'You are not authorized to perform that action.',
    'invalid_input' => 'Please complete all required fields before submitting.',
    'past_date' => 'Due date cannot be in the past.',
    'task_failed' => 'Unable to assign the task. Please try again.',
    'task_exception' => 'Unexpected error assigning the task.',
    'missing_task' => 'Task reference was missing from the request.',
    'invalid_task' => 'Task reference was invalid.',
    'complete_failed' => 'Unable to update the task status.',
    'complete_exception' => 'Unexpected error completing the task.',
    'task_update_failed' => 'Unable to update the task. Please try again.',
  ],
];

$alert = null;
foreach (['success', 'error'] as $type) {
    if (isset($_GET[$type])) {
        $code = $_GET[$type];
        if (isset($messages[$type][$code])) {
            $alert = ['type' => $type, 'message' => $messages[$type][$code]];
        }
        break;
    }
}

$canAssign = $isAdmin && !empty($projects) && !empty($employees);
$editModals = [];
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
  <div>
    <h2 class="mb-1"><i class="bi bi-kanban"></i> Task Manager</h2>
    <p class="text-muted mb-0">Stay ahead of deadlines and keep assignments organized.</p>
  </div>
  <?php if (checkPermission($pdo, 'Create_Task')): ?>
  <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#addTaskModal" <?php echo $canAssign ? '' : 'disabled'; ?>>
    <i class="bi bi-plus-circle"></i>
    Assign New Task
  </button>
  <?php endif; ?>
</div>

<?php if ($alert): ?>
<div class="alert alert-<?php echo $alert['type'] === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
  <?php echo htmlspecialchars($alert['message'], ENT_QUOTES, 'UTF-8'); ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<?php if ($isAdmin && !$canAssign): ?>
<div class="alert alert-warning" role="alert">
  Create at least one active project and employee before assigning tasks.
</div>
<?php endif; ?>

<div class="row row-cols-1 row-cols-md-4 g-3 mb-4">
  <div class="col">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body">
        <p class="text-muted text-uppercase small mb-1">Total Tasks</p>
        <h3 class="fw-semibold mb-0"><?php echo htmlspecialchars((string) $summary['total'], ENT_QUOTES, 'UTF-8'); ?></h3>
      </div>
    </div>
  </div>
  <div class="col">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body">
        <p class="text-muted text-uppercase small mb-1">Completed</p>
        <h3 class="fw-semibold text-success mb-0"><?php echo htmlspecialchars((string) $summary['completed'], ENT_QUOTES, 'UTF-8'); ?></h3>
      </div>
    </div>
  </div>
  <div class="col">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body">
        <p class="text-muted text-uppercase small mb-1">Due Soon</p>
        <h3 class="fw-semibold text-primary mb-0"><?php echo htmlspecialchars((string) $summary['dueSoon'], ENT_QUOTES, 'UTF-8'); ?></h3>
      </div>
    </div>
  </div>
  <div class="col">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body">
        <p class="text-muted text-uppercase small mb-1">Overdue</p>
        <h3 class="fw-semibold text-danger mb-0"><?php echo htmlspecialchars((string) $summary['overdue'], ENT_QUOTES, 'UTF-8'); ?></h3>
      </div>
    </div>
  </div>
</div>

<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th style="width:24%">Task</th>
          <th style="width:18%">Project</th>
          <th style="width:18%">Assigned To</th>
          <th style="width:16%">Due</th>
          <th style="width:14%">Status</th>
          <th style="width:10%" class="text-center">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($tasks)): ?>
        <tr>
          <td colspan="6" class="text-center text-muted py-4">No tasks to display yet.</td>
        </tr>
        <?php else: ?>
        <?php foreach ($tasks as $task): ?>
        <?php
          $taskId = (int) $task['task_id'];
          $taskIdEsc = htmlspecialchars((string) $taskId, ENT_QUOTES, 'UTF-8');
          $title = $task['task_title'] ?? '';
            $status = $task['task_status'] ?? Constants::TASK_STATUS_DUE;
          $assignedName = trim((string) ($task['assigned_name'] ?? '')) ?: 'Unassigned';
          $assignedTo = (int) ($task['task_assigned_to'] ?? 0);
          $isCompleted = ($status === Constants::TASK_STATUS_COMPLETED);
            $canComplete = ($status === Constants::TASK_STATUS_DUE) && ($isAdmin || $assignedTo === $userId);

          $statusClass = 'bg-warning text-dark';
          if ($status === Constants::TASK_STATUS_COMPLETED) {
              $statusClass = 'bg-success';
            } elseif ($status === Constants::TASK_STATUS_INACTIVE) {
              $statusClass = 'bg-secondary';
          }

          $dueRaw = $task['task_due_date'] ?? null;
          $dueDisplay = $dueRaw ? date('M d, Y', strtotime($dueRaw)) : 'No due date';
          $dueBadge = null;

            if ($dueRaw && $status === Constants::TASK_STATUS_DUE) {
              $dueDateObject = DateTimeImmutable::createFromFormat('Y-m-d', $dueRaw);
              if ($dueDateObject === false) {
                  $dueDateObject = new DateTimeImmutable($dueRaw);
              }

              if ($dueDateObject < $today) {
                  $dueBadge = 'overdue';
              } elseif ($dueDateObject <= $dueSoonThreshold) {
                  $dueBadge = 'dueSoon';
              }
          }

          $description = trim((string) ($task['task_description'] ?? ''));
          $descriptionPreview = $description !== '' ? mb_strimwidth($description, 0, 80, '…') : 'No description provided.';
          $statusClassEsc = htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8');
        ?>
        <tr id="task-row-<?php echo $taskIdEsc; ?>">
          <td>
            <strong><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></strong>
            <p class="text-muted small mb-0"><?php echo htmlspecialchars($descriptionPreview, ENT_QUOTES, 'UTF-8'); ?></p>
          </td>
          <td><?php echo htmlspecialchars($task['project_title'] ?? 'Unassigned Project', ENT_QUOTES, 'UTF-8'); ?></td>
          <td><i class="bi bi-person-circle me-1"></i><?php echo htmlspecialchars($assignedName, ENT_QUOTES, 'UTF-8'); ?></td>
          <td>
            <?php echo htmlspecialchars($dueDisplay, ENT_QUOTES, 'UTF-8'); ?>
            <?php if ($dueBadge === 'overdue'): ?>
              <span class="badge bg-danger ms-2">Overdue</span>
            <?php elseif ($dueBadge === 'dueSoon'): ?>
              <span class="badge bg-warning text-dark ms-2">Due Soon</span>
            <?php endif; ?>
          </td>
          <td>
            <span class="badge task-status-badge <?php echo $statusClassEsc; ?>"><?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?></span>
          </td>
          <td class="text-center">
            <?php if ($isAdmin): ?>
            <div class="btn-group btn-group-sm" role="group">
              <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editTaskModal<?php echo $taskIdEsc; ?>">Edit</button>
              <?php if ($canComplete): ?>
              <button type="button" class="btn btn-success" onclick="completeTask(<?php echo $taskIdEsc; ?>)" id="btn-task-<?php echo $taskIdEsc; ?>">Done</button>
              <?php endif; ?>
              <button type="button" class="btn btn-outline-danger" onclick="deleteTask(<?php echo $taskIdEsc; ?>)">Delete</button>
            </div>
            <?php elseif ($canComplete): ?>
            <button type="button" class="btn btn-sm btn-success" onclick="completeTask(<?php echo $taskIdEsc; ?>)" id="btn-task-<?php echo $taskIdEsc; ?>">Done</button>
            <?php elseif ($isCompleted): ?>
            <i class="bi bi-check-all text-success"></i>
            <?php else: ?>
            <span class="text-muted">—</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php if ($isAdmin):
          $modalId = 'editTaskModal' . $taskId;
          $modalIdEsc = htmlspecialchars($modalId, ENT_QUOTES, 'UTF-8');
          ob_start();
        ?>
        <div class="modal fade" id="<?php echo $modalIdEsc; ?>" tabindex="-1" aria-labelledby="<?php echo $modalIdEsc; ?>Label" aria-hidden="true">
          <div class="modal-dialog">
            <form class="modal-content task-update-form" data-task-id="<?php echo $taskIdEsc; ?>">
              <input type="hidden" name="task_id" value="<?php echo $taskIdEsc; ?>">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
              <div class="modal-header">
                <h5 class="modal-title" id="<?php echo $modalIdEsc; ?>Label">Update Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <div class="mb-3">
                  <label class="form-label" for="task_title_<?php echo $taskIdEsc; ?>">Task Title</label>
                  <input type="text" id="task_title_<?php echo $taskIdEsc; ?>" name="task_title" class="form-control" value="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>
                <div class="mb-3">
                  <label class="form-label" for="task_description_<?php echo $taskIdEsc; ?>">Description</label>
                  <textarea id="task_description_<?php echo $taskIdEsc; ?>" name="task_description" class="form-control" rows="3" placeholder="Optional details"><?php echo htmlspecialchars($description, ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
                <div class="mb-3">
                  <label class="form-label" for="task_project_<?php echo $taskIdEsc; ?>">Project</label>
                  <select id="task_project_<?php echo $taskIdEsc; ?>" name="project_id" class="form-select" required>
                    <?php foreach ($projects as $project): ?>
                    <option value="<?php echo htmlspecialchars((string) $project['project_id'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo ((int) $project['project_id'] === (int) ($task['task_project_id'] ?? 0)) ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($project['project_title'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="mb-3">
                  <label class="form-label" for="task_assignee_<?php echo $taskIdEsc; ?>">Assign To</label>
                  <select id="task_assignee_<?php echo $taskIdEsc; ?>" name="assigned_to" class="form-select" required>
                    <?php foreach ($employees as $employee): ?>
                    <option value="<?php echo htmlspecialchars((string) $employee['user_id'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo ((int) $employee['user_id'] === $assignedTo) ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars(trim($employee['full_name']) ?: 'Unnamed User', ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="mb-3">
                  <label class="form-label" for="task_due_date_<?php echo $taskIdEsc; ?>">Due Date</label>
                  <input type="date" id="task_due_date_<?php echo $taskIdEsc; ?>" name="task_due_date" class="form-control" value="<?php echo htmlspecialchars($dueRaw ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>
                <div class="mb-3">
                  <label class="form-label" for="task_status_<?php echo $taskIdEsc; ?>">Status</label>
                  <select id="task_status_<?php echo $taskIdEsc; ?>" name="task_status" class="form-select" required>
                    <?php foreach ($statusOptions as $statusOption): ?>
                    <option value="<?php echo htmlspecialchars($statusOption, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $statusOption === $status ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($statusOption, ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Save Changes</button>
              </div>
            </form>
          </div>
        </div>
        <?php
          $editModals[] = ob_get_clean();
        endif;
        ?>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($isAdmin): ?>
  <?php foreach ($editModals as $modalHtml): ?><?php echo $modalHtml; ?><?php endforeach; ?>
  <div class="modal fade" id="addTaskModal" tabindex="-1" aria-labelledby="addTaskModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <form id="createTaskForm" class="modal-content">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
        <div class="modal-header">
          <h5 class="modal-title" id="addTaskModalLabel">Assign New Task</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label" for="new_task_title">Task Title</label>
            <input type="text" id="new_task_title" name="title" class="form-control" <?php echo $canAssign ? 'required' : 'disabled'; ?>>
          </div>
          <div class="mb-3">
            <label class="form-label" for="new_task_description">Description</label>
            <textarea id="new_task_description" name="description" class="form-control" rows="3" placeholder="Optional details" <?php echo $canAssign ? '' : 'disabled'; ?>></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label" for="new_task_project">Project</label>
            <select id="new_task_project" name="project_id" class="form-select" <?php echo $canAssign ? 'required' : 'disabled'; ?>>
              <?php foreach ($projects as $project): ?>
                <option value="<?php echo htmlspecialchars((string) $project['project_id'], ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo htmlspecialchars($project['project_title'], ENT_QUOTES, 'UTF-8'); ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label" for="new_task_assignee">Assign To</label>
            <select id="new_task_assignee" name="assigned_to" class="form-select" <?php echo $canAssign ? 'required' : 'disabled'; ?>>
              <?php foreach ($employees as $employee): ?>
                <option value="<?php echo htmlspecialchars((string) $employee['user_id'], ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo htmlspecialchars(trim($employee['full_name']) ?: 'Unnamed User', ENT_QUOTES, 'UTF-8'); ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label" for="new_task_due_date">Due Date</label>
            <input type="date" id="new_task_due_date" name="due_date" class="form-control" <?php echo $canAssign ? 'required' : 'disabled'; ?>>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary" <?php echo $canAssign ? '' : 'disabled'; ?>>Create Task</button>
        </div>
      </form>
    </div>
  </div>
<?php endif; ?>

<script>
async function completeTask(taskId) {
  try {
    const response = await window.apiRequest('tasks/' + taskId, {
      method: 'PUT',
      body: JSON.stringify({ status: '<?php echo Constants::TASK_STATUS_COMPLETED; ?>' })
    });

    if (!response.success) {
      alert('Error: ' + (response.message || 'Unable to complete task.'));
      return;
    }

    const button = document.getElementById('btn-task-' + taskId);
    const statusBadge = document.querySelector('#task-row-' + taskId + ' .task-status-badge');

    if (button && button.parentElement) {
      button.parentElement.innerHTML = '<i class="bi bi-check-all text-success"></i> Done';
    } else if (button) {
      button.outerHTML = '<i class="bi bi-check-all text-success"></i>';
    }

    if (statusBadge) {
      statusBadge.className = 'badge task-status-badge bg-success';
      statusBadge.textContent = '<?php echo Constants::TASK_STATUS_COMPLETED; ?>';
    }
  } catch (error) {
    alert(error.message || 'Error updating task status.');
  }
}

async function deleteTask(taskId) {
  if (!confirm('Are you sure?')) {
    return;
  }

  try {
    const response = await window.apiRequest('tasks/' + taskId, {
      method: 'DELETE'
    });

    if (!response.success) {
      alert('Error: ' + (response.message || 'Unable to delete task.'));
      return;
    }

    const row = document.getElementById('task-row-' + taskId);
    if (row) {
      row.remove();
    }
  } catch (error) {
    alert(error.message || 'Error deleting task.');
  }
}

document.addEventListener('DOMContentLoaded', function () {
  const allowedStatuses = new Set([
    '<?php echo Constants::TASK_STATUS_DUE; ?>',
    '<?php echo Constants::TASK_STATUS_COMPLETED; ?>',
    '<?php echo Constants::TASK_STATUS_INACTIVE; ?>'
  ]);

  function isValidDateNotPast(dateString) {
    if (!dateString) {
      return false;
    }

    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const due = new Date(dateString + 'T00:00:00');
    return !Number.isNaN(due.getTime()) && due >= today;
  }

  function isValidCreateTaskPayload(payload) {
    return payload.title.length >= 3
      && payload.project_id > 0
      && payload.assigned_to > 0
      && isValidDateNotPast(payload.due_date);
  }

  function isValidUpdateTaskPayload(payload) {
    return payload.title.length >= 3
      && payload.project_id > 0
      && payload.assigned_to > 0
      && isValidDateNotPast(payload.due_date)
      && allowedStatuses.has(payload.status);
  }

  const createTaskForm = document.getElementById('createTaskForm');
  if (createTaskForm) {
    createTaskForm.addEventListener('submit', async function (event) {
      event.preventDefault();

      const formData = new FormData(createTaskForm);
      const payload = {
        title: String(formData.get('title') || '').trim(),
        description: String(formData.get('description') || '').trim(),
        project_id: Number(formData.get('project_id') || 0),
        assigned_to: Number(formData.get('assigned_to') || 0),
        due_date: String(formData.get('due_date') || '').trim()
      };

      if (!isValidCreateTaskPayload(payload)) {
        window.location.href = '<?php echo APP_BASE; ?>/tasks?error=invalid_input';
        return;
      }

      try {
        const response = await window.apiRequest('tasks', {
          method: 'POST',
          body: JSON.stringify(payload)
        });

        if (response.success) {
          window.location.href = '<?php echo APP_BASE; ?>/tasks?success=task_created';
          return;
        }

        window.location.href = '<?php echo APP_BASE; ?>/tasks?error=task_failed';
      } catch (error) {
        window.location.href = '<?php echo APP_BASE; ?>/tasks?error=task_exception';
      }
    });
  }

  document.querySelectorAll('.task-update-form').forEach(function (updateForm) {
    updateForm.addEventListener('submit', async function (event) {
      event.preventDefault();

      const taskId = Number(updateForm.getAttribute('data-task-id') || 0);
      const formData = new FormData(updateForm);
      const payload = {
        title: String(formData.get('task_title') || '').trim(),
        description: String(formData.get('task_description') || '').trim(),
        project_id: Number(formData.get('project_id') || 0),
        assigned_to: Number(formData.get('assigned_to') || 0),
        due_date: String(formData.get('task_due_date') || '').trim(),
        status: String(formData.get('task_status') || '<?php echo Constants::TASK_STATUS_DUE; ?>')
      };

      if (taskId <= 0 || !isValidUpdateTaskPayload(payload)) {
        window.location.href = '<?php echo APP_BASE; ?>/tasks?error=invalid_input';
        return;
      }

      try {
        const response = await window.apiRequest('tasks/' + taskId, {
          method: 'PUT',
          body: JSON.stringify(payload)
        });

        if (response.success) {
          window.location.href = '<?php echo APP_BASE; ?>/tasks?success=task_updated';
          return;
        }

        window.location.href = '<?php echo APP_BASE; ?>/tasks?error=task_update_failed';
      } catch (error) {
        window.location.href = '<?php echo APP_BASE; ?>/tasks?error=task_update_failed';
      }
    });
  });
});
</script>
