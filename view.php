<?php
require_once __DIR__ . '/includes/functions.php';
session_start();

$id = (int)($_GET['id'] ?? 0);
$entry = getEntry($id);

if (!$entry) {
    header('Location: index.php');
    exit;
}

$flash = '';
if (isset($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
}

$moods = [
    'happy'    => ['emoji' => '☀️', 'label' => 'Happy',    'color' => '#F59E0B'],
    'grateful' => ['emoji' => '🌸', 'label' => 'Grateful', 'color' => '#EC4899'],
    'neutral'  => ['emoji' => '🌊', 'label' => 'Neutral',  'color' => '#6366F1'],
    'anxious'  => ['emoji' => '🌩️', 'label' => 'Anxious',  'color' => '#8B5CF6'],
    'sad'      => ['emoji' => '🌧️', 'label' => 'Sad',      'color' => '#3B82F6'],
    'angry'    => ['emoji' => '🔥', 'label' => 'Angry',    'color' => '#EF4444'],
    'excited'  => ['emoji' => '⚡', 'label' => 'Excited',  'color' => '#10B981'],
];

$mood = $moods[$entry['mood']] ?? $moods['neutral'];
$tags = array_filter(array_map('trim', explode(',', $entry['tags'])));

$wordCount = str_word_count(strip_tags($entry['content']));
$readTime = max(1, ceil($wordCount / 200));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= sanitize($entry['title']) ?> — Folio</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,900;1,400;1,600&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
  :root {
    --ink: #1A1614; --paper: #F7F3ED; --cream: #EDE8DF;
    --sienna: #C1440E; --muted: #8C7B6B; --border: #D9CFC3; --white: #FEFCF9;
    --mood-color: <?= $mood['color'] ?>;
  }
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: 'DM Sans', sans-serif; background: var(--paper); color: var(--ink); min-height: 100vh;
    display: grid; grid-template-columns: 1fr min(720px,100%) 1fr; padding: 60px 20px 100px;
    background-image: radial-gradient(ellipse 60% 40% at 80% 10%, color-mix(in srgb, var(--mood-color) 8%, transparent) 0%, transparent 60%);
  }
  .container { grid-column: 2; }

  .nav { display: flex; align-items: center; justify-content: space-between; margin-bottom: 48px; }
  .back-link {
    display: inline-flex; align-items: center; gap: 8px;
    font-size: 13px; color: var(--muted); text-decoration: none; transition: color .15s;
  }
  .back-link:hover { color: var(--ink); }
  .nav-actions { display: flex; gap: 10px; }

  .btn { padding: 9px 20px; border-radius: 8px; font-size: 13px; font-weight: 500;
    font-family: 'DM Sans', sans-serif; text-decoration: none; cursor: pointer; border: none;
    transition: all .15s; display: inline-flex; align-items: center; gap: 6px; }
  .btn-edit { background: var(--white); color: var(--ink); border: 1.5px solid var(--border); }
  .btn-edit:hover { border-color: var(--ink); }
  .btn-del { background: transparent; color: #EF4444; border: 1.5px solid #FCA5A5; }
  .btn-del:hover { background: #FEE2E2; }

  .flash {
    padding: 14px 20px; border-radius: 10px; margin-bottom: 28px;
    font-size: 14px; background: #D1FAE5; color: #065F46; border: 1px solid #A7F3D0;
    animation: slideIn .3s ease;
  }
  @keyframes slideIn { from{transform:translateY(-8px);opacity:0} to{transform:translateY(0);opacity:1} }

  /* Mood banner */
  .mood-banner {
    display: inline-flex; align-items: center; gap: 10px;
    padding: 8px 18px; border-radius: 30px;
    background: color-mix(in srgb, var(--mood-color) 12%, transparent);
    border: 1px solid color-mix(in srgb, var(--mood-color) 30%, transparent);
    margin-bottom: 24px;
  }
  .mood-banner span { font-size: 13px; font-weight: 500; color: var(--mood-color); }

  .entry-title {
    font-family: 'Playfair Display', serif;
    font-size: clamp(32px,5vw,48px); font-weight: 900; line-height: 1.15;
    margin-bottom: 18px; color: var(--ink);
  }

  .entry-meta {
    display: flex; align-items: center; gap: 20px; margin-bottom: 40px;
    font-size: 13px; color: var(--muted); flex-wrap: wrap;
  }
  .meta-item { display: flex; align-items: center; gap: 6px; }

  .divider { height: 2px; background: linear-gradient(90deg, var(--border) 0%, transparent 100%); margin-bottom: 40px; }

  .entry-body {
    font-family: 'Playfair Display', serif;
    font-size: 19px; line-height: 1.85; color: var(--ink);
    white-space: pre-wrap; letter-spacing: .01em;
  }

  .tags-section { margin-top: 48px; padding-top: 28px; border-top: 1px solid var(--border); }
  .tags-label { font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; color: var(--muted); margin-bottom: 12px; }
  .tags-list { display: flex; flex-wrap: wrap; gap: 8px; }
  .tag {
    font-size: 13px; padding: 5px 14px;
    background: var(--cream); color: var(--muted);
    border-radius: 20px;
  }

  .updated-note { font-size: 12px; color: var(--border); margin-top: 40px; text-align: right; }

  @media (max-width: 600px) {
    body { padding: 32px 16px 60px; }
  }
</style>
</head>
<body>
<div class="container">

  <?php if ($flash): ?>
    <div class="flash"><?= sanitize($flash) ?></div>
  <?php endif; ?>

  <nav class="nav">
    <a href="index.php" class="back-link">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
      All entries
    </a>
    <div class="nav-actions">
      <a href="edit.php?id=<?= $entry['id'] ?>" class="btn btn-edit">
        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
          <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
        </svg>
        Edit
      </a>
      <form method="POST" action="delete.php" onsubmit="return confirm('Delete this entry permanently?')">
        <input type="hidden" name="id" value="<?= $entry['id'] ?>">
        <button type="submit" class="btn btn-del">
          <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/>
            <path d="M10 11v6M14 11v6M9 6V4h6v2"/>
          </svg>
          Delete
        </button>
      </form>
    </div>
  </nav>

  <div class="mood-banner">
    <span style="font-size:20px"><?= $mood['emoji'] ?></span>
    <span><?= $mood['label'] ?></span>
  </div>

  <h1 class="entry-title"><?= sanitize($entry['title']) ?></h1>

  <div class="entry-meta">
    <span class="meta-item">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
      <?= formatDate($entry['created_at']) ?>
    </span>
    <span class="meta-item">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
      ~<?= $readTime ?> min read · <?= number_format($wordCount) ?> words
    </span>
  </div>

  <div class="divider"></div>

  <div class="entry-body"><?= sanitize($entry['content']) ?></div>

  <?php if (!empty($tags)): ?>
  <div class="tags-section">
    <p class="tags-label">Tags</p>
    <div class="tags-list">
      <?php foreach ($tags as $tag): ?>
        <a href="index.php?search=<?= urlencode($tag) ?>" class="tag">#<?= sanitize($tag) ?></a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($entry['updated_at'] !== $entry['created_at']): ?>
    <p class="updated-note">Last edited <?= formatDate($entry['updated_at']) ?></p>
  <?php endif; ?>

</div>
</body>
</html>
