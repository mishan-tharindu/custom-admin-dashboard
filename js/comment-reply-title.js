document.addEventListener('DOMContentLoaded', function () {
    const title = document.getElementById('reply-title');
    if (!title) return;

    const comments = document.querySelectorAll('#comments > li');
    const count = comments.length;

    let text = 'No Comments';
    if (count === 1) text = '1 Comment';
    if (count > 1) text = count + ' Comments';

    title.setAttribute('data-title', text);
});
