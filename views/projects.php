<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/classes/Constants.php';
require_once __DIR__ . '/../src/classes/Project.php';

$projectObj = new Project($pdo);
$projects = $projectObj->getAllProjects();

$messages = [
    'success' => [
        Constants::MSG_PROJECT_CREATED => 'Project created successfully.',
        Constants::MSG_PROJECT_UPDATED => 'Project updated successfully.',
    ],
    'error' => [
        Constants::MSG_INVALID_INPUT => 'Project title is required.',
        'create_failed' => 'Unable to create the project. Please try again.',
        'create_exception' => 'Unexpected error creating the project.',
        Constants::MSG_UNAUTHORIZED => 'You are not authorized to perform that action.',
        'update_failed' => 'Unable to update the project. Please try again.',
        'update_exception' => 'Unexpected error updating the project.',
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

$userRole = $_SESSION['user_role'] ?? null;
$isAdmin = ($userRole === Constants::ROLE_ADMIN);
$statusOptions = Constants::PROJECT_STATUSES;
$editModals = [];
?>

<?php if ($alert): ?>
<div class="alert alert-<?php echo $alert['type'] === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
  <?php echo htmlspecialchars($alert['message'], ENT_QUOTES, 'UTF-8'); ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h2><i class="bi bi-kanban"></i> Projects</h2>
  <?php if (checkPermission($pdo, 'Create_Project')): ?>
  <button class="btn btn-success" type="button" data-bs-toggle="modal" data-bs-target="#addProjectModal">
    <i class="bi bi-plus-lg"></i>
    New Project
  </button>
  <?php endif; ?>
</div>
<div class="row">
  <?php if (empty($projects)): ?>
  <div class="col-12">
    <div class="alert alert-info" role="alert">
      No projects found. <?php echo $isAdmin ? 'Create one to get started.' : 'Please check back later.'; ?>
    </div>
  </div>
  <?php endif; ?>
  <?php foreach ($projects as $project): ?>
  <?php
    $projectId = (int) $project['project_id'];
    $projectIdEsc = htmlspecialchars((string) $projectId, ENT_QUOTES, 'UTF-8');
  ?>
  <div class="col-md-4 mb-4">
    <div class="card h-100">
      <div class="card-body">
        <h5 class="card-title"><?php echo htmlspecialchars($project['project_title'], ENT_QUOTES, 'UTF-8'); ?></h5>
        <p class="card-text text-muted">
          <?php
          $description = $project['project_description'] ?? '';
          echo htmlspecialchars(mb_strimwidth($description, 0, 80, '...'), ENT_QUOTES, 'UTF-8');
          ?>
        </p>
        <span class="badge bg-secondary">
          <?php echo htmlspecialchars($project['project_status'], ENT_QUOTES, 'UTF-8'); ?>
        </span>
      </div>
      <div class="card-footer bg-transparent">
        <a href="<?php echo APP_BASE; ?>/tasks" class="btn btn-sm btn-link">View Tasks</a>
        <?php if ($isAdmin): ?>
        <button class="btn btn-sm btn-outline-primary float-end" type="button" data-bs-toggle="modal" data-bs-target="#editProjectModal<?php echo $projectIdEsc; ?>">Edit</button>
        <?php else: ?>
        <button class="btn btn-sm btn-outline-secondary float-end" type="button" disabled>Edit</button>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php if ($isAdmin):
    $modalId = 'editProjectModal' . $projectId;
    $modalIdEsc = htmlspecialchars($modalId, ENT_QUOTES, 'UTF-8');
    $projectTitle = $project['project_title'] ?? '';
    $projectDescription = $project['project_description'] ?? '';
    $projectStatus = $project['project_status'] ?? Constants::PROJECT_STATUS_ACTIVE;
    ob_start();
  ?>
  <div class="modal fade" id="<?php echo $modalIdEsc; ?>" tabindex="-1" aria-labelledby="<?php echo $modalIdEsc; ?>Label" aria-hidden="true">
    <div class="modal-dialog">
      <form class="modal-content project-update-form" data-project-id="<?php echo $projectIdEsc; ?>">
        <input type="hidden" name="project_id" value="<?php echo $projectIdEsc; ?>">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
        <div class="modal-header">
          <h5 class="modal-title" id="<?php echo $modalIdEsc; ?>Label">Edit Project</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label" for="project_title_<?php echo $projectIdEsc; ?>">Project Title</label>
            <input type="text" id="project_title_<?php echo $projectIdEsc; ?>" name="project_title" class="form-control" value="<?php echo htmlspecialchars($projectTitle, ENT_QUOTES, 'UTF-8'); ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label" for="project_description_<?php echo $projectIdEsc; ?>">Description</label>
            <textarea id="project_description_<?php echo $projectIdEsc; ?>" name="project_description" class="form-control" rows="3" placeholder="Optional details"><?php echo htmlspecialchars($projectDescription, ENT_QUOTES, 'UTF-8'); ?></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label" for="project_status_<?php echo $projectIdEsc; ?>">Status</label>
            <select id="project_status_<?php echo $projectIdEsc; ?>" name="project_status" class="form-select">
              <?php foreach ($statusOptions as $statusOption): ?>
              <option value="<?php echo htmlspecialchars($statusOption, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $statusOption === $projectStatus ? 'selected' : ''; ?>>
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
</div>

<?php if ($isAdmin): ?>
<?php foreach ($editModals as $modalHtml): echo $modalHtml; endforeach; ?>
<div class="modal fade" id="addProjectModal" tabindex="-1" aria-labelledby="addProjectModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="createProjectForm" class="modal-content">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
      <div class="modal-header">
        <h5 class="modal-title" id="addProjectModalLabel">Create New Project</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label" for="project_title">Project Title</label>
          <input type="text" id="project_title" name="project_title" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label" for="project_description">Description</label>
          <textarea id="project_description" name="project_description" class="form-control" rows="3" placeholder="Optional details"></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label" for="project_status">Status</label>
          <select id="project_status" name="project_status" class="form-select">
            <option value="<?php echo Constants::PROJECT_STATUS_ACTIVE; ?>" selected><?php echo Constants::PROJECT_STATUS_ACTIVE; ?></option>
            <option value="<?php echo Constants::PROJECT_STATUS_INACTIVE; ?>"><?php echo Constants::PROJECT_STATUS_INACTIVE; ?></option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-success">Save Project</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const createProjectForm = document.getElementById('createProjectForm');
  if (createProjectForm) {
    createProjectForm.addEventListener('submit', async function (event) {
      event.preventDefault();

      const formData = new FormData(createProjectForm);
      const payload = {
        title: String(formData.get('project_title') || '').trim(),
        description: String(formData.get('project_description') || '').trim(),
        status: String(formData.get('project_status') || '<?php echo Constants::PROJECT_STATUS_ACTIVE; ?>')
      };

      if (!payload.title) {
        window.location.href = '<?php echo APP_BASE; ?>/projects?error=invalid_input';
        return;
      }

      try {
        const response = await window.apiRequest('projects', {
          method: 'POST',
          body: JSON.stringify(payload)
        });

        if (response.success) {
          window.location.href = '<?php echo APP_BASE; ?>/projects?success=project_created';
          return;
        }

        window.location.href = '<?php echo APP_BASE; ?>/projects?error=create_failed';
      } catch (error) {
        window.location.href = '<?php echo APP_BASE; ?>/projects?error=create_exception';
      }
    });
  }

  document.querySelectorAll('.project-update-form').forEach(function (updateForm) {
    updateForm.addEventListener('submit', async function (event) {
      event.preventDefault();

      const projectId = Number(updateForm.getAttribute('data-project-id') || 0);
      const formData = new FormData(updateForm);
      const payload = {
        title: String(formData.get('project_title') || '').trim(),
        description: String(formData.get('project_description') || '').trim(),
        status: String(formData.get('project_status') || '<?php echo Constants::PROJECT_STATUS_ACTIVE; ?>')
      };

      if (projectId <= 0 || !payload.title) {
        window.location.href = '<?php echo APP_BASE; ?>/projects?error=invalid_input';
        return;
      }

      try {
        const response = await window.apiRequest('projects/' + projectId, {
          method: 'PUT',
          body: JSON.stringify(payload)
        });

        if (response.success) {
          window.location.href = '<?php echo APP_BASE; ?>/projects?success=project_updated';
          return;
        }

        window.location.href = '<?php echo APP_BASE; ?>/projects?error=update_failed';
      } catch (error) {
        window.location.href = '<?php echo APP_BASE; ?>/projects?error=update_exception';
      }
    });
  });
});
</script>
