<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * Algemene leerteksten, beheerd door de contentbeheerder (FE-11).
 * Een tekst is een concept (is_published = 0) of gepubliceerd (is_published = 1).
 */
final class TipRepository extends Repository
{
    /**
     * Alle gepubliceerde teksten, nieuwste eerst (pagina Tips voor gebruikers).
     */
    public function published(): array
    {
        return $this->fetchAll(
            'SELECT id, title, body, published_at FROM tips
             WHERE is_published = 1 ORDER BY published_at DESC, id DESC'
        );
    }

    /**
     * Eén willekeurige gepubliceerde tekst (voor de startpagina en het dashboard).
     */
    public function randomPublished(): ?array
    {
        return $this->fetchOne(
            'SELECT id, title, body FROM tips WHERE is_published = 1 ORDER BY RAND() LIMIT 1'
        );
    }

    /**
     * Alle teksten, ook concepten, met de naam van de schrijver (voor de beheerpagina).
     * LEFT JOIN: ook teksten waarvan de schrijver niet meer bestaat komen mee.
     */
    public function all(): array
    {
        return $this->fetchAll(
            'SELECT t.id, t.title, t.is_published, t.published_at, t.updated_at, u.name AS author_name
             FROM tips t LEFT JOIN users u ON u.id = t.author_id
             ORDER BY t.updated_at DESC, t.id DESC'
        );
    }

    /**
     * Eén tekst.
     */
    public function find(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT id, title, body, is_published, published_at FROM tips WHERE id = :id',
            ['id' => $id]
        );
    }

    /**
     * Nieuwe tekst opslaan. Bij direct publiceren wordt de publicatiedatum NOW().
     *
     * @param array{title: string, body: string, is_published: bool} $data
     */
    public function create(array $data, int $authorId): int
    {
        return $this->insert(
            'INSERT INTO tips (title, body, is_published, published_at, author_id)
             VALUES (:title, :body, :is_published, IF(:publish_check = 1, NOW(), NULL), :author_id)',
            [
                'title' => $data['title'],
                'body' => $data['body'],
                'is_published' => (int) $data['is_published'],
                'publish_check' => (int) $data['is_published'], // zelfde waarde, tweede plek in de query
                'author_id' => $authorId,
            ]
        );
    }

    /**
     * Bij (opnieuw) publiceren krijgt de tekst een publicatiedatum als die er nog niet was.
     * Bij terugzetten naar concept wordt de publicatiedatum leeggemaakt.
     */
    public function update(int $id, array $data): void
    {
        $this->execute(
            'UPDATE tips
             SET title = :title, body = :body, is_published = :is_published,
                 published_at = CASE
                     WHEN :publish_check = 1 THEN COALESCE(published_at, NOW())
                     ELSE NULL
                 END
             WHERE id = :id',
            [
                'title' => $data['title'],
                'body' => $data['body'],
                'is_published' => (int) $data['is_published'],
                'publish_check' => (int) $data['is_published'],
                'id' => $id,
            ]
        );
    }

    /**
     * Tekst verwijderen.
     */
    public function delete(int $id): void
    {
        $this->execute('DELETE FROM tips WHERE id = :id', ['id' => $id]);
    }
}
