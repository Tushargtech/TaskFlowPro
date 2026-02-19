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
        $stmt = $this->pdo->query('SELECT project_id, project_title, project_description, project_status, project_created_on FROM projects ORDER BY project_created_on DESC');

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProjectById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT project_id, project_title, project_description, project_status, project_created_on
             FROM projects
             WHERE project_id = :id'
        );

        $stmt->execute(['id' => $id]);
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

    public function updateProject(array $data): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE projects SET
                project_title = :title,
                project_description = :description,
                project_status = :status,
                project_modified_by = :modified_by
             WHERE project_id = :id'
        );

        return $stmt->execute([
            'title' => $data['title'],
            'description' => $data['description'] !== '' ? $data['description'] : null,
            'status' => $data['status'],
            'modified_by' => $data['modified_by'],
            'id' => $data['id'],
        ]);
    }
}
