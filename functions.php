<?php
require_once __DIR__ . '/db.php';

// CREATE
function createEntry(string $title, string $content, string $mood, string $tags): int {
    $db = getDB();
    $stmt = $db->prepare("
        INSERT INTO entries (title, content, mood, tags, created_at, updated_at)
        VALUES (:title, :content, :mood, :tags, datetime('now'), datetime('now'))
    ");
    $stmt->execute([
        ':title'   => trim($title),
        ':content' => trim($content),
        ':mood'    => $mood,
        ':tags'    => trim($tags),
    ]);
    return (int)$db->lastInsertId();
}

// READ ALL
function getAllEntries(string $search = '', string $mood = ''): array {
    $db = getDB();
    $sql = "SELECT * FROM entries WHERE 1=1";
    $params = [];

    if ($search !== '') {
        $sql .= " AND (title LIKE :search OR content LIKE :search OR tags LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }
    if ($mood !== '') {
        $sql .= " AND mood = :mood";
        $params[':mood'] = $mood;
    }

    $sql .= " ORDER BY created_at DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// READ ONE
function getEntry(int $id): ?array {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM entries WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

// UPDATE
function updateEntry(int $id, string $title, string $content, string $mood, string $tags): bool {
    $db = getDB();
    $stmt = $db->prepare("
        UPDATE entries
        SET title = :title, content = :content, mood = :mood, tags = :tags,
            updated_at = datetime('now')
        WHERE id = :id
    ");
    $stmt->execute([
        ':id'      => $id,
        ':title'   => trim($title),
        ':content' => trim($content),
        ':mood'    => $mood,
        ':tags'    => trim($tags),
    ]);
    return $stmt->rowCount() > 0;
}

// DELETE
function deleteEntry(int $id): bool {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM entries WHERE id = :id");
    $stmt->execute([':id' => $id]);
    return $stmt->rowCount() > 0;
}

// STATS
function getStats(): array {
    $db = getDB();
    $total = $db->query("SELECT COUNT(*) FROM entries")->fetchColumn();
    $moods = $db->query("SELECT mood, COUNT(*) as cnt FROM entries GROUP BY mood ORDER BY cnt DESC")->fetchAll();
    $latest = $db->query("SELECT created_at FROM entries ORDER BY created_at DESC LIMIT 1")->fetchColumn();
    return ['total' => $total, 'moods' => $moods, 'latest' => $latest];
}

function sanitize(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function formatDate(string $dt): string {
    return date('F j, Y · g:i A', strtotime($dt));
}

function formatDateShort(string $dt): string {
    return date('M j', strtotime($dt));
}
