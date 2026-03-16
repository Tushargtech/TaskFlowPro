<?php

declare(strict_types=1);

class Task
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getTasks(?int $userId = null): array
    {
        $sql = 'SELECT t.task_id, t.task_title, t.task_description, t.task_due_date, t.task_status,
                   t.task_assigned_to, t.task_project_id,
                       p.project_title,
                       CONCAT(COALESCE(u.user_first_name, ""), " ", COALESCE(u.user_last_name, "")) AS assigned_name
                FROM tasks t
                JOIN projects p ON t.task_project_id = p.project_id
                LEFT JOIN users u ON t.task_assigned_to = u.user_id';

        $params = [];

        if ($userId !== null) {
            $sql .= ' WHERE t.task_assigned_to = :userId';
            $params['userId'] = $userId;
        }

        $sql .= ' ORDER BY t.task_due_date ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTaskCount(?int $userId = null): int
    {
        $sql = 'SELECT COUNT(*) FROM tasks t';
        $params = [];

        if ($userId !== null) {
            $sql .= ' WHERE t.task_assigned_to = :userId';
            $params['userId'] = $userId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    public function getTaskCountByStatus(string $status, ?int $userId = null): int
    {
        $sql = 'SELECT COUNT(*) FROM tasks t WHERE t.task_status = :status';
        $params = ['status' => $status];

        if ($userId !== null) {
            $sql .= ' AND t.task_assigned_to = :userId';
            $params['userId'] = $userId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    public function getTasksPaginated(int $limit, int $offset, ?int $userId = null): array
    {
        $limit = max(1, $limit);
        $offset = max(0, $offset);

        $sql = 'SELECT t.task_id, t.task_title, t.task_description, t.task_due_date, t.task_status,
                   t.task_assigned_to, t.task_project_id,
                       p.project_title,
                       CONCAT(COALESCE(u.user_first_name, ""), " ", COALESCE(u.user_last_name, "")) AS assigned_name
                FROM tasks t
                JOIN projects p ON t.task_project_id = p.project_id
                LEFT JOIN users u ON t.task_assigned_to = u.user_id';

        if ($userId !== null) {
            $sql .= ' WHERE t.task_assigned_to = :userId';
        }

        $sql .= ' ORDER BY t.task_due_date ASC, t.task_id DESC LIMIT :limit OFFSET :offset';

        $stmt = $this->pdo->prepare($sql);

        if ($userId !== null) {
            $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTaskSummary(?int $userId = null): array
    {
        $sql = 'SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN t.task_status = :completed_status THEN 1 ELSE 0 END) AS completed,
                    SUM(CASE
                        WHEN t.task_status = :due_status_overdue AND t.task_due_date < CURDATE() THEN 1
                        ELSE 0
                    END) AS overdue,
                    SUM(CASE
                        WHEN t.task_status = :due_status_duesoon
                             AND t.task_due_date >= CURDATE()
                             AND t.task_due_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 1
                        ELSE 0
                    END) AS dueSoon
                FROM tasks t';

        $params = [
            'completed_status' => Constants::TASK_STATUS_COMPLETED,
            'due_status_overdue' => Constants::TASK_STATUS_DUE,
            'due_status_duesoon' => Constants::TASK_STATUS_DUE,
        ];

        if ($userId !== null) {
            $sql .= ' WHERE t.task_assigned_to = :userId';
            $params['userId'] = $userId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'total' => (int) ($row['total'] ?? 0),
            'completed' => (int) ($row['completed'] ?? 0),
            'overdue' => (int) ($row['overdue'] ?? 0),
            'dueSoon' => (int) ($row['dueSoon'] ?? 0),
        ];
    }

    public function getTaskById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT t.task_id, t.task_title, t.task_description, t.task_due_date, t.task_status,
                    t.task_assigned_to, t.task_project_id,
                    p.project_title,
                    CONCAT(COALESCE(u.user_first_name, ""), " ", COALESCE(u.user_last_name, "")) AS assigned_name
             FROM tasks t
             JOIN projects p ON t.task_project_id = p.project_id
             LEFT JOIN users u ON t.task_assigned_to = u.user_id
             WHERE t.task_id = :id'
        );

        $stmt->execute(['id' => $id]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);

        return $task ?: null;
    }

    public function createTask(array $data): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO tasks (task_title, task_description, task_project_id, task_assigned_to, task_due_date, task_created_by)
             VALUES (:title, :description, :project_id, :assigned_to, :due_date, :created_by)'
        );

        return $stmt->execute([
            'title' => $data['title'],
            'description' => $data['description'] !== '' ? $data['description'] : null,
            'project_id' => $data['project_id'],
            'assigned_to' => $data['assigned_to'],
            'due_date' => $data['due_date'],
            'created_by' => $data['created_by'],
        ]);
    }

    public function getLastInsertId(): int
    {
        return (int) $this->pdo->lastInsertId();
    }

    public function updateTask(array $data): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE tasks SET
                task_title = :title,
                task_description = :description,
                task_project_id = :project_id,
                task_assigned_to = :assigned_to,
                task_due_date = :due_date,
                task_status = :status,
                task_modified_by = :modified_by
             WHERE task_id = :id'
        );

        return $stmt->execute([
            'title' => $data['title'],
            'description' => $data['description'] !== '' ? $data['description'] : null,
            'project_id' => $data['project_id'],
            'assigned_to' => $data['assigned_to'],
            'due_date' => $data['due_date'],
            'status' => $data['status'],
            'modified_by' => $data['modified_by'],
            'id' => $data['id'],
        ]);
    }

    public function updateTaskStatus(int $taskId, string $status): bool
    {
        $modifierId = $_SESSION['user_id'] ?? null;

        $stmt = $this->pdo->prepare(
            'UPDATE tasks SET task_status = ?, task_modified_by = ? WHERE task_id = ?'
        );

        return $stmt->execute([
            $status,
            $modifierId,
            $taskId,
        ]);
    }

    public function deleteTask(int $taskId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM tasks WHERE task_id = :id');

        return $stmt->execute(['id' => $taskId]);
    }

    public function markComplete(int $taskId, int $userId, bool $isAdmin = false): bool
    {
        if ($isAdmin) {
            $stmt = $this->pdo->prepare(
                'UPDATE tasks SET task_status = "Completed", task_modified_by = :userId WHERE task_id = :taskId'
            );

            return $stmt->execute([
                'userId' => $userId,
                'taskId' => $taskId,
            ]);
        }

        $stmt = $this->pdo->prepare(
            'UPDATE tasks SET task_status = "Completed", task_modified_by = :userId
             WHERE task_id = :taskId AND task_assigned_to = :userId'
        );

        return $stmt->execute([
            'userId' => $userId,
            'taskId' => $taskId,
        ]);
    }

    public function taskNameExistsInProject(string $title, int $projectId): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM tasks WHERE task_title = ? AND task_project_id = ? AND task_status != "Deleted"');
        $stmt->execute([$title, $projectId]);

        return $stmt->rowCount() > 0;
    }

    public function taskNameExistsInProjectForOther(string $title, int $projectId, int $taskId): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM tasks WHERE task_title = ? AND task_project_id = ? AND task_id != ? AND task_status != "Deleted"');
        $stmt->execute([$title, $projectId, $taskId]);

        return $stmt->rowCount() > 0;
    }
}
