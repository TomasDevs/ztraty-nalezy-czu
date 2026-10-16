<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class BuildingRepository
{
    private const COLUMNS = 'id, slug, name, short, lat, lng';

    public function __construct(private readonly PDO $db)
    {
    }

    public function all(): array
    {
        return array_map(
            $this->hydrate(...),
            $this->db->query('SELECT ' . self::COLUMNS . ' FROM buildings ORDER BY name')->fetchAll()
        );
    }

    public function find(int $id): ?array
    {
        return $this->fetchOne('id', $id);
    }

    public function findBySlug(string $slug): ?array
    {
        return $this->fetchOne('slug', $slug);
    }

    /**
     * @param array{name: string, short: string, slug: string, lat: float, lng: float} $data
     */
    public function create(array $data): int
    {
        $this->db
            ->prepare('INSERT INTO buildings (slug, name, short, lat, lng) VALUES (?, ?, ?, ?, ?)')
            ->execute([$data['slug'], $data['name'], $data['short'], $data['lat'], $data['lng']]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $this->db
            ->prepare('UPDATE buildings SET slug = ?, name = ?, short = ?, lat = ?, lng = ? WHERE id = ?')
            ->execute([$data['slug'], $data['name'], $data['short'], $data['lat'], $data['lng'], $id]);
    }

    public function isUsed(int $id): bool
    {
        $statement = $this->db->prepare('SELECT COUNT(*) FROM items WHERE building_id = ?');
        $statement->execute([$id]);

        return (int) $statement->fetchColumn() > 0;
    }

    public function delete(int $id): void
    {
        $this->db->prepare('DELETE FROM buildings WHERE id = ?')->execute([$id]);
    }

    private function fetchOne(string $column, int|string $value): ?array
    {
        $statement = $this->db->prepare('SELECT ' . self::COLUMNS . " FROM buildings WHERE {$column} = ?");
        $statement->execute([$value]);
        $row = $statement->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    private function hydrate(array $row): array
    {
        return [...$row, 'id' => (int) $row['id'], 'lat' => (float) $row['lat'], 'lng' => (float) $row['lng']];
    }
}
