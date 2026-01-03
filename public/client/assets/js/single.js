$(document).ready(function() {
    $('.like-btn').on('click', function() {
        var button = $(this);
        
        button.toggleClass('liked');
        
        if (button.hasClass('liked')) {
            button.text('Đã thích');
        } else {
            button.text('Thích');
        }
    });

    $('.reply-btn').on('click', function() {
        var replyForm = $(this).closest('.comment-content').find('.reply-form');
        
        replyForm.toggle();
    });
    
    $('.submit-reply').on('click', function() {
        var replyText = $(this).prev('textarea').val();
        
        if (replyText.trim() !== '') {
            alert('Reply submitted: ' + replyText);
        }
        
        $(this).closest('.reply-form').hide();
    });
});
