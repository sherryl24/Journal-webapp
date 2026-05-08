<?php
require_once __DIR__ . '/includes/functions.php';
session_start();

$errors = [];
$old = ['title' => '', 'content' => '', 'mood' => 'neutral', 'tags' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title   = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $mood    = trim($_POST['mood'] ?? 'neutral');
    $tags    = trim($_POST['tags'] ?? '');

    if ($title === '') $errors[] = 'Title is required.';
    if ($content === '') $errors[] = 'Content is required.';

    $validMoods = ['happy','grateful','neutral','anxious','sad','angry','excited'];
    if (!in_array($mood, $validMoods)) $mood = 'neutral';

    if (empty($errors)) {
        $id = createEntry($title, $content, $mood, $tags);
        $_SESSION['flash'] = '✨ Entry created successfully!';
        header('Location: view.php?id=' . $id);
        exit;
    }

    $old = compact('title', 'content', 'mood', 'tags');
}

$moods = [
    'happy'     => ['emoji' => '☀️', 'label' => 'Happy',     'color' => '#F59E0B'],
    'grateful'  => ['emoji' => '🌸', 'label' => 'Grateful',  'color' => '#EC4899'],
    'neutral'   => ['emoji' => '🌊', 'label' => 'Neutral',   'color' => '#6366F1'],
    'anxious'   => ['emoji' => '🌩️', 'label' => 'Anxious',   'color' => '#8B5CF6'],
    'sad'       => ['emoji' => '🌧️', 'label' => 'Sad',       'color' => '#3B82F6'],
    'angry'     => ['emoji' => '🔥', 'label' => 'Angry',     'color' => '#EF4444'],
    'excited'   => ['emoji' => '⚡', 'label' => 'Excited',   'color' => '#10B981'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>New Entry — Folio</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,900;1,400;1,600&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
  :root {
    --ink: #1A1614; --paper: #F7F3ED; --cream: #EDE8DF;
    --sienna: #C1440E; --gold: #D4A843; --muted: #8C7B6B;
    --border: #D9CFC3; --white: #FEFCF9;
  }
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'DM Sans', sans-serif; background: var(--paper); color: var(--ink); min-height: 100vh;
    display: grid; grid-template-columns: 1fr min(680px,100%) 1fr; padding: 60px 20px 80px;
    background-image: radial-gradient(ellipse 60% 40% at 80% 10%, rgba(212,168,67,.10) 0%, transparent 60%);
  }
  .container { grid-column: 2; }

  .back-link {
    display: inline-flex; align-items: center; gap: 8px;
    font-size: 13px; color: var(--muted); text-decoration: none;
    margin-bottom: 32px; transition: color .15s;
  }
  .back-link:hover { color: var(--ink); }

  .page-header { margin-bottom: 40px; }
  .page-title { font-family: 'Playfair Display', serif; font-size: 40px; font-weight: 900; line-height: 1.1; }
  .page-sub { font-size: 14px; color: var(--muted); margin-top: 8px; }

  .form-card {
    background: var(--white);
    border: 1.5px solid var(--border);
    border-radius: 18px;
    padding: 40px;
  }

  .errors {
    background: #FEE2E2; border: 1px solid #FCA5A5; border-radius: 10px;
    padding: 14px 18px; margin-bottom: 28px;
  }
  .errors p { font-size: 13px; color: #991B1B; line-height: 1.8; }

  .form-group { margin-bottom: 28px; }
  label { display: block; font-size: 11px; font-weight: 500; letter-spacing: 1.5px; text-transform: uppercase;
    color: var(--muted); margin-bottom: 10px; }

  input[type="text"], textarea {
    width: 100%; padding: 14px 16px;
    background: var(--paper); border: 1.5px solid var(--border);
    border-radius: 10px; font-family: 'DM Sans', sans-serif; font-size: 15px;
    color: var(--ink); outline: none; transition: border-color .2s;
  }
  input[type="text"]:focus, textarea:focus { border-color: var(--sienna); background: var(--white); }

  .title-input { font-family: 'Playfair Display', serif; font-size: 22px; font-weight: 600; }

  textarea { resize: vertical; min-height: 220px; line-height: 1.8; }

  /* Mood selector */
  .mood-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 8px; }
  .mood-option { display: none; }
  .mood-label {
    display: flex; flex-direction: column; align-items: center; gap: 4px;
    padding: 10px 4px; border-radius: 10px; cursor: pointer;
    border: 1.5px solid var(--border);
    transition: all .15s; text-align: center;
  }
  .mood-emoji { font-size: 22px; line-height: 1; }
  .mood-text { font-size: 10px; color: var(--muted); font-weight: 500; letter-spacing: .3px; }
  .mood-option:checked + .mood-label {
    border-color: var(--mood-color);
    background: color-mix(in srgb, var(--mood-color) 10%, transparent);
  }
  .mood-label:hover { border-color: var(--mood-color); }

  .hint { font-size: 12px; color: var(--muted); margin-top: 8px; }

  .form-actions { display: flex; align-items: center; gap: 12px; margin-top: 36px; padding-top: 28px; border-top: 1px solid var(--border); }
  .btn-submit {
    padding: 14px 32px; background: var(--sienna); color: var(--white);
    border: none; border-radius: 10px;
    font-family: 'DM Sans', sans-serif; font-size: 15px; font-weight: 500;
    cursor: pointer; transition: background .2s, transform .15s;
  }
  .btn-submit:hover { background: #a83a0c; transform: translateY(-1px); }
  .btn-cancel { font-size: 14px; color: var(--muted); text-decoration: none; padding: 14px 16px; }
  .btn-cancel:hover { color: var(--ink); }

  .char-count { font-size: 12px; color: var(--muted); margin-top: 6px; text-align: right; }
</style>
</head>
<body>
<div class="container">
  <a href="index.php" class="back-link">
    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
    Back to journal
  </a>

  <div class="page-header">
    <h1 class="page-title">New Entry</h1>
    <p class="page-sub"><?= date('l, F j, Y') ?></p>
  </div>

  <?php if (!empty($errors)): ?>
    <div class="errors">
      <?php foreach ($errors as $e): ?><p>⚠ <?= sanitize($e) ?></p><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="form-card">
    <form method="POST" action="create.php">

      <div class="form-group">
        <label for="title">Title</label>
        <input class="title-input" type="text" id="title" name="title"
               placeholder="What's on your mind today?" maxlength="200"
               value="<?= sanitize($old['title']) ?>" autocomplete="off" required>
      </div>

      <div class="form-group">
        <label>How are you feeling?</label>
        <div class="mood-grid">
          <?php foreach ($moods as $key => $m): ?>
          <div>
            <input type="radio" class="mood-option" id="mood_<?= $key ?>" name="mood" value="<?= $key ?>"
              <?= $old['mood'] === $key ? 'checked' : '' ?>>
            <label class="mood-label" for="mood_<?= $key ?>"
                   style="--mood-color:<?= $m['color'] ?>">
              <span class="mood-emoji"><?= $m['emoji'] ?></span>
              <span class="mood-text"><?= $m['label'] ?></span>
            </label>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="form-group">
        <label for="content">Your thoughts</label>
        <textarea id="content" name="content"
                  placeholder="Write freely. This space is yours…"><?= sanitize($old['content']) ?></textarea>
        <div class="char-count" id="charCount">0 characters</div>
      </div>

      <div class="form-group">
        <label for="tags">Tags <span style="font-weight:300;text-transform:none;letter-spacing:0">(optional)</span></label>
        <input type="text" id="tags" name="tags"
               placeholder="gratitude, reflection, goals (comma-separated)"
               value="<?= sanitize($old['tags']) ?>">
        <p class="hint">Separate multiple tags with commas.</p>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn-submit">Save Entry ✦</button>
        <a href="index.php" class="btn-cancel">Cancel</a>
      </div>
    </form>
  </div>
</div>

<script>
  const ta = document.getElementById('content');
  const cc = document.getElementById('charCount');
  function updateCount() { cc.textContent = ta.value.length.toLocaleString() + ' characters'; }
  ta.addEventListener('input', updateCount);
  updateCount();
</script>
</body>
</html>
