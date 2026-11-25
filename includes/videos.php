<?php

declare(strict_types=1);

/** Creates the premium_videos table on first use (also available as database/premium_videos_migration.sql). */
function ensurePremiumVideosTable(PDO $pdo): void
{
    static $done = false;

    if ($done) {
        return;
    }
    $pdo->exec("CREATE TABLE IF NOT EXISTS premium_videos (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(160) NOT NULL,
        description TEXT NULL,
        youtube_id VARCHAR(20) NOT NULL,
        category ENUM('workout','diet','general') NOT NULL DEFAULT 'workout',
        fitness_level ENUM('all','beginner','intermediate','expert') NOT NULL DEFAULT 'all',
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_by BIGINT UNSIGNED NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $done = true;
}

/** Extracts the 11-character video id from a YouTube link (watch, youtu.be, shorts, embed, live) or returns null. */
function youtubeIdFromUrl(string $url): ?string
{
    $url = trim($url);

    if ($url === '') {
        return null;
    }

    if (!preg_match('#^https?://#i', $url)) {
        $url = 'https://' . $url;
    }
    $parts = parse_url($url);

    if (!$parts || empty($parts['host'])) {
        return null;
    }
    $host = strtolower(preg_replace('/^(www\.|m\.)/', '', $parts['host']));
    $path = $parts['path'] ?? '';
    $id = null;

    if ($host === 'youtu.be') {
        $id = trim($path, '/');
    } elseif ($host === 'youtube.com' || $host === 'youtube-nocookie.com') {
        if (preg_match('#^/(embed|shorts|live|v)/([^/?]+)#', $path, $m)) {
            $id = $m[2];
        } elseif ($path === '/watch') {
            parse_str($parts['query'] ?? '', $qs);
            $id = $qs['v'] ?? null;
        }
    }

    return (is_string($id) && preg_match('/^[A-Za-z0-9_-]{11}$/', $id)) ? $id : null;
}
