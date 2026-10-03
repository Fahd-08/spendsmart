<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * Algemene categorievoorstellen, beheerd door de contentbeheerder.
 */
final class CategorySuggestionRepository extends Repository
{
    public function all(): array
    {
        return $this->fetchAll(
            'SELECT s.id, s.name, s.type, s.description, s.is_active,
                    (SELECT COUNT(*) FROM categories c WHERE c.suggestion_id = s.id) AS adoption_count
             FROM category_suggestions s
             ORDER BY s.type DESC, s.name'
        );
    }

    public function active(): array
    {
        return $this->fetchAll(
            'SELECT id, name, type, description FROM category_suggestions
             WHERE is_active = 1 ORDER BY type DESC, name'
        );
    }

    public function find(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT id, name, type, description, is_active FROM category_suggestions WHERE id = :id',
            ['id' => $id]
        );
    }

    public function findActive(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT id, name, type, description FROM category_suggestions WHERE id = :id AND is_active = 1',
            ['id' => $id]
        );
    }

    public function nameExists(string $name, string $type, ?int $exceptId = null): bool
    {
        return (bool) $this->fetchValue(
            'SELECT COUNT(*) FROM category_suggestions WHERE name = :name AND type = :type AND id <> :except_id',
            ['name' => $name, 'type' => $type, 'except_id' => $exceptId ?? 0]
        );
    }

    /**
     * @param array{name: string, type: string, description: ?string, is_active: bool} $data
     */
    public function create(array $data, int $createdBy): int
    {
        return $this->insert(
            'INSERT INTO category_suggestions (name, type, description, is_active, created_by)
             VALUES (:name, :type, :description, :is_active, :created_by)',
            $this->columns($data) + ['created_by' => $createdBy]
        );
    }

    public function update(int $id, array $data): void
    {
        $this->execute(
            'UPDATE category_suggestions
             SET name = :name, type = :type, description = :description, is_active = :is_active
             WHERE id = :id',
            $this->columns($data) + ['id' => $id]
        );
    }

    public function delete(int $id): void
    {
        // Categorieën van gebruikers blijven bestaan; alleen de koppeling vervalt (ON DELETE SET NULL).
        $this->execute('DELETE FROM category_suggestions WHERE id = :id', ['id' => $id]);
    }

    private function columns(array $data): array
    {
        return [
            'name' => $data['name'],
            'type' => $data['type'],
            'description' => $data['description'],
            'is_active' => (int) $data['is_active'],
        ];
    }
}
