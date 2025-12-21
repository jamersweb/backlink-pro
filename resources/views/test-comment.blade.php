<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Test Comment Form</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; }
        .container { max-width: 640px; margin: auto; }
        textarea { width: 100%; height: 120px; padding: 10px; }
        button { margin-top: 10px; padding: 8px 14px; }
        .comments { margin-top: 20px; }
        .comment { padding: 8px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 8px; }
        .success { color: green; margin-top: 10px; }
    </style>
</head>
<body>
<div class="container">
    <h2>Test Comment Form</h2>
    <p>This page is for testing the automation. Submitting will render your comment below.</p>

    <form id="comment-form">
        <label for="comment-text">Comment</label><br>
        <textarea id="comment-text" name="comment" placeholder="Enter your comment..."></textarea><br>
        <button id="submit-comment" type="submit">Post Comment</button>
    </form>

    <div id="status" class="success" style="display:none;">Comment submitted!</div>

    <div class="comments" id="comments"></div>
</div>

<script>
    document.getElementById('comment-form').addEventListener('submit', function (e) {
        e.preventDefault();
        const text = document.getElementById('comment-text').value.trim();
        if (!text) return;

        const comments = document.getElementById('comments');
        const div = document.createElement('div');
        div.className = 'comment';
        div.textContent = text;
        comments.prepend(div);

        document.getElementById('status').style.display = 'block';
        document.getElementById('comment-text').value = '';
    });
</script>
</body>
</html>

