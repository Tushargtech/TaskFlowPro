<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/classes/Constants.php';
require_once __DIR__ . '/../src/classes/Project.php';

$projectObj = new Project($pdo);

$perPage = 8;
$currentPage = max(1, (int) ($_GET['page'] ?? 1));
$totalProjects = $projectObj->getProjectCount();
$totalPages = max(1, (int) ceil($totalProjects / $perPage));

if ($currentPage > $totalPages) {
  $currentPage = $totalPages;
}

$offset = ($currentPage - 1) * $perPage;
$projects = $projectObj->getProjectsPaginated($perPage, $offset);

$startItem = $totalProjects > 0 ? ($offset + 1) : 0;
$endItem = $totalProjects > 0 ? ($offset + count($projects)) : 0;

$pageParams = $_GET;
unset($pageParams['url']);
unset($pageParams['success'], $pageParams['error']);

$buildPageUrl = static function (int $page) use ($pageParams): string {
  $params = $pageParams;
  $params['page'] = $page;

  return APP_BASE . '/projects?' . http_build_query($params);
};

$messages = [
    'success' => [
        Constants::MSG_PROJECT_CREATED => 'Project created successfully.',
        Constants::MSG_PROJECT_UPDATED => 'Project updated successfully.',
    ],
    'error' => [
        Constants::MSG_INVALID_INPUT => 'Project title is required.',
      'project_attachment_failed' => 'Project saved, but attachment upload failed. Please try again from Edit.',
      'missing_file' => 'No file was selected for upload.',
      'file_too_large' => 'Attachment is too large. Your server currently allows up to 2 MB per file.',
      'unsupported_file_type' => 'Unsupported file type. Allowed: PDF, DOC, DOCX, PNG, JPG, JPEG, TXT.',
      'storage_unavailable' => 'Attachment storage is not writable. Please contact administrator.',
      'upload_move_failed' => 'Attachment upload failed while saving the file. Please retry.',
      'upload_failed' => 'Attachment upload failed. Please retry.',
        'project_name_exists' => 'Project name already exists. Please choose a different name.',
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
        <div class="small mb-2" data-project-attachment-summary="<?php echo $projectIdEsc; ?>"></div>
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
  <div class="modal fade project-edit-modal" id="<?php echo $modalIdEsc; ?>" tabindex="-1" aria-labelledby="<?php echo $modalIdEsc; ?>Label" aria-hidden="true">
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
          <div class="mb-3">
            <label class="form-label" for="project_attachments_<?php echo $projectIdEsc; ?>">Attachments</label>
            <input type="file" id="project_attachments_<?php echo $projectIdEsc; ?>" name="attachments[]" class="form-control" multiple accept=".pdf,.doc,.docx,.png,.jpg,.jpeg,.txt">
            <div class="form-text">Allowed: PDF, DOC, DOCX, PNG, JPG, JPEG, TXT. Max 5 MB each.</div>
            <ul class="list-group mt-2 attachment-list" data-entity-type="project" data-entity-id="<?php echo $projectIdEsc; ?>"></ul>
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

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
  <p class="mb-0 text-muted small">
    Showing <?php echo htmlspecialchars((string) $startItem, ENT_QUOTES, 'UTF-8'); ?>
    to <?php echo htmlspecialchars((string) $endItem, ENT_QUOTES, 'UTF-8'); ?>
    of <?php echo htmlspecialchars((string) $totalProjects, ENT_QUOTES, 'UTF-8'); ?> projects
  </p>

  <?php if ($totalPages > 1): ?>
  <nav aria-label="Projects pagination">
    <ul class="pagination pagination-sm mb-0">
      <li class="page-item <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>">
        <a class="page-link" href="<?php echo htmlspecialchars($buildPageUrl(max(1, $currentPage - 1)), ENT_QUOTES, 'UTF-8'); ?>">Previous</a>
      </li>

      <?php
        $windowStart = max(1, $currentPage - 2);
        $windowEnd = min($totalPages, $currentPage + 2);
        for ($page = $windowStart; $page <= $windowEnd; $page++):
      ?>
      <li class="page-item <?php echo $page === $currentPage ? 'active' : ''; ?>">
        <a class="page-link" href="<?php echo htmlspecialchars($buildPageUrl($page), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) $page, ENT_QUOTES, 'UTF-8'); ?></a>
      </li>
      <?php endfor; ?>

      <li class="page-item <?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?>">
        <a class="page-link" href="<?php echo htmlspecialchars($buildPageUrl(min($totalPages, $currentPage + 1)), ENT_QUOTES, 'UTF-8'); ?>">Next</a>
      </li>
    </ul>
  </nav>
  <?php endif; ?>
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
        <div class="mb-3">
          <label class="form-label" for="new_project_attachments">Attachments</label>
          <input type="file" id="new_project_attachments" name="attachments[]" class="form-control" multiple accept=".pdf,.doc,.docx,.png,.jpg,.jpeg,.txt">
          <div class="form-text">Allowed: PDF, DOC, DOCX, PNG, JPG, JPEG, TXT. Max 5 MB each.</div>
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
  const currentPage = <?php echo (int) $currentPage; ?>;
  const canDeleteAttachments = <?php echo $isAdmin ? 'true' : 'false'; ?>;
  const allowedStatuses = new Set(['<?php echo Constants::PROJECT_STATUS_ACTIVE; ?>', '<?php echo Constants::PROJECT_STATUS_INACTIVE; ?>']);

  function escapeHtml(value) {
    return String(value || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function formatBytes(bytes) {
    const size = Number(bytes || 0);
    if (size <= 0) {
      return '0 B';
    }

    if (size < 1024) {
      return size + ' B';
    }

    if (size < 1024 * 1024) {
      return (size / 1024).toFixed(1) + ' KB';
    }

    return (size / (1024 * 1024)).toFixed(1) + ' MB';
  }

  async function uploadAttachments(entityType, entityId, fileInput) {
    if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
      return;
    }

    const formData = new FormData();
    formData.append('entity_type', entityType);
    formData.append('entity_id', String(entityId));

    Array.from(fileInput.files).forEach(function (file) {
      formData.append('attachments[]', file);
    });

    await window.apiRequest('attachments', {
      method: 'POST',
      body: formData
    });
  }

  async function loadAttachmentList(listElement) {
    if (!listElement) {
      return;
    }

    const entityType = String(listElement.getAttribute('data-entity-type') || '');
    const entityId = Number(listElement.getAttribute('data-entity-id') || 0);

    if (!entityType || entityId <= 0) {
      listElement.innerHTML = '';
      return;
    }

    try {
      const response = await window.apiRequest('attachments?entity_type=' + encodeURIComponent(entityType) + '&entity_id=' + entityId, {
        method: 'GET'
      });

      const attachments = Array.isArray(response.attachments) ? response.attachments : [];

      if (attachments.length === 0) {
        listElement.innerHTML = '<li class="list-group-item text-muted small">No attachments yet.</li>';
        return;
      }

      listElement.innerHTML = attachments.map(function (attachment) {
        const id = Number(attachment.attachment_id || 0);
        const name = escapeHtml(attachment.original_name || 'Attachment');
        const size = escapeHtml(formatBytes(attachment.file_size || 0));
        const downloadUrl = String(attachment.download_url || '#');
        const deleteButton = canDeleteAttachments
          ? '<button type="button" class="btn btn-sm btn-outline-danger" data-delete-attachment-id="' + id + '">Delete</button>'
          : '';

        return '<li class="list-group-item d-flex justify-content-between align-items-center gap-2">'
          + '<a href="' + downloadUrl + '" class="small text-decoration-none" target="_blank" rel="noopener">' + name + ' <span class="text-muted">(' + size + ')</span></a>'
          + deleteButton
          + '</li>';
      }).join('');

      if (canDeleteAttachments && !listElement.dataset.boundDelete) {
        listElement.dataset.boundDelete = '1';
        listElement.addEventListener('click', async function (event) {
          const button = event.target.closest('[data-delete-attachment-id]');
          if (!button) {
            return;
          }

          const attachmentId = Number(button.getAttribute('data-delete-attachment-id') || 0);
          if (attachmentId <= 0 || !confirm('Delete this attachment?')) {
            return;
          }

          try {
            await window.apiRequest('attachments/' + attachmentId, {
              method: 'DELETE'
            });
            await loadAttachmentList(listElement);
          } catch (error) {
            alert(error.message || 'Unable to delete attachment.');
          }
        });
      }
    } catch (error) {
      listElement.innerHTML = '<li class="list-group-item text-danger small">Unable to load attachments.</li>';
    }
  }

  function loadInlineAttachmentSummary(summaryElement) {
    const projectId = Number(summaryElement.getAttribute('data-project-attachment-summary') || 0);
    if (projectId <= 0) {
      summaryElement.textContent = '';
      return;
    }

    window.apiRequest('attachments?entity_type=project&entity_id=' + projectId, { method: 'GET' })
      .then(function (response) {
        const attachments = Array.isArray(response.attachments) ? response.attachments : [];
        if (attachments.length === 0) {
          summaryElement.innerHTML = '<span class="text-muted">No attachments</span>';
          return;
        }

        const top = attachments.slice(0, 2);
        const links = top.map(function (attachment) {
          const name = escapeHtml(attachment.original_name || 'Attachment');
          const downloadUrl = String(attachment.download_url || '#');
          return '<a href="' + downloadUrl + '" class="text-decoration-none" target="_blank" rel="noopener">' + name + '</a>';
        }).join(', ');

        const remaining = attachments.length - top.length;
        const more = remaining > 0 ? ' <span class="text-muted">+' + remaining + ' more</span>' : '';
        summaryElement.innerHTML = '<i class="bi bi-paperclip"></i> ' + links + more;
      })
      .catch(function () {
        summaryElement.innerHTML = '<span class="text-muted">Attachments unavailable</span>';
      });
  }

  function isValidProjectPayload(payload) {
    return payload.title.length >= 3 && payload.title.length <= 100 && allowedStatuses.has(payload.status);
  }

  function redirectWithProjectError(errorCode, fallbackCode) {
    const validCodes = new Set([
      'missing_file',
      'file_too_large',
      'unsupported_file_type',
      'storage_unavailable',
      'upload_move_failed',
      'upload_failed',
      'project_attachment_failed'
    ]);

    const code = validCodes.has(String(errorCode || '')) ? String(errorCode) : fallbackCode;
    window.location.href = '<?php echo APP_BASE; ?>/projects?error=' + encodeURIComponent(code) + '&page=' + encodeURIComponent(String(currentPage));
  }

  function redirectWithProjectSuccess(successCode) {
    window.location.href = '<?php echo APP_BASE; ?>/projects?success=' + encodeURIComponent(String(successCode)) + '&page=' + encodeURIComponent(String(currentPage));
  }

  function redirectWithProjectSimpleError(errorCode) {
    window.location.href = '<?php echo APP_BASE; ?>/projects?error=' + encodeURIComponent(String(errorCode)) + '&page=' + encodeURIComponent(String(currentPage));
  }

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

      if (!isValidProjectPayload(payload)) {
        redirectWithProjectSimpleError('invalid_input');
        return;
      }

      try {
        const response = await window.apiRequest('projects', {
          method: 'POST',
          body: JSON.stringify(payload)
        });

        if (response.success) {
          const projectId = Number(response.id || 0);
          const attachmentInput = createProjectForm.querySelector('input[name="attachments[]"]');
          if (attachmentInput && attachmentInput.files && attachmentInput.files.length > 0) {
            if (projectId <= 0) {
              redirectWithProjectError('project_attachment_failed', 'project_attachment_failed');
              return;
            }

            try {
              await uploadAttachments('project', projectId, attachmentInput);
            } catch (error) {
              redirectWithProjectError(error.message, 'project_attachment_failed');
              return;
            }
          }

          redirectWithProjectSuccess('project_created');
          return;
        }

        redirectWithProjectSimpleError('create_failed');
      } catch (error) {
        if (error.message === 'project_name_exists') {
          redirectWithProjectSimpleError('project_name_exists');
          return;
        }
        redirectWithProjectSimpleError('create_exception');
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

      if (projectId <= 0 || !isValidProjectPayload(payload)) {
        redirectWithProjectSimpleError('invalid_input');
        return;
      }

      try {
        const response = await window.apiRequest('projects/' + projectId, {
          method: 'PUT',
          body: JSON.stringify(payload)
        });

        if (response.success) {
          const attachmentInput = updateForm.querySelector('input[name="attachments[]"]');
          if (attachmentInput && attachmentInput.files && attachmentInput.files.length > 0) {
            try {
              await uploadAttachments('project', projectId, attachmentInput);
            } catch (error) {
              redirectWithProjectError(error.message, 'project_attachment_failed');
              return;
            }
          }

          redirectWithProjectSuccess('project_updated');
          return;
        }

        redirectWithProjectSimpleError('update_failed');
      } catch (error) {
        if (error.message === 'project_name_exists') {
          redirectWithProjectSimpleError('project_name_exists');
          return;
        }
        redirectWithProjectSimpleError('update_exception');
      }
    });
  });

  document.querySelectorAll('.project-edit-modal').forEach(function (modalElement) {
    modalElement.addEventListener('show.bs.modal', function () {
      const listElement = modalElement.querySelector('.attachment-list[data-entity-type="project"]');
      loadAttachmentList(listElement);
    });
  });

  document.querySelectorAll('[data-project-attachment-summary]').forEach(function (summaryElement) {
    loadInlineAttachmentSummary(summaryElement);
  });
});
</script>
