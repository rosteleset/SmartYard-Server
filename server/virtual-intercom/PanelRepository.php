<?php

namespace VirtualIntercom;

/** Uses existing custom fields; no changes to the database schema. */
final class PanelRepository
{
    public const FLAT_NAME = 'virtualIntercomName';
    public const FLAT_CALLS = 'virtualIntercomCallsEnabled';
    private const SETTINGS = 'virtualIntercom';
    private const SLUG = 'virtualIntercomSlug';
    private const FIELDS = ['enabled', 'title', 'subtitle', 'listEnabled', 'allowAllFlats'];

    public function __construct(private $db) {}

    public static function settings(array $values): array
    {
        return array_intersect_key($values, array_flip(self::FIELDS));
    }

    public function registerFields(): void
    {
        // RBT removes values of unregistered fields every five minutes.
        // Use its existing catalog; generic forms must not edit these values.
        $q = $this->db->prepare("INSERT INTO custom_fields
            (apply_to, catalog, type, field, field_display, editor, \"add\", modify)
            VALUES ('entrance', 'virtualIntercom', 'text', :field, :display, 'text', 0, 0)
            ON CONFLICT (field) DO NOTHING");
        $check = $this->db->prepare('SELECT apply_to FROM custom_fields WHERE field = :field');
        foreach ([self::SETTINGS => 'Виртуальный домофон: настройки', self::SLUG => 'Виртуальный домофон: ссылка'] as $field => $display) {
            $q->execute(['field' => $field, 'display' => $display]);
            $check->execute(['field' => $field]);
            if ($check->fetchColumn() !== 'entrance') throw new \RuntimeException('Virtual intercom custom field name is already in use');
        }
        // Public apartment names are edited by the native apartment form.
        $q = $this->db->prepare("INSERT INTO custom_fields
            (apply_to, catalog, type, field, field_display, field_description, editor, regex, \"add\", modify, tab)
            VALUES ('flat', 'virtualIntercom', 'text', :field, 'addresses.virtualIntercomFlatName',
                'addresses.virtualIntercomFlatNameHint', 'text', '^.{0,120}$', 1, 1, 'addresses.virtualIntercom')
            ON CONFLICT (field) DO NOTHING");
        $q->execute(['field' => self::FLAT_NAME]);
        $check->execute(['field' => self::FLAT_NAME]);
        if ($check->fetchColumn() !== 'flat') throw new \RuntimeException('Virtual intercom apartment name field is already in use');
        $q = $this->db->prepare("INSERT INTO custom_fields
            (apply_to, catalog, type, field, field_display, field_description, editor, \"add\", modify, tab)
            VALUES ('flat', 'virtualIntercom', 'text', :field, 'addresses.virtualIntercomFlatCalls',
                'addresses.virtualIntercomFlatCallsHint', 'noyes', 1, 1, 'addresses.virtualIntercom')
            ON CONFLICT (field) DO NOTHING");
        $q->execute(['field' => self::FLAT_CALLS]);
        $check->execute(['field' => self::FLAT_CALLS]);
        if ($check->fetchColumn() !== 'flat') throw new \RuntimeException('Virtual intercom apartment permission field is already in use');
    }

    public function flatCallsEnabled(int $flatId): bool
    {
        $q = $this->db->prepare("SELECT value FROM custom_fields_values WHERE apply_to = 'flat' AND id = :id AND field = :field");
        $q->execute(['id' => $flatId, 'field' => self::FLAT_CALLS]);
        return $q->fetchColumn() === '1';
    }

    private function find(string $condition, array $params): ?array
    {
        // Resolve public codes through the existing custom-field value index.
        $q = $this->db->prepare("SELECT settings.id, settings.value, slug.value AS slug
            FROM custom_fields_values settings
            JOIN custom_fields_values slug ON slug.apply_to = settings.apply_to AND slug.id = settings.id AND slug.field = :slug_field
            JOIN houses_entrances entrance ON entrance.house_entrance_id = settings.id
            WHERE settings.apply_to = 'entrance' AND settings.field = :settings_field AND $condition");
        $q->execute($params + ['slug_field' => self::SLUG, 'settings_field' => self::SETTINGS]);
        $row = $q->fetch(\PDO::FETCH_ASSOC);
        if (!$row) return null;
        $panel = json_decode($row['value'], true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($panel)) throw new \RuntimeException('Invalid virtual intercom settings');
        $panel = self::settings($panel);
        $panel['entranceId'] = (int)$row['id'];
        $panel['slug'] = $row['slug'];
        return $panel;
    }

    public function bySlug(string $slug): ?array
    {
        return $this->find('slug.value = :slug', ['slug' => $slug]);
    }

    public function byEntrance(int $entranceId): ?array
    {
        return $this->find('settings.id = :id', ['id' => $entranceId]);
    }

    private function upsert(int $id, string $field, string $value): void
    {
        $q = $this->db->prepare("INSERT INTO custom_fields_values (apply_to, id, field, value)
            VALUES ('entrance', :id, :field, :value)
            ON CONFLICT (apply_to, id, field) DO UPDATE SET value = excluded.value");
        $q->execute(['id' => $id, 'field' => $field, 'value' => $value]);
    }

    private function locked(int $entranceId, callable $callback): mixed
    {
        $ownTransaction = !$this->db->inTransaction();
        if ($ownTransaction) $this->db->beginTransaction();
        try {
            // Serializes first saves without updating the entrance or queuing
            // physical provisioning. SQLite is used only by isolated tests.
            $lock = $this->db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'pgsql' ? ' FOR UPDATE' : '';
            $q = $this->db->prepare('SELECT house_entrance_id FROM houses_entrances WHERE house_entrance_id = :id' . $lock);
            $q->execute(['id' => $entranceId]);
            if (!$q->fetchColumn()) throw new \RuntimeException('Вход не найден', 404);
            $result = $callback();
            if ($ownTransaction) $this->db->commit();
            return $result;
        } catch (\Throwable $error) {
            if ($ownTransaction && $this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }

    public function save(int $entranceId, array $values): array
    {
        return $this->locked($entranceId, function () use ($entranceId, $values) {
            $panel = $this->byEntrance($entranceId) ?? [];
            if (!$panel) {
                $this->upsert($entranceId, self::SLUG, strtr(base64_encode(random_bytes(9)), '+/', '-_'));
            }
            $panel = self::settings($values);
            $this->upsert($entranceId, self::SETTINGS, json_encode($panel, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
            return $this->byEntrance($entranceId);
        });
    }

}
