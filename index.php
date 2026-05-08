<?php
require_once __DIR__ . '/includes/functions.php';

$search = trim($_GET['search'] ?? '');
$moodFilter = trim($_GET['mood'] ?? '');
$entries = getAllEntries($search, $moodFilter);
$stats = getStats();

$moods = [
    'happy'     => ['emoji' => '☀️', 'label' => 'Happy',     'color' => '#F59E0B'],
    'grateful'  => ['emoji' => '🌸', 'label' => 'Grateful',  'color' => '#EC4899'],
    'neutral'   => ['emoji' => '🌊', 'label' => 'Neutral',   'color' => '#6366F1'],
    'anxious'   => ['emoji' => '🌩️', 'label' => 'Anxious',   'color' => '#8B5CF6'],
    'sad'       => ['emoji' => '🌧️', 'label' => 'Sad',       'color' => '#3B82F6'],
    'angry'     => ['emoji' => '🔥', 'label' => 'Angry',     'color' => '#EF4444'],
    'excited'   => ['emoji' => '⚡', 'label' => 'Excited',   'color' => '#10B981'],
];

$flash = '';
if (isset($_SESSION['flash'])) {
    session_start();
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
} else {
    session_start();
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Folio — Personal Journal</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,900;1,400;1,600&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
  :root {
    --ink:       #1A1614;
    --paper:     #F7F3ED;
    --cream:     #EDE8DF;
    --sienna:    #C1440E;
    --gold:      #D4A843;
    --muted:     #8C7B6B;
    --border:    #D9CFC3;
    --white:     #FEFCF9;
    --shadow:    rgba(26,22,20,.08);
  }

  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: 'DM Sans', sans-serif;
    background: var(--paper);
    color: var(--ink);
    min-height: 100vh;
    background-image:
      radial-gradient(ellipse 60% 40% at 80% 10%, rgba(212,168,67,.10) 0%, transparent 60%),
      radial-gradient(ellipse 50% 50% at 10% 90%, rgba(193,68,14,.06) 0%, transparent 60%);
  }

  /* ── Sidebar ── */
  .sidebar {
    position: fixed; top: 0; left: 0;
    width: 260px; height: 100vh;
    background: var(--ink);
    padding: 40px 28px;
    display: flex; flex-direction: column; gap: 32px;
    z-index: 100;
  }
  .brand { display: flex; flex-direction: column; gap: 4px; }
  .brand-name {
    font-family: 'Playfair Display', serif;
    font-size: 28px; font-weight: 900; color: var(--white);
    letter-spacing: -.5px;
  }
  .brand-sub { font-size: 11px; font-weight: 300; color: var(--muted); letter-spacing: 2px; text-transform: uppercase; }

  .divider { height: 1px; background: rgba(255,255,255,.08); }

  .stats-block { display: flex; flex-direction: column; gap: 12px; }
  .stat-row { display: flex; justify-content: space-between; align-items: center; }
  .stat-label { font-size: 11px; color: var(--muted); text-transform: uppercase; letter-spacing: 1.5px; }
  .stat-val { font-family: 'Playfair Display', serif; font-size: 22px; color: var(--white); font-weight: 600; }

  .mood-legend { display: flex; flex-direction: column; gap: 8px; }
  .mood-item {
    display: flex; align-items: center; gap: 10px;
    padding: 8px 12px; border-radius: 8px;
    font-size: 12px; color: rgba(255,255,255,.6);
    text-decoration: none; transition: background .15s;
    cursor: pointer;
  }
  .mood-item:hover, .mood-item.active { background: rgba(255,255,255,.08); color: var(--white); }
  .mood-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
  .mood-item .mood-count { margin-left: auto; font-size: 11px; color: var(--muted); }

  .sidebar-footer { margin-top: auto; }
  .new-btn {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    width: 100%; padding: 14px;
    background: var(--sienna); color: var(--white);
    border: none; border-radius: 10px;
    font-family: 'DM Sans', sans-serif; font-size: 14px; font-weight: 500;
    text-decoration: none; cursor: pointer;
    transition: background .2s, transform .15s;
  }
  .new-btn:hover { background: #a83a0c; transform: translateY(-1px); }

  /* ── Main ── */
  .main { margin-left: 260px; padding: 48px 48px 80px; min-height: 100vh; }

  .topbar {
    display: flex; align-items: center; gap: 16px;
    margin-bottom: 40px;
  }
  .search-wrap { position: relative; flex: 1; max-width: 480px; }
  .search-wrap svg { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--muted); }
  .search-input {
    width: 100%; padding: 12px 16px 12px 42px;
    background: var(--white); border: 1.5px solid var(--border);
    border-radius: 10px; font-family: 'DM Sans', sans-serif; font-size: 14px;
    color: var(--ink); outline: none; transition: border-color .2s;
  }
  .search-input:focus { border-color: var(--sienna); }
  .search-input::placeholder { color: var(--muted); }

  .page-title {
    font-family: 'Playfair Display', serif;
    font-size: 34px; font-weight: 900;
    color: var(--ink); margin-bottom: 8px;
  }
  .page-sub { font-size: 14px; color: var(--muted); margin-bottom: 36px; }

  /* ── Entries Grid ── */
  .entries-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
  }

  .entry-card {
    background: var(--white);
    border: 1.5px solid var(--border);
    border-radius: 14px;
    padding: 24px;
    display: flex; flex-direction: column; gap: 12px;
    transition: transform .2s, box-shadow .2s;
    position: relative; overflow: hidden;
    text-decoration: none; color: inherit;
  }
  .entry-card::before {
    content: '';
    position: absolute; top: 0; left: 0;
    width: 4px; height: 100%;
    background: var(--mood-color, var(--border));
    border-radius: 4px 0 0 4px;
  }
  .entry-card:hover { transform: translateY(-3px); box-shadow: 0 12px 32px var(--shadow); }

  .entry-meta { display: flex; align-items: center; justify-content: space-between; }
  .entry-date { font-size: 11px; color: var(--muted); letter-spacing: .5px; }
  .entry-mood-badge {
    font-size: 11px; padding: 3px 10px;
    border-radius: 20px; font-weight: 500;
    background: color-mix(in srgb, var(--mood-color, #888) 15%, transparent);
    color: var(--mood-color, #888);
  }

  .entry-title {
    font-family: 'Playfair Display', serif;
    font-size: 18px; font-weight: 600; line-height: 1.3;
    color: var(--ink);
  }

  .entry-preview {
    font-size: 13px; color: var(--muted); line-height: 1.7;
    display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;
  }

  .entry-tags { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 4px; }
  .tag {
    font-size: 11px; padding: 3px 10px;
    background: var(--cream); color: var(--muted);
    border-radius: 20px; letter-spacing: .3px;
  }

  .entry-actions {
    display: flex; gap: 8px; margin-top: auto; padding-top: 12px;
    border-top: 1px solid var(--border);
  }
  .btn-action {
    padding: 7px 14px; border-radius: 7px; font-size: 12px; font-weight: 500;
    border: none; cursor: pointer; font-family: 'DM Sans', sans-serif;
    text-decoration: none; transition: background .15s;
  }
  .btn-edit { background: var(--cream); color: var(--ink); }
  .btn-edit:hover { background: var(--border); }
  .btn-view { background: var(--sienna); color: var(--white); }
  .btn-view:hover { background: #a83a0c; }
  .btn-delete { background: transparent; color: #EF4444; border: 1px solid #FCA5A5; margin-left: auto; }
  .btn-delete:hover { background: #FEE2E2; }

  /* Empty state */
  .empty-state {
    grid-column: 1/-1;
    text-align: center; padding: 80px 40px;
  }
  .empty-icon {
    font-size: 64px; margin-bottom: 20px;
    display: block;
    animation: float 3s ease-in-out infinite;
  }
  @keyframes float { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-10px)} }
  .empty-title {
    font-family: 'Playfair Display', serif; font-size: 28px; font-weight: 600;
    color: var(--ink); margin-bottom: 10px;
  }
  .empty-sub { font-size: 15px; color: var(--muted); }

  /* Flash */
  .flash {
    padding: 14px 20px; border-radius: 10px; margin-bottom: 28px;
    font-size: 14px; font-weight: 500;
    background: #D1FAE5; color: #065F46; border: 1px solid #A7F3D0;
    animation: slideIn .3s ease;
  }
  @keyframes slideIn { from{transform:translateY(-10px);opacity:0} to{transform:translateY(0);opacity:1} }

  /* Responsive */
  @media (max-width: 768px) {
    .sidebar { display: none; }
    .main { margin-left: 0; padding: 24px 20px 60px; }
  }
</style>
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar">
  <div class="brand">
    <span class="brand-name">Folio</span>
    <span class="brand-sub">Personal Journal</span>
  </div>
  <div class="divider"></div>
  <div class="stats-block">
    <div class="stat-row">
      <span class="stat-label">Entries</span>
      <span class="stat-val"><?= $stats['total'] ?></span>
    </div>
    <?php if ($stats['latest']): ?>
    <div class="stat-row">
      <span class="stat-label">Last entry</span>
      <span style="font-size:12px;color:var(--muted)"><?= formatDateShort($stats['latest']) ?></span>
    </div>
    <?php endif; ?>
  </div>
  <div class="divider"></div>
  <div class="mood-legend">
    <a href="index.php" class="mood-item <?= $moodFilter === '' ? 'active' : '' ?>">
      <span class="mood-dot" style="background:#8C7B6B"></span>
      <span>All Entries</span>
      <span class="mood-count"><?= $stats['total'] ?></span>
    </a>
    <?php foreach ($moods as $key => $m):
      $cnt = 0;
      foreach ($stats['moods'] as $sm) { if ($sm['mood'] === $key) { $cnt = $sm['cnt']; break; } }
    ?>
    <a href="index.php?mood=<?= $key ?>" class="mood-item <?= $moodFilter === $key ? 'active' : '' ?>">
      <span class="mood-dot" style="background:<?= $m['color'] ?>"></span>
      <span><?= $m['emoji'] ?> <?= $m['label'] ?></span>
      <span class="mood-count"><?= $cnt ?></span>
    </a>
    <?php endforeach; ?>
  </div>
  <div class="sidebar-footer">
    <a href="create.php" class="new-btn">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
      New Entry
    </a>
  </div>
</aside>

<!-- Main Content -->
<main class="main">
  <?php if ($flash): ?>
    <div class="flash"><?= sanitize($flash) ?></div>
  <?php endif; ?>

  <h1 class="page-title">
    <?php if ($moodFilter && isset($moods[$moodFilter])): ?>
      <?= $moods[$moodFilter]['emoji'] ?> <?= $moods[$moodFilter]['label'] ?> Entries
    <?php elseif ($search): ?>
      Results for "<?= sanitize($search) ?>"
    <?php else: ?>
      My Journal
    <?php endif; ?>
  </h1>
  <p class="page-sub">
    <?= count($entries) ?> <?= count($entries) === 1 ? 'entry' : 'entries' ?>
    <?= $search ? ' found' : ' total' ?>
  </p>

  <div class="topbar">
    <form method="GET" action="index.php" class="search-wrap">
      <?php if ($moodFilter): ?><input type="hidden" name="mood" value="<?= sanitize($moodFilter) ?>"><?php endif; ?>
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>
      </svg>
      <input
        class="search-input" type="text" name="search"
        placeholder="Search entries, tags…" value="<?= sanitize($search) ?>"
        autocomplete="off"
      >
    </form>
    <?php if ($search || $moodFilter): ?>
      <a href="index.php" style="font-size:13px;color:var(--muted);text-decoration:none;">Clear</a>
    <?php endif; ?>
  </div>

  <div class="entries-grid">
    <?php if (empty($entries)): ?>
      <div class="empty-state">
        <span class="empty-icon">📖</span>
        <p class="empty-title">No entries yet</p>
        <p class="empty-sub">
          <?= $search ? 'No entries match your search.' : 'Start writing — your first page awaits.' ?>
        </p>
      </div>
    <?php else: ?>
      <?php foreach ($entries as $e):
        $mood = $moods[$e['mood']] ?? $moods['neutral'];
        $color = $mood['color'];
        $tags = array_filter(array_map('trim', explode(',', $e['tags'])));
      ?>
      <div class="entry-card" style="--mood-color:<?= $color ?>">
        <div class="entry-meta">
          <span class="entry-date"><?= formatDate($e['created_at']) ?></span>
          <span class="entry-mood-badge" style="--mood-color:<?= $color ?>">
            <?= $mood['emoji'] ?> <?= $mood['label'] ?>
          </span>
        </div>
        <h2 class="entry-title"><?= sanitize($e['title']) ?></h2>
        <p class="entry-preview"><?= sanitize($e['content']) ?></p>
        <?php if (!empty($tags)): ?>
          <div class="entry-tags">
            <?php foreach ($tags as $tag): ?>
              <span class="tag">#<?= sanitize($tag) ?></span>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        <div class="entry-actions">
          <a href="view.php?id=<?= $e['id'] ?>" class="btn-action btn-view">Read</a>
          <a href="edit.php?id=<?= $e['id'] ?>" class="btn-action btn-edit">Edit</a>
          <form method="POST" action="delete.php" style="margin-left:auto"
                onsubmit="return confirm('Delete this entry?')">
            <input type="hidden" name="id" value="<?= $e['id'] ?>">
            <button type="submit" class="btn-action btn-delete">Delete</button>
          </form>
        </div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</main>

</body>
</html>
