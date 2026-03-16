<?php

declare(strict_types=1);

class Project
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getAllProjects(): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT project_id, project_title, project_description, project_status, project_created_on
             FROM projects
             ORDER BY project_created_on DESC'
        );
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProjectCount(): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM projects');
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    public function getProjectCountByStatus(string $status): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM projects WHERE project_status = :status');
        $stmt->execute(['status' => $status]);

        return (int) $stmt->fetchColumn();
    }

    public function getProjectsPaginated(int $limit, int $offset): array
    {
        $limit = max(1, $limit);
        $offset = max(0, $offset);

        $stmt = $this->pdo->prepare(
            'SELECT project_id, project_title, project_description, project_status, project_created_on
             FROM projects
             ORDER BY project_created_on DESC
             LIMIT :limit OFFSET :offset'
        );

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProjectById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM projects WHERE project_id = ?');
        $stmt->execute([$id]);

        $project = $stmt->fetch(PDO::FETCH_ASSOC);

        return $project ?: null;
    }

    public function createProject(string $title, ?string $description, int $createdBy, string $status = 'Active'): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO projects (project_title, project_description, project_status, project_created_by)
             VALUES (:title, :description, :status, :created_by)'
        );

        return $stmt->execute([
            'title' => $title,
            'description' => $description,
            'status' => $status,
            'created_by' => $createdBy,
        ]);
    }

    public function getLastInsertId(): int
    {
        return (int) $this->pdo->lastInsertId();
    }

    public function updateProject(array $data): bool
    {
        $description = $data['desc'] ?? ($data['description'] ?? '');
        $status = $data['status'] ?? 'Active';

        $stmt = $this->pdo->prepare(
            'UPDATE projects SET project_title = ?, project_description = ?, project_status = ? WHERE project_id = ?'
        );

        return $stmt->execute([
            $data['title'],
            $description,
            $status,
            $data['id'],
        ]);
    }

    public function deactivateProject(int $id, ?int $modifiedBy = null): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE projects SET project_status = "Inactive", project_modified_by = :modified_by WHERE project_id = :id'
        );

        return $stmt->execute([
            'modified_by' => $modifiedBy,
            'id' => $id,
        ]);
    }

    public function projectNameExists(string $title): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM projects WHERE project_title = ? AND project_status = "Active"');
        $stmt->execute([$title]);

        return $stmt->rowCount() > 0;
    }

    public function projectNameExistsForOther(string $title, int $projectId): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM projects WHERE project_title = ? AND project_id != ? AND project_status = "Active"');
        $stmt->execute([$title, $projectId]);

        return $stmt->rowCount() > 0;
    }
}
