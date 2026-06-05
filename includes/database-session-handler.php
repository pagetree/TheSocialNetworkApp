<?php

declare(strict_types=1);

final class DatabaseSessionHandler implements SessionHandlerInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        $stmt = $this->pdo->prepare(
            'SELECT payload FROM app_sessions WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $payload = $stmt->fetchColumn();

        return $payload === false ? '' : (string) $payload;
    }

    public function write(string $id, string $data): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO app_sessions (id, payload, last_activity)
             VALUES (:id, :payload, NOW())
             ON CONFLICT (id) DO UPDATE SET
                payload = EXCLUDED.payload,
                last_activity = NOW()'
        );

        return $stmt->execute([
            'id' => $id,
            'payload' => $data,
        ]);
    }

    public function destroy(string $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM app_sessions WHERE id = :id');

        return $stmt->execute(['id' => $id]);
    }

    public function gc(int $max_lifetime): int|false
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM app_sessions WHERE last_activity < NOW() - (:seconds || \' seconds\')::interval'
        );
        $stmt->execute(['seconds' => max(1, $max_lifetime)]);

        return $stmt->rowCount();
    }
}

function databaseSessionStorageAvailable(): bool
{
    static $available = null;

    if ($available !== null) {
        return $available;
    }

    try {
        $pdo = createPdoConnection();
        $stmt = $pdo->query("SELECT to_regclass('public.app_sessions')");
        $available = $stmt !== false && $stmt->fetchColumn() !== null;
    } catch (Throwable) {
        $available = false;
    }

    return $available;
}
